<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
       public function index()
    {
          $categories = Category::parentsOnly()->paginate(6);

    return response()->json(['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        return Category::create(['name' => $request->name]);
    }


public function show(Request $request,$id)
{
    $perPage = 12; // عدد الفئات الفرعية في كل صفحة

    $category = Category::findOrFail($id);

    // الأطفال paginated
    $childrenQuery = Category::where('parent_id', $category->id);
    $children = $childrenQuery->paginate($perPage);

    // المنتجات برضو paginated
    $products = $category->products()->paginate($perPage);

    
    return response()->json([
        'category' => $category,
        'children' => $children,
        'products' => $products,
    ]);
}


    public function update(Request $request,  $category)
    {
        $category = Category::findOrFail($category);
        $request->validate(['name' => 'required|string']);
        $category->update(['name' => $request->name]);
        $category->save();
        return response()->json(['category'=> $category ]);
    }

    public function destroy($category)
    {
        $category = Category::findOrFail($category);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

}
