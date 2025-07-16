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
        'name_ar'            => 'required|string',
        'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // ← أفضل من url
        'price'              => 'required|numeric',
        'price_wholesale'    => 'required|numeric',
        'quantity'           => 'nullable|integer',
        'description'        => 'nullable|string',
        'subscription'       => 'required|in:true,false,1,0',
        'category_id'        => 'required|exists:categories,id', // ← إضافي لضمان وجود الفئة
    ]);

    $validated['subscription'] = filter_var($validated['subscription'], FILTER_VALIDATE_BOOLEAN);


    
    // رفع الصورة لو موجودة
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('products', 'public'); // غيّرت المجلد إلى 'products'
        $validated['image'] = $imagePath;
    }
   
    // جلب الفئة والتأكد من وجودها
    $category = Category::findOrFail($validated['category_id']);

    // توليد external_id إذا لم يكن موجود
    $validated['external_id'] = uniqid();
    $validated['category_external_id'] = $category->external_id;

    // إنشاء المنتج
    $product = Product::create($validated);

    return response()->json([
        'product' => $product,
        'message' => 'Product created successfully'
    ], 201);
}


   public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'name_ar'             => 'sometimes|string',
        'image'               => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:2048',
        'price'               => 'sometimes|numeric',
        'price_wholesale'     => 'sometimes|numeric',
        'quantity'            => 'sometimes|integer',
        'description'         => 'nullable|string',
        'subscription'        => 'sometimes|in:true,false,1,0',
    ]);

    // تحويل subscription إلى boolean لو موجودة
    if (isset($validated['subscription'])) {
        $validated['subscription'] = filter_var($validated['subscription'], FILTER_VALIDATE_BOOLEAN);
    }

    // رفع صورة جديدة لو موجودة
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('products', 'public');
        $validated['image'] = $imagePath;
    }

    // ربط category_id من category_external_id لو أُرسلت
    if (isset($validated['category_id'])) {
        $category = Category::findOrFail($validated['category_id']);
        if ($category) {
            $validated['category_id'] = $category->id;
        }
    }

    $product->update($validated);

    return response()->json([
        'product' => $product,
        'message' => 'Product updated successfully'
    ]);
}


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
