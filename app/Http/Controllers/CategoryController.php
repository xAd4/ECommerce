<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * Retrieves all categories, separating them into available and unavailable.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $categoriesAvailable = Category::where("is_available", true)->get();
            $categoriesUnavailable = Category::where("is_available", false)->get();
            return response()->json([
                "ok" => true,
                "categoriesAvailable" => $categoriesAvailable,
                "categoriesUnavailable" => $categoriesUnavailable,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
                "categoriesAvailable" => [],
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * 
     * Validates the request and creates a new category.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            "name" => "required|string|min:3|max:100|unique:categories,name",
        ]);

        try {
            $newCategory = Category::create([
                "name" => $validated["name"],
            ]);

            return response()->json([
                "ok" => true,
                "message" => "Category created successfully",
                $newCategory,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * 
     * Retrieves a single category by its ID.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            return response()->json([
                "ok" => true,
                "category" => $category,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * 
     * Validates the request and updates the category with the given ID.
     * 
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            "name" => "sometimes|string|min:3|max:100|unique:categories,name,$id",
        ]);

        try {
            $category = Category::findOrFail($id);
            $category->update([
                "name" => $validated["name"],
            ]);

            return response()->json([
                "ok" => true,
                "message" => "Category updated successfully",
                $category,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * Marks the category as unavailable instead of deleting it.
     * 
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $category = Category::findOrFail($id);
            $category->update(["is_available" => false]);
            return response()->json([
                "ok" => true,
                "message" => "Category deleted successfully",
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }
}
