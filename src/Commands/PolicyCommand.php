<?php

namespace IamAudit\Commands;

use IamAudit\Policy\PolicyGenerator;
use Illuminate\Console\Command;

class PolicyCommand extends Command
{
    protected $signature = 'iam:policy';

    protected $description = 'Generate the minimal IAM policy this Laravel app needs';

    public function handle(): int
    {
        $disks = config('filesystems.disks');

        $policy = (new PolicyGenerator)->generate($disks);

        if ($policy['Statement'] === []) {
            $this->warn('No S3 bucket found in config/filesystems.php.');
            $this->line('Set AWS_BUCKET in your .env, or add a disk with the s3 driver.');

            return self::SUCCESS;
        }

        $this->line(json_encode($policy, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
