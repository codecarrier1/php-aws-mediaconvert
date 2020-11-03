<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\MediaConvert\MediaConvertClient;
use Aws\Sns\SnsClient;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Aws\Sns\Exception\InvalidSnsMessageException;

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index() {

		$protocol = 'https';
		$endpoint = 'https://e532bdd83126.ngrok.io/mediaconvert_status';
		$topicArn = 'arn:aws:sns:us-east-1:404711272547:MediaConvertTopic';
 
		$fileInput = "s3://mediaconvert-input-storage/Snow Slowly Falling Down.mp4";
		$fileOutput = "s3://mediaconvert-output-storage/";

		$roleArn = "arn:aws:iam::404711272547:role/MediaConvert_Default_Role";
		$queueArn = "arn:aws:mediaconvert:us-east-2:404711272547:queues/mediaconvert-queue-default";

		// Subscribe to Amazon SNS Topic

		$SnSclient = new SnsClient([
			'profile' => 'default',
			'region' => 'us-east-1',
			'version' => '2010-03-31'
		]);
		
		try {
				$result = $SnSclient->subscribe([
						'Protocol' => $protocol,
						'Endpoint' => $endpoint,
						'ReturnSubscriptionArn' => true,
						'TopicArn' => $topicArn,
				]);
				$subscription_token = $result->get('SubscriptionArn');
		} catch (AwsException $e) {
				// output error message if fails
				error_log($e->getMessage());
		} 

		// Creating AWS MediaConvert Jobs

		$client = new Aws\MediaConvert\MediaConvertClient([
			'version' => '2017-08-29',
			'region' => 'us-east-2'
		]);

		//retrieve endpoint
		try {

			$single_endpoint_url = $client->describeEndpoints([])['Endpoints'][0]['Url'];

			print("Your endpoint is " . $single_endpoint_url);

			//Create an AWSMediaConvert client object with the endpoint URL that you retrieved => 
			$mediaConvertClient = new MediaConvertClient([
					'version' => '2017-08-29',
					'region' => 'us-east-2',
					'endpoint' => $single_endpoint_url
			]);

			$jobSetting = [
					"TimecodeConfig" => [
						"Source" => "ZEROBASED"
					],
					"OutputGroups" => [
						[
							"Name" => "Apple HLS",
							"Outputs" => [
								[
									"Preset" => "System-Avc_16x9_1080p_29_97fps_8500kbps",
									"NameModifier" => "nameModifier"
								]
							],
							"OutputGroupSettings" => [
								"Type" => "HLS_GROUP_SETTINGS",
								"HlsGroupSettings" => [
									"ManifestDurationFormat" => "INTEGER",
									"SegmentLength" => 10,
									"TimedMetadataId3Period" => 10,
									"CaptionLanguageSetting" => "OMIT",
									"Destination" => $fileOutput,
									"TimedMetadataId3Frame" => "PRIV",
									"CodecSpecification" => "RFC_4281",
									"OutputSelection" => "MANIFESTS_AND_SEGMENTS",
									"ProgramDateTimePeriod" => 600,
									"MinSegmentLength" => 0,
									"MinFinalSegmentLength" => 0,
									"DirectoryStructure" => "SINGLE_DIRECTORY",
									"ProgramDateTime" => "EXCLUDE",
									"SegmentControl" => "SEGMENTED_FILES",
									"ManifestCompression" => "NONE",
									"ClientCache" => "ENABLED",
									"AudioOnlyHeader" => "INCLUDE",
									"StreamInfResolution" => "INCLUDE"
								]
							]
						]
					],
					"AdAvailOffset" => 0,
					"Inputs" => [
						[
							"AudioSelectors" => [
								"Audio Selector 1" => [
									"Offset" => 0,
									"DefaultSelection" => "DEFAULT",
									"ProgramSelection" => 1
								]
							],
							"VideoSelector" => [
								"ColorSpace" => "FOLLOW",
								"Rotate" => "DEGREE_0",
								"AlphaBehavior" => "DISCARD"
							],
							"FilterEnable" => "AUTO",
							"PsiControl" => "USE_PSI",
							"FilterStrength" => 0,
							"DeblockFilter" => "DISABLED",
							"DenoiseFilter" => "DISABLED",
							"InputScanType" => "AUTO",
							"TimecodeSource" => "ZEROBASED",
							"FileInput" => $fileInput
						]
					]
			];

			$result = $mediaConvertClient->createJob([
        "Role" => $roleArn,
        "Settings" => $jobSetting, //JobSettings structure
        "Queue" => $queueArn,
        "UserMetadata" => [
            "Customer" => "Amazon"
        ],
			]);

		} catch (AwsException $e) {
			// output error message if fails
			echo $e->getMessage();
			echo "\n";
		}
	}

	public function mediaconvert_status() {

		// Instantiate the Message and Validator
		$message = Message::fromRawPostData();
		$validator = new MessageValidator();

		// Validate the message and log errors if invalid.
		try {
			$validator->validate($message);
		} catch (InvalidSnsMessageException $e) {
			// Pretend we're not here if the message is invalid.
			http_response_code(404);
			error_log('SNS Message Validation Error: ' . $e->getMessage());
			die();
		}

		// Check the type of the message and handle the subscription.
		if ($message['Type'] === 'SubscriptionConfirmation') {
			// Confirm the subscription by sending a GET request to the SubscribeURL
			file_get_contents($message['SubscribeURL']);
		}

		if ($message['Type'] === 'Notification') {
			// Do whatever you want with the message body and data.
			echo $message['MessageId'] . ': ' . $message['Message'] . "\n";
	 	}
	}
}
