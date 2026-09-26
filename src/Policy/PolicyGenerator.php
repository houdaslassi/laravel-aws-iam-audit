<?php

namespace IamAudit\Policy;

final class PolicyGenerator
{
    public function generate(array $disks): array
    {
        $statements = [];

        foreach ($disks as $name => $disk) {
            if ($disk['driver'] !== 's3' || empty($disk['bucket'])) {
                continue;
            }

            $bucket = $disk['bucket'];

            $statements[] = [
                'Sid' => 'ListS3Bucket',
                'Effect' => 'Allow',
                'Action' => ['s3:ListBucket'],
                'Resource' => "arn:aws:s3:::{$bucket}",
            ];

            $statements[] = [
                'Sid' => 'ManageS3Objects',
                'Effect' => 'Allow',
                'Action' => ['s3:GetObject', 's3:PutObject', 's3:DeleteObject'],
                'Resource' => "arn:aws:s3:::{$bucket}/*",
            ];
        }

        return [
            'Version' => '2012-10-17',
            'Statement' => $statements,
        ];
    }
}
