<?php

namespace App\Http\Requests;

use App\Enums\EffectiveArea;
use App\Enums\QuestionStatus;
use App\Models\OfficialRole;
use App\Models\Question;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Question $question */
        $question = $this->route('question');

        return $this->user()?->can('update', $question) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Question $question */
        $question = $this->route('question');

        $rules = [
            'official_role_id' => [
                'required',
                'integer',
                'exists:official_roles,id',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $officialRole = OfficialRole::query()->find((int) $value);

                    if ($officialRole === null || ! $officialRole->isWindowOpen()) {
                        $fail('The selected official role is not in an open question window.');
                    }
                },
            ],
            'effective_area' => ['required', new Enum(EffectiveArea::class)],
            'province_id' => [
                'nullable',
                'integer',
                'prohibited_if:effective_area,public',
                'required_if:effective_area,province',
                'required_if:effective_area,city',
                'exists:provinces,id',
            ],
            'city_id' => [
                'nullable',
                'integer',
                'prohibited_if:effective_area,public',
                'prohibited_if:effective_area,province',
                'required_if:effective_area,city',
                Rule::exists('cities', 'id')->where(function ($query) {
                    $provinceId = $this->input('province_id');

                    if ($provinceId === null || $provinceId === '') {
                        return $query->whereRaw('1 = 0');
                    }

                    return $query->where('province', (int) $provinceId);
                }),
            ],
        ];

        if ($question->status === QuestionStatus::Incomplete) {
            $rules['body'] = ['required', 'string', 'max:1000'];
        }

        return $rules;
    }
}
