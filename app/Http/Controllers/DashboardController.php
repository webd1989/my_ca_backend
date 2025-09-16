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
use App\Models\Deals;
use App\Models\Tasks;
use App\Models\UserCompanyLink;

class DashboardController extends Controller{

	private static $Users;
	private static $TokenHelper;
	private static $Deals;
	private static $Tasks;
	private static $UserCompanyLink;
	
	public function __construct(){
		self::$Users = new Users();
		self::$TokenHelper = new TokenHelper();
		self::$Deals = new Deals();
		self::$Tasks = new Tasks();
		self::$UserCompanyLink = new UserCompanyLink();
	}
	public function getDashboardReports(Request $request){
		
		$loginUserData = self::$Users->GetRecordById($GLOBALS['USER.ID']);
		
		if($request->type =='Today'){	
			$startDate = date('Y-m-d 00:00:00');
			$endDate = date('Y-m-d 23:59:59');
		}
		if($request->type =='Week'){
			 $startDate = date('Y-m-d 00:00:00', strtotime("sunday -1 week"));
			 $endDate = date('Y-m-d 23:59:59', strtotime("saturday 0 week"));
		}
		if($request->type =='Month'){	
			$startDate = date('Y-m-01 00:00:00');
			$endDate = date('Y-m-t 23:59:59');
		}
		
		if($loginUserData->type == 'Admin'){
			
			if($request->user_id > 0){
				
				$linkedCompanyArray = [];
				$linkedCompanies = self::$UserCompanyLink->where('user_id',$request->user_id)->where('status',1)->get();
				foreach($linkedCompanies as $key => $linkedCompany){
					$linkedCompanyArray[] = $linkedCompany->company_id;
				}
				
				$taskCount = self::$Tasks->whereIn('company_id',$linkedCompanyArray)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				$taskCompletedCount = self::$Tasks->whereIn('company_id',$linkedCompanyArray)->where('is_completed',1)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				$accountCount = self::$Users->where('type','Company')->whereIn('id',$linkedCompanyArray)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				$contactCount = self::$Users->whereIn('company_id',$linkedCompanyArray)->where('type','Contact')->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				
			}else{
		
				$taskCount = self::$Tasks->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				$taskCompletedCount = self::$Tasks->where('is_completed',1)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				$accountCount = self::$Users->where('type','Company')->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
				$contactCount = self::$Users->where('type','Contact')->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
			
			}
		
		}else{
			
			$linkedCompanyArray = [];
			$linkedCompanies = self::$UserCompanyLink->where('user_id',$GLOBALS['USER.ID'])->where('status',1)->get();
			foreach($linkedCompanies as $key => $linkedCompany){
				$linkedCompanyArray[] = $linkedCompany->company_id;
			}
			
			$taskCount = self::$Tasks->whereIn('company_id',$linkedCompanyArray)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
			$taskCompletedCount = self::$Tasks->whereIn('company_id',$linkedCompanyArray)->where('is_completed',1)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
			$accountCount = self::$Users->where('type','Company')->whereIn('id',$linkedCompanyArray)->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
			$contactCount = self::$Users->whereIn('company_id',$linkedCompanyArray)->where('type','Contact')->where('created_at','>=',$startDate)->where('created_at','<=',$endDate)->count();
		}
		
		return response()->json(['success'=>true,'startDate' => $startDate, 'endDate' => $endDate, 'total_task_added'=>$taskCount,'total_task_completed'=>$taskCompletedCount,'total_calls_made'=>0,'total_email_sent'=>0,'total_text_sent'=>0,'new_account_added'=>$accountCount,'new_contact_added'=>$contactCount],200);
	}
	
}
