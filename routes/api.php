<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/* Auth Routes */
Route::middleware("throttle:auth")->group(function(){
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);
    
    Route::middleware("auth:sanctum")->group(function() {
        Route::post("logout", [AuthController::class, "logout"]);
    });
});

/* ECommerce Routes */
Route::middleware("throttle:api")->group(function(){
    Route::middleware("auth:sanctum")->group(function(){
        Route::apiResource("categories", CategoryController::class);
        Route::apiResource("products", ProductController::class);
    
        // Cart
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/add/{product}', [CartController::class, 'addProduct']);
        Route::delete('/cart/remove/{product}', [CartController::class, 'removeProduct']);
        Route::delete('/cart/clear', [CartController::class, 'clear']);
        
        // Order
        Route::post('/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
    });
});

