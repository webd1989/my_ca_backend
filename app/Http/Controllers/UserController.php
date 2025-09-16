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

class UserController extends Controller{

	private static $Users;
	private static $TokenHelper;
	private static $UserCompanyLink;
	
	public function __construct(){
		self::$Users = new Users();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		
	}
	public function getUser(Request $request){
		$user = self::$Users->where('id',$request->id)->first();
		return response()->json(['success'=>true,'user'=>$user],200);
	}
	public function getAllList(Request $request){
		$users = self::$Users->select('id','name')->where('type','User')->where('status','!=',3)->get();
		return response()->json(['success'=>true,'users'=>$users],200);
	}
	public function getList(Request $request){
		$query = self::$Users->where('type','User')->where('status','!=',3);
		
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
			$f_data = self::$Users->select('id','name')->where('id',$user->f_city)->first();
			$user->franchise_name = isset($f_data->name) ? $f_data->name : 'N/A';
			$user->created_date = date('m/d/Y',strtotime($user->created_at));
		}
		return response()->json(['success'=>true,'users'=>$users],200);
	}
	public function updateUserStatus(Request $request,$id,$status){
		$users = self::$Users->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function createUser(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'phone' => 'required|numeric',
			'email' => 'required|email',
			'password' => 'required',
			'level' => 'required',
			
		],[
			'name.required' => 'Please enter your name.',
			'phone.required' => 'Please enter your phone.',
			'email.required' => 'Please enter your email.',
			'email.email' => 'Please enter correct email.',
			'password.required' => 'Please enter your password.',
			'level.required' => 'Please select level.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('phone')){
				return response()->json(['success'=>false, 'message' => $errors->first('phone')]);
			}
			if($errors->first('email')){
				return response()->json(['success'=>false, 'message' => $errors->first('email')]);
			}
			if($errors->first('password')){
				return response()->json(['success'=>false, 'message' => $errors->first('password')]);
			}
			if($errors->first('level')){
				return response()->json(['success'=>false, 'message' => $errors->first('level')]);
			}
		}else{
			$count = self::$Users->where('email',$request->email)->count();
			if($count == 0){
				$setData = [
				'name' => ucwords($request->name),
				'phone' => $request->phone,
				'level' => $request->level,
				'email' => strtolower($request->email),
				'password' => password_hash($request->post('password'),PASSWORD_BCRYPT),
				'type' => 'User',
				'designation' => ucwords($request->role),
				'parent_id' => $GLOBALS['USER.ID']
				];
				self::$Users->create($setData);
				return response()->json(['success'=>true, 'message' => 'Employee added successfully']);
			}else{
				return response()->json(['success'=>false, 'message' => 'Email address already exist']);
			}
		}
	}
	public function updateUser(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'phone' => 'required|numeric',
			'email' => 'required|email',
			'level' => 'required',
			
		],[
			'name.required' => 'Please enter your name.',
			'phone.required' => 'Please enter your phone.',
			'email.required' => 'Please enter your email.',
			'email.email' => 'Please enter correct email.',
			'level.required' => 'Please select level.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('phone')){
				return response()->json(['success'=>false, 'message' => $errors->first('phone')]);
			}
			if($errors->first('email')){
				return response()->json(['success'=>false, 'message' => $errors->first('email')]);
			}
			if($errors->first('level')){
				return response()->json(['success'=>false, 'message' => $errors->first('level')]);
			}
		}else{
			$count = self::$Users->where('email',$request->email)->where('id','!=',$request->id)->count();
			if($count == 0){
				
				$setData['name'] = ucwords($request->name);
				$setData['phone'] = $request->phone;
				$setData['level'] = $request->level;
				$setData['email'] = strtolower($request->email);
				$setData['designation'] = ucwords($request->role);
				if($request->post('password') != ""){
					$setData['password'] = password_hash($request->post('password'),PASSWORD_BCRYPT);
				}
				
				self::$Users->where('id',$request->id)->update($setData);
				return response()->json(['success'=>true, 'message' => 'Employee updated successfully']);
			}else{
				return response()->json(['success'=>false, 'message' => 'Email address already exist']);
			}
		}
	}
}
