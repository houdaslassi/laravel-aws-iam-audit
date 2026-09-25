<?php

namespace IamAudit\Enums;

enum Severity: string
{
    case Critical = 'critical';
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function weight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }

    public function isAtLeast(self $other): bool
    {
        return $this->weight() >= $other->weight();
    }
}
