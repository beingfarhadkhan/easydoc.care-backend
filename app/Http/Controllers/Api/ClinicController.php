<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Clinic;
use App\Models\Account;
use App\Models\AssignToClinic;
use App\Models\User;
use App\Models\AssignToAccount;

class ClinicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clinics = Clinic::all();
        return response()->json($clinics);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $clinic = Clinic::create([
            'account_id' => $request->account_id,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'location' => $request->location,
            'logo_url' => $request->logo_url,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        AssignToClinic::create([
            'user_id' => auth()->user()->id,
            'clinic_id' => $clinic->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Clinic created successfully',
            'clinic' => $clinic
            ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $clinic = Clinic::find($id);
        if(!$clinic){
            return response()->json(['message' => 'Clinic not found'], 404); 
        }
        // Return clinic details as JSON
        return response()->json([
            'clinic_id' => $id,
            'clinic' => $clinic
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        clinic::where('id',$request->id) ->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'location' => $request->location,
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Clinic updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //]
    }

    public function assignToClinic(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 200);
        }

        $clinic = Clinic::where('id', $request->clinic_id)->first();

        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 200);
        }
        
        $record = AssignToClinic::where('user_id', $request->user_id)
            ->where('clinic_id', $request->clinic_id)
            ->first();

        if ($record) {
            return response()->json(['message' => 'User is already assigned to the clinic'], 200);
        }

        AssignToClinic::create([
            'user_id' => $request->user_id,
            'clinic_id' => $request->clinic_id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['message' => 'User assigned to clinic successfully'], 201);
    }   

    public function removeUserFromClinic(Request $request)
    {
        $user = User::where('id', $request->user_id)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found'], 200);
        }

        $clinic = Clinic::where('id', $request->clinic_id)->first();

        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 200);
        }
        
        $record = AssignToClinic::where('user_id', $request->user_id)
            ->where('clinic_id', $request->clinic_id)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'User is not assigned to the clinic'], 200);
        }

        AssignToClinic::where('user_id', $request->user_id)
            ->where('clinic_id', $request->clinic_id)
            ->delete();

        return response()->json(['message' => 'User removed from clinic successfully'], 200);
    }

    public function getAllClinicsByAccount(Request $request)
    {
        $account = Account::where('id', $request->account_id)->first();
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 201);
        }

        $clinics = Clinic::where('account_id', $request->account_id)->get();

        if($clinics->isEmpty()){
            return response()->json(['message' => 'No clinics found for this account'], 201);
        }

        return response()->json([
            'account_id' => $request->account_id,
            'clinics' => $clinics
        ], 200);
    }

    public function getAllUsersByClinic(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 200);
        }

        $assignments = AssignToClinic::where('clinic_id', $request->clinic_id)->get();

        $userIds = $assignments->pluck('user_id')->toArray();

        $users = User::whereIn('id', $userIds)->get();

        return response()->json([
            'clinic_id' => $request->clinic_id,
            'users' => $users
        ], 200);
    }

    public function getAllClinicByUser(Request $request)
    {
        $userId = auth()->user()->id;

        if (! $request->has('account_id')) {
            return response()->json(['message' => 'Account ID is required'], 400);
        }

        $account = Account::find($request->account_id);

        if (! $account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        $assignedAccountIds = AssignToAccount::where('user_id', $userId)
            ->pluck('account_id')
            ->toArray();

            
        if (!in_array($request->account_id, $assignedAccountIds)) {
            return response()->json(['message' => 'User is not assigned to this account'], 404);
        }

        $assignedClinicIds = AssignToClinic::where('user_id', $userId)
            ->pluck('clinic_id')
            ->toArray();

        if (empty($assignedClinicIds)) {
            return response()->json(['message' => 'No clinics assigned to this user'], 404);
        }

        $clinics = Clinic::where('account_id', $request->account_id)
            ->whereIn('id', $assignedClinicIds)
            ->get();
            // var_dump($clinics);

        if ($clinics->isEmpty()) {
            return response()->json(['message' => 'No clinics found for this user in this account'], 404);
        }

        return response()->json([
            'account_id' => $request->account_id,
            'clinics' => $clinics
        ], 200);
    }

    public function getAccountByClinic(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        return response()->json([
            'account_id' => $clinic->account_id
        ], 200);
    }

    public function updateSelectedClinic(Request $request)
    {
        $user = auth()->user();
        $user->selected_clinic = $request->clinic_id;
        $user->save();

        return response()->json([
            'message' => 'Selected clinic updated successfully'
        ], 200);
    }

    public function updateSelectedAccount(Request $request)
    {
        $user = auth()->user();
        $user->selected_account = $request->account_id;
        $user->save();

        return response()->json([
            'message' => 'Selected account updated successfully'
        ], 200);
    }

     public function uploadClinicLogo(Request $request)
    {
        
            $file = $request->file('logo');
            $uploadPath = public_path('clinic_logo');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);
            $fileUrl = url('clinic_logo/' . $filename);
            return response()->json([
                'message' => 'Logo uploaded successfully',
                'logo_url' => $filename,
                
            ], 201);
        
    }
}
