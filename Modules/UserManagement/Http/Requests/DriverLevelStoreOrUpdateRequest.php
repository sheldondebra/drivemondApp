<?php

namespace Modules\UserManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DriverLevelStoreOrUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->id;
        $rewardType = $this->reward_type;
        return [
            'sequence' => [
                Rule::requiredIf(empty($id)),
                'numeric',
                Rule::unique('user_levels', 'sequence')->where('user_type', 'driver')->ignore($id)
            ],
            'name' => [
                Rule::requiredIf(empty($id)),
                Rule::unique('user_levels', 'name')->where('user_type', 'driver')->ignore($id)
            ],
            'reward_type' => [
                Rule::requiredIf(empty($id)),
                'in:no_rewards,wallet,loyalty_points'
            ],
            'reward_amount' => $rewardType == 'no_rewards'? '' : [
                Rule::requiredIf(empty($id)),
                'numeric',
                'gt:0',
            ],
            'targeted_ride' => 'required|numeric|gt:0',
            'targeted_ride_point' => 'required|numeric|gt:0',
            'targeted_amount' => 'required|numeric|gt:0',
            'targeted_amount_point' => 'required|numeric|gt:0',
            'targeted_cancel' => 'required|numeric|gt:0',
            'targeted_cancel_point' => 'required|numeric|gt:0',
            'targeted_review' => 'required|numeric|gt:0',
            'targeted_review_point' => 'required|numeric|gt:0',
            'image' => [
                Rule::requiredIf(empty($id)),
                'image',
                'mimes:png',
                'max:5000']
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::check();
    }
}
