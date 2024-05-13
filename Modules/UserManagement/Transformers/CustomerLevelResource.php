<?php

namespace Modules\UserManagement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerLevelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'name' => $this->name,
            'reward_type' => $this->reward_type,
            'reward_amount' => $this->reward_amount,
            'image' => $this->image,
            'min_ride' => $this->min_ride,
            'min_ride_point' => $this->min_ride_point,
            'min_spend' => $this->min_spend,
            'min_spend_point' => $this->min_spend_point,
            'min_cancel' => $this->min_cancel,
            'min_cancel_point' => $this->min_cancel_point,
            'review_given' => $this->review_given,
            'review_given_point' => $this->review_given_point,
            'user_type' => $this->user_type,
            'is_active' => $this->is_active
        ];
    }
}
