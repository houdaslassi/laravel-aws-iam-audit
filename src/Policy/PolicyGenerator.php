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
            $root = trim($disk['root'] ?? '', '/');

            if ($root === '') {
                // No root folder: the app can access all files in the bucket
                $path = '*';
            } else {
                // Root folder set: the app can only access files inside that folder
                $path = "{$root}/*";
            }

            // Rule 1: listing the bucket (NEW version, replaces the old one)
            $listStatement = [
                'Sid' => 'ListS3Bucket',
                'Effect' => 'Allow',
                'Action' => ['s3:ListBucket'],
                'Resource' => "arn:aws:s3:::{$bucket}",
            ];

            if ($root !== '') {
                // Root folder set: only allow listing inside that folder
                $listStatement['Condition'] = [
                    'StringLike' => [
                        's3:prefix' => [$root, "{$root}/*"],
                    ],
                ];
            }

            $statements[] = $listStatement;

            // Rule 2: reading, uploading, deleting files (UNCHANGED)

            $statements[] = [
                'Sid' => 'ManageS3Objects',
                'Effect' => 'Allow',
                'Action' => ['s3:GetObject', 's3:PutObject', 's3:DeleteObject'],
                'Resource' => "arn:aws:s3:::{$bucket}/{$path}",
            ];
        }

        return [
            'Version' => '2012-10-17',
            'Statement' => $statements,
        ];
    }
}
