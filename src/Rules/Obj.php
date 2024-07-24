<?php

namespace Onekone\Lore\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule that makes it so that it's insisted that field under validation is an object
 */
class Obj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

    }
}
