<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\User;
use App\Models\Order;
use App\Models\PostType;
use App\Models\PostLike;
use App\Models\PostMedia;
use App\Models\PostComment;
use App\Models\CommentLike;
use App\Models\SavedPost;
use App\Models\Views;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Friends;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /* protected functions */
    public function sendResponse($message,$data) {
        return response()->json(['status' => 'success','message' => $message,'data' => $data], 200);
    }
    public function sendException($message,$data) {
        return response()->json(['status' => 'error','message' => $message,'data' => $data], 500);
    }
    public function sendBadException($message,$data) {
        return response()->json(['status' => 'error','message' => $message,'data' => $data], 400);
    }
    public function sendUnAuthException($message,$data) {
        return response()->json(['status' => 'error','message' => $message,'data' => $data], 401);
    }


    //grouping comments based on parentId
    public function convertToHierarchicalJson($elements, $parentId = null,$authUserId) 
    {
        $branch = array();
        foreach ($elements as $element) {
            //adding comment likes count and authuser like status
            $element->likesCount = CommentLike::where('commentId',$element->commentId)->count();
            $authUsrLikes = CommentLike::where([['userId',$authUserId],['commentId',$element->commentId]])->count();
            $element->is_liked = ($authUsrLikes == 0)?false:true;
            unset($element->userId);
            $element->is_active = ($element->is_active==1)?true:false;
            //ends

            //$element->replyCount = PostComment::where([['is_active',1],['parentId',$element->commentId]])->count();
            
            if ($element['parentId'] == $parentId) {
                // return $element;
                $children = $this->convertToHierarchicalJson($elements, $element['commentId'],$authUserId);
                if ($children) {
                    $element['children'] = $children;
                }else{
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    //grouping comments based on parentId
    public function BusinessReviewConvertToHierarchicalJson($elements, $parentId = null,$authUserId) 
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parentId'] == $parentId) {
                // return $element;
                $children = $this->BusinessReviewConvertToHierarchicalJson($elements, $element['id'],$authUserId);
                if ($children) {
                    $element['children'] = $children;
                }else{
                    $element['children'] = [];
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }



    public function calculateBoyPaymentBasedOnOrderCount($from,$to,$boyId)
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

            //boy orders settlement calculation
            return $this->boyOrdersPaymentCalculation($orderCount);

        }
        return array("error"=>1);
    }

    // re usable function based on orders counts
    private function boyOrdersPaymentCalculation($number)
    {
        //$number = 32;
        $len = strlen($number);
        //echo "number is : ".$number."<br>";
        if($len>1)
        {
            $n = substr($number, 0, 1);
            $lastDigit = substr($number, -1); // 8
            //echo $n."<br>";

            $values = [];
            while ($number > 0 && $n > 0) {
                $a = floor($number / $n / 10) * 10;
                //echo $a."<br>";
                $number -= $a;
                $n--;
                array_push($values, $a);
            }
            $sum = array_sum($values);
            //echo "values sum: ".$sum."<br>";
            //echo $lastDigit."<br>";
           // print_r($values);
            //echo "<br>";

            $amount = array();
            for ($i = 0; $i < count($values); $i++) 
            {
                $a = $values[$i] * $this->checkOrderDeliveryPrice($i);
                //echo $a."<br>";
                array_push($amount,$a);
            }
            //print_r($amount);
            //echo "<br>";
            $amount_sum = array_sum($amount);

            $findlast_i_value = count($values);
            //echo $findlast_i_value."<br>";

            $lastDigitAmount = $lastDigit * $this->checkOrderDeliveryPrice($findlast_i_value);
            //echo $lastDigitAmount."<br>";

            //echo "amount is: ".($amount_sum + $lastDigitAmount);
            //echo "<br>";
            return ($amount_sum + $lastDigitAmount);

        }else{
            //echo "amount: ".$number*10;
            return $number*10; 
        }
    }

    private function checkOrderDeliveryPrice($i)
    {   
      switch ($i) 
      {
        case 0: //1-10
            $var3 = 30;
            break;
        case 1: //11-20
            $var3 = 40;
            break;
        case 2: //21-30
            $var3 = 45;
            break;
        case $i >= 3:
            $var3 = 50;
            break;
        default:
            $var3 = 0;
            break;
      }
      return $var3;
    }
}
