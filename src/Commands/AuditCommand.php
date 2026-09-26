<?php

namespace IamAudit\Commands;

use Aws\Exception\AwsException;
use Aws\Iam\IamClient;
use IamAudit\Auditor;
use IamAudit\Aws\CredentialReport;
use IamAudit\Checks\RootAccountCheck;
use IamAudit\Checks\UserMfaCheck;
use Illuminate\Console\Command;

class AuditCommand extends Command
{
    protected $signature = 'iam:audit';

    protected $description = 'Audit your AWS IAM account for security problems';

    public function handle(): int
    {
        $iam = new IamClient([
            'version' => 'latest',
            'region' => 'eu-west-1',
        ]);

        try {
            $report = CredentialReport::fetch($iam);
        } catch (AwsException $e) {
            $this->error('Could not talk to AWS: '.($e->getAwsErrorMessage() ?? $e->getMessage()));

            return self::FAILURE;
        }

        $auditor = new Auditor(
            new UserMfaCheck,
            new RootAccountCheck,
        );

        $findings = $auditor->run($report);

        if ($findings === []) {
            $this->info('No problems found!');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($findings as $finding) {
            $color = $finding->severity->color();

            $rows[] = [
                "<fg={$color}>".strtoupper($finding->severity->value).'</>',
                $finding->message,
                $finding->remediation,
            ];
        }

        $this->table(['Severity', 'Problem', 'How to fix'], $rows);

        return self::SUCCESS;
    }
}
