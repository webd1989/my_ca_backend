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

class CompanyController extends Controller{

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
	public function getListSimple(Request $request){
		
		$loginUserData = self::$Users->GetRecordById($GLOBALS['USER.ID']);
		
		$query = self::$Users->where('type','Company')->where('status','!=',3);
		
		if($loginUserData->type != 'Admin'){
			
			$linkedCompanyArray = [];
			$linkedCompanies = self::$UserCompanyLink->where('user_id',$GLOBALS['USER.ID'])->where('status',1)->get();
			foreach($linkedCompanies as $key => $linkedCompany){
				$linkedCompanyArray[] = $linkedCompany->company_id;
			}
			
			$query = $query->whereIn('id',$linkedCompanyArray);
		}
		$users = $query->get();
		
		return response()->json(['success'=>true,'users'=>$users],200);
	}
	public function assignCompany(Request $request){
		
		self::$UserCompanyLink->where('company_id',$request->company_id)->update(['status'=> 2]);
		
		foreach($request->user_account_id as $key => $userID){
			$exist = self::$UserCompanyLink->where('company_id',$request->company_id)->where('user_id',$userID['user_id'])->first();
			if(isset($exist->id)){
				self::$UserCompanyLink->where('id',$exist->id)->update(['status'=>1]);
			}else{
				self::$UserCompanyLink->insert(['user_id' => $userID['user_id'],'company_id' => $request->company_id]);
			}
		}
		
		return response()->json(['success'=>true,'message' => 'Company assign to user successfully'],200);
	}
	public function getCompany(Request $request){
		$user = self::$Users->where('id',$request->id)->first();
		$equipment_types = '';
		if($user->equipment_types != "" && is_array($user->equipment_types)){
			$equipment_types = implode(',',json_decode($user->equipment_types));
		}
		$modes = '';
		if($user->modes != "" && is_array($user->modes)){
			$modes = implode(',',json_decode($user->modes));
		}
		$pain_paints = '';
		if($user->pain_paints != "" && is_array($user->pain_paints)){
			$pain_paints = implode(',',json_decode($user->pain_paints));
		}
		$contracted = '';
		if($user->contracted != "" && is_array($user->contracted)){
			$contracted = implode(',',json_decode($user->contracted));
		}
		return response()->json(['success'=>true,'user'=>$user,'equipment_types' => $equipment_types,'modes' => $modes,'pain_paints' => $pain_paints,'contracted' => $contracted],200);
	}
	public function getCompanyOverView(Request $request){
		$tasks = self::$Tasks->where('status','!=',3)->where('is_completed',0)->where('company_id',$request->input('company_id'))->orderBy('task_date','ASC')->get();
		$deals = self::$Deals->where('company_id',$request->company_id)->where('status','!=',3)->get();
		$contacts = self::$Users->where('company_id',$request->company_id)->where('type','Contact')->where('status','!=',3)->get();
		$loginUserData = self::$Users->select('type','name')->where('id',$GLOBALS['USER.ID'])->first();
		if($loginUserData->type == 'Admin'){
			$userArray = [];
			$linkedDatas = self::$UserCompanyLink->where('company_id',$request->company_id)->where('status',1)->get();
			foreach($linkedDatas as $key => $linkedData){
				$userData = self::$Users->select('name')->where('id',$linkedData->user_id)->first();
				$userArray[] = $userData->name;
			}
			if(count($userArray) > 0){
				$AccountOwner = implode(', ',$userArray);
			}else{
				$AccountOwner = $loginUserData->name;
			}
			
		}else{
			$AccountOwner = $loginUserData->name;
		}
		return response()->json(['success'=>true,'deals'=>$deals,'contacts'=>$contacts,'tasks'=>$tasks,'account_owner' => $AccountOwner],200);
	}
	public function getCompanyDealList(Request $request){
		$deals = self::$Deals->where('company_id',$request->company_id)->where('status','!=',3)->get();
		return response()->json(['success'=>true,'deals'=>$deals],200);
	}
	public function getCompanyContactList(Request $request){
		$totalContacts = self::$Users->where('company_id',$request->company_id)->where('type','Contact')->where('status','!=',3)->count();
		$contacts = self::$Users->where('company_id',$request->company_id)->where('type','Contact')->where('status','!=',3)->paginate(5);
		return response()->json(['success'=>true,'contacts'=>$contacts,'total_contacts' => $totalContacts],200);
	}
	public function updateCompanyAdStatus(Request $request){
		self::$Users->where('id',$request->id)->update(['company_status'=> $request->status]);
		return response()->json(['success'=>true,'message' => 'Status updated successfully'],200);
	}
	public function uploadAccounts(Request $request){
		$loginUserData = self::$Users->GetRecordById($GLOBALS['USER.ID']);
		if(isset($_FILES['file']['name'])){
			$csvMimes = array('text/csv', 'text/excel', 'text/vnd.msexcel', 'text/vnd.ms-excel' , 'application/vnd.ms-excel');
			if(in_array($_FILES['file']['type'], $csvMimes)){
				if(!empty($_FILES['file']['name']) && $_FILES["file"]["size"] > 0){
					$fileName = $_FILES["file"]["tmp_name"];
					$file = fopen($fileName, "r");
					$num = 1;
					while(($column = fgetcsv($file, 10000, ",")) !== FALSE){
						if($num > 1){
							$companyExist = self::$Users->where('type','Company')->where('phone',trim($column[1]))->count();
							if($companyExist == 0){
								
								if(trim($column[11]) != ""){
									if(strtolower(trim($column[11])) == 'approved'){
										$setData['company_status'] = 2;
									}elseif(strtolower(trim($column[11])) == 'house account'){
										$setData['company_status'] = 3;
									}else{
										$setData['company_status'] = 1;
									}
								}else{
									$setData['company_status'] = 1;
								}
								
								$setData['parent_id'] = $GLOBALS['USER.ID'];
								$setData['type'] = 'Company';
								$setData['name'] = $column[0];
								$setData['phone'] = $column[1];
								$setData['email'] = $column[3];
								$setData['address'] = $column[4];
								$setData['city'] = $column[5];
								$setData['state'] = $column[6];
								$setData['country'] = $column[8];
								$setData['zipcode'] = $column[7];
								$setData['phone_ext'] = $column[2];
								$setData['preffered_communication'] = 'p_call';
								$setData['source'] = $column[9];
								$setData['website'] = $column[12];
								$setData['linkedin'] = $column[13];
								$setData['description'] = $column[14];
								$setData['industry'] = $column[15];
								$setData['annual_revenue'] = $column[16];
								$setData['equipment_types'] = $column[17];
								$setData['modes'] = $column[18];
								$setData['pain_paints'] = $column[19];
								$setData['contracted'] = $column[20];
								$setData['shipment'] = $column[21];
								$setData['pick_drops'] = $column[22];
								$setData['special_requirements'] = $column[23];
								$record = self::$Users->create($setData);
								
								#first contact
								if(trim($column[24]) != "" && trim($column[25]) != "" && trim($column[26]) != ""){
									$setData1['type'] = 'Contact';
									$setData1['name'] = ucwords(trim($column[24]));
									$setData1['phone'] = trim($column[25]);
									$setData1['email'] = strtolower(trim($column[26]));
									$setData1['parent_id'] = $GLOBALS['USER.ID'];
									$setData1['company_id'] = $record->id;
									self::$Users->create($setData1);
								}
								
								#second contact
								if(trim($column[27]) != "" && trim($column[28]) != "" && trim($column[29]) != ""){
									$setData2['type'] = 'Contact';
									$setData2['name'] = ucwords(trim($column[27]));
									$setData2['phone'] = trim($column[28]);
									$setData2['email'] = strtolower(trim($column[29]));
									$setData2['parent_id'] = $GLOBALS['USER.ID'];
									$setData2['company_id'] = $record->id;
									self::$Users->create($setData2);
								}
								
								#third contact
								if(trim($column[30]) != "" && trim($column[31]) != "" && trim($column[32]) != ""){
									$setData3['type'] = 'Contact';
									$setData3['name'] = ucwords(trim($column[30]));
									$setData3['phone'] = trim($column[31]);
									$setData3['email'] = strtolower(trim($column[32]));
									$setData3['parent_id'] = $GLOBALS['USER.ID'];
									$setData3['company_id'] = $record->id;
									self::$Users->create($setData3);
								}
								
								if($loginUserData->type != 'Admin'){
									self::$UserCompanyLink->insert(['user_id' => $GLOBALS['USER.ID'],'company_id' => $record->id]);
								}
								if($loginUserData->type == 'Admin'){
									$userDetails = self::$Users->where('type','User')->where('email',strtolower(trim($column[10])))->first();
									if(isset($userDetails->id)){
										self::$UserCompanyLink->insert(['user_id' => $userDetails->id,'company_id' => $record->id]);
									}
								}
							}
							
						}
						$num++;
					}
					return response()->json(['success'=>true, 'message' => 'Accounts uploaded successfully','num' => $num]);
				}else{
					return response()->json(['success'=>false, 'message' => 'Please choose CSV file']);
				}
			}else{
				return response()->json(['success'=>false, 'message' => 'Invalid file format. Please choose CSV file']);
			}
		}else{
			return response()->json(['success'=>false, 'message' => 'Please choose customer CSV file']);
		}
	}
	public function getList(Request $request){
		
		$loginUserData = self::$Users->GetRecordById($GLOBALS['USER.ID']);
		
		$query = self::$Users->where('type','Company')->where('status','!=',3);
		
		if($loginUserData->type != 'Admin'){
			
			$linkedCompanyArray = [];
			$linkedCompanies = self::$UserCompanyLink->where('user_id',$GLOBALS['USER.ID'])->where('status',1)->get();
			foreach($linkedCompanies as $key => $linkedCompany){
				$linkedCompanyArray[] = $linkedCompany->company_id;
			}
			$query = $query->whereIn('id',$linkedCompanyArray);
		}
		
		if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('name', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('email', 'like', '%'.$SearchKeyword.'%')
                    ->orWhere('phone', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}
		
		$users = $query->paginate(10);
		foreach($users as $key => $user){
			$userArray = [];
			$linkedDatas = self::$UserCompanyLink->where('company_id',$user->id)->where('status',1)->get();
			foreach($linkedDatas as $key => $linkedData){
				$userData = self::$Users->select('name')->where('id',$linkedData->user_id)->first();
				$userArray[] = $userData->name;
			}
			if(count($userArray) > 0){
				$user->owner = implode(', ',$userArray);
			}else{
				$user->owner = 'Connect with User';
			}
			$user->login_user_type = $loginUserData->type;
			$user->created_date = date('m/d/Y',strtotime($user->created_at));
			
			
			if($user->company_status == 1){
				$borderColor = 'border: 2px solid #f00';
			}elseif($user->company_status == 2){
				$borderColor = 'border: 2px solid #093';
			}elseif($user->company_status == 3){
				$borderColor = 'border: 2px solid #F7B239';
			}
			
			$user->border_color = $borderColor;
		}
		return response()->json(['success'=>true,'users'=>$users],200);
	}
	public function updateCompanyStatus(Request $request,$id,$status){
		$users = self::$Users->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function createCompany(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'website' => 'required'
			
		],[
			'name.required' => 'Please enter your name.',
			'website.required' => 'Please enter your website.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('website')){
				return response()->json(['success'=>false, 'message' => $errors->first('website')]);
			}
		}else{
			if($request->phone != "" && !is_numeric($request->phone)){
				return response()->json(['success'=>false, 'message' => 'Invalid phone number']);
			}
			if($request->email != "" && !filter_var($request->email, FILTER_VALIDATE_EMAIL)){
				return response()->json(['success'=>false, 'message' => 'Invalid email address']);
			}
			$setData['parent_id'] = $GLOBALS['USER.ID'];
			$setData['type'] = 'Company';
			$setData['name'] = $request->name;
			$setData['phone'] = $request->phone;
			$setData['email'] = $request->email;
			$setData['address'] = $request->address;
			$setData['city'] = $request->city;
			$setData['state'] = $request->state;
			$setData['country'] = $request->country;
			$setData['zipcode'] = $request->zipcode;
			$setData['phone_ext'] = $request->phone_ext;
			$setData['preffered_communication'] = $request->preffered_communication;
			$setData['source'] = $request->source;
			$setData['website'] = $request->website;
			$setData['linkedin'] = $request->linkedin;
			$setData['description'] = $request->description;
			$setData['industry'] = $request->industry;
			$setData['annual_revenue'] = $request->annual_revenue;
			$setData['equipment_types'] = $request->equipment_types;
			$setData['modes'] = $request->modes;
			$setData['pain_paints'] = $request->pain_paints;
			$setData['contracted'] = $request->contracted;
			$setData['shipment'] = $request->shipment;
			$setData['pick_drops'] = $request->pick_drops;
			$setData['special_requirements'] = $request->special_requirements;
			
			$record = self::$Users->create($setData);
			if($request->user_id > 0){
				self::$UserCompanyLink->insert(['user_id' => $request->user_id,'company_id' => $record->id]);
			}else{
				self::$UserCompanyLink->insert(['user_id' => $GLOBALS['USER.ID'],'company_id' => $record->id]);
			}
			
			return response()->json(['success'=>true, 'message' => 'Company added successfully']);
					
			/*$count = self::$Users->where('email',$request->email)->count();
			if($count == 0){
				$countPhone = self::$Users->where('phone',$request->phone)->count();
				if($countPhone == 0){
				}else{
					return response()->json(['success'=>false, 'message' => 'Phone number already exist']);
				}
			}else{
				return response()->json(['success'=>false, 'message' => 'Email address already exist']);
			}*/
		}
	}
	public function updateCompany(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'website' => 'required|url',
			
		],[
			'name.required' => 'Please enter your name.',
			'website.required' => 'Please enter your website.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('website')){
				return response()->json(['success'=>false, 'message' => $errors->first('website')]);
			}
		}else{
			if($request->phone != "" && !is_numeric($request->phone)){
				return response()->json(['success'=>false, 'message' => 'Invalid phone number']);
			}
			if($request->email != "" && !filter_var($request->email, FILTER_VALIDATE_EMAIL)){
				return response()->json(['success'=>false, 'message' => 'Invalid email address']);
			}
			$setData['name'] = $request->name;
			$setData['phone'] = $request->phone;
			$setData['email'] = $request->email;
			$setData['address'] = $request->address;
			$setData['city'] = $request->city;
			$setData['state'] = $request->state;
			$setData['country'] = $request->country;
			$setData['zipcode'] = $request->zipcode;
			$setData['phone_ext'] = $request->phone_ext;
			$setData['preffered_communication'] = $request->preffered_communication;
			$setData['source'] = $request->source;
			$setData['website'] = $request->website;
			$setData['linkedin'] = $request->linkedin;
			$setData['description'] = $request->description;
			$setData['industry'] = $request->industry;
			$setData['annual_revenue'] = $request->annual_revenue;
			$setData['equipment_types'] = json_encode($request->equipment_types);
			$setData['modes'] = json_encode($request->modes);
			$setData['pain_paints'] = json_encode($request->pain_paints);
			$setData['contracted'] = json_encode($request->contracted);
			$setData['shipment'] = $request->shipment;
			$setData['pick_drops'] = json_encode($request->pick_drops);
			$setData['special_requirements'] = $request->special_requirements;
			
			self::$Users->where('id',$request->id)->update($setData);
			return response()->json(['success'=>true, 'message' => 'Company updated successfully']);
			
			/*$count = self::$Users->where('email',$request->email)->where('id','!=',$request->id)->count();
			if($count == 0){
				
				$countPhone = self::$Users->where('phone',$request->phone)->where('id','!=',$request->id)->count();
				if($countPhone == 0){
					
				}else{
					return response()->json(['success'=>false, 'message' => 'Phone number already exist']);
				}
			}else{
				return response()->json(['success'=>false, 'message' => 'Email address already exist']);
			}*/
		}
	}
}
