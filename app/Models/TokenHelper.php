<?php

namespace App\Models;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use ReallySimpleJWT\Token;

class TokenHelper{
	private $AuthToken = 'Token';
	public function ParseToken($Token){
		try{
			// Check if token is valid
			$Valid = Token::validate($Token, env('JWT_KEY'));
			if(!$Valid){
				return false;
			}
			$this->AuthToken = Token::getPayload($Token, env('JWT_KEY'));
			self::GetUserId();
			return true;
		}catch (\Exception $E){
			return false;
		}
	}
	public function GetUserId(){
		$this->SetUIDGlobal($this->AuthToken['user_id']);
		return $this->AuthToken['user_id'];
	}
	public function GetIssuer(){
		$GLOBALS['USER.TOKEN.ISS'] = $this->AuthToken['iss'];
		return $this->AuthToken['iss'];
	}
	public function SetUIDGlobal($Uid){
		$GLOBALS['REQ.UID'] = $Uid;
		$GLOBALS['USER.ID'] = $Uid;
	}
}
