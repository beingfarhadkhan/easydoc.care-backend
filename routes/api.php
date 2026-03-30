<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\ClinicController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\VitalController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\AccountBillingController;
use App\Http\Controllers\Api\RazorPayPaymentController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ConsumableController;
use App\Http\Controllers\Api\SaleOrderController;




// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/hello', function() {
        return response()->json(['message' => 'Hello, authenticated user!']);
    })->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password-reset', [AuthController::class, 'passwordReset']);
Route::post('reset-password-otp-verify', [AuthController::class, 'resetPasswordVerify']);
Route::post('update-password', [AuthController::class, 'updatePassword']);




// Route::get('clinic/{id}', [ClinicController::class, 'show']);
// Route::apiResource('clinic', ClinicController::class);
// Route::post('get-all-users-by-clinic', [ClinicController::class, 'getAllUsersByClinic']);

// Route::post('get-account-user', [UserController::class, 'getAccountUser']);









Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);    
    Route::post('logout', [AuthController::class, 'logout']);
    
    
        // Account API Routes
    Route::post('account/store', [AccountController::class, 'store']); //only admin access 
    Route::post('assign-to-account', [AccountController::class, 'assignToAccount']); //only admin access 
    Route::post('account/update', [AccountController::class, 'update']); //only admin access 
    Route::get('account/{id}', [AccountController::class, 'show']); 
    Route::post('remove-user-from-account', [AccountController::class, 'removeUserFromAccount']); //only admin access 
    Route::get('get-all-accounts-by-user', [AccountController::class, 'getAllAccountsByUser']); 
    Route::get('get-all-users-by-account', [AccountController::class, 'getAllUsersByAccount']); //only admin access
    Route::get('get-invoice-by-account', [AccountController::class, 'getInvoiceByAccount']);  
    Route::get('get-current-billing-detail', [AccountController::class, 'getCurrentBillingDetail']);
    Route::post('update-patient-details-config',[AccountController::class, 'updatePatientDetailsConfig']);  
    Route::get('get-patient-details-config',[AccountController::class, 'getPatientDetailsConfig']);  
    Route::post('update-payment-details',[AccountController::class, 'updatePaymentDetails']);  
    Route::get('get-payment-details',[AccountController::class, 'getPaymentDetails']);
    Route::post('upload-qr-image', [AccountController::class, 'uploadQR']);
    Route::post('remove-bank-account',[AccountController::class, 'removeBankAccount']);  



    // Clinic API Routes
    Route::post('clinic/store', [ClinicController::class, 'store']); //only admin access 
    Route::get('get-all-clinics-by-account', [ClinicController::class, 'getAllClinicsByAccount']); //only admin access 
    Route::get('get-all-clinics-by-user', [ClinicController::class, 'getAllClinicByUser']); 
    Route::post('assign-to-clinic', [ClinicController::class, 'assignToClinic']); //only admin access 
    Route::post('remove-user-from-clinic', [ClinicController::class, 'removeUserFromClinic']); //only admin access
    Route::get('get-account-by-clinic', [ClinicController::class, 'getAccountByClinic']); 
    Route::post('update-selected-clinic', [ClinicController::class, 'updateSelectedClinic']); 
    Route::post('update-selected-account', [ClinicController::class, 'updateSelectedAccount']);
    Route::post('upload-clinic-logo', [ClinicController::class, 'uploadClinicLogo']);
    Route::post('upload-clinic-header-img', [ClinicController::class, 'uploadPDFHeaderImg']);
    Route::post('upload-clinic-footer-img', [ClinicController::class, 'uploadPDFFooterImg']);
    Route::post('upload-letterhead-img', [ClinicController::class, 'uploadLetterheadImage']);
    Route::post('clinic/update', [ClinicController::class, 'update']);  
    Route::post('update-receipt-config', [ClinicController::class, 'updateReceiptConfig']);  
    Route::get('get-receipt-config', [ClinicController::class, 'getReceiptConfig']);  
    Route::post('update-prescription-config', [ClinicController::class, 'updatePrescriptionConfig']);  
    Route::get('get-prescription-config', [ClinicController::class, 'getPrescriptionConfig']);
    

    // patient API Routes
    Route::post('patient/store', [PatientController::class, 'store']); 
    Route::post('patient/update', [PatientController::class, 'update']);
    Route::get('patient/{id}', [PatientController::class, 'show']); 
    Route::get('recommendation', [PatientController::class, 'recommendPatients']);
    Route::get('recommendation-by-account', [PatientController::class, 'recommendPatientsByAccount']);
    Route::get('get-all-patients-by-clinic', [PatientController::class, 'getAllPatientsByClinic']); 
    Route::get('get-all-patients-by-account', [PatientController::class, 'getAllPatientByAccount']); 
    Route::get('get-patient-by-phone', [PatientController::class, 'getPatientByPhone']); 
    Route::post('add-patient-to-account', [PatientController::class, 'addPatientToAccount']);

    //appointment API Routes
    Route::post('appointment/store', [AppointmentController::class, 'store']);
    Route::post('appointment/update', [AppointmentController::class, 'update']);
    Route::get('appointment/{id}', [AppointmentController::class, 'show']);
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointment/cancel', [AppointmentController::class, 'cancelAppointment']);
    Route::get('get-all-appointments-by-clinic', [AppointmentController::class, 'getAllAppointmentsByClinic']);
    Route::get('get-all-appointment-by-patient', [AppointmentController::class, 'getAllAppointmentsByPatientInClinic']);
    Route::get('past-visits', [AppointmentController::class, 'getAllPastVisitsByPatient']);
    Route::get('get-monthly-appointment', [AppointmentController::class, 'getMonthlyAppointments']);

    //Receipt API Routes
    Route::post('receipt/store', [ReceiptController::class, 'store']);
    Route::post('receipt/update', [ReceiptController::class, 'update']);
    Route::get('receipt/{id}', [ReceiptController::class, 'show']);
    Route::post('generate-receipt-pdf/{id}', [ReceiptController::class, 'generatePDF']);
    Route::get('receipt-by-appointment', [ReceiptController::class, 'getReceiptByAppointment']);
    Route::get('receipt-by-patient', [ReceiptController::class, 'getReceiptByPatientId']);
    Route::get('get-payment-by-doctor', [ReceiptController::class, 'getPaymentByDoctor']);

    //Service API Routes
    Route::get('service', [ServiceController::class, 'index']);
    Route::post('service/store', [ServiceController::class, 'store']);
    Route::post('service/update', [ServiceController::class, 'update']);
    Route::get('service/{id}', [ServiceController::class, 'show']);
    Route::get('reccommendation-services', [ServiceController::class, 'recommendationService']);
    Route::get('service-delete', [ServiceController::class, 'destroy']);

    //Payment API Routes
    Route::post('payment/store', [PaymentController::class, 'store']);
    Route::post('payment/update', [PaymentController::class, 'update']);
    Route::get('payment/{id}', [PaymentController::class, 'show']);
    Route::get('payments-by-clinic', [PaymentController::class, 'basedOnClinic']);
    Route::get('payments-by-account', [PaymentController::class, 'basedOnAccount']);
    Route::get('advance-payments', [PaymentController::class, 'advancePayment']);

    

    //Medical Record API Routes
    Route::post('/upload-file', [MedicalRecordController::class, 'uploadFile']);
    Route::post('save-medical-record', [MedicalRecordController::class, 'saveMedicalRecord']);
    Route::post('delete-medical-record', [MedicalRecordController::class, 'deleteMedicalRecord']);
    Route::get('get-medical-records-by-appointment', [MedicalRecordController::class, 'getMedicalRecordsByAppointment']);
    Route::get('get-medical-records-by-patient', [MedicalRecordController::class, 'getMedicalRecordsByPatient']);

    //Vital API Routes
    Route::post('vital/store', [VitalController::class, 'store']);
    Route::get('vital/{id}', [VitalController::class, 'show']);
    Route::get('vitals-by-appointment', [VitalController::class, 'getVitalsByAppointment']);

    //Prescription API Routes
    Route::post('prescription/store', [PrescriptionController::class, 'store']);
    Route::get('prescription/{id}', [PrescriptionController::class, 'show']);
    Route::get('prescription-by-appointment', [PrescriptionController::class, 'getPrescriptionByAppointment']);
    Route::Post('generate-prescription-pdf/{id}', [PrescriptionController::class, 'generatePDF']);
    Route::get('recommendations', [PrescriptionController::class, 'getRecommendations']);

    //Account Billing API Routes
    Route::prefix('billing')->group(function () { //only admin access 
    Route::post('/store', [AccountBillingController::class, 'store']);
    Route::get('/all', [AccountBillingController::class, 'index']);   
    });

    Route::get('/billing-by-account', [AccountBillingController::class, 'getBillingByAccountId']);

    //User API Routes
    Route::post('user-management',[UserController::class,'userManagement']); //only admin access
    Route::get('get-user-management',[UserController::class,'getUserManagement']); //only admin access
    Route::post('last-accessed-clinic',[UserController::class,'lastAccessedClinic']); //only admin access 
    Route::get('user',[UserController::class,'getUser']); 
    Route::get('get-doctors-by-clinic', [UserController::class, 'getDoctorsByClinic']);
    Route::post('update-pad-configuration', [UserController::class, 'updatePadConfiguration']);
    Route::post('update-vital-config', [UserController::class, 'updateVitalConfig']);
    Route::post('update-template-config', [UserController::class, 'updateTemplateConfig']);
    Route::get('get-template-config', [UserController::class, 'getTemplateConfig']);
    Route::post('remove-user', [UserController::class, 'removeUser']); //only admin access
    Route::post('update-profile', [UserController::class, 'updateProfile']);
    Route::post('change-password', [UserController::class, 'changePassword']);
    Route::post('update-availability', [UserController::class, 'updateAvailability']);
    Route::post('update-profile-picture', [UserController::class, 'updateProfilePicture']);
    Route::post('update-signature', [UserController::class, 'updateSignatureImage']);
    Route::post('update-google-review', [UserController::class, 'updateGoogleReview']);
    Route::post('update-advice', [UserController::class, 'updateAdvice']);
    Route::get('get-pad-config', [UserController::class, 'getPadConfig']);
    
    

    // Razorpay Payment Routes
    Route::post('/create-order', [RazorPayPaymentController::class, 'createOrder']);
    Route::post('/verify-payment', [RazorPayPaymentController::class, 'verifyPayment']);

    // Plan Routes
    Route::get('/get-plan', [PlanController::class, 'getPlan']);
    Route::get('/get-addon', [PlanController::class, 'getAddon']);

    //Inventory Routes
    Route::post('inventory/store', [InventoryController::class, 'store']);
    Route::post('inventory/update', [InventoryController::class, 'update']);
    Route::get('inventories', [InventoryController::class, 'index']);
    Route::get('inventory/show', [InventoryController::class, 'show']);
    Route::get('inventory/search', [InventoryController::class, 'search']);
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock']);
    Route::get('inventory/expiring', [InventoryController::class, 'expiring']);
    Route::put('inventory/{id}', [InventoryController::class, 'update']);
    Route::delete('inventory/{id}', [InventoryController::class, 'destroy']);
    Route::post('inventory/add-stock', [InventoryController::class, 'addStock']);
    Route::get('inventory/recommend-product-codes', [InventoryController::class, 'recommendProductCodes']);
    Route::post('upload-product-image', [InventoryController::class, 'uploadProductImage']);
    Route::post('inventory/add-product', [InventoryController::class, 'addProduct']);
    Route::post('inventory/update-product', [InventoryController::class, 'updateProduct']);
    Route::post('inventory/add-stock', [InventoryController::class, 'addStocks']);
    Route::get('inventory/product-list', [InventoryController::class, 'listProducts']);
    Route::get('inventory/get-product', [InventoryController::class, 'getProduct']);
    Route::post('inventory/add-vendor', [InventoryController::class, 'addVendor']);
    Route::post('inventory/update-vendor', [InventoryController::class, 'updateVendor']);
    Route::get('inventory/vendor-list', [InventoryController::class, 'listVendor']);
    Route::get('inventory/get-vendor', [InventoryController::class, 'getVendor']);
    Route::get('inventory/recommendation', [InventoryController::class, 'recommendInventories']);
    



    //Order Routes
    Route::post('order/store', [OrderController::class, 'store']);
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('get-orders-by-patient', [OrderController::class, 'getOrdersByPatient']);
    Route::get('order/show', [OrderController::class, 'show']);
    Route::post('order/update', [OrderController::class, 'update']);
    // Route::post('update-advance', [OrderController::class, 'addAdvance']);


     //Consumable Routes
    Route::get('consumables', [ConsumableController::class, 'index']);
    Route::post('consumable/update', [ConsumableController::class, 'update']);
    Route::post('consumable/mark-as', [ConsumableController::class, 'markAs']);

    //Sale Order Routes
    Route::post('sale-order/store', [SaleOrderController::class, 'store']);
    Route::get('sale-orders', [SaleOrderController::class, 'index']);
    Route::post('sale-order/receive-items', [SaleOrderController::class, 'receiveOrderItems']);
     
});

Route::post('/razorpay-webhook', [RazorPayPaymentController::class, 'razorpayWebhook']);
// Route::post('inventory/migration-fix', [InventoryController::class, 'migrationFix']);
