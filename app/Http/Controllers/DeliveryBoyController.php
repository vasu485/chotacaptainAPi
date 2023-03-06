<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Order;
use App\Models\OrderUpdates;
use App\Models\Status;
use App\Models\Item;
use App\Models\Notification;
use App\Models\SearchBoyForOrderAssignCronJob;
use App\Models\OrderAssignToDeliveryBoy;
use App\Models\UserLoginHistory;
use App\Models\OrderPickupFeedback;
use App\Models\FeedbackQuestion;
use App\Models\Sattlements;
use App\Models\Settings;
use App\Models\Wallet;
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
use App\Http\Controllers\FCMNotificationConroller;

class DeliveryBoyController extends Controller
{
    
    public function __construct()
    {
       $this->middleware('jwt.auth', ['except' => ['boyAcceptOrRejectOrder','check_version']]);
    }

    public function boyAcceptOrRejectOrder(Request $request)
    {   
        $validator = Validator::make($request->all(), ['orderId' => 'required','accept' => 'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised update.",'data' => null],401);
        }

        $check_order = Order::find($request->orderId);
        if($check_order==null){
            return response()->json(["status"=>"error","message"=>"Order not found",'data' => null],400);
        }
        if($check_order->deliveryBoy!=null){
            return response()->json(["status"=>"error","message"=>"Sorry, Someone is accepted this order.",'data' => null],200);
        }


        if($request->accept==true)
        {       

                $ongng_orders = Order::where([['deliveryBoy',$user->id],['createdOn', 'like','%'.date('Y-m-d').'%']])
                                       ->whereNotIn('status',[4,16])
                                       ->count();
                if($ongng_orders >= 2)
                {
                    return response()->json(["status"=>"error","message"=>"Sorry, Please deliver accepted orders first.",'data' => null],200);
                }
                  
                //check anyone accepted or not
                $checkassigned = OrderAssignToDeliveryBoy::where([['orderId',$request->orderId],['boyDecision',1]])->count();
                if($checkassigned > 0)
                {
                    return response()->json(["status"=>"error","message"=>"Sorry, Someone accepted this order.",'data' => null],200);
                }
                //ends

                //assign boy
                $userAssigned = OrderAssignToDeliveryBoy::where([['orderId',$request->orderId],['boyId',$user->id]])->first();
                $userAssigned->boyDecision = 1;
                $userAssigned->updatedOn = date('Y-m-d H:i:s');
                $userAssigned->update();
                //ends

                //order and history updated with new status
                $order = Order::find($request->orderId);
                $order->deliveryBoy = $user->id;
                $order->status = 12;
                $order->update();
                $checkOrderHistory = OrderUpdates::where([['orderId',$order->id],['statusId',12]])->count();
                if($checkOrderHistory==0)
                {   
                    $order_status = new OrderUpdates();
                    $order_status->orderId = $order->id;
                    $order_status->updatedBy = 'BOT';
                    $order_status->updatedById = 1;
                    $order_status->statusId = 12;
                    $order_status->status = 'Delivery Boy Assigned';
                    $order_status->createdOn = date('Y-m-d H:i:s');
                    $order_status->save();
                }
                //ends

                //$removeAssignment = OrderAssignToDeliveryBoy::where([['orderId',$request->orderId],['boyDecision', 2],['boyId','!=',$user->id]])->update(['boyDecision' => 3]);
                
                $removeAssignment = OrderAssignToDeliveryBoy::where([['orderId',$request->orderId],['boyDecision', 2],['boyId','!=',$user->id]])->get();
                if(count($removeAssignment)>0)
                {   
                    foreach ($removeAssignment as $ra) 
                    {
                       $fb = OrderAssignToDeliveryBoy::find($ra->id); 
                       $fb->boyDecision = 3;
                       $fb->update();
                    }
                }

        }
        else if($request->accept==false)
        {
                //assign boy
                $userAssigned = OrderAssignToDeliveryBoy::where([['orderId',$request->orderId],['boyId',$user->id]])->first();
                $userAssigned->boyDecision = 0;
                $userAssigned->updatedOn = date('Y-m-d H:i:s');
                $userAssigned->update();
                //ends
        }

        $mssg = ($request->accept==true)?"accepted":"rejected";
        return $this->sendResponse("Order $mssg Successfully...!",null);

    }

       /*
    * Save user online status
    */
    public function saveBoyActiveStatus(Request $request)
    {
        $validator = Validator::make($request->all(), ['is_active' => 'required|numeric']);
        if($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $status = array(0,1); //Here 0 = offline, 1 = online, 2 = away
        if(!in_array($request->is_active, $status)){
            return $this->sendBadException('Online status not valid',null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        // the token is valid and we have found the user via the sub claim
        $checkUser = User::find($user->id);
        if($checkUser == null){
            return $this->sendBadException('User Not Found',null);
        }
        $checkUser->is_active = $request->is_active;
        $checkUser->update();

        //insert in login history table
        if($request->is_active==1 && $checkUser->loginHistoryId==null)
        {
            $his = new UserLoginHistory();
            $his->userId = $checkUser->id;
            $his->loginTime = date('Y-m-d H:i:s');
            $his->live_status = $request->is_active;
            $his->createdOn = date('Y-m-d');
            $his->save();

            $checkUser->loginHistoryId = $his->id;
            $checkUser->update();

        }else if($request->is_active==1 && $checkUser->loginHistoryId!=null)
        {
            $his = UserLoginHistory::find($checkUser->loginHistoryId);
            $his->loginTime = date('Y-m-d H:i:s');
            $his->live_status = $request->is_active;
            $his->save();

            $checkUser->loginHistoryId = $his->id;
            $checkUser->update();

        }else if($request->is_active==0 && $checkUser->loginHistoryId!=null)
        {
            $his = UserLoginHistory::find($checkUser->loginHistoryId);
            $logoutTime = date('Y-m-d H:i:s');
            //calculating time difference
            $datetime1 = new \DateTime($his->loginTime);//start time
            $datetime2 = new \DateTime($logoutTime);//end time
            $interval = $datetime1->diff($datetime2);
            $diff = $interval->format('%H:%I:%s');//00 years 0 months 0 days 08 hours 0 minutes 0 seconds

            $his->logoutTime = $logoutTime;
            $his->loginHours = $diff;
            $his->live_status = $request->is_active;
            $his->update();

            $checkUser->loginHistoryId = null;
            $checkUser->update();
        }else if($request->is_active==0 && $checkUser->loginHistoryId==null)
        {
            return $this->sendResponse("Sorry, you already LoggedOut",null);
        }
        
        
        //ends

        return $this->sendResponse("User online status updated successfully",null);
    }

    public function getBoyOrders(Request $request)
    {

        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric','is_active'=>'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $orders = Order::with('vendor','location','items')->where('deliveryBoy',$user->id);

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

        if($request->is_active==true){
           $orders = $orders->whereNotIn('status',[3,4,11,16]); //live
        }else if($request->is_active==false){
           $orders = $orders = $orders->whereIn('status',[3,4,11,16]); //past
        }

        $orders = $orders->orderBy('createdOn','desc')->offset($offset)->limit($limit)->get();

        foreach ($orders as $or) {
            if($or->deliveryBoy!=null){
                $or->deliveryBoy = User::find($or->deliveryBoy);
                $or->order_by = User::where('id',$or->orderBy)->select(['id', 'first_name','last_name', 'mobile'])->first();
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
            }
            $status = Status::find($or->status);
            $or->order_status = array("id"=>$status->id,"name"=>$status->name);
            unset($or->items_data);
            unset($or->status);
            foreach ($or->items as $item) {
                $my_item = Item::where('id',$item->itemId)->select('id','name','price','rating','type','price_quantity','image')->first();
                unset($item->orderId);
                unset($item->id);
                unset($item->createdOn);
                unset($item->itemId);
                $my_item->price_quantity = json_decode($my_item->price_quantity);
                $my_item->image = ($my_item->image!=null)?request()->getSchemeAndHttpHost().'/images/items/'.$my_item->image:null;
                $item->item = $my_item;
            }
        }

        return $this->sendResponse("boy orders list...!",$orders);
    }

    public function boyNotifications($version=null)
    {   
        
        /*if($request->header('version')==null || $request->header('version')!='3.0'){
            return response()->json(["status"=>"error","message"=>"Sorry, Please install latest APK",'data' => null],400);
        }*/

        if($version==null || $version!='0.3'){
            return response()->json(["status"=>"error","message"=>"Sorry, Please install latest APK",'data' => null],400);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $notification = OrderAssignToDeliveryBoy::where([['boyId',$user->id],['boyDecision',2]])->get();

        if(count($notification) > 0 )
        {   
            foreach ($notification as $n_v) {
                $ordr = Order::with('vendor','location','items')->where('id',$n_v->orderId)->first();
                
                $cat_sub_category = null;
                if($ordr->categoryId!=null)
                {   
                    $c = Category::find($ordr->categoryId);
                    $cat_sub_category = array('categoryId'=>$c->id,"categoryName"=>$c->name);
                }
                if($ordr->subCategoryId!=null)
                {   
                    $sc = SubCategory::find($ordr->subCategoryId);
                    $cat_sub_category['subCategoryId'] = $sc->id;
                    $cat_sub_category['subCategoryName'] = $sc->name;
                }
                $ordr->cat_sub_category = $cat_sub_category;
                $n_v->order = $ordr;
                
                unset($n_v->orderId);
                unset($n_v->cronjobId);
                $n_v->boyDecision = "Waiting for Confirmation";
            }  
        }

        return $this->sendResponse("boy notifications list...!",$notification);
    }

    public function getOrderFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), ['orderId' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $questions = FeedbackQuestion::select('id as qId','question')->get();
        foreach ($questions as $q) {
                $fb = OrderPickupFeedback::where([['orderId',$request->orderId],['boyId',$user->id],['qId',$q->qId]])->first();
                if($fb==null)
                {
                    $q->feedback = null;
                    $q->createdOn = null;
                }else{
                    $q->feedback = ($fb->feedback==1)?true:false;
                    $q->createdOn = $fb->createdOn;
                }
        }

        return $this->sendResponse("getOrderFeedback...!",$questions);
    }

    public function updateOrderFeedback(Request $request)
    {
        $validator = Validator::make($request->all(), ['orderId' => 'required|numeric','questions' => 'required|Array']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        
        if(count($request->questions)>0)
        {
            foreach ($request->questions as $q) 
            {   
                $feedback = OrderPickupFeedback::where([['orderId',$request->orderId],['boyId',$user->id],['qId',$q['qId']]])->first();
                if($feedback==null)
                {
                    $fb = new OrderPickupFeedback();
                    $fb->qId = $q['qId'];
                    $fb->feedback = $q['feedback'];
                    $fb->orderId = $request->orderId;
                    $fb->boyId = $user->id;
                    $fb->createdOn = date('Y-m-d H:i:s');
                    $fb->save();
                }else{
                    $fb = OrderPickupFeedback::find($feedback->id);
                    $fb->qId = $q['qId'];
                    $fb->feedback = $q['feedback'];
                    $fb->orderId = $request->orderId;
                    $fb->boyId = $user->id;
                    $fb->createdOn = date('Y-m-d H:i:s');
                    $fb->update();
                }

                
            }
        }else{
            return response()->json(["status"=>"error","message"=>"Questions array missing",'data' => null],400);
        }
        

        return $this->sendResponse("OrderFeedback Updated Successfully...!",null);
    }

    
    public function check_version(Request $request)
    {   

        $validator = Validator::make($request->all(), ['version' => 'required','app_type'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        if($request->app_type=='deliveryBoy' && $request->version=='2.0')
        {
            $data['status'] = true;
        }else if($request->app_type=='user' && $request->version=='5.5')
        {
            $data['status'] = true;
        }else{
            $data['status'] = false;
        }

        return $this->sendResponse("App Status...!",$data);
    }


    public function boyLoginHistory(Request $request)
    {   

        $validator = Validator::make($request->all(), ['date' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }


        $history = UserLoginHistory::where([['userId',$user->id],['createdOn',$request->date]])->get();
        $logout = array();
        $login = array();
        foreach ($history as $time) {
            if($time->live_status=='0')
            {
                array_push($logout, $time->loginHours);
            }
            if($time->live_status=='1')
            {
                array_push($login, $time->loginTime);
            }
        }

        $calculateTime =  $this->findTotalTime($logout);

        $data['is_active'] = $user->is_active;
        $data['totalLoginHours'] = $calculateTime;
        $data['loginHistory'] = $history;

        return $this->sendResponse("Login history...!",$data);
    }

    public function getWallet(Request $request,$id)
    {   
        $user = JWTAuth::parseToken()->authenticate();
        $user = User::find($id);
        if($user->user_role=='USER' || $user->user_role=='VENDOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $wallet = Wallet::where([['is_active',1],['user_id','=',$user->id]])->first();

        return $this->sendResponse("Boy Wallet...!",$wallet);
    }

    public function createWallet(Request $request)
    {   

        $validator = Validator::make($request->all(), ['amount' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }


        $wallet = Wallet::where([['user_id',$user->id],['is_active',1]])->first();

        if($wallet == null){
           $w = new Wallet();
           $w->user_id = $user->id;
           $w->amount = trim($request->amount);
           $w->createdOn = date('Y-m-d H:i:s');
           $w->status = 'CREATED';
           $w->is_active = 1;
           $w->save();
        }else{
            $w = Wallet::find($wallet->id);
            $w->user_id = $user->id;
            $w->amount = $w->amount + trim($request->amount);
            $w->updatedOn = date('Y-m-d H:i:s');
            //$w->status = 'CREATED';
            //$w->is_active = 1;
            $w->update();
        }

        return $this->sendResponse("Amount moved to Wallet Successfully...!",null);
    }

    public function WalletRequestForApproval(Request $request)
    {   

        $validator = Validator::make($request->all(), ['walletId' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }


        $wallet = Wallet::where([['id',$request->walletId],['is_active',1]])->first();

        if($wallet == null){
            return $this->sendBadException('Wallet Not Found',null);
        }else{
            $w = Wallet::find($wallet->id);
            $w->status = 'PAID_REQUEST';
            $w->status_updated_by = $user->id;
            $w->updatedOn = date('Y-m-d H:i:s');
            $w->update();
        }

        return $this->sendResponse("Wallet Paid Successfully...!",null);
    }

    public function getWalletPaidRequestForAdminApproval(Request $request)
    {   
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER' || $user->user_role=='VENDOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $wallet = Wallet::with('boy')->where([['is_active',1],['status','=','PAID_REQUEST']])->get();

        foreach($wallet as $w)
        {
            unset($w->user_id);
        }

        return $this->sendResponse("Boys Wallet paid requests...!",$wallet);
    }

    public function approveWallet(Request $request)
    {   

        $validator = Validator::make($request->all(), ['walletId' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $user = JWTAuth::parseToken()->authenticate();
        /*if($user->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }*/


        $wallet = Wallet::where([['id',$request->walletId],['is_active',1]])->first();


        if($wallet == null){
            return $this->sendBadException('Wallet Not Found',null);
        }else{
            $w = Wallet::find($wallet->id);
            $w->status = 'APPROVED';
            $w->status_updated_by = $user->id;
            $w->updatedOn = date('Y-m-d H:i:s');
            $w->is_active = 0;
            $w->update();
        }

        return $this->sendResponse("Wallet Amount Approved Successfully...!",null);
    }



  /*----------re usable private functions--------------*/

    private function findTotalTime($logout)
    {
                // Declare an array containing times
        $arr = $logout;
          
        $total = 0;
          
        // Loop the data items
        foreach( $arr as $element):
              
            // Explode by seperator :
            $temp = explode(":", $element);
              
            // Convert the hours into seconds
            // and add to total
            $total+= (int) $temp[0] * 3600;
              
            // Convert the minutes to seconds
            // and add to total
            $total+= (int) $temp[1] * 60;
              
            // Add the seconds to total
            $total+= (int) $temp[2];
        endforeach;
          
        // Format the seconds back into HH:MM:SS
        $formatted = sprintf('%02d:%02d:%02d', 
                        ($total / 3600),
                        ($total / 60 % 60),
                        $total % 60);
          
        return $formatted; 
    }


    public function getBoyOrderSettlements(Request $request)
    {
        //validation start
        $validator = Validator::make($request->all(), ['dates' => 'required|Array']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $boy = JWTAuth::parseToken()->authenticate();
        if($boy->user_role!='DELIVERY_BOY'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        if( count($request->dates)==2 )
        {   
            $from = $request->dates[0]." 00:00:00";
            $to = $request->dates[1]." 23:59:59";
            //$orders = $orders->whereIn('status',[4])->whereBetween('updatedOn', [$from, $to]);

        }else{
            $from = $request->dates[0]." 00:00:00";
            $to = $request->dates[0]." 23:59:59";
            //$orders = $orders->whereIn('status',[4])->whereBetween('updatedOn', [$from, $to]);
        }

        $orders = Order::with('orderBy','status','location','vendor')->where([['deliveryBoy',$boy->id]])->whereIn('status',[4,16])->whereBetween('updatedOn', [$from, $to])->orderBy('createdOn','desc')->get();
        
        $cashPayment = Order::where([['deliveryBoy',$boy->id],['paymentMode','CASH']])->whereIn('status',[4,16])->whereBetween('updatedOn', [$from, $to])->sum('finalPrice');

        $onlinePayment = Order::where([['deliveryBoy',$boy->id],['paymentMode','ONLINE']])->whereIn('status',[4,16])->whereBetween('updatedOn', [$from, $to])->sum('finalPrice');

        if(count($orders)==0){
            return $this->sendResponse('Sorry, No Orders on this Date for this Delivery Boy',null);
        }

        $apnachotuTotalPrice = array(); 
        $vendorSattlementPrice = array(); 
        $apnachotuDeliveryPrice = array(); 
        $apnachotuTotalDiscountPrice = array(); 
        $tip = array(); 

        foreach ($orders as $v) 
        {
            array_push($apnachotuTotalPrice, $v->finalPrice);
            array_push($vendorSattlementPrice, $v->itemsPrice);
            array_push($apnachotuDeliveryPrice, $v->deliveryFee);
            array_push($apnachotuTotalDiscountPrice, $v->discountPrice);
            array_push($tip, $v->tip);
        }

        //minus total mount with not delivered orders amount
        $apnachotuTotalPrice = array_sum($apnachotuTotalPrice);// - $not_del_f_amount;
        $apnachotuDeliveryPrice = array_sum($apnachotuDeliveryPrice);// - $not_del_fee; 
        //ends

        //$getCommision = $this->calculateBoyCommision($from,$to,$boy->id);
        $boySettlementAmount = $this->calculateBoyPaymentBasedOnOrderCount($from,$to,$boy->id);
        $boyBikePertolAmount = (count($orders)>=10)?100:0;

        $settlements = array("vendorSettlementPrice"=> array_sum($vendorSattlementPrice),
                             "TotalDiscountPrice"=> array_sum($apnachotuTotalDiscountPrice),
                             "TotalOrdersAmount"=> $apnachotuTotalPrice,
                             "onlineOrdersAmount"=> $onlinePayment,
                             "cashOrdersAmount"=> $cashPayment,
                             "totalOrdersDeliveryFee"=> $apnachotuDeliveryPrice,
                             "totalTip"=> array_sum($tip),
                             "totalBoyCommision"=> null,//$getCommision
                             "boyOrdersSettlementAmount"=>$boySettlementAmount,
                             "boyBikePertolAmount"=>$boyBikePertolAmount,
                             "boyFinalSettlementAmount"=>$boySettlementAmount+$boyBikePertolAmount);

        $client_stlmnt_json = array("TotalOrdersAmount"=> $apnachotuTotalPrice,
                                    "onlineOrdersAmount"=> $onlinePayment,
                                    "cashOrdersAmount"=> $cashPayment,
                                    "totalOrdersDeliveryFee"=> $apnachotuDeliveryPrice,
                                    "totalTip"=> array_sum($tip),
                                    "totalBoyCommision"=> null,//$getCommision
                                    "boyOrdersSettlementAmount"=>$boySettlementAmount,
                                    "boyBikePertolAmount"=>$boyBikePertolAmount,
                                    "boyFinalSettlementAmount"=>$boySettlementAmount+$boyBikePertolAmount);


        //counts fetching
        $del_count= Order::where('deliveryBoy',$boy->id)->where('status',4)->whereBetween('updatedOn', [$from, $to])->count();
        $not_del_count= Order::where('deliveryBoy',$boy->id)->where('status',16)->whereBetween('updatedOn', [$from, $to])->count();

        $on_going= Order::where('deliveryBoy',$boy->id)->whereNotIn('status',[4,16])->whereBetween('updatedOn', [$from, $to])->count();
        
        $accept_orders = OrderAssignToDeliveryBoy::where([['boyId',$boy->id],['boyDecision',1]])->whereBetween('updatedOn', [$from, $to])->count();
        $reject_orders = OrderAssignToDeliveryBoy::where([['boyId',$boy->id],['boyDecision',0]])->whereBetween('updatedOn', [$from, $to])->count();

        $total_count = $del_count + $not_del_count + $reject_orders + $on_going;
        //ends

        //arranging data for output
        $dates = $from.' to '.$to;
        $res['dates'] = $dates;
        //$res['boy'] = $boy;
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
           // $res['settlementStatus'] = 'PENDING';
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
                //$res['settlementStatus'] = $add_satlements->status;
                $res['settlementId'] = $add_satlements->id;
                $res['paymentMode'] = null;
            }else{
                //$res['settlementStatus'] = $add_satlements->status;
                $res['settlementId'] = $add_satlements->id;
                $res['paymentMode'] = $add_satlements->paymentMode;
            }
            
        }

        $res['orderCounts'] = array("total"=>$total_count,"delivered"=>$del_count,"not_delivered"=>$not_del_count,'on_going'=>$on_going,"accepted"=> $accept_orders,"declined"=>$reject_orders);
        $res['settlements'] = $client_stlmnt_json;
        //ends
        return $this->sendResponse("deliveryBoy Settlement data..!",$res);
    }

    private function calculateBoyCommision($from,$to,$boyId)
    {   
        $newarray = [];$commison = [];
        $period = new \DatePeriod(new \DateTime($from),new \DateInterval('P1D'),new \DateTime($to));
        foreach ($period as $date) 
        {
            //$alldates[] = $date->format("Y-m-d");
            $d = $date->format("Y-m-d");
            $from_d = $d." 00:00:00";
            $to_d = $d." 23:59:59";
            $orderCount = Order::where('deliveryBoy',$boyId)->whereIn('status',[4,16])->whereBetween('updatedOn', [$from_d, $to_d])->count();


            $s_c = Settings::where('createdOn',$d)->count();
            if($s_c>0)
            {   
                $setting = Settings::where('createdOn',$d)->first();
                $commisionOrders = ($orderCount>$setting->min_orders_for_commision)?($orderCount-$setting->min_orders_for_commision):0;
                $c_amount = $commisionOrders*$setting->commision_per_order;
                //$min_orders_for_commision = $setting->min_orders_for_commision;
            }else
            {
                $c_amount = $commisionOrders*0;
                $commisionOrders = 0;
            }

            array_push($commison, $c_amount);

            $newarray[] = ['date'=>$d,'TotalOrders'=>$orderCount,'commisionOrders'=>$commisionOrders,'commisionAmount'=>$c_amount];
        }
        return array("TotalCommision"=>array_sum($commison),"commisionList"=>$newarray);
    }

}