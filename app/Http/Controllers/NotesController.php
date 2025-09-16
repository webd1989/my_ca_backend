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
use App\Models\Notes;

class NotesController extends Controller{

	private static $Users;
	private static $Tasks;
	private static $TokenHelper;
	private static $UserCompanyLink;
	private static $Notes;
	
	public function __construct(){
		self::$Users = new Users();
		self::$Tasks = new Tasks();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		self::$Notes = new Notes();
		
	}
	public function getData(Request $request){
		$noteData = self::$Notes->where('id',$request->id)->first();
		return response()->json(['success'=>true,'note_data'=>$noteData],200);
	}
	public function getList(Request $request){
		$notes = self::$Notes->where('status','!=',3)->where('company_id',$request->company_id)->orderBy('id','DESC')->get();
		foreach($notes as $key => $note){
			$note->display_date = date('m/d/Y',strtotime($note->created_at));
		}
		$total_notes = self::$Notes->where('status','!=',3)->where('company_id',$request->company_id)->count();
		return response()->json(['success'=>true,'notes'=>$notes,'total_notes' => $total_notes],200);
	}
	public function updateStatus(Request $request,$id,$status){
		$users = self::$Notes->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function create(Request $request){
		$validator = Validator::make($request->all(), [
			'company_notes' => 'required',
			
		],[
			'company_notes.required' => 'Please enter note.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('company_notes')){
				return response()->json(['success'=>false, 'message' => $errors->first('company_notes')]);
			}
		}else{
			$setData['added_by'] = $GLOBALS['USER.ID'];
			$setData['company_id'] = $request->company_id;
			$setData['notes'] = $request->company_notes;
			self::$Notes->create($setData);
			return response()->json(['success'=>true, 'message' => 'Note added successfully']);
		}
	}
	public function update(Request $request){
		$validator = Validator::make($request->all(), [
			'company_notes' => 'required',
			
		],[
			'company_notes.required' => 'Please select account.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('company_notes')){
				return response()->json(['success'=>false, 'message' => $errors->first('company_notes')]);
			}
		}else{
			
			$setData['notes'] = $request->company_notes;
			
			self::$Notes->where('id',$request->id)->update($setData);
			return response()->json(['success'=>true, 'message' => 'Note updated successfully']);
		}
	}
}
