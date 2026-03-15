# ElevenLabs Laravel Package

A clean, well-tested Laravel package for the [ElevenLabs](https://elevenlabs.io) Voice API.  
Supports **Text to Speech**, **Speech to Text**, and **Sound Effects** with a fluent API and Laravel Facade.

---

## Table of Contents

1. [Requirements](#requirements)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Usage](#usage)
   - [Text to Speech](#text-to-speech)
   - [Speech to Text](#speech-to-text)
   - [Sound Effects](#sound-effects)
   - [Listing Voices](#listing-voices)
5. [Available Models](#available-models)
6. [Testing](#testing)
7. [Publishing to Packagist](#publishing-to-packagist)
8. [Package Architecture Explained](#package-architecture-explained)

---

## Requirements

- PHP 8.2+
- Laravel 10 or 11
- An [ElevenLabs API key](https://elevenlabs.io/app/settings/api-keys)

---

## Installation

```bash
composer require scatterBrain/elevenlabs
```

Laravel's **package auto-discovery** will automatically register the service provider and `ElevenLabs` facade — no manual setup needed.

If you have auto-discovery disabled, add these to `config/app.php`:

```php
'providers' => [
    Back2ops\ElevenLabs\ElevenLabsServiceProvider::class,
],

'aliases' => [
    'ElevenLabs' => Back2ops\ElevenLabs\Facades\ElevenLabs::class,
],
```

---

## Configuration

### 1. Publish the config file

```bash
php artisan vendor:publish --tag=elevenlabs-config
```

This creates `config/elevenlabs.php` in your application.

### 2. Add your API key to `.env`

```env
ELEVENLABS_API_KEY=your_api_key_here

# Optional overrides (these have sensible defaults in the config):
ELEVENLABS_VOICE_ID=21m00Tcm4TlvDq8ikWAM   # Rachel (default)
ELEVENLABS_TTS_MODEL=eleven_multilingual_v2
ELEVENLABS_STT_MODEL=scribe_v2
ELEVENLABS_SFX_MODEL=eleven_text_to_sound_v2
ELEVENLABS_OUTPUT_FORMAT=mp3_44100_128
ELEVENLABS_DISK=                            # Leave blank to use default filesystem disk
```

---

## Usage

You can use the `ElevenLabs` facade anywhere, or inject `Back2ops\ElevenLabs\ElevenLabs` directly.

### Text to Speech

#### Get raw audio bytes

```php
use ElevenLabs;

$audioBytes = ElevenLabs::textToSpeech('Hello, welcome to our platform!');

// Store it yourself
Storage::put('audio/welcome.mp3', $audioBytes);

// Or stream it back to the browser
return response($audioBytes, 200)->header('Content-Type', 'audio/mpeg');
```

#### With custom options

```php
$audioBytes = ElevenLabs::textToSpeech(
    text: 'Bonjour tout le monde!',
    voiceId: 'EXAVITQu4vr4xnSDxMaL',    // Override voice
    modelId: 'eleven_flash_v2_5',          // Ultra-low latency model
    outputFormat: 'mp3_44100_128',
    voiceSettings: [
        'stability'        => 0.8,
        'similarity_boost' => 0.9,
        'style'            => 0.2,
    ],
    languageCode: 'fr',                    // Force French
);
```

#### Generate and store in one call

```php
// Returns the storage path 'audio/greeting.mp3'
$path = ElevenLabs::textToSpeechAndStore(
    text: 'Welcome back!',
    path: 'audio/greeting.mp3',
    disk: 's3',                           // Optional: override filesystem disk
);

// Get the public URL
$url = Storage::disk('s3')->url($path);
```

---

### Speech to Text

#### Transcribe a file on disk

```php
// Full response — includes word-level timestamps
$result = ElevenLabs::transcribeFile('/path/to/recording.mp3');

echo $result['text'];           // Full transcript string
echo $result['language_code']; // 'en'

foreach ($result['words'] as $word) {
    echo "{$word['text']} ({$word['start']}s – {$word['end']}s)\n";
}
```

#### Just the text

```php
$transcript = ElevenLabs::transcribeToText('/path/to/recording.mp3');
echo $transcript; // "Hello, this is the transcribed content."
```

#### From an uploaded file (Controller example)

```php
public function transcribe(Request $request): JsonResponse
{
    $request->validate(['audio' => 'required|file|mimes:mp3,wav,webm,m4a|max:51200']);

    $file = $request->file('audio');

    $result = ElevenLabs::transcribeBytes(
        bytes: file_get_contents($file->getRealPath()),
        filename: $file->getClientOriginalName(),
        language: $request->input('language'),  // e.g. 'en', 'es' — optional
    );

    return response()->json([
        'transcript' => $result['text'],
        'language'   => $result['language_code'],
    ]);
}
```

---

### Sound Effects

#### Generate and return bytes

```php
$sfx = ElevenLabs::soundEffect('a heavy wooden door creaking open slowly');

Storage::put('sfx/door-creak.mp3', $sfx);
```

#### With options

```php
$sfx = ElevenLabs::soundEffect(
    prompt: 'gentle rain on a tin roof',
    durationSeconds: 10.0,        // 0.5–30 seconds (null = auto-detect)
    promptInfluence: 0.5,         // 0.0–1.0 (default 0.3)
    loopable: true,               // Seamless loop (eleven_text_to_sound_v2 only)
);
```

#### Generate and store

```php
$path = ElevenLabs::soundEffectAndStore(
    prompt: 'crowd cheering in a stadium',
    path: 'sfx/crowd-cheer.mp3',
    options: [
        'duration_seconds' => 8.0,
        'loopable'         => false,
    ],
);
```

---

### Listing Voices

```php
// All voices on your account
$voices = ElevenLabs::voices();

foreach ($voices as $voice) {
    echo $voice['voice_id'] . ' — ' . $voice['name'] . "\n";
}

// Single voice details
$voice = ElevenLabs::voice('21m00Tcm4TlvDq8ikWAM');

// All available models
$models = ElevenLabs::models();
```

---

## Available Models

### Text to Speech

| Model ID | Description |
|---|---|
| `eleven_multilingual_v2` | Highest quality, 32 languages (**default**) |
| `eleven_flash_v2_5` | Ultra-low ~75ms latency, great for real-time |
| `eleven_turbo_v2_5` | Balanced speed & quality |

### Speech to Text

| Model ID | Description |
|---|---|
| `scribe_v2` | State-of-the-art accuracy (**default**) |
| `scribe_v1` | Legacy model |

### Sound Effects

| Model ID | Description |
|---|---|
| `eleven_text_to_sound_v2` | Latest, supports looping (**default**) |

### Output Formats

| Format | Description |
|---|---|
| `mp3_44100_128` | MP3 44.1kHz 128kbps (**default**) |
| `mp3_22050_32` | MP3 22kHz 32kbps (smaller files) |
| `mp3_44100_192` | MP3 44.1kHz 192kbps (Creator tier+) |
| `pcm_16000` | PCM 16kHz (lossless) |
| `pcm_44100` | PCM 44.1kHz (lossless, large) |

---
