<?php

namespace IamAudit\Aws;

use Aws\Iam\IamClient;

final class CredentialReport
{
    public function __construct(private array $rows) {}

    public static function fromCsv(string $csv): self
    {
        $lines = explode("\n", trim($csv));
        $headers = str_getcsv(array_shift($lines), ',', '"', '');

        $rows = [];

        foreach ($lines as $line) {
            $values = str_getcsv($line, ',', '"', '');
            $rows[] = array_combine($headers, $values);
        }

        return new self($rows);
    }

    public function root(): ?array
    {
        foreach ($this->rows as $row) {
            if ($row['user'] === '<root_account>') {
                return $row;
            }
        }

        return null;
    }

    public function users(): array
    {
        return array_filter($this->rows, fn ($row) => $row['user'] !== '<root_account>');
    }


    public static function fetch(IamClient $iam): self
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $result = $iam->generateCredentialReport();

            if ($result['State'] === 'COMPLETE') {
                $content = $iam->getCredentialReport()['Content'];

                return self::fromCsv($content);
            }

            sleep(2);
        }

        throw new \RuntimeException('The credential report is not ready yet, try again.');
    }
}
