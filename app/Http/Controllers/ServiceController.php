<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\Request;
use Storage;

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

    if ($request->hasFile('image_path')) {
        $validated['image_path'] = $request->file('image_path')->store('images', 'public');
    }

    $service = Service::create($validated);

    return response()->json([
        'message' => 'Service Created successfully',
        'service' => new ServiceResource($service->load('category'))
    ]);
}
public function update(Request $request, $id)
{
    $service = Service::find($id);

    $validated = 
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
              'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Optional image validation
      ]);

    if ($request->hasFile('image_path')) {
        // حذف الصورة القديمة
        if ($service->image_path) {
            Storage::disk('public')->delete($service->image_path);
        }

        // حفظ الصورة الجديدة
        $validated['image_path'] = $request->file('image_path')->store('images', 'public');
    }

    $service->updated($validated);
    return response()->json([
        'service' => new ServiceResource($service->load('category'))
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
