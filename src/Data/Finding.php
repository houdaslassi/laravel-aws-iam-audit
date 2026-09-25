<?php


namespace IamAudit\Data;

use IamAudit\Enums\Severity;

final readonly class Finding
{
    public function __construct(
        public string   $checkId,
        public Severity $severity,
        public string   $resource,
        public string   $message,
        public string   $remediation = '',
    ) {
    }

    public function toArray(): array
    {
        return [
            'check' => $this->checkId,
            'severity' => $this->severity->value,
            'resource' => $this->resource,
            'message' => $this->message,
            'remediation' => $this->remediation,
        ];
    }
}
