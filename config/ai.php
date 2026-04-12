<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    | 'anthropic'  — Claude (recommended for clinical reasoning)
    | 'openai'     — GPT-4o / GPT-4o-mini
    |
    | Can be overridden per-request from the UI.
    */
    'default_provider' => env('AI_PROVIDER', 'anthropic'),

    /*
    |--------------------------------------------------------------------------
    | Anthropic (Claude)
    |--------------------------------------------------------------------------
    | Key:   https://console.anthropic.com
    | Models: claude-3-5-haiku-20241022  (fast, cheap)
    |         claude-3-5-sonnet-20241022 (smarter, pricier)
    |         claude-3-opus-20240229     (most capable)
    */
    'anthropic' => [
        'api_key'  => env('ANTHROPIC_API_KEY', ''),
        'model'    => env('ANTHROPIC_MODEL', 'claude-3-5-haiku-20241022'),
        'base_url' => 'https://api.anthropic.com/v1/messages',
        'version'  => '2023-06-01',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI (GPT)
    |--------------------------------------------------------------------------
    | Key:   https://platform.openai.com/api-keys
    | Models: gpt-4o-mini  (fast, cheap)
    |         gpt-4o       (most capable)
    |         gpt-4-turbo  (balanced)
    */
    'openai' => [
        'api_key'  => env('OPENAI_API_KEY', ''),
        'model'    => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => 'https://api.openai.com/v1/chat/completions',
    ],

    /*
    |--------------------------------------------------------------------------
    | Available models shown in the UI switcher
    |--------------------------------------------------------------------------
    */
    'available_models' => [
        'anthropic' => [
            'claude-3-5-haiku-20241022'  => 'Claude 3.5 Haiku (Fast)',
            'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet (Smart)',
            'claude-3-opus-20240229'     => 'Claude 3 Opus (Powerful)',
        ],
        'openai' => [
            'gpt-4o-mini'  => 'GPT-4o Mini (Fast)',
            'gpt-4o'       => 'GPT-4o (Smart)',
            'gpt-4-turbo'  => 'GPT-4 Turbo (Balanced)',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token limits
    |--------------------------------------------------------------------------
    */
    'max_tokens'    => (int) env('AI_MAX_TOKENS', 2048),
    'timeout'       => (int) env('AI_TIMEOUT', 45),
];
