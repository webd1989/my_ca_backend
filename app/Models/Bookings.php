<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Bookings extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'added_by',
        'do_date',
		'do_date_str',
		'sline',
		'shipment_no',
		'container_no',
		'is_cont_no_fine',
		'size',
		'from',
		'to',
		'vehicle_no',
		'container_pickup_date',
		'container_offloading_date',
		'bac',
		'sline_handle_by',
		'incentive',
		'transport_name',
		'trpt_frieght',
		'status',
		'billed_to',
		'advance_1',
		'remarks_0',
		
		'advance_2',
		'trpt_tds',
		'munsyana',
		'detention_charge',
		'handling_charge',
		'other_charge',
		'trpt_balence',
		'trpt_bill_no',
		'trpt_bill_date',
		'utr_no',
		'utr_date',
		'transport_handle_by',
		'incentive_2',
		'remarks_1',
		'trpt_total_bill_amt',
		'vehicle_no_2',
		'trpt_pay_date',
		
		'l_freight',
		'l_invo_no',
		'l_invo_date',
		'remarks_2',
		'credit_note_amt',
		'credit_note_no',
		'credit_note_date',
    ];

	public function GetRecordById($id){
		return $this::where('id', $id)->first();
	}
	public function UpdateRecord($Details){
		$Record = $this::where('id', $Details['id'])->update($Details);
		return true;
	}
	public function CreateRecord($Details){
		$Record = $this::create($Details);
		return $Record;
	}
}
