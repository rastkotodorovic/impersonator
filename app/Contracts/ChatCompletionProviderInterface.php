<?php

namespace App\Contracts;

interface ChatCompletionProviderInterface
{
    public function chatCompletionWithMetadata(array $messages, ?string $model = null): array;

    public function modelName(): string;
}
