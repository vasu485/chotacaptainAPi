<?php

namespace App\Http\Controllers;
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
use App\Models\Item;
use App\Models\Notification;
use App\Models\SearchBoyForOrderAssignCronJob;
use App\Models\OrderAssignToDeliveryBoy;
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
use Razorpay\Api\Api;
use App\Http\Controllers\FCMNotificationConroller;

class OrderController extends Controller
{
    
    public function __construct()
    {
       $this->middleware('jwt.auth', ['except' => ['misOrderCreate']]);
    }

    public function getStatus()
    {
        $user = JWTAuth::parseToken()->authenticate(); //'VENDOR', 'DELIVERY_BOY', 'USER', 'ADMIN')

        if($user->user_role=='VENDOR'){
            $status = Status::find([2,3,6,8,11]);
        }else if($user->user_role=='DELIVERY_BOY'){
            $status = Status::find([5,4,7,14,15,16]);
        }else if($user->user_role=='ADMIN'){
            $status = Status::all();
        }else{
            return $this->sendBadException("Sorry, you are not authorised to view...!",null);
        }
        return $this->sendResponse("Status list...!",$status);
    }

    public function createOrder(Request $request)
    {
		 //start validations
        $validator = Validator::make($request->all(), ['vendorId' => 'required|numeric','finalPrice'=>'required','itemsPrice'=>'required','deliveryFee'=>'required','paymentMode'=>'required','address'=>'required|string','items'=>'required|Array','locationId'=>'required','discountPrice'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->is_active!=1){
            return $this->sendBadException('Sorry, Your status is in-active, we are unable to create your order!, Please contact administrator',null);
        }

        $orderById = $user->id;
        $createdByAdmin = false;

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }
        $vendorId = $v->id;

        $loc = Location::find($request->locationId);
        if($loc==null){
            return $this->sendBadException('Location not found',null);
        }

        $order = new Order();

        if(isset($request->newUser) && $request->newUser!=null)
        {   
            $checkUsr = User::where('mobile',$request->newUser['mobile'])->first();
            if($checkUsr=="" || $checkUsr==null)
            {   
                $newUser1 = new User();
                $newUser1->first_name      = trim($request->newUser['first_name']);
                $newUser1->last_name      = trim($request->newUser['last_name']);
                $newUser1->user_role  = 'user';
                $newUser1->mobile  = trim($request->newUser['mobile']);
                $newUser1->is_active = 1;
                $newUser1->createdByAdmin = true;
                $newUser1->createdOn = date('Y-m-d H:i:s');
                $newUser1->save();
                $orderById = $newUser1->id;
            }else{
                $orderById = $checkUsr->id;
                $createdByAdmin = true;
            }
        }

        $orderByUser = User::find($orderById);

        $order->vendorId      = trim($vendorId);
        $order->apnachotu_commision = $v->percentage_to_chotu;
        $order->orderBy      = trim($orderById);
        $order->is_active = true;
        $order->status = 1; //ordered
        $order->type = "normal";
        $order->categoryId = $v->categoryId;

        //is free delivery or not
        //$myfile = fopen("api_logs.txt", "a") or die("Unable to open file!");
        //fwrite($myfile, "\n". json_encode($request));
        //fwrite($myfile, "\n". 'is_free_delivery-'.$v->is_free_delivery);

        $order->is_free_delivery = ($v->is_free_delivery==1)?true:false;
        //$deliveryFee = ($v->is_free_delivery==1)?0:trim($request->deliveryFee);

        if($v->is_free_delivery==1)
        {
            $deliveryFee = 0;
        }
        else if($orderByUser->prime_customer==2)
        {
            $deliveryFee = 0;
        }
        else if($orderByUser->prime_customer==1 && $v->categoryId==1)
        {
            $deliveryFee = 0;
        }else
        {
            $deliveryFee = trim($request->deliveryFee);
        }

        //fwrite($myfile, "\n". 'deliveryf1-'.$deliveryFee);
        //$deliveryFee = ($user->prime_customer==1)?0:$deliveryFee; //checking prime_customer or not
        //fwrite($myfile, "\n". 'deliveryf2-'.$deliveryFee);
        $order->deliveryFee = $deliveryFee;
        $order->discountPrice = trim($request->discountPrice);
        $order->itemsPrice = trim($request->itemsPrice);
        //$order->finalPrice =  trim($request->finalPrice);
        $finalPrice = (float)$request->itemsPrice+(float)$deliveryFee-(float)$request->discountPrice;
        if(isset($request->tip) && $request->tip!=null)
        {
            $tip = trim($request->tip);
            $finalPrice = $finalPrice + (float)$tip;
            $order->tip = $tip;
        }
        
        //total tax amount cgst+sgst+rozarpay tax
        if(isset($request->tax) && $request->tax!=null)
        {
            $tax = trim($request->tax);
            $finalPrice = $finalPrice + (float)$tax;
            $order->tax = $tax;
        }

        $order->finalPrice = $finalPrice;

        if(isset($request->taxPercentage) && $request->taxPercentage!=null)
        {
            $order->taxPercentage = trim($request->taxPercentage);
        }

        if(isset($request->serviceTax) && $request->serviceTax!=null)
        {
            $order->serviceTax = trim($request->serviceTax);
        }

        if(isset($request->gstTax) && $request->gstTax!=null)
        {
            $order->gstTax = trim($request->gstTax);
        }

        if(isset($request->originalPrice) && $request->originalPrice!=null)
        {
            $order->originalPrice = trim($request->originalPrice);
        }
        if(isset($request->updatedPrice) && $request->updatedPrice!=null)
        {
            $order->updatedPrice = trim($request->updatedPrice);
        }

        $order->locationId = trim($request->locationId);
        $order->partnerid=$v->locationid;
        $order->paymentMode = trim($request->paymentMode);
        $order->address = trim($request->address);
        $order->lat = ($request->lat!=null)?$request->lat:null;
        $order->lng = ($request->lng!=null)?$request->lng:null;
        $order->createdOn = date('Y-m-d H:i:s');
        $order->createdByAdmin = $createdByAdmin;
        $order->delivery_time = $v->delivery_time;

        if(isset($request->alt_mobile) && $request->alt_mobile!=null)
        {
            $order->alt_mobile = trim($request->alt_mobile);
        }

        if(isset($request->tip) && $request->tip!=null)
        {
            $order->tip = trim($request->tip);
        }

        if(isset($request->payment_id) && $request->payment_id!=null)
        {
            $order->payment_id = trim($request->payment_id);
        }

        if(isset($request->extra_items) && $request->extra_items!=null)
        {
            $order->extra_items = trim($request->extra_items);
        }

        $order->order_json = json_encode($request->all());

        $order->save();

        $order_status = new OrderUpdates();
        $order_status->orderId = $order->id;
        $order_status->updatedBy = $user->user_role;
        $order_status->updatedById = $user->id;
        $order_status->statusId = 1;
        $order_status->status = 'Order Placed';
        $order_status->createdOn = date('Y-m-d H:i:s');
        $order_status->save();

        foreach ($request->items as $value) 
        {
            $item = new OrderItems();
            $item->orderId = $order->id;
            $item->itemId = $value['itemId'];
            $item->quantity = $value['quantity'];
            $item->price = (float)$value['price'];
            $item->createdOn = date('Y-m-d H:i:s');
            $item->save();
        }

        if($v->mobile!=null)
        {
            $notifyCtrl = new FCMNotificationController();
            $notifyCtrl->sendFCMNotification($user,$v,'NEW_ORDER',$order->id);
        }

        //user notifications
        $notifyCtrl = new FCMNotificationController();
        $notifyCtrl->sendFCMNotification($v,$user,'ORDER_PLACED',$order->id);

        return $this->sendResponse("Order Created Successfully...!",null);
    }

    public function updateOrder(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['id' => 'required|numeric','status'=>'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update.",'data' => null],401);
        }

        $order = Order::find($request->id);
        if($order==null){
            return $this->sendBadException('Order not found',null);
        }

        $s = Status::find($request->status);
        if($s==null){
            return $this->sendBadException('Status not found',null);
        }

        if($order->status==3)
        {
            return response()->json(["status"=>"error","message"=>"Sorry, This order is already Canceled.",'data' => null],400);
        }
        if($order->status==11)
        {
            return response()->json(["status"=>"error","message"=>"Sorry, This order is already updated with Out of stack.",'data' => null],400);
        }

        if($order->deliveryBoy != null && $s->id == 12)
        {
            return response()->json(["status"=>"error","message"=>"Sorry, Delivery boy is already assigned to this order.",'data' => null],400);
        }


        $order->status = $s->id;
        if(isset($request->deliveryBoy) && $request->deliveryBoy!=null){
            $order->deliveryBoy = trim($request->deliveryBoy);
        }

        if(isset($request->delivery_time) && $request->delivery_time!=null){
            $order->delivery_time = trim($request->delivery_time);
        }

        if(isset($request->cancel_reason) && $request->cancel_reason!=null){
            $order->cancel_reason = trim($request->cancel_reason);
        }

        if(isset($request->payment_id) && $request->payment_id!=null)
        {
            $order->payment_id = trim($request->payment_id);
        }


        //refund amount
        if($s->id==3)
        {
            if($order->payment_id!=null)
            {
                $razorpay = $this->RazorPay($order->payment_id);
                //return $razorpay;
                if($razorpay!='refunded')
                {
                    return $this->sendBadException('Something went wrong, Please try again later!',null);
                }
            }
        }
        //end

        $order->updatedOn = date('Y-m-d H:i:s');

        if($s->id==4){
            $order->is_active = false;
        }

        if(isset($request->additional_charge) && $request->additional_charge!=null)
        {
            /*if((int)$request->additional_charge >= 0)
            {
                $deliveryFee = (int)$order->deliveryFee+(int)$request->additional_charge;
                return "+".$deliveryFee;
            }else{
                $deliveryFee = (int)$order->deliveryFee-(int)$request->additional_charge;
                return "-".$deliveryFee;
            }*/
            $deliveryFee = (int)$order->deliveryFee+(int)$request->additional_charge;
            //return $deliveryFee;
            $order->deliveryFee = $deliveryFee;
        }else{
            $deliveryFee = $order->deliveryFee;
        }
        

        //add price to mis orders
        if($request->itemsPrice!=null)
        {
            $order->itemsPrice = $request->itemsPrice;
            $finalPrice = (int)$request->itemsPrice+(int)$deliveryFee-(int)$order->discountPrice;
        
            if(isset($request->tax) && $request->tax!=null)
            {
                $tax = trim($request->tax);
                $finalPrice = $finalPrice + (float)$tax;
                $order->tax = $tax;
            }
            if(isset($request->tip) && $request->tip!=null)
            {
                $tip = trim($request->tip);
                $finalPrice = $finalPrice + (float)$tip;
                $order->tip = $tip;
            }

            $order->finalPrice = $finalPrice;
        }

        if(isset($request->tip) && $request->tip!=null)
        {
            $tip = trim($request->tip);
            $finalPrice = $order->finalPrice + (float)$tip;
            $order->tip = $tip;
            $order->finalPrice = $finalPrice;
        }

        if(isset($request->taxPercentage) && $request->taxPercentage!=null)
        {
            $order->taxPercentage = trim($request->taxPercentage);
        }


        if($s->id==9) //payment updated
        {
            $checkStatus = 0;
        }else{
            $checkStatus = OrderUpdates::where([['orderId',$order->id],['statusId',$s->id]])->count();
        }
        //$checkStatus = OrderUpdates::where([['orderId',$order->id],['statusId',$s->id]])->count();
        if($checkStatus==0)
        {   
            $order_status = new OrderUpdates();
            $order_status->orderId = $order->id;
            $order_status->updatedBy = $user->user_role;
            $order_status->updatedById = $user->id;
            $order_status->statusId = $s->id;
            $order_status->status = $s->name;
            $order_status->createdOn = date('Y-m-d H:i:s');
            $order_status->save();
        }else{
            return response()->json(["status"=>"error","message"=>"Sorry, This status is already updated to this order.",'data' => null],400);
        }
    

        //assigning boy
        if(isset($request->deliveryBoy) && $request->deliveryBoy!=null && $s->id == 12)
        {
            $userAssigned = OrderAssignToDeliveryBoy::where([['orderId',$order->id],['boyId',$request->deliveryBoy]])->first();
            if($userAssigned==null)
            {
                $userAssigned1 = new OrderAssignToDeliveryBoy();
                $userAssigned1->orderId = $order->id;
                $userAssigned1->boyId = $request->deliveryBoy;
                $userAssigned1->boyDecision = 1;
                $userAssigned1->assignedBy = 'ADMIN';
                $userAssigned1->updatedOn = date('Y-m-d H:i:s');
                $userAssigned1->save();
            }else{
                $userAssigned->boyDecision = 1;
                $userAssigned->updatedOn = date('Y-m-d H:i:s');
                $userAssigned->update();
            }
            //$removeAssignment = OrderAssignToDeliveryBoy::where([['orderId',$order->id],['boyDecision', 2],['boyId','!=',$request->deliveryBoy]])->update(['boyDecision' => 3]);
            $removeAssignment = OrderAssignToDeliveryBoy::where([['orderId',$order->id],['boyDecision', 2],['boyId','!=',$request->deliveryBoy]])->get();
            if(count($removeAssignment)>0)
            {   
                foreach ($removeAssignment as $ra) 
                {
                   $fb = OrderAssignToDeliveryBoy::find($ra->id); 
                   $fb->boyDecision = 3;
                   $fb->update();
                }
            }

            $cron_del = SearchBoyForOrderAssignCronJob::where('orderId',$order->id)->first();
            if($cron_del != null)
            {
                $cron_del->searching_status = 2;
                $cron_del->update();
            }

            if($order->vendorId == '0')
            {
                $v = null;
            }else{
                $v = Vendor::find($order->vendorId);
            }
            $find_user = User::find($request->deliveryBoy);
            $notifyCtrl = new FCMNotificationController();
            $notifyCtrl->sendFCMNotification($v,$find_user,'ACCEPT_ORDER_DELIVERY',$order->id);
        }

        $order->update();
        

        //if order prepared from vendor (8) send notifications
        if($s->id==8 && $order->type=='normal')
        {
           $checking = SearchBoyForOrderAssignCronJob::where('orderId',$order->id)->first();
           if($checking==null)
           {
                $cronjob = new SearchBoyForOrderAssignCronJob();
                $cronjob->orderId =  $order->id;
                $cronjob->vendorId =  $order->vendorId;
                $cronjob->type =  $order->type;
                $cronjob->searching_status =  0;
                $cronjob->save();
           }
        }else if($s->id==2 && $order->type!='normal')
        {
           $checking = SearchBoyForOrderAssignCronJob::where('orderId',$order->id)->first();
           if($checking==null)
           {
                $cronjob = new SearchBoyForOrderAssignCronJob();
                $cronjob->orderId =  $order->id;
                $cronjob->vendorId =  $order->vendorId;
                $cronjob->type =  $order->type;
                $cronjob->searching_status =  0;
                $cronjob->save();
           }
        }

        //sending notifications to user
            if($order->vendorId == '0')
            {
                $v = null;
            }else{
                $v = Vendor::find($order->vendorId);
            }

            $find_user = User::find($order->orderBy);

            if($s->id=='2')
            {
                $msg = 'Confirmed';
            }else if($s->id=='3')
            {
                $msg = 'Cancelled';
            }
            else if($s->id=='4')
            {
                $msg = 'Delivered';
            }
            else if($s->id=='5')
            {
                $msg = 'On_the_way';
            }
            else if($s->id=='6')
            {
                $msg = 'Cooking';
            }
            else if($s->id=='7')
            {
                $msg = 'Payment_Received';
            }
            else if($s->id=='8')
            {
                $msg = 'Order_Prepared';
            }else if($s->id=='9')
            {
                $msg = 'items_Final_Price_Added';
            }else if($s->id=='10')
            {
                $msg = 'Order_Placed_Waiting_for_Final_Price';
            }else if($s->id=='11')
            {
                $msg = 'Out_Of_Stock';
            }else if($s->id=='12')
            {
                $msg = 'Delivery_Boy_Assigned';
            }else if($s->id=='13')
            {
                $msg = 'Searching_Boy';
            }else if($s->id=='14')
            {
                $msg = 'Order_PickedUp';
            }else if($s->id=='15')
            {
                $msg = 'Order_Arrived';
            }else{
                $msg = "";
            }

            $notifyCtrl = new FCMNotificationController();
            $notifyCtrl->sendFCMNotification($v,$find_user,$msg,$order->id);
        //ends

        return $this->sendResponse("Order Updated Successfully...!",null);
    }

    public function updateOrderPaymentFromUser(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['id' => 'required|numeric','payment_id'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        
        /*if($user->user_role!='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update payment.",'data' => null],401);
        }*/

        $order = Order::find($request->id);
        if($order==null){
            return $this->sendBadException('Order not found',null);
        }

        if($order->orderBy!=$user->id){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update this order payment.",'data' => null],401);
        }

        if(isset($request->payment_id) && $request->payment_id!=null)
        {
            $order->payment_id = trim($request->payment_id);
            $order->paymentMode = 'ONLINE';
        }

        $order->updatedOn = date('Y-m-d H:i:s');
        $order->update();

        return $this->sendResponse("Order Payment Updated Successfully...!",null);
    }

    public function cancelOrder(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['orderId' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        
        $order = Order::find($request->orderId);
        if($order==null){
            return $this->sendBadException('Order not found',null);
        }

        if($order->status==4){
            return $this->sendBadException('Sorry, Unable to cancel. Its already delivered !',null);
        }

        if($order->status==3){
            return $this->sendBadException('Sorry, this order is already Cancelled !',null);
        }

        $s = Status::find(3); //cancel id
        if($s==null){
            return $this->sendBadException('Status not found',null);
        }

        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR')
        {
            if($order->orderBy!=$user->id)
            { 
                return $this->sendBadException('Sorry, You are not authorized to cancel this Order',null);
            }
        }

        if($order->payment_id!=null)
        {
            $razorpay = $this->RazorPay($order->payment_id);
            //return $razorpay;
            if($razorpay!='refunded')
            {
                return $this->sendBadException('Something went wrong, Please try again later!',null);
            }
        }
        
        $order->status = $s->id;
        $order->updatedOn = date('Y-m-d H:i:s');
        $order->is_active = false;;
        if(isset($request->cancel_reason) && $request->cancel_reason!=null){
            $order->cancel_reason = trim($request->cancel_reason);
        }
        
        $checkStatus = OrderUpdates::where([['orderId',$order->id],['statusId',$s->id]])->count();

        if($checkStatus==0)
        {   
            $order_status = new OrderUpdates();
            $order_status->orderId = $order->id;
            $order_status->updatedBy = $user->user_role;
            $order_status->updatedById = $user->id;
            $order_status->statusId = $s->id;
            $order_status->status = $s->name;
            $order_status->createdOn = date('Y-m-d H:i:s');
            $order_status->save();
        }
        
        $order->update();



        //sending notifications to user
            if($order->vendorId == '0')
            {
                $v = null;
            }else{
                $v = Vendor::find($order->vendorId);
            }
            $user = User::find($order->orderBy);

            $notifyCtrl = new FCMNotificationController();
            $notifyCtrl->sendFCMNotification($v,$user,'ORDER_CANCEL',$order->id);
        //ends

        return $this->sendResponse("Order Canceled Successfully...!",null);
    }

    public function misOrderCreate(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['deliveryFee'=>'required','address'=>'required|string','items_data'=>'required','locationId'=>'required']); //,'discountPrice'=>'required','paymentMode'=>'required'
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        $orderById = $user->id;
        $createdByAdmin = false;

        $vendorId = 0;

        $loc = Location::find($request->locationId);
        if($loc==null){
            return $this->sendBadException('Location not found',null);
        }

        $order = new Order();

        if(isset($request->newUser) && $request->newUser!=null)
        {   
            $checkUsr = User::where('mobile',$request->newUser['mobile'])->first();
            if($checkUsr=="" || $checkUsr==null)
            {   
                $newUser1 = new User();
                $newUser1->first_name      = trim($request->newUser['first_name']);
                $newUser1->last_name      = trim($request->newUser['last_name']);
                $newUser1->user_role  = 'user';
                $newUser1->mobile  = trim($request->newUser['mobile']);
                $newUser1->is_active = 1;
                $newUser1->createdByAdmin = true;
                $newUser1->createdOn = date('Y-m-d H:i:s');
                $newUser1->save();
                $orderById = $newUser1->id;
            }else{
                $orderById = $checkUsr->id;
                $createdByAdmin = true;
            }
            
        }

        $orderByUser = User::find($orderById);

        if($orderByUser->is_active!='1'){
            return $this->sendResponse('Sorry, User status is in-active, we are unable to create your order!, Please contact administrator.',null);
        }

        $order->vendorId      = trim($vendorId);
        $order->orderBy      = trim($orderById);
        $order->is_active = true;

        //for applienace orders
        if(isset($request->categoryId) && isset($request->subCategoryId))
        {
            $order->status = 1;
        }else{
            $order->status = 10; //order placed waiting for final price
        }
        
        //$order->finalPrice = trim($request->finalPrice);
        $order->discountPrice = trim($request->discountPrice);
        //$order->itemsPrice = trim($request->itemsPrice);

        if($orderByUser->prime_customer==2)
        {
            $deliveryFee = 0;
        }else
        {
            $deliveryFee = trim($request->deliveryFee);
        }

        $order->deliveryFee = trim($deliveryFee);

        if(isset($request->taxPercentage) && $request->taxPercentage!=null)
        {
            $order->taxPercentage = trim($request->taxPercentage);
        }

        if(isset($request->serviceTax) && $request->serviceTax!=null)
        {
            $order->serviceTax = trim($request->serviceTax);
        }

        if(isset($request->gstTax) && $request->gstTax!=null)
        {
            $order->gstTax = trim($request->gstTax);
        }

        if(isset($request->originalPrice) && $request->originalPrice!=null)
        {
            $order->originalPrice = trim($request->originalPrice);
        }
        if(isset($request->updatedPrice) && $request->updatedPrice!=null)
        {
            $order->updatedPrice = trim($request->updatedPrice);
        }

        $order->locationId = trim($request->locationId);
        $order->paymentMode = 'CASH';
        $order->address = trim($request->address);
        $order->lat = ($request->lat!=null)?$request->lat:null;
        $order->lng = ($request->lng!=null)?$request->lng:null;
        $order->createdOn = date('Y-m-d H:i:s');
        $order->createdByAdmin = $createdByAdmin;
        $order->type = 'mis';
        $order->items_data = $request->items_data;
        $order->order_json = json_encode($request->all());

        if(isset($request->alt_mobile) && $request->alt_mobile!=null)
        {
            $order->alt_mobile = trim($request->alt_mobile);
        }

        if(isset($request->tip) && $request->tip!=null)
        {
            $order->tip = trim($request->tip);
        }

        if(isset($request->payment_id) && $request->payment_id!=null)
        {
            $order->payment_id = trim($request->payment_id);
        }

        if(isset($request->tax) && $request->tax!=null)
        {
            $order->tax = trim($request->tax);
        }

        if(isset($request->taxPercentage) && $request->taxPercentage!=null)
        {
            $order->taxPercentage = trim($request->taxPercentage);
        }

        if(isset($request->pickup_store) && $request->pickup_store!=null)
        {
            $order->pickup_store = trim($request->pickup_store);
        }

        if(isset($request->categoryId) && $request->categoryId!=null)
        {
            $order->categoryId = trim($request->categoryId);
        }

        if(isset($request->subCategoryId) && $request->subCategoryId!=null)
        {
            $order->subCategoryId = trim($request->subCategoryId);
        }

        if(isset($request->preferredTime) && $request->preferredTime!=null)
        {
            $order->preferredTime = trim($request->preferredTime);
        }

        if(isset($request->extra_items) && $request->extra_items!=null)
        {
            $order->extra_items = trim($request->extra_items);
        }

        $order->save();

        $order_status = new OrderUpdates();
        $order_status->orderId = $order->id;
        $order_status->updatedBy = $user->user_role;
        $order_status->updatedById = $user->id;
        $order_status->statusId = 1;
        $order_status->status = 'Order Placed';
        $order_status->createdOn = date('Y-m-d H:i:s');
        $order_status->save();

        //send notifications to user
        $user_new = User::find($orderById);
        $notifyCtrl = new FCMNotificationController();
        $notifyCtrl->sendFCMNotification(null,$user_new,'MIS_ORDER_PLACED',$order->id);

        /*foreach ($request->items as $value) 
        {
            $item = new OrderItems();
            $item->orderId = $order->id;
            $item->itemId = $value['itemId'];
            $item->quantity = (int)$value['quantity'];
            $item->price = (float)$value['price'];
            $item->createdOn = date('Y-m-d H:i:s');
            $item->save();
        }*/

        return $this->sendResponse("Order Created Successfully...!",null);
    }

    public function getUserOrders(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        /*$orders_active = Order::with('vendor','status','location')->where('orderBy',$user->id)->whereNotIn('status',[3,4,11])->orderBy('createdOn','desc')->get();
        $orders_in_active = Order::with('vendor','status','location')->where('orderBy',$user->id)->whereIn('status',[3,4,11])->orderBy('createdOn','desc')->get();

        foreach ($orders_active as $or_ac) {
            if($or_ac->deliveryBoy!=null){
                $or_ac->deliveryBoy = User::find($or_ac->deliveryBoy);
            }
        }

        foreach ($orders_in_active as $or_in_ac) {
            if($or_in_ac->deliveryBoy!=null){
                $or_in_ac->deliveryBoy = User::find($or_in_ac->deliveryBoy);
            }
        }

        $orders['active'] = $orders_active;
        $orders['in-active'] = $orders_in_active;*/

        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric','is_active'=>'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end

        if($request->is_active==true)
        {
            $orders = Order::with('vendor','status','location')->where('orderBy',$user->id)->whereNotIn('status',[3,4,11,16])->orderBy('createdOn','desc')->offset($offset)->limit($limit)->get();
        }else{
            $orders = Order::with('vendor','status','location')->where('orderBy',$user->id)->whereIn('status',[3,4,11,16])->orderBy('createdOn','desc')->offset($offset)->limit($limit)->get();
        }

        foreach ($orders as $or) 
        {
            if($or->deliveryBoy!=null)
            {
                $or->deliveryBoy = User::find($or->deliveryBoy);
                $or->order_by = User::where('id',$or->orderBy)->select(['id', 'first_name','last_name', 'mobile'])->first();
                unset($or->orderBy);
                $or->categoryId = ($or->vendor!=null)?$or->vendor['categoryId']:null;
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
        }

        return $this->sendResponse("My orders list...!",$orders);
    }

    public function getUserOrdersHistory(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), ['mobile' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $u = User::where('mobile','LIKE',$request->mobile)->first();

        if($u==null)
        {
            return $this->sendBadException('Sorry User not found',null);
        }

        $orders = Order::with('vendor','status','location')->where('orderBy',$u->id)->orderBy('createdOn','desc')->get();

        foreach ($orders as $or) 
        {
            if($or->deliveryBoy!=null)
            {
                $or->deliveryBoy = User::find($or->deliveryBoy);
                $or->order_by = User::where('id',$or->orderBy)->select(['id', 'first_name','last_name', 'mobile'])->first();
                unset($or->orderBy);
                $or->categoryId = ($or->vendor!=null)?$or->vendor['categoryId']:null;
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
        }

        return $this->sendResponse("User Orders History list...!",$orders);
    }

    public function getVendorOrders(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $validator = Validator::make($request->all(), ['offset' => 'required|numeric','limit' => 'required|numeric','dates'=>'required','vendorId'=>'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        $offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;
        //validation end

        $orders = Order::with('vendor','location','items')->where('vendorId',$request->vendorId);

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
                $or->categoryId = ($or->vendor!=null)?$or->vendor['categoryId']:null;
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

        return $this->sendResponse("vendor orders list...!",$orders);
    }

    public function getOrder($id)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $order = Order::with('vendor','status','location','updates','items')->find($id);
        if($order==null)
        {
            return $this->sendBadException("Order not found",null);
        }

        foreach ($order->updates as $up) 
        {
            unset($up->orderId);
            $updatedUser = User::where('id',$up->updatedById)->select('id','first_name','last_name','user_role')->first();
            unset($up->updatedById);
            $up->updatedUser = $updatedUser;
        }

        if($order->vendor!=null)
        {
           $vCat = Category::where('id',$order->vendor->categoryId)->select('id','name','is_popular')->first();
           unset($order->vendor->categoryId);
           unset($order->locationId);
           $order->vendor->category=$vCat; 
        }
        
        foreach ($order->items as $item) 
        {
            $my_item = Item::where('id',$item->itemId)->select('id','name','price','rating','type','price_quantity','image')->first();
            unset($item->orderId);
            unset($item->id);
            unset($item->createdOn);
            unset($item->itemId);
            $my_item->price_quantity = json_decode($my_item->price_quantity);
            $my_item->image = ($my_item->image!=null)?request()->getSchemeAndHttpHost().'/images/items/'.$my_item->image:null;
            $item->item = $my_item;
        }

        if($order->deliveryBoy!=null)
        {
           $order->deliveryBoy = User::find($order->deliveryBoy);
        }

        if($order->orderBy!=null)
        {
           $order->order_by = User::find($order->orderBy);
           unset($order->orderBy);
        }

        $cat_sub_category = null;
        if($order->categoryId!=null)
        {   
            $c = Category::find($order->categoryId);
            $cat_sub_category = array('categoryId'=>$c->id,"categoryName"=>$c->name);
        }else{
            $cat_sub_category = array('categoryId'=>null,"categoryName"=>null);
        }

        if($order->subCategoryId!=null)
        {   
            $sc = SubCategory::find($order->subCategoryId);
            $cat_sub_category['subCategoryId'] = $sc->id;
            $cat_sub_category['subCategoryName'] = $sc->name;
        }else{
            $cat_sub_category['subCategoryId'] = null;
            $cat_sub_category['subCategoryName'] = null;
        }
        $order->cat_sub_category = $cat_sub_category;

        return $this->sendResponse("My Order",$order);
    }

    private function RazorPay($payment_id)
    {
        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        
        try {   
                $payment = $api->payment->fetch($payment_id);

                $capture = $api->payment->fetch($payment_id)->capture(array('amount'=>$payment['amount']));

                $refund = $payment->refund();
                //$refund = $payment->refund(array('amount' => 500100)); for partial refund

                if($refund['status']=='processed')
                {
                    return "refunded";
                    //return $refund;
                }
  
            } catch (Exception $e) {
                return  $e->getMessage();
            }
    }

}
