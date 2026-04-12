<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(private AiService $ai) {}

    /**
     * Full-page AI chat screen.
     * GET /ai
     */
    public function page(\Illuminate\Http\Request $request)
    {
        $models    = config('ai.available_models', []);
        $default   = config('ai.default_provider', 'anthropic');

        // Pre-load a patient if patient_id is passed (e.g. from patient show page)
        $preloadPatient = null;
        if ($request->filled('patient_id')) {
            $preloadPatient = Patient::find($request->patient_id);
        }

        return view('ai.chat', compact('models', 'default', 'preloadPatient'));
    }

    /**
     * Chat endpoint — used by the full-page chat and any inline panels.
     * POST /ai/chat
     */
    public function chat(Request $request)
    {
        $request->validate([
            'messages'           => 'required|array|min:1',
            'messages.*.role'    => 'required|in:user,assistant',
            'messages.*.content' => 'required|string|max:8000',
            'provider'           => 'nullable|in:anthropic,openai',
            'model'              => 'nullable|string|max:80',
            'patient_id'         => 'nullable|integer|exists:patients,id',
        ]);

        $patient  = $request->filled('patient_id') ? Patient::find($request->patient_id) : null;
        $provider = $request->input('provider', '');
        $model    = $request->input('model', '');

        $reply = $this->ai->assistantChat($request->messages, $patient, $provider, $model);

        return response()->json(['reply' => $reply]);
    }

    /**
     * Generate a full AI patient summary.
     * POST /ai/patient/{patient}/summary
     */
    public function patientSummary(Request $request, Patient $patient)
    {
        $request->validate([
            'provider' => 'nullable|in:anthropic,openai',
            'model'    => 'nullable|string|max:80',
        ]);

        $summary = $this->ai->summarizePatient(
            $patient,
            $request->input('provider', ''),
            $request->input('model', '')
        );

        return response()->json(['summary' => $summary]);
    }

    /**
     * Suggest note content.
     * POST /ai/patient/{patient}/suggest-note
     */
    public function suggestNote(Request $request, Patient $patient)
    {
        $request->validate([
            'note_type' => 'required|string',
            'hint'      => 'nullable|string|max:500',
            'provider'  => 'nullable|in:anthropic,openai',
            'model'     => 'nullable|string|max:80',
        ]);

        $suggestion = $this->ai->suggestNote(
            $patient,
            $request->note_type,
            $request->hint ?? '',
            $request->input('provider', ''),
            $request->input('model', '')
        );

        return response()->json(['suggestion' => $suggestion]);
    }
}
