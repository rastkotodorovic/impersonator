<?php

namespace App\Http\Middleware;

class ValidatePostSize extends \Illuminate\Http\Middleware\ValidatePostSize
{
    protected function getPostMaxSize(): int
    {
        $configuredSize = env('APP_POST_MAX_SIZE', '5G');

        if (is_numeric($configuredSize)) {
            return (int) $configuredSize;
        }

        $metric = strtoupper(substr($configuredSize, -1));
        $postMaxSize = (int) $configuredSize;

        return match ($metric) {
            'K' => $postMaxSize * 1024,
            'M' => $postMaxSize * 1048576,
            'G' => $postMaxSize * 1073741824,
            default => $postMaxSize,
        };
    }
}
