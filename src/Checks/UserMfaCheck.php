<?php

namespace IamAudit\Checks;

use IamAudit\Aws\CredentialReport;
use IamAudit\Contracts\Check;
use IamAudit\Data\Finding;
use IamAudit\Enums\Severity;

final class UserMfaCheck implements Check
{
    public function run(CredentialReport $report): array
    {
        $findings = [];

        foreach ($report->users() as $user) {
            if ($user['password_enabled'] === 'true' && $user['mfa_active'] === 'false') {
                $findings[] = new Finding(
                    checkId: 'user-mfa',
                    severity: Severity::High,
                    resource: $user['arn'],
                    message: "User {$user['user']} can log in to the console without MFA.",
                    remediation: 'Enable MFA for this user.',
                );
            }
        }

        return $findings;
    }
}
