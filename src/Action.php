<?php

declare(strict_types=1);

namespace Kiboko\Component\Action\Flow\SFTP;

use Kiboko\Contract\Action\ActionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class Action implements ActionInterface
{
    public function __construct(
        private string $host,
        private string $user,
        private string $password,
        private int $port,
        private string $localFilePath,
        private string $remoteFilePath,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function execute(): void
    {
        $connection = ssh2_connect($this->host, $this->port);
        if (!$connection) {
            $this->logger->error('Unable to connect to the server. Please check that you are using the correct host and port and try again.');

            return;
        }

        if (!ssh2_auth_password($connection, $this->user, $this->password)) {
            $this->logger->error('Unable to connect to the server. Please check your login information and try again.');

            return;
        }

        $sftp = ssh2_sftp($connection);
        $remoteFile = "ssh2.sftp://{$sftp}{$this->remoteFilePath}";

        $localFile = fopen($this->localFilePath, 'r');
        if (false === $localFile) {
            $this->logger->error('Impossible to open the local file. Please check if the path is correct and try again.');

            return;
        }
        $uploaded = file_put_contents($remoteFile, $localFile);
        fclose($localFile);

        if (!$uploaded) {
            $this->logger->error('Failed to upload file to the SFTP server.');

            return;
        }

        $this->logger->info('The file has been uploaded to the SFTP server.');
    }
}
