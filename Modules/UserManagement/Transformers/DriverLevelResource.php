<?php

namespace Modules\UserManagement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverLevelResource extends JsonResource
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
            'min_earn' => $this->min_earn,
            'min_earn_point' => $this->min_earn_point,
            'max_cancel' => $this->max_cancel,
            'max_cancel_point' => $this->max_cancel_point,
            'review_received' => $this->review_received,
            'review_received_point' => $this->review_received_point,
            'user_type' => $this->user_type,
            'is_active' => $this->is_active
        ];
    }
}
