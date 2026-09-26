<?php

namespace IamAudit\Checks;

use IamAudit\Aws\CredentialReport;
use IamAudit\Contracts\Check;
use IamAudit\Data\Finding;
use IamAudit\Enums\Severity;

final class RootAccountCheck implements Check
{
    public function run(CredentialReport $report): array
    {
        $root = $report->root();

        if ($root === null) {
            return [];
        }

        if ($root['mfa_active'] === 'false') {
            return [
                new Finding(
                    checkId: 'root-mfa',
                    severity: Severity::Critical,
                    resource: $root['arn'],
                    message: 'The root account has no MFA.',
                    remediation: 'Enable MFA on the root account immediately.',
                ),
            ];
        }

        return [];
    }
}
