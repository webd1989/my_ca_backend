<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Validator;
use Illuminate\Validation\Rule;
use ReallySimpleJWT\Token;
use App\Models\Deals;
use App\Models\Responses;
use Session;
use App\Models\TokenHelper;
use App\Models\Users;
use App\Models\UserCompanyLink;

class DealController extends Controller{

	private static $Deals;
	private static $TokenHelper;
	private static $Users;
	private static $UserCompanyLink;
	
	public function __construct(){
		self::$Deals = new Deals();
		self::$TokenHelper = new TokenHelper();
		self::$Users = new Users();
		self::$UserCompanyLink = new UserCompanyLink();
		
	}
	public function getDeal(Request $request){
		$user = self::$Deals->where('id',$request->id)->first();
		return response()->json(['success'=>true,'user'=>$user],200);
	}
	public function getList(Request $request){
		$loginUserData = self::$Users->select('type')->where('id',$GLOBALS['USER.ID'])->first();
		$query = self::$Deals->join('users','users.id','=','deals.parent_id')->select(['deals.*','users.name as owner_name'])->where('deals.status','!=',3);
		if($loginUserData->type == 'User'){
			
			$linkedCompanyArray = [];
			$linkedCompanies = self::$UserCompanyLink->where('user_id',$GLOBALS['USER.ID'])->where('status',1)->get();
			foreach($linkedCompanies as $key => $linkedCompany){
				$linkedCompanyArray[] = $linkedCompany->company_id;
			}
			
			$query->whereIn('deals.company_id',$linkedCompanyArray);
		}
		if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('deals.deal_name', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('deals.revenue', 'like', '%'.$SearchKeyword.'%')
					->orWhere('deals.cose_date', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}
		if($request->input('f_funnel')  && $request->input('f_funnel') != ""){
			$query->where('deals.funnel',$request->input('f_funnel'));
		}
		if($request->input('f_stage')  && $request->input('f_stage') != ""){
			$query->where('deals.stage',$request->input('f_stage'));
		}
		if($request->input('f_source')  && $request->input('f_source') != ""){
			$query->where('deals.source',$request->input('f_source'));
		}
		
		$users = $query->paginate(10);
		
		$totalAmt = 0;
		if($loginUserData->type == 'Admin'){
			$deals = self::$Deals->select('revenue')->where('status','!=',3)->get();
		}else{
			$deals = self::$Deals->select('revenue')->whereIn('company_id',$linkedCompanyArray)->where('status','!=',3)->get();
		}
		
		foreach($deals as $key => $deal){
			if($deal->revenue > 0){
				$totalAmt = $totalAmt+$deal->revenue;
			}
		}
		foreach($users as $key => $user){
			$companyData = self::$Users->select('name')->where('id',$user->company_id)->first();
			$user->account_name = $companyData->name;
		}
		foreach($users as $key => $user){
			$user->created_date = date('m/d/Y',strtotime($user->created_at));
		}
		return response()->json(['success'=>true,'users'=>$users,'count' => $deals->count(),'total_amt' => $totalAmt],200);
	}
	public function updateDealStatus(Request $request,$id,$status){
		$users = self::$Deals->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function createDeal(Request $request){
		$validator = Validator::make($request->all(), [
			'deal_name' => 'required',
			'revenue' => 'required|numeric',
			'cose_date' => 'required',
			
		],[
			'deal_name.required' => 'Please enter deal name.',
			'revenue.required' => 'Please enter deal revenue.',
			'cose_date.required' => 'Please enter close date.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('deal_name')){
				return response()->json(['success'=>false, 'message' => $errors->first('deal_name')]);
			}
			if($errors->first('revenue')){
				return response()->json(['success'=>false, 'message' => $errors->first('revenue')]);
			}
			if($errors->first('cose_date')){
				return response()->json(['success'=>false, 'message' => $errors->first('cose_date')]);
			}
		}else{
			$setData['parent_id'] = $GLOBALS['USER.ID'];
			$setData['added_by'] = $GLOBALS['USER.ID'];
			$setData['company_id'] = $request->company_id;
			$setData['deal_name'] = $request->deal_name;
			$setData['revenue'] = $request->revenue;
			$setData['cose_date'] = date('m/d/Y',strtotime($request->cose_date."+1 days"));
			$setData['funnel'] = $request->funnel;
			$setData['stage'] = $request->stage;
			$setData['source'] = $request->source;
			$setData['description'] = $request->description;
			self::$Deals->create($setData);
			return response()->json(['success'=>true, 'message' => 'Deal added successfully']);
		}
	}
	public function updateDeal(Request $request){
		$validator = Validator::make($request->all(), [
			'deal_name' => 'required',
			'revenue' => 'required|numeric',
			'cose_date' => 'required',
			
		],[
			'deal_name.required' => 'Please enter deal name.',
			'revenue.required' => 'Please enter deal revenue.',
			'cose_date.required' => 'Please enter close date.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('deal_name')){
				return response()->json(['success'=>false, 'message' => $errors->first('deal_name')]);
			}
			if($errors->first('revenue')){
				return response()->json(['success'=>false, 'message' => $errors->first('revenue')]);
			}
			if($errors->first('cose_date')){
				return response()->json(['success'=>false, 'message' => $errors->first('cose_date')]);
			}
		}else{
			$setData['company_id'] = $request->company_id;
			$setData['deal_name'] = $request->deal_name;
			$setData['revenue'] = $request->revenue;
			$setData['cose_date'] = date('m/d/Y',strtotime($request->cose_date."+1 days"));
			$setData['funnel'] = $request->funnel;
			$setData['stage'] = $request->stage;
			$setData['source'] = $request->source;
			$setData['description'] = $request->description;
			
			self::$Deals->where('id',$request->id)->update($setData);
			return response()->json(['success'=>true, 'message' => 'Deal updated successfully']);
		}
	}
}
