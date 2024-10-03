<?php

namespace App\Http\Requests\Api\DescriptiveAnalytics;

use App\Http\Requests\ApiBaseRequest;

class GetProductMetricsRequest extends ApiBaseRequest
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
            'start_date' => 'required|date_format:Y-m-d H:i:s',
            'end_date'   => 'required|date_format:Y-m-d H:i:s',
        ];
    }
}
