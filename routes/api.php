<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Ruta para obtener el usuario autenticado
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/* 
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
| Routes related to user authentication.
| Includes registration, login, and logout.
*/
Route::middleware("throttle:auth")->group(function () {
    // Registro y login
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);

    // Logout (requiere autenticación)
    Route::middleware("auth:sanctum")->group(function () {
        Route::post("logout", [AuthController::class, "logout"]);
    });
});

/* 
|--------------------------------------------------------------------------
| ECommerce Routes
|--------------------------------------------------------------------------
| Routes related to e-commerce functionalities.
| Includes categories, products, cart, and orders.
*/
Route::middleware("throttle:api")->group(function () {
    Route::middleware("auth:sanctum")->group(function () {
        // Categories
        Route::apiResource("categories", CategoryController::class);

        // Products
        Route::apiResource("products", ProductController::class);

        // Carts
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/add/{product}', [CartController::class, 'addProduct']);
        Route::delete('/cart/remove/{product}', [CartController::class, 'removeProduct']);
        Route::delete('/cart/clear', [CartController::class, 'clear']);

        // Orders
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });
});

