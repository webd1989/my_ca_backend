<?php

namespace App\Http\Middleware;
use App\RouteHelper;
use Illuminate\Routing\Route;
use Illuminate\Http\Request;
use App\Models\TokenHelper;
use App\Models\Responses;
use App\Models\Users;
use Closure;


class RequiredParameters
{
	private static $TokenHelper;
	private static $Users;
	public function __construct(){
		self::$TokenHelper = new TokenHelper();
		self::$Users = new Users();
	}
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->header('Authorization')){
            $Token = $request->header('Authorization');
            $tokenData = trim(str_replace('Bearer','',$Token));
            // This endpoint required a token, lets makre sure its valid
            $ValidToken = self::$TokenHelper->ParseToken($tokenData);
            if(!$ValidToken){
                return response()->json(['success'=>false, 'message'=>Responses::GetResponse('GLOBAL.INVALID_TOKEN')], 401);
            }
            self::$TokenHelper->GetIssuer();
            // This is a fucking disgusting fix.
        }else{
            $checkRoutes = ['api.login', 'api.OtpVerify', 'api.createAccount'];
            if(!$request->header('Authorization')){
                return response()->json(['success'=>false, 'message'=>Responses::GetResponse('GLOBAL.EMPTY_TOKEN')], 401);
            }
        }
        return $next($request);
    }
}
