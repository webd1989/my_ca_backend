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
use App\Models\Tasks;
use App\Models\Responses;
use Session;
use App\Models\TokenHelper;
use App\Models\UserCompanyLink;

class TaskController extends Controller{

	private static $Users;
	private static $Tasks;
	private static $TokenHelper;
	private static $UserCompanyLink;
	
	public function __construct(){
		self::$Users = new Users();
		self::$Tasks = new Tasks();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		
	}
	public function getTask(Request $request){
		$taskData = self::$Tasks->where('id',$request->id)->first();
		
		$taskData->task_formated_date = date('Y-m-d',strtotime($taskData->task_date));
		return response()->json(['success'=>true,'task_data'=>$taskData],200);
	}
	public function getList(Request $request){
		$query = self::$Tasks->select(['tasks.*'])->where('tasks.status','!=',3)->where('tasks.is_completed',0);
		$loginUserData = self::$Users->select('type')->where('id',$GLOBALS['USER.ID'])->first();
		
		if($request->input('company_id') && $request->input('company_id') > 0){
			$query->where('tasks.company_id',$request->input('company_id'));
		}else{
			if($loginUserData->type == 'User'){
				$linkedCompanyArray = [];
				$linkedCompanies = self::$UserCompanyLink->where('user_id',$GLOBALS['USER.ID'])->where('status',1)->get();
				foreach($linkedCompanies as $key => $linkedCompany){
					$linkedCompanyArray[] = $linkedCompany->company_id;
				}
				
				$query->whereIn('tasks.company_id',$linkedCompanyArray);
			}
		}
		
		if($request->input('filter_by')){
			if($request->input('filter_by') == 'Today'){
				$startDate = date('Y-m-d 00:00:00');
				$endDate = date('Y-m-d 23:59:59');
			}
			if($request->input('filter_by') == 'Tomorrow'){
				$tomorrowDate = date('Y-m-d', strtotime('+1 days'));
				$startDate = $tomorrowDate.' 00:00:00';
				$endDate = $tomorrowDate.' 23:59:59';
			}
			if($request->input('filter_by') == 'Week'){
				$startDate = date('Y-m-d 00:00:00', strtotime("sunday -1 week"));
				$endDate = date('Y-m-d 23:59:59', strtotime("saturday 0 week"));
			}
			if($request->input('filter_by') == 'Month'){
				$startDate = date('Y-m-01 00:00:00');
				$endDate = date('Y-m-t 23:59:59');
			}
			$query->where('task_date_full','>=',$startDate)->where('task_date_full','<=',$endDate);
		}
		
		if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('tasks.task_name', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('tasks.type', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}
		
		$users = $query->orderBy('task_date','ASC')->paginate(5);
		
		foreach($users as $key => $user){
			$contactData = self::$Users->select('name')->where('id',$user->contact_id)->first();
			$user->contact_name = isset($contactData->name) ? $contactData->name : '';
		}
		
		$total_task = self::$Tasks->where('status','!=',3)->where('tasks.company_id',$request->input('company_id'))->where('is_completed',0)->count();
		
		return response()->json(['success'=>true,'tasks'=>$users,'total_task' => $total_task],200);
	}
	public function getTaskList(Request $request){
		$tasks = self::$Tasks->where('status','!=',3)->where('is_completed',0)->where('company_id',$request->input('company_id'))->orderBy('task_date','ASC')->get();
		return response()->json(['success'=>true,'tasks'=>$tasks],200);
	}
	public function updateTaskStatus(Request $request,$id,$status){
		$users = self::$Tasks->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function createShortTask(Request $request){
		$validator = Validator::make($request->all(), [
			'company_id' => 'required',
			'type' => 'required',
			'day' => 'required'
			
		],[
			'company_id.required' => 'Please select account.',
			'type.required' => 'Please select type.',
			'day.required' => 'Please select day.'
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('company_id')){
				return response()->json(['success'=>false, 'message' => $errors->first('company_id')]);
			}
			if($errors->first('type')){
				return response()->json(['success'=>false, 'message' => $errors->first('type')]);
			}
			if($errors->first('day')){
				return response()->json(['success'=>false, 'message' => $errors->first('day')]);
			}
		}else{
			
			if($request->type == 'p_call'){
				$type = 'Call';
			}
			if($request->type == 'p_email'){
				$type = 'Email';
			}
			if($request->type == 'p_text'){
				$type = 'Text';
			}
			if($request->day == '1d'){
				$task_date = date('m/d/Y',strtotime("+1 days"));
			}
			if($request->day == '2d'){
				$task_date = date('m/d/Y',strtotime("+2 days"));
			}
			if($request->day == '3d'){
				$task_date = date('m/d/Y',strtotime("+3 days"));
			}
			if($request->day == '5d'){
				$task_date = date('m/d/Y',strtotime("+5 days"));
			}
			if($request->day == '1w'){
				$task_date = date('m/d/Y',strtotime("+1 week"));
			}
			if($request->day == '2w'){
				$task_date = date('m/d/Y',strtotime("+2 week"));
			}
			if($request->day == '4w'){
				$task_date = date('m/d/Y',strtotime("+4 week"));
			}
			if($request->day == '6w'){
				$task_date = date('m/d/Y',strtotime("+6 week"));
			}
			if($request->day == '8w'){
				$task_date = date('m/d/Y',strtotime("+8 week"));
			}
			if($request->day == '12w'){
				$task_date = date('m/d/Y',strtotime("+12 week"));
			}
			$setData['parent_id'] = $GLOBALS['USER.ID'];
			$setData['added_by'] = $GLOBALS['USER.ID'];
			$setData['company_id'] = $request->company_id;
			$setData['deal_id'] = 0;
			$setData['contact_id'] = 0;
			$setData['task_name'] = 'Follow-up '.$type;
			$setData['task_date'] = $task_date;
			$setData['type'] = $request->type;
			$setData['start_time'] = '00:00';
			$setData['end_time'] = '23:59';
			$setData['task_date_full'] = date('Y-m-d',strtotime($task_date)).' 00:00:00';
			
			self::$Tasks->create($setData);
			return response()->json(['success'=>true, 'message' => 'Task added successfully']);
		}
	}
	public function createTask(Request $request){
		$validator = Validator::make($request->all(), [
			'company_id' => 'required',
			'contact_id' => 'required',
			'task_date' => 'required',
			'start_time' => 'required',
			'end_time' => 'required',
			
		],[
			'company_id.required' => 'Please select account.',
			'contact_id.required' => 'Please select contact.',
			'task_date.required' => 'Please enter task date.',
			'start_time.required' => 'Please enter start time.',
			'end_time.required' => 'Please enter end time.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('company_id')){
				return response()->json(['success'=>false, 'message' => $errors->first('company_id')]);
			}
			if($errors->first('contact_id')){
				return response()->json(['success'=>false, 'message' => $errors->first('contact_id')]);
			}
			if($errors->first('task_date')){
				return response()->json(['success'=>false, 'message' => $errors->first('task_date')]);
			}
			if($errors->first('start_time')){
				return response()->json(['success'=>false, 'message' => $errors->first('start_time')]);
			}
			if($errors->first('end_time')){
				return response()->json(['success'=>false, 'message' => $errors->first('end_time')]);
			}
		}else{
			$startTime = $request->start_time !="" ? $request->start_time : '00:00';
			$endTime = $request->end_time !="" ? $request->end_time : '23:59';
			
			if($request->type == 'p_call'){
				$type = 'Call';
			}
			if($request->type == 'p_email'){
				$type = 'Email';
			}
			if($request->type == 'p_text'){
				$type = 'Text';
			}
			if($request->type == 'p_visit'){
				$type = 'Visit';
			}
			if($request->type == 'p_meeting'){
				$type = 'Meeting';
			}
			if($request->type == 'p_notes'){
				$type = 'Notes';
			}
			if($request->type == 'p_other'){
				$type = 'Other';
			}
			
			$setData['parent_id'] = $GLOBALS['USER.ID'];
			$setData['added_by'] = $GLOBALS['USER.ID'];
			$setData['company_id'] = $request->company_id;
			$setData['deal_id'] = $request->deal_id;
			$setData['contact_id'] = $request->contact_id;
			$setData['task_name'] = 'Follow-up '.$type;
			$setData['task_notes'] = $request->task_notes;
			$setData['task_date'] = date('m/d/Y',strtotime($request->task_date));
			$setData['type'] = $request->type;
			$setData['start_time'] = $startTime;
			$setData['end_time'] = $endTime;
			$setData['task_date_full'] = date('Y-m-d',strtotime($request->task_date)).' '.$startTime.':00';
			
			self::$Tasks->create($setData);
			return response()->json(['success'=>true, 'message' => 'Task added successfully']);
		}
	}
	public function updateTask(Request $request){
		$validator = Validator::make($request->all(), [
			'company_id' => 'required',
			'contact_id' => 'required',
			'task_date' => 'required',
			'start_time' => 'required',
			'end_time' => 'required',
			
		],[
			'company_id.required' => 'Please select account.',
			'contact_id.required' => 'Please select contact.',
			'task_date.required' => 'Please enter task date.',
			'start_time.required' => 'Please enter start time.',
			'end_time.required' => 'Please enter end time.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('company_id')){
				return response()->json(['success'=>false, 'message' => $errors->first('company_id')]);
			}
			
			if($errors->first('contact_id')){
				return response()->json(['success'=>false, 'message' => $errors->first('contact_id')]);
			}
			if($errors->first('task_date')){
				return response()->json(['success'=>false, 'message' => $errors->first('task_date')]);
			}
			if($errors->first('start_time')){
				return response()->json(['success'=>false, 'message' => $errors->first('start_time')]);
			}
			if($errors->first('end_time')){
				return response()->json(['success'=>false, 'message' => $errors->first('end_time')]);
			}
		}else{
			
			$startTime = $request->start_time !="" ? $request->start_time : '00:00';
			$endTime = $request->end_time !="" ? $request->end_time : '23:59';
			
			if($request->type == 'p_call'){
				$type = 'Call';
			}
			if($request->type == 'p_email'){
				$type = 'Email';
			}
			if($request->type == 'p_text'){
				$type = 'Text';
			}
			if($request->type == 'p_visit'){
				$type = 'Visit';
			}
			if($request->type == 'p_meeting'){
				$type = 'Meeting';
			}
			if($request->type == 'p_notes'){
				$type = 'Notes';
			}
			if($request->type == 'p_other'){
				$type = 'Other';
			}
			
			$setData['parent_id'] = $GLOBALS['USER.ID'];
			$setData['added_by'] = $GLOBALS['USER.ID'];
			$setData['company_id'] = $request->company_id;
			$setData['deal_id'] = $request->deal_id;
			$setData['contact_id'] = $request->contact_id;
			$setData['task_name'] = 'Follow-up '.$type;
			$setData['task_notes'] = $request->task_notes;
			$setData['task_date'] = date('m/d/Y',strtotime($request->task_date));
			$setData['type'] = $request->type;
			$setData['start_time'] = $startTime;
			$setData['end_time'] = $endTime;
			$setData['task_date_full'] = date('Y-m-d',strtotime($request->task_date)).' '.$startTime.':00';
			
			self::$Tasks->where('id',$request->id)->update($setData);
			return response()->json(['success'=>true, 'message' => 'Task updated successfully']);
		}
	}
}
