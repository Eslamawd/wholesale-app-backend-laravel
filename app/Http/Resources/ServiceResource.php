<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage; // Ensure this is imported

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
            'service_name' => $this->service_name,
            'description' => $this->description,
            'price'       => $this->price,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
            'email'        => optional(auth()->user())->role === 'admin' ? $this->email : null,
            'password'     => optional(auth()->user())->role === 'admin' ? $this->password : null,
            
        ];
    }
}