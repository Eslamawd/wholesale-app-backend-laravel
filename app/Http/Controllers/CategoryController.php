<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
       public function index()
    {
        return response()->json(['categories' => Category::all()]) ;
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        return Category::create(['name' => $request->name]);
    }

    public function show( $category)
    {
        $category = Category::findOrFail($category);
        return $category->load('services');
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
