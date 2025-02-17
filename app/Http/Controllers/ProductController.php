<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Product::all(), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showBySupplier($supplier_id)
    {
        $supplier = Supplier::find($supplier_id);

        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        return response()->json($supplier->products, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        $product->update($request->validated());

        return response()->json($product, 200);
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
            return response()->json(['error' => "Couldn't find product with ID {$id}"], 404);
        }

        $product->delete();
        return response()->json(['message' => 'Product deleted successfully.'], 200);
    }

    // BONUS
    public function exportBySupplier(Supplier $supplier)
    {
        $filename = str_replace([' ', '/', '\\'], '_', strtolower($supplier->name)) . '_' . now()->format('Y_m_d-H_i') . '.csv';
        $filepath = "exports/$filename";

        $csvContent = "supplier_name,days_valid,priority,part_number,part_desc,quantity,price,condition,category\n";

        foreach ($supplier->products as $product) {
            $csvContent .= "{$supplier->name},{$supplier->days_valid},{$supplier->priority},{$product->part_number},{$product->part_desc},{$product->quantity},{$product->price},{$product->condition},{$product->category}\n";
        }

        Storage::put($filepath, $csvContent);
        return response()->download(storage_path("app/$filepath"));
    }
}
