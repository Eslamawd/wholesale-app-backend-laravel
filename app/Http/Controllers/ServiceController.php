<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;

use App\Http\Requests\ServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Validation\ValidationException; 
use Illuminate\Http\Request;

class ServiceController extends Controller
{
       public function index()
    {
        $services = Service::with('category')->get();
        return response()->json([
            'services' => ServiceResource::collection($services)
        ]);
    }
public function store(ServiceRequest $request)
{
    $validated = $request->validated();

    // التحقق من وجود الصورة
    if ($request->hasFile('image_path')) {
        // تخزين الصورة داخل مجلد images في قرص public
        $validated['image_path'] = $request->file('image_path')->store('images', 'public');
    }

    // إنشاء الخدمة
    $service = Service::create($validated);

    return response()->json([
        'message' => 'Service created successfully.',
        'service' => new ServiceResource($service->load('category'))
    ], 201); // كود الحالة 201 يعني "تم الإنشاء"
}



public function update(Request $request, $id)
{
  
    $service = Service::find($id);

    if (!$service) {

        return response()->json(['message' => 'Service not found'], 404);
    }


    try {
        // Use validate for rules, but note it might not pick up all fields from multipart/form-data for PATCH
        // You might need to merge or specifically access fields.
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // --- NEW: Manually gather fields for update ---
        // Create an array for fields to be updated,
        // prioritizing data from the request if present.
        $updateData = [];

        if ($request->has('title')) {
            $updateData['title'] = $request->input('title');
        }
        if ($request->has('description')) {
            $updateData['description'] = $request->input('description');
        }
        if ($request->has('price')) {
            $updateData['price'] = $request->input('price');
        }
        if ($request->has('category_id')) {
            $updateData['category_id'] = $request->input('category_id');
        }
        // --- END NEW ---

      
    } catch (ValidationException $e) { // Make sure to import ValidationException
        return response()->json(['errors' => $e->errors()], 422);
    }


    if ($request->hasFile('image_path')) {
        // حذف الصورة القديمة
        if ($service->image_path) {
            // Check if the old image is a local path, not an external URL
            if (strpos($service->image_path, 'http') !== 0) { // If it doesn't start with http
                Storage::disk('public')->delete($service->image_path);
            } 
        }

        // حفظ الصورة الجديدة
        $imagePath = $request->file('image_path')->store('images', 'public');
        $updateData['image_path'] = $imagePath; // Add new image path to update data
  
    }

    // Now, update using the manually collected data
    $service->update($updateData); // Use $updateData here
    return response()->json([
        'service' => new ServiceResource($service->load('category')),
        'message' => 'Service updated successfully!' // Added a message for frontend toast
    ]);
}

    public function show( $id)
    {
        $service = Service::findOrFail($id);
        $service->load('category');
        return response()->json(['service'=> $service]);
    }

    public function destroy( $id)
    {
        $service = Service::findOrFail($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }
        $service->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
