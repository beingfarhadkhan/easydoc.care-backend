<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Appointment;
use App\Models\Consumable;
use App\Models\Receipt;
use App\Models\Inventory;


class ConsumableController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $Consumable = Consumable::where('clinic_id', $user->selected_clinic)
        ->where('mark_as',1)
        ->orderBy('date_of_appointment', 'desc')
        ->get();

        return response()->json([
            'status' => true,
            'data'   => $Consumable
        ], 200);
        
    }

    public function update(Request $request){


        $consumable = Consumable::find($request->id);

        if (!$consumable) {
            return response()->json([
                'status' => false,
                'message' => 'Consumable not found'
            ], 404);
        }

        // Update basic fields
        $consumable->update([
            'product_used'       => json_encode($request->product_used),
        ]);

        foreach ($request->product_used as $item) {

            $productName = $item['product_name'] ?? null;
            $size        = $item['size'] ?? null;
            $qtyNeeded   = (int) ($item['quantity'] ?? 0);

            if (!$productName || !$size || $qtyNeeded <= 0) {
                continue;
            }

            $inventory = Inventory::where('clinic_id', $consumable->clinic_id)
                ->where('product_name', $productName)
                ->first();

            if (!$inventory) {
                continue;
            }

            $stockDetails = json_decode($inventory->stock_details, true) ?? [];

            foreach ($stockDetails as &$batch) {

                if ($batch['size'] === $size && $qtyNeeded > 0) {

                    $available = $batch['stock'];

                    if ($available >= $qtyNeeded) {
                        $batch['stock'] -= $qtyNeeded;
                        $qtyNeeded = 0;
                    } else {
                        $qtyNeeded -= $available;
                        $batch['stock'] = 0;
                    }
                }
            }

            // Update inventory
            $inventory->update([
                'stock_details' => json_encode($stockDetails),
                'total_stock_available' => max(
                    0,
                    $inventory->total_stock_available - ($item['quantity'] ?? 0)
                )
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Consumable updated and inventory deducted successfully',
            'data' => $consumable->fresh()
        ], 200);
    }

    public function markAs(Request $request)
    {
        $consumable = Consumable::find($request->id);

        if (!$consumable) {
            return response()->json([
                'status' => false,
                'message' => 'Consumable not found'
            ], 404);
        }

        $consumable->mark_as = $request->mark_as;
        $consumable->save();

        return response()->json([
            'status' => true,
            'message' => 'Consumable mark_as updated successfully',
            'data' => $consumable
        ], 200);
    }
 }
