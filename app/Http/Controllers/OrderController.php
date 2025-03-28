<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a paginated list of orders for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Fetch orders for the authenticated user with related products
            $orders = Order::where("user_id", $request->user()->id)
                ->with('products')
                ->paginate(10);

            return response()->json([
                "ok" => true,
                "orders" => $orders,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle the checkout process for the authenticated user's cart.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkout(Request $request): JsonResponse
    {
        try {
            // Retrieve the cart for the authenticated user
            $cart = Cart::where("user_id", $request->user()->id)->first();

            // Check if the cart is empty
            if (!$cart || $cart->products->isEmpty()) {
                return response()->json([
                    "ok" => false,
                    "message" => "Cart void",
                ], 422);
            }

            // Calculate the total price of the cart
            $total = 0;
            foreach ($cart->products as $product) {
                $total += $product->pivot->price * $product->pivot->quantity;
            }

            // Create a new order for the user
            $order = $request->user()->orders()->create([
                'total_price' => $total,
                'status' => 'Pending',
            ]);

            // Attach products to the order and update product stock
            foreach ($cart->products as $product) {
                $order->products()->attach($product->id, [
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price,
                ]);
                $product->stock -= $product->pivot->quantity;
                $product->save();
            }

            // Clear the cart
            $cart->products()->detach();

            return response()->json([
                "ok" => true,
                "message" => "Order created",
                "order" => $order,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the details of a specific order for the authenticated user.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            // Fetch the order with related products
            $order = Order::with('products')->findOrFail($id);

            // Check if the order belongs to the authenticated user
            if ($order->user_id != Auth::id()) {
                return response()->json([
                    "ok" => false,
                    "message" => "Unauthorized",
                ], 403);
            }

            return response()->json([
                "ok" => true,
                "order" => $order,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage(),
            ], 500);
        }
    }
}