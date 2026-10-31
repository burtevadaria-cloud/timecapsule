<?php

declare(strict_types=1);

namespace App;

use Aws\S3\S3Client;

class Storage
{
    private S3Client $client;
    private string $bucket;

    public function __construct(private readonly string $directory = '')
    {
        $this->bucket = $_ENV['S3_BUCKET'] ?? getenv('S3_BUCKET') ?: 'timecapsule-dev-uploads-burtevadaria';
        $region = $_ENV['AWS_REGION'] ?? getenv('AWS_REGION') ?: 'eu-central-1';

        $this->client = new S3Client([
            'version' => 'latest',
            'region' => $region,
        ]);
    }

    public function save(array $uploadedFile, string $extension): string
    {
        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $contentType = mime_content_type($uploadedFile['tmp_name']) ?: ($uploadedFile['type'] ?? 'application/octet-stream');

        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => 'uploads/' . $storedName,
            'SourceFile' => $uploadedFile['tmp_name'],
            'ContentType' => $contentType,
        ]);

        return $storedName;
    }

    public function delete(?string $storedName): void
    {
        if ($storedName === null || $storedName === '') {
            return;
        }

        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => 'uploads/' . basename($storedName),
        ]);
    }

    public function stream(string $storedName, string $mime, string $downloadName, string $disposition): void
    {
        $asciiName = preg_replace('/[^A-Za-z0-9._ -]/', '_', $downloadName);
        $contentDisposition = sprintf(
            '%s; filename="%s"; filename*=UTF-8\'\'%s',
            $disposition,
            $asciiName,
            rawurlencode($downloadName)
        );

        $cmd = $this->client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => 'uploads/' . basename($storedName),
            'ResponseContentType' => $mime,
            'ResponseContentDisposition' => $contentDisposition,
        ]);

        $request = $this->client->createPresignedRequest($cmd, '+5 minutes');

        header('Location: ' . (string) $request->getUri(), true, 302);
        exit;
    }
}
EOF

sudo sed -i 's/local disk (storage\/uploads)/timecapsule-dev-uploads-burtevadaria/g' /var/www/timecapsule/views/layout.php

grep -q "S3_BUCKET" /var/www/timecapsule/.env || sudo tee -a /var/www/timecapsule/.env > /dev/null << 'EOF'
S3_BUCKET=timecapsule-dev-uploads-burtevadaria
AWS_REGION=eu-central-1
EOF

grep -q "S3_BUCKET" /var/www/timecapsule/.env.example || sudo tee -a /var/www/timecapsule/.env.example > /dev/null << 'EOF'
S3_BUCKET=timecapsule-dev-uploads-burtevadaria
AWS_REGION=eu-central-1
EOF

sudo chown -R ec2-user:apache /var/www/timecapsule
composer require aws/aws-sdk-php
sudo systemctl restart php-fpm httpd