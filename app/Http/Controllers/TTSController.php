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
    private function textToSpeech(
        string $language,
        string $text,
        ?string $filePath = null
    )
    {
        // Use voice map or allow client to pass exact voice name
        $voiceMap = [
            'bn-IN' => 'bn-IN-Chirp3-HD-Erinome',
            'en-US' => 'en-US-JennyNeural', // Example fallback
        ];

        $voiceName = $voiceMap[$language] ?? 'bn-IN-Chirp3-HD-Erinome';

        if (!$filePath) {
            // Generate a unique hash for caching
            $hash     = md5($language . '|' . $text);
            $filename = "tts_cache/{$hash}.wav";
            $filePath = $filePath ?? storage_path("app/public/{$filename}");
        }

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
            File::put($filePath, $audioContent);
        }

        return $filePath;
    }

    /**
     * @throws ApiException
     * @throws ValidationException
     */
    public function getIvrMenu()
    {
        $path = Storage::disk("public")->path("tts_cache/ivr_menu.wav");

        if (!Storage::exists($path)) {
            $this->textToSpeech(
                language: "bn-IN",
                text    : "বাংলাদেশ কৃষি ব্যাংকের অনলাইন গ্রাহক সেবায় আপনাকে স্বাগতম।"
                          . " আমাদের গ্রাহক পরিসেবা হতে আপনার প্রয়োজনীয় অপশন বাছাই করুন।"
                          . " আপনার বর্তমান ব্যাল্যান্স জানার জন্য ১ চাপুন।"
                          . " আপনার হিসাবের তথ্য হালনাগাদ করার জন্য ২ চাপুন।"
                          . " ক্রেডিট কার্ড সম্পর্কিত সেবার জন্য ৩ চাপুন।"
                          . " কার্ড হারিয়ে যাওয়ার বিষয়ে তাৎক্ষনিক অভিযোগ জানানোর জন্য ৪ চাপুন।"
                          . " কৃষি ব্যাংকের গ্রাহক পরিসেবা নিয়ে অভিযোগ জানানোর জন্য ৫ চাপুন।"
                          . " ব্যাংকের সেবা প্রতিনিধির সাথে সরাসরি কথা বলার জন্য ০ চাপুন।",
                filePath: $path
            );
        }

        return response()->file($path);
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


        return response()->file($this->textToSpeech(
            language: $language,
            text    : $text
        ));
    }

}
