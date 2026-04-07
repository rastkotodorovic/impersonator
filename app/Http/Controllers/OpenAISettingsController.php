<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class OpenAISettingsController extends Controller
{
    public function index(): View
    {
        $credential = auth()->user()->openaiCredential;

        return view('openai.index', [
            'hasCredential' => $credential && $credential->hasValidCredential(),
            'credential' => $credential,
        ]);
    }
}
