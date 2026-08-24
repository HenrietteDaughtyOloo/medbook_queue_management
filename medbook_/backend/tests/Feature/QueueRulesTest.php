<?php

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use App\Enums\Priority;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueRulesTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $at;

    protected function setUp(): void
    {
        parent::setUp();
        $this->at = CarbonImmutable::parse('2026-08-20 11:15:00');
    }

    private function customer(string $name, string $arrival, Priority $priority, CustomerStatus $status = CustomerStatus::Waiting): Customer
    {
        return Customer::factory()->create([
            'name' => $name,
            'arrival_at' => CarbonImmutable::parse("2026-08-20 {$arrival}"),
            'original_priority' => $priority,
            'status' => $status,
        ]);
    }

    private function queueUrl(): string
    {
        return '/api/queue?'.http_build_query(['at' => $this->at->toIso8601String()]);
    }

    public function test_supplied_scenario_is_calculated_in_the_correct_order(): void
    {
        $this->customer('Peter', '09:45', Priority::NORMAL);
        $this->customer('Mary', '11:01', Priority::EMERGENCY);
        $this->customer('John', '11:04', Priority::EMERGENCY);
        $this->customer('Susan', '10:25', Priority::PRIORITY);
        $this->customer('Daniel', '10:50', Priority::NORMAL);

        $response = $this->getJson($this->queueUrl())->assertOk();

        $response->assertJsonPath('next_customer.name', 'Peter')
            ->assertJsonPath('queue.0.effective_priority', 'Emergency')
            ->assertJsonPath('queue.1.effective_priority', 'Emergency')
            ->assertJsonPath('queue.4.effective_priority', 'Normal');
        $this->assertSame(['Peter', 'Susan', 'Mary', 'John', 'Daniel'],
            array_column($response->json('queue'), 'name'));
    }

    public function test_escalation_thresholds_are_inclusive(): void
    {
        $this->customer('Normal 60', '10:15', Priority::NORMAL);
        $this->customer('Normal 90', '09:45', Priority::NORMAL);
        $this->customer('Priority 45', '10:30', Priority::PRIORITY);

        $queue = collect($this->getJson($this->queueUrl())->json('queue'))->keyBy('name');

        $this->assertSame('Priority', $queue['Normal 60']['effective_priority']);
        $this->assertSame('Emergency', $queue['Normal 90']['effective_priority']);
        $this->assertSame('Emergency', $queue['Priority 45']['effective_priority']);
    }

    public function test_identical_priority_and_arrival_use_creation_order(): void
    {
        $first = $this->customer('First created', '11:00', Priority::EMERGENCY);
        $second = $this->customer('Second created', '11:00', Priority::EMERGENCY);
        $ids = array_column($this->getJson($this->queueUrl())->json('queue'), 'id');
        $this->assertSame([$first->id, $second->id], $ids);
    }

    public function test_non_waiting_customers_are_excluded(): void
    {
        $this->customer('Waiting', '11:00', Priority::NORMAL);
        $this->customer('Completed', '10:00', Priority::EMERGENCY, CustomerStatus::Completed);
        $this->getJson($this->queueUrl())
            ->assertJsonCount(1, 'queue')->assertJsonPath('queue.0.name', 'Waiting');
    }

    public function test_invalid_transition_is_rejected_without_changing_data(): void
    {
        $customer = $this->customer('Done', '10:00', Priority::NORMAL, CustomerStatus::Completed);
        $this->patchJson("/api/customers/{$customer->id}/status", ['status' => 'Waiting'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'status' => 'Completed']);
    }

    public function test_only_one_customer_can_be_served(): void
    {
        $active = $this->customer('Active', '10:00', Priority::NORMAL, CustomerStatus::BeingServed);
        $waiting = $this->customer('Waiting', '10:10', Priority::NORMAL);
        $this->patchJson("/api/customers/{$waiting->id}/status", ['status' => 'Being Served'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertDatabaseHas('customers', ['id' => $waiting->id, 'status' => 'Waiting']);
        $this->assertDatabaseHas('customers', ['id' => $active->id, 'status' => 'Being Served']);
    }

    public function test_returning_to_waiting_does_not_reset_arrival_time(): void
    {
        $customer = $this->customer('Return', '09:45', Priority::NORMAL, CustomerStatus::BeingServed);
        $this->patchJson("/api/customers/{$customer->id}/status", ['status' => 'Waiting'])->assertOk();
        $this->getJson($this->queueUrl())
            ->assertJsonPath('queue.0.waiting_minutes', 90)
            ->assertJsonPath('queue.0.effective_priority', 'Emergency');
        $this->assertTrue($customer->arrival_at->equalTo($customer->refresh()->arrival_at));
    }

    public function test_customer_can_be_added_with_an_iso_timestamp(): void
    {
        CarbonImmutable::setTestNow($this->at);

        $this->postJson('/api/customers', [
            'name' => 'Amina',
            'service' => 'Consultation',
            'arrival_at' => '2026-08-20T08:00:00.000Z',
            'original_priority' => 'Normal',
        ])->assertCreated()
            ->assertJsonPath('message', 'Customer added to the queue.')
            ->assertJsonPath('customer.status', 'Waiting');

        $this->assertDatabaseHas('customers', [
            'name' => 'Amina',
            'service' => 'Consultation',
            'status' => 'Waiting',
        ]);
    }
}

