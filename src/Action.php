<?php

declare(strict_types=1);

namespace Kiboko\Component\Action\Flow\SFTP;

use Kiboko\Contract\Action\ActionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Action implements ActionInterface
{
    public function __construct(
        private readonly string $host,
        private readonly string $user,
        private readonly string $password,
        private readonly string $port,
        private readonly string $localFilePath,
        private readonly string $remoteFilePath,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function execute(): void
    {
        $connection = ssh2_connect($this->host, $this->port);
        if (!$connection) {
            $this->logger->error("Unable to connect to the server. Please check that you are using the correct host and port");
        }

        if (!ssh2_auth_password($connection, $this->user, $this->password)) {
            $this->logger->error("Unable to connect to the server. Please check your login information.");
        }

        $sftp = ssh2_sftp($connection);
        $remoteFile = "ssh2.sftp://{$sftp}{$this->remoteFilePath}";
        $localFile = fopen($this->localFilePath, 'r');
        $uploaded = file_put_contents($remoteFile, $localFile);
        fclose($localFile);

        if (!$uploaded) {
            $this->logger->error("Failed to upload file to the SFTP server.");
        }

        $this->logger->info("The file has been uploaded to the SFTP server.");
    }
}
