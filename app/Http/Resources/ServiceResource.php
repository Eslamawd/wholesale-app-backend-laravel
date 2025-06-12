<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'        => $this->title,
            'description' => $this->description,
            'price'       => $this->price,
            'category_id' => $this->category_id,
            'image_path'  => $this->image_path,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
