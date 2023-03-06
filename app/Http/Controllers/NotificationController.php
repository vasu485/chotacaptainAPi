<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Models\CommentLike;
use App\Models\Notification;
use App\Models\NotificationsStopUsers;
use App\Models\DeviceToken;
use App\Models\PostMedia;
use App\Models\Friends;
use App\Models\PostType;
use App\Models\Story;
use DB;
use JWTAuth;
use Exception;
use Validator;
date_default_timezone_set('Asia/Kolkata');

// API access key from Google API's Console
define( 'API_ACCESS_KEY', 'AIzaSyAljfEpxIfMOhx4aoB3zYRlbmTcFza-bps' );
//define( 'API_ACCESS_KEY', 'AIzaSyDLDVXmhND3Lm4COWSyNoHzfoTTDegVQE8' );

class NotificationConroller extends Controller
{

	/*
    * readOrUnreadNotifications
    */
    public function readOrUnreadNotifications(Request $request)
    {
    	$validator = Validator::make($request->all(), ['notificationId' => 'required','type' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        /*$notify = Notification::find($request->notificationId);
        if($notify == null){
        	return $this->sendBadException('Notification not found',null);
        }
        $notify->is_read = $request->is_read;
        $notify->update();
        return $this->sendResponse("Notification status changed successfully",null);*/
        if($request->type=='read'){
            $notify = Notification::where('id',$request->notificationId)->update(['is_read'=>1]);
            return $this->sendResponse("Notification status changed successfully",null);
        }elseif ($request->type=='delete'){
            $notify = Notification::where('id',$request->notificationId)->delete();
            return $this->sendResponse("Notification removed successfully",null);
        }else{
            return $this->sendBadException('type parameter is required',null);
        }
    }

    /*
    * markAllRead
    */
    public function markAllReadOrDelete(Request $request)
    {
        $validator = Validator::make($request->all(), ['type' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $authUser = JWTAuth::parseToken()->authenticate();
        if($request->type=='read'){
            $notify = Notification::where('receiver',$authUser->id)->update(['is_read'=>1]);
            return $this->sendResponse("Notifications status changed successfully",null);
        }elseif ($request->type=='delete'){
            $notify = Notification::where('receiver',$authUser->id)->delete();
            return $this->sendResponse("Notifications removed successfully",null);
        }else{
            return $this->sendBadException('type parameter is required',null);
        }

    }

     /*
    * markAllRead
    */
    public function stopNotificationsFromUser(Request $request)
    {
        $validator = Validator::make($request->all(), ['userId' => 'required','stop' => 'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $authUser = JWTAuth::parseToken()->authenticate();

        if($request->stop==true){
            
            $check_notify = NotificationsStopUsers::where([['userId',$authUser->id],['stopUserId',$request->userId]])->count();
            if($check_notify==0){
                $notify = new NotificationsStopUsers();
                $notify->userId = $authUser->id;
                $notify->stopUserId = $request->userId;
                $notify->save();
            }
            return $this->sendResponse("Notifications stopped for this user successfully",null);
        }elseif ($request->stop==false){
            $notify = NotificationsStopUsers::where([['userId',$authUser->id],['stopUserId',$request->userId]])->delete();
            return $this->sendResponse("Notifications status changed successfully",null);
        }else{
            return $this->sendBadException('stop parameter is required',null);
        }

    }

    /*
    * user notifications list
    */
    public function userNotificationsList(Request $request)
    {
    	$offset = isset($request->offset)?$request->offset:0;
        $limit = isset($request->limit)?$request->limit:10;

    	//find loggend user
        $authUser = JWTAuth::parseToken()->authenticate();

        /*$notify = Notification::with('initiator','receiver')->where('receiver',$authUser->id)
                  ->offset($offset)
                  ->limit($limit)
                  ->orderBy('createdOn','DESC')
                  ->get();

        foreach ($notify as $value) {
        	$value->notificationId = $value->id;
        	$value->is_read = ($value->is_read==1)?true:false;
        	unset($value->msgStatus);
        	unset($value->msgResponse);
        	unset($value->id);

            $media = PostMedia::where([['postId',$value->postId],['type',2]])->first();
            $value->thumbnail = ($media!=null)?$media->url:null;
        }*/

        $notify = Notification::with('receiver')->where('receiver',$authUser->id)
                  ->select(DB::raw('group_concat(id ORDER BY createdOn DESC) as ids'),'notifications.receiver','notifications.type','notifications.postId', DB::raw('group_concat(initiator ORDER BY createdOn DESC) as initiator'),DB::raw('group_concat(createdOn ORDER BY createdOn DESC) as createdOn'))
                  ->offset($offset)
                  ->limit($limit)
                  ->groupBy('postId','type')
                  ->orderBy('createdOn','DESC')
                  ->get();
        //return $notify;

        foreach ($notify as $value) {
            $latestNotifyId = explode(",", $value->ids);
            $value->notificationId = $latestNotifyId[0];

            $users = array_values(array_unique(explode(",", $value->initiator)));
            $initiators = array();
            if(count($users)>0)
            {
                foreach ($users as $value1) {
                   $usr = User::where('id',$value1)->select(['id', 'first_name', 'last_name','image','is_active','is_online','user_role','business_name','image_ratio','cover_pic_ratio','cover_pic'])->first();
                   //fetching is_follow
                   $findStatus = Friends::where([['userId',$authUser->id],['friendId',$value1],['status',1]])->count();
                   $usr->is_follow = ($findStatus==0)?false:true;
                   //ends
                   array_push($initiators, $usr);
                }
            }
            $value->initiator = $initiators;

            $find_notify = Notification::find($latestNotifyId[0]);
            $value->is_read = ($find_notify->is_read==1)?true:false;
            $value->title = $find_notify->title;
            $createdOnDates = array_values(array_unique(explode(",", $value->createdOn)));
            $value->createdOn = $createdOnDates[0];
            unset($value->ids);

            if($value->type=='STORY')
            {
                 $media = Story::find($value->postId);
            }else {
                 $media = PostMedia::where([['postId',$value->postId],['type',2]])->first();
            }
           
            $value->thumbnail = ($media!=null)?$media->url:null;

            if($value->postId!=null){
                 $findPost = Post::find($value->postId);
                 if($findPost==null){
                    $value->post_type = null;
                 }else{
                    $find_pType = PostType::find($findPost->type);
                    $value->post_type = $find_pType->type;
                 }
                 
             }else{
                 $value->post_type = null;
             }
        }

        return $this->sendResponse("User notifications list",$notify);
    }


    /*
    * sendNotification common function
    * 'FOLLOW','UNFOLLOW','LIKE','DISLIKE','COMMENT','COMMENT-REPLY','COMMENT-LIKE','TAG'
    */
    public function sendNotification($data,$initiator,$notifyType)
    {   

        //checking user notifications stopped or not for particular user
    	$receiverId = $data['userId'];
        $checkStoppedUsers = NotificationsStopUsers::where([['userId',$receiverId],['stopUserId',$initiator->id]])->count();
        if($checkStoppedUsers>0){
            return "stopped";
        }
        //ends

        //find initiator user role
        $title = ($initiator->user_role=='USER')?($initiator->first_name.' '.$initiator->last_name):($initiator->business_name);


    	$notification = new Notification();
    	$notification->type = $notifyType;
    	$notification->initiator = $initiator->id;
    	$notification->receiver = $receiverId;
    	$notification->type = $notifyType;
    	$notification->createdOn = date('Y-m-d H:i:s');
    	if($notifyType=='FOLLOW')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'started following you';
    		$summary = null;$bigText = "";$bigImage = null;$pId = null;$pType =null;
    		$notification->title = '<b>'.$title.'</b> '.$message;
    	}
    	if($notifyType=='UNFOLLOW')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'unfollowed you';
    		$summary = null;$bigText = "";$bigImage = null;$pId = null;$pType =null;
    		$notification->title = '<b>'.$title.'</b> '.$message;
    	}
    	if($notifyType=='LIKE')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'has liked your post';
    		$post = $data['post'];

			$media = PostMedia::where([['postId',$post->id],['type',2]])->first();
    		$bigImage = ($media!=null)?$media->url:null;
    		$summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

    		$notification->title = '<b>'.$title.'</b> '.$message;
    		$notification->postId = $post->id;
    	}
    	if($notifyType=='DISLIKE')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'has disliked your post';
    		$post = $data['post'];

    		$media = PostMedia::where([['postId',$post->id],['type',2]])->first();
    		$bigImage = ($media!=null)?$media->url:null;
    		$summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

    		$notification->postId = $post->id;
    		$notification->title = '<b>'.$title.'</b> '.$message;
    	}
    	if($notifyType=='COMMENT')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'commented on your post'.' "'.$data['text'].'"';
    		$post = $data['post'];

    		$media = PostMedia::where([['postId',$post->id],['type',2]])->first();
    		$bigImage = ($media!=null)?$media->url:null;
    		$summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

    		$notification->title = '<b>'.$title.'</b> '.$message;
    		$notification->postId = $post->id;
    	}
    	if($notifyType=='COMMENT-REPLY')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'has replied to your comment on this post'.' "'.$data['text'].'"';
    		$post = $data['post'];

    		$media = PostMedia::where([['postId',$post->id],['type',2]])->first();
    		$bigImage = ($media!=null)?$media->url:null;
    		$summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

    		$notification->title = '<b>'.$title.'</b> '.$message;
    		$notification->postId = $post->id;
    	}
    	if($notifyType=='COMMENT-LIKE')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'liked your comment on this post';
    		$post = $data['post'];

    		$media = PostMedia::where([['postId',$post->id],['type',2]])->first();
    		$bigImage = ($media!=null)?$media->url:null;
    		$summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

    		$notification->title = '<b>'.$title.'</b> '.$message;
    		$notification->postId = $post->id;
    	}
    	if($notifyType=='TAG')
    	{
    		//$title = $initiator->first_name.' '.$initiator->last_name;
    		$message = 'tagged you on his post';
    		$post = $data['post'];

    		$media = PostMedia::where([['postId',$post->id],['type',2]])->first();
    		$bigImage = ($media!=null)?$media->url:null;
    		$summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

    		$notification->title = '<b>'.$title.'</b> '.$message;
    		$notification->postId = $post->id;
    	}
        if($notifyType=='SHARE')
        {
            //$title = $initiator->first_name.' '.$initiator->last_name;
            $message = 'shared your post';
            $post = $data['post'];

            $media = PostMedia::where([['postId',$post->id],['type',2]])->first();
            $bigImage = ($media!=null)?$media->url:null;
            $summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

            $notification->title = '<b>'.$title.'</b> '.$message;
            $notification->postId = $post->id;
        }
        if($notifyType=='STORY')
        {
            //$title = $initiator->first_name.' '.$initiator->last_name;
            $message = 'added new Story';
            $post = $data['post'];

            $media = Story::find($post->id);
            $bigImage = ($media!=null)?$media->url:null;
            $summary = null;
            $bigText = ""; //$post->text;
            $pId = $post->id;
            $pType = $post->type;

            $notification->title = '<b>'.$title.'</b> '.$message;
            $notification->postId = $post->id;
        }

    	$notification->save();

    	//fetching tokens and sending notification
    	$registrationIds = DeviceToken::where('userId',$receiverId)->pluck('deviceToken');
    	if(count($registrationIds)>0)
    	{  
            if($pType!=null){
                 $find_pType = PostType::find($pType);
                 $find_pTypeName = $find_pType->type;
             }else{
                 $find_pTypeName = null;
             }
           
            //Message, bigText, Summary all are same
	    	$msg = array
				(	"postId"=>$pId,
                    "postType"=>$find_pTypeName,
				    "title"=>$title,
				    "message"=>$message,
				    "color"=>"#619bff",
				    "summary"=>$title.' '.$message,//$summary,//"SUMMARY",
				    "bigText"=>$title.' '.$message,//$bigText,//"Odcet Fcm Notify",
				    "notificationId"=>$notification->id,
				    "bigAllowIcon"=>1,
				    "bigIconUrl"=>$initiator->image,
				    "url"=>$bigImage,
				    "type"=>1,
				    'vibrate'=> 1,
					'sound'=> 1,
					'initiatorId'=>$initiator->id,
					'receiverId'=>$receiverId,
					'notifyType'=>$notifyType
				);
    	    $result = json_decode($this->sendFCM($registrationIds,$msg));

            if(isset($result->results[0]->message_id))
            {
                //$notification->msgResponse=($result->success>0)?"success:".$result->results[0]->message_id:"failure:".$result->results[0]->error;
                $notification->msgResponse=json_encode($result);
                $notification->msgStatus=($result->success>0)?1:0;
                $notification->update();
                $responseText = "success";
            }else{
                $notification->msgResponse="failure:";
                $notification->msgStatus=0;
                $notification->update();
                $responseText = "failure";
            }

    	    return $responseText;
    	}
    }


	/*-------- Private functions --------*/
    //sending fcm server
    private function sendFCM($registrationIds,$msg)
    {
		/*$registrationIds = ["fpfTtaoWs3I:APA91bFll-0HLDyfE0-n7c4QYW2HxkNt-WBQCp1mCFKEDOCk_O_po3iAg0zFXpoyHWLkLomTpR6DyNZl0jG74EmF8KDFYTa_BaXcNNk4zaPGLbLD-YTwxei5avA0pEgEkxVWqF2PU7l5"];

		// payload
		$msg = array
		(
			"id"=>"1",
		    "title"=>"I LOVE U",
		    "message"=>"Alia Bhatt",
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
		);*/

		/*if(is_array($registrationIds)){
		  	$fields['registration_ids'] = $registrationIds;
		}else{
		  	$fields['to'] = $registrationIds;
		}*/

		$fields['registration_ids'] = $registrationIds;
		$fields['data'] = $msg;

		$headers = array('Authorization: key=' . API_ACCESS_KEY,'Content-Type: application/json');

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


}
