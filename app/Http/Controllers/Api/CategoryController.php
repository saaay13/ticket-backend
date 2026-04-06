<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreCategoryRequest;

class CategoryController extends Controller
{
    public function index()
    {
        $sort = request()->query('sort', 'name');
        $order = request()->query('order', 'asc');

        $categories = Category::orderBy($sort, $order)->get();
        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());
        return (new CategoryResource($category))
            ->additional(['message' => 'Category created successfully']);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return (new CategoryResource($category))
            ->additional(['message' => 'Category updated successfully']);
    }

    public function destroy(Category $category)
    {
        if (! $category->active) {
            return response()->json(['message' => 'The category is already inactive'], 400);
        }

        $category->update(['active' => false]);
        return response()->json(['message' => 'Category deactivated successfully'], 200);
    }
}

