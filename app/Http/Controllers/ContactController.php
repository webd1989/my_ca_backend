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
use App\Models\Customers;

class ContactController extends Controller{

	private static $Users;
	private static $TokenHelper;
	private static $UserCompanyLink;
	private static $Customers;
	
	public function __construct(){
		self::$Users = new Users();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		self::$Customers = new Customers();
		
	}
	public function getContact(Request $request){
		if($request->id > 0){
			$user = self::$Customers->where('id',$request->id)->first();
		}
		if($request->phone && $request->phone != ""){
			$user = self::$Customers->where('phone',$request->phone)->first();
		}
		return response()->json(['success'=>true,'user'=>$user],200);
	}
	public function getListAll(Request $request){
		$query = self::$Customers->where('status','!=',3)->orderBy('name','ASC')->get();
		return response()->json(['success'=>true,'users'=>$query],200);
	}
	public function getList(Request $request){
		
		$loginUserData = self::$Users->select('type')->where('id',$GLOBALS['USER.ID'])->first();
		
		$query = self::$Customers->where('status','!=',3);
		
		
		if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('name', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('email', 'like', '%'.$SearchKeyword.'%')
					->orWhere('city', 'like', '%'.$SearchKeyword.'%')
                    ->orWhere('phone', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}
		
		$users = $query->paginate(10);
		foreach($users as $key => $user){
			$user->created_date = date('m/d/Y',strtotime($user->created_at));
		}
		return response()->json(['success'=>true,'users'=>$users],200);
	}
	public function updateContactStatus(Request $request,$id,$status){
		$users = self::$Customers->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function createContact(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'phone' => 'required|numeric',
			
		],[
			'name.required' => 'Please enter your name.',
			'name.phone' => 'Please enter your phone.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('phone')){
				return response()->json(['success'=>false, 'message' => $errors->first('phone')]);
			}
		}else{
			$count = self::$Customers->where('phone',$request->phone)->count();
			if($count > 0){
				return response()->json(['success'=>false, 'message' => 'Phone already exist']);
			}
			$setData['added_by'] = $GLOBALS['USER.ID'];
			$setData['name'] = $request->name;
			$setData['phone'] = $request->phone;
			$setData['email'] = $request->email;
			$setData['address'] = $request->address;
			$setData['city'] = $request->city;
			$setData['gst_no'] = $request->gst_no;
			self::$Customers->create($setData);
			return response()->json(['success'=>true, 'message' => 'Customer added successfully']);
			
		}
	}
	public function updateContact(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'phone' => 'required|numeric',
			
		],[
			'name.required' => 'Please enter your name.',
			'name.phone' => 'Please enter your phone.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('phone')){
				return response()->json(['success'=>false, 'message' => $errors->first('phone')]);
			}
		}else{
			$count = self::$Customers->where('phone',$request->phone)->where('id','!=',$request->id)->count();
			if($count > 0){
				return response()->json(['success'=>false, 'message' => 'Phone already exist']);
			}
			$setData['name'] = $request->name;
			$setData['phone'] = $request->phone;
			$setData['email'] = $request->email;
			$setData['address'] = $request->address;
			$setData['city'] = $request->city;
			$setData['gst_no'] = $request->gst_no;
			
			self::$Customers->where('id',$request->id)->update($setData);
			return response()->json(['success'=>true, 'message' => 'Customer updated successfully']);
		}
	}
}
