<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFleetRequest extends FormRequest
{
    use \App\Http\Requests\Concerns\VehicleDetailsRules;
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
        return array_merge($this->vehicleDetailsRules(), [
            'car_brand_model' => 'nullable|required_without_all:make,model|string|max:255',
            'licenceClass' => 'required|exists:licence_classes,id',
            'reg_number' => 'required|string|unique:fleets,car_registration_number|max:20',
            'tracker_device_id' => 'nullable|string|unique:fleets,tracker_device_id|max:50',
            'instructor' => 'required|exists:instructors,id',
            'fleet_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'car_description' => 'nullable|string|max:1000',
            'group_id' => 'nullable|integer|exists:groups,id',
            'contact_phone' => 'nullable|string|max:20',
        ]);
    }

    public function messages()
    {
        return [
            'car_brand_model.required' => 'The car name/brand field is required!',
            'reg_number.required' => 'The car number plate field is required!',
            'reg_number.unique' => 'This car number plate is already registered!',
            'licenceClass.required' => 'Licence class is required',
            'licenceClass.exists' => 'Selected licence class is invalid',
            'instructor.exists' => 'Selected instructor is invalid',
            'tracker_device_id.unique' => 'This tracking device ID is already in use',
            'fleet_image.image' => 'The fleet image must be an image file',
            'fleet_image.mimes' => 'The fleet image must be a file of type: jpeg, png, jpg, gif',
            'fleet_image.max' => 'The fleet image may not be greater than 2MB',
            'car_description.max' => 'The car description may not exceed 1000 characters',
        ];
    }
}
