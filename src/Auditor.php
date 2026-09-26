<?php

namespace IamAudit;

use IamAudit\Aws\CredentialReport;
use IamAudit\Contracts\Check;

final class Auditor
{
    private array $checks;

    public function __construct(Check ...$checks)
    {
        $this->checks = $checks;
    }

    public function run(CredentialReport $report): array
    {
        $findings = [];

        foreach ($this->checks as $check) {
            foreach ($check->run($report) as $finding) {
                $findings[] = $finding;
            }
        }

        usort($findings, fn ($a, $b) => $b->severity->weight() <=> $a->severity->weight());

        return $findings;
    }
}
