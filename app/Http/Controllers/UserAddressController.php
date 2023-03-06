<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserAddress;
use DB;
use JWTAuth;
use Exception;
use Storage;
use Validator;
use Tymon\JWTAuthExceptions\JWTException;
date_default_timezone_set('Asia/Kolkata');
ini_set('max_execution_time', 300);

class UserAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }

    public function addAddress(Request $request)
    {
		 //start validations
        $validator = Validator::make($request->all(), ['h_no' => 'required|string','street' => 'required|string','locality'=>'required|string','address_type'=>'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        /*if($user->user_role!='ADMIN'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }*/

        $add = new UserAddress();
        $add->user_id      = $user->id;
        $add->h_no      = trim($request->h_no);
        $add->street      = trim($request->street);
        $add->locality = trim($request->locality);
        $add->address_type = trim($request->address_type);
        if($request->address_type=='OTHERS')
        {
            $add->address_name = trim($request->address_name);
        }
        if(isset($request->lat) && $request->lat!=null)
        {
            $lat = trim($request->lat);
            $add->lng = $lat;
        }
        if(isset($request->lng) && $request->lng!=null)
        {
            $lng = trim($request->lng);
            $add->lng = $lng;
        }
        $add->landmark = isset($request->landmark)?$request->landmark:null;
        $add->contact_no = isset($request->contact_no)?$request->contact_no:null;
        $add->createdOn = date('Y-m-d H:i:s');
        $add->save();

        return $this->sendResponse("Address saved successfully...!",null);
    }

    public function updateAddress(Request $request)
    {
    	//start validations
        $validator = Validator::make($request->all(), ['id' =>'required|numeric','h_no' => 'required|string','street' => 'required|string','locality'=>'required|string','address_type'=>'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $cat = UserAddress::find($request->id);
        if($cat==null){
            return $this->sendBadException('UserAddress not found',null);
        }

        //logged user
        /*$user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update.",'data' => null],401);
        }*/

        $cat->h_no      = trim($request->h_no);
        $cat->street      = trim($request->street);
        $cat->locality = trim($request->locality);
        $cat->address_type = trim($request->address_type);
        if($request->address_type=='OTHERS')
        {
            $cat->address_name = trim($request->address_name);
        }
        if(isset($request->lat) && $request->lat!=null)
        {
            $lat = trim($request->lat);
            $cat->lng = $lat;
        }
        if(isset($request->lng) && $request->lng!=null)
        {
            $lng = trim($request->lng);
            $cat->lng = $lng;
        }
        $cat->landmark = isset($request->landmark)?$request->landmark:null;
        $cat->contact_no = isset($request->contact_no)?$request->contact_no:null;
        $cat->update();

        return $this->sendResponse("UserAddress updated successfully...!",null);
    }

    public function deleteAddress($id)
    {

        $cat = UserAddress::find($id);
        if($cat==null){
            return $this->sendBadException('UserAddress not found',null);
        }
        $cat->delete();
        return $this->sendResponse("UserAddress Deleted successfully...!",null);
    }

    public function singleAddress($id)
    {
        $cat = UserAddress::find($id);
        if($cat==null){
            return $this->sendBadException('UserAddress not found',null);
        }
        return $this->sendResponse("Single UserAddress",$cat);
    }


    public function getAddress()
    {
        $user = JWTAuth::parseToken()->authenticate();
		$cat = UserAddress::where('user_id',$user->id)->get();
        return $this->sendResponse("UserAddress list",$cat);
    }


}
