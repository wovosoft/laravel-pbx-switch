<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

}
