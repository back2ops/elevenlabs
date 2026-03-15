<?php

namespace Back2ops\ElevenLabs\Facades;

use Illuminate\Support\Facades\Facade;
use Back2ops\ElevenLabs\ElevenLabs as ElevenLabsService;

/**
 * @method static string  textToSpeech(string $text, ?string $voiceId = null, ?string $modelId = null, ?string $outputFormat = null, array $voiceSettings = [], ?string $languageCode = null)
 * @method static string  textToSpeechAndStore(string $text, string $path, ?string $disk = null, array $options = [])
 * @method static array   transcribeFile(string $filePath, ?string $modelId = null, ?string $language = null, bool $timestamps = true)
 * @method static array   transcribeBytes(string $bytes, string $filename = 'audio.mp3', ?string $modelId = null, ?string $language = null, bool $timestamps = true)
 * @method static string  transcribeToText(string $filePath, ?string $language = null)
 * @method static string  soundEffect(string $prompt, ?float $durationSeconds = null, float $promptInfluence = 0.3, bool $loopable = false, ?string $modelId = null, ?string $outputFormat = null)
 * @method static string  soundEffectAndStore(string $prompt, string $path, ?string $disk = null, array $options = [])
 * @method static array   voices()
 * @method static array   voice(string $voiceId)
 * @method static array   models()
 *
 * @see \Back2ops\ElevenLabs\ElevenLabs
 */
class ElevenLabs extends Facade
{
    /**
     * Get the registered name of the component.
     * This must match the alias bound in the service provider.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'elevenlabs';
    }

    /**
     * Return the underlying service instance for IDE support / mocking in tests.
     */
    public static function getFacadeRoot(): ElevenLabsService
    {
        return parent::getFacadeRoot();
    }
}
