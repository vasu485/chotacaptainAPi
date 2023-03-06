<?php

namespace App\Http\Controllers\AdminDashboard;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 
use App\Models\DeviceToken;
use App\Models\Admin\Admin;
use DB;
use Hash;
use Mail;
use JWTAuth;
use Exception;
use Storage;
use Validator;
use Tymon\JWTAuthExceptions\JWTException;
date_default_timezone_set('Asia/Kolkata');
//ini_set('max_execution_time', 30000);

class DashboardController extends Controller
{
    
    public function __construct()
    {
       
       $this->middleware('jwt.auth', ['except' => []]);

	     \Config::set('jwt.user', 'App\Models\Admin\Admin');
        //\Config()->set('auth.guard', 'admin');
        \Config::set('auth.providers', ['users' => [
                'driver' => 'eloquent',
                'model' => 'App\Models\Admin\Admin',
            ]]);
    }
		/* User counts for dashboard */
    public function getDashboardCounts(Request $request)
    {
    	$activeUsers = User::where([['is_active',1],['user_role','user']])->count();
    	$onlineUsers = User::where([['is_active',1],['user_role','user'],['is_online',1]])->count();
    	$inactiveUsers = User::where([['is_active','!=',1],['user_role','user']])->count();

    	$males = User::where([['gender','male'],['is_active',1],['user_role','user']])->count();
    	$females = User::where([['gender','female'],['is_active',1],['user_role','user']])->count();

    	$data['user'] = array("total"=>$activeUsers+$inactiveUsers,"active"=>$activeUsers,"inactive"=>$inactiveUsers,"online" =>$onlineUsers);
    	$data['gender'] = array("male"=>$males,"female"=>$females);

    	return response()->json(['status' => 'success','message' => 'User Counts','data' => $data]);

    }

}
