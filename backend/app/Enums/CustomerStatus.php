<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Waiting = 'Waiting';
    case BeingServed = 'Being Served';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Waiting => [self::BeingServed, self::Cancelled],
            self::BeingServed => [self::Completed, self::Waiting],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
