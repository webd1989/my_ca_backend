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
use Instagram\User\BusinessDiscovery;

class CustomerController extends Controller{

	private static $Users;
	private static $TokenHelper;
	
	public function __construct(){
		self::$Users = new Users();
		self::$TokenHelper = new TokenHelper();
		
	}
	public function test(Request $request){
		$curl = curl_init();

		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://graph.instagram.com/me/media?fields=media_url%2Cpermalink%2Ctimestamp%2Cthumbnail_url&access_token=IGQWRNbWlIWEZAtVi1yOTQ0a3gtSXBRWlhZAS1dSVDFhOUthUURDRG9FbVQ1LUI1cGFlNzdfdkUtVlRMekZAKcTJnb0JiWmVSUnlfcUpEaGhJd01XTTlkdWFIbGE4WVBTVUhvMEUtajRFdmNOaXVWYjFXOGh1NEI3VTQZD',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'GET',
		  CURLOPT_POSTFIELDS => array('access_token' => '123'),
		  CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoyLCJleHAiOjE3MDUyMjYxMDUsImlzcyI6ImNybS5jb20iLCJpYXQiOjE3MDI2MzQxMDV9.pM-_wRxPAzjl-EIWZ2D2MhCpfb4l6-zZgOaJa1WRTS8',
			'Cookie: ig_did=EC398798-C9C1-42B3-8140-29F7ACD76C2D; ig_nrcb=1'
		  ),
		));
		
		$response = curl_exec($curl);
		
		curl_close($curl);
		$instaData = json_decode($response);
		$resultArray = [];
		foreach($instaData->data as $key => $single){
			$imageURL = isset($single->thumbnail_url) ? $single->thumbnail_url : $single->media_url;
			$resultArray[$key]['thumbnail_url'] = $imageURL;
			$resultArray[$key]['permalink'] = $single->permalink;
			$resultArray[$key]['image_url'] = env('APP_URL').'api/get/image/250/250/'.base64_encode(base64_encode($imageURL));
			
		}
		return response()->json(['success'=>true,'posts'=>$resultArray],200);
	}
	public function createImage(Request $request,$max_width,$max_height,$image){

		$quality = 80;
		$dst_dir=NULL;
		$source_file = base64_decode(base64_decode($image));
		
		$imgsize = getimagesize($source_file);
		$width = $imgsize[0];
		$height = $imgsize[1];
		$mime = $imgsize['mime'];
		
		switch($mime){

				case 'image/gif':

					$image_create = "imagecreatefromgif";
					header("Content-Type: image/gif");
					$image = "imagegif";
					break;

				case 'image/png':

					$image_create = "imagecreatefrompng";
					header("Content-Type: image/png");
					$image = "imagepng";
					$quality = 7;
					break;

				case 'image/jpeg':

					$image_create = "imagecreatefromjpeg";
					header("Content-Type: image/jpeg");
					$image = "imagejpeg";
					$quality = 80;
					break;

				default:

					return false;
					break;

			}

			$dst_img = imagecreatetruecolor($max_width, $max_height);
			
			$src_img = $image_create($source_file);

			$width_new = $height * $max_width / $max_height;
			$height_new = $width * $max_height / $max_width;

			//if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
			if($image_create == "imagecreatefrompng"){
				$transparent = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
				imagecolortransparent($dst_img, $transparent);
				imagefill($dst_img, 0, 0, $transparent);
			}
			if($width_new > $width){

				//cut point by height
				$h_point = (($height - $height_new) / 2);

				//copy image
				imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $max_width, $max_height, $width, $height_new);

			}else{

				//cut point by width
				$w_point = (($width - $width_new) / 2);
				imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);

			}
			if($image_create == "imagecreatefrompng"){
				imagesavealpha($dst_img,true);
			}
			$image($dst_img, $dst_dir, $quality);

			if($dst_img)imagedestroy($dst_img);
			if($src_img)imagedestroy($src_img);

			die;
		
		echo $source_file;
	}
	public function uploadProfilePic(Request $request){
		
		$newFileName = '';
		if(isset($_FILES['file']['name'])){
			$fileMimes = array('image/jpg', 'image/jpeg', 'image/png');
			if(in_array($_FILES['file']['type'], $fileMimes)){
				$tempName =$_FILES['file']['tmp_name'];
				$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
				$newFileName = rand(1001,9999).time().'.'.$ext;
				move_uploaded_file($tempName,base_path().'/storage/profile/'.$newFileName);
				
				$userData = self::$Users->where('id',$GLOBALS['USER.ID'])->first();
				if($userData->photo != "" && file_exists(base_path().'/storage/profile/'.$userData->photo)){
					unlink(base_path().'/storage/profile/'.$userData->photo);
				}
				self::$Users->where('id',$GLOBALS['USER.ID'])->update(['photo' => $newFileName]);
				
				$file_path = env('APP_URL').'storage/profile/'.$newFileName;
				
				return response()->json(['success'=>true, 'message' => 'Profile pic uploaded successfully','file_path' => $file_path]);
				
			}else{
				return response()->json(['success'=>false, 'message' => 'Invalid file format']);
			}
		}else{
			return response()->json(['success'=>false, 'message' => 'Please choose document']);
		}
	}
	public function getProfile(Request $request){
		$userData = self::$Users->where('id',$GLOBALS['USER.ID'])->first();
		return response()->json(['success'=>true,'user_date'=>$userData],200);
	}
	public function updateProfile(Request $request){
		$validator = Validator::make($request->all(), [
			'name' => 'required',
			'phone' => 'required',
			'address' => 'required',
			'city' => 'required',
			'state' => 'required',
			'country' => 'required',
			'zipcode' => 'required',
		],[
			'name.required' => 'Please enter your name.',
			'phone.required' => 'Please enter your phone.',
			'address.required' => 'Please enter your address.',
			'city.required' => 'Please enter your city.',
			'state.required' => 'Please enter your state.',
			'country.required' => 'Please enter your country.',
			'zipcode.required' => 'Please enter your zipcode.',
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('name')){
				return response()->json(['success'=>false, 'message' => $errors->first('name')]);
			}
			if($errors->first('phone')){
				return response()->json(['success'=>false, 'message' => $errors->first('phone')]);
			}
			if($errors->first('address')){
				return response()->json(['success'=>false, 'message' => $errors->first('address')]);
			}
			if($errors->first('city')){
				return response()->json(['success'=>false, 'message' => $errors->first('city')]);
			}
			if($errors->first('state')){
				return response()->json(['success'=>false, 'message' => $errors->first('state')]);
			}
			if($errors->first('country')){
				return response()->json(['success'=>false, 'message' => $errors->first('country')]);
			}
			if($errors->first('zipcode')){
				return response()->json(['success'=>false, 'message' => $errors->first('zipcode')]);
			}
		}else{
			self::$Users->where('id',$GLOBALS['USER.ID'])->update(['name' => $request->name,'phone' => $request->phone,'address' => $request->address,'city' => $request->city,'state' => $request->state,'country' => $request->country,'zipcode' => $request->zipcode]);
			return response()->json(['success'=>true, 'message' => 'Profile updated successfully']);
		}
	}
	public function updatePassword(Request $request){
		$validator = Validator::make($request->all(), [
			'current_password' => 'required',
			'new_password' => 'required',
			're_password' => 'required',
		],[
			'current_password.required' => 'Please enter current password.',
			'new_password.required' => 'Please enter new password.',
			're_password.required' => 'Please enter retype password.',
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('current_password')){
				return response()->json(['success'=>false, 'message' => $errors->first('current_password')]);
			}
			if($errors->first('new_password')){
				return response()->json(['success'=>false, 'message' => $errors->first('new_password')]);
			}
			if($errors->first('re_password')){
				return response()->json(['success'=>false, 'message' => $errors->first('re_password')]);
			}
		}else{
			$User = self::$Users->where('id',$GLOBALS['USER.ID'])->first();
			if($request->post('current_password') && !empty($request->post('current_password'))){
                $Password = password_hash($request->post('current_password'),PASSWORD_BCRYPT);
                $PasswordMatch = password_verify($request->post('current_password'), $User->password);
                if(!$PasswordMatch){
                    return response()->json(['success'=>false,'message'=>'Current password is wrong'],200);
                }
            }
			if($request->post('new_password') != $request->post('re_password')){
				return response()->json(['success'=>false,'message'=>'Password do not match'],200);
			}
			self::$Users->where('id',$GLOBALS['USER.ID'])->update(['password' => password_hash($request->post('new_password'),PASSWORD_BCRYPT)]);
			return response()->json(['success'=>true, 'message' => 'Password updated successfully']);
		}
	}
	public function login(Request $request){
		$validator = Validator::make($request->all(), [
			'username' => 'required',
			'password' => 'required',
		],[
			'username.required' => 'Please enter your username.',
			'password.required' => 'Please enter your password.',
		]);
		if($validator->fails()){
			$errors = $validator->errors();
			if($errors->first('username')){
				return response()->json(['success'=>false, 'message' => $errors->first('username')]);
			}
			if($errors->first('password')){
				return response()->json(['success'=>false, 'message' => $errors->first('password')]);
			}
		}else{
			if(!self::$Users->ExistingRecord($request->input('username'))){
                return response()->json(['success'=>false,'message'=>Responses::GetResponse('USER.INVALID_CREDENTIALS')],200);
            }

            $User = self::$Users->getRecordByUsername($request->input('username'));

            if($request->post('password') && !empty($request->post('password'))){
                $Password = password_hash($request->post('password'),PASSWORD_BCRYPT);
                $PasswordMatch = password_verify($request->post('password'), $User->password);
                if(!$PasswordMatch){
                    return response()->json(['success'=>false,'message'=>Responses::GetResponse('USER.INVALID_CREDENTIALS')],200);
                }
            }
            if($User->status != 1){
                return response()->json(['success'=>false,'message'=>Responses::GetResponse('USER.ACCOUNT_INACTIVE')],200);
            }
			if($request->post('password') && !empty($request->post('password'))){
				
				self::$Users->where('id',$User->id)->update(['last_login_date' => date('Y-m-d')]);
				
                $userId = $User->id;
                $secret = env('JWT_KEY');
                $expiration = time() + 2592000;
                $issuer = 'crm.com';
                $token = Token::create($userId, $secret, $expiration, $issuer);
                $User = self::$Users->GetRecordById($userId)->toArray();

                return response()->json(['success'=>true,'token'=>$token,'details'=>$User],200);
            }else{
				return response()->json(['success'=>false,'message'=>'Internal error'],200);
			}
		}
	}
}
