<?php

namespace App\Http\Controllers;

use App\Enums\CustomerStatus;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerStatusRequest;
use App\Models\Customer;
use App\Services\QueueService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function __construct(private readonly QueueService $queue)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate(['at' => ['sometimes', 'date']]);
        $at = $request->filled('at') ? CarbonImmutable::parse($request->string('at')) : now()->toImmutable();
        $customers = $this->queue->ordered($at);
        $data = $customers->map(fn(Customer $customer, int $index) => $this->queue->present($customer, $at, $index + 1));
        $active = Customer::query()->where('status', CustomerStatus::BeingServed->value)->first();

        return response()->json([
            'calculated_at' => $at->toIso8601String(),
            'next_customer' => $data->first(),
            'active_customer' => $active ? $this->queue->present($active, $at) : null,
            'queue' => $data,
        ]);
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated() + ['status' => CustomerStatus::Waiting]);

        return response()->json([
            'message' => 'Customer added to the queue.',
            'customer' => $this->queue->present($customer, now()->toImmutable()),
        ], 201);
    }

    public function updateStatus(UpdateCustomerStatusRequest $request, Customer $customer): JsonResponse
    {
        $target = CustomerStatus::from($request->validated('status'));

        $customer = DB::transaction(function () use ($customer, $target): Customer {
            DB::table('queue_locks')->where('id', 1)->lockForUpdate()->first();
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            if (!$customer->status->canTransitionTo($target)) {
                throw ValidationException::withMessages([
                    'status' => sprintf(
                        'Cannot change status from %s to %s.',
                        $customer->status->value,
                        $target->value
                    )
                ]);
            }

            if (
                $target === CustomerStatus::BeingServed && Customer::query()
                    ->where('status', CustomerStatus::BeingServed->value)->whereKeyNot($customer->id)->exists()
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Another customer is already being served. Complete or return them to Waiting first.',
                ]);
            }

            $customer->update(['status' => $target]);

            return $customer->refresh();
        });

        return response()->json([
            'message' => "Customer status changed to {$target->value}.",
            'customer' => $this->queue->present($customer, now()->toImmutable()),
        ]);
    }
}