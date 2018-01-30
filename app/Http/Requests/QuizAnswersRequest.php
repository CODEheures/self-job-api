<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Http\FormRequest;

class QuizAnswersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(Guard $auth)
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
            'id' => 'required|integer|exists:adverts,id',
            'email' => 'required|email',
            'phone' => ['required', 'regex:/^(\d\d.){4}(\d\d)$/'],
            'answers' => 'required|array'
        ];
    }
}
