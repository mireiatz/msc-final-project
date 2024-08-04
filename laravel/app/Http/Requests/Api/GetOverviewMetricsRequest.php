<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\ApiBaseRequest;

class GetOverviewMetricsRequest extends ApiBaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => 'required|string',
        ];
    }
}
