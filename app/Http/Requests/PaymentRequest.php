<?php

namespace App\Http\Requests;

use auth;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isPost = $this->isMethod('post');
        return [
                'amount' => 'required|numeric',
                'currency' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string',
                'callback_url' => 'required|url'
        ];
    }
}
