# Setting up AWS configurations

1. Set up AWS SDK access_key and secret_key.
   Create 'credentials' file in .aws directory in the root directory.
   It should look like this.
   [default]
   aws_access_key_id = 'XXXXX'
   aws_secret_access_key = 'XXXXX'
2. Create S3 buckets for video storage.

   - Sign in to the AWS Management Console and open the Amazon S3 console at https://console.aws.amazon.com/s3/.
   - On the Amazon S3 console, choose Create bucket.
   - In the Create bucket dialog box, type a bucket name. If you want to create separate input and output buckets, give the bucket an appropriate name that will help you identify it later.
   - Choose a Region for your bucket. Make sure that you create your Amazon S3 buckets and do your MediaConvert transcoding in the same Region.
   - Choose Create.
   - If you want to create separate buckets for your input files and output files, repeat steps 2 through step 5.

3. Set up IAM permissions and copy the ARN of the role.
   https://docs.aws.amazon.com/mediaconvert/latest/ug/iam-role.html
4. Create a MediaConvert job queue and keep the Queue ARN of the queue.
   https://us-east-2.console.aws.amazon.com/mediaconvert/home?region=us-east-2#/queues/list
5. Create job settings json on this page by creating a new job and use the JSON in the code.
   https://us-east-2.console.aws.amazon.com/mediaconvert/home?region=us-east-2#/jobs/list

Once you are done with everything above, replace these values with the newly created ones.
$fileInput, $fileOutput, $roleArn, $queueArn
