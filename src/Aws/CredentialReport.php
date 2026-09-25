<?php

namespace IamAudit\Aws;

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
}
