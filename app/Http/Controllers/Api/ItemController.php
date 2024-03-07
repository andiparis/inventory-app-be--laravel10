<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $items = Item::all();

      return new InventoryResource(true, 'List items data', $items);
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
        'item_code'     => 'required|max:10|unique:items',
        'name'          => 'required|max:75',
        'price'         => 'required|numeric',
        'min_stock'     => 'required|numeric|min:1',
        'description'   => 'max:255',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      $item = Item::create([
        'item_code'     => $request->item_code,
        'name'          => $request->name,
        'price'         => $request->price,
        'min_stock'     => $request->min_stock,
        'description'   => $request->description,
      ]);

      return new InventoryResource(true, 'New item has been saved', $item);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(Item $item)
  {
    try {
      return new InventoryResource(true, 'Found item data', $item);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Item $item)
  {
    try {
      $validation = Validator::make($request->all(), [
        'name'          => 'required|max:75',
        'price'         => 'required|numeric',
        'min_stock'     => 'required|numeric|min:1',
        'description'   => 'max:255',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      $item->update([
        'name'          => $request->name,
        'price'         => $request->price,
        'min_stock'     => $request->min_stock,
        'description'   => $request->description,
      ]);

      return new InventoryResource(true, 'Selected item has been updated', $item);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Item $item)
  {
    try {
      Item::destroy($item->id);

      return new InventoryResource(true, 'Selected item has been deleted', null);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }
}
