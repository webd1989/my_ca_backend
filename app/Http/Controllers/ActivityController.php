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
use App\Models\Activities;
use App\Models\Tasks;
use App\Models\Responses;
use Session;
use App\Models\TokenHelper;

class ActivityController extends Controller{

	private static $Users;
	private static $TokenHelper;
	private static $Activities;
	private static $Tasks;
	
	public function __construct(){
		self::$Users = new Users();
		self::$TokenHelper = new TokenHelper();
		self::$Activities = new Activities();
		self::$Tasks = new Tasks();
		
	}
	public function getContact(Request $request){
		$user = self::$Activities->where('id',$request->id)->first();
		return response()->json(['success'=>true,'user'=>$user],200);
	}
	public function getList(Request $request){
		$query = self::$Activities->where('company_id',$request->company_id)->where('status','!=',3);
		
		if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('name', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('email', 'like', '%'.$SearchKeyword.'%')
					->orWhere('designation', 'like', '%'.$SearchKeyword.'%')
                    ->orWhere('phone', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}
		
		$activities = $query->paginate(10);
		foreach($activities as $key => $activity){
			$contactName = '';
			if($activity->contact_id > 0){
				$contactData = self::$Users->select('name')->where('id',$activity->contact_id)->first();
				$contactName = $contactData->name;
			}
			$activity->contact_name = $contactName;
			$activity->created_a_date = date('m/d/Y',strtotime($activity->created_at));
			$activity->created_a_time = date('h:i a',strtotime($activity->created_at));
		}
		return response()->json(['success'=>true,'activities'=>$activities],200);
	}
	public function updateContactStatus(Request $request,$id,$status){
		$users = self::$Users->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function createActivity(Request $request){
		if($request->save_type == 'New'){
			$validator = Validator::make($request->all(), [
				'company_id' => 'required',
				'task_name' => 'required',
				'description' => 'required'
				
			],[
				'company_id.required' => 'Please select account.',
				'task_name.required' => 'Please enter task name.',
				'description.required' => 'Please enter description.',
				
			]);
			if($validator->fails()){
				$errors = $validator->errors();
				if($errors->first('company_id')){
					return response()->json(['success'=>false, 'message' => $errors->first('company_id')]);
				}
				if($errors->first('task_name')){
					return response()->json(['success'=>false, 'message' => $errors->first('task_name')]);
				}
				if($errors->first('description')){
					return response()->json(['success'=>false, 'message' => $errors->first('description')]);
				}
			}else{
				$setData['parent_id'] = $GLOBALS['USER.ID'];
				$setData['added_by'] = $GLOBALS['USER.ID'];
				$setData['company_id'] = $request->company_id;
				$setData['deal_id'] = $request->deal_id;
				$setData['contact_id'] = $request->contact_id;
				$setData['task_name'] = $request->task_name;
				$setData['description'] = $request->description;
				$setData['type'] = $request->type;
				$setData['result'] = $request->result;
				
				self::$Activities->create($setData);
				return response()->json(['success'=>true, 'message' => 'Activity added successfully']);
			}
		}
		if($request->save_type == 'Complete'){
			$validator = Validator::make($request->all(), [
				'company_id' => 'required',
				'task_id' => 'required',
				'description' => 'required'
				
			],[
				'company_id.required' => 'Please select account.',
				'task_id.required' => 'Please select task name.',
				'description.required' => 'Please enter description.',
				
			]);
			if($validator->fails()){
				$errors = $validator->errors();
				if($errors->first('company_id')){
					return response()->json(['success'=>false, 'message' => $errors->first('company_id')]);
				}
				if($errors->first('task_id')){
					return response()->json(['success'=>false, 'message' => $errors->first('task_id')]);
				}
				if($errors->first('description')){
					return response()->json(['success'=>false, 'message' => $errors->first('description')]);
				}
			}else{
				
				$taskData = self::$Tasks->where('id',$request->task_id)->first();
				
				$expType = explode('_',$taskData->type);
				
				$setData['parent_id'] = $GLOBALS['USER.ID'];
				$setData['added_by'] = $GLOBALS['USER.ID'];
				$setData['company_id'] = $request->company_id;
				$setData['deal_id'] = $request->deal_id;
				$setData['contact_id'] = $request->contact_id;
				$setData['task_id'] = $request->task_id;
				$setData['task_name'] = $taskData->task_name;
				$setData['description'] = $request->description;
				$setData['type'] = 'p2_'.$expType[1];
				$setData['result'] = $request->result;
				
				self::$Activities->create($setData);
				
				self::$Tasks->where('id',$request->task_id)->update(['is_completed' => 1]);
				
				return response()->json(['success'=>true, 'message' => 'Activity added successfully']);
			}
		}
		
	
	}
	public function updateContact(Request $request){}
}
