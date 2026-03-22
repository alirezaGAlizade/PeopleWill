<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOfficialQuestionResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Question $question */
        $question = $this->route('question');

        return $this->user()->can('respondAsOfficial', $question);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
