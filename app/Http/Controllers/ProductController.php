<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Retrieves a paginated list of products with their associated user and category.
     * 
     * @return JsonResponse JSON response containing the list of products or an error message.
     */
    public function index(): JsonResponse
    {
        try {
            $allProducts = Product::with(["user", "category"])->paginate(10);
            return response()->json([
                "ok" => true,
                "products" => $allProducts,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * Validates the request data and creates a new product in the database.
     * The product image is stored in the "products/images" directory.
     * 
     * @param Request $request The HTTP request containing product data.
     * @return JsonResponse JSON response indicating success or failure.
     */
    public function store(Request $request): JsonResponse
    {
        $validate = $request->validate([
            "name" => "required|string|min:3|max:100",
            "description" => "required|string|min:10",
            "price" => "required|numeric|min:0",
            "stock" => "required|integer|min:0",
            "img" => "required|file|mimes:png,jpg,jpeg|max:2048",
            "category_id" => "required|integer",
        ]);

        try {
            $newProduct = Product::create([
                "user_id" => $request->user()->id,
                "name" => $validate["name"],
                "description" => $validate["description"],
                "price" => $validate["price"],
                "stock" => $validate["stock"],
                "is_available" => true,
                "img" => $validate["img"]->store("products/images", "public"),
                "category_id" => $validate["category_id"]]);

                return response()->json([
                    "ok" => true,
                    "message" => "Product added",
                    $newProduct,
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
     * Retrieves a single product by its ID, including its associated user and category.
     * 
     * @param string $id The ID of the product to retrieve.
     * @return JsonResponse JSON response containing the product data or an error message.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = Product::with("user", "category")->findOrFail($id);
            return response()->json([
                "ok" => true,
                "product" => $product,
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
     * Validates the request data and updates the specified product in the database.
     * If a new image is provided, the old image is deleted, and the new one is stored.
     * 
     * @param Request $request The HTTP request containing updated product data.
     * @param string $id The ID of the product to update.
     * @return JsonResponse JSON response indicating success or failure.
     */
    public function update(Request $request, string $id): JsonResponse
    {

        $product = Product::findOrFail($id);

        $validate = $request->validate([
            "name" => "sometimes|string|min:3|max:100",
            "description" => "sometimes|string|min:10|max:500",
            "price" => "sometimes|numeric|min:0",
            "stock" => "sometimes|integer|min:0",
            "img" => "sometimes|file|mimes:png,jpg,jpeg|max:2048",
            "category_id" => "sometimes|integer|exists:categories,id,is_available,true",
        ]);

        try {

            if ($request->hasFile('img')) {
                Storage::disk('public')->delete($product->img);
                $validate['img'] = $request->file('img')->store("products/images", "public");
            }

            $product->update($validate);

            return response()->json([
                "ok" => true,
                "message" => "Product updated",
                $product,
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
     * Deletes the specified product from the database.
     * 
     * @param string $id The ID of the product to delete.
     * @return JsonResponse JSON response indicating success or failure.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            return response()->json([
                "message" => "Product deleted",
            ], 204);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }
}
