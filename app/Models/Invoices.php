<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Invoices extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'added_by',
        'customer_id',
        'customer_name',
		'customer_address',
        'customer_gst',
		'bill_date',
		'bill_no',
		'ref_no',
		'amount',
		'status',
		'file_name',
		'option_1',
		'option_2',
		'option_3',
		'value_1',
		'value_2',
		'value_3',
		'is_paid',
		'remark_1',
		'courier_no',
		'deduction',
		'tds_amt',
		'rec_amt',
		'payment_mode',
		'rec_date',
		'remark_2',
		'settlement'
		
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
