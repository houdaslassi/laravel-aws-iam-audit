<?php

namespace IamAudit\Contracts;

use IamAudit\Aws\CredentialReport;

interface Check
{
    public function run(CredentialReport $report): array;
}
