<?php

namespace App\Models;
class Responses
{
	public static $Responses=[
		// Global
		'GLOBAL.NO_TOKEN'=>'No token was provided in the request',
		'GLOBAL.INVALID_TOKEN'=>'Your session has expired. You need to log in again.',
		'GLOBAL.MISSING_PARAMETER'=>'The request is missing a required parameter',
		'GLOBAL.VALID_TOKEN'=>'Provided token is valid',
        'GLOBAL.EMPTY_TOKEN'=>'Invalid login token',
		'GLOBAL.UNKNOWN'=>'Something went wrong...',
		// User Specific
		'USER.EMAIL_USED'=>'This email address is already exist',
		'USER.EMAIL_UNUSED'=>'This email address is not linked to any accounts',
		'USER.INVALID_CREDENTIALS'=>'The email and password combination provided are incorrect',
		'USER.INVALID_EMAIL'=>'The email provided is incorrect',
		'USER.UPDATE_DETAILS'=>'Your account information has been updated',
        'USER.PASSWORD_INCORRECT'=>'Your old password is not correct',
		'USER.PASSWORD_RESET'=>'If the email you provided is linked to an account you will receive an email with instructions on how to recover your account',
		'USER.PASSWORD_CHANGED'=>'Your password has been updated successfully',
		'USER.LOGOUT'=>'You have logged out successfully.',
		'USER.LINK_FAIL'=>'You are already linked to a company, plese remove this link to join a different one.',
		'USER.LINKED'=>'You have been linked to this company and will receive their promotions and offers',
		'USER.NOT_LINKED'=>'You are not linked to a company',
		'USER.UNLINKED'=>'You have been unlinked from this company',
		'USER.FORGOT_PASSWORD_EMAIL_NOT_SEND'=>'Forgot password email not send',
		'USER.PASSWORD_CHANGED'=>'Your password has been changed successfully',
        'USER.INVALID_OTP'=>'The OTP provided is incorrect',
        'USER.ACCOUNT_INACTIVE'=>'Your account is inactive.',
        'USER.PHONE_USED'=>'This phone number is already exist',
	];

	public static function GetResponse($Index){
		// If the globals array contains the user ID, change the language
		if(isset($GLOBALS['REQ.UID'])){
			$User = new User();
			$Language = 'eng';
			$LanguageFile = self::LoadResponseFile($Language);
			if($LanguageFile != false){
				if(isset($LanguageFile[$Index])){
					return $LanguageFile[$Index];
				}
			}
		}
		return self::$Responses[$Index];
	}

	public static function LoadResponseFile($Language){
		if(is_file(getenv('RESPONSE_LANG_FILES').$Language.'.json')){
			return json_decode(file_get_contents(getenv('RESPONSE_LANG_FILES').$Language.'.json'), true);
		}
		else{
			return false;
		}
	}
}
