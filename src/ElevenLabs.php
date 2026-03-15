<?php

namespace Back2ops\ElevenLabs;

use Illuminate\Support\Facades\Storage;
use Back2ops\ElevenLabs\Http\ElevenLabsClient;

/**
 * Main ElevenLabs service.
 *
 * This is the class you'll interact with directly (or through the Facade).
 * It wraps the three core ElevenLabs capabilities:
 *  - Text to Speech (TTS)
 *  - Speech to Text / Transcription (STT)
 *  - Sound Effects generation (SFX)
 *
 * Plus a helper to list available voices.
 */
class ElevenLabs
{
    public function __construct(
        private readonly ElevenLabsClient $client,
    ) {}

    // -------------------------------------------------------------------------
    // TEXT TO SPEECH
    // -------------------------------------------------------------------------

    /**
     * Convert text to speech and return the raw audio bytes.
     *
     * Usage:
     *   $audio = ElevenLabs::textToSpeech('Hello world!');
     *   Storage::put('audio/hello.mp3', $audio);
     *
     * @param  string       $text          The text to speak.
     * @param  string|null  $voiceId       Override the default voice.
     * @param  string|null  $modelId       Override the default TTS model.
     * @param  string|null  $outputFormat  Override the default output format.
     * @param  array        $voiceSettings Override stability, similarity_boost, etc.
     * @param  string|null  $languageCode  ISO 639-1 code e.g. 'en', 'es', 'fr'.
     * @return string                      Raw audio bytes.
     */
    public function textToSpeech(
        string  $text,
        ?string $voiceId = null,
        ?string $modelId = null,
        ?string $outputFormat = null,
        array   $voiceSettings = [],
        ?string $languageCode = null,
    ): string {
        $voiceId      = $voiceId      ?? config('elevenlabs.default_voice_id');
        $modelId      = $modelId      ?? config('elevenlabs.default_tts_model');
        $outputFormat = $outputFormat ?? config('elevenlabs.default_output_format');

        $body = [
            'text'     => $text,
            'model_id' => $modelId,
            'voice_settings' => array_merge(
                config('elevenlabs.voice_settings'),
                $voiceSettings,
            ),
        ];

        if ($languageCode !== null) {
            $body['language_code'] = $languageCode;
        }

        return $this->client->postJsonForAudio(
            endpoint: "text-to-speech/{$voiceId}",
            body: $body,
            query: ['output_format' => $outputFormat],
        );
    }

    /**
     * Convert text to speech and save it to a file via Laravel's filesystem.
     *
     * Usage:
     *   $path = ElevenLabs::textToSpeechAndStore('Hello!', 'audio/hello.mp3');
     *   // Returns 'audio/hello.mp3'
     *
     * @param  string       $text     The text to speak.
     * @param  string       $path     Storage path e.g. 'audio/greeting.mp3'.
     * @param  string|null  $disk     Laravel disk name. Defaults to config('elevenlabs.disk').
     * @param  array        $options  Same options as textToSpeech().
     * @return string                 The storage path where the file was saved.
     */
    public function textToSpeechAndStore(
        string  $text,
        string  $path,
        ?string $disk = null,
        array   $options = [],
    ): string {
        $audio = $this->textToSpeech(
            text: $text,
            voiceId: $options['voice_id'] ?? null,
            modelId: $options['model_id'] ?? null,
            outputFormat: $options['output_format'] ?? null,
            voiceSettings: $options['voice_settings'] ?? [],
            languageCode: $options['language_code'] ?? null,
        );

        $disk = $disk ?? config('elevenlabs.disk');

        Storage::disk($disk)->put($path, $audio);

        return $path;
    }

    // -------------------------------------------------------------------------
    // SPEECH TO TEXT
    // -------------------------------------------------------------------------

    /**
     * Transcribe an audio file and return the full API response array.
     *
     * The response includes:
     *   - 'text'   (string) Full transcript
     *   - 'words'  (array)  Word-level timestamps [ {text, start, end, type} ]
     *   - 'language_code' (string) Detected language e.g. 'en'
     *
     * Usage:
     *   $result = ElevenLabs::transcribeFile('/path/to/recording.mp3');
     *   echo $result['text'];
     *
     * @param  string       $filePath   Absolute path to the audio/video file.
     * @param  string|null  $modelId    Override the default STT model.
     * @param  string|null  $language   Hint the language (ISO 639-1) for better accuracy.
     * @param  bool         $timestamps Include word-level timestamps in the response.
     * @return array
     */
    public function transcribeFile(
        string  $filePath,
        ?string $modelId = null,
        ?string $language = null,
        bool    $timestamps = true,
    ): array {
        $modelId = $modelId ?? config('elevenlabs.default_stt_model');

        $multipart = [
            [
                'name'     => 'model_id',
                'contents' => $modelId,
            ],
            [
                'name'     => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
            [
                'name'     => 'timestamps_granularity',
                'contents' => $timestamps ? 'word' : 'none',
            ],
        ];

        if ($language !== null) {
            $multipart[] = [
                'name'     => 'language_code',
                'contents' => $language,
            ];
        }

        return $this->client->postMultipart('speech-to-text', $multipart);
    }

    /**
     * Transcribe audio from a string of raw bytes (e.g. from an uploaded file).
     *
     * Usage:
     *   $audio   = $request->file('recording')->get();
     *   $result  = ElevenLabs::transcribeBytes($audio, 'recording.webm');
     *   echo $result['text'];
     *
     * @param  string       $bytes      Raw audio bytes.
     * @param  string       $filename   Filename with extension so the API knows the format.
     * @param  string|null  $modelId    Override the default STT model.
     * @param  string|null  $language   Hint the language.
     * @param  bool         $timestamps Include word-level timestamps.
     * @return array
     */
    public function transcribeBytes(
        string  $bytes,
        string  $filename = 'audio.mp3',
        ?string $modelId = null,
        ?string $language = null,
        bool    $timestamps = true,
    ): array {
        $modelId = $modelId ?? config('elevenlabs.default_stt_model');

        $multipart = [
            [
                'name'     => 'model_id',
                'contents' => $modelId,
            ],
            [
                'name'     => 'file',
                'contents' => $bytes,
                'filename' => $filename,
            ],
            [
                'name'     => 'timestamps_granularity',
                'contents' => $timestamps ? 'word' : 'none',
            ],
        ];

        if ($language !== null) {
            $multipart[] = [
                'name'     => 'language_code',
                'contents' => $language,
            ];
        }

        return $this->client->postMultipart('speech-to-text', $multipart);
    }

    /**
     * Convenience method — transcribe and return only the plain text string.
     *
     * Usage:
     *   $text = ElevenLabs::transcribeToText('/tmp/recording.mp3');
     */
    public function transcribeToText(string $filePath, ?string $language = null): string
    {
        $result = $this->transcribeFile($filePath, language: $language, timestamps: false);

        return $result['text'] ?? '';
    }

    // -------------------------------------------------------------------------
    // SOUND EFFECTS
    // -------------------------------------------------------------------------

    /**
     * Generate a sound effect from a text description and return raw audio bytes.
     *
     * Usage:
     *   $sfx = ElevenLabs::soundEffect('a crackling campfire in the forest');
     *   Storage::put('sfx/campfire.mp3', $sfx);
     *
     * @param  string       $prompt           Describe the sound you want.
     * @param  float|null   $durationSeconds  Length in seconds (0.5–30). Null = auto.
     * @param  float        $promptInfluence  0.0–1.0. Higher = follows prompt more strictly.
     * @param  bool         $loopable         Generate a seamlessly looping sound.
     * @param  string|null  $modelId          Override the default SFX model.
     * @param  string|null  $outputFormat     Override the default output format.
     * @return string                         Raw audio bytes.
     */
    public function soundEffect(
        string  $prompt,
        ?float  $durationSeconds = null,
        float   $promptInfluence = 0.3,
        bool    $loopable = false,
        ?string $modelId = null,
        ?string $outputFormat = null,
    ): string {
        $modelId      = $modelId      ?? config('elevenlabs.default_sfx_model');
        $outputFormat = $outputFormat ?? config('elevenlabs.default_output_format');

        $body = [
            'text'              => $prompt,
            'prompt_influence'  => $promptInfluence,
            'model_id'          => $modelId,
        ];

        if ($durationSeconds !== null) {
            $body['duration_seconds'] = $durationSeconds;
        }

        // Looping is only supported on eleven_text_to_sound_v2
        if ($loopable) {
            $body['loopable'] = true;
        }

        return $this->client->postJsonForAudio(
            endpoint: 'sound-generation',
            body: $body,
            query: ['output_format' => $outputFormat],
        );
    }

    /**
     * Generate a sound effect and store it via Laravel's filesystem.
     *
     * Usage:
     *   $path = ElevenLabs::soundEffectAndStore('thunder crack', 'sfx/thunder.mp3');
     *
     * @param  string       $prompt   Sound description.
     * @param  string       $path     Storage path.
     * @param  string|null  $disk     Laravel disk name.
     * @param  array        $options  Same options as soundEffect().
     * @return string                 Storage path.
     */
    public function soundEffectAndStore(
        string  $prompt,
        string  $path,
        ?string $disk = null,
        array   $options = [],
    ): string {
        $audio = $this->soundEffect(
            prompt: $prompt,
            durationSeconds: $options['duration_seconds'] ?? null,
            promptInfluence: $options['prompt_influence'] ?? 0.3,
            loopable: $options['loopable'] ?? false,
            modelId: $options['model_id'] ?? null,
            outputFormat: $options['output_format'] ?? null,
        );

        $disk = $disk ?? config('elevenlabs.disk');

        Storage::disk($disk)->put($path, $audio);

        return $path;
    }

    // -------------------------------------------------------------------------
    // VOICES
    // -------------------------------------------------------------------------

    /**
     * List all voices available to your account.
     *
     * Returns an array of voice objects with keys:
     *   voice_id, name, category, labels, preview_url, settings
     *
     * Usage:
     *   $voices = ElevenLabs::voices();
     *   foreach ($voices as $voice) {
     *       echo $voice['voice_id'] . ' — ' . $voice['name'];
     *   }
     */
    public function voices(): array
    {
        $response = $this->client->getJson('voices');

        return $response['voices'] ?? [];
    }

    /**
     * Get details for a single voice by ID.
     *
     * @param  string  $voiceId
     * @return array
     */
    public function voice(string $voiceId): array
    {
        return $this->client->getJson("voices/{$voiceId}");
    }

    /**
     * List all available models.
     *
     * Useful to check which models support TTS, STT, etc.
     *
     * @return array
     */
    public function models(): array
    {
        return $this->client->getJson('models');
    }
}
