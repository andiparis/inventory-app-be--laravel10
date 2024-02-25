<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\Item;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StockController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index($type)
  {
    try {
      $stocks = Stock::where('type', $type)->get();

      return new InventoryResource(true, "List stocks $type", $stocks);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    try {
      $validation = Validator::make($request->all(), [
        'item_id'       => 'required',
        'type'          => 'required|max:3',
        'detail'        => 'max:100',
        'quantity'      => 'required|numeric|min:1',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      $itemId = $request->item_id;
      $type = $request->type;
      $quantity = $request->quantity;
      $item = Item::find($itemId);
      $updatedStock = $item->stock;

      if ($type == 'out') {
        if ($updatedStock < $quantity) {
          return response()->json([
            'success' => false,
            'message' => 'The initial item stock is less than the requested quantity',
          ], 422);
        }

        $updatedStock -= $quantity;
      } else {
        $updatedStock += $quantity;
      }

      $stock = Stock::create([
        'item_id'       => $itemId,
        'type'          => $type,
        'detail'        => $request->detail,
        'quantity'      => $quantity,
      ]);

      $item->update([
        'stock'   => $updatedStock,
      ]);

      return new InventoryResource(true, 'New item stock has been saved', $stock);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Stock $stock)
  {
    try {
      $validation = Validator::make($request->all(), [
        'item_id'       => 'required',
        'type'          => 'required|max:3',
        'detail'        => 'max:100',
        'quantity'      => 'required|numeric',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      $prevItemId = $stock->item_id;
      $prevType = $stock->type;
      $prevStock = $stock->quantity;
      $item = Item::find($prevItemId);
      $updatedStock = $item->stock;

      $newItemId = $request->item_id;
      $newType = $request->type;
      $newQuantity = $request->quantity;

      if ($prevItemId != $newItemId) {
        return response()->json([
          'success' => false,
          'message' => 'Previous item does not match with the requested item',
        ], 422);
      }

      // Update stock based on type change
      if ($prevType != $newType) {
        // Undo previous stock change
        if ($prevType == 'out') {
          $updatedStock += $prevStock;
        } else {
          $updatedStock -= $prevStock;
        }

        // Adjust for new stock change
        if ($newType == 'out') {
          if ($updatedStock < $newQuantity) {
            return response()->json([
              'success' => false,
              'message' => 'The initial item stock is less than the requested quantity',
            ], 422);
          }

          $updatedStock -= $newQuantity;
        } else {
          $updatedStock += $newQuantity;
        }
      } else {
        // Update stock without changing type
        if ($newType == 'out') {
          if (($updatedStock + $prevStock) < $newQuantity) {
            return response()->json([
              'success' => false,
              'message' => 'The initial item stock is less than the requested quantity',
            ], 422);
          }

          $updatedStock = $updatedStock + $prevStock - $newQuantity;
        } else {
          $updatedStock = $updatedStock - $prevStock + $newQuantity;
        }
      }

      $stock->update([
        'item_id'       => $newItemId,
        'type'          => $newType,
        'detail'        => $request->detail,
        'quantity'      => $newQuantity,
      ]);

      $item->update([
        'stock'   => $updatedStock,
      ]);

      return new InventoryResource(true, 'Selected item stock has been updated', $stock);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Stock $stock)
  {
    try {
      $item = Item::find($stock->item_id);
      $updatedStock = $item->stock;

      if ($stock->type == 'out') {
        $updatedStock += $stock->quantity;
      } else {
        $updatedStock -= $stock->quantity;
      }

      $item->update([
        'stock'   => $updatedStock,
      ]);

      Stock::destroy($stock->id);

      return new InventoryResource(true, 'Selected item stock has been deleted', null);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }
}
