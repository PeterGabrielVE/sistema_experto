<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Classifies a patient into a nutritional category (rules.id).
 *
 * Uses the Python ML service when configured and reachable, and falls back
 * to the original IMC expert rules otherwise.
 */
class InferenceEngine
{
    public const CATEGORIES = [
        1 => 'Bajo peso',
        2 => 'Normal',
        3 => 'Sobrepeso',
        4 => 'Obesidad',
    ];

    /**
     * @param  array{weight: float, size: float, age: int, gender: string, physical_activity: int}  $patient
     * @return array{id_rule: int, inference_source: string, inference_confidence: float|null, model_version: string|null}
     */
    public function classify(array $patient): array
    {
        if (config('services.inference.url')) {
            try {
                $response = $this->client()->post('/predict', [
                    'weight' => (float) $patient['weight'],
                    'size' => (float) $patient['size'],
                    'age' => (int) $patient['age'],
                    'gender' => $patient['gender'],
                    'physical_activity' => (int) $patient['physical_activity'],
                ]);

                if ($response->successful() && isset(self::CATEGORIES[$response->json('rule_id')])) {
                    return [
                        'id_rule' => $response->json('rule_id'),
                        'inference_source' => 'ml',
                        'inference_confidence' => $response->json('confidence'),
                        'model_version' => $response->json('model_version'),
                    ];
                }

                Log::warning('Inference service returned an invalid response', ['status' => $response->status()]);
            } catch (ConnectionException $e) {
                Log::warning('Inference service unreachable, using expert rules', ['error' => $e->getMessage()]);
            }
        }

        $weight = (float) $patient['weight'];
        $size = (float) $patient['size'] / 100;

        return [
            'id_rule' => self::ruleForImc($size > 0 ? $weight / ($size * $size) : 0),
            'inference_source' => 'rules',
            'inference_confidence' => null,
            'model_version' => null,
        ];
    }

    /**
     * Retrains the model with synthetic data plus doctor-confirmed diagnoses.
     */
    public function train(): array
    {
        if (! config('services.inference.url')) {
            throw new RuntimeException('INFERENCE_URL is not configured.');
        }

        return $this->client()->timeout(300)->post('/train')->throw()->json();
    }

    /**
     * The original expert rules.
     */
    public static function ruleForImc(float $imc): int
    {
        return match (true) {
            $imc < 18.5 => 1,
            $imc < 25 => 2,
            $imc < 30 => 3,
            default => 4,
        };
    }

    private function client()
    {
        return Http::baseUrl(config('services.inference.url'))
            ->withToken((string) config('services.inference.token'))
            ->timeout((int) config('services.inference.timeout'))
            ->acceptJson();
    }
}
