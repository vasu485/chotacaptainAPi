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
use App\Models\DeviceToken;
use App\Models\OrderItems;
use App\Models\Status;
use App\Models\Location;
use App\Models\Item;
use App\Models\Notification;
use DB;
use Hash;
use Mail;
use JWTAuth;
use Exception;
use Storage;
use Validator;
use Tymon\JWTAuthExceptions\JWTException;
date_default_timezone_set('Asia/Kolkata');
ini_set('memory_limit','-1');
ini_set('max_execution_time', 1800); //30 mins, 300-5 mins
ini_set( 'upload_max_size' , '64M' );
ini_set( 'post_max_size', '64M');

// FCM_KEY from Google API's Console
define( 'FCM_KEY', 'AAAA81bwIcA:APA91bHTUsUvvs_MvXiSJHr3h0rVIOVDtHVh1ambaKiOPC-nfRdlU9WipCfBiVj99sl8HPxs0ugpV_RiLPuF-euWf67j7ukf4vKmXiG53iim_fQXkpFwh62FdRRIUMt8SJJkrbGG_UP7' );

define( 'USER_FCM_KEY', 'AAAAEE30bhI:APA91bGz6TXRaSbvuBrhhSwgsjAcGpOGFQJkX3I2i88pOEh2-nFlXH1rJZED87n-ZC0WsM6-pHDPj5ZlumRySdDQ5IgIPh2YldTQQHF8inMFHmVIAr7-GUW1Shn8OC5wJ7_PSo-kihO3' );

class FCMNotificationController extends Controller
{
    public function pushGroupFCMNotifications(Request $request)
    {   
        $validator = Validator::make($request->all(), ['title' => 'required','message' => 'required','selectUsers' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $mssg = trim($request->message);
        $title = trim($request->title);
        $receiverId = 0;
        $senderId = 0;
        $orderId = 0;
        $n_type = 2;

        $msg = array
        (
            "id"=>rand(10,100),
            "title"=>$title,
            "message"=>$mssg,
            "summary"=>$mssg,
            "bigText"=>$mssg,
            "notificationId"=>rand(10,100),
            "bigAllowIcon"=>1,
            "bigIconUrl"=> null,
            "image"=>null,
            "url"=>null,
            'vibrate'=>1,
            'sound'=>'my_sound',
            'senderId'=>$senderId,
            'receiverId'=>$receiverId,
            'orderId'=>$orderId,
            'type'=>$n_type,
            "messageContent"=>"Testing",
            "messageType"=>"notification",
            "body"=>$mssg,
            "collapse_key"=>"com.apna.chotu",
            "show_notification"=>"true",
            "notification_foreground"=> "true",
            "priority"=> "high",
            "android_channel_id"=>"my_channel_id",
        );

        $msg2 = array
        (
            "notificationId"=>rand(10,100),
            'senderId'=>$senderId,
            'receiverId'=>$receiverId,
            'orderId'=>$orderId,
            "notification_foreground"=> "true",
            "notification_body" => $mssg,
            "notification_title"=> $title,
            "notification_android_sound"=> "my_sound",
            "notification_android_channel_id"=>"my_channel_id"
        );

        $userRole = $request->selectUsers;

        if($userRole=='all')
        {   
            //user
            $u = User::where('user_role','=','USER')->pluck('id');
            $u_tokens = DeviceToken::whereIn('user_id',$u)->pluck('deviceToken')->toArray();
            $u_tokens = array_chunk($u_tokens,1000);
            foreach ($u_tokens as $token_set) {
                $u_result = $this->sendUSERFCM($token_set,$msg,$msg2);
            }

            //vendors
            $v = User::where('user_role','=','VENDOR')->pluck('id');
            $v_tokens = DeviceToken::whereIn('user_id',$v)->pluck('deviceToken')->toArray();
            $v_tokens = array_chunk($v_tokens,1000);
            foreach ($v_tokens as $token_set) {
                $v_result = $this->sendVENDORFCM($token_set,$msg);
            }

            //boys
            $db = User::where('user_role','=','DELIVERY_BOY')->pluck('id');
            $db_tokens = DeviceToken::whereIn('user_id',$db)->pluck('deviceToken')->toArray();
            $db_tokens = array_chunk($db_tokens,1000);
            foreach ($db_tokens as $token_set) {
                $db_result = $this->sendDELIVERYBOYFCM($token_set,$msg,$msg2);
            }

            
            $m = "Notifications sent to all Apnachotu members";
        }else if($userRole=='user')
        {
            
            $u = User::where('user_role','=','USER')->pluck('id');
            $u_tokens = DeviceToken::whereIn('user_id',$u)->pluck('deviceToken')->toArray();
            $u_tokens = array_chunk($u_tokens,1000);
            foreach ($u_tokens as $token_set) {
                $u_result = $this->sendUSERFCM($token_set,$msg,$msg2);
                /*$myfile = fopen("fcm_test.txt", "a") or die("Unable to open file!");
                fwrite($myfile, "\n". $u_result);
                fclose($myfile);*/
            }
            $m = "Notifications sent to all Apnachotu users";

        }else if($userRole=='vendor')
        {
            
            $v = User::where('user_role','=','VENDOR')->pluck('id');
            $v_tokens = DeviceToken::whereIn('user_id',$v)->pluck('deviceToken')->toArray();
            $v_tokens = array_chunk($v_tokens,1000);
            foreach ($v_tokens as $token_set) {
                $v_result = $this->sendVENDORFCM($token_set,$msg);
            }
            $m = "Notifications sent to all Apnachotu vendors";

        }else if($userRole=='delivery_boy')
        {
            
            $db = User::where('user_role','=','DELIVERY_BOY')->pluck('id');
            $db_tokens = DeviceToken::whereIn('user_id',$db)->pluck('deviceToken')->toArray();
            $db_tokens = array_chunk($db_tokens,1000);
            foreach ($db_tokens as $token_set) {
                $db_result = $this->sendDELIVERYBOYFCM($token_set,$msg,$msg2);
            }
            $m = "Notifications sent to all Apnachotu delivery boys";
            
        }

        return $this->sendResponse($m,null);;
       
    }

    //app dynamic notifications from order updates
    public function sendFCMNotification($sender=null,$receiver,$notifyType,$orderId)
    {   
    	//fetching tokens and sending notification
    	$registrationIds = DeviceToken::where('mobile','=',$receiver->mobile)->pluck('deviceToken');
    	if(count($registrationIds)>0)
    	{  
            if($notifyType == 'NEW_ORDER')
            {
            	$mssg = 'New Order Received...!';
                $title = "Apna Chotu";
            	$receiverId = $receiver->id;
            	$senderId = $sender->id;
            	$orderId = $orderId;
            	$n_type = 1;
            }else if($notifyType == 'ACCEPT_ORDER_DELIVERY'){
            	$mssg = 'Hey '.$receiver->last_name.'!, New Order is assigned to you.';
                $title = "Order #".$orderId;
            	$receiverId = $receiver->id;
            	$senderId = 0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'ORDER_PLACED'){
            	$mssg = 'Your Order Placed Successfully in '.$sender->name;
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = $sender->id;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'ORDER_CANCEL'){
            	$mssg = 'Sorry, Your Order has been Canceled';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'MIS_ORDER_PLACED'){
            	$mssg = 'Your Order Placed Successfully';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = 0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Confirmed'){
            	$mssg = 'Your Order has been Confirmed by the Vendor';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Cancelled'){
            	$mssg = 'Sorry, Your Order has been Canceled';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Delivered'){
            	$mssg = 'Your Order has been Delivered Successfully';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'On_the_way'){
            	$mssg = 'Your Order is On the way to your place';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Cooking'){
            	$mssg = 'Your Order items has been started Cooking';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Payment_Received'){
                $mssg = 'Your Order Payment has been Received';
                $title = "Order #".$orderId." Status!";
                $receiverId = $receiver->id;
                $senderId = ($sender!=null)?$sender->id:0;
                $orderId = $orderId;
                $n_type = 2;
            }else if($notifyType == 'Order_Prepared' || $notifyType == 'Searching_Boy'){
            	$mssg = 'Your Order is Prepared and Searching for Chotu';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'items_Final_Price_Added'){
            	$mssg = 'Your Order items Final Price is Added';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Order_Placed_Waiting_for_Final_Price'){
            	$mssg = 'Your Order Placed and Waiting for Final Price';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Out_Of_Stock'){
            	$mssg = 'Your Order is canceled because of Out Of Stock';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Delivery_Boy_Assigned'){
            	$mssg = 'Delivery Boy assigned for your Order';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Order_PickedUp'){
            	$mssg = 'Delivery Boy PickedUp your Order';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else if($notifyType == 'Order_Arrived'){
            	$mssg = 'Delivery Boy arrived at your place !, please collect your Order';
                $title = "Order #".$orderId." Status!";
            	$receiverId = $receiver->id;
            	$senderId = ($sender!=null)?$sender->id:0;
            	$orderId = $orderId;
            	$n_type = 2;
            }else{
            	$mssg = 'Orders Received';
                $title = "Apna Chotu";
            	$receiverId = $receiver->id;
            	$senderId = 0;
            	$orderId = 0;
            	$n_type = 0;
            }

            //Message, bigText, Summary all are same
	    	$msg = array
			(
				"id"=>rand(10,100),
			    "title"=>$title,
			    "message"=>$mssg,
			    "summary"=>$mssg,
			    "bigText"=>$mssg,
			    "notificationId"=>rand(10,100),
			    "bigAllowIcon"=>1,
			    "bigIconUrl"=> null,
                "image"=>null,
			    "url"=>null,
			    'vibrate'=>1,
				'sound'=>'my_sound',
				'senderId'=>$senderId,
				'receiverId'=>$receiverId,
				'orderId'=>$orderId,
				'type'=>$n_type,
	            "messageContent"=>"Testing",
                "messageType"=>"notification",
                "body"=>$mssg,
                "collapse_key"=>"com.apna.chotu",
                "show_notification"=>"true",
                "notification_foreground"=> "true",
                "priority"=> "high",
                "android_channel_id"=>"my_channel_id",
			);

            $msg2 = array
            (
                "notificationId"=>rand(10,100),
                'senderId'=>$senderId,
                'receiverId'=>$receiverId,
                'orderId'=>$orderId,
                "notification_foreground"=> "true",
                "notification_body" => $mssg,
                "notification_title"=> $title,
                "notification_android_sound"=> "my_sound",
                "notification_android_channel_id"=>"my_channel_id"
            );

			if($receiver->user_role=='USER')
			{
				$result = json_decode($this->sendUSERFCM($registrationIds,$msg,$msg2));
			}else if($receiver->user_role=='DELIVERY_BOY')
            {
                $result = json_decode($this->sendDELIVERYBOYFCM($registrationIds,$msg,$msg2));
            }else{
				$result = json_decode($this->sendVENDORFCM($registrationIds,$msg));
			}


            /*$myfile = fopen("19_logs.txt", "a") or die("Unable to open file!");
            fwrite($myfile, "\n". json_encode($msg));
            fwrite($myfile, "\n". json_encode($registrationIds));
            fclose($myfile);*/
    	    

            if(isset($result->results[0]->message_id))
            {
                //$notification->msgResponse=($result->success>0)?"success:".$result->results[0]->message_id:"failure:".$result->results[0]->error;
                /*$notification->msgResponse=json_encode($result);
                $notification->msgStatus=($result->success>0)?1:0;
                $notification->update();*/
                $responseText = "success";

            }else{
                /*$notification->msgResponse="failure:";
                $notification->msgStatus=0;
                $notification->update();*/
                $responseText = "failure";

            }

            /*$myfile = fopen("19_logs.txt", "a") or die("Unable to open file!");
            fwrite($myfile, "\n". json_encode($result));
            fclose($myfile);*/

    	    return $responseText;
    	}
    }

    public function sendCronFCMNotificationToBoys($receivers,$orderId)
    {   
        //fetching tokens and sending notification
        $registrationIds = DeviceToken::whereIn('user_id',$receivers)->pluck('deviceToken');
        if(count($registrationIds)>0)
        {  
            
            $mssg = 'Hey Chotu!, New Order is assigned to you.';
            $title = "Order #".$orderId;
            $receiverId = 0;
            $senderId = 0;
            $orderId = $orderId;
            $n_type = 2;
            //Message, bigText, Summary all are same
            $msg = array
            (
                "id"=>rand(10,100),
                "title"=>$title,
                "message"=>$mssg,
                "summary"=>$mssg,
                "bigText"=>$mssg,
                "notificationId"=>rand(10,100),
                "bigAllowIcon"=>1,
                "bigIconUrl"=> null,
                "image"=>null,
                "url"=>null,
                'vibrate'=>1,
                'sound'=>'my_sound',
                'senderId'=>$senderId,
                'receiverId'=>$receiverId,
                'orderId'=>$orderId,
                'type'=>$n_type,
                "messageContent"=>"Testing",
                "messageType"=>"notification",
                "body"=>$mssg,
                "collapse_key"=>"com.apna.chotu",
                "show_notification"=>"true",
                "notification_foreground"=> "true",
                "priority"=> "high",
                "android_channel_id"=>"my_channel_id",
            );

            $msg2 = array
            (
                "notificationId"=>rand(10,100),
                'senderId'=>$senderId,
                'receiverId'=>$receiverId,
                'orderId'=>$orderId,
                "notification_foreground"=> "true",
                "notification_body" => $mssg,
                "notification_title"=> $title,
                "notification_android_sound"=> "my_sound",
                "notification_android_channel_id"=>"my_channel_id"
            );

            $result = json_decode($this->sendDELIVERYBOYFCM($registrationIds,$msg,$msg2));

            /*$myfile = fopen("19_logs.txt", "a") or die("Unable to open file!");
            fwrite($myfile, "\n". json_encode($msg));
            fwrite($myfile, "\n". json_encode($registrationIds));
            fclose($myfile);*/
            if(isset($result->results[0]->message_id))
            {
                $responseText = "success";
            }else{
                $responseText = "failure";
            }
           /* $myfile = fopen("19_logs.txt", "a") or die("Unable to open file!");
            fwrite($myfile, "\n". json_encode($result));
            fclose($myfile);*/
            return $responseText;
        }
    }

	/*-------- Private functions --------*/
    //sending fcm server
    private function sendVENDORFCM($registrationIds,$msg)
    {
		/*$registrationIds = ["eS3aj4GZSiKqB2jJcVykSw:APA91bH7gkjHOYwCETooh04PpWben9MHUxnd_Ahznaftr6AsBjZW7sLumSMjPuJZr7dxVK1AHsdSGt3NG1VqHKb_Eg_LR0dbPS59Y2wo56zulBTcnljJfWuVVtKnGL0FcRg-mbhte3f6","dGnurRGgSl-meI1e2GQ8D6:APA91bHaDJGOT41D_lQ98gHYhPx9F5kx4P67Lzoc0OFRYUdWDdk7nkNoh8MPKEalO4fi7kSk4nUx_3pW7WdUZjfqklVLhLZhTtgV7l9ykpb6da5IzsyJ9EB1slkHgCYM03gPjDPQqMkv"];*/

		// payload
		/*$msg = array
		(
			"id"=>"1",
		    "title"=>"Apna Chotu",
		    "message"=>"New Order Received",
		    "color"=>"#FF000",
		    "summary"=>"SUMMARY",
		    "bigText"=>"Odcet Fcm Notify",
		    "notificationId"=>"1439",
		    "bigAllowIcon"=>1,
		    "bigIconUrl"=> "https://img.timesnownews.com/story/1551004158-Untitled_design_14_3.jpg?d=400x400",
		    "url"=>"http://www.baltana.com/files/wallpapers-3/Alia-Bhatt-Background-Wallpaper-11187.jpg",
		    "type"=>1,
		    'vibrate'	=> 1,
			'sound'		=> 1,
			"image" => "https://toraeats.toracabs.com/org/cuisine-image/CSNtoraeats3c8ca6e1c4e06a8742485/1596278591883-depositphotos_79588356-stock-photo-south-indian-meals-on-banana.jpg",
            "messageContent"=> "Your are now registered with Tora Eats. You can now add your Menu and Go Live in 10 Minutes!"
		);*/

		/*if(is_array($registrationIds)){
		  	$fields['registration_ids'] = $registrationIds;
		}else{
		  	$fields['to'] = $registrationIds;
		}*/

		$fields['registration_ids'] = $registrationIds;
		$fields['data'] = $msg;

		$headers = array('Authorization: key=' . FCM_KEY,'Content-Type: application/json');

		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		curl_close( $ch );
		return $result;
    }

    private function sendUSERFCM($registrationIds,$msg,$msg2)
    {

		$fields['registration_ids'] = $registrationIds;
		$fields['notification'] = $msg;
        $fields['data'] = $msg2;

		$headers = array('Authorization: key=' . USER_FCM_KEY,'Content-Type: application/json');

		$ch = curl_init();
		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
		curl_setopt( $ch,CURLOPT_POST, true );
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
		$result = curl_exec($ch );
		curl_close( $ch );
		return $result;
    }

    private function sendDELIVERYBOYFCM($registrationIds,$msg,$msg2)
    {
        $fields['registration_ids'] = $registrationIds;
        $fields['notification'] = $msg;
        $fields['data'] = $msg2;

        $headers = array('Authorization: key=' . FCM_KEY,'Content-Type: application/json');

        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
        return $result;
    }

    public function TestFCM(Request $request)
    {
        $registrationIds = ["$request->fcm_token"];

        // payload
            $msg = array("id"=>28,
                "title"=>"Apna Chotu",
                "message"=>"Hey Chotu!, New Order is Assigned to you",
                "summary"=>"Hey Chotu!, New Order is Assigned to you",
                "bigText"=>"Hey Chotu!, New Order is Assigned to you",
                "notificationId"=>23,
                "bigAllowIcon"=>1,
                "bigIconUrl"=>null,
                "image"=>null,
                "url"=>null,
                "vibrate"=>1,
                "sound"=> "my_sound",
                "senderId"=>0,
                "receiverId"=>94,
                "orderId"=>15354,
                "type"=>2,
                "messageContent"=>"Testing",
               // "channel_id"=>"my_channel_id",
                "messageType"=>"notification",
                "body"=>"Hey Chotu!, New Order is Assigned to you",
                "collapse_key"=>"com.apna.chotu",
                "show_notification"=>"true",
                "notification_foreground"=>"true",
                "priority"=> "high",
                "android_channel_id"=>"my_channel_id",
                );

        //$fields['name'] = 'my_notification'; 
        $fields['registration_ids'] = $registrationIds;
        $fields['notification'] = $msg;
        //$fields['android'] = array("notification"=> array("channel_id"=> "my_channel_id","sound"=> "my_sound.mp3"));
        $fields['data'] = array("notification_foreground"=> "true",
                                "notification_body" => "Hey Chotu!, New Order is Assigned to you",
                                "notification_title"=> "Hey Chotu!, New Order is Assigned to you",
                                "notification_android_sound"=> "my_sound",
                                "notification_android_channel_id"=>"my_channel_id");

        $headers = array('Authorization: key=' . FCM_KEY,'Content-Type: application/json'); //USER_FCM_KEY

        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
        //return json_encode( $fields );
        return $result;
    }

}
