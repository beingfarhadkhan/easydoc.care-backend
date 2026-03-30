<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\Vendor;

class SaleOrderController extends Controller
{
    public function index()
    {
        $orders = SalesOrder::with([
        'patient:id,name,phone,age,gender,occupation'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
         $saleOrderNo = 'SO-' . now()->timestamp;

        $products = [];

        foreach ($request->products as $item) {

            $products[] = [
                'product_name' => $item['product_name'],
                'quantity' => (int)$item['quantity'],
                'size' => $item['size'] ?? null,
                'color' => $item['color'] ?? null,
                'notes' => $item['notes'] ?? null,
                'received_qty' => 0,
                'vendor_receives' => []
            ];
        }

        $order = SalesOrder::create([
            'saleorder_no' => $saleOrderNo,
            'clinic_id' => $request->clinic_id,
            'patient_id' => $request->patient_id,
            'height' => $request->height,
            'weight' => $request->weight,
            'products' => json_encode($request->products),
            'received_logs' => json_encode(null),           
            'status' => '1',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Sales Order created successfully',
            'data' => $order
        ]);
    }

    public function receiveOrderItems(Request $request)
    {
        // ✅ BASIC VALIDATION
        if (!$request->saleorder_id || !is_array($request->items)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request data'
            ], 400);
        }

        $saleorder = SalesOrder::find($request->saleorder_id);

        if (!$saleorder) {
            return response()->json([
                'status'=>false,
                'message'=>'Order not found'
            ],404);
        }

        $products = json_decode($saleorder->products, true) ?? [];
        $logs = json_decode($saleorder->received_logs, true) ?? [];

        foreach ($request->items as $item) {

            // ✅ SAFE EXTRACTION
            $name  = $item['product_name'] ?? null;
            $size  = $item['size'] ?? null;
            $color = $item['color'] ?? null;
            $qty   = (int) ($item['received_qty'] ?? 0);
            $pprate = (float) ($item['pprate'] ?? 0);
            $vendorId = $item['vendor_id'] ?? null;

            // ✅ VALIDATION CHECKS
            if (!$name || !$size || !$color) continue;

            if ($qty <= 0) continue;

            if ($pprate < 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid rate for product: ' . $name
                ], 400);
            }

            // ✅ FETCH VENDOR SAFELY (ONLY ONCE)
            $vendor = null;
            if ($vendorId) {
                $vendor = Vendor::where("clinic_id", $request->clinic_id)
                    ->where('id', $vendorId)
                    ->first();

                if (!$vendor) {
                    return response()->json([
                        'status' => false,
                        'message' => "Vendor not found"
                    ], 404);
                }
            }

            foreach ($products as &$product) {

                // ✅ FIX KEY NAME ISSUE
                if (
                    ($product['product_name'] ?? $product['product'] ?? null) === $name &&
                    $product['size'] === $size &&
                    $product['color'] === $color
                ) {

                    $product['received_qty'] = $product['received_qty'] ?? 0;

                    $remaining = $product['quantity'] - $product['received_qty'];

                    if ($remaining <= 0) break;

                    $receiveQty = min($qty, $remaining);

                    // ✅ UPDATE QTY
                    $product['received_qty'] += $receiveQty;

                    // ✅ INIT ARRAY
                    if (!isset($product['vendor_receives']) || !is_array($product['vendor_receives'])) {
                        $product['vendor_receives'] = [];
                    }

                    // ✅ ADD VENDOR RECEIVE ENTRY
                    $product['vendor_receives'][] = [
                        'vendor_id' => $vendorId,
                        'vendor_name' => $vendor->vendor_name ?? null,
                        'vendor_phone' => $vendor->phone ?? null,
                        'qty' => $receiveQty,
                        'pprate' => $pprate,
                        'total' => $receiveQty * $pprate,
                        'date' => now()->toDateTimeString()
                    ];

                    // ✅ LOG ENTRY
                    $logs[] = [
                        'product_name' => $name,
                        'size' => $size,
                        'color' => $color,
                        'received_qty' => $receiveQty,
                        'pprate' => $pprate,
                        'total' => $receiveQty * $pprate,
                        'vendor_id' => $vendorId,
                        'vendor_name' => $vendor->vendor_name ?? null,
                        'date' => now()->toDateTimeString()
                    ];

                    break;
                }
            }
        }

        // ✅ STATUS LOGIC
        $allReceived = true;
        $anyReceived = false;

        foreach ($products as $p) {
            $received = $p['received_qty'] ?? 0;

            if ($received > 0) $anyReceived = true;
            if ($received < $p['quantity']) $allReceived = false;
        }

        $status = $allReceived ? 2 : ($anyReceived ? 3 : 1);

        // ✅ UPDATE ORDER
        $saleorder->update([
            'products' => json_encode($products),
            'received_logs' => json_encode($logs),
            'status' => $status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Items received successfully',
            'sale_order' => $saleorder->fresh()
        ]);
    }
}
