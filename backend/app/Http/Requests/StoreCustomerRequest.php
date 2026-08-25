<?php

namespace App\Http\Requests;

use App\Enums\Priority;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'service' => ['required', 'string', 'max:255'],
            'arrival_at' => ['required', 'date', 'before_or_equal:now'],
            'original_priority' => ['required', Rule::enum(Priority::class)],
        ];
    }
    
    }
