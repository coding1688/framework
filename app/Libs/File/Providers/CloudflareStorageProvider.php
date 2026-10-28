<?php

namespace App\Libs\File\Providers;

use App\Models\Common\FileStorageAccount;
use Aws\CommandInterface;
use Aws\Middleware;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;

final class CloudflareStorageProvider extends S3StorageProvider
{
    public function customize(FilesystemAdapter $filesystem, FileStorageAccount $account): FilesystemAdapter
    {
        if ($filesystem instanceof AwsS3V3Adapter) {
            $filesystem->getClient()->getHandlerList()->appendInit(Middleware::mapCommand(function (CommandInterface $command): CommandInterface {
                unset($command['ACL']);

                return $command;
            }), 'r2_without_acl');
        }

        return $filesystem;
    }
}
