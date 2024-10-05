<?php

namespace Tests\Unit\Commands;

use App\Jobs\RunDemandPredictionRequests as RunDemandPredictionRequestsJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ForecastDemandTest extends TestCase
{
    public function testCommandRunsWithoutArguments(): void
    {
        // Fake the queue
        Queue::fake();

        // Run the command like the schedule does (no arguments)
        $this->artisan('ml:forecast-demand')
            ->expectsOutput('RunPredictionRequestsJob dispatched successfully') // Check the console output
            ->assertExitCode(0);  // Ensure the command exits successfully

        // Assert that the job was dispatched
        Queue::assertPushed(RunDemandPredictionRequestsJob::class);
    }
}
