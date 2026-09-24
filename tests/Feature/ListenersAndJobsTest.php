<?php

namespace Tests\Feature;

use App\Events\DiagnosisCategoryConfirmed;
use App\Events\PatientRegistered;
use App\Events\UserAccountCreated;
use App\Jobs\RetrainInferenceModel;
use App\Listeners\RecordAuditTrail;
use App\Listeners\ScheduleModelRetraining;
use App\Listeners\SendAccountCreatedNotification;
use App\Models\Diagnosis;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\AccountCreatedNotification;
use App\Services\InferenceEngine;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ListenersAndJobsTest extends TestCase
{
    use RefreshDatabase;

    private function confirmedEvent(): DiagnosisCategoryConfirmed
    {
        return new DiagnosisCategoryConfirmed(new Diagnosis(['id_rule' => 4]), User::factory()->make(), 3, 'ml');
    }

    public function test_correction_schedules_a_delayed_retraining(): void
    {
        Queue::fake();
        config(['services.inference.url' => 'http://inference:8000']);

        (new ScheduleModelRetraining)->handle($this->confirmedEvent());

        Queue::assertPushed(RetrainInferenceModel::class, fn ($job) => $job->reason === 'doctor-confirmation'
            && $job->delay !== null);
    }

    public function test_no_retraining_without_inference_service(): void
    {
        Queue::fake();
        config(['services.inference.url' => null]);

        (new ScheduleModelRetraining)->handle($this->confirmedEvent());

        Queue::assertNothingPushed();
    }

    public function test_retraining_job_calls_the_ml_service(): void
    {
        config(['services.inference.url' => 'http://inference:8000']);
        Http::fake(['inference:8000/train' => Http::response([
            'version' => '20260925', 'samples_synthetic' => 20000, 'samples_manual' => 7, 'accuracy' => 0.99,
        ])]);

        (new RetrainInferenceModel('test'))->handle(app(InferenceEngine::class));

        Http::assertSent(fn ($request) => $request->url() === 'http://inference:8000/train');
    }

    public function test_retraining_job_fails_on_service_error_so_it_is_retried(): void
    {
        config(['services.inference.url' => 'http://inference:8000']);
        Http::fake(['*' => Http::response('boom', 500)]);

        $this->expectException(RequestException::class);
        (new RetrainInferenceModel('test'))->handle(app(InferenceEngine::class));
    }

    public function test_retraining_job_is_queued_unique_and_retried(): void
    {
        $job = new RetrainInferenceModel;

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertInstanceOf(ShouldBeUnique::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame([60, 300], $job->backoff());
    }

    public function test_retraining_is_scheduled_nightly(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->description ?? '', RetrainInferenceModel::class));

        $this->assertNotNull($event);
        $this->assertSame('0 3 * * *', $event->expression);
    }

    public function test_new_account_gets_a_queued_notification_without_password(): void
    {
        Notification::fake();
        $this->assertInstanceOf(ShouldQueue::class, new SendAccountCreatedNotification);

        $user = User::factory()->create();
        (new SendAccountCreatedNotification)->handle(new UserAccountCreated($user, User::factory()->admin()->create()));

        Notification::assertSentTo($user, AccountCreatedNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $text = implode(' ', array_merge($mail->introLines, $mail->outroLines));

            return str_contains($text, $user->email) && ! str_contains($text, 'password');
        });
    }

    public function test_audit_trail_records_actor_and_record(): void
    {
        $doctor = User::factory()->create();
        $patient = new Patient;
        $patient->id = 42;

        $channel = Mockery::mock();
        $channel->shouldReceive('info')->once()->with('patient.registered', Mockery::on(
            fn ($context) => $context['actor_id'] === $doctor->id
                && $context['actor_role'] === 'Doctor'
                && $context['patient_id'] === 42
        ));
        Log::shouldReceive('channel')->with('audit')->andReturn($channel);

        (new RecordAuditTrail)->handlePatientRegistered(new PatientRegistered($patient, $doctor));
    }
}
