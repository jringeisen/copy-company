<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider
    |--------------------------------------------------------------------------
    |
    | The AI provider to use for text generation. Supported providers:
    | openai, anthropic, mistral, ollama, groq, deepseek, xai
    |
    */

    'provider' => env('AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | AI Model
    |--------------------------------------------------------------------------
    |
    | The model to use for text generation. This should match the provider.
    |
    | OpenAI: gpt-4o, gpt-4o-mini, gpt-4-turbo
    | Anthropic: claude-3-5-sonnet-20241022, claude-3-opus-20240229
    | Mistral: mistral-large-latest, mistral-medium-latest
    | Ollama: llama2, codellama, mistral
    |
    */

    'model' => env('AI_MODEL', 'gpt-4o'),

    /*
    |--------------------------------------------------------------------------
    | Max Tokens
    |--------------------------------------------------------------------------
    |
    | The maximum number of tokens to generate in completions.
    |
    */

    'max_tokens' => env('AI_MAX_TOKENS', 4096),

];
