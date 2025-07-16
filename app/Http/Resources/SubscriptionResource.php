<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'duration'   => $this->duration,
            'status'     => $this->status,
            'starts_at'  => $this->starts_at,
            'ends_at'    => $this->ends_at,
            'created_at' => $this->created_at,

            'product' => [
                'id'           => $this->product->id,
                'name_ar' => $this->product->name_ar,
                'description'  => $this->product->description,
                'image'        => $this->product->image,
                'price'        => $this->product->price,
                'price_whalesale'   => $this->product->price_whalesale,

           ]
        ];
    }
}
