<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Enums\Priority;
use App\Models\Customer;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class QueueService
{
    public function waitingMinutes(Customer $customer, CarbonInterface $at): int
    {
        return max(0, $customer->arrival_at->diffInMinutes($at, false));
    }

    public function effectivePriority(Customer $customer, CarbonInterface $at): Priority
    {
        $minutes = $this->waitingMinutes($customer, $at);

        return match ($customer->original_priority) {
            Priority::EMERGENCY => Priority::EMERGENCY,
            Priority::PRIORITY => $minutes >= 45 ? Priority::EMERGENCY : Priority::PRIORITY,
            Priority::NORMAL => match (true) {
                $minutes >= 90 => Priority::EMERGENCY,
                $minutes >= 60 => Priority::PRIORITY,
                default => Priority::NORMAL,
            },
        };
    }

    /** @return Collection<int, Customer> */
    public function ordered(CarbonInterface $at): Collection
    {
        return Customer::query()
            ->where('status', CustomerStatus::Waiting->value)
            ->get()
            ->sort(function (Customer $a, Customer $b) use ($at): int {
                $priority = $this->effectivePriority($b, $at)->rank()
                    <=> $this->effectivePriority($a, $at)->rank();

                return $priority
                    ?: ($a->arrival_at <=> $b->arrival_at)
                    ?: ($a->id <=> $b->id);
            })->values();
    }

    public function present(Customer $customer, CarbonInterface $at, ?int $position = null): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'service' => $customer->service,
            'arrival_at' => $customer->arrival_at->toIso8601String(),
            'original_priority' => $customer->original_priority->value,
            'effective_priority' => $customer->status === CustomerStatus::Waiting
                ? $this->effectivePriority($customer, $at)->value : null,
            'waiting_minutes' => $customer->status === CustomerStatus::Waiting
                ? $this->waitingMinutes($customer, $at) : null,
            'status' => $customer->status->value,
            'position' => $position,
            'allowed_transitions' => array_map(
                fn (CustomerStatus $status) => $status->value,
                $customer->status->allowedTransitions(),
            ),
        ];
    }
}
