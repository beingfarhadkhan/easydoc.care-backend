<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Clinic;
use App\Models\Account;
use App\Models\AssignToClinic;
use App\Models\AssignToAccount;
use App\Models\AccountBilling;


class UserController extends Controller
{
    public function createUser(Request $request)
    {
        validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|string|max:15|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:1,2,3' // Example roles: 1=Admin, 2=Doctor, 3=Staff
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'role' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // dd($user);
    }


    public function getAccountUser(Request $request)
    {
    
    // Step 1: Check if user is an Account Admin
    // var_dump($request->user_id);
    $accountAssignment = AssignToAccount::where('user_id', $request->user_id)
        ->whereHas('user', function($q) {
            $q->where('role', '1');
        })
        ->with('account')
        ->first();
    //  var_dump($accountAssignment);
        if (!$accountAssignment) {
            return response()->json(['message' => 'Not an account admin'], 403);
        }

        // Step 2: Fetch all clinics under that account with their users
        $clinics = Clinic::where('account_id', $accountAssignment->account_id)
            ->with(['users' => function($q) {
                $q->select('users.id','users.name','users.email','users.phone');
            }])
            ->get();

        // Step 3: Fetch all users of the account (from assign_to_accounts)
        $accountUsers = User::whereHas('accounts', function($q) use ($accountAssignment) {
            $q->where('accounts.id', $accountAssignment->account_id);
        })->select('users.id','users.name','users.email','users.phone')->get();

        return response()->json([
            'account' => $accountAssignment->account->legal_name,
            'clinics' => $clinics,
            'account_users' => $accountUsers
        ]);
    }

    public function userManagement(request $request){

        // $usermag = User::where('id',$request->user_id);
        
        // if(!$usermag){
        //     return response()->json([
        //         'message'=> 'User not found'
        //     ],403);
        // }
        
        // $request->validate([
        //     'email' => 'required|string|email|max:255|unique:users',
        //     'phone' => 'required|string|max:10|unique:users',
        // ]);

        //  $authUser = auth()->user();
        // if (!$authUser) {
        //     return response()->json([
        //     'message' => 'Unauthorized Access'
        //     ], 403);
        // }

        // $isAccountAdmin = AssignToAccount::where('user_id', $authUser->id)
        //     ->whereHas('user', function($q) {
        //     $q->where('role', '1');
        //     })
        //     ->first();

        // if (!$isAccountAdmin) {
        //     return response()->json([
        //     'message' => 'Unauthorized Access'
        //     ], 403);
        // }
        $accountId = Clinic::where('id', $request->clinics[0])->first()->account_id;
        
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->phone),
            // 'is_admin' => $request->is_admin,
            'pad_configuration' => json_encode($request->pad_configuration),
            'role' => $request->role,
            'status' => $request->status,    
            'selected_clinic' => $request->clinics[0],
            'selected_account' => $accountId,    
        ]);



        // if($user->is_admin === 1){
        //     foreach($request->accounts as $account){
        //         AssignToAccount::create([
        //             'user_id' => $user->id,
        //             'account_id' => $account,
        //             'created_at' => now(),
        //             'updated_at' => now()
        //         ]);
        //     }                    
        // }


        foreach($request->clinics as $clinic){
            AssignToClinic::create([
                'user_id' => $user->id,
                'clinic_id' => $clinic,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

       return response()->json([
            'message' => 'User created Successfully'
        ],200);

    }

    public function updateUserManagement(Request $request)
    {
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->role = $request->role;
        $user->status = $request->status;
        $user->pad_configuration = json_encode($request->pad_configuration);
        $user->save();

        // Update clinic assignments
        AssignToClinic::where('user_id', $user->id)->delete();
        foreach ($request->clinics as $clinic) {
            AssignToClinic::create([
                'user_id' => $user->id,
                'clinic_id' => $clinic,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        return response()->json(['message' => 'User updated successfully'], 200);
    }

    public function getUserManagement(Request $request)
    {
        // Validate account
        $account = Account::find($request->account_id);
        if (!$account) {
            return response()->json(['message' => 'Account not found'], 404);
        }

        // Get all clinics under this account
        $clinicIds = Clinic::where('account_id', $request->account_id)
            ->pluck('id')
            ->toArray();

        if (empty($clinicIds)) {
            return response()->json(['message' => 'No clinics found for this account'], 204);
        }

        // Get all user ids assigned to these clinics
        $assignedUserIds = AssignToClinic::whereIn('clinic_id', $clinicIds)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        if (empty($assignedUserIds)) {
            return response()->json(['message' => 'No users assigned to these clinics'], 204);
        }

        // Fetch users from users table
        $users = User::whereIn('id', $assignedUserIds)->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found'], 204);
        }

        
        // Prepare output
        $doctors = [];
        $staff = [];

        foreach ($users as $user) {

            $userData = $user->toArray(); // convert to array

            $userData['is_admin'] = AssignToAccount::where('user_id', $userData['id'])
                ->where('account_id', $request->account_id)                
                ->first() ? true : false;

            if ($user->role == 1 || $user->role == 2) {
            
                $doctors[] = $userData;
                
            } elseif ($user->role == 3) {
                $staff[] = $userData;
            }

            

        }

        return response()->json([
            'doctors' => array_map(function($doctor) {
                $doctor['pad_configuration'] = $doctor['pad_configuration'] ? json_decode($doctor['pad_configuration'], true) : null;
                return $doctor;
            }, $doctors),
            'staff' => $staff,
            // 'is_admin' => $is_admin
        ], 200);
    }

    // public function lastAccessedClinic(Request $request)
    // {
    //     $user = User::find(auth()->user()->id);

    //     $user->last_excessed_clinic = $request->clinic_id;
    //     $user->save();

    //     return response()->json(['message' => 'Last accessed clinic updated successfully'], 200);
    // }
    
    public function getUser(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $user->pad_configuration = $user->pad_configuration ? json_decode($user->pad_configuration, true) : null;
        $user->vital_config = $user->vital_config ? json_decode($user->vital_config, true) : null;
        $user->template_config = $user->template_config ? json_decode($user->template_config, true) : null;
        $user->education = $user->education ? json_decode($user->education, true) : null;
        $user->social_links = $user->social_links ? json_decode($user->social_links, true) : null;
        $user->availability = $user->availability ? json_decode($user->availability, true) : null;
        $user->google_review = $user->google_review ? json_decode($user->google_review, true) : null; 

        $accounts = Account::whereIn('id', function($query) use ($user) {
            $query->select('account_id')
                  ->from('assign_to_accounts')
                  ->where('user_id', $user->id);
        })->get();

        if(!$accounts->isEmpty()){
            foreach($accounts as $account){
                $account['billing_details'] = AccountBilling::where('account_id', $account->id)->first();
            }
        }


        $clinics = Clinic::whereIn('id', function($query) use ($user) {
            $query->select('clinic_id')
                  ->from('assign_to_clinics')
                  ->where('user_id', $user->id);
        })->get();

        $selectedclinic = null;
        $selectedaccount = null;
        

        $selectedclinic = Clinic::find($user->selected_clinic);

        if($selectedclinic){
        $selectedaccount = Account::find($selectedclinic->account_id);
        }
        
        if($selectedaccount){
            $selectedaccount['billing_details'] = AccountBilling::where('account_id', $selectedaccount->id)->first();
        }

        if($selectedaccount){  
            $selectedaccountadmin = AssignToAccount::where('user_id', $user->id)
                ->where('account_id', $selectedaccount->id)
                ->first() ? true : false;
        }else{
            $selectedaccountadmin = false;
        }
        
        return response()->json([
            'user' => $user,
            'accounts' => $accounts,
            'clinics' => $clinics,
            'selected_clinic' => $selectedclinic,
            'selected_account' => $selectedaccount,
            'selected_account_admin' => $selectedaccountadmin
        ] , 200);
    }

    public function getDoctorsByClinic(Request $request)
    {
        $clinic = Clinic::where('id', $request->clinic_id)->first();
        if (!$clinic) {
            return response()->json(['message' => 'Clinic not found'], 404);
        }

        $doctorIds = AssignToClinic::where('clinic_id', $request->clinic_id)
            ->whereHas('user', function($q) {
            $q->whereIn('role', [1, 2]); // roles 1 and 2
            })
            ->pluck('user_id')
            ->toArray();

        if (empty($doctorIds)) {
            return response()->json(['message' => 'No doctors assigned to this clinic'], 204);
        }

        $doctors = User::whereIn('id', $doctorIds)->get();
        $authUser = auth()->user();

        if ($authUser) {
            $isAssignedToAccount = AssignToAccount::where('user_id', $authUser->id)
                ->where('account_id', $clinic->account_id)
                ->exists();

            if ($isAssignedToAccount && ! $doctors->contains('id', $authUser->id)) {
                // reload fresh user record to ensure full attributes
                $doctors->push(User::find($authUser->id));
            }
        }

        return response()->json([
            'doctors' => $doctors
        ], 200);
    }

    public function updatePadConfiguration(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
        
        $user->pad_configuration = json_encode($request->pad_configuration);
        $user->save();

        return response()->json(['message' => 'Pad configuration updated successfully'], 200);
    }

    public function updateVitalConfig(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->vital_config = json_encode($request->vital_config);
        $user->save();

        return response()->json(['message' => 'Vital configuration updated successfully'], 200);
    }

    // public function updateTemplateConfig(Request $request)
    // {
    //     $user = User::find(auth()->user()->id);

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $user->template_config = json_encode($request->template_config);
    //     $user->save();

    //     return response()->json(['message' => 'Template configuration updated successfully'], 200);
    // }

    public function updateTemplateConfig(Request $request)
    {
        $request->validate([
            'template' => 'required|array',
            'template.id' => 'required|string'
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Get old templates
        $config = $user->template_config
            ? json_decode($user->template_config, true)
            : [];

        // Template ID → used as key
        $templateId = $request->template['id'];

        // Save or update template
        $config[$templateId] = $request->template;

        // Save back to database
        $user->template_config = json_encode($config);
        $user->save();

        return response()->json([
            'message' => 'Template saved successfully',
            'templates' => $config
        ], 200);
    }


    // public function getTemplateConfig(Request $request)
    // {
    //     $user = User::find(auth()->user()->id);

    //     if (!$user) {
    //         return response()->json(['message' => 'User not found'], 404);
    //     }

    //     $templateConfig = $user->template_config ? json_decode($user->template_config, true) : null;

    //     return response()->json([
    //         'template_config' => $templateConfig
    //     ], 200);
    // }

    public function getTemplateConfig(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Get all saved templates
        $config = $user->template_config
            ? json_decode($user->template_config, true)
            : [];

        // If "id" is given → return only that template
        if ($request->has('id')) {

            $id = $request->id;

            if (isset($config[$id])) {
                return response()->json([
                    'template' => $config[$id]
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Template not found'
                ], 404);
            }
        }

        // Otherwise → return all templates
        return response()->json([
            'templates' => $config
        ], 200);
    }

    public function removeUser(Request $request)
    {
        // 1. Fetch user
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // 2. Check if the user is primary admin in ANY account
        $isPrimaryAdmin = Account::where('primary_user', $user->id)->exists();

        if ($isPrimaryAdmin) {
            return response()->json([
                'message' => 'Cannot remove this user. This user is the Primary Admin of an account.'
            ], 403);
        }

        // 3. Remove user from assigned accounts
        AssignToAccount::where('user_id', $user->id)->delete();

        // 4. Remove user from assigned clinics
        AssignToClinic::where('user_id', $user->id)->delete();

       

        return response()->json([
            'message' => 'User removed successfully'
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|min:6'
        ]);

        // Check current password
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        if ($request->new_password !== $request->confirm_password) {
            return response()->json(['message' => 'New password and confirm password do not match'], 400);
        }

        // Update to new password
        $user->password = bcrypt($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully'], 200);
    }


    public function updateProfilePicture(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $path = $file->store('profile_pictures', 'public');

            $user->profile_picture = $path;
            $user->save();

            return response()->json(['message' => 'Profile picture updated successfully', 'profile_picture' => $path], 200);
        } else {
            return response()->json(['message' => 'No profile picture uploaded'], 400);
        }
    }

    public function updateSignatureImage(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($request->hasFile('signature_image')) {
            $file = $request->file('signature_image');
            $path = $file->store('signature_images', 'public');

            $user->signature_image = $path;
            $user->save();

            return response()->json(['message' => 'Signature image updated successfully', 'signature_image' => $path], 200);
        } else {
            return response()->json(['message' => 'No signature image uploaded'], 400);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Update fields if provided
        if ($request->has('education')) {
            $user->education = json_encode($request->education);
        }
        if ($request->has('specialization')) {
            $user->specialization = $request->specialization;
        }
        if ($request->has('working_since')) {
            $user->working_since = $request->working_since;
        }
        if ($request->has('social_links')) {
            $user->social_links = json_encode($request->social_links);
        }        
                   
        $user->save();

        return response()->json(['message' => 'User profile updated successfully'], 200);
    }

    public function updateAvailability(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->availability = json_encode($request->availability);
        $user->save();

        return response()->json(['message' => 'Availability status updated successfully'], 200);
    }

    public function updateGoogleReview(Request $request)
    {
        $user = User::find(auth()->user()->id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->google_review = json_encode($request->google_review);
        $user->save();

        return response()->json(['message' => 'Google review link updated successfully'], 200);
    }
}
