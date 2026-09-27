<?php

use IamAudit\Policy\PolicyGenerator;

it('ignores disks that are not s3', function () {
    $disks = [
        'local' => ['driver' => 'local'],
    ];

    $policy = (new PolicyGenerator)->generate($disks);

    expect($policy['Statement'])->toBe([]);
});
