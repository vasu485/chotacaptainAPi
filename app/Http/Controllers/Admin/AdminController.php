<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorTags;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Order;
use App\Models\OrderUpdates;
use App\Models\OrderItems;
use App\Models\Status;
use App\Models\Location;
use App\Models\PartnerLocation;
use App\Models\Item;
use App\Models\Settings;
use App\Models\Notification;
use App\Models\Sattlements;
use App\Models\OrderAssignToDeliveryBoy;
use App\Models\PrimeCustomerData;
use App\Models\TermsCondition;
use DB;
use Hash;
use Mail;
use JWTAuth;
use Exception;
use Storage;
use Validator;
use Tymon\JWTAuthExceptions\JWTException;
date_default_timezone_set('Asia/Kolkata');
ini_set('memory_limit','1024M');
ini_set('max_execution_time', 1800); //30 mins, 300-5 mins

class AdminController extends Controller
{
    
    public function __construct()
    {
       $this->middleware('jwt.auth', ['except' => ['login']]);
    }

    /*
    * Login api
    */
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), ['password' => 'required']);
        if ($validator->fails()) {
           return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }


       if(isset($request->mobile) && $request->mobile!=null){
         $validator1 = Validator::make($request->all(), ['mobile' => 'numeric']);
         if ($validator1->fails()) {
                return $this->sendBadException(implode(',',$validator1->errors()->all()),null);
            }
        }

        if(isset($request->email) && $request->email!=null){
         $validator2 = Validator::make($request->all(), ['email' => 'email']);
         if ($validator2->fails()) {
                return $this->sendBadException(implode(',',$validator2->errors()->all()),null);
            }
        }

        if(!isset($request->mobile) && !isset($request->email)){
            return $this->sendBadException("Email or Mobile field is required",null);
        }

        // Request input contains mobile, email and password
            if ($request->has(['mobile', 'email', 'password'])) {
                $credentials = $request->only('mobile', 'password');
            } // Request input contains `enter code here`username and password
            elseif ($request->has(['mobile', 'password'])) {
                $credentials = $request->only('mobile', 'password');
            } // Request input contains email and password
            elseif ($request->has(['email', 'password'])) {
                $credentials = $request->only('email', 'password');
            }
            else {
                $credentials = $request->only('email', 'password');
            }

        try {
              // verify the credentials and create a token for the user
              if (! $token = JWTAuth::attempt($credentials)) {
                  return response()->json(['status' => 'error','message' => 'invalid_credentials','data' => null], 401);
              }
        } catch (JWTException $e) {
              // something went wrong
            return response()->json(['status' => 'error','message' => 'could_not_create_token','data' => null], 500);
        }

        $user = JWTAuth::user();
        $user->image = ($user->image!=null)?request()->getSchemeAndHttpHost().'/images/users/'.$user->image:null;

        $msg = 'User loggedIn Successfully..!';

        return response()->json(['status' => 'success','message' => $msg,'data' => $user])
                         ->header('token', $token)->header('token_expires', auth('api')
                         ->factory()
                         ->getTTL());
    }

    public function getAllOrders(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric','is_active'=>'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
          return $this->sendUnAuthException("Sorry..! you are not authorized to access",null);
        }

        $orders = Order::with('vendor','status','location','updates');
        if(isset($request->partnerid)){
            $orders = $orders->where('partnerid',$request->partnerid);
        }
        if($request->is_active==true){
           $orders = $orders->whereNotIn('status',[3,4,11,16]);
        }else{
           if(isset($request->statusId) && $request->statusId!=null)
           {
                $orders = $orders->where('status',$request->statusId);
           }else{
                $orders = $orders->whereIn('status',[3,4,11,16]);
           } 
           
        }

        if( isset($request->dates) && count($request->dates) > 0 )
        {   
            if( count($request->dates)==2 )
            {   
                $from = $request->dates[0]." 00:00:00";
                $to = $request->dates[1]." 23:59:59";

                $orders = $orders->whereBetween('createdOn', [$from, $to]);
            }else{
                $from = $request->dates[0]." 00:00:00";
                $to = $request->dates[0]." 23:59:59";
                $orders = $orders->whereBetween('createdOn', [$from, $to]);
            }
        }

        //with order type normal/mis
        if($request->type!=null){
           $orders = $orders->where('type',$request->type);
        }

        $counts = $orders->orderBy('createdOn','desc')->count();

        $orders = $orders->orderBy('createdOn','desc')->offset($offset)->limit($limit)->get();

        foreach ($orders as $or) {
            $or->deliveryBoy = User::find($or->deliveryBoy);
            $or->order_by = User::find($or->orderBy);
            unset($or->orderBy);
            //$or->categoryId = ($or->vendor!=null)?$or->vendor['categoryId']:null;
            $cat_sub_category = null;
            if($or->categoryId!=null)
            {   
                $c = Category::find($or->categoryId);
                $cat_sub_category = array('categoryId'=>$c->id,"categoryName"=>$c->name);
            }
            if($or->subCategoryId!=null)
            {   
                $sc = SubCategory::find($or->subCategoryId);
                $cat_sub_category['subCategoryId'] = $sc->id;
                $cat_sub_category['subCategoryName'] = $sc->name;
            }
            $or->cat_sub_category = $cat_sub_category;

            foreach ($or->updates as $up_date) 
            {
                if($up_date->updatedBy!='BOT')
                {
                    $u = User::find($up_date->updatedById);
                    $up_date->username = $u->first_name.' '.$u->last_name;
                }else{
                 $up_date->username = 'AI';
                }
            }
        }

        return response()->json(['status' => 'success','message' => "Orders list...!",'order_count'=>$counts,'data' => $orders], 200);

        //return $this->sendResponse("Orders list...!",$orders);
    }

    public function getAllDeliveryBoys($id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
          return $this->sendUnAuthException("Sorry..! you are not authorized to access",null);
        };
        if(isset($id)){
            $boys = User::where([['user_role','DELIVERY_BOY'],['is_active','!=',2],['locationid',$id]])->get();
        }else{
            $boys = User::where([['user_role','DELIVERY_BOY'],['is_active','!=',2]])->get();

        }
        
        
        // ,['is_active',1]

        foreach($boys as $b)
        {
            if($b->boyCategory!=null)
            {  
              $b->boyCategory = explode(",",$b->boyCategory);  
            }else{
              $b->boyCategory = array();
            }
        }

        return $this->sendResponse("Delivery Boys list...!",$boys);
    }

    public function getSingleDeliveryBoy($id)
    {   
        if($id==null){
            return $this->sendBadException('Invalid id',null);
        }

        $del_boy = User::where([['id',$id],['user_role','DELIVERY_BOY']])->first();
        if($del_boy==null){
            return $this->sendBadException('Delivery Boy not found',null);
        }

        if($del_boy->boyCategory!=null && count($del_boy->boyCategory)>0)
        {
          $del_boy->boyCategory = explode(",",$del_boy->boyCategory);  
        }else{
          $del_boy->boyCategory = array();
        }
        
        unset($del_boy->address);
        unset($del_boy->lat);
        unset($del_boy->lng);
        unset($del_boy->privilege);

        return $this->sendResponse("Single Delivery Boy...!",$del_boy);
    }

    public function editDeliveryBoy(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['id' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $del_boy = User::find($request->id);
        if($del_boy==null){
            return $this->sendBadException('Delivery Boy not found',null);
        }

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

        if(isset($request->boyCategory))
        {
            $del_boy->boyCategory     = implode(",",$request->boyCategory);
        }
        
        $del_boy->update();

        return $this->sendResponse("Delivery Boy Details Updated Successfully...!",null);

    }




/*-------vendor settlements starts------------*/
    public function getCurrentDaySettlementOrders(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['vendorId' => 'required|numeric','date' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $vendor = Vendor::find($request->vendorId);
        if($vendor==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $orders = Order::with('orderBy','status','location','deliveryBoy')
                  ->where([['vendorId',$vendor->id],['createdOn','LIKE','%'.$request->date.'%']])
                  ->whereIn('status',[4,16])
                  ->orderBy('createdOn','desc')
                  ->get();

        if(count($orders)==0){
            return $this->sendBadException('Sorry, No Orders on this Date for this Vendor',null);
        }

        $totalOrdersPrice = array(); 
        $vendorItemsPrice = array(); 
        $deliveryFee = array(); 
        $discountPrice = array();
        $comission = array();
        $c =array();
        $notDeliveredOrdersCommisionDiscount = array();
        $notDeliveredOrdersAfterCommisionDiscount = array();
        $tip = array();

        foreach ($orders as $v) 
        {   

            array_push($totalOrdersPrice, $v->finalPrice);
            array_push($vendorItemsPrice, $v->itemsPrice);           
            //daily and hourly vendor can change commision to chotu
            if($v->status==4)
            {   
               array_push($deliveryFee, $v->deliveryFee);
               array_push($discountPrice, $v->discountPrice);
               array_push($tip, $v->tip);

               $c1 = ($v->itemsPrice*($v->apnachotu_commision/100));
               array_push($comission, $c1);
            }else{
               $c2 = ($v->itemsPrice*($v->apnachotu_commision/100));
               $c2 = $c2 + $v->discountPrice;
               array_push($notDeliveredOrdersCommisionDiscount, $c2);
               $c3 = $v->itemsPrice - $c2;
               array_push($notDeliveredOrdersAfterCommisionDiscount, $c3);
            }
            $v->apnachotu_commision =  $v->apnachotu_commision.'%';
        }


        $notDeliveredOrdersCommisionDiscount  = array_sum($notDeliveredOrdersCommisionDiscount);
        $notDeliveredOrdersAfterCommisionDiscount = array_sum($notDeliveredOrdersAfterCommisionDiscount);
        // return $comission;

        $vendorItemsPrice = array_sum($vendorItemsPrice);
        $comission = array_sum($comission) + $notDeliveredOrdersCommisionDiscount;
        $tip = array_sum($tip);
        $deliveryFee = array_sum($deliveryFee);
        $discountPrice = array_sum($discountPrice);

        $settlements = array(
        "totalOrdersPrice"=> array_sum($totalOrdersPrice) - $tip,
        "itemsPrice"=> $vendorItemsPrice,
        "deliveryFee"=> $deliveryFee,
        "discountPrice"=> $discountPrice,
        "comission" => round($comission, 2),
        "tip" => $tip,
        "finalVendorAmount"=> round($vendorItemsPrice - $comission - $discountPrice,2),
        "finalVendorAmountCalculation"=> 'itemsPrice - comission - discountPrice',
        "apnachotuProfit"=> round($deliveryFee + $comission  - $notDeliveredOrdersAfterCommisionDiscount,2), 
        "apnachotuProfitCalculation"=> 'deliveryFee + comission  - notDeliveredOrdersAmountAfterComissionAndDiscount',
        "currentPercentageToChotu"=>$vendor->percentage_to_chotu.'%',
        "notDeliveredOrdersCommisionDiscount"=>$notDeliveredOrdersCommisionDiscount,
        "notDeliveredOrdersAmountAfterComissionAndDiscount"=>$notDeliveredOrdersAfterCommisionDiscount,
        );

        unset($vendor->percentage_to_chotu);

        $res['dates'] = $request->date;
        $res['vendor'] = $vendor;

        $findSatment = Sattlements::where([['sattlementForId',$vendor->id],['dates',$request->date]])->first();

        if(count($orders)>0)
        {
                if($findSatment==null){
                    $add_satlements = new Sattlements();
                    $add_satlements->dates = $request->date;
                    $add_satlements->sattlementFor = 'VENDOR';
                    $add_satlements->sattlementForId = $vendor->id;
                    $add_satlements->paymentMode = 'CASH';
                    $add_satlements->status = 'PENDING';
                    $add_satlements->sattlements = json_encode($settlements);
                    $add_satlements->orders = json_encode($orders);
                    $add_satlements->createdOn = date('Y-m-d H:i:s');
                    $add_satlements->save();
                    $res['settlementStatus'] = 'PENDING';
                    $res['settlementId'] = $add_satlements->id;
                    $res['paymentMode'] = null;
                    $res['settlements'] = $settlements;
                    $res['orders'] = $orders;
                }else{
                    $add_satlements = Sattlements::find($findSatment->id);
                    //$add_satlements->paymentMode = 'CASH';
                    //$add_satlements->status = 'PENDING';
                    if($add_satlements->status!='DONE'){
                        $add_satlements->sattlements = json_encode($settlements);
                        $add_satlements->orders = json_encode($orders);
                        $add_satlements->updatedOn = date('Y-m-d H:i:s');
                        $add_satlements->update();
                        $res['settlementStatus'] = $add_satlements->status;
                        $res['settlementId'] = $add_satlements->id;
                        $res['paymentMode'] = null;
                        $res['settlements'] = $settlements;
                        $res['orders'] = $orders;
                    }else{
                        $res['settlementStatus'] = $add_satlements->status;
                        $res['settlementId'] = $add_satlements->id;
                        $res['paymentMode'] = $add_satlements->paymentMode;
                        $res['settlements'] = json_decode($add_satlements->sattlements);
                        $res['orders'] = json_decode($add_satlements->orders);
                    }
                    
                }
        }

        return $this->sendResponse("Vendor Settlement data..!",$res);
    }

    public function vendorMakeSettlementPayment(Request $request)
    {
       //validation start
        $validator = Validator::make($request->all(), ['settlementId' => 'required|numeric','paymentMode' => 'required','status' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        
        $settlement = Sattlements::find($request->settlementId);
        if($settlement==null){
            return $this->sendBadException('settlement not found',null);
        }
        if($settlement->sattlementFor!='VENDOR'){
            return $this->sendBadException('This is not Vendor settlement',null);
        }

        if($settlement->status=='DONE' && $request->status=='PENDING')
        {
            return $this->sendResponse("Sorry, You can't update status, because te payment is alredy done..!",null);
        }else if($settlement->status=='DONE' && $request->status=='DONE')
        {
            return $this->sendResponse("Vendor Settlement payment already done..!",null);
        }else if($settlement->status=='PENDING' && $request->status=='DONE'){
            $settlement->paymentMode = $request->paymentMode;
            $settlement->status = $request->status;
            $settlement->paymentDoneOn = date('Y-m-d H:i:s');
            $settlement->updatedOn = date('Y-m-d H:i:s');
            $settlement->update();
            return $this->sendResponse("Vendor Settlement payment done..!",null);
        }

    }

    public function getAllSettlements(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
          return $this->sendUnAuthException("Sorry..! you are not authorized to access",null);
        }
   
        $settlements = Sattlements::with('vendor')
                       ->where([['sattlementFor','VENDOR']])
                       ->orderBy('createdOn','desc')
                       ->offset($offset)->limit($limit)->get();

        foreach ($settlements as $value) {
            $value->sattlements = json_decode($value->sattlements);
            $value->orders = json_decode($value->orders);
            unset($value->boyAmount);
        }

        return $this->sendResponse("All Vendors Settlements..!",$settlements);

    }

    public function getSingleVendorSettlements(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['vendorId' => 'required|numeric','offset' => 'required|numeric','limit' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
          return $this->sendUnAuthException("Sorry..! you are not authorized to access",null);
        }
   
        $settlements = Sattlements::with('vendor')
                       ->where([['sattlementFor','VENDOR'],['sattlementForId',$request->vendorId]])
                       ->orderBy('createdOn','desc')
                       ->offset($offset)->limit($limit)->get();

        foreach ($settlements as $value) {
            $value->sattlements = json_decode($value->sattlements);
            $value->orders = json_decode($value->orders);
            unset($value->boyAmount);
        }

        return $this->sendResponse("All Single Vendor Settlements..!",$settlements);

    }
/*-------vendor settlements ends------------*/

/*-------deliveryBoy settlements starts------------*/
    public function getBoyCurrentDaySettlementOrders(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['deliveryBoyId' => 'required|numeric','date' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $boy = User::find($request->deliveryBoyId);
        if($boy==null){
            return $this->sendBadException('Boy not found',null);
        }
        if($boy->user_role!='DELIVERY_BOY'){
            return $this->sendBadException('Boy not found',null);
        }

        $orders = Order::with('orderBy','status','location','vendor')
                    ->where([['deliveryBoy',$boy->id],['createdOn','LIKE','%'.$request->date.'%']])
                    ->whereIn('status',[4,16])
                    ->orderBy('createdOn','desc')->get();

        if(count($orders)==0){
            return $this->sendBadException('Sorry, No Orders on this Date for this Delivery Boy',null);
        }

        $cashPayment = Order::where([['deliveryBoy',$boy->id],['paymentMode','CASH'],['createdOn','LIKE','%'.$request->date.'%']])->sum('finalPrice');

        $onlinePayment = Order::where([['deliveryBoy',$boy->id],['paymentMode','ONLINE'],['createdOn','LIKE','%'.$request->date.'%']])->sum('finalPrice');

        $apnachotuTotalPrice = array(); 
        $vendorSattlementPrice = array(); 
        $apnachotuDeliveryPrice = array(); 
        $apnachotuTotalDiscountPrice = array(); 
        $tip = array(); 

        foreach ($orders as $v) 
        {
            if($v->status==4)
            {
                array_push($apnachotuTotalPrice, $v->finalPrice);
                array_push($vendorSattlementPrice, $v->itemsPrice);
                array_push($apnachotuDeliveryPrice, $v->deliveryFee);
                array_push($apnachotuTotalDiscountPrice, $v->discountPrice); 
                array_push($tip, $v->tip); 
            }
        }

        /*$settlements = array("apnachotuTotalPrice"=> array_sum($apnachotuTotalPrice),
        "vendorSattlementPrice"=> array_sum($vendorSattlementPrice),
        "apnachotuDeliveryPrice"=> array_sum($apnachotuDeliveryPrice),
        "apnachotuTotalDiscountPrice"=> array_sum($apnachotuTotalDiscountPrice),
        "tip"=> array_sum($tip));*/

        $from = $request->date." 00:00:00";
        $to = $request->date." 23:59:59";

        $boySettlementAmount = $this->calculateBoyPaymentBasedOnOrderCount($from,$to,$boy->id);
        $boyBikePertolAmount = (count($orders)>=10)?100:0;

        $settlements = array("TotalOrdersAmount"=> array_sum($apnachotuTotalPrice),
                            "totalTip"=> array_sum($tip),
                            "onlineOrdersAmount"=> $onlinePayment,
                            "cashOrdersAmount"=> $cashPayment,
                            "finalPayAmountToApnachotu"=> array_sum($apnachotuTotalPrice) - array_sum($tip),
                            "totalBoyCommision"=> null,//$getCommision
                            "boyOrdersSettlementAmount"=>$boySettlementAmount,
                            "boyBikePertolAmount"=>$boyBikePertolAmount,
                            "boyFinalSettlementAmount"=>$boySettlementAmount+$boyBikePertolAmount);


        $res['dates'] = $request->date;
        $res['boy'] = $boy;

        $findSatment = Sattlements::where([['sattlementForId',$boy->id],['dates',$request->date]])->first();

        if($findSatment==null){
            $add_satlements = new Sattlements();
            $add_satlements->dates = $request->date;
            $add_satlements->sattlementFor = 'DELIVERY_BOY';
            $add_satlements->sattlementForId = $boy->id;
            $add_satlements->paymentMode = 'CASH';
            $add_satlements->status = 'PENDING';
            $add_satlements->sattlements = json_encode($settlements);
            $add_satlements->orders = json_encode($orders);
            $add_satlements->createdOn = date('Y-m-d H:i:s');
            $add_satlements->save();
            $res['settlementStatus'] = 'PENDING';
            $res['settlementId'] = $add_satlements->id;
            $res['paymentMode'] = null;
        }else{
            $add_satlements = Sattlements::find($findSatment->id);
            //$add_satlements->paymentMode = 'CASH';
            //$add_satlements->status = 'PENDING';
            if($add_satlements->status!='DONE'){
                $add_satlements->sattlements = json_encode($settlements);
                $add_satlements->orders = json_encode($orders);
                $add_satlements->updatedOn = date('Y-m-d H:i:s');
                $add_satlements->update();
                $res['settlementStatus'] = $add_satlements->status;
                $res['settlementId'] = $add_satlements->id;
                $res['paymentMode'] = null;
            }else{
                $res['settlementStatus'] = $add_satlements->status;
                $res['settlementId'] = $add_satlements->id;
                $res['paymentMode'] = $add_satlements->paymentMode;
            }
            
        }

        $res['settlements'] = $settlements;
        $res['orders'] = $orders;
        
        return $this->sendResponse("deliveryBoy Settlement data..!",$res);
    }

    public function getBoyBetweenDatesSettlementOrders(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['deliveryBoyId' => 'required|numeric','fromDate' => 'required','toDate' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $boy = User::find($request->deliveryBoyId);
        if($boy==null){
            return $this->sendBadException('Boy not found',null);
        }
        if($boy->user_role!='ADMIN' && $boy->user_role!='EDITOR' && $boy->user_role!='OPERATOR'){
            return $this->sendBadException('Boy not found',null);
        }

        $orders = Order::with('orderBy','status','location','vendor')
                ->where([['deliveryBoy',$boy->id]])
                ->whereIn('status',[4])
                ->whereBetween('createdOn', [$request->fromDate, $request->toDate])
                ->orderBy('createdOn','desc')
                ->get();

        if(count($orders)==0){
            return $this->sendBadException('Sorry, No Orders on this Date for this Delivery Boy',null);
        }

        $apnachotuTotalPrice = array(); 
        $vendorSattlementPrice = array(); 
        $apnachotuDeliveryPrice = array(); 
        $apnachotuTotalDiscountPrice = array(); 
        $tip = array(); 

        foreach ($orders as $v) {

            array_push($apnachotuTotalPrice, $v->finalPrice);
            array_push($vendorSattlementPrice, $v->itemsPrice);
            array_push($apnachotuDeliveryPrice, $v->deliveryFee);
            array_push($apnachotuTotalDiscountPrice, $v->discountPrice);
            array_push($tip, $v->tip);
            
        }

        $settlements = array("apnachotuTotalPrice"=> array_sum($apnachotuTotalPrice),
        "vendorSattlementPrice"=> array_sum($vendorSattlementPrice),
        "apnachotuDeliveryPrice"=> array_sum($apnachotuDeliveryPrice),
        "apnachotuTotalDiscountPrice"=> array_sum($apnachotuTotalDiscountPrice),
        "tip"=> array_sum($tip));


        $dates = $request->fromDate.' to '.$request->toDate;

        $res['dates'] = $dates;
        $res['boy'] = $boy;

        $findSatment = Sattlements::where([['sattlementForId',$boy->id],['dates',$dates]])->first();

        if($findSatment==null){
            $add_satlements = new Sattlements();
            $add_satlements->dates = $dates;
            $add_satlements->sattlementFor = 'DELIVERY_BOY';
            $add_satlements->sattlementForId = $boy->id;
            $add_satlements->paymentMode = 'CASH';
            $add_satlements->status = 'PENDING';
            $add_satlements->sattlements = json_encode($settlements);
            $add_satlements->orders = json_encode($orders);
            $add_satlements->createdOn = date('Y-m-d H:i:s');
            $add_satlements->save();
            $res['settlementStatus'] = 'PENDING';
            $res['settlementId'] = $add_satlements->id;
            $res['paymentMode'] = null;
        }else{
            $add_satlements = Sattlements::find($findSatment->id);
            //$add_satlements->paymentMode = 'CASH';
            //$add_satlements->status = 'PENDING';
            if($add_satlements->status!='DONE'){
                $add_satlements->sattlements = json_encode($settlements);
                $add_satlements->orders = json_encode($orders);
                $add_satlements->updatedOn = date('Y-m-d H:i:s');
                $add_satlements->update();
                $res['settlementStatus'] = $add_satlements->status;
                $res['settlementId'] = $add_satlements->id;
                $res['paymentMode'] = null;
            }else{
                $res['settlementStatus'] = $add_satlements->status;
                $res['settlementId'] = $add_satlements->id;
                $res['paymentMode'] = $add_satlements->paymentMode;
            }
            
        }

        $res['settlements'] = $settlements;
        $res['orders'] = $orders;
        
        return $this->sendResponse("deliveryBoy Settlement data..!",$res);
    }

    public function boyMakeSettlementPayment(Request $request)
    {
       //validation start
        $validator = Validator::make($request->all(), ['settlementId' => 'required|numeric','paymentMode' => 'required','status' => 'required','boyAmount'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        
        $settlement = Sattlements::find($request->settlementId);
        if($settlement==null){
            return $this->sendBadException('settlement not found',null);
        }
        if($settlement->sattlementFor!='DELIVERY_BOY'){
            return $this->sendBadException('This is not Delivery boy settlement',null);
        }

        if($settlement->status=='DONE' && $request->status=='PENDING')
        {
            return $this->sendResponse("Sorry, You can't update status, because te payment is alredy done..!",null);
        }else if($settlement->status=='DONE' && $request->status=='DONE')
        {
            return $this->sendResponse("Boy Settlement already done..!",null);
        }else if($settlement->status=='PENDING' && $request->status=='DONE'){
            $settlement->paymentMode = $request->paymentMode;
            $settlement->status = $request->status;
            $settlement->boyAmount = $request->boyAmount;
            $settlement->paymentDoneOn = date('Y-m-d H:i:s');
            $settlement->updatedOn = date('Y-m-d H:i:s');
            $settlement->update();
            return $this->sendResponse("Boy Settlement Done..!",null);
        }

    }

    public function getBoysAllSettlements(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
          return $this->sendUnAuthException("Sorry..! you are not authorized to access",null);
        }
   
        $settlements = Sattlements::with('deliveryBoy')
                       ->where([['sattlementFor','DELIVERY_BOY']])
                       ->orderBy('createdOn','desc')
                       ->offset($offset)->limit($limit)->get();

        foreach ($settlements as $value) {
            $value->sattlements = json_decode($value->sattlements);
            $value->orders = json_decode($value->orders);
        }

        return $this->sendResponse("All Boys Settlements..!",$settlements);

    }

    public function getSingleBoySettlements(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['deliveryBoyId' => 'required|numeric','offset' => 'required|numeric','limit' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
          return $this->sendUnAuthException("Sorry..! you are not authorized to access",null);
        }
   
        $settlements = Sattlements::with('deliveryBoy')
                       ->where([['sattlementFor','DELIVERY_BOY'],['sattlementForId',$request->deliveryBoyId]])
                       ->orderBy('createdOn','desc')
                       ->offset($offset)
                       ->limit($limit)
                       ->get();

        foreach ($settlements as $value) {
            $value->sattlements = json_decode($value->sattlements);
            $value->orders = json_decode($value->orders);
        }

        return $this->sendResponse("All Single deliveryBoy Settlements..!",$settlements);

    }

/*-------deliveryBoy settlements ends------------*/

/*-------Misc settlements starts------------*/
    public function getParticularDayMiscSettlementOrders(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['date' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }


        $orders = Order::with('orderBy','status','location','deliveryBoy')
                  ->where([['type','mis'],['createdOn','LIKE','%'.$request->date.'%']])
                  ->whereIn('status',[4])
                  ->orderBy('createdOn','desc')
                  ->get();

        if(count($orders)==0){
            return $this->sendBadException('Sorry, No Mis-Orders on this Date',null);
        }

        $apnachotuTotalPrice = array(); 
        $vendorSattlementPrice = array(); 
        $apnachotuDeliveryPrice = array(); 
        $apnachotuTotalDiscountPrice = array(); 


        foreach ($orders as $v) 
        {
            array_push($apnachotuTotalPrice, $v->finalPrice);
            array_push($vendorSattlementPrice, $v->itemsPrice);
            array_push($apnachotuDeliveryPrice, $v->deliveryFee);
            array_push($apnachotuTotalDiscountPrice, $v->discountPrice);
        }


        $vendorSattlementPrice = array_sum($vendorSattlementPrice);

        $perctg = $vendorSattlementPrice;

        $settlements = array("apnachotuTotalPrice"=> array_sum($apnachotuTotalPrice),
        "vendorSattlementPrice"=> $vendorSattlementPrice,
        "apnachotuDeliveryPrice"=> array_sum($apnachotuDeliveryPrice),
        "apnachotuTotalDiscountPrice"=> array_sum($apnachotuTotalDiscountPrice),
        "afterVendorDiscountFinalSettlementToVendor"=> $perctg,
        "percentage_to_chotu"=>0);


        $res['dates'] = $request->date;
        $res['settlements'] = $settlements;
        $res['orders'] = $orders;

        /*$findSatment = Sattlements::where([['sattlementForId',$vendor->id],['dates',$request->date]])->first();

        if(count($orders)>0)
        {
                if($findSatment==null){
                    $add_satlements = new Sattlements();
                    $add_satlements->dates = $request->date;
                    $add_satlements->sattlementFor = 'VENDOR';
                    $add_satlements->sattlementForId = $vendor->id;
                    $add_satlements->paymentMode = 'CASH';
                    $add_satlements->status = 'PENDING';
                    $add_satlements->sattlements = json_encode($settlements);
                    $add_satlements->orders = json_encode($orders);
                    $add_satlements->createdOn = date('Y-m-d H:i:s');
                    $add_satlements->save();
                    $res['settlementStatus'] = 'PENDING';
                    $res['settlementId'] = $add_satlements->id;
                    $res['paymentMode'] = null;
                    $res['settlements'] = $settlements;
                    $res['orders'] = $orders;
                }else{
                    $add_satlements = Sattlements::find($findSatment->id);
                    //$add_satlements->paymentMode = 'CASH';
                    //$add_satlements->status = 'PENDING';
                    if($add_satlements->status!='DONE'){
                        $add_satlements->sattlements = json_encode($settlements);
                        $add_satlements->orders = json_encode($orders);
                        $add_satlements->updatedOn = date('Y-m-d H:i:s');
                        $add_satlements->update();
                        $res['settlementStatus'] = $add_satlements->status;
                        $res['settlementId'] = $add_satlements->id;
                        $res['paymentMode'] = null;
                        $res['settlements'] = $settlements;
                        $res['orders'] = $orders;
                    }else{
                        $res['settlementStatus'] = $add_satlements->status;
                        $res['settlementId'] = $add_satlements->id;
                        $res['paymentMode'] = $add_satlements->paymentMode;
                        $res['settlements'] = json_decode($add_satlements->sattlements);
                        $res['orders'] = json_decode($add_satlements->orders);
                    }
                    
                }
        }*/

        return $this->sendResponse("Misc Settlement data..!",$res);
    }

    /* location apis at admin side list and update data */
    public function getLocations()
    {
        $meta = Location::all();
        return $this->sendResponse("Locations",$meta);
    }
    public function getPartnerLocations()
    {
        $meta = PartnerLocation::all();
        return $this->sendResponse("Locations",$meta);
    }

    public function updateLocations(Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['id' =>'required|numeric','name' => 'required|string','is_active'=>'required|numeric','charge' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $loc = Location::find($request->id);
        if($loc==null){
            return $this->sendBadException('Location not found',null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update.",'data' => null],401);
        }

        if(isset($request->name) && $request->name!=null){
            $loc->name = trim($request->name);
        }
        if(isset($request->charge) && $request->charge!=null){
            $loc->charge = trim($request->charge);
        }
        $loc->is_active = ($request->is_active=="1")?true:false;
        $loc->update();

        return $this->sendResponse("Location updated successfully...!",null);
    }

    public function createLocations(Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string','charge' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }

        $checkloc = Location::where('name',$request->name)->first();
        if($checkloc!=null)
        {
            return $this->sendBadException("Location name already existed...!",null);
        }else{
            $loc = new Location();
            $loc->name = trim($request->name);
            $loc->charge = trim($request->charge);
            $loc->is_active = true;
            $loc->save();
        }
        

        return $this->sendResponse("Location created successfully...!",null);
    }
    public function createPartner (Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string','lat' => 'required|string','lon' => 'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='VENDOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => $user],401);
        }

        $checkloc = PartnerLocation::where('name',$request->name)->first();
        if($checkloc!=null)
        {
            return $this->sendBadException("Location name already existed...!",null);
        }else{
            $loc = new PartnerLocation();
            $loc->name = trim($request->name);
            $loc->lat = $request->lat;
            $loc->lon = $request->lon;

            $loc->save();
        }
        

        return $this->sendResponse("Location created successfully...!",null);
    }

    public function addDeliveryBoyCommision(Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['date' => 'required','commision_per_order' => 'required','min_orders_for_commision' => 'required',]);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $checkloc = Settings::where('createdOn',$request->date)->first();
        if($checkloc!=null)
        {
            $checkloc->commision_per_order = trim($request->commision_per_order);
            $checkloc->min_orders_for_commision = trim($request->min_orders_for_commision);
            $checkloc->createdOn = trim($request->date);
            $checkloc->update();
        }else{
            $loc = new Settings();
            $loc->commision_per_order = trim($request->commision_per_order);
            $loc->min_orders_for_commision = trim($request->min_orders_for_commision);
            $loc->createdOn = trim($request->date);
            $loc->save();
        }
        
        return $this->sendResponse("Settings updated successfully...!",null);
    }

    public function getDeliveryBoyCommisions(Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $comisions = Settings::orderBy('createdOn','desc')->offset($offset)->limit($limit)->get();
        
        return $this->sendResponse("Comisions list...!",$comisions);
    }

    //deliveryBoysCurrentOrdersStatus for admin only
    public function deliveryBoysCurrentOrdersStatus(Request $request)
    {

        $validator = Validator::make($request->all(), ['date' => 'required|Array']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $boy = JWTAuth::parseToken()->authenticate();
        if($boy->user_role!='ADMIN' && $boy->user_role!='EDITOR' && $boy->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $search_date = $request->date[0];

        $boys = User::where([['user_role','DELIVERY_BOY'],['is_active',1]])->select('id','first_name','last_name','is_active','mobile','lat','lng')->get();

        //$boys = User::where([['user_role','DELIVERY_BOY'],['is_active','!=',2]])->select('id','first_name','last_name','is_active','mobile','lat','lng')->get();
        //$active_boys_cnt = User::where([['user_role','DELIVERY_BOY'],['is_active',1]])->count();
        //$inactive_boys_cnt = User::where([['user_role','DELIVERY_BOY'],['is_active',0]])->count();

        foreach ($boys as $key => $boy) 
        {   

            $boy->delivered_orders = Order::where([['deliveryBoy',$boy->id],['status',4]])->whereDate('updatedOn', $search_date)->pluck('id');

            $boy->not_delivered_orders = Order::where([['deliveryBoy',$boy->id],['status',16]])->whereDate('updatedOn', $search_date)->pluck('id');
        
            $boy->accepted_orders = OrderAssignToDeliveryBoy::where([['boyId',$boy->id],['boyDecision',1]])->whereDate('updatedOn', $search_date)->pluck('orderId');

            $boy->rejected_orders = OrderAssignToDeliveryBoy::where([['boyId',$boy->id],['boyDecision',0]])->whereDate('updatedOn', $search_date)->pluck('orderId');

            $boy->waiting_for_boy_confirmation_orders = OrderAssignToDeliveryBoy::where([['boyId',$boy->id],['boyDecision',2]])->whereDate('updatedOn', $search_date)->pluck('orderId');

            $boy->ontheway_orders = Order::where('deliveryBoy',$boy->id)->whereDate('updatedOn', $search_date)->whereNotIn('status',[4,16])->pluck('id');

            //$boy->ontheway_orders = Order::where([['deliveryBoy',$boy->id],['updatedOn', 'like','%'.$search_date.'%']])->whereNotIn('status',[4,16])->pluck('id');
            
            $boy->expired_orders = [];                      
            //$boy->expired_orders = OrderAssignToDeliveryBoy::where([['boyId',$boy->id],['boyDecision',3],['updatedOn', 'like','%'.$search_date.'%']])->pluck('orderId');
           
        }

        return $this->sendResponse("deliveryBoys current orders status data..!",$boys);
    }

    public function add_primecustomer(Request $request)
    {   

        $validator = Validator::make($request->all(), ['mobile' => 'required','prime_category'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = User::where('mobile',$request->mobile)->first();
        if($user==null)
        {
            return $this->sendBadException('User not found',null);
        }


        if($request->prime_category<0)
        {
            return $this->sendBadException('Please select correct category',null);
        }

        if($request->prime_category>0)
        {
            if($request->months<1)
            {
                return $this->sendBadException('Please provide correct month field',null);
            }
        }

        $user->prime_customer = $request->prime_category;
        $user->save();

        if($request->prime_category!=0)
        {   
            $checking = PrimeCustomerData::where('user_id',$user->id)->update(['is_active'=>0]);

            $prime_data = new PrimeCustomerData();
            $prime_data->user_id = $user->id;
            $prime_data->createdOn = date('Y-m-d H:i:s');
            $prime_data->months = $request->months;
            $prime_data->category = $request->prime_category;

            $c_dt = strtotime(date('Y-m-d'));
            $exp_dt = date("Y-m-d", strtotime("+$request->months month", $c_dt))."\n";

            $prime_data->expiredOn = $exp_dt;
            $prime_data->is_active = 1;
            $prime_data->save();
        }else{
            $checking = PrimeCustomerData::where('user_id',$user->id)->update(['is_active'=>0]);
        }
        
        return $this->sendResponse("User prime package data updated successfully!",null);
    }

    /* payment methods apis*/
    public function getPaymentMethods(Request $request)
    {   

        $terms = TermsCondition::first();
       
        return $this->sendResponse("Payment Method details",array('paymentMode'=>$terms->paymentMethod));
    }

    public function editPaymentMethods(Request $request)
    {   

        $validator = Validator::make($request->all(), ['paymentMode' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $terms = TermsCondition::first();


        if($request->paymentMode<0)
        {
            return $this->sendBadException('paymentMode value is incorrect',null);
        }

        if($request->paymentMode>2)
        {
            return $this->sendBadException('paymentMode value is incorrect',null);
        }

        $terms->paymentMethod = $request->paymentMode;
        $terms->update();
        
        return $this->sendResponse("Payment Method details updated successfully!",null);
    }

}
