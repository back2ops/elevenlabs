<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ElevenLabs API Key
    |--------------------------------------------------------------------------
    |
    | Your ElevenLabs API key. Get yours at https://elevenlabs.io/app/settings/api-keys
    | Store this in your .env file as ELEVENLABS_API_KEY.
    |
    */

    'api_key' => env('ELEVENLABS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the ElevenLabs API. You generally won't need to change this.
    |
    */

    'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),

    /*
    |--------------------------------------------------------------------------
    | Default Voice ID
    |--------------------------------------------------------------------------
    |
    | The default voice to use for text-to-speech requests. You can find voice IDs
    | by calling ElevenLabs::voices() or browsing https://elevenlabs.io/app/voice-library
    |
    | Some popular built-in voices:
    |   Rachel  => 21m00Tcm4TlvDq8ikWAM
    |   Domi    => AZnzlk1XvdvUeBnXmlld
    |   Bella   => EXAVITQu4vr4xnSDxMaL
    |   Antoni  => ErXwobaYiN019PkySvjV
    |   Josh    => TxGEqnHWrfWFTfGW9XjX
    |
    */

    'default_voice_id' => env('ELEVENLABS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'),

    /*
    |--------------------------------------------------------------------------
    | Default TTS Model
    |--------------------------------------------------------------------------
    |
    | The model used for text-to-speech. Options:
    |   eleven_multilingual_v2  — Highest quality, 32 languages (default)
    |   eleven_flash_v2_5       — Ultra-low latency (~75ms), good for real-time
    |   eleven_turbo_v2_5       — Balanced speed and quality
    |
    */

    'default_tts_model' => env('ELEVENLABS_TTS_MODEL', 'eleven_multilingual_v2'),

    /*
    |--------------------------------------------------------------------------
    | Default STT Model
    |--------------------------------------------------------------------------
    |
    | The model used for speech-to-text transcription. Options:
    |   scribe_v2  — Highest accuracy (default)
    |   scribe_v1  — Legacy model
    |
    */

    'default_stt_model' => env('ELEVENLABS_STT_MODEL', 'scribe_v2'),

    /*
    |--------------------------------------------------------------------------
    | Default Sound Effects Model
    |--------------------------------------------------------------------------
    |
    | The model used for sound effect generation.
    |   eleven_text_to_sound_v2  — Latest sound effects model (default, supports looping)
    |
    */

    'default_sfx_model' => env('ELEVENLABS_SFX_MODEL', 'eleven_text_to_sound_v2'),

    /*
    |--------------------------------------------------------------------------
    | Default Audio Output Format
    |--------------------------------------------------------------------------
    |
    | The output format for generated audio. Format: codec_samplerate_bitrate
    |
    | Free / Starter plans:
    |   mp3_44100_128   — MP3 44.1kHz 128kbps (recommended default)
    |   mp3_22050_32    — MP3 22kHz 32kbps (smaller file)
    |   pcm_16000       — PCM 16kHz (lossless, larger)
    |   pcm_22050       — PCM 22kHz
    |   pcm_44100       — PCM 44.1kHz
    |
    | Creator tier and above:
    |   mp3_44100_192   — MP3 44.1kHz 192kbps (higher quality)
    |
    */

    'default_output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_128'),

    /*
    |--------------------------------------------------------------------------
    | Default Voice Settings
    |--------------------------------------------------------------------------
    |
    | Default voice settings applied to TTS requests unless overridden per-call.
    |
    |   stability        — (0.0–1.0) Higher = more consistent but less expressive.
    |   similarity_boost — (0.0–1.0) Higher = closer to the original voice.
    |   style             — (0.0–1.0) Style exaggeration (0 = off, use sparingly).
    |   use_speaker_boost — Boost speaker similarity at slight quality cost.
    |
    */

    'voice_settings' => [
        'stability'        => 0.5,
        'similarity_boost' => 0.75,
        'style'            => 0.0,
        'use_speaker_boost' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout (in seconds) for API requests. TTS and STT can take several seconds
    | for longer content, so this is set generously by default.
    |
    */

    'timeout' => env('ELEVENLABS_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Disk for Storing Audio Files
    |--------------------------------------------------------------------------
    |
    | When storing generated audio, this is the disk (from config/filesystems.php)
    | that will be used. Defaults to the app's default filesystem disk.
    |
    */

    'disk' => env('ELEVENLABS_DISK', null),

];
