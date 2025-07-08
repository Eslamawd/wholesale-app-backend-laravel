<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->paginate(12); 
        return response()->json(['products' => $products]);
    }
   public function getByCat($id)
{
    $category = Category::findOrFail($id); 
    
    $products = Product::where('category_id', $category->id)
                ->with('category')
                ->paginate(12);

    return response()->json([
        'products' => $products,
        'category' => $category
    ]);
}


    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json(['product' => $product]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'external_id' => 'required|integer|unique:products,external_id',
            'category_external_id' => 'required|integer',
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'image' => 'nullable|url',
            'price' => 'required|numeric',
            'quantity' => 'nullable|integer',
            'description' => 'nullable|string',
            'manage_stock' => 'required|boolean',
            'user_fields' => 'nullable|array',
        ]);

        $category = Category::where('external_id', $validated['category_external_id'])->first();
        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $validated['category_id'] = $category->id;

        $product = Product::create($validated);

        return response()->json(['product' => $product, 'message' => 'Product created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name_ar' => 'sometimes|string',
            'name_en' => 'sometimes|string',
            'image' => 'sometimes|url',
            'price' => 'sometimes|numeric',
            'quantity' => 'sometimes|integer',
            'description' => 'nullable|string',
            'manage_stock' => 'sometimes|boolean',
            'user_fields' => 'nullable|array',
            'category_external_id' => 'sometimes|integer',
        ]);

        if (isset($validated['category_external_id'])) {
            $category = Category::where('external_id', $validated['category_external_id'])->first();
            if ($category) {
                $validated['category_id'] = $category->id;
            }
        }

        $product->update($validated);

        return response()->json(['product' => $product, 'message' => 'Product updated successfully']);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
