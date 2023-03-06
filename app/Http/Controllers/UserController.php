<?php

namespace App\Http\Controllers;
use App\Models\BlockedUser;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\DeviceToken;
use App\Models\UserMedia;
use App\Models\Vendor;
use App\Models\Post;
use App\Models\PostType;
use App\Models\PostMedia;
use App\Models\Friends;
use App\Models\UserMute;
use App\Models\MobileOTP;
use App\Models\Category;
use App\Models\SubCategory;
use DB;
use Hash;
use Mail;
use JWTAuth;
use Exception;
use Storage;
use Validator;
use Tymon\JWTAuthExceptions\JWTException;
/*use App\Http\Controllers\PostController;*/
date_default_timezone_set('Asia/Kolkata');
ini_set('max_execution_time', 30000);
class UserController extends Controller
{
    public function __construct()
    {
     $this->middleware('jwt.auth', ['except' => ['signup','login','mobileVerify','mobileVerifyEncryptOTP','forgotPwd','checkResetToken','resetPwd','changeUserRole','checkUserExistedOrNot']]);
    }

    /*
    * checking user jwt token expired or not
    */
    public function tokenValidOrNot()
    {
        try {
               $user = JWTAuth::parseToken()->authenticate();
               $response = array("isExpired"=>false);
        }
        catch (\Exception $e) {
            //return $e->getMessage();
            $response = array("isExpired"=>true);
        }
       return $this->sendResponse("token validate!",$response);
    }

   /*
    * Mobile number verification
    */
    public function getPartners(){
        $meta = User::whereNotNull('locationid')->get();
        return $this->sendResponse("partners data",$meta);
    }
    public function mobileVerify(Request $request)
    {
		$validator = Validator::make($request->all(), ['mobile' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $validator1 = Validator::make($request->all(), ['mobile' => 'min:10|max:12']);
        if ($validator1->fails()) {
            return $this->sendBadException(implode(',',$validator1->errors()->all()),null);
        }

        $otp = mt_rand(1000,9999);

        if($request->mobile=='7989926546')
        {   
            return $this->sendResponse("OTP Sent Successfully...!",array("otp"=>'9898'));
        }else{
                

                //email otp
                $findUsr = USER::where('mobile',$request->mobile)->first();
                if($findUsr!=null)
                {   
                    if($findUsr->email!=null)
                    {
                        //$this->sendOTPEmail($otp,$findUsr->email);
                    }
                    
                }
                //ends

                //sms otp
                $smsStatus = $this->sendSMS($request->mobile,"mobile_verify",$otp);

                return $this->sendResponse("OTP Sent Successfully...!",array("otp"=>$otp));

                /*if($smsStatus['status'] == 'ok')
                {   
                    return $this->sendResponse("OTP Sent Successfully...!",array("otp"=>$smsStatus['otp']));
                }else{
                    return $this->sendException("Something went wrong, Please try again later",null);
                }*/
        }

    }

    public function mobileVerifyEncryptOTP(Request $request)
    {
        $validator = Validator::make($request->all(), ['mobile' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $validator1 = Validator::make($request->all(), ['mobile' => 'min:10|max:12']);
        if ($validator1->fails()) {
            return $this->sendBadException(implode(',',$validator1->errors()->all()),null);
        }

        
        $otp = 1234;
        
        //email otp
        $findUsr = USER::where('mobile',$request->mobile)->first();
        if($findUsr!=null)
        {   
            if($findUsr->email!=null)
            {
                //$this->sendOTPEmail($otp,$findUsr->email);
            }
            
        }
        //ends

        //sms otp
        $smsStatus = $this->sendSMS($request->mobile,"mobile_verify",$otp);

        return $this->sendResponse("OTP Sent Successfully...!",array("otp"=>base64_encode($otp)));

        /*if($smsStatus['status'] == 'ok')
        {   
            $otp = base64_encode($smsStatus['otp']);
            return $this->sendResponse("OTP Sent Successfully...!",array("otp"=>$otp));
        }else{
            return $this->sendException("Something went wrong, Please try again later",null);
        }*/
    }

   /*
    * Signup api
    */
    public function signup(Request $request)
    {
            //validation start
            $validator = Validator::make($request->all(), ['first_name' => 'required|min:3|max:50','last_name' => 'required|min:3|max:50', 'user_role' => 'required','mobile' => 'required|numeric']);
            if ($validator->fails()) {
                return $this->sendBadException(implode(',',$validator->errors()->all()),null);
            }

            $validator1 = Validator::make($request->all(), ['mobile' => 'min:10|max:12|unique:users']);
            if ($validator1->fails()) 
            {
                    $findUser = User::where('mobile',$request->mobile)->first();
                    if($findUser->user_role=='USER' && $request->user_role=='DELIVERY_BOY' && $request->user_role=='EDITOR' && $request->user_role=='OPERATOR')
                    {
                        $del_boy = User::find($findUser->id);
                        if(isset($request->email))
                        {
                            $del_boy->email     = trim($request->email);
                        }

                        if(isset($request->first_name))
                        {
                            $del_boy->first_name     = trim($request->first_name);
                        }

                        if(isset($request->last_name))
                        {
                            $del_boy->last_name     = trim($request->last_name);
                        }

                        if(isset($request->gender))
                        {
                            $del_boy->gender     = trim($request->gender);
                        }
                        if(isset($request->is_active))
                        {
                            $del_boy->is_active     = trim($request->is_active);
                        }
                        if(isset($request->aadhaar))
                        {
                            $del_boy->aadhaar     = trim($request->aadhaar);
                        }
                        if(isset($request->driving_license))
                        {
                            $del_boy->driving_license     = trim($request->driving_license);
                        }
                        if(isset($request->delivery_boy_address))
                        {
                            $del_boy->delivery_boy_address     = trim($request->delivery_boy_address);
                        }
                        if(isset($request->emergency_phone_number))
                        {
                            $del_boy->emergency_phone_number     = trim($request->emergency_phone_number);
                        }
                        $del_boy->updatedOn = date('Y-m-d H:i:s');
                        if(strtolower($request->user_role)=='delivery_boy')
                        {
                           $del_boy->createdByAdmin = true; 
                        }

                        if(isset($request->prime_customer))
                        {
                            $del_boy->prime_customer     = trim($request->prime_customer);
                        }

                        $del_boy->privilege = null;
                        $del_boy->user_role  = trim($request->user_role);
                        
                        $del_boy->update();
                        $userId = $findUser->id;
                    }else{
                        return $this->sendBadException(implode(',',$validator1->errors()->all()),array("id"=>$findUser->id,"existed_user_role"=>$findUser->user_role));
                    }
            }
            else{
                    if(strtolower($request->user_role)!='user' && strtolower($request->user_role)!='admin' && strtolower($request->user_role)!='delivery_boy' && strtolower($request->user_role)!='vendor' && strtolower($request->user_role)!='editor' && strtolower($request->user_role)!='operator'){
                        return $this->sendBadException('User_role is not valid',null);
                    }

                    if(strtolower($request->user_role)=='admin' && $request->privilege==null)
                    {
                       return $this->sendBadException('Admin privileges required',null); 
                    }

                    //Minimum eight characters, at least one letter, one number and one special character:
                    /*if (!preg_match("^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$^",$request->password)) {
                      return $this->sendBadException('Your password should contain atleast one letter, one number and one special character',null);
                    }*/
                    //validations end

                    //inserting new user data
                    $user = new User();
                    $user->locationid = $request->locationid;
                    $user->first_name      = trim($request->first_name);
                    $user->last_name      = trim($request->last_name);
                    if(isset($request->email)){
                        $user->email     = trim($request->email);
                    }
                    if(isset($request->password)){
                        $user->password     = bcrypt($request->password);
                        $user->pwdString  = $request->password;
                    }else{
                        $randomS = str_random(8);
                        $user->password  = bcrypt($randomS);
                        $user->pwdString  = $randomS;
                    }
                    $user->user_role  = trim($request->user_role);
                    $user->mobile  = trim($request->mobile);
                    $user->is_active = 1;
                    $user->createdOn = date('Y-m-d H:i:s');
                    if(strtolower($request->user_role)=='delivery_boy')
                    {
                       $user->createdByAdmin = true; 
                    }

                    if(strtolower($request->user_role)=='admin')
                    {
                       $user->privilege = trim($request->privilege); // read or write values
                    }else{
                       $user->privilege = null;
                    }

                    if(isset($request->prime_customer))
                    {
                        $user->prime_customer = trim($request->prime_customer);
                    }

                    $user->save();
                    $userId = $user->id;
                }

            $response = User::find($userId);
            if($response!='' && $response!=null)
            {
                //welcome email
                    //$data = array('name' => ucfirst($request->first_name).' '.ucfirst($request->last_name),'email'=>$request->email);
                   // $this->emailSending($data,$request->email,"registration");
                //ends
                return $this->generateJWT_Token('signup',$request->mobile);
            }else{
                return $this->sendException("Something went wrong, Please try again later",null);
            }


    }

    public function changeUserRole(Request $request)
    {

        $validator1 = Validator::make($request->all(), ['id' => 'required|numeric','user_role' => 'required','is_active' => 'required']);
        if ($validator1->fails()) {
            return $this->sendBadException(implode(',',$validator1->errors()->all()),null);
        }

        $del_boy = User::find($request->id);
        if($del_boy==null){
            return $this->sendBadException('User not found',null);
        }
        $del_boy->is_active = $request->is_active;
        $del_boy->user_role = $request->user_role; //"DELIVERY_BOY";
        $del_boy->createdByAdmin = true;
        $del_boy->updatedOn = date('Y-m-d H:i:s');
        $del_boy->update();

        return $this->sendResponse("User Role Updated...!",null);
    }

    public function checkUserExistedOrNot(Request $request)
    {

        $validator1 = Validator::make($request->all(), ['mobile' => 'required|numeric']);
        if ($validator1->fails()) {
            return $this->sendResponse(implode(',',$validator1->errors()->all()),null);
        }

        $usr = User::where('mobile',$request->mobile)->first();
        if($usr==null){
            return $this->sendResponse('User not foundddd',null);
        }

        return $this->sendResponse("checkUserExistedOrNot...!",array("id"=>$usr->id,"existed_user_role"=>$usr->user_role,'is_active'=>$usr->is_active));
    }

   /*
    * Login api
    */
    public function login(Request $request)
    {

       if(isset($request->mobile) && $request->mobile!=null){
         $validator1 = Validator::make($request->all(), ['mobile' => 'numeric']);
         if ($validator1->fails()) {
                return $this->sendBadException(implode(',',$validator1->errors()->all()),null);
            }
        }
        return $this->generateJWT_Token('login',$request->mobile);
    }

    //generate jwt token common function for login and signup response
    private function generateJWT_Token($from,$mobile)
    {
        try { 
                $user = User::where('mobile',$mobile)->first();
                if($user==null || $user==''){
                    return response()->json(["status"=>"error","message"=>"Sorry User does not exist, please register.",'data' => null],200);
                }
                if ($user->is_active==2) { //attempt
                    return response()->json(['status' => 'error','message' => 'Sorry, Your account is Disabled, Please contact Administrator','data' => null], 401);
                }
                // verify the credentials and create a token for the user
                if (! $token = JWTAuth::fromUser($user)) { //attempt
                    return response()->json(['status' => 'error','message' => 'invalid_credentials','data' => null], 401);
                }
        } catch (JWTException $e) {
              // something went wrong
            return response()->json(['status' => 'error','message' => 'could_not_create_token','data' => null], 500);
        }

        $user->image = ($user->image!=null)?request()->getSchemeAndHttpHost().'/images/users/'.$user->image:null;
        if($user->user_role=='VENDOR')
        {
            $user->vendor_data = Vendor::where('mobile',$user->mobile)->first();
        }else{
            $user->vendor_data = null;
        }
        
        $msg = ($from=='login')?'User loggedIn Successfully..!':'Your Registration Done Successfully...!';

        return response()->json(['status' => 'success','message' => $msg,'data' => $user])
                         ->header('token', $token)->header('token_expires', auth('api')
                         ->factory()
                         ->getTTL());
    }

   /*
    * Change password
    */
 	public function changePassword(Request $request)
  	{
		$validator = Validator::make($request->all(), ['old_pwd' => 'required', 'new_pwd' => 'required|string|min:8|max:128']);
	    if($validator->fails()) {
	      	return $this->sendBadException(implode(',',$validator->errors()->all()),null);
	    }
	    if($request->old_pwd == $request->new_pwd){
	        return $this->sendBadException("Sorry, oldPassword and newPassword are same...",null);
	    }

         //Minimum eight characters, at least one letter, one number and one special character:
        if(!preg_match("^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$^",$request->new_pwd)) {
          return $this->sendBadException('Your password should contain atleast one letter, one number and one special character',null);
        }

        /*--get auth user with jwt--*/
        $user = JWTAuth::parseToken()->authenticate();

        $checkUser = User::find($user->id);
        if (Hash::check($request->old_pwd,$checkUser->password)) {
            $checkUser->password = bcrypt($request->new_pwd);
            $checkUser->save();
            return $this->sendResponse("Your Password Changed Successfully...",null);
        }else{
            return $this->sendBadException("Sorry, oldPassword is Incorrect...",null);
        }
  	}

   /*
    * Logout api
    */
	public function logout()
	{
	      $token = JWTAuth::getToken();
          if ($token) {
                JWTAuth::setToken($token)->invalidate();
          }
          return $this->sendResponse("User logout successfully",null);
	}

   /*
    * Forgot password
    */
	public function forgotPwd(Request $request)
    {
    	$validator = Validator::make($request->all(), ['emailOrMobile' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $type = "mobile";
        $validator1 = Validator::make($request->all(), ['emailOrMobile' => 'numeric']);
        if ($validator1->fails()) { //is failed, i.e email
            $validator2 = Validator::make($request->all(), ['emailOrMobile' => 'email']);
	        if ($validator2->fails()) {
	            return $this->sendBadException("Field must be email or mobile",null);
	        }
	        $type="email";
        } //mobile

        if($type == "mobile")
        {   
        	$user = User::where([['mobile',$request->emailOrMobile],['is_active',1]])->first();
		    if($user == null || $user == ""){
		       return $this->sendBadException("User Not Found",null);
		    }

            //checking otp with same day
            $checkOtpSent = MobileOTP::where([['mobileno',$request->emailOrMobile],['createdOn','like','%'.date('Y-m-d').'%'],['type','FORGOTPWD']])->count();
            if($checkOtpSent>=5)
            {
                return $this->sendBadException("OTP Already sent to this mobile number..!",null);
            }
            //ends
            $otp = mt_rand(1000,9999); 
        	$smsStatus = $this->sendSMS($request->emailOrMobile,"forgot_pwd",$otp);
	        if($smsStatus['status'] == 'ok')
	        {
	        	$checkUsr = User::find($user->id);
			    $checkUsr->reset_token = $smsStatus['otp'];
			    $checkUsr->update();
	        	return $this->sendResponse("Forgot password OTP sent successfully...!",array("otp"=>$smsStatus['otp']));
	        }else{
	        	return $this->sendException("Something went wrong, Please try again later",null);
	        }
        }

        if($type="email")
        {
        	$user = User::where([['email',$request->emailOrMobile],['is_active',1]])->first();
		    if($user == null || $user == ""){
		       return $this->sendBadException("User Not Found",null);
		    }
		    $checkUsr = User::find($user->id);
		    $str = $this->randomString();
		    $checkUsr->reset_token = $str;
		    $checkUsr->update();
	        //forgot pwd email with link construction
		        $data = array('name' => ucfirst($user->first_name).' '.ucfirst($user->last_name),'link' => "http://odcet.com/#/resetpwd/".$str);
		        return $this->emailSending($data,$user->email,"forgot_pwd");
	        //ends
        }
    }

   /*
    * Reset password
    */
    public function resetPwd(Request $request)
    {
    	$validator = Validator::make($request->all(), ['reset_token' => 'required','new_pwd' => 'required|string|min:8|max:128']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //Minimum eight characters, at least one letter, one number and one special character:
        if (!preg_match("^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]{8,}$^",$request->new_pwd)) {
          return $this->sendBadException('Your password should contain atleast one letter, one number and one special character',null);
        }

	    $user = User::where('reset_token',$request->reset_token)->first();
	    if($user == null || $user==""){
	       return $this->sendBadException("Reset_token is expired",null);
	    }
	    $checkUsr = User::find($user->id);
	    $checkUsr->reset_token = null;
	    $checkUsr->password = bcrypt($request->new_pwd);
	    $checkUsr->update();
	    return $this->sendResponse("Your Password Reset Successfully",null);
    }

   /*
    * Check reset password token here
    */
    public function checkResetToken(Request $request)
    {
    	$validator = Validator::make($request->all(), ['reset_token' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

     	$user = User::where('reset_token',$request->reset_token)->first();
     	if($user == null || $user==""){
       		return $this->sendResponse("Check reset token",array("isValid"=>false));
      	}
        return $this->sendResponse("Check reset token",array("isValid"=>true));
    }

   /*
    * Save user lat-lngs and live location
    */
    public function saveUserLiveLocation(Request $request)
    {
        $validator = Validator::make($request->all(), ['lat' => ['required','regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'], 'lng' => ['required','regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],'location'=>'required|string|min:5']);
        if($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        // the token is valid and we have found the user via the sub claim
        $checkUser = User::find($user->id);
        if($checkUser == null){
            return $this->sendBadException('User Not Found',null);
        }
        $checkUser->lat = $request->lat;
        $checkUser->lng = $request->lng;
        $checkUser->live_location = $request->location;
        $checkUser->updatedOn = date('Y-m-d H:i:s');
        $checkUser->update();
        return $this->sendResponse("User location updated successfully",null);
    }


    /*
     * add user device token
     */
    public function addDeviceToken(Request $request)
    {
        $validator = Validator::make($request->all(), ['deviceToken' => 'required','deviceId' => 'required','mobile' => 'required']);
        if($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $checkTokens = DeviceToken::where('mobile',$request->mobile)->first();
        if($checkTokens==null)
        {
            $token = new DeviceToken();
            $token->mobile = $request->mobile;
            $token->deviceId = $request->deviceId;
            $token->deviceToken = $request->deviceToken;
            $token->user_id = $user->id;
            $token->updatedOn = date('Y-m-d H:i:s');
            $token->save();
        }else{
            $checkTokens->deviceId = $request->deviceId;
            $checkTokens->deviceToken = $request->deviceToken;
            $checkTokens->user_id = $user->id;
            $checkTokens->updatedOn = date('Y-m-d H:i:s');
            $checkTokens->update();
        }

        if($user->user_role=='VENDOR')
        {
            $vendor_data = Vendor::where('mobile',$user->mobile)->first();
            $vendor_status = $vendor_data->is_active;
        }else{
            $vendor_status = $user->is_active;
        }

        return $this->sendResponse("Device token updated successfully",array("is_active"=>$vendor_status));
    }

    /**
     * user dp pic update
     * @return upload pic response
     */
    public function userImageUpdate(Request $request)
    {   
        //file extension validations based on image
        $validator = Validator::make($request->all(),
                            ['image' => 'required|mimes:jpeg,png,jpg|max:10000','image.required' => 'Please upload an image',
                             'image.mimes' => 'Only jpeg,png,jpg images are allowed',
                             'image.max' => 'Sorry! Maximum allowed size for an image is 10MB']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //auth user
        $user = JWTAuth::parseToken()->authenticate();

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $destination_file_name = time().".".$extension;

        if($extension==null || $extension=="" || $extension==" ")
        {
            return $this->sendBadException('Image properties are not valid',null);
        }

        $fileName = sha1(date('YmdHis') . str_random(30)).$file->getClientOriginalName();
        $destinationPath = public_path().'/images/users/' ;
        $file->move($destinationPath,$fileName);

        $usrInf = User::find($user->id);
        $usrInf->image = $fileName;
        $usrInf->update();  

        return $this->sendResponse("Image updated..!",array("url"=>request()->getSchemeAndHttpHost().'/images/users/'.$fileName));  
    }

       /*
    * Get Auth user by token
    */
    public function getAuthenticatedUser($id=null)
    {

        if($id==null){ //logged user profile
            $user = JWTAuth::parseToken()->authenticate();
            $authUserId = $user->id;
        }else{ //another user view profile
            $user = User::find($id); //profileId
            $authUser = JWTAuth::parseToken()->authenticate(); //loggeduserid
            $authUserId = $authUser->id;
        }

        if($user == null)
        {
            return $this->sendBadException('User not found',null);
        }
        $user->image = ($user->image!=null)?request()->getSchemeAndHttpHost().'/images/users/'.$user->image:null;
        return $this->sendResponse("User details",$user);
    }

    /*
    * Update Auth user profile
    */
    public function authenticatedUserProfileUpdate(Request $request)
    {
       
        $validator = Validator::make($request->all(), ['first_name' => 'required','gender' => 'required']);
            //,'last_name' => 'required'
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate(); //auth user

        if(isset($request->id) && $user->user_role=='ADMIN')
        {
            $findUsr = User::find($request->id);
            if(isset($request->user_role)){
                $findUsr->user_role = $request->user_role;
            }
        }else{
            $findUsr = User::find($user->id);
        }

        if(isset($request->first_name)){
            $findUsr->first_name = $request->first_name;
        }

        if(isset($request->last_name)){
            $findUsr->last_name = $request->last_name;
        }
        if(isset($request->email))
        {
            $email = $request->email;
            if($email==null || $email==''){
                return $this->sendBadException('The email is required.',null);
            }
            $eString = explode("@", $email);
            $userPart = $eString[0]; //default length max is 64
            $domainString = explode(".", $eString[1],2);
            $domainName = $domainString[0]; //default length max is 62
            $domainPart = $domainString[1]; //default length is 63
            if(strlen($userPart)>64){
                return $this->sendBadException('The email must be a valid email address.',null);
            }
            if(strlen($domainName)>62){
                return $this->sendBadException('The email must be a valid email address.',null);
            }
            //email extensions
            /*$extensions = array('com', 'co', 'in', 'org','net','info','us','co.in','online');
            if (!in_array($domainPart, $extensions)){
              return $this->sendBadException('Invalid extension, it must be .com,.co,.in,.org,.net,.info,.us,.co.in,.online',null);
            }*/
            
            //check unique email while updating profile
            /*$checkEmail = User::where([['email',$email],['id','!=',$user->id]])->count();
            if($checkEmail>0){
                return $this->sendBadException('The email is already existed.',null);
            }*/
            $findUsr->email = $email;
        }

        if(isset($request->address)){
            $findUsr->address = $request->address;
        }
        if(isset($request->gender)){
            $findUsr->gender = $request->gender;
        }

        if(isset($request->is_active)){
            $findUsr->is_active = $request->is_active;
        }

        if(isset($request->prime_customer))
        {
            $findUsr->prime_customer = trim($request->prime_customer);
        }

        $findUsr->update();
        return $this->sendResponse("Profile updated successfully",null);
    }

    /*
    * update user newsfeed preferences
    */
    public function userNewsfeedPreferencesUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), ['newsFeedPreference' => 'required|numeric|']);
        if($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $status = array(0,2,3,4); //Here 0 = all, 2 = image, 3 = audio , 4 = video
        if(!in_array($request->newsFeedPreference, $status)){
            return $this->sendBadException('newsFeedPreference not valid',null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        // the token is valid and we have found the user via the sub claim
        $checkUser = User::find($user->id);
        if($checkUser == null){
            return $this->sendBadException('User Not Found',null);
        }
        $checkUser->newsFeedPreference = $request->newsFeedPreference;
        $checkUser->update();
        return $this->sendResponse("User newsFeedPreferences updated successfully",null);
    }

    /*
    * get user getUserPrivacy
    */
    public function getUserPrivacy()
    {

        $user = JWTAuth::parseToken()->authenticate();
        // the token is valid and we have found the user via the sub claim
        $checkUser = User::where('id',$user->id)
                    ->select('id','allow_comments','tag_me','allow_friends','allow_photos','allow_videos','allow_audios','newsFeedPreference','is_online','allow_products','allow_offers','tabs')
                    ->first();

        if($checkUser == null){
            return $this->sendBadException('User Not Found',null);
        }
        $checkUser->tag_me = ($checkUser->tag_me==1)?true:false;
        $checkUser->tabs = unserialize($checkUser->tabs);

        return $this->sendResponse("User privacy settings",$checkUser);
    }

    /*
    * get user getUserPrivacy
    */
    public function updateUserPrivacy(Request $request)
    {
        $validator = Validator::make($request->all(), ['tag_me' => 'required|boolean','allow_friends' => 'required|numeric','allow_photos' => 'required|numeric','allow_comments' => 'required|numeric','allow_videos' => 'required|numeric','allow_audios' => 'required|numeric']);
        if($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        // the token is valid and we have found the user via the sub claim
        $checkUser = User::find($user->id);
        if($checkUser == null){
            return $this->sendBadException('User Not Found',null);
        }

        $checkUser->allow_friends = $request->allow_friends;
        $checkUser->tag_me = $request->tag_me;
        $checkUser->allow_photos = $request->allow_photos;
        $checkUser->allow_comments = $request->allow_comments;
        $checkUser->allow_videos = $request->allow_videos;
        $checkUser->allow_audios = $request->allow_audios;

        if(isset($request->allow_products)){
            $checkUser->allow_products = $request->allow_products;
        }
        if(isset($request->allow_offers)){
            $checkUser->allow_offers = $request->allow_offers;
        }

        if(isset($request->tabs)){
            $checkUser->tabs = serialize($request->tabs);
        }else{
             $checkUser->tabs = null;
        }

        $checkUser->update();

        return $this->sendResponse("Privacy settings updated...!",null);
    }

    /*
    * change email / mobile fields api
    */
    public function updateEmailMobile(Request $request)
    {

        $validator = Validator::make($request->all(), ['password' => 'required']);
        if ($validator->fails()) {
           return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }


        if(isset($request->mobile) && $request->mobile!=null)
        {
            $validator1 = Validator::make($request->all(), ['mobile' => 'numeric']);
            if ($validator1->fails()) {
                return $this->sendBadException(implode(',',$validator1->errors()->all()),null);
            }

            $validator2 = Validator::make($request->all(), ['mobile' => 'min:10|max:15|unique:users']);
                if ($validator2->fails()) {
                    return $this->sendBadException(implode(',',$validator2->errors()->all()),null);
            }
        }

        if(isset($request->email) && $request->email!=null)
        {
            $validator2 = Validator::make($request->all(), ['email' => 'required|email|unique:users']);
            if ($validator2->fails()) {
                return $this->sendBadException(implode(',',$validator2->errors()->all()),null);
            }

            $email = $request->email;
            $eString = explode("@", $email);
            $userPart = $eString[0]; //default length max is 64
            $domainString = explode(".", $eString[1],2);
            $domainName = $domainString[0]; //default length max is 62
            $domainPart = $domainString[1]; //default length is 63
            if(strlen($userPart)>64){
                return $this->sendBadException('The email must be a valid email address.',null);
            }
            if(strlen($domainName)>62){
                return $this->sendBadException('The email must be a valid email address.',null);
            }

            $extensions = array('com', 'co', 'in', 'org','net','info','us','co.in','online');
            if (!in_array($domainPart, $extensions)){
              return $this->sendBadException('The email must be a valid email address.',null);
            }
        }

        if(!isset($request->mobile) && !isset($request->email)){
            return $this->sendBadException("Email or Mobile field is required",null);
        }
        //validation end


        $user = JWTAuth::parseToken()->authenticate();

        $checkUser = User::find($user->id);

        if (Hash::check($request->password,$checkUser->password))
        {
            if(isset($request->email) && $request->email!=null)
            {
              $checkUser->email = $request->email;
            }

            if(isset($request->mobile) && $request->mobile!=null)
            {
              $checkUser->mobile = $request->mobile;
            }

            $checkUser->update();
            return $this->sendResponse("Updated Successfully...",null);
        }else{
            return $this->sendBadException("Sorry, Password is Incorrect...",null);
        }

    }

    /*
    * Delete account api
    */
    public function deleteAccount(Request $request)
    {

        $validator = Validator::make($request->all(), ['password' => 'required']);
        if ($validator->fails()) {
           return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();

        $checkUser = User::find($user->id);

        if (Hash::check($request->password,$checkUser->password))
        {
            $checkUser->is_active = 2; //0->inactive,1->active,2->deleted
            $checkUser->update();

            //token deletion
            $token = JWTAuth::getToken();
            if ($token) {
                JWTAuth::setToken($token)->invalidate();
            }

            return $this->sendResponse("Profile Deleted Successfully...",null);
        }else{
            return $this->sendBadException("Sorry, Password is Incorrect...",null);
        }

    }

    /**
     * user mute or unmute
     * @return json seccess response
     */
    public function userMuteOrUnmute(Request $request)
    {   
        //find loggend user
        $authUser = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), ['userId' =>'required|numeric','mute' =>'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //find product
        $usr = User::find($request->userId);
        if($usr == null){
            return $this->sendBadException('User not found',null);
        }

        //find user muted or not
        $count_ld = UserMute::where([['userId',$authUser->id],['mutedUserId',$usr->id]])->count();
        if($request->mute==true)
        {
            if($count_ld == 0)
            {   //if not exists
                $new_save = new UserMute();
                $new_save->mutedUserId = $usr->id;
                $new_save->userId = $authUser->id;
                $new_save->savedOn = date('Y-m-d H:i:s');
                $new_save->save();
                $message = "User muted successfully";
            }else{
                $message = "You already muted this User";
            }
        }
        else if($request->save==false)
        {
            if($count_ld > 0)
            {   //if exists
                $record_del = UserMute::where([['userId',$authUser->id],['mutedUserId',$usr->id]])->delete();
                $message = "User un-muted successfully";
            }else{
                $message = "This user is not muted, please mute first then un-mute";
                return $this->sendBadException($message,null);
            }
        }

        return $this->sendResponse($message,null);
    }

    /**
     * delete user profile or cover pics
     * @return json seccess response
     */
    public function deleteProfilePics(Request $request)
    {
        $validator = Validator::make($request->all(), ['type' => 'required']);
        if($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        // the token is valid and we have found the user via the sub claim
        $checkUser = User::find($user->id);
        if($checkUser == null){
            return $this->sendBadException('User Not Found',null);
        }


        if($request->type=='dp')
        {
            $column = 'image'; $ratioClm = 'image_ratio'; $columnType = 'DP';
        }else if($request->type=='cover')
        {
            $column = 'cover_pic'; $ratioClm = 'cover_pic_ratio'; $columnType = 'COVER';
        }else{
            return $this->sendBadException("type is not valid, it must be dp/cover",null);
        }


        //delete from s3
           // $path = parse_url($checkUser->$column, PHP_URL_PATH);
           // if(Storage::disk('s3')->exists($path)) {
             //   Storage::disk('s3')->delete($path);
                    //delete posts based on url
                        $post = PostMedia::where([['userId',$user->id],['url',$checkUser->$column]])->first();
                        if($post==null){
                            return $this->sendBadException("Sorry, $column is not found",null);
                        }
                        $cronCtrl = new CronJobController();
                        $deletePosts = $cronCtrl->deletePostsCommonFunction($post->postId);
                    //ends
                $checkUser->$column = null;
                $checkUser->$ratioClm = null;
                UserMedia::where([['userId',$user->id],['url',$checkUser->$column],['type',$columnType]])->delete();
            //}else{
            //    return $this->sendBadException('Sorry, $request->type is not found',null);
            //}
        //ends

        $checkUser->update();

        return $this->sendResponse("$request->type deleted successfully...!",null);
    }


/********************* Private / Re-Usable functions here************************************/

    /*generating random string here for forgot pwd*/
    private function randomString($length = 20)
    {
          $str = "";
          $characters = array_merge(range('A','Z'), range('a','z'), range('0','9'));
          $max = count($characters) - 1;
          for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
          }
          return $str;
    }

    /** followDefaultOdcetUsers after registration **/
    private function followDefaultOdcetUsers($userId)
    {   
        $odcetPublicIds = [1,2,3,4,5,7,8,9,10,11,12,13,14,15,16,17];
        foreach ($odcetPublicIds as $followId) {
            $friend = new Friends();
            $friend->userId = $userId;
            $friend->friendId = $followId;
            $friend->createdOn = date('Y-m-d H:i:s');
            $friend->status = true;
            $friend->save();
        }
        return "done";
    }

    //re-usable function for email sending with params
    /*private function emailSending($data,$emails_list,$emailType)
    {
    	$template=null;$subject=null;

    	if($emailType=='forgot_pwd'){
    		$template = 'emails.forgotpwd';
    		$subject = 'Forgot Password from ODCET';
    	}

        if($emailType=='registration'){
            $template = 'emails.register';
            $subject = 'Welcome to ODCET';
        }

        Mail::send($template,$data, function ($message) use ($emails_list,$subject){
            $message->from('noreply@odcet.com', 'ODCET');
            $message->to($emails_list)->subject($subject);
        });
        if (Mail::failures()) {
      	  return $this->sendException("Something went wrong, Please try again later",null);
        }
        return $this->sendResponse("Email has been sent successfully",null);
    }*/

   /* private function sendSMS($mobile,$typeMsg,$otp)
    {

        $endpoint = "http://bhashsms.com/api/sendmsg.php"; 
        $client = new \GuzzleHttp\Client();
        //$otp = mt_rand(1000,9999);

        $mobileNo = substr($mobile, -10);  //take only last 10 digits from mobile
        $countryCode = substr($mobile, 0, 2);
        //http://bhashsms.com/api/sendmsg.php?user=Apnachotu&pass=Sheshu@2021&sender=ACHOTU&phone=9182761331&text=our%20OTP%20for%20APNA%20CHOTU%20app%20login%20is%201234.Thank%20you%20for%20choosing%20us.&priority=ndnd&stype=normal
        if($typeMsg=='mobile_verify'){
            //$value = "APNA CHOTU Mobile Number Verification OTP is ".$otp;
            $value = "our OTP for APNA CHOTU app login is ".$otp.".Thank you for choosing us.";
        }else if($typeMsg=='forgot_pwd'){
            $value = "our OTP for APNA CHOTU app login is ".$otp.".Thank you for choosing us.";
        }

        //sending sms
        try{
                $response = $client->request('GET', $endpoint, 
                            ['query' => [
                                'user' => 'Apnachotu',
                                'pass' => 'Sheshu@2021',
                                'sender' => 'ACHOTU',
                                'phone' => $mobileNo,
                                'text' => $value,
                                'priority'=>'ndnd',
                                'stype'=>'normal'
                            ]]);

                $statusCode = $response->getStatusCode();
                //$content = $response->getBody();
                
                //save otp response
                $saveOtp = new MobileOTP();
                $saveOtp->createdOn = date('Y-m-d H:i:s');
                $saveOtp->mobileno = $mobile;
                $saveOtp->type = ($typeMsg=='mobile_verify')?'REGISTER':'FORGOTPWD';
                $saveOtp->otp =$otp;
                $saveOtp->status =($statusCode==200)?'success':'failure';
                $saveOtp->save();

                if($statusCode==200){
                    return array("otp"=>$otp,"status"=>"ok");
                }else{
                    return array("otp"=>null,"status"=>"bad");
                }

        }catch(Exception $ex){
            return array("otp"=>null,"status"=>"bad");
        }
    }*/

    private function sendSMS($mobile,$typeMsg,$otp)
    {

        $endpoint = "http://113.193.191.132/smpp/"; 
        $client = new \GuzzleHttp\Client();
        //$otp = mt_rand(1000,9999);

        $mobileNo = substr($mobile, -10);  //take only last 10 digits from mobile
        $countryCode = substr($mobile, 0, 2);
        //http://bhashsms.com/api/sendmsg.php?user=Apnachotu&pass=Sheshu@2021&sender=ACHOTU&phone=9182761331&text=our%20OTP%20for%20APNA%20CHOTU%20app%20login%20is%201234.Thank%20you%20for%20choosing%20us.&priority=ndnd&stype=normal
        if($typeMsg=='mobile_verify'){
            //$value = "APNA CHOTU Mobile Number Verification OTP is ".$otp;
            $value = "our OTP for APNA CHOTU app login is ".$otp.".Thank you for choosing us.";
        }else if($typeMsg=='forgot_pwd'){
            $value = "our OTP for APNA CHOTU app login is ".$otp.".Thank you for choosing us.";
        }

        //sending sms
        try{
                $response = $client->request('GET', $endpoint, 
                            ['query' => [
                                'username' => 'apnachotu',
                                'password' => '582803',
                                'to' => '91'.$mobileNo,
                                'text' => $value,
                                'from' => 'ACHOTU'
                            ]]);

                $statusCode = $response->getStatusCode();
                //$content = $response->getBody();
                
                //save otp response
                $saveOtp = new MobileOTP();
                $saveOtp->createdOn = date('Y-m-d H:i:s');
                $saveOtp->mobileno = $mobile;
                $saveOtp->type = ($typeMsg=='mobile_verify')?'REGISTER':'FORGOTPWD';
                $saveOtp->otp =$otp;
                $saveOtp->status =($statusCode==200)?'success':'failure';
                $saveOtp->save();

                if($statusCode==200){
                    return array("otp"=>$otp,"status"=>"ok");
                }else{
                    return array("otp"=>null,"status"=>"bad");
                }

        }catch(Exception $ex){
            return array("otp"=>null,"status"=>"bad");
        }
    }

    private function sendOTPEmail($otp,$email)
    {
        $data = array('otp' => $otp);
        Mail::send('emails.forgotpwd',$data, function ($message) use ($email){
            $message->from('noreply.apnachotu@gmail.com', 'Apnachotu');
            $message->to($email)->subject('Mobile Verify OTP from APNACHOTU');
        });
        if (Mail::failures()) {
            return "email sending failure";
        }
    }

    //re-usable function for email sending with params
    /*private function sendSMSOLD($mobile,$typeMsg)
    {
        //new url 2:  url will be: http://msg.vcansoftsol.com/otphttp.php?authkey=CGjFa2JDJjPHnK0Oy697&mobiles=9603958323&message=ODCET mobile number verification OTP is 3432&sender=ODCETM&route=4&country=91;

        //http://msg.vcansoftsol.com/otp_balance.php?authkey=hANTDuZhE47HbfP6fvcX
        //http://msg.vcansoftsol.com/otphttp.php?authkey=hANTDuZhE47HbfP6fvcX&mobiles=9182761331&message=hello test message&sender=ANPACH&route=4&country=91
       
        $endpoint = "http://msg.vcansoftsol.com/otphttp.php"; //"http://vcansoftsol.com/send.php";
		$client = new \GuzzleHttp\Client();
		$otp = mt_rand(1000,9999);

        //return array("otp"=>$otp,"status"=>"ok");

        $mobileNo = substr($mobile, -10);  //take only last 10 digits from mobile no
        $countryCode = substr($mobile, 0, 2);

		if($typeMsg=='mobile_verify'){
			//$value = "APNA CHOTU Mobile Number Verification OTP is ".$otp;
            $value = "our OTP for APNA CHOTU app login is ".$otp.". Thank you for choosing us";
		}else if($typeMsg=='forgot_pwd'){
			$value = "APNA CHOTU Forgot Password OTP is ".$otp;
		}
        //sending sms
		try{
                $response = $client->request('GET', $endpoint, ['query' => [
                                'mobile' => $mobileNo,
                                'text' => $value
                            ]]);

                $response = $client->request('GET', $endpoint, ['query' => [
                                'authkey' => 'hANTDuZhE47HbfP6fvcX',
                                'mobiles' => $mobileNo,
                                'message' => $value,
                                'sender' => 'ACHOTU',
                                'route'=>'4',
                                'country'=>($countryCode!=null)?$countryCode:'91'
                            ]]);

                

                $statusCode = $response->getStatusCode();
                //$content = $response->getBody();
                
                //save otp response
                $saveOtp = new MobileOTP();
                $saveOtp->createdOn = date('Y-m-d H:i:s');
                $saveOtp->mobileno = $mobile;
                $saveOtp->type = ($typeMsg=='mobile_verify')?'REGISTER':'FORGOTPWD';
                $saveOtp->otp =$otp;
                $saveOtp->status =($statusCode==200)?'success':'failure';
                $saveOtp->save();

                if($statusCode==200){
                    return array("otp"=>$otp,"status"=>"ok");
                }else{
                    return array("otp"=>null,"status"=>"bad");
                }

        }catch(Exception $ex){
            return array("otp"=>null,"status"=>"bad");
        }
    }*/


}
