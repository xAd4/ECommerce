<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        try {
            $cart = Cart::where("user_id", $request->user()->id)->first();

            if (!$cart) {
                return response()->json([
                    "ok" => true,
                    "message" => "Cart void",
                    "products" => []
                ], 200);
            }

            $products = $cart->products()->with('category')->get();

            return response()->json([
                "ok" => true,
                "cart" => [
                    "id" => $cart->id,
                    "products" => $products
                ]
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }

    public function addProduct(Request $request, string $id): JsonResponse
    {
        $request->validate([
            "quantity" => "required|integer|min:1"
        ]);

        try {
            $cart = Cart::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['user_id' => $request->user()->id]
            );

            $product = Product::findOrFail($id);

            $cart->products()->syncWithoutDetaching([
                $product->id => [
                    'quantity' => $request->quantity,
                    'price' => $product->price
                ]
            ]);

            return response()->json([
                "ok" => true,
                "message" => "Product added to cart",
                "cart" => [
                    "id" => $cart->id,
                    "products" => $cart->products()->with('category')->get()
                ]
            ], 201);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }

    public function removeProduct(Request $request, string $id): JsonResponse
    {
        try {
            $cart = Cart::where("user_id", $request->user()->id)->first();
            
            if ($cart) {
                $cart->products()->detach($id);
            }

            return response()->json([
                "ok" => true,
                "message" => "Producto deleted to cart",
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }

    public function clear(Request $request): JsonResponse
    {
        try {
            $cart = Cart::where("user_id", $request->user()->id)->first();
            
            if ($cart) {
                $cart->products()->detach();
            }

            return response()->json([
                "ok" => true,
                "message" => "Cart cleared",
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                "ok" => false,
                "message" => $th->getMessage()
            ], 500);
        }
    }
}

/* 
# EXPLICACIÓN DE CÓDIGO


### 1. `Cart::where("user_id", $request->user()->id)->first()`
**Qué hace:**
- Busca el carrito asociado al usuario autenticado.
- `where()`: Filtra los carritos por el `user_id`.
- `first()`: Devuelve el **primer** registro que coincida (o `null` si no existe).

**¿Es indispensable el `first()`?**  
✅ **Sí**, porque:
- Un usuario solo debe tener un carrito activo.
- Sin `first()` obtendrías una colección (aunque haya 1 registro), y no podrías acceder a métodos de modelo como `products()`.

---

### 2. `$cart->products()->with('category')->get()`
**Qué hace:**
- Obtiene los productos del carrito con sus categorías.
- `products()`: Relación muchos-a-muchos (tabla pivot `cart_product`).
- `with('category')`: Carga la relación `category` de cada producto (**evita el problema N+1**).
- `get()`: Ejecuta la consulta y devuelve una colección de resultados.

**¿Por qué usar `get()`?**  
✅ **Es necesario** para obtener los datos. Sin `get()` obtendrías un objeto `Builder` (consulta no ejecutada).

---

### 3. `syncWithoutDetaching()`
**Qué hace:**
- Sincroniza los productos en la tabla pivot **sin eliminar los existentes**.
- Si el producto ya está en el carrito: Actualiza los campos pivot (`quantity`, `price`).
- Si no existe: Lo agrega.

**Ejemplo:**  
```php
$cart->products()->syncWithoutDetaching([
    1 => ['quantity' => 2, 'price' => 100],
    2 => ['quantity' => 1, 'price' => 50]
]);
```

**Alternativas:**
- `attach()`: Agrega sin verificar duplicados.
- `sync()`: Reemplaza todos los productos (elimina los que no están en la lista).

**¿Es indispensable?**  
✅ **Sí**, si quieres:
- Evitar duplicados.
- Actualizar cantidades si el producto ya existe en el carrito.

---

### 4. `detach($id)`
**Qué hace:**
- Elimina la relación entre el carrito y un producto específico en la tabla pivot.

**Ejemplo:**  
```php
$cart->products()->detach(5); // Elimina el producto con ID 5
```

**¿Es indispensable?**  
✅ **Sí**, para:
- Quitar productos individuales del carrito.
- Mantener limpia la tabla pivot.

---

### **Tabla Comparativa: Métodos de Relaciones**

| Método               | Uso Recomendado                          | Ejemplo                          |
|----------------------|------------------------------------------|----------------------------------|
| `syncWithoutDetaching`| Actualizar/Agregar sin perder datos      | Actualizar cantidad de producto |
| `detach`             | Eliminar un producto específico          | Quitar item del carrito          |
| `attach`             | Agregar sin verificar duplicados         | Añadir producto nuevo            |
| `sync`               | Reemplazar todos los productos           | Vaciar y agregar nuevos          |

---

### **¿Por qué tu código falló antes?**
El error ocurría porque al usar `firstOrCreate()` sin especificar el `user_id` en la creación, Laravel intentaba crear un carrito sin ese campo (que es obligatorio en la base de datos).

**Solución aplicada:**  
```php
Cart::firstOrCreate(
    ['user_id' => $user->id], // Buscar por esto
    ['user_id' => $user->id]  // Crear con esto si no existe
);
```

---

### **Conclusión:**
- **`first()`**: Imprescindible para obtener un único carrito.
- **`get()`**: Necesario para ejecutar consultas de relaciones.
- **`syncWithoutDetaching`**: Clave para manejar items duplicados.
- **`detach()`**: Esencial para eliminar productos del carrito.

¡Estos métodos son fundamentales para el funcionamiento básico de un carrito de compras! 🛒

*/