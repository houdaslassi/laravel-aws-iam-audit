<?php


require __DIR__ . '/vendor/autoload.php';

use IamAudit\Data\Finding;
use IamAudit\Enums\Severity;

$finding = new Finding(
    checkId: 'user-mfa',
    severity: Severity::High,
    resource: 'arn:aws:iam::123456789012:user/alice',
    message: 'User "alice" can sign in to the console without MFA.',
);

print_r($finding->toArray());

var_dump($finding->severity->isAtLeast(Severity::Medium)); // true
var_dump($finding->severity->isAtLeast(Severity::Critical)); // false
var_dump(Severity::tryFrom('banana')); // NULL

$severity = $finding->severity->value;

$color = match ($severity) {
    'high' => 'red',
    'medium' => 'orange',
    'low' => 'gray'
};

echo $color;

// Uncomment this line: it should crash, because Finding is readonly
// $finding->message = 'changed';
