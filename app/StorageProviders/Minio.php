<?php

namespace App\StorageProviders;

use App\Models\Server;
use App\SSH\Storage\Storage;
use App\SSH\Storage\Minio as MinioStorage;
use Aws\S3\Exception\S3Exception;
use Illuminate\Support\Facades\Log;

class Minio extends S3AbstractStorageProvider
{
    private const DEFAULT_REGION = 'us-east-1';

    public function validationRules(): array
    {
        return [
            'key' => 'required|string',
            'secret' => 'required|string',
            'region' => 'required|string',
            'bucket' => 'required|string',
            'path' => 'required|string',
            'api_url' => 'required|string',
        ];
    }

    public function credentialData(array $input): array
    {
        return [
            'key' => $input['key'],
            'secret' => $input['secret'],
            'region' => $input['region'],
            'bucket' => $input['bucket'],
            'path' => $input['path'],
            'api_url' => $input['api_url'],
        ];
    }

    public function connect(): bool
    {
        try {
            $this->setBucketRegion($this->storageProvider->credentials['region']);
            $this->setApiUrl();
            $this->buildClientConfig();
            $this->getClient()->listBuckets();

            return true;
        } catch (S3Exception $e) {
            Log::error('Failed to connect to S3', ['exception' => $e]);

            return false;
        }
    }

    /**
     * Build the configuration array for the S3 client.
     * This method can be overridden by child classes to modify the configuration.
     */
    public function buildClientConfig(): array
    {
        $this->clientConfig = [
            'credentials' => [
                'key' => $this->storageProvider->credentials['key'],
                'secret' => $this->storageProvider->credentials['secret'],
            ],
            'region' => $this->getBucketRegion(),
            'version' => 'latest',
            'endpoint' => $this->getApiUrl(),
            'use_path_style_endpoint' => true,
        ];

        return $this->clientConfig;
    }

    public function ssh(Server $server): Storage
    {
        return new MinioStorage($server, $this->storageProvider);
    }

    public function setApiUrl(?string $region = null): void
    {
        $this->apiUrl = $this->storageProvider->credentials['api_url'];
    }

    public function delete(array $paths): void {}
}
