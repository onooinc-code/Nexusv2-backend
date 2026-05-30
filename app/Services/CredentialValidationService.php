<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Collection;

/**
 * CredentialValidationService
 *
 * Validates credentials by testing connectivity to external services.
 */
class CredentialValidationService
{
    /**
     * Test Pinecone API key connectivity.
     */
    public function testPinecone(string $apiKey): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(5)->get('https://api.pinecone.io/indexes');

            return [
                'valid' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Pinecone API key is valid' : 'Invalid API key',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test Neo4j credentials connectivity.
     */
    public function testNeo4j(string $host, string $username, string $password): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($username, $password)
                ->timeout(5)
                ->get("http://{$host}:7474/db/neo4j/tx");

            return [
                'valid' => in_array($response->status(), [200, 201]),
                'status' => $response->status(),
                'message' => in_array($response->status(), [200, 201]) ? 'Neo4j connection is valid' : 'Connection failed',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test WAHA API key connectivity.
     */
    public function testWaha(string $apiUrl, string $apiToken): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiToken}",
            ])->timeout(5)->get("{$apiUrl}/health");

            return [
                'valid' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'WAHA API is valid' : 'API returned error',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test OpenAI API key.
     */
    public function testOpenAi(string $apiKey): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(5)->get('https://api.openai.com/v1/models');

            return [
                'valid' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'OpenAI API key is valid' : 'Invalid API key',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test Anthropic API key.
     */
    public function testAnthropic(string $apiKey): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey,
            ])->timeout(5)->get('https://api.anthropic.com/v1/models');

            return [
                'valid' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Anthropic API key is valid' : 'Invalid API key',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test Gemini API key.
     */
    public function testGemini(string $apiKey): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

            return [
                'valid' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Gemini API key is valid' : 'Invalid API key',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Test Groq API key.
     */
    public function testGroq(string $apiKey): array
    {
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(5)->get('https://api.groq.com/openai/v1/models');

            return [
                'valid' => $response->successful(),
                'status' => $response->status(),
                'message' => $response->successful() ? 'Groq API key is valid' : 'Invalid API key',
            ];
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate all credentials from settings.
     */
    public function validateAllCredentials(): array
    {
        $results = [];

        // Get all integration settings
        $settings = Setting::byGroup('integrations')->get();

        foreach ($settings as $setting) {
            if ($setting->is_encrypted) {
                $value = (new CredentialEncryptionService())->decrypt($setting->value);
            } else {
                $value = $setting->value;
            }

            $result = $this->validateCredential($setting->key, $value);
            $results[$setting->key] = $result;
        }

        return [
            'timestamp' => now()->toIso8601String(),
            'results' => $results,
            'valid_count' => collect($results)->filter(fn ($r) => $r['valid'])->count(),
            'invalid_count' => collect($results)->filter(fn ($r) => !$r['valid'])->count(),
            'total' => count($results),
        ];
    }

    /**
     * Validate a single credential based on key.
     */
    public function validateCredential(string $key, string $value): array
    {
        if (str_contains($key, 'pinecone')) {
            return $this->testPinecone($value);
        }
        if (str_contains($key, 'neo4j')) {
            return ['message' => 'Neo4j validation requires multiple parameters', 'valid' => null];
        }
        if (str_contains($key, 'waha')) {
            return ['message' => 'WAHA validation requires URL parameter', 'valid' => null];
        }
        if (str_contains($key, 'openai')) {
            return $this->testOpenAi($value);
        }
        if (str_contains($key, 'anthropic')) {
            return $this->testAnthropic($value);
        }
        if (str_contains($key, 'gemini')) {
            return $this->testGemini($value);
        }
        if (str_contains($key, 'groq')) {
            return $this->testGroq($value);
        }

        return ['valid' => null, 'message' => 'Unknown credential type'];
    }
}
