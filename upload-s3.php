<?php

require 'vendor/autoload.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$path    = $_ENV['PATH_DEMOS'];
$files = scandir($path);
$files = array_diff(scandir($path), array('.', '..'));

$s3 = new S3Client([
    'region' => $_ENV['S3_REGION'],
    'version' => $_ENV['S3_VERSION'],
    'endpoint' => $_ENV['S3_ENDPOINT'],
    'credentials' => [
        'key' => $_ENV['S3_KEY'],
        'secret' => $_ENV['S3_SECRET']
    ]
]);

if(!$files) {
    die;
}

foreach($files as $file) {
    $filename = pathinfo("$path/$file")["filename"];

    $zip = new ZipArchive;
    if ($zip->open("$path/$filename.zip", ZipArchive::CREATE) === TRUE)
    {
        // Add files to the zip file
        $zip->addFile("$path/$file", "$file");
    
        // All files are added, so close the zip file.
        $zip->close();

        unlink("$path/$file");

        // Upload data.
        $result = $s3->putObject([
            'Bucket' => $_ENV['S3_BUCKET'],
            'Key'    => "$filename.zip",
            'ACL'    => 'public-read',
            'SourceFile' => "$path/$filename.zip"
        ]);
        
        unlink("$path/$filename.zip");
    }
}
