<?php

namespace App\Http\Controllers;

use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use Google\Cloud\TextToSpeech\V1\Client\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\SynthesizeSpeechRequest;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class TTSController extends Controller
{
    public function generate(Request $request)
    {
        $text = $request->get('text');
        $lang = $request->get('lang', 'en'); // Bengali default

        if (!$text) {
            return response()->json(['error' => 'Text is required.'], 400);
        }

        $filename = 'tts_' . Str::random(8) . '.wav';
        $filepath = storage_path("app/public/tts/{$filename}");

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($filepath));

        // Build command
        $cmd = [
            'espeak-ng',
            "-v", $lang,
            "-w", $filepath,
            $text,
        ];

        // Run command
        $process = new Process($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json(['error' => 'TTS failed: ' . $process->getErrorOutput()], 500);
        }

        return response()->file("storage/tts/{$filename}");
    }

    public function generateWithFlite(Request $request)
    {
        $text = $request->get('text');
        $lang = $request->get('lang', 'us'); // flite voices are limited, 'us' is default

        if (!$text) {
            return response()->json(['error' => 'Text is required.'], 400);
        }

        $filename = 'tts_' . Str::random(8) . '.wav';
        $filepath = storage_path("app/public/tts/{$filename}");

        // Ensure directory exists
        File::ensureDirectoryExists(dirname($filepath));

        // Build flite command
        // flite -voice <voice_name> -t "text" -o <outputfile>
        $cmd = [
            'flite',
            '-voice', 'slt',
            '-t', $text,
            '-o', $filepath,
        ];

        // Run command
        $process = new Process($cmd);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json(['error' => 'TTS failed: ' . $process->getErrorOutput()], 500);
        }

        return response()->file("storage/tts/{$filename}");
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    public function synthesizeWithGoogleClient(Request $request)
    {
        $validated = $request->validate([
            'text'          => 'required|string',
            'language_code' => 'nullable|string',
        ]);

        $text     = $validated['text'];
        $language = $validated['language_code'] ?? "bn-IN";

        // Use voice map or allow client to pass exact voice name
        $voiceMap = [
            'bn-IN' => 'bn-IN-Chirp3-HD-Erinome',
            'en-US' => 'en-US-JennyNeural', // Example fallback
        ];

        $voiceName = $voiceMap[$language] ?? 'bn-IN-Chirp3-HD-Erinome';

        // Generate a unique hash for caching
        $hash     = md5($language . '|' . $text);
        $filename = "tts_cache/{$hash}.wav";
        $filePath = storage_path("app/public/{$filename}");

        if (!file_exists($filePath)) {
            $client = new TextToSpeechClient([
                'credentials' => storage_path('app/private/google/tts-credentials.json'),
            ]);

            $synthesisInput = new SynthesisInput([
                'text' => $text,
            ]);

            $voice = new VoiceSelectionParams([
                'language_code' => $language,
                'name'          => $voiceName,
            ]);

            $audioConfig = new AudioConfig([
                'audio_encoding' => AudioEncoding::LINEAR16,
                'speaking_rate'  => 1.05,
            ]);

            $request = new SynthesizeSpeechRequest([
                'input'        => $synthesisInput,
                'voice'        => $voice,
                'audio_config' => $audioConfig,
            ]);

            $response     = $client->synthesizeSpeech($request);
            $audioContent = $response->getAudioContent();

            // Store in public/tts_cache
            Storage::disk('public')->put("tts_cache/{$hash}.wav", $audioContent);
        }

        return response()->file($filePath);
    }

}
