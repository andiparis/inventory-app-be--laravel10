<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryResource;
use App\Models\Delivery;
use App\Models\DeliveryDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeliveryController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index()
  {
    try {
      $delivery = Delivery::all();

      return new InventoryResource(true, 'List deliveries data', $delivery);
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
        'order_code'        => 'required|max:12',
        'date'              => 'required|date',
        'status'            => 'required|max:25',
        'detail'            => 'max:100',
        'items'             => 'required|array',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      foreach ($request->items as $key => $item) {
        $validation = Validator::make($item, [
          'item_id'   => 'required',
          'quantity'  => 'required|numeric|min:1',
        ]);

        if ($validation->fails()) {
          return response()->json([
            'success' => false,
            'message' => $validation->errors(),
            'extra'   => "The validation errors is in row $key",
          ], 422);
        }
      }

      $delivery = Delivery::create([
        'order_code'    => $request->order_code,
        'date'          => $request->date,
        'status'        => $request->status,
        'detail'        => $request->detail,
      ]);


      $deliveryDetails = [];
      foreach ($request->items as $item) {
        $deliveryDetails[] = DeliveryDetails::create([
          'delivery_id'     => $delivery->id,
          'item_id'         => $item['item_id'],
          'quantity'        => $item['quantity'],
        ]);
      }

      $createdData = [
        'delivery'  => $delivery,
        'items'     => $deliveryDetails,
      ];

      return new InventoryResource(true, 'New delivery data has been saved', $createdData);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Display the specified resource.
   */
  public function show(Delivery $delivery)
  {
    try {
      $deliveryDetails = $delivery->load('deliveryDetails.item');

      $formattedDeliveryDetails = [
        'delivery'  => [
          'id'          => $deliveryDetails->id,
          'order_code'  => $deliveryDetails->order_code,
          'date'        => $deliveryDetails->date,
          'status'      => $deliveryDetails->status,
          'detail'      => $deliveryDetails->detail,
        ],
        'items'     => $deliveryDetails->deliveryDetails->map(fn ($detail) => [
          'id'        => $detail->id,
          'name'      => $detail->item->name,
          'price'     => $detail->item->price,
          'quantity'  => $detail->quantity,
        ]),
      ];

      return new InventoryResource(true, 'Found details delivery data', $formattedDeliveryDetails);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, Delivery $delivery)
  {
    try {
      $validation = Validator::make($request->all(), [
        'date'              => 'required|date',
        'status'            => 'required|max:25',
        'detail'            => 'max:100',
        'items'             => 'required|array',
      ]);

      if ($validation->fails()) {
        return response()->json([
          'success' => false,
          'message' => $validation->errors(),
        ], 422);
      }

      foreach ($request->items as $key => $item) {
        $validation = Validator::make($item, [
          'item_id'   => 'required',
          'quantity'  => 'required|numeric|min:1',
        ]);

        if ($validation->fails()) {
          return response()->json([
            'success' => false,
            'message' => $validation->errors(),
            'extra'   => "The validation errors is in row $key",
          ], 422);
        }
      }

      $deliveryDetails = DeliveryDetails::where('delivery_id', $delivery->id)->get();
      foreach ($deliveryDetails as $key => $detail) {
        $detail->update([
          'item_id'     => $request->items[$key]['item_id'],
          'quantity'    => $request->items[$key]['quantity'],
        ]);
      }

      $delivery->update([
        'date'          => $request->date,
        'status'        => $request->status,
        'detail'        => $request->detail,
      ]);

      $updatedData = [
        'delivery'  => $delivery,
        'items'     => $deliveryDetails,
      ];

      return new InventoryResource(true, 'Selected delivery data has been updated', $updatedData);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Delivery $delivery)
  {
    try {
      $deliveryDetails = DeliveryDetails::where('delivery_id', $delivery->id)->get();

      foreach ($deliveryDetails as $detail) {
        $detail->delete();
      }
      $delivery->delete();

      return new InventoryResource(true, 'Selected item has been deleted', null);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Internal server error'], 500);
    }
  }
}
