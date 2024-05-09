<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateUserAsset implements ValidationRule
{
    public $model;
    public $userId;

    public function __construct(string $model, array $userId)
    {
        $this->model = $model;
        $this->userId = $userId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (isset($value)) {
            $data = resolve($this->model)->whereIn('id', $value)->where('user_id', $this->userId)->get();
            if(!empty(array_diff($value, $data->pluck('id')))){
                $fail('Invalid data from :attribute was passed.');
            }
        }
    }
}
