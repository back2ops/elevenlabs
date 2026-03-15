<?php

namespace Back2ops\ElevenLabs\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use Back2ops\ElevenLabs\ElevenLabs;
use Back2ops\ElevenLabs\Http\ElevenLabsClient;

class ElevenLabsTest extends TestCase
{
    private ElevenLabsClient $client;
    private ElevenLabs $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the HTTP client so tests never hit the real API.
        $this->client  = Mockery::mock(ElevenLabsClient::class);
        $this->service = new ElevenLabs($this->client);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // TTS
    // -------------------------------------------------------------------------

    public function test_text_to_speech_returns_audio_bytes(): void
    {
        $fakeAudio = 'FAKE_AUDIO_BYTES';

        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body, array $query) {
                return str_contains($endpoint, 'text-to-speech')
                    && $body['text'] === 'Hello world'
                    && isset($query['output_format']);
            })
            ->andReturn($fakeAudio);

        $result = $this->service->textToSpeech('Hello world');

        $this->assertSame($fakeAudio, $result);
    }

    public function test_text_to_speech_uses_custom_voice_id(): void
    {
        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(fn($endpoint) => str_contains($endpoint, 'custom-voice-id'))
            ->andReturn('audio');

        $this->service->textToSpeech('Hello', voiceId: 'custom-voice-id');
    }

    public function test_text_to_speech_includes_language_code_when_provided(): void
    {
        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body) {
                return $body['language_code'] === 'es';
            })
            ->andReturn('audio');

        $this->service->textToSpeech('Hola', languageCode: 'es');
    }

    public function test_text_to_speech_omits_language_code_when_null(): void
    {
        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body) {
                return !array_key_exists('language_code', $body);
            })
            ->andReturn('audio');

        $this->service->textToSpeech('Hello');
    }

    // -------------------------------------------------------------------------
    // STT
    // -------------------------------------------------------------------------

    public function test_transcribe_file_returns_api_response(): void
    {
        $fakeResponse = [
            'text'          => 'Hello world from the recording.',
            'language_code' => 'en',
            'words'         => [],
        ];

        $this->client
            ->shouldReceive('postMultipart')
            ->once()
            ->with('speech-to-text', Mockery::type('array'))
            ->andReturn($fakeResponse);

        // Use a real temp file so fopen() works inside the service.
        $tmpFile = tempnam(sys_get_temp_dir(), 'elabs_test_') . '.mp3';
        file_put_contents($tmpFile, 'fake-audio-content');

        $result = $this->service->transcribeFile($tmpFile);

        $this->assertSame('Hello world from the recording.', $result['text']);

        unlink($tmpFile);
    }

    public function test_transcribe_to_text_returns_plain_string(): void
    {
        $this->client
            ->shouldReceive('postMultipart')
            ->once()
            ->andReturn(['text' => 'Simple transcript.', 'words' => []]);

        $tmpFile = tempnam(sys_get_temp_dir(), 'elabs_test_') . '.mp3';
        file_put_contents($tmpFile, 'fake-audio');

        $text = $this->service->transcribeToText($tmpFile);

        $this->assertSame('Simple transcript.', $text);

        unlink($tmpFile);
    }

    // -------------------------------------------------------------------------
    // SFX
    // -------------------------------------------------------------------------

    public function test_sound_effect_returns_audio_bytes(): void
    {
        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body) {
                return $endpoint === 'sound-generation'
                    && $body['text'] === 'thunder crack';
            })
            ->andReturn('sfx-audio-bytes');

        $result = $this->service->soundEffect('thunder crack');

        $this->assertSame('sfx-audio-bytes', $result);
    }

    public function test_sound_effect_with_duration_includes_it_in_body(): void
    {
        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body) {
                return $body['duration_seconds'] === 5.0;
            })
            ->andReturn('audio');

        $this->service->soundEffect('ocean waves', durationSeconds: 5.0);
    }

    public function test_sound_effect_with_loopable_flag(): void
    {
        $this->client
            ->shouldReceive('postJsonForAudio')
            ->once()
            ->withArgs(function (string $endpoint, array $body) {
                return $body['loopable'] === true;
            })
            ->andReturn('audio');

        $this->service->soundEffect('background hum', loopable: true);
    }

    // -------------------------------------------------------------------------
    // VOICES
    // -------------------------------------------------------------------------

    public function test_voices_returns_voices_array(): void
    {
        $this->client
            ->shouldReceive('getJson')
            ->once()
            ->with('voices')
            ->andReturn([
                'voices' => [
                    ['voice_id' => 'abc123', 'name' => 'Rachel'],
                    ['voice_id' => 'def456', 'name' => 'Domi'],
                ],
            ]);

        $voices = $this->service->voices();

        $this->assertCount(2, $voices);
        $this->assertSame('Rachel', $voices[0]['name']);
    }
}
