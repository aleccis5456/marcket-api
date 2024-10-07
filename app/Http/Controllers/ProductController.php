<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
     //   dd($request);
        $orderBy = $request->query('orderBy');
        $column = $request->query('column') ?? 'created_at';
        $priceMin = $request->query('priceMin');
        $priceMax = $request->query('priceMax');
        $search = $request->query('search');
        $query = Product::query();

        if($orderBy == 'asc'){
            $query->orderBy($column,$orderBy);
            $cantidad = $query->count();
        }else{
            $query->orderByDesc($column);
            $cantidad = $query->count();
        }

        if(!is_null($priceMin)){
            $query->where('price', '>=',$priceMin);
            $cantidad = $query->count();
        }

        if(!is_null($priceMax)){
            $query->where('price', '<=',$priceMax);
            $cantidad = $query->count();
        }

        if(!is_null($search)){
            $query->whereLike('name', "%$search%")->orWhereLike('price', "%$search%");
            $cantidad = $query->count();
        }
        
        $products = $query->paginate(8)->appends([
            'orderBy' => $orderBy, 
            'column' => $column,
            'priceMin' => $priceMin, 
            'priceMax' => $priceMax,
            'search' => $search,
        ]);

        return response()->json([
            'cantidad' => $cantidad ?? null,
            'products' => $products,            
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'stock' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
        ]);

        return response()->json($product, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);

        if(!$product){
            return response()->json(['message' => 'producto no encontrado'], 404);
        }

        return response()->json($product, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'stock' => 'sometimes|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'error',
                'errors' => $validator->errors(),
            ]);
        }

        $product = Product::find($id);

        if(!$product){
            return response()->json(['message' => 'producto no encontrado'], 404);
        }

        $product->update($request->all());

        return response()->json(['message' => 'producto actualizado', $product], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::destroy($id);

        if(!$product){
            return response()->json(['message' => 'producto no encontrado'], 404);
        }

        return response()->json(['message' => 'producto borrado']);
    }
}

