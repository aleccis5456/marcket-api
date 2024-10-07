<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\OrderProduct;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {   
        $user_id = $request->user()->id;                
        $orders = Order::where('user_id',$user_id)->with('products')->get();
        $count = Order::where('user_id',$user_id)->with('products')->count();
        
        return response()->json([
            'cantidad' => $count,
            'orders' => $orders,        
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {                
        $validator = Validator::make($request->all(), [            
            'products' => 'required|array|min:1',            
            'products.*.id' => 'required|exists:products,id',            
            'products.*.quantity' => 'required|integer|min:1',            
        ]);        
        
        if($validator->fails()){
            return response()->json([
                'message' => 'error',
                'errors' => $validator->errors(),
            ]);
        }                           

        DB::beginTransaction();
        try{            
            $ordenes = [];
            
            $orderProducts = [];
            $products = json_decode(json_encode($request->products));     
            foreach($products as $item){ 
                $total = 0;
                $product = Product::find($item->id);                  
                if(!$product){
                    return response()->json(['error'=>'product not found'], 404);
                }
                if($item->quantity > $product->stock){
                    return response()->json(['error'=>"el producto $product->name no tiene suficiente stock"], 400);
                }                
                $total += $product->price * $item->quantity;
                
                $orderProducts[$product->id] = [
                    'name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $item->quantity
                ];                
                
                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'total' => $total,
                    'status' => 'pending',
                ]);
                
                $orderProducts = $this->storeOrderProducts($order, $orderProducts);
                $product->decrement('stock', $item->quantity);                
                
                $ordenes[] = [$order];
            }
            DB::commit(); 
            return response()->json([
                'message' => 'orden creado',
                'detalle' => $ordenes,
            ]);  
        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }
    /**
     * Agrega datos a la tabla order_products
     */
    public function storeOrderProducts(Order $order, array $orderProducts){
        $orderProducts = json_decode(json_encode($orderProducts));                
        foreach($orderProducts as $index => $item){               
            $orderProduct = OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $index,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        }        
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $order = Order::find($id);
        if(!$order){
            return response()->json(['error' => 'order not found'], 404);
        }

        return response()->json($order. 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:pending,completed,cancelled'
        ]);

        if($validator->fails()){
            return response()->json(['error' => $validator->errors()], 400);
        }

        $order = Order::find($id);

        if(!$order){
            return response()->json([
                'error' => 'order not found'
            ], 404);
        }

        return response()->json(['message' => 'order updated', $order], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $order = Order::destroy($id);

        if(!$order){
            return response()->json(['error' => 'order not found'], 404);
        }

        return response()->json(['message' => 'order deleted']);
    }
}
