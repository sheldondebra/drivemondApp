<?php

namespace Modules\ChattingManagement\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;

class ChannelListResource extends JsonResource
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
            'trip_id' => $this->channelable_id,
            'updated_at' => $this->updated_at,
            'channel_users' => ChannelUserResource::collection($this->whenLoaded('channel_users')),
            'last_channel_conversations' => $this->whenLoaded('last_channel_conversations')
        ];
    }
}
