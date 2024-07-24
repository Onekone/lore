<?php

namespace Onekone\Lore\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;


class Hidden implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

    }
}
