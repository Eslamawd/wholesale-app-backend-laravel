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
            'image_path' => $this->image_path,
            'zddk_product_id'=> $this->zddk_product_id,
            'is_zddk_product'=> (bool) $this->is_zddk_product, // تحويل إلى boolean
            'product_type'        => $this->product_type,
            'zddk_required_params' => json_decode($this->zddk_required_params, true), // فك ترميز JSON
            'zddk_qty_values'     => json_decode($this->zddk_qty_values, true),     // فك ترميز JSON
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
