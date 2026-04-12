<?php

namespace App\Services;

use App\Models\Patient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiService
{
    // ─── Entry points ─────────────────────────────────────────────────────────

    /**
     * Main chat method. Routes to the correct provider.
     *
     * @param  array   $messages  [['role'=>'user'|'assistant', 'content'=>'...'], ...]
     * @param  string  $provider  'anthropic' | 'openai' | '' (use config default)
     * @param  string  $model     model slug, or '' to use config default
     * @param  string  $system    system prompt override
     */
    public function chat(
        array  $messages,
        string $provider = '',
        string $model    = '',
        string $system   = ''
    ): string {
        $provider = $provider ?: config('ai.default_provider', 'anthropic');
        $system   = $system   ?: $this->defaultSystemPrompt();

        return match ($provider) {
            'openai'    => $this->callOpenAI($messages, $model, $system),
            default     => $this->callAnthropic($messages, $model, $system),
        };
    }

    /**
     * Generate a clinical patient summary.
     */
    public function summarizePatient(Patient $patient, string $provider = '', string $model = ''): string
    {
        $patient->load([
            'medicalInfo', 'clinicalFlags', 'branch',
            'notes'         => fn($q) => $q->latest()->limit(10),
            'appointments'  => fn($q) => $q->with('service')->latest('scheduled_at')->limit(5),
            'followUps'     => fn($q) => $q->where('status', 'pending')->orderBy('due_date')->limit(5),
            'treatmentPlans'=> fn($q) => $q->with('service'),
        ]);

        $context = $this->buildPatientContext($patient);

        $prompt = <<<PROMPT
Please provide a concise clinical summary for this patient. Structure it as:

**1. Patient Overview** — key demographics, registration date, status
**2. Clinical Profile** — skin type, known conditions, allergies, contraindications
**3. Treatment History** — active/completed plans and progress
**4. Recent Activity** — last appointment, upcoming follow-ups
**5. Risk Flags** — clinical flags, warnings, or concerns before treatment
**6. Recommended Next Steps** — what the clinic should do next

Be practical and clinical. Use bullet points. Highlight any ⚠️ risks clearly.

Patient data:
{$context}
PROMPT;

        return $this->chat([['role' => 'user', 'content' => $prompt]], $provider, $model);
    }

    /**
     * Draft a note for a patient given type + optional staff hint.
     */
    public function suggestNote(Patient $patient, string $noteType, string $hint = '', string $provider = '', string $model = ''): string
    {
        $patient->load(['medicalInfo', 'clinicalFlags', 'notes' => fn($q) => $q->latest()->limit(5)]);
        $context = $this->buildPatientContext($patient);

        $hintText = $hint ? " The staff member's concern or starting point: \"{$hint}\"." : '';

        $prompt = "Based on the patient profile below, draft a professional {$noteType} note for a clinic staff member.{$hintText}\n\n"
            . "Write only the note body — 2–4 sentences, clinical and factual, no title or preamble.\n\n"
            . "Patient profile:\n{$context}";

        return $this->chat([['role' => 'user', 'content' => $prompt]], $provider, $model);
    }

    /**
     * General assistant chat with optional patient context injected into the system prompt.
     */
    public function assistantChat(array $messages, ?Patient $patient = null, string $provider = '', string $model = ''): string
    {
        $system = $this->defaultSystemPrompt();

        if ($patient) {
            $patient->load([
                'medicalInfo', 'clinicalFlags',
                'notes'        => fn($q) => $q->latest()->limit(5),
                'appointments' => fn($q) => $q->with('service')->latest('scheduled_at')->limit(5),
                'followUps'    => fn($q) => $q->where('status', 'pending')->orderBy('due_date')->limit(3),
            ]);
            $system .= "\n\n## Active Patient Context\nYou currently have this patient loaded:\n\n"
                . $this->buildPatientContext($patient)
                . "\n\nUse this data when answering questions about this patient.";
        }

        return $this->chat($messages, $provider, $model, $system);
    }

    // ─── Provider implementations ─────────────────────────────────────────────

    private function callAnthropic(array $messages, string $model, string $system): string
    {
        $apiKey = config('ai.anthropic.api_key', '');
        if (empty($apiKey)) {
            return "⚠️ Anthropic API key not configured. Add `ANTHROPIC_API_KEY` to your `.env` file.";
        }

        $model = $model ?: config('ai.anthropic.model', 'claude-3-5-haiku-20241022');

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => config('ai.anthropic.version', '2023-06-01'),
                'content-type'      => 'application/json',
            ])->timeout(config('ai.timeout', 45))->post(config('ai.anthropic.base_url'), [
                'model'      => $model,
                'max_tokens' => config('ai.max_tokens', 2048),
                'system'     => $system,
                'messages'   => $messages,
            ]);

            if ($response->successful()) {
                return $response->json('content.0.text', 'No response received from Claude.');
            }

            $err = $response->json('error.message', $response->body());
            Log::error('Anthropic API error', ['status' => $response->status(), 'error' => $err]);
            return "⚠️ Anthropic error ({$response->status()}): {$err}";

        } catch (\Throwable $e) {
            Log::error('AiService::callAnthropic', ['message' => $e->getMessage()]);
            return '⚠️ Could not reach Anthropic. Check your network and try again.';
        }
    }

    private function callOpenAI(array $messages, string $model, string $system): string
    {
        $apiKey = config('ai.openai.api_key', '');
        if (empty($apiKey)) {
            return "⚠️ OpenAI API key not configured. Add `OPENAI_API_KEY` to your `.env` file.";
        }

        $model = $model ?: config('ai.openai.model', 'gpt-4o-mini');

        // OpenAI uses the system message as the first message with role=system
        $openAiMessages = array_merge(
            [['role' => 'system', 'content' => $system]],
            $messages
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
            ])->timeout(config('ai.timeout', 45))->post(config('ai.openai.base_url'), [
                'model'      => $model,
                'max_tokens' => config('ai.max_tokens', 2048),
                'messages'   => $openAiMessages,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content', 'No response received from OpenAI.');
            }

            $err = $response->json('error.message', $response->body());
            Log::error('OpenAI API error', ['status' => $response->status(), 'error' => $err]);
            return "⚠️ OpenAI error ({$response->status()}): {$err}";

        } catch (\Throwable $e) {
            Log::error('AiService::callOpenAI', ['message' => $e->getMessage()]);
            return '⚠️ Could not reach OpenAI. Check your network and try again.';
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function defaultSystemPrompt(): string
    {
        return <<<PROMPT
You are MedFlow AI — a smart clinical assistant embedded in MedFlow CRM, used by a medical aesthetics and laser clinic.

You help clinic staff with:
• Patient summaries and clinical risk assessment
• Drafting clinical, session, follow-up, and reception notes
• Treatment recommendations based on skin type, history, and contraindications
• Identifying risks before treatment (pacemakers, pregnancy, implants, allergies)
• Scheduling and follow-up suggestions
• Answering clinical, operational, and CRM-related questions

Tone: Professional, concise, clinically accurate. Use bullet points for lists.
Always flag serious contraindications prominently with ⚠️.
Never fabricate patient data — work only with what is provided.
If something requires a doctor's judgment, say so briefly and move on.
PROMPT;
    }

    public function buildPatientContext(Patient $patient): string
    {
        $lines = [];

        $lines[] = "─── Patient Record ───────────────────────────────";
        $lines[] = "Name:        {$patient->full_name}";
        $lines[] = "Code:        {$patient->patient_code}";
        $lines[] = "Gender:      " . ucfirst($patient->gender ?? 'Unknown');
        $lines[] = "Age:         " . ($patient->date_of_birth ? $patient->date_of_birth->age . ' years' : 'Unknown');
        $lines[] = "Status:      " . ucfirst($patient->status);
        $lines[] = "Branch:      " . ($patient->branch?->name ?? 'Unknown');
        $lines[] = "Registered:  " . $patient->created_at->format('d M Y');
        $lines[] = "Consent:     " . ($patient->consent_given ? 'Given ✓' : 'NOT given ✗');
        if ($patient->phone)  $lines[] = "Phone:       {$patient->phone}";
        if ($patient->nationality) $lines[] = "Nationality: {$patient->nationality}";

        // Medical
        if ($med = $patient->medicalInfo) {
            $lines[] = "";
            $lines[] = "─── Medical Info ─────────────────────────────────";
            if ($med->skin_type)           $lines[] = "Skin Type (Fitzpatrick): {$med->skin_type}";
            if ($med->skin_tone)           $lines[] = "Skin Tone:               " . ucfirst($med->skin_tone);
            if ($med->height_cm)           $lines[] = "Height/Weight:           {$med->height_cm}cm / {$med->weight_kg}kg";
            if ($med->allergies)           $lines[] = "Allergies:               {$med->allergies}";
            if ($med->contraindications)   $lines[] = "⚠️ Contraindications:   {$med->contraindications}";
            if ($med->medical_history)     $lines[] = "Medical History:         {$med->medical_history}";
            if ($med->current_medications) $lines[] = "Medications:             {$med->current_medications}";
            if ($med->other_conditions)    $lines[] = "Other Conditions:        {$med->other_conditions}";
            if ($med->is_pregnant)         $lines[] = "⚠️ CURRENTLY PREGNANT";
            if ($med->has_pacemaker)       $lines[] = "⚠️ HAS PACEMAKER — avoid electrical/laser treatments";
            if ($med->has_metal_implants)  $lines[] = "⚠️ METAL IMPLANTS — check treatment compatibility";
        }

        // Clinical flags
        if ($patient->clinicalFlags?->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "─── Clinical Flags ───────────────────────────────";
            foreach ($patient->clinicalFlags as $f) {
                $detail = $f->pivot->detail ? " — {$f->pivot->detail}" : '';
                $lines[] = "  • {$f->name}{$detail}";
            }
        }

        // Treatment plans
        if ($patient->treatmentPlans?->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "─── Treatment Plans ──────────────────────────────";
            foreach ($patient->treatmentPlans as $plan) {
                $pct = $plan->total_sessions > 0
                    ? round($plan->completed_sessions / $plan->total_sessions * 100) . '%'
                    : '—';
                $lines[] = "  • {$plan->service?->name} | {$plan->completed_sessions}/{$plan->total_sessions} sessions ({$pct}) | " . ucfirst($plan->status);
            }
        }

        // Recent appointments
        if ($patient->appointments?->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "─── Recent Appointments ──────────────────────────";
            foreach ($patient->appointments->take(5) as $appt) {
                $dt = \Carbon\Carbon::parse($appt->scheduled_at)->format('d M Y');
                $lines[] = "  • {$dt}: " . ($appt->service?->name ?? 'Unknown service') . " — " . ucfirst(str_replace('_', ' ', $appt->status));
            }
        }

        // Recent notes
        if ($patient->notes?->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "─── Recent Notes ─────────────────────────────────";
            foreach ($patient->notes->where('is_private', false)->take(5) as $note) {
                $lines[] = "  [{$note->note_type}] " . Str::limit($note->content, 150);
            }
        }

        // Pending follow-ups
        if ($patient->followUps?->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "─── Pending Follow-ups ───────────────────────────";
            foreach ($patient->followUps->take(3) as $fu) {
                $lines[] = "  • " . ucfirst($fu->type) . " due " . $fu->due_date->format('d M Y');
            }
        }

        return implode("\n", $lines);
    }
}
