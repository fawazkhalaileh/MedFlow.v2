@extends('layouts.app')

@section('title', 'MedFlow AI — {{ __('Clinical Assistant') }}')
@section('breadcrumb', 'AI Assistant')

{{-- Override page padding so the chat fills the full content area --}}
@push('page_style')
<style>
  .page-container { padding: 0 !important; overflow: hidden !important; }
</style>
@endpush

@section('content')
@php
  $anthropicKey = config('ai.anthropic.api_key', '');
  $openaiKey    = config('ai.openai.api_key', '');
  $hasAnthropic = !empty($anthropicKey);
  $hasOpenAI    = !empty($openaiKey);
  $defaultProvider = $default; // passed from controller
@endphp

<div id="ai-chat-shell" style="display:flex;height:100%;overflow:hidden;">

  {{-- ══════════════════════════════════════════════════════════
       LEFT PANEL — controls, context, quick prompts
  ══════════════════════════════════════════════════════════ --}}
  <div id="ai-left-panel" style="width:280px;min-width:280px;background:var(--bg-secondary);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;">

    {{-- Header --}}
    <div style="padding:18px 16px 12px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
        <div style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#2563eb,#7c3aed);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:17px;height:17px;"><path d="M12 2a10 10 0 1 0 10 10"/><path d="M12 8v4l3 3"/><circle cx="19" cy="5" r="3" fill="white" stroke="none"/></svg>
        </div>
        <div>
          <div style="font-weight:700;font-size:.92rem;line-height:1.2;">MedFlow AI</div>
          <div style="font-size:.72rem;color:var(--text-tertiary);">{{ __('Clinical Assistant') }}</div>
        </div>
      </div>
      <button id="new-chat-btn" style="width:100%;padding:9px;background:linear-gradient(135deg,#2563eb,#7c3aed);border:none;border-radius:var(--radius-md);color:#fff;font-size:.84rem;font-weight:500;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;font-family:var(--font-body);transition:opacity .2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:14px;height:14px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Conversation
      </button>
    </div>

    {{-- Provider / Model selector --}}
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--text-tertiary);margin-bottom:8px;">{{ __('AI Provider') }}</div>

      {{-- Provider tabs --}}
      <div style="display:flex;gap:4px;margin-bottom:10px;">
        <button class="provider-tab {{ $defaultProvider === 'anthropic' ? 'active' : '' }}" data-provider="anthropic"
          style="{{ !$hasAnthropic ? 'opacity:.45;' : '' }}"
          title="{{ !$hasAnthropic ? 'Add ANTHROPIC_API_KEY to .env' : 'Use Claude (Anthropic)' }}">
          <img src="https://www.anthropic.com/favicon.ico" onerror="this.style.display='none'" style="width:13px;height:13px;border-radius:2px;">
          Claude
          @if(!$hasAnthropic)<span style="font-size:.6rem;color:var(--danger);">⚠</span>@endif
        </button>
        <button class="provider-tab {{ $defaultProvider === 'openai' ? 'active' : '' }}" data-provider="openai"
          style="{{ !$hasOpenAI ? 'opacity:.45;' : '' }}"
          title="{{ !$hasOpenAI ? 'Add OPENAI_API_KEY to .env' : 'Use GPT (OpenAI)' }}">
          <svg viewBox="0 0 24 24" fill="currentColor" style="width:12px;height:12px;"><path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.033.062L9.74 19.95a4.5 4.5 0 0 1-6.14-1.646zM2.34 7.896a4.485 4.485 0 0 1 2.366-1.973V11.6a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0l-4.83-2.786A4.504 4.504 0 0 1 2.34 7.872zm16.597 3.855l-5.843-3.371 2.02-1.168a.076.076 0 0 1 .071 0l4.83 2.78a4.494 4.494 0 0 1-.676 8.109v-5.678a.79.79 0 0 0-.402-.672zm2.01-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.83-2.787a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.704 5.46a.795.795 0 0 0-.393.681zm1.097-2.365l2.602-1.5 2.603 1.5v2.999l-2.597 1.5-2.608-1.5z"/></svg>
          GPT
          @if(!$hasOpenAI)<span style="font-size:.6rem;color:var(--danger);">⚠</span>@endif
        </button>
      </div>

      {{-- Model select --}}
      <select id="model-select" style="width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.8rem;font-family:var(--font-body);background:#fff;color:var(--text-primary);outline:none;cursor:pointer;">
        @foreach(config('ai.available_models.'.$defaultProvider, []) as $slug => $label)
          <option value="{{ $slug }}" {{ $slug === config('ai.'.$defaultProvider.'.model') ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>

      {{-- Key status --}}
      <div style="margin-top:8px;font-size:.72rem;">
        @if(!$hasAnthropic && !$hasOpenAI)
          <span style="color:var(--danger);">⚠️ No API keys configured. Add to <code>.env</code></span>
        @elseif($defaultProvider === 'anthropic' && !$hasAnthropic)
          <span style="color:var(--danger);">⚠️ ANTHROPIC_API_KEY missing</span>
        @elseif($defaultProvider === 'openai' && !$hasOpenAI)
          <span style="color:var(--danger);">⚠️ OPENAI_API_KEY missing</span>
        @else
          <span style="color:var(--success);">✓ API key active</span>
        @endif
      </div>
    </div>

    {{-- Patient context --}}
    <div style="padding:14px 16px;border-bottom:1px solid var(--border);flex-shrink:0;">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--text-tertiary);margin-bottom:8px;">{{ __('Patient Context') }}</div>
      <div style="position:relative;">
        <input type="text" id="patient-search-input" placeholder="{{ __('Search patient') }}…" autocomplete="off"
          style="width:100%;padding:7px 10px 7px 30px;border:1px solid var(--border);border-radius:var(--radius-sm);font-size:.82rem;font-family:var(--font-body);background:#fff;outline:none;transition:border-color .2s;"
          onfocus="this.style.borderColor='var(--accent)'" onblur="setTimeout(()=>document.getElementById('patient-results').style.display='none',200)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:9px;top:50%;transform:translateY(-50%);width:13px;height:13px;color:var(--text-tertiary);pointer-events:none;"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <div id="patient-results" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--border);border-radius:var(--radius-sm);box-shadow:var(--shadow-md);z-index:50;max-height:180px;overflow-y:auto;margin-top:2px;"></div>
      </div>
      <div id="patient-ctx-chip" style="display:none;margin-top:8px;padding:6px 10px;background:var(--accent-light);border-radius:var(--radius-sm);border:1px solid rgba(37,99,235,.2);display:none;align-items:center;justify-content:space-between;gap:6px;">
        <div style="display:flex;align-items:center;gap:6px;min-width:0;">
          <div style="width:22px;height:22px;border-radius:50%;background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#fff;font-weight:600;flex-shrink:0;" id="patient-ctx-avatar">P</div>
          <span style="font-size:.8rem;font-weight:500;color:var(--accent);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" id="patient-ctx-name">Patient Name</span>
        </div>
        <button onclick="clearPatientCtx()" style="background:none;border:none;cursor:pointer;color:var(--accent);flex-shrink:0;opacity:.7;line-height:1;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=.7">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:13px;height:13px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>

    {{-- Quick prompts --}}
    <div style="padding:14px 16px;flex:1;overflow-y:auto;">
      <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:var(--text-tertiary);margin-bottom:10px;">{{ __('Quick Prompts') }}</div>
      <div style="display:flex;flex-direction:column;gap:4px;" id="quick-prompt-list">

        <div class="qp-group-label">{{ __('General') }}</div>
        <button class="qp-btn" data-prompt="What are the most common contraindications for laser hair removal that I should always check before treatment?">Laser contraindications</button>
        <button class="qp-btn" data-prompt="Summarize the key Fitzpatrick skin types and which laser treatments are safe for each.">Fitzpatrick guide</button>
        <button class="qp-btn" data-prompt="What should I document after a laser session to ensure proper clinical record-keeping?">Post-session checklist</button>
        <button class="qp-btn" data-prompt="What are the signs of a post-treatment adverse reaction I should report immediately?">Adverse reactions</button>

        <div class="qp-group-label" style="margin-top:10px;">Patient (when linked)</div>
        <button class="qp-btn qp-patient" data-action="summary" disabled>📋 Full patient summary</button>
        <button class="qp-btn qp-patient" data-action="risks" disabled>⚠️ Risk & contraindication check</button>
        <button class="qp-btn qp-patient" data-action="nextSteps" disabled>📞 Recommend next steps</button>
        <button class="qp-btn qp-patient" data-action="noteSession" disabled>📝 Draft session note</button>

      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════
       RIGHT PANEL — chat
  ══════════════════════════════════════════════════════════ --}}
  <div style="flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--bg-primary);">

    {{-- Chat header --}}
    <div style="height:56px;background:var(--bg-secondary);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:12px;flex-shrink:0;">
      <div style="flex:1;min-width:0;">
        <div style="font-size:.88rem;font-weight:600;" id="chat-header-title">MedFlow AI — {{ __('Clinical Assistant') }}</div>
        <div style="font-size:.73rem;color:var(--text-tertiary);" id="chat-header-sub">No patient linked · Claude (Anthropic)</div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div id="ai-status-dot" style="width:8px;height:8px;border-radius:50%;background:var(--success);animation:aiPulse 2.5s infinite;" title="{{ __('AI Ready') }}"></div>
        <span style="font-size:.76rem;color:var(--text-tertiary);" id="ai-status-label">{{ __('Ready') }}</span>
      </div>
    </div>

    {{-- Messages --}}
    <div id="chat-messages" style="flex:1;overflow-y:auto;padding:24px 28px;display:flex;flex-direction:column;gap:16px;">
      {{-- Welcome --}}
      <div class="msg-row msg-assistant">
        <div class="msg-avatar msg-avatar-ai">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        </div>
        <div class="msg-bubble msg-bubble-ai">
          <div style="font-weight:600;margin-bottom:6px;">👋 Welcome to MedFlow AI</div>
          <div>I'm your clinical assistant. I can help you with:</div>
          <ul style="margin:8px 0 0 16px;display:flex;flex-direction:column;gap:3px;font-size:.86rem;">
            <li>📋 Patient summaries and clinical risk assessment</li>
            <li>⚠️ Contraindication and safety checks</li>
            <li>📝 Drafting clinical, session, and follow-up notes</li>
            <li>💡 Treatment recommendations by skin type</li>
            <li>📞 Follow-up scheduling suggestions</li>
          </ul>
          <div style="margin-top:10px;font-size:.8rem;color:#94a3b8;">
            Tip: Link a patient using the search on the left to get context-aware answers.
          </div>
        </div>
      </div>
    </div>

    {{-- Typing indicator --}}
    <div id="typing-indicator" style="display:none;padding:0 28px 8px;">
      <div style="display:inline-flex;align-items:center;gap:8px;">
        <div class="msg-avatar msg-avatar-ai" style="width:28px;height:28px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:12px;height:12px;"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4"/></svg>
        </div>
        <div style="background:var(--bg-secondary);border:1px solid var(--border);padding:8px 14px;border-radius:14px;border-bottom-left-radius:4px;display:flex;gap:5px;align-items:center;">
          <span class="typing-dot"></span>
          <span class="typing-dot" style="animation-delay:.2s;"></span>
          <span class="typing-dot" style="animation-delay:.4s;"></span>
        </div>
      </div>
    </div>

    {{-- Input area --}}
    <div style="padding:16px 20px;background:var(--bg-secondary);border-top:1px solid var(--border);flex-shrink:0;">
      <div style="max-width:800px;margin:0 auto;">
        <div style="display:flex;gap:10px;align-items:flex-end;background:#fff;border:1.5px solid var(--border);border-radius:12px;padding:10px 12px;transition:border-color .2s;box-shadow:var(--shadow-sm);" id="input-wrapper">
          <textarea id="chat-input"
            placeholder="{{ __('Ask anything clinical, or link a patient for context') }}…"
            rows="1"
            style="flex:1;border:none;outline:none;font-family:var(--font-body);font-size:.88rem;resize:none;background:transparent;line-height:1.55;max-height:140px;overflow-y:auto;color:var(--text-primary);"
          ></textarea>
          <button id="send-btn" style="width:38px;height:38px;border-radius:9px;background:linear-gradient(135deg,#2563eb,#7c3aed);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:opacity .2s;align-self:flex-end;">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" style="width:16px;height:16px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:8px;font-size:.71rem;color:var(--text-tertiary);">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          MedFlow AI · <span id="footer-model-label">Claude 3.5 Haiku</span> · Enter to send, Shift+Enter for newline
        </div>
      </div>
    </div>

  </div>
</div>

<style>
/* ── Layout fix for full-height chat ───────────────── */
.page-container { padding: 0 !important; height: calc(100vh - var(--topbar-height)); overflow: hidden !important; }

/* ── Provider tabs ─────────────────────────────────── */
.provider-tab {
  flex: 1;
  padding: 6px 10px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: .78rem;
  font-family: var(--font-body);
  cursor: pointer;
  background: #fff;
  color: var(--text-secondary);
  font-weight: 500;
  transition: all .15s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
}
.provider-tab:hover { border-color: var(--accent); color: var(--accent); }
.provider-tab.active { background: linear-gradient(135deg,#2563eb,#7c3aed); border-color: transparent; color: #fff; }

/* ── Quick prompt buttons ───────────────────────────── */
.qp-group-label {
  font-size: .68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .6px;
  color: var(--text-tertiary);
  padding: 2px 2px 4px;
  margin-top: 4px;
}
.qp-btn {
  width: 100%;
  text-align: left;
  padding: 7px 10px;
  background: var(--bg-tertiary);
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  font-size: .79rem;
  font-family: var(--font-body);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all .15s;
  line-height: 1.4;
}
.qp-btn:hover:not(:disabled) { background: var(--accent-light); color: var(--accent); border-color: rgba(37,99,235,.2); }
.qp-btn:disabled { opacity: .4; cursor: not-allowed; }
.qp-btn.qp-patient:not(:disabled) { border-left: 3px solid var(--accent); background: var(--accent-light); color: var(--accent); }

/* ── Chat message rows ─────────────────────────────── */
.msg-row {
  display: flex;
  gap: 10px;
  max-width: 820px;
  animation: msgIn .2s ease-out;
}
.msg-assistant { align-self: flex-start; width: 100%; }
.msg-user      { align-self: flex-end; flex-direction: row-reverse; }

.msg-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: .75rem;
  font-weight: 600;
  margin-top: 2px;
}
.msg-avatar-ai   { background: linear-gradient(135deg,#2563eb,#7c3aed); }
.msg-avatar-user { background: linear-gradient(135deg,#0891b2,#2563eb); color: #fff; }

.msg-bubble {
  padding: 11px 15px;
  border-radius: 14px;
  font-size: .875rem;
  line-height: 1.6;
  max-width: 78%;
  word-wrap: break-word;
  overflow-wrap: break-word;
}
.msg-bubble-ai {
  background: var(--bg-secondary);
  border: 1px solid var(--border);
  border-bottom-left-radius: 4px;
  color: var(--text-primary);
}
.msg-bubble-user {
  background: linear-gradient(135deg,#2563eb,#7c3aed);
  color: #fff;
  border-bottom-right-radius: 4px;
}
/* Markdown styles inside AI bubbles */
.msg-bubble-ai strong { font-weight: 600; }
.msg-bubble-ai em { font-style: italic; color: var(--text-secondary); }
.msg-bubble-ai code { background: var(--bg-tertiary); padding: 1px 5px; border-radius: 4px; font-size: .82em; }
.msg-bubble-ai ul, .msg-bubble-ai ol { padding-left: 18px; margin: 6px 0; }
.msg-bubble-ai li { margin-bottom: 2px; }
.msg-bubble-ai h1,.msg-bubble-ai h2,.msg-bubble-ai h3 { font-weight: 600; margin: 10px 0 4px; }
.msg-bubble-ai h1 { font-size:1rem; }
.msg-bubble-ai h2 { font-size:.93rem; }
.msg-bubble-ai h3 { font-size:.87rem; }
.msg-bubble-ai hr { border: none; border-top: 1px solid var(--border); margin: 10px 0; }
.msg-bubble-ai p  { margin: 0 0 8px; }
.msg-bubble-ai p:last-child { margin-bottom: 0; }
.msg-bubble-ai blockquote { border-left: 3px solid var(--accent); padding-left: 10px; color: var(--text-secondary); margin: 6px 0; }

/* Typing dots */
.typing-dot {
  width: 7px; height: 7px;
  background: var(--text-tertiary);
  border-radius: 50%;
  display: inline-block;
  animation: typingBounce 1.2s infinite;
}

/* Status dot */
#ai-status-dot.thinking { background: var(--warning); }
#ai-status-dot.error    { background: var(--danger); animation: none; }

/* Animations */
@keyframes msgIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
@keyframes typingBounce { 0%,80%,100% { transform:scale(.6); opacity:.5; } 40% { transform:scale(1); opacity:1; } }
@keyframes aiPulse { 0%,100% { opacity:1; } 50% { opacity:.35; } }

/* Scrollbar */
#chat-messages::-webkit-scrollbar { width: 5px; }
#chat-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
#ai-left-panel > div:last-child::-webkit-scrollbar { width: 4px; }

/* Mobile collapse */
@media (max-width: 768px) {
  #ai-left-panel { display: none; }
}
</style>
@endsection

@push('page_style')
<style>
  /* Override topbar breadcrumb section styles for the push */
</style>
@endpush

@push('scripts')
<script>
// ════════════════════════════════════════════════════════════
//  MEDFLOW AI CHAT — FULL PAGE
// ════════════════════════════════════════════════════════════
(function () {
  // ── State ─────────────────────────────────────────────────
  const state = {
    history:    [],
    patientId:  null,
    patientName: null,
    provider:   '{{ $defaultProvider }}',
    model:      document.getElementById('model-select')?.value || '',
    sending:    false,
  };

  // ── DOM refs ──────────────────────────────────────────────
  const chatMessages  = document.getElementById('chat-messages');
  const chatInput     = document.getElementById('chat-input');
  const sendBtn       = document.getElementById('send-btn');
  const typingInd     = document.getElementById('typing-indicator');
  const inputWrapper  = document.getElementById('input-wrapper');
  const statusDot     = document.getElementById('ai-status-dot');
  const statusLabel   = document.getElementById('ai-status-label');
  const chatTitle     = document.getElementById('chat-header-title');
  const chatSub       = document.getElementById('chat-header-sub');
  const footerModel   = document.getElementById('footer-model-label');
  const modelSelect   = document.getElementById('model-select');
  const newChatBtn    = document.getElementById('new-chat-btn');

  // Model labels for display
  const modelLabels = @json(collect(config('ai.available_models'))->map(fn($m) => $m)->collapse());

  // ── Provider tabs ─────────────────────────────────────────
  const providerModels = @json(config('ai.available_models'));

  document.querySelectorAll('.provider-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.provider-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      state.provider = tab.dataset.provider;

      // Rebuild model options
      const opts = providerModels[state.provider] || {};
      modelSelect.innerHTML = Object.entries(opts)
        .map(([slug, label]) => `<option value="${slug}">${label}</option>`)
        .join('');
      state.model = modelSelect.value;
      updateFooter();
      updateHeader();
    });
  });

  modelSelect && modelSelect.addEventListener('change', () => {
    state.model = modelSelect.value;
    updateFooter();
    updateHeader();
  });

  function updateFooter() {
    const label = modelLabels[state.model] || state.model;
    if (footerModel) footerModel.textContent = label;
  }

  function updateHeader() {
    const provLabel = state.provider === 'openai' ? 'GPT (OpenAI)' : 'Claude (Anthropic)';
    const patLabel  = state.patientName ? `Patient: ${state.patientName}` : 'No patient linked';
    if (chatSub) chatSub.textContent = `${patLabel} · ${provLabel}`;
  }

  updateFooter();

  // ── New chat ───────────────────────────────────────────────
  newChatBtn && newChatBtn.addEventListener('click', () => {
    state.history = [];
    chatMessages.innerHTML = `
      <div class="msg-row msg-assistant">
        <div class="msg-avatar msg-avatar-ai">
          <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        </div>
        <div class="msg-bubble msg-bubble-ai" style="font-size:.85rem;color:var(--text-secondary);">
          New conversation started. How can I help?
        </div>
      </div>`;
  });

  // ── Patient search ─────────────────────────────────────────
  const patientInput   = document.getElementById('patient-search-input');
  const patientResults = document.getElementById('patient-results');
  const patientChip    = document.getElementById('patient-ctx-chip');
  const patientChipName= document.getElementById('patient-ctx-name');
  const patientChipAvatar = document.getElementById('patient-ctx-avatar');
  let searchTimer;

  patientInput && patientInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = patientInput.value.trim();
    if (q.length < 2) { patientResults.style.display = 'none'; return; }
    searchTimer = setTimeout(() => {
      fetch(`/patients/search?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
          if (!data.length) { patientResults.style.display = 'none'; return; }
          patientResults.innerHTML = data.map(p =>
            `<div style="padding:9px 12px;cursor:pointer;font-size:.82rem;border-bottom:1px solid var(--border-light);"
              onmousedown="selectPatient(${p.id},'${escJs(p.full_name)}','${escJs(p.phone)}')"
              onmouseover="this.style.background='var(--bg-tertiary)'"
              onmouseout="this.style.background=''">
              <div style="font-weight:500;">${p.full_name}</div>
              <div style="color:var(--text-tertiary);font-size:.75rem;">${p.phone} · ${p.patient_code}</div>
            </div>`
          ).join('');
          patientResults.style.display = 'block';
        });
    }, 250);
  });

  window.selectPatient = function(id, name, phone) {
    state.patientId   = id;
    state.patientName = name;
    patientInput.value = '';
    patientResults.style.display = 'none';

    // Show chip
    patientChipName.textContent = name;
    patientChipAvatar.textContent = name.substring(0,2).toUpperCase();
    patientChip.style.display = 'flex';

    // Enable patient quick prompts
    document.querySelectorAll('.qp-patient').forEach(b => b.disabled = false);

    updateHeader();

    // Auto-inject context message
    appendMessage('assistant',
      `✅ **${name}** linked. I now have their clinical context. You can ask me to summarize, check risks, draft notes, or suggest next steps.`
    );
  };

  window.clearPatientCtx = function() {
    state.patientId   = null;
    state.patientName = null;
    patientChip.style.display = 'none';
    document.querySelectorAll('.qp-patient').forEach(b => b.disabled = true);
    updateHeader();
  };

  // ── Quick prompts ──────────────────────────────────────────
  const patientActions = {
    summary:     'Please give me a full clinical summary for this patient.',
    risks:       'What are the key risks, contraindications, or clinical warnings I should know before treating this patient?',
    nextSteps:   'Based on this patient\'s profile and history, what are your recommended next steps for the clinic?',
    noteSession: 'Draft a professional session note for this patient\'s most recent treatment.',
  };

  document.querySelectorAll('.qp-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.action;
      const prompt = action ? patientActions[action] : btn.dataset.prompt;
      if (prompt) {
        chatInput.value = prompt;
        autoResizeInput();
        chatInput.focus();
        send();
      }
    });
  });

  // ── Input auto-resize ──────────────────────────────────────
  chatInput && chatInput.addEventListener('input', autoResizeInput);
  function autoResizeInput() {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 140) + 'px';
  }

  chatInput && chatInput.addEventListener('focus', () => {
    if (inputWrapper) inputWrapper.style.borderColor = '#7c3aed';
  });
  chatInput && chatInput.addEventListener('blur', () => {
    if (inputWrapper) inputWrapper.style.borderColor = 'var(--border)';
  });

  // ── Send on Enter ──────────────────────────────────────────
  chatInput && chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  });
  sendBtn && sendBtn.addEventListener('click', send);

  // ── Core send ─────────────────────────────────────────────
  function send() {
    const text = chatInput.value.trim();
    if (!text || state.sending) return;

    appendMessage('user', text);
    state.history.push({ role: 'user', content: text });

    chatInput.value = '';
    chatInput.style.height = 'auto';

    // UI: thinking
    state.sending = true;
    sendBtn.style.opacity = '.5';
    typingInd.style.display = 'block';
    statusDot.className = 'thinking';
    statusLabel.textContent = 'Thinking…';
    scrollBottom();

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch('/ai/chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        messages:   state.history,
        provider:   state.provider,
        model:      state.model,
        patient_id: state.patientId,
      }),
    })
    .then(r => r.json())
    .then(data => {
      const reply = data.reply || 'No response received.';
      typingInd.style.display = 'none';
      appendMessage('assistant', reply);
      state.history.push({ role: 'assistant', content: reply });
      statusDot.className = '';
      statusDot.style.background = 'var(--success)';
      statusLabel.textContent = 'Ready';
    })
    .catch(err => {
      typingInd.style.display = 'none';
      appendMessage('assistant', '⚠️ Connection error. Please check your network and try again.');
      statusDot.style.background = 'var(--danger)';
      statusLabel.textContent = 'Error';
      console.error(err);
    })
    .finally(() => {
      state.sending = false;
      sendBtn.style.opacity = '1';
      scrollBottom();
    });
  }

  // ── Render message ─────────────────────────────────────────
  function appendMessage(role, text) {
    const isUser = role === 'user';
    const initials = isUser
      ? '{{ strtoupper(substr(Auth::user()->first_name ?? Auth::user()->name ?? "U", 0, 2)) }}'
      : '✦';

    const row  = document.createElement('div');
    row.className = `msg-row ${isUser ? 'msg-user' : 'msg-assistant'}`;

    const avatar = document.createElement('div');
    avatar.className = `msg-avatar ${isUser ? 'msg-avatar-user' : 'msg-avatar-ai'}`;
    avatar.innerHTML = isUser
      ? `<span style="font-size:.72rem;">${initials}</span>`
      : `<svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>`;

    const bubble = document.createElement('div');
    bubble.className = `msg-bubble ${isUser ? 'msg-bubble-user' : 'msg-bubble-ai'}`;
    bubble.innerHTML = isUser ? escHtml(text) : renderMarkdown(text);

    row.appendChild(avatar);
    row.appendChild(bubble);
    chatMessages.appendChild(row);
    scrollBottom();
  }

  function scrollBottom() {
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }

  // ── Markdown renderer (no external library) ────────────────
  function renderMarkdown(text) {
    // Escape HTML first
    let html = text
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;');

    // Headers
    html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^## (.+)$/gm,  '<h2>$1</h2>');
    html = html.replace(/^# (.+)$/gm,   '<h1>$1</h1>');

    // Bold / italic / inline-code
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.+?)\*/g,     '<em>$1</em>');
    html = html.replace(/`(.+?)`/g,       '<code>$1</code>');

    // HR
    html = html.replace(/^---$/gm, '<hr>');

    // Bullet lists (lines starting with - or •)
    html = html.replace(/^[-•] (.+)$/gm, '<li>$1</li>');
    html = html.replace(/(<li>.*<\/li>(\n|$))+/gs, m => `<ul>${m}</ul>`);

    // Numbered lists
    html = html.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');

    // Blockquote
    html = html.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');

    // Paragraphs (double newlines)
    html = html.replace(/\n\n+/g, '</p><p>');
    html = '<p>' + html + '</p>';

    // Single newlines inside paragraphs → <br>
    html = html.replace(/([^>])\n([^<])/g, '$1<br>$2');

    // Clean up empty paragraphs
    html = html.replace(/<p>\s*<\/p>/g, '');

    return html;
  }

  function escHtml(text) {
    return text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
  }

  function escJs(str) {
    return (str || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
  }

  // Initialise footer
  updateFooter();

  // ── Auto-load patient from URL ─────────────────────────────
  @if(isset($preloadPatient) && $preloadPatient)
  // Pre-load patient passed from patient profile
  setTimeout(() => {
    selectPatient(
      {{ $preloadPatient->id }},
      @json($preloadPatient->full_name),
      @json($preloadPatient->phone ?? '')
    );
  }, 300);
  @endif

})();
</script>
@endpush
