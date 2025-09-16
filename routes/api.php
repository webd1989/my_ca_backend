<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RequiredParameters;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InvoicesController;
use App\Http\Controllers\FranchiseController;
use App\Http\Controllers\CreditNoteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('api')->group(function () {

    #Customer Controller
	Route::any('/get/insta/posts',[CustomerController::class, 'test']);
	Route::get('/get/image/{max_width}/{max_height}/{image}',[CustomerController::class, 'createImage']);
    Route::post('/login',[CustomerController::class, 'login']);
	Route::get('/profile/get',[CustomerController::class, 'getProfile'])->middleware(RequiredParameters::class);
	Route::post('/profile/update',[CustomerController::class, 'updateProfile'])->middleware(RequiredParameters::class);
	Route::post('/password/update',[CustomerController::class, 'updatePassword'])->middleware(RequiredParameters::class);
	Route::post('/profile/pic/upload',[CustomerController::class, 'uploadProfilePic'])->middleware(RequiredParameters::class);
	
	Route::post('/franchise/create',[FranchiseController::class, 'createUser'])->middleware(RequiredParameters::class);
	Route::post('/franchise/list',[FranchiseController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/franchise/get',[FranchiseController::class, 'getUser'])->middleware(RequiredParameters::class);
	Route::post('/franchise/update',[FranchiseController::class, 'updateUser'])->middleware(RequiredParameters::class);
	Route::put('/franchise/status/update/{id}/{status}',[FranchiseController::class, 'updateUserStatus'])->middleware(RequiredParameters::class);
	Route::post('/all/franchise/list',[FranchiseController::class, 'getAllList'])->middleware(RequiredParameters::class);
	
	Route::post('/credit-note/create',[CreditNoteController::class, 'createCreditNote'])->middleware(RequiredParameters::class);
	Route::post('/bill/get',[CreditNoteController::class, 'billGet'])->middleware(RequiredParameters::class);
	Route::post('/credit-note/list',[CreditNoteController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::put('/credit-note/status/update/{id}/{status}',[CreditNoteController::class, 'updateStatus'])->middleware(RequiredParameters::class);
	Route::post('/credit-note/pdf/generate',[CreditNoteController::class, 'generatePDF'])->middleware(RequiredParameters::class);
	
	Route::post('/user/create',[UserController::class, 'createUser'])->middleware(RequiredParameters::class);
	Route::post('/user/list',[UserController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/user/get',[UserController::class, 'getUser'])->middleware(RequiredParameters::class);
	Route::post('/user/update',[UserController::class, 'updateUser'])->middleware(RequiredParameters::class);
	Route::put('/user/status/update/{id}/{status}',[UserController::class, 'updateUserStatus'])->middleware(RequiredParameters::class);
	Route::post('/all/user/list',[UserController::class, 'getAllList'])->middleware(RequiredParameters::class);
	
	Route::post('/booking/create',[BookingController::class, 'createBooking'])->middleware(RequiredParameters::class);
	Route::post('/booking/list',[BookingController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/booking/list/export',[BookingController::class, 'getListExport'])->middleware(RequiredParameters::class);
	Route::post('/booking/get',[BookingController::class, 'getBooking'])->middleware(RequiredParameters::class);
	Route::post('/booking/update',[BookingController::class, 'updateBooking'])->middleware(RequiredParameters::class);
	Route::put('/booking/status/update/{id}/{status}',[BookingController::class, 'updateBookingStatus'])->middleware(RequiredParameters::class);
	Route::put('/booking/complete/update/{id}/{status}',[BookingController::class, 'updateBookingComplete'])->middleware(RequiredParameters::class);
	Route::put('/booking/emp-complete/update/{id}/{status}',[BookingController::class, 'updateBookingCompleteEmp'])->middleware(RequiredParameters::class);
	Route::post('/invoice/get',[BookingController::class, 'InvoicePdf'])->middleware(RequiredParameters::class);
	Route::post('/booking/type/list',[BookingController::class, 'getBookingTypeList'])->middleware(RequiredParameters::class);
	Route::post('/document/upload',[BookingController::class, 'uploadDocument'])->middleware(RequiredParameters::class);
	Route::post('/document/list',[BookingController::class, 'documentList'])->middleware(RequiredParameters::class);
	Route::put('/document/status/update/{id}/{status}',[BookingController::class, 'updateDocumentStatus'])->middleware(RequiredParameters::class);
	Route::post('/order/copy',[BookingController::class, 'orderCopy'])->middleware(RequiredParameters::class);
	Route::post('/booking/delete/all',[BookingController::class, 'deleteALl'])->middleware(RequiredParameters::class);
	Route::post('/booking/invoice',[BookingController::class, 'invoice'])->middleware(RequiredParameters::class);
	Route::post('/invoice/get',[BookingController::class, 'getInvoice'])->middleware(RequiredParameters::class);
	Route::post('/invoice/items/list',[BookingController::class, 'getInvoiceItemList'])->middleware(RequiredParameters::class);
	Route::put('/invoice/items/status/update/{id}/{status}',[BookingController::class, 'updateInvoiceItemStatus'])->middleware(RequiredParameters::class);
	Route::post('/invoice/items/update',[BookingController::class, 'updateInvoiceItem'])->middleware(RequiredParameters::class);
	Route::post('/invoice/regenerate',[BookingController::class, 'regenerateInvoice'])->middleware(RequiredParameters::class);
	Route::post('/sline/list',[BookingController::class, 'slineList'])->middleware(RequiredParameters::class);
	Route::post('/tranportor/list',[BookingController::class, 'transportorName'])->middleware(RequiredParameters::class);
	Route::post('/invoice/update',[BookingController::class, 'invoiceUpdate'])->middleware(RequiredParameters::class);
	Route::post('/ship-no/list',[BookingController::class, 'shipNoList'])->middleware(RequiredParameters::class);
	Route::post('/billed-to/list',[BookingController::class, 'billedToList'])->middleware(RequiredParameters::class);
	Route::post('/booking-from/list',[BookingController::class, 'bookingFromList'])->middleware(RequiredParameters::class);
	Route::post('/booking-to/list',[BookingController::class, 'bookingtoList'])->middleware(RequiredParameters::class);
	Route::post('/vehicle/list',[BookingController::class, 'vehicleList'])->middleware(RequiredParameters::class);
	Route::post('/bill-no/list',[BookingController::class, 'billNoList'])->middleware(RequiredParameters::class);
	
	Route::post('/company/create',[CompanyController::class, 'createCompany'])->middleware(RequiredParameters::class);
	Route::post('/company/list',[CompanyController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/company/get',[CompanyController::class, 'getCompany'])->middleware(RequiredParameters::class);
	Route::post('/company/update',[CompanyController::class, 'updateCompany'])->middleware(RequiredParameters::class);
	Route::put('/company/status/update/{id}/{status}',[CompanyController::class, 'updateCompanyStatus'])->middleware(RequiredParameters::class);
	Route::post('/company/list/simply',[CompanyController::class, 'getListSimple'])->middleware(RequiredParameters::class);
	Route::post('/company/deal/list',[CompanyController::class, 'getCompanyDealList'])->middleware(RequiredParameters::class);
	Route::post('/company/contact/list',[CompanyController::class, 'getCompanyContactList'])->middleware(RequiredParameters::class);
	Route::post('/company/overview',[CompanyController::class, 'getCompanyOverView'])->middleware(RequiredParameters::class);
	Route::post('/upload/accounts',[CompanyController::class, 'uploadAccounts'])->middleware(RequiredParameters::class);
	Route::post('/assign/company',[CompanyController::class, 'assignCompany'])->middleware(RequiredParameters::class);
	Route::post('/update/company/status',[CompanyController::class, 'updateCompanyAdStatus'])->middleware(RequiredParameters::class);
	
	Route::post('/customer/create',[ContactController::class, 'createContact'])->middleware(RequiredParameters::class);
	Route::post('/customer/list',[ContactController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/customer/get',[ContactController::class, 'getContact'])->middleware(RequiredParameters::class);
	Route::post('/customer/update',[ContactController::class, 'updateContact'])->middleware(RequiredParameters::class);
	Route::put('/customer/status/update/{id}/{status}',[ContactController::class, 'updateContactStatus'])->middleware(RequiredParameters::class);
	Route::post('/customer/list/all',[ContactController::class, 'getListAll'])->middleware(RequiredParameters::class);
	
	Route::post('/deal/create',[DealController::class, 'createDeal'])->middleware(RequiredParameters::class);
	Route::post('/deal/list',[DealController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/deal/get',[DealController::class, 'getDeal'])->middleware(RequiredParameters::class);
	Route::post('/deal/update',[DealController::class, 'updateDeal'])->middleware(RequiredParameters::class);
	Route::put('/deal/status/update/{id}/{status}',[DealController::class, 'updateDealStatus'])->middleware(RequiredParameters::class);
	
	Route::post('/task/create',[TaskController::class, 'createTask'])->middleware(RequiredParameters::class);
	Route::post('/task/list',[TaskController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/task/get',[TaskController::class, 'getTask'])->middleware(RequiredParameters::class);
	Route::post('/task/update',[TaskController::class, 'updateTask'])->middleware(RequiredParameters::class);
	Route::put('/task/status/update/{id}/{status}',[TaskController::class, 'updateTaskStatus'])->middleware(RequiredParameters::class);
	Route::post('/task/short/create',[TaskController::class, 'createShortTask'])->middleware(RequiredParameters::class);
	Route::post('/task/list/get',[TaskController::class, 'getTaskList'])->middleware(RequiredParameters::class);
	
	Route::post('/activity/create',[ActivityController::class, 'createActivity'])->middleware(RequiredParameters::class);
	Route::post('/activity/list',[ActivityController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/activity/get',[ActivityController::class, 'getActivity'])->middleware(RequiredParameters::class);
	Route::post('/activity/update',[ActivityController::class, 'updateActivity'])->middleware(RequiredParameters::class);
	Route::put('/activity/status/update/{id}/{status}',[ActivityController::class, 'updateActivityStatus'])->middleware(RequiredParameters::class);
	
	Route::post('/notes/create',[NotesController::class, 'create'])->middleware(RequiredParameters::class);
	Route::post('/notes/list',[NotesController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/notes/get',[NotesController::class, 'getData'])->middleware(RequiredParameters::class);
	Route::post('/notes/update',[NotesController::class, 'update'])->middleware(RequiredParameters::class);
	Route::put('/notes/status/update/{id}/{status}',[NotesController::class, 'updateStatus'])->middleware(RequiredParameters::class);
	

	Route::post('/target/list',[InvoicesController::class, 'getList'])->middleware(RequiredParameters::class);
	Route::post('/invoice/customers',[InvoicesController::class, 'getInvoiceCustomers'])->middleware(RequiredParameters::class);
	Route::post('/invoice/item/add',[InvoicesController::class, 'AddInvoiceItem'])->middleware(RequiredParameters::class);
	Route::put('/target/status/update/{id}/{status}',[InvoicesController::class, 'updateStatus'])->middleware(RequiredParameters::class);
	Route::post('/invoice/csv/generate',[InvoicesController::class, 'generateCsv'])->middleware(RequiredParameters::class);
	Route::post('/invoice/pdf/generate',[InvoicesController::class, 'generatePDF'])->middleware(RequiredParameters::class);
	Route::post('/payment/csv/generate',[InvoicesController::class, 'generatePaymentCSV'])->middleware(RequiredParameters::class);
	
	Route::post('/dashboard/report/get',[DashboardController::class, 'getDashboardReports'])->middleware(RequiredParameters::class);
	Route::post('/payment/update',[InvoicesController::class, 'updatePayment'])->middleware(RequiredParameters::class);

});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
