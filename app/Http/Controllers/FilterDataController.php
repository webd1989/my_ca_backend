<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Validation\Rule;
use ReallySimpleJWT\Token;
use App\Models\Users;
use App\Models\Responses;
use Session;
use App\Models\TokenHelper;
use App\Models\UserCompanyLink;
use App\Models\Bookings;
use App\Models\Customers;
use App\Models\BookingImages;
use Mpdf\Mpdf;
use App\Models\Invoices;
use App\Models\InvoiceItems;

class FilterDataController extends Controller{
	private static $Users;
	private static $TokenHelper;
	private static $UserCompanyLink;
	private static $Bookings;
	private static $Customers;
	private static $BookingImages;
	private static $Invoices;
	private static $InvoiceItems;
	
	public function __construct(){
		self::$Users = new Users();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		self::$Bookings = new Bookings();
		self::$Customers = new Customers();
		self::$BookingImages = new BookingImages();
		self::$Invoices = new Invoices();
		self::$InvoiceItems = new InvoiceItems();
		
	}
	public function shipNoList($request){
		$query2 = self::$Bookings->select('shipment_no')->where('shipment_no','!=',NULL);
		if($request->input('sline')  && count($request->input('sline')) > 0){
			$query2->whereIn('sline', $request->input('sline')) ;
		}
		if($request->input('is_billed')  && $request->input('is_billed') != ""){
			if($request->input('is_billed') == 'Yes'){
				$query2->where('l_invo_no','!=',NULL) ;
			}else{
				$query2->where('l_invo_no',NULL) ;
			}
			
		}
		$shipNos = $query2->where('status','!=',3)->orderBy('shipment_no')->groupBy('shipment_no')->get();
		return $shipNos;
	}
}