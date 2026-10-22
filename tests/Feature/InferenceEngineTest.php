<?php

namespace Tests\Feature;

use App\Services\InferenceEngine;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class InferenceEngineTest extends TestCase
{
    private array $patient = [
        'weight' => 80, 'size' => 170, 'age' => 40, 'gender' => 'H', 'physical_activity' => 2,
    ];

    #[Group('US-4.1/AC-1')] // the request carries the service's credential
    public function test_uses_ml_service_prediction(): void
    {
        config(['services.inference.url' => 'http://inference:8000', 'services.inference.token' => 't0k']);
        Http::fake([
            'inference:8000/predict' => Http::response([
                'rule_id' => 4, 'confidence' => 0.91, 'model_version' => '20260924',
            ]),
        ]);

        $result = app(InferenceEngine::class)->classify($this->patient);

        $this->assertSame(4, $result['id_rule']);
        $this->assertSame('ml', $result['inference_source']);
        $this->assertSame(0.91, $result['inference_confidence']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer t0k')
            && $request['gender'] === 'H');
    }

    #[Group('US-4.1/AC-4')]
    public function test_falls_back_to_rules_when_service_is_down(): void
    {
        config(['services.inference.url' => 'http://inference:8000']);
        Http::fake(fn () => throw new ConnectionException('down'));

        $result = app(InferenceEngine::class)->classify($this->patient);

        $this->assertSame(3, $result['id_rule']); // IMC 27.7
        $this->assertSame('rules', $result['inference_source']);
    }

    #[Group('US-4.1/AC-4')]
    public function test_falls_back_to_rules_on_invalid_response(): void
    {
        config(['services.inference.url' => 'http://inference:8000']);
        Http::fake(['*' => Http::response(['detail' => 'Model not trained'], 503)]);

        $this->assertSame('rules', app(InferenceEngine::class)->classify($this->patient)['inference_source']);
    }

    #[Group('US-4.1/AC-4')]
    public function test_falls_back_to_rules_when_the_token_is_rejected_or_missing(): void
    {
        config(['services.inference.url' => 'http://inference:8000', 'services.inference.token' => 'wrong']);

        foreach ([401 => 'Invalid token', 503 => 'Credencial del servicio no configurada'] as $status => $detail) {
            Http::fake(['*' => Http::response(['detail' => $detail], $status)]);

            $result = app(InferenceEngine::class)->classify($this->patient);

            $this->assertSame(3, $result['id_rule']); // IMC 27.7
            $this->assertSame('rules', $result['inference_source']);
        }
    }

    #[Group('US-4.1/AC-6')]
    public function test_uses_rules_when_service_not_configured(): void
    {
        config(['services.inference.url' => null]);
        Http::fake();

        $result = app(InferenceEngine::class)->classify($this->patient);

        $this->assertSame('rules', $result['inference_source']);
        Http::assertNothingSent();
    }

    public function test_imc_rules_have_no_gaps(): void
    {
        $this->assertSame(1, InferenceEngine::ruleForImc(18.45));
        $this->assertSame(2, InferenceEngine::ruleForImc(18.5));
        $this->assertSame(2, InferenceEngine::ruleForImc(24.95));
        $this->assertSame(3, InferenceEngine::ruleForImc(25));
        $this->assertSame(4, InferenceEngine::ruleForImc(30));
    }
}
