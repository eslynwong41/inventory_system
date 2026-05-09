<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;


class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Cache::remember('categories:all', 600, function () {
            return Category::active()->withCount('products')->get();
        });

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $category = Category::create($data);
        Cache::forget('categories:all');

        return response()->json([
            'message' => 'Category created successfully.',
            'data'    => new CategoryResource($category),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        $category->loadCount('products');

        return response()->json(['data' => new CategoryResource($category)]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:100', 'unique:categories,name,' . $category->id],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $category->update($data);
        Cache::forget('categories:all');

        return response()->json([
            'message' => 'Category updated successfully.',
            'data'    => new CategoryResource($category),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category with associated products.',
            ], 422);
        }

        $category->delete();
        Cache::forget('categories:all');

        return response()->json(['message' => 'Category deleted successfully.']);
    }
}