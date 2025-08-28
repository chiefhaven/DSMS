<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorebulkAttendanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'bulkAttendanceDescription' => 'nullable|string|max:255',
            'students' => 'required|array|min:1',
            'students.*.lessonDate' => 'nullable|date',
            'students.*.instructorId' => 'nullable|uuid',
        ];
    }
}
