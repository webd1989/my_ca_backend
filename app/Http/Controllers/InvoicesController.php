<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\BookingController;
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
use App\Models\Invoices;
use App\Models\InvoiceItems;
use App\Models\Bookings;
use App\Models\Customers;
use Mpdf\Mpdf;

class InvoicesController extends Controller{

	private static $Users;
	private static $Tasks;
	private static $TokenHelper;
	private static $UserCompanyLink;
	private static $Invoices;
	private static $InvoiceItems;
	private static $Bookings;
	private static $Customers;
	private static $BookingController;
	
	public function __construct(){
		self::$Users = new Users();
		self::$Tasks = new Tasks();
		self::$TokenHelper = new TokenHelper();
		self::$UserCompanyLink = new UserCompanyLink();
		self::$Invoices = new Invoices();
		self::$InvoiceItems = new InvoiceItems();
		self::$Bookings = new Bookings();
		self::$Customers = new Customers();
		self::$BookingController = new BookingController();
	}
	public function updatePayment(Request $request){
		
		self::$Invoices->where('id',$request->id)->update([$request->colum => $request->value]);
		
		$inData = self::$Invoices->where('id',trim($request->id))->first();
		$deduction = $inData->deduction > 0 ? $inData->deduction : 0;
		$tds = $inData->tds_amt > 0 ? $inData->tds_amt : 0;
		$recAmt = $inData->amount - ($deduction+$tds);
		
		self::$Invoices->where('id',$request->id)->update(['rec_amt' => $recAmt]);
		
		return response()->json(['success'=>true, 'message' => 'Invoices updated successfully']);
	}
	public function generatePaymentCSV(Request $request){
		
		$query = self::$Invoices->where('status','!=',3);
		
		if($request->input('is_paid')  && $request->input('is_paid') != ""){
			$query->where('is_paid',$request->input('is_paid')) ;
		}
		if($request->input('bill_no')  && $request->input('bill_no') != ""){
			$query->where('bill_no', 'like', '%'.$request->input('bill_no').'%') ;
		}
		if($request->input('bill_date')  && $request->input('bill_date') != ""){
			$bill_date = date('Y-m-d',strtotime('+1 day', strtotime($request->input('bill_date'))));
			$query->where('bill_date',$bill_date) ;
		}
		if($request->input('rec_date')  && $request->input('rec_date') != ""){
			$bill_date = date('d-m-Y',strtotime('+1 day', strtotime($request->input('rec_date'))));
			$query->where('rec_date',$bill_date) ;
		}
		if($request->input('customer_name')  && $request->input('customer_name') != ""){
			$query->where('customer_name', 'like', '%'.$request->input('customer_name').'%') ;
		}
		if($request->input('customer_gst')  && $request->input('customer_gst') != ""){
			$query->where('customer_gst', 'like', '%'.$request->input('customer_gst').'%') ;
		}
		if($request->input('courier_no')  && $request->input('courier_no') != ""){
			$query->where('courier_no', 'like', '%'.$request->input('courier_no').'%') ;
		}
		if($request->input('amount')  && $request->input('amount') != ""){
			$query->where('amount', 'like', '%'.$request->input('amount').'%') ;
		}
		if($request->input('payment_mode')  && $request->input('payment_mode') != ""){
			$query->where('payment_mode', 'like', '%'.$request->input('payment_mode').'%') ;
		}
		
		$notes = $query->orderBy('id','DESC')->get();
		
		$delimiter = ",";
		$filename = "payments_" . date('d_F_Y') . ".csv";
		
		$destination = "storage/csv/".$filename;
		$f = fopen($destination,"w");
		
		$fields = array(
						'S.NO',
						'STATUS',
						'BILL NO.',
						'BILL DATE',
						'GST CITY/STATE',
						'GST',
						'CO. RECE. NO.',
						'AMOUNT',
						'TDS AMOUNT',
						'PENDING PAYMENT DAYS'
						);
		 
		fputcsv($f, $fields, $delimiter);
		$totalAmount = $tds = 0;
		foreach($notes as $key => $note){
			
			$custData = self::$Customers->where('id',trim($note->customer_id))->first();
			$gstCity = isset($custData->city) ? $custData->city : 'N/A';
			$status = $note->is_paid == 1 ? 'Paid' : 'Unpaid';
			$totalAmount = $totalAmount+$note->amount;
			$tds = $tds+$note->tds_amt;
			
			$noOfDays = 0;
			if($note->bill_date != ''){
				$now = time(); // or your date as well
				$your_date = strtotime($note->bill_date);
				$datediff = $now - $your_date;
				$noOfDays = round($datediff / (60 * 60 * 24));
			}
			
			$lineData = array(
							$key+1,
							$status,
							$note->bill_no,
							date('d-m-Y',strtotime($note->bill_date)),
							$gstCity,
							$note->customer_gst,
							$note->courier_no,
							$note->amount,
							$note->tds_amt,
							$noOfDays
							);
			fputcsv($f, $lineData, $delimiter);
		}
		$lineData3 = array('','','','','','','','','','');						
		fputcsv($f, $lineData3, $delimiter); 
		
		$lineData2 = array('','','','','','','',$totalAmount,$tds,'');						
		fputcsv($f, $lineData2, $delimiter);                     
		
		fclose ($f);

		//move back to beginning of file
		//fseek($f, 0);
		//set headers to download file rather than displayed
		
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header("Cache-Control: max-age=0");		
				

		return response()->json(['success'=>true,'download_url' => env('APP_URL').$destination]);
	}
	public function generatePDF(Request $request){
		$query = self::$Invoices->where('status','!=',3);
		
		if($request->input('is_paid')  && $request->input('is_paid') != ""){
			$query->where('is_paid',$request->input('is_paid')) ;
		}
		if($request->input('bill_no')  && $request->input('bill_no') != ""){
			$query->where('bill_no', 'like', '%'.$request->input('bill_no').'%') ;
		}
		if($request->input('bill_date')  && $request->input('bill_date') != ""){
			$bill_date = date('Y-m-d',strtotime('+1 day', strtotime($request->input('bill_date'))));
			$query->where('bill_date',$bill_date) ;
		}
		if($request->input('rec_date')  && $request->input('rec_date') != ""){
			$bill_date = date('d-m-Y',strtotime('+1 day', strtotime($request->input('rec_date'))));
			$query->where('rec_date',$bill_date) ;
		}
		if($request->input('customer_name')  && $request->input('customer_name') != ""){
			$query->where('customer_name', 'like', '%'.$request->input('customer_name').'%') ;
		}
		if($request->input('customer_gst')  && $request->input('customer_gst') != ""){
			$query->where('customer_gst', 'like', '%'.$request->input('customer_gst').'%') ;
		}
		if($request->input('courier_no')  && $request->input('courier_no') != ""){
			$query->where('courier_no', 'like', '%'.$request->input('courier_no').'%') ;
		}
		if($request->input('amount')  && $request->input('amount') != ""){
			$query->where('amount', 'like', '%'.$request->input('amount').'%') ;
		}
		if($request->input('payment_mode')  && $request->input('payment_mode') != ""){
			$query->where('payment_mode', 'like', '%'.$request->input('payment_mode').'%') ;
		}
		
		$notes = $query->orderBy('id','DESC')->get();
		
		$html = '<table cellspacing="0" width="100%">';
		$html .='<tr><td><img width="100" src="'.self::$BookingController->getLogo().'" ></td><td width="100%" colspan="7" align="center"><img width="350" src="'.self::$BookingController->getHeader().'" ><p style="font-family:tahoma;color:#3D28D9">TRANSPORT CONTRACTORS TRUCK & TRAILOR SUPPLIER</p><p style="font-family:tahoma;color:#3D28D9">B-72/73, 3rd FLOOR, ROHIT HOUSE, VISHWAKARMA COLONY, NEW DELHI-110044</p></td><td valign="top" width=""><P style="font-size:11px;font-family:tahoma">PH. NO. 9810610060</P><P style="font-size:11px;font-family:tahoma">9310610060</P><P style="font-size:11px;font-family:tahoma">9310113121</P></td></tr>';
		$html .= '</table><br>';	
		$html .= '<table cellspacing="0" width="100%">';
		
		$html .= '<tr>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">S.No.</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">Status</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">Bill No.</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">Bill Date</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">GST City/State</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">GST</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;padding:6px;">Co.Rece No.</td>
		<td style="font-family:tahoma;background-color:#E5E5E5;border-left:1px solid #000;border-top:1px solid #000;border-bottom:1px solid #000;border-right:1px solid #000;padding:6px;">Amount</td>
				  </tr>';
		foreach($notes as $key => $note){
			$custData = self::$Customers->where('id',trim($note->customer_id))->first();
			$gstCity = isset($custData->city) ? $custData->city : 'N/A';
			$status = $note->is_paid == 1 ? 'Paid' : 'Unpaid';
			$html .= '<tr>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.($key+1).'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.$status.'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.$note->bill_no.'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.date('d-m-Y',strtotime($note->bill_date)).'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.$gstCity.'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.$note->customer_gst.'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;padding:6px;">'.$note->courier_no.'</td>
				<td style="font-family:tahoma;border-left:1px solid #000;border-bottom:1px solid #000;border-right:1px solid #000;padding:6px;">'.$note->amount.'</td>
						  </tr>';
		}
		$html .= '</table>';
		
		
		$fileName = 'report.pdf';
		$mypdf = new mPDF([
			'margin_left' => 5,
			'margin_right' => 5,
			'margin_top' => 5,
			'margin_bottom' => 5,
			'margin_header' => 1,
			'margin_footer' => 1,
		]);
		$mypdf->SetDisplayMode('fullpage');
		$mypdf->WriteHTML($html);
		$storage_path = storage_path();
		$structure = $storage_path . "/pdf/";
		$file_name = $structure . $fileName;
		$mypdf->Output($file_name);
		$destination = "storage/pdf/".$fileName;
		
		return response()->json(['success'=>true,'file_name'=>$fileName,'download_url' => env('APP_URL').$destination,'message'=>'Invoice generated successfully'],200);
	}
	public function getList(Request $request){
		$query = self::$Invoices->where('status','!=',3);
		
		/*if($request->input('search_key')  && $request->input('search_key') != ""){
            $SearchKeyword = $request->input('search_key');
            $query->where(function($query) use ($SearchKeyword)  {
                if(!empty($SearchKeyword)) {
                    $query->where('customer_name', 'like', '%'.$SearchKeyword.'%') 
                    ->orWhere('customer_gst', 'like', '%'.$SearchKeyword.'%')
					->ref_no('customer_gst', 'like', '%'.$SearchKeyword.'%')
					->amount('customer_gst', 'like', '%'.$SearchKeyword.'%')
                    ->orWhere('bill_no', 'like', '%'.$SearchKeyword.'%');
                }
             });
		}*/
		
		if($request->input('is_paid')  && $request->input('is_paid') != ""){
			$query->where('is_paid',$request->input('is_paid')) ;
		}
		if($request->input('bill_no')  && $request->input('bill_no') != ""){
			$query->where('bill_no', 'like', '%'.$request->input('bill_no').'%') ;
		}
		if($request->input('bill_date')  && $request->input('bill_date') != ""){
			$bill_date = date('Y-m-d',strtotime('+1 day', strtotime($request->input('bill_date'))));
			$query->where('bill_date',$bill_date) ;
		}
		if($request->input('rec_date')  && $request->input('rec_date') != ""){
			$bill_date = date('d-m-Y',strtotime('+1 day', strtotime($request->input('rec_date'))));
			$query->where('rec_date',$bill_date) ;
		}
		if($request->input('customer_name')  && $request->input('customer_name') != ""){
			$query->where('customer_name', 'like', '%'.$request->input('customer_name').'%') ;
		}
		if($request->input('customer_gst')  && $request->input('customer_gst') != ""){
			$query->where('customer_gst', 'like', '%'.$request->input('customer_gst').'%') ;
		}
		if($request->input('courier_no')  && $request->input('courier_no') != ""){
			$query->where('courier_no', 'like', '%'.$request->input('courier_no').'%') ;
		}
		if($request->input('amount')  && $request->input('amount') != ""){
			$query->where('amount', 'like', '%'.$request->input('amount').'%') ;
		}
		if($request->input('payment_mode')  && $request->input('payment_mode') != ""){
			$query->where('payment_mode', 'like', '%'.$request->input('payment_mode').'%') ;
		}
		
		$notes = $query->orderBy('id','DESC')->paginate(10);
		
		foreach($notes as $key => $note){
			$custData = self::$Customers->where('id',trim($note->customer_id))->first();
			$note->gst_city = isset($custData->city) ? $custData->city : 'N/A';
			$note->display_date = date('d-m-Y',strtotime($note->created_at));
			$note->display_date2 = date('d-m-Y',strtotime($note->bill_date));
			
			if($note->is_paid == 1){
				$note->row_style = 'background-color:#0B32F3 !important; color:#fff;';
			}else{
				$note->row_style = '';
			}
		}
		
		$totalAmt = $query->sum('amount');
		$totalDeduction = $query->sum('deduction');
		$totalTds = $query->sum('tds_amt');
		$totalSettlement = $query->sum('settlement');
		$totalRecAmt = $query->sum('rec_amt');
		
		return response()->json(['success'=>true,'users'=>$notes,'totalAmt' => $totalAmt,'totalDeduction' => $totalDeduction,'totalTds' => $totalTds,'totalSettlement' => $totalSettlement,'totalRecAmt' => $totalRecAmt],200);
	}
	public function getInvoiceCustomers(Request $request){
		$customers = self::$Invoices->select('customer_name')->where('status','!=',3)->orderBy('customer_name','ASC')->groupBy('customer_name')->get();
		return response()->json(['success'=>true,'customers' => $customers],200);
	}
	public function AddInvoiceItem(Request $request){
		$validator = Validator::make($request->all(), [
			'id' => 'required|numeric',
			'container_no' => 'required'
			
		],[
			'id.required' => 'Please enter id.',
			'container_no.required' => 'Please enter container no.'
			
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('id')){
				return response()->json(['success'=>false, 'message' => $errors->first('id')]);
			}
			if($errors->first('container_no')){
				return response()->json(['success'=>false, 'message' => $errors->first('container_no')]);
			}
		}else{
			$bookingData = self::$Bookings->where('container_no',trim($request->container_no))->first();
			$itemData = self::$InvoiceItems->where('invoice_id',trim($request->id))->first();
			if(isset($bookingData->id)){
				
				$itemExist = self::$InvoiceItems->where('item_id',trim($bookingData->id))->where('status',1)->count();
				if($itemExist > 0){
					return response()->json(['success'=>false, 'message' => 'Container already exist on other invoice']);
				}
				$setData2['added_by'] = $GLOBALS['USER.ID'];
				$setData2['item_id'] = $bookingData->id;
				$setData2['con_no'] = $bookingData->container_no;
				$setData2['size'] = $bookingData->size;
				$setData2['do_date'] = $bookingData->do_date;
				$setData2['from'] = $bookingData->from;
				$setData2['to'] = $bookingData->to;
				$setData2['ref_no'] = $bookingData->shipment_no;
				$setData2['freight'] = $bookingData->l_freight;
				$setData2['vehicle_no'] = $bookingData->vehicle_no;
				$setData2['invoice_id'] = $request->id;
				$setData2['file_name'] = isset($itemData->id) ? $itemData->file_name : '';
				self::$InvoiceItems->create($setData2);
				
				$invoiceData = self::$Invoices->where('id',trim($request->id))->first();
				self::$Bookings->where('id',$bookingData->id)->update(['l_invo_no' => $invoiceData->bill_no,'l_invo_date' => $invoiceData->bill_date]);
				
				$total = 0;
				$allItems = self::$InvoiceItems->where('invoice_id',trim($request->id))->where('status',1)->get();
				foreach($allItems as $key =>$allItem){
					$total = $total+$allItem->freight;
				}
				self::$Invoices->where('id',trim($request->id))->update(['amount' => $total]);
				
				return response()->json(['success'=>true, 'message' => 'Container added successfully']);
				
			}else{
				return response()->json(['success'=>false, 'message' => 'Container does notexist']);
				
			}
		}
	}
	public function updateStatus(Request $request,$id,$status){
		
		$items = self::$InvoiceItems->where('invoice_id',$id)->get();
		foreach($items as $key => $item){
			$users = self::$InvoiceItems->where('id',$item->id)->update(['status' => 3]);
			self::$Bookings->where('id',$item->item_id)->update(['l_invo_no' => NULL,'l_invo_date' => NULL]);
		}
		$users = self::$Invoices->where('id',$id)->update(['status' => 3]);
		return response()->json(['success'=>true,'message'=>'Record deleted successfully'],200);
	}
	public function generateCsv(Request $request){
		
		$delimiter = ",";
		$filename = "invoices_" . date('d_F_Y') . ".csv";
		
		$destination = "storage/csv/".$filename;
		$f = fopen($destination,"w");
		
		$fields = array(
						'S.NO',
						'BILL No',
						'BILL Date',
						'CUSTOMER NAME',
						'CONT NO.',
						'SIZE',
						'DO DATE',
						'FROM',
						'TO',
						'REF NO.',
						'FREIGHT.',
						);
		 
		fputcsv($f, $fields, $delimiter);
		
		$counter = 1;
		foreach($request->selectedRow as $key => $rowID){
			$invoiceData = self::$Invoices->where('id',$rowID)->first();
			$items = self::$InvoiceItems->where('invoice_id',$invoiceData->id)->where('status',1)->get();
			foreach($items as $key => $item){
				$lineData = array(
								$counter,
								$invoiceData->bill_no,
								$invoiceData->bill_date,
								$invoiceData->customer_name,
								$item->con_no,
								$item->size,
								$item->do_date,
								$item->from,
								$item->to,
								$item->ref_no,
								$item->freight
								);
				fputcsv($f, $lineData, $delimiter);
				$counter++;
			}
		}
		
		$lineData2 = array('','');						
		fputcsv($f, $lineData2, $delimiter);                     
		
		fclose ($f);

		//move back to beginning of file
		//fseek($f, 0);
		//set headers to download file rather than displayed
		
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header("Cache-Control: max-age=0");		
				

		return response()->json(['success'=>true,'download_url' => env('APP_URL').$destination]);
	}
}
