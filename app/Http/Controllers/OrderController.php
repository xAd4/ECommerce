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
    public function index(Request $request): JsonResponse
    {
        try {
            $orders = Order::where("user_id", $request->user()->id)->with('products')->paginate(10);

            return response()->json([
                "ok" => true,
                "orders" => $orders
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }

    public function checkout(Request $request): JsonResponse
    {
        try {
            $cart = Cart::where("user_id", $request->user()->id)->first();

            if (!$cart || $cart->products->isEmpty()) {
                return response()->json([
                    "ok" => false,
                    "message" => "Cart void"
                ], 422);
            }

            $total = 0;
            foreach ($cart->products as $product) {
                $total += $product->pivot->price * $product->pivot->quantity;
            }
            
            $order = $request->user()->orders()->create([
                'total_price' => $total,
                'status' => 'Pending'
            ]);

            foreach ($cart->products as $product) {
                $order->products()->attach($product->id, [
                    'quantity' => $product->pivot->quantity,
                    'price' => $product->pivot->price
                ]);
                $product->stock -= $product->pivot->quantity;
                $product->save();
            }

            $cart->products()->detach();

            return response()->json([
                "ok" => true,
                "message" => "Order created",
                "order" => $order
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $order = Order::with('products')->findOrFail($id);

            if ($order->user_id != Auth::id()) {
                return response()->json([
                    "ok" => false,
                    "message" => "Unauthorized"
                ], 403);
            }

            return response()->json([
                "ok" => true,
                "order" => $order
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }
}