<?php

namespace App\Rules;

use App\Support\SsrfGuard;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeWebhookUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! SsrfGuard::isSafeUrl((string) $value)) {
            $fail('The :attribute must be a public http(s) URL — internal, private, and loopback addresses are not allowed.');
        }
    }
}
