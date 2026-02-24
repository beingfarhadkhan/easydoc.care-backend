<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Clinic;
use App\Models\Account;
use App\Models\Vendor;

class InventoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $inventories = Inventory::where('account_id', $user->selected_account)
            ->where('clinic_id', $user->selected_clinic)
            ->orderBy('created_at', 'desc')
            ->get();          

        return response()->json([
            'status' => true,
            'count'  => $inventories->count(),
            'data'   => $inventories
        ], 200);
    }


    public function store(Request $request)
    {
        $user = auth()->user();
        
        $inventory = Inventory::create($request->all());

        return response()->json([
            'message' => 'Inventory created successfully',
            'data' => $inventory
        ], 201);
    }

    public function show(Request $request)
    {
        $inventory = Inventory::where('id', $request->id)->first();

        if (!$inventory) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json($inventory);
    }

    public function search(Request $request)
    {
        $q = $request->search;

        $data = Inventory::where('product_name', 'like', "%$q%")
            ->orWhere('product_code', 'like', "%$q%")
            ->orWhere('barcode', 'like', "%$q%")
            ->get();

        return response()->json(['data' => $data]);
    }

    public function lowStock(Request $request)
    {
        $limit = $request->limit ?? 10;

        return response()->json([
            'data' => Inventory::where('stock', '<=', $limit)->get()
        ]);
    }

    public function expiring(Request $request)
    {
        $days = $request->days ?? 30;

        return response()->json([
            'data' => Inventory::whereDate(
                'expiry_date', '<=', now()->addDays($days)
            )->get()
        ]);
    }

    public function addStock(Request $request)
    {
        $request->validate([
            'account_id'     => 'required|integer',
            'clinic_id'      => 'required|integer',
            'product_code'   => 'required|string',
            'quantity'       => 'required|integer|min:1',
            'expiry_date'    => 'required|date',
            'mrp'            => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
        ]);

        // Check if same product + same expiry + same clinic + same account exists
        $inventory = Inventory::where('account_id', $request->account_id)
            ->where('clinic_id', $request->clinic_id)
            ->where('product_code', $request->product_code)
            ->whereDate('expiry_date', $request->expiry_date)
            ->first();

        if ($inventory) {
            $inventory->increment('stock', (int) $request->quantity);

            return response()->json([
                'message' => 'Stock added to existing expiry batch',
                'data' => $inventory->fresh()
            ], 200);
        }

        // Get base product (without expiry filter but same account & clinic)
        $stock = Inventory::where('account_id', $request->account_id)
            ->where('clinic_id', $request->clinic_id)
            ->where('product_code', $request->product_code)
            ->first();

        if (!$stock) {
            return response()->json([
                'message' => 'Inventory product not found for this clinic/account'
            ], 404);
        }

        // Create new expiry batch
        $newInventory = Inventory::create([
            'account_id'     => $request->account_id,
            'clinic_id'      => $request->clinic_id,
            'product_name'   => $stock->product_name,
            'product_code'   => $stock->product_code,
            'barcode'        => $stock->barcode,
            'category'       => $stock->category,
            'product_type'   => $stock->product_type,
            'consume_type'   => $stock->consume_type,
            'mrp'            => $request->mrp,
            'selling_price'  => $request->selling_price,
            'purchase_price'  => $request->purchase_price,
            'stock'          => $request->quantity,
            'expiry_date'    => $request->expiry_date,
            'manufacture_date' => $request->manufacture_date,
            'manufacturer'   => $request->manufacturer ?? $stock->manufacturer,
            'description'    => $stock->description,
            'side_effects'   => $stock->side_effects,
            'disclaimer'     => $stock->disclaimer,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json([
            'message' => 'New expiry batch created',
            'data' => $newInventory
        ], 201);

    }

    // public function recommendProductCodes(Request $request)
    // {
    //     $query = $request->recommendation;

    //     if (!$query || strlen($query) < 1) {
    //         return response()->json([
    //             'status' => true,
    //             'data' => []
    //         ]);
    //     }

    //     $products = Inventory::where(function ($q) use ($query) {
    //         $q->where('product_name', 'LIKE', $query . '%')
    //           ->orWhere('product_code', 'LIKE', $query . '%')
    //           ->orWhere('barcode', 'LIKE', $query . '%')
    //         ->select(
    //             'id',
    //             'product_code',
    //             'product_name',
    //             'barcode'
    //         )
    //         ->groupBy('id','product_code', 'product_name', 'barcode')
    //         ->orderBy('product_code')
    //         ->limit(10)
    //         ->get();

    //     return response()->json([
    //         'status' => true,
    //         'count' => $products->count(),
    //         'data' => $products
    //     ]);
    // }

    public function recommendProductCodes(Request $request)
    {
        $search = $request->recommendation;

        if (!$search || strlen($search) < 1) {
            return response()->json([
                'status' => true,
                'data' => []
            ]);
        }

        $products = Inventory::where(function ($q) use ($search) {
                $q->where('product_name', 'LIKE', $search . '%')
                ->orWhere('product_code', 'LIKE', $search . '%')
                ->orWhere('barcode', 'LIKE', $search . '%');
            })
            ->selectRaw('id,
                product_code,
                product_name,
                barcode,
                SUM(stock) as total_stock
            ')
            ->groupBy('id','product_code', 'product_name', 'barcode')
            ->orderBy('product_code', 'asc')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'count' => $products->count(),
            'data' => $products
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'product_name' => 'nullable|string',
            'category' => 'nullable|string',
            'product_type' => 'nullable|string',
            'consume_type' => 'nullable|string',
            // 'mrp' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'manufacture_date' => 'nullable|date',
            'manufacturer' => 'nullable|string',
            'description' => 'nullable|string',
            'side_effects' => 'nullable|string',
            'disclaimer' => 'nullable|string',
        ]);

        $inventory = Inventory::find($request->id);

        if (!$inventory) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        $inventory->update($request->except(['id', 'stock']));

        return response()->json([
            'message' => 'Inventory updated successfully',
            'data' => $inventory->fresh()
        ], 200);
    }

     public function uploadProductImage(Request $request)
    {
        
            $file = $request->file('image');
            $uploadPath = public_path('product_image');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
            $file->move($uploadPath, $filename);
            $fileUrl = url('product_image/' . $filename);
            return response()->json([
                'message' => 'Image uploaded successfully',
                'logo_url' => $fileUrl,                
            ], 201);
        
    }



    
    public function addProduct(Request $request)
    {   
        $account = Account::where('id', $request->account_id)->first();

        if (!$account) {
            return response()->json(['message' => 'Account not found'], 204);
        }
        
        $clinic = Clinic::where('id', $request->clinic_id)->first();

        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 204);
        }

        $inventory = Inventory::create([
            'account_id' => $request->account_id,
            'clinic_id'  => $request->clinic_id,
            'product_code'   => $request->product_code,
            'product_name' => $request->product_name,
            'barcode' => $request->barcode ?? null,
            'category' => $request->category ?? null,
            'product_type' => $request->product_type ?? null,
            'consume_type' => $request->consume_type ?? null,

            'sizes' => json_encode($request->sizes),
            'content' => json_encode($request->content),

            'manufacturer' => $request->manufacturer,
            // 'mrp' => $request->mrp ?? 0,

            'total_stock_available' => 0,
            'stock_details' => json_encode([])
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product added successfully',
            'data' => $inventory
        ], 201);
    }


    // public function addStock(Request $request)
    // {
    //     $inventory = Inventory::find($request->inventory_id);

    //     if (!$inventory) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Product not found'
    //         ], 404);
    //     }

    //     /* Check size exists in product sizes JSON */
    //     $sizes = json_decode($inventory->sizes, true) ?? [];

    //     if (!in_array($request->size, $sizes)) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Selected size does not belong to this product'
    //         ], 422);
    //     }

    //     $stockDetails = json_decode($inventory->stock_details, true) ?? [];

    //     $quantity = (int) ($request->quantity_purchased ?? 0);

    //     $stockDetails[] = [
    //         'batch_number'       => $request->batch_number,
    //         'size'               => $request->size,
    //         'purchase_price'     => $request->purchase_price ?? 0,
    //         'quantity_purchased' => $quantity,
    //         'stock'              => $quantity,
    //         'manufacture_date'   => $request->manufacture_date,
    //         'expiry_date'        => $request->expiry_date,
    //         'vendor_id'          => $request->vendor_id ?? null,
    //         'vendor_name'        => $request->vendor_name ?? null
    //     ];

    //     $inventory->update([
    //         'stock_details' => json_encode($stockDetails),
    //         'total_stock_available' =>
    //             ($inventory->total_stock_available ?? 0) + $quantity
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Stock added successfully',
    //         'data' => $inventory
    //     ]);
    // }


    public function addStocks(Request $request)
    {
        $inventory = Inventory::find($request->inventory_id);

        if (!$inventory) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        //  $vendor = null;

        // If vendor_id exists → fetch vendor
        if (!empty($request->vendor_id)) {
            $vendor = Vendor::where('id', $request->vendor_id)
                ->where('clinic_id', $request->clinic_id)
                ->first();
        }

        // If vendor not found OR vendor_id not provided → create vendor
        // if (!$vendor && !empty($request->vendor)) {
        //     $vendor = Vendor::create([
        //         'clinic_id' => $request->clinic_id,
        //         'vendor_name' => $request->vendor['vendor_name'],
        //         'phone' => $request->vendor['phone'] ,
        //         'email' => $request->vendor['email'] ,
        //         'address' => json_encode($request->vendor['address'] ?? [])
        //     ]);
        // }

        // Vendor still missing → allow anonymous stock entry
        if (!$vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor information missing'
            ], 422);
        }

        /* Check size exists in product sizes JSON */
        $sizes = json_decode($inventory->sizes, true) ?? [];

        if (!in_array($request->size, $sizes)) {
            return response()->json([
                'status' => false,
                'message' => 'Selected size does not belong to this product'
            ], 422);
        }

        $stockDetails = json_decode($inventory->stock_details, true) ?? [];

        $quantity = (int) ($request->quantity_purchased ?? 0);

        $stockDetails[] = [
            'batch_number'       => $request->batch_number,
            'size'               => $request->size,
            'mrp'                => $request->mrp,
            'purchase_price'     => $request->purchase_price ?? 0,
            'quantity_purchased' => $quantity,
            'stock'              => $quantity,
            'manufacture_date'   => $request->manufacture_date,
            'expiry_date'        => $request->expiry_date,
            'vendor_id'          => $vendor->id,
            'vendor_name'        => $vendor->vendor_name
        ];

        $inventory->update([
            'stock_details' => json_encode($stockDetails),
            'total_stock_available' =>
                ($inventory->total_stock_available ?? 0) + $quantity
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Stock added successfully',
            'data' => $inventory
        ]);
    }

    public function listProducts(Request $request)
    {
        $products = Inventory::where('account_id', $request->account_id)
            ->where('clinic_id', $request->clinic_id)
            ->orderBy('product_name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'count' => $products->count(),
            'data' => $products
        ]);
    }

    public function getProduct(Request $request)
    {
        $inventory = Inventory::find($request->id);

        if (!$inventory) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $inventory->id,
                'product_name' => $inventory->product_name,
                'sizes' => json_decode($inventory->sizes, true),
                'total_stock_available' => $inventory->total_stock_available,
                'stock_details' => json_decode($inventory->stock_details, true)
            ]
        ]);
    }

    public function updateProduct(Request $request)
    {
        $inventory = Inventory::find($request->inventory_id);

        if (!$inventory) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $inventory->update([
            'product_name' => $request->product_name ?? $inventory->product_name,
            'sizes' => json_encode($request->sizes ?? json_decode($inventory->sizes, true)),
            // 'mrp' => $request->mrp ?? $inventory->mrp,
            'content' => json_encode($request->content ?? json_decode($inventory->content, true)),
            'barcode' => $request->barcode,
            'category' => $request->category,
            'product_type' => $request->product_type,
            'consume_type' => $request->consume_type,
            'manufacturer' => $request->manufacturer,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Product updated',
            'data' => $inventory
        ]);
    }

    public function addVendor(Request $request)
    {
         $vendor = Vendor::where('clinic_id', $request->clinic_id)
            ->where('vendor_name', $request->vendor_name)
            ->first();

        if ($vendor) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor already exists'
            ], 409);
        }

        $vendor = Vendor::create([
            'clinic_id' => $request->clinic_id,
            'vendor_name' => $request->vendor_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => json_encode($request->address ?? [])
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Vendor added successfully',
            'data' => $vendor
        ], 201);    
    }

    public function updateVendor(Request $request)
    {
        $vendor = Vendor::where('id', $request->vendor_id)
            ->where('clinic_id', $request->clinic_id)
            ->first();

        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }

        $vendor->update([
            'vendor_name' => $request->vendor_name ?? $vendor->vendor_name,
            'phone' => $request->phone ?? $vendor->phone,
            'email' => $request->email ?? $vendor->email,
            'address' => json_encode($request->address ?? json_decode($vendor->address, true))
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Vendor updated',
            'data' => $vendor
        ]);
    }

    public function listVendor(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();

        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 204);
        }

        $vendors = Vendor::where('clinic_id', $request->clinic_id)
            ->orderBy('vendor_name', 'asc')
            ->get(['id', 'vendor_name', 'phone']);

        return response()->json([
            'status' => true,
            'count' => $vendors->count(),
            'data' => $vendors
        ]);
    }

    public function getVendor(Request $request)
    {
        $vendor = Vendor::find($request->id);

        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $vendor
        ]);
    }

    public function recommendInventories(Request $request)
    {
        $search = $request->search;

        if (!$search) {
            return response()->json([
                'status' => false,
                'message' => 'Search parameter is required'
            ], 400);
        }

        $inventories = Inventory::where(function ($q) use ($search) {
                $q->where('product_name', 'LIKE', $search . '%')
                ->orWhere('product_code', 'LIKE', $search . '%')
                ->orWhere('barcode', 'LIKE', $search . '%');
            })
            ->orderBy('product_name', 'asc')
            ->limit(20)
            ->get();

        $recommendations = [];

        foreach ($inventories as $inventory) {

            $stockDetails = json_decode($inventory->stock_details, true) ?? [];

            foreach ($stockDetails as $batch) {

                $size  = $batch['size'] ?? null;
                $mrp   = $batch['mrp'] ?? 0;
                $stock = $batch['stock'] ?? 0;

                if (!$size || $stock <= 0) {
                    continue;
                }

                $key = $inventory->product_name . '|' . $size . '|' . $mrp;

                if (!isset($recommendations[$key])) {
                    $recommendations[$key] = [
                        'product_name' => $inventory->product_name,
                        'size' => $size,
                        'mrp' => $mrp
                    ];
                }
            }
        }

        return response()->json([
            'status' => true,
            'query' => $search,
            'data' => array_values($recommendations)
        ]);
    }

    public function migrationFix(){
        $inventory = Inventory::whereNull('stock_details')
        ->get();

        foreach ($inventory as $item) {

                $content = [
                    [
                        "name" => "description",
                        "text" => $item->description
                    ],
                    [
                        "name" => "side_effects",
                        "text" => $item->side_effects
                    ],
                    [
                        "name" => "disclaimer",
                        "text" => $item->disclaimer
                    ]
                ];

                $stockDetails = [
                    [
                        'batch_number'        => null,
                        'size'                => $item->size,
                        'mrp'                 => $item->mrp,
                        'stock'               => $item->stock, // 🔴 ensure column name
                        'vendor_id'           => null,
                        'vendor_name'           => null,
                        'purchase_price'      => $item->purchase_price,
                        'quantity_purchased'  => $item->stock,
                        'manufacture_date'    => $item->manufacture_date,
                        'expiry_date'         => $item->expiry_date
                    ]
                ];

                if ($item->size!= null){
                $sizes = [
                    $item->size
                    ];
                } else {
                    $sizes = null;
                }

                $item->stock_details = $stockDetails;
                $item->content       = $content;
                $item->total_stock_available = $item->stock; // 🔴 ensure column name
                $item->sizes = $sizes;
                

                $item->save();
            }
    }





}
