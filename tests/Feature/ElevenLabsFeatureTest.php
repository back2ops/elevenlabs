<?php

namespace Back2ops\ElevenLabs\Tests\Feature;

use Mockery;
use Orchestra\Testbench\TestCase;
use Back2ops\ElevenLabs\ElevenLabs;
use Back2ops\ElevenLabs\ElevenLabsServiceProvider;
use Back2ops\ElevenLabs\Facades\ElevenLabs as ElevenLabsFacade;
use Back2ops\ElevenLabs\Http\ElevenLabsClient;

/**
 * Feature tests run inside a minimal Laravel application provided by Orchestra Testbench.
 * They verify that the service provider, container bindings, config, and Facade all work together.
 */
class ElevenLabsFeatureTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ElevenLabsServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'ElevenLabs' => ElevenLabsFacade::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Set up config values as if they were in .env
        $app['config']->set('elevenlabs.api_key', 'test-api-key');
        $app['config']->set('elevenlabs.default_voice_id', 'test-voice-id');
        $app['config']->set('elevenlabs.default_tts_model', 'eleven_multilingual_v2');
        $app['config']->set('elevenlabs.default_stt_model', 'scribe_v2');
        $app['config']->set('elevenlabs.default_sfx_model', 'eleven_text_to_sound_v2');
        $app['config']->set('elevenlabs.default_output_format', 'mp3_44100_128');
        $app['config']->set('elevenlabs.timeout', 60);
        $app['config']->set('elevenlabs.voice_settings', [
            'stability'        => 0.5,
            'similarity_boost' => 0.75,
            'style'            => 0.0,
            'use_speaker_boost' => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Container & Facade wiring
    // -------------------------------------------------------------------------

    public function test_service_is_resolvable_from_container(): void
    {
        $service = $this->app->make(ElevenLabs::class);

        $this->assertInstanceOf(ElevenLabs::class, $service);
    }

    public function test_service_resolves_as_singleton(): void
    {
        $a = $this->app->make(ElevenLabs::class);
        $b = $this->app->make(ElevenLabs::class);

        $this->assertSame($a, $b);
    }

    public function test_facade_resolves_to_service_class(): void
    {
        $service = ElevenLabsFacade::getFacadeRoot();

        $this->assertInstanceOf(ElevenLabs::class, $service);
    }

    // -------------------------------------------------------------------------
    // Config is loaded
    // -------------------------------------------------------------------------

    public function test_config_is_accessible(): void
    {
        $this->assertSame('test-api-key', config('elevenlabs.api_key'));
        $this->assertSame('test-voice-id', config('elevenlabs.default_voice_id'));
    }

    // -------------------------------------------------------------------------
    // TTS through the Facade (with mocked HTTP client)
    // -------------------------------------------------------------------------

    public function test_facade_text_to_speech_calls_client_correctly(): void
    {
        // Swap the real HTTP client for a mock in the container.
        $mockClient = Mockery::mock(ElevenLabsClient::class);
        $mockClient
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body, array $query) {
                return str_contains($endpoint, 'test-voice-id')
                    && $body['text'] === 'Hello from feature test'
                    && $query['output_format'] === 'mp3_44100_128';
            })
            ->andReturn('fake-audio');

        $this->app->instance(ElevenLabsClient::class, $mockClient);
        // Re-bind ElevenLabs so it gets the mocked client.
        $this->app->singleton(ElevenLabs::class, fn($app) => new ElevenLabs($mockClient));
        $this->app->alias(ElevenLabs::class, 'elevenlabs');

        $result = ElevenLabsFacade::textToSpeech('Hello from feature test');

        $this->assertSame('fake-audio', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
