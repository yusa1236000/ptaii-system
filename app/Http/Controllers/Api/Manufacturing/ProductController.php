<?php

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\Manufacturing\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::with(['category', 'unitOfMeasure'])->get();
        return response()->json(['data' => $products]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string|max:50|unique:Product,product_code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'required|integer|exists:ItemCategory,category_id',
            'uom_id' => 'required|integer|exists:UnitOfMeasure,uom_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product = Product::create($request->all());
        return response()->json(['data' => $product, 'message' => 'Product created successfully'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with(['category', 'unitOfMeasure'])->find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        
        return response()->json(['data' => $product]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'product_code' => 'sometimes|required|string|max:50|unique:Product,product_code,' . $id . ',product_id',
            'name' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'category_id' => 'sometimes|required|integer|exists:ItemCategory,category_id',
            'uom_id' => 'sometimes|required|integer|exists:UnitOfMeasure,uom_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $product->update($request->all());
        return response()->json(['data' => $product, 'message' => 'Product updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        // Check if product is being used in BOMs, Routings, or Work Orders
        if ($product->boms()->count() > 0 || $product->routings()->count() > 0 || $product->workOrders()->count() > 0) {
            return response()->json(['message' => 'Cannot delete product. It is being used in BOMs, Routings, or Work Orders.'], 400);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }
}