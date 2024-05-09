<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidateUserAsset implements ValidationRule
{
    public $model;
    public $userId;

    public function __construct(string $model, int $userId)
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
            $data = resolve($this->model)
                ->when(is_array($value), function($query) use ($value){
                    return $query->whereIn('id', $value);
                })
                ->when(!is_array($value), function($query) use ($value){
                    return $query->where('id', $value);
                })
                ->where('user_id', $this->userId)->get();

            // Check if any item from the request does not exist in the DB
            // or is invalid
            if(!empty(
                array_diff(is_array($value) ? $value : [$value] , $data->pluck('id'))
            )){
                $fail("Invalid data from $attribute was passed.");
            }
        }
    }
}
