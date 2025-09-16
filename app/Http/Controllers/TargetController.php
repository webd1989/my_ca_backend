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
use App\Models\Targets;

class TargetController extends Controller{

	private static $Users;
	private static $Tasks;
	private static $TokenHelper;
	private static $UserCompanyLink;
	private static $Targets;
	
	public function __construct(){
		self::$Users = new Users();
		self::$Tasks = new Tasks();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		self::$Targets = new Targets();
		
	}
	public function getData(Request $request){
		$noteData = self::$Targets->where('id',$request->id)->first();
		return response()->json(['success'=>true,'note_data'=>$noteData],200);
	}
	public function getListAll(Request $request){
		$notes = self::$Targets->where('status','!=',3)->orderBy('date_str','ASC')->get();
		foreach($notes as $key => $note){
			$months = [
						'01' => 'January',
						'02' => 'February',
						'03' => 'March',
						'04' => 'April',
						'05' => 'May',
						'06' => 'June',
						'07' => 'July',
						'08' => 'August',
						'09' => 'September',
						'10' => 'October',
						'11' => 'November',
						'12' => 'December',
					   ];
			$note->display_month = $months[$note->month];
			$note->display_to_month = $note->to_month > 0 ? $months[$note->to_month] : '';
		}
		return response()->json(['success'=>true,'targets'=>$notes],200);
	}
	public function getList(Request $request){
		$query = self::$Targets->where('status','!=',3);
		
		if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('target_amt', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('month', 'like', '%'.$SearchKeyword.'%')
                    ->orWhere('year', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}
		
		$notes = $query->orderBy('date_str','ASC')->paginate(10);
		
		foreach($notes as $key => $note){
			$months = [
						'01' => 'January',
						'02' => 'February',
						'03' => 'March',
						'04' => 'April',
						'05' => 'May',
						'06' => 'June',
						'07' => 'July',
						'08' => 'August',
						'09' => 'September',
						'10' => 'October',
						'11' => 'November',
						'12' => 'December',
					   ];
			$note->display_date = date('m/d/Y',strtotime($note->created_at));
			$note->display_month = $months[$note->month];
			$note->display_to_month = $note->to_month > 0 ? $months[$note->to_month] : '';
		}
		return response()->json(['success'=>true,'users'=>$notes],200);
	}
	public function updateStatus(Request $request,$id,$status){
		$users = self::$Targets->where('id',$id)->update(['status' => $status]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function create(Request $request){
		$validator = Validator::make($request->all(), [
			'month' => 'required|numeric',
			'year' => 'required|numeric',
			'to_month' => 'required|numeric',
			'to_year' => 'required|numeric',
			'target_amt' => 'required|numeric',
			'target_piece' => 'required|numeric',
			
		],[
			
			'month.required' => 'Please enter month.',
			'year.required' => 'Please enter year.',
			'to_month.required' => 'Please enter to month.',
			'to_year.required' => 'Please enter to year.',
			'target_amt.required' => 'Please enter traget amount.',
			'target_piece.required' => 'Please enter traget piece.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			
			if($errors->first('month')){
				return response()->json(['success'=>false, 'message' => $errors->first('month')]);
			}
			if($errors->first('year')){
				return response()->json(['success'=>false, 'message' => $errors->first('year')]);
			}
			if($errors->first('to_month')){
				return response()->json(['success'=>false, 'message' => $errors->first('to_month')]);
			}
			if($errors->first('to_year')){
				return response()->json(['success'=>false, 'message' => $errors->first('to_year')]);
			}
			if($errors->first('target_amt')){
				return response()->json(['success'=>false, 'message' => $errors->first('target_amt')]);
			}
			if($errors->first('target_piece')){
				return response()->json(['success'=>false, 'message' => $errors->first('target_piece')]);
			}
		}else{
			$setData['added_by'] = $GLOBALS['USER.ID'];
				$setData['target_amt'] = $request->target_amt;
				$setData['month'] = $request->month;
				$setData['year'] = $request->year;
				$setData['to_month'] = $request->to_month;
				$setData['to_year'] = $request->to_year;
				$setData['target_piece'] = $request->target_piece;
				$setData['date_str'] = strtotime('01-'.$request->month.'-'.$request->year);
				self::$Targets->create($setData);
				return response()->json(['success'=>true, 'message' => 'Target added successfully']);
		}
	}
	public function update(Request $request){
		$validator = Validator::make($request->all(), [
			'month' => 'required|numeric',
			'year' => 'required|numeric',
			'to_month' => 'required|numeric',
			'to_year' => 'required|numeric',
			'target_amt' => 'required|numeric',
			'target_piece' => 'required|numeric',
			
		],[
			'month.required' => 'Please enter month.',
			'year.required' => 'Please enter year.',
			'to_month.required' => 'Please enter to month.',
			'to_year.required' => 'Please enter to year.',
			'target_amt.required' => 'Please enter traget amount.',
			'target_piece.required' => 'Please enter traget piece.',
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('month')){
				return response()->json(['success'=>false, 'message' => $errors->first('month')]);
			}
			if($errors->first('year')){
				return response()->json(['success'=>false, 'message' => $errors->first('year')]);
			}
			if($errors->first('to_month')){
				return response()->json(['success'=>false, 'message' => $errors->first('to_month')]);
			}
			if($errors->first('to_year')){
				return response()->json(['success'=>false, 'message' => $errors->first('to_year')]);
			}
			if($errors->first('target_amt')){
				return response()->json(['success'=>false, 'message' => $errors->first('target_amt')]);
			}
			if($errors->first('target_piece')){
				return response()->json(['success'=>false, 'message' => $errors->first('target_piece')]);
			}
		}else{
			$setData['target_amt'] = $request->target_amt;
				$setData['month'] = $request->month;
				$setData['year'] = $request->year;
				$setData['to_month'] = $request->to_month;
				$setData['to_year'] = $request->to_year;
				$setData['target_piece'] = $request->target_piece;
				$setData['date_str'] = strtotime('01-'.$request->month.'-'.$request->year);
				
				self::$Targets->where('id',$request->id)->update($setData);
				return response()->json(['success'=>true, 'message' => 'Target updated successfully']);
		}
	}
}
