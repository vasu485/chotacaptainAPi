<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Requests;
use Redirect,Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Category;
use App\Models\Location;
use App\Models\Order;
use App\Models\PrimeCustomerData;
use App\Models\OrderUpdates;
use App\Models\Status;
use App\Models\Notification;
use App\Models\SearchBoyForOrderAssignCronJob;
use App\Models\OrderAssignToDeliveryBoy;
use App\Models\Settings;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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

class CronJobController extends Controller
{
    
   public function __construct()
   {
       //$this->middleware('jwt.auth', ['except' => ['searchNearByDeliveryBoyFromVendor']]);
   }

   public function updatePrimeCustomersData()
   {
      $data = PrimeCustomerData::where('is_active',1)->get();
      if(count($data)>0)
      {  
         foreach($data as $d)
         {  
            $date_now = date("Y-m-d"); // this format is string comparable

            if ($date_now >= $d->expiredOn) 
            {  
               $p_data = PrimeCustomerData::find($d->id);
               $p_data->is_active = 0;
               $p_data->update();

               $u=User::find($d->user_id);
               $u->prime_customer = 0;
               $u->update();
            }            
         }
      }
      return "done";
   }

   public function testPrimeCustomersData(Request $request)
   {  
      $validator = Validator::make($request->all(), ['mobile' => 'required','end_date'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

      $user = User::where('mobile',$request->mobile)->first();
        if($user==null)
        {   
            return $this->sendBadException('User not found',null);
        }
        if($user->prime_customer==0)
        {   
            return $this->sendBadException('Sorry,this User not a prime customer',null);
        }
        $checking = PrimeCustomerData::where([['user_id',$user->id],['is_active',1]])->update(['expiredOn'=>$request->end_date]);
      return "updated end date";
   }

   public function insertCommisionIfNull()
   {
    	$setting = Settings::where('createdOn',date('Y-m-d'))->count();
    	if($setting==0)
    	{
    		$s = Settings::where('createdOn',date('Y-m-d',strtotime("-1 days")))->first();
    		$s_n = new Settings();
    		$s_n->commision_per_order = $s->commision_per_order;
    		$s_n->min_orders_for_commision =$s->min_orders_for_commision;
    		$s_n->createdOn = date('Y-m-d');
    		$s_n->save();
    		return "done";
    	}else{
    		return "existed";
    	}
   }

   public function updateBoyDecisionsFromCronIfBotNotWorkSometimes()
   {
      $noti = OrderAssignToDeliveryBoy::where('boyDecision',2)->get();
      if(count($noti)>0)
      {  
         foreach ($noti as $n) 
         {  
            $aboy = OrderAssignToDeliveryBoy::find($n->id);
            $ord = Order::find($aboy->orderId);
            if($ord->deliveryBoy!=null)
            {
               $aboy->boyDecision = 3;
               $aboy->updatedOn = date('Y-m-d H:i:s');
               $aboy->update();
            }
         }
      }
      return "done";
   }

   public function searchNearByDeliveryBoyFromVendor()
   {
    	$normal = $this->NormalOrdersAssigning();
    	$mis = $this->MisOrdersAssigning();
    	return "orders_".$normal."_".$mis;
   }

   private function NormalOrdersAssigning()
   {
    	
    	$orders = SearchBoyForOrderAssignCronJob::where([['searching_status',0],['type','=','normal']])->get();
    	//return $orders;

    	$d_boys = User::where([['user_role','DELIVERY_BOY'],['is_active',1]])->select('id','first_name','last_name','email','mobile','lat','lng','live_location')->whereNotNull('lat')->whereNotNull('lng')->get();
    	//return $d_boys;
    	foreach ($orders as $value) 
    	{
    		$v  = Vendor::find($value->vendorId);
    		$nearByBoys = array();

    		//update search status
    		$singleOrder = SearchBoyForOrderAssignCronJob::find($value->id);
    		$singleOrder->searching_status = 1;
    		$singleOrder->update();
    		//ends

    		//order status update
    		$ord = Order::find($value->orderId);
         if($ord->status == 12 || $ord->deliveryBoy!=null)
         {
            $singleOrder->searching_status = 2;
            $singleOrder->update();

            $ao = new OrderAssignToDeliveryBoy();
            $ao->orderId = $ord->id;
            $ao->boyId =  $ord->deliveryBoy;
            $ao->boyDecision = 1;
            $ao->cronjobId = 0;
            $ao->assignedBy = 'ADMIN';
            $ao->updatedOn = date('Y-m-d H:i:s');
            $ao->save();
         }else
         {
            $ord->status = 13;
            $ord->update();

            $checkOrderHistory = OrderUpdates::where([['orderId',$ord->id],['statusId',13]])->count();
             if($checkOrderHistory==0)
             {   
                 $order_status = new OrderUpdates();
                 $order_status->orderId = $ord->id;
                 $order_status->updatedBy = 'BOT';
                 $order_status->updatedById = 1;
                 $order_status->statusId = 13;
                 $order_status->status = 'Searching for Boy';
                 $order_status->createdOn = date('Y-m-d H:i:s');
                 $order_status->save();
             }

             //searching for appliance boys if near by
               //code here
             //ends

            foreach ($d_boys as $d_b) 
            {
               $distance = $this->distance(floatval($v->lat), floatval($v->lng), floatval($d_b->lat), floatval($d_b->lng),"K");

               if($distance <= '1.600')
               {  
                  $ongng_orders = Order::where([['deliveryBoy',$d_b->id],['createdOn', 'like','%'.date('Y-m-d').'%']])
                                       ->whereNotIn('status',[4,16])
                                       ->count();
                  if($ongng_orders <= '2')
                  {
                     array_push($nearByBoys, $d_b->id);
                  }
                  
               }
            }

            if(count($nearByBoys)>0)
            {  
               //assigning order to boys and sending notifications
               $this->assignOrdertoBoys($nearByBoys,$value);
               //update search status
               $singleOrder->searching_status = 2;
               $singleOrder->update();
               //ends
            }else{
               //update search status
               $singleOrder->searching_status = 0;
               $singleOrder->update();
               //ends
            }
         }
    		//ends

    	}
    	return "done";
   }

   private function MisOrdersAssigning()
   {
    	
    	$orders = SearchBoyForOrderAssignCronJob::where([['searching_status',0],['type','=','mis']])->get();

    	$d_boys = User::where([['user_role','DELIVERY_BOY'],['is_active',1]])->select('id','first_name','last_name','email','mobile','lat','lng','live_location')->whereNotNull('lat')->whereNotNull('lng')->get();
    	//return $d_boys;
    	foreach ($orders as $value) 
    	{
    		$v  = Vendor::find($value->vendorId);
    		$nearByBoys = array();

    		//update search status
    		$singleOrder = SearchBoyForOrderAssignCronJob::find($value->id);
    		$singleOrder->searching_status = 1;
    		$singleOrder->update();
    		//ends

    		//order status update
    		$ord = Order::find($value->orderId);
         if($ord->status == 12 || $ord->deliveryBoy!=null)
         {
            $singleOrder->searching_status = 2;
            $singleOrder->update();

            $ao = new OrderAssignToDeliveryBoy();
            $ao->orderId = $ord->id;
            $ao->boyId =  $ord->deliveryBoy;
            $ao->boyDecision = 1;
            $ao->cronjobId = 0;
            $ao->assignedBy = 'ADMIN';
            $ao->updatedOn = date('Y-m-d H:i:s');
            $ao->save();
         }else
         {
       		$ord->status = 13;
       		$ord->update();

            $checkOrderHistory = OrderUpdates::where([['orderId',$ord->id],['statusId',13]])->count();
             if($checkOrderHistory==0)
             {   
                 $order_status = new OrderUpdates();
                 $order_status->orderId = $ord->id;
                 $order_status->updatedBy = 'BOT';
                 $order_status->updatedById = 1;
                 $order_status->statusId = 13;
                 $order_status->status = 'Searching for Boy';
                 $order_status->createdOn = date('Y-m-d H:i:s');
                 $order_status->save();
             }
             
       		//ends
       		foreach ($d_boys as $d_b) 
       		{  
               //checking boy on gng orders count if orders < 4 then ww are assiging new orders
               
               $ongng_orders = Order::where([['deliveryBoy',$d_b->id],['createdOn', 'like','%'.date('Y-m-d').'%']])
                                       ->whereNotIn('status',[4,16])
                                       ->count();
               if($ongng_orders <= '2')
               {
                  array_push($nearByBoys, $d_b->id);
               }
               //array_push($nearByBoys, $d_b->id);
               //ends here
       		}

       		if(count($nearByBoys)>0)
       		{	
       			//assigning order to boys and sending notifications
       			$this->assignOrdertoBoys($nearByBoys,$value);

       			//update search status
       			$singleOrder->searching_status = 2;
       			$singleOrder->update();
       			//ends
       		}else{
       			//update search status
       			$singleOrder->searching_status = 0;
       			$singleOrder->update();
       			//ends
       		}
         }

    	}
    	return "done";
   }

   private function assignOrdertoBoys($nearByBoys,$value)
   {

      $boys_ids = array();
    	foreach ($nearByBoys as $boy) 
    	{   
         $assignedornot = OrderAssignToDeliveryBoy::where([['orderId',$value->orderId],['boyId',$boy]])->count();
         if($assignedornot==0)
         {
            $ao = new OrderAssignToDeliveryBoy();
            $ao->orderId = $value->orderId;
            $ao->boyId = $boy;
            $ao->boyDecision = 2;
            $ao->cronjobId = $value->id;
            $ao->assignedBy = 'BOT';
            $ao->updatedOn = date('Y-m-d H:i:s');
            $ao->save();
            //$receiver = User::find($boy);
            array_push($boys_ids,$boy);
         }
    	}

      //send fcm notifications to boys
      $notifyCtrl = new FCMNotificationController();
      $notifyCtrl->sendCronFCMNotificationToBoys($boys_ids,$value->orderId);
      //ends
      return "assigned";
   }

   private function distance($lat1, $lon1, $lat2, $lon2, $unit) //K,M,N 
   {
      // https://www.geodatasource.com/developers/php\

	   if (($lat1 == $lat2) && ($lon1 == $lon2)) {
	    	return 0;
	   }
	   else {
	    $theta = $lon1 - $lon2;
	    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	    $dist = acos($dist);
	    $dist = rad2deg($dist);
	    $miles = $dist * 60 * 1.1515;
	    $unit = strtoupper($unit);

	    if ($unit == "K") {
	      return round(($miles * 1.609344), 3);
	    } else if ($unit == "N") {
	      return ($miles * 0.8684);
	    } else {
	      return $miles;
	    }
	   }
	}

   //get today total report in excel file
   public function getTodayTotalReport($date)
   {

        $search_date = $date;//'2021-07-03';//date('Y-m-d'); //2021-04-03 local
        $vendorList = array();
        
        $orders = Order::where([['createdOn', 'like','%'.$search_date.'%']])->get();

        if(count($orders)==0)
        {
            return $this->sendResponse("Sorry, No orders on this date",null);
        }
        //return $orders;

        $a=array();$fp=array();$ip=array();
        foreach ($orders as $order) 
        {
            if($order->vendorId!=0)
            {
               $v = Vendor::find($order->vendorId);
               $order->vendor = $v;
            }else{
               $order->vendor = null;
            }
            array_push($vendorList,$order->vendorId);
            unset($order->vendorId);

            $order->orderBy = User::find($order->orderBy);
            $order->status = Status::find($order->status);
            $order->deliveryBoy = User::find($order->deliveryBoy);
            $order->category = Category::find($order->categoryId);
            $order->location = Location::find($order->locationId);
            unset($order->categoryId);
            unset($order->locationId);

            $o_updates = OrderUpdates::where('orderId',$order->id)->get();
            $htimes=array();
            foreach ($o_updates as $ou) 
            {  
               array_push($htimes,array("s"=>str_replace(" ", "_", $ou->status),"t"=>$ou->createdOn));
            }

            $text = '';
            $history = array();
            for ($i = 1; $i < count($htimes); $i++) {
               $text= $htimes[$i-1]['s'].' to '.$htimes[$i]['s'].' ---> '.$this->diffBtwTimesAsPerType2($htimes[$i]['t'], $htimes[$i-1]['t']);
               array_push($history,$text);
            }            
            $order->updatesHistory = $history;
            //return $order;

            //getting mis orders deliveryfee
            if($order->status->name=='Delivered' && $order->type!='normal')
            {
               array_push($a, ($order->deliveryFee!=null)?$order->deliveryFee:0);
               array_push($fp, ($order->finalPrice!=null)?$order->finalPrice:0);
               array_push($ip, ($order->itemsPrice!=null)?$order->itemsPrice:0);
            }
        }
        //return $fp;
        $mis_orders_d_fee = array_sum($a);
        $mis_fp = array_sum($fp);
        $mis_ip = array_sum($ip);

        //return $mis_fp;

        $vendorList = array_values(array_unique($vendorList));
        $vendorList = array_values(array_filter($vendorList, function($a) { return ($a !== 0); }));
        if(count($vendorList)>0)
        {
            $vendorList = $this->vendorReport($search_date,$vendorList);
        }
        return $this->downloadReport($search_date,$orders,$vendorList,$mis_orders_d_fee,$mis_fp,$mis_ip);
   }

   private function diffBtwTimesAsPerType2($start, $end) 
   {
      $datetime1 = new \DateTime($start);//'2020-11-24 13:21:21.0');//start time
      $datetime2 = new \DateTime($end);//'2020-11-24 13:19:28');//end time
      $interval = $datetime1->diff($datetime2);
      return $interval->format('%H hours %i mins %s secs'); //%d days 
      //00 years 0 months 0 days 08 hours 0 minutes 0 seconds
   }

   private function vendorReport($search_date,$vendor_list)
   {
         //counts fetching
         $order_counts = array();
         $getStatuses = Status::all();

         $main_v_data = array();

         $holeday_orderamount = array();
         $holeday_itemsprice = array();
         $holeday_commision = array();
         $holeday_discount = array();
         $holeday_deiveryfee = array();
         $holeday_vendorSettlement = array();
         $holeday_order_profit = array();
         $holeday_gst = array();
         $holeday_servicetax = array();

         foreach($vendor_list as $vendorId)
         {

            $v = Vendor::find($vendorId);

            $order_counts['total_Orders'] = Order::where([['vendorId',$vendorId],['createdOn', 'like','%'.$search_date.'%']])->count();

            foreach ($getStatuses as $stl) 
            {
               $order_counts[str_replace(" ","_",$stl->name)] = Order::where([['vendorId',$vendorId],['status',$stl->id],['createdOn', 'like','%'.$search_date.'%']])->count();
            }

            $totalOrdersPrice = array(); 
            $vendorItemsPrice = array(); 
            $deliveryFee = array(); 
            $discountPrice = array();
            $comission = array();
            $c =array();  
            $notDeliveredOrdersCommision = array();
            $notDeliveredOrdersDiscount = array();
            $notDeliveredOrdersAmountAfterCommisionDiscount = array();
            $tip = array();
            $gst = array(); 
            $servicetax = array(); 

            $orders = Order::where([['vendorId',$vendorId],['createdOn', 'like','%'.$search_date.'%']])
                              ->whereIn('status',[4,16])
                              ->orderBy('createdOn','desc')
                              ->get();

            foreach ($orders as $o) 
            {
               array_push($totalOrdersPrice, $o->finalPrice);
               array_push($vendorItemsPrice, $o->itemsPrice);
               array_push($gst, $o->gstTax);
               array_push($servicetax, $o->serviceTax);
               
               /*array_push($deliveryFee, $o->deliveryFee);
               array_push($discountPrice, $o->discountPrice);
               $c1 = ($o->itemsPrice*($o->apnachotu_commision/100));
               array_push($comission, $c1);*/

               if($o->status==4)
               {   
                  array_push($deliveryFee, $o->deliveryFee);
                  array_push($discountPrice, $o->discountPrice);
                  array_push($tip, $o->tip);

                  $c1 = ($o->itemsPrice*($o->apnachotu_commision/100));
                  array_push($comission, $c1);
               }else{
                  $c2 = ($o->itemsPrice*($o->apnachotu_commision/100));
                  array_push($notDeliveredOrdersCommision, $c2);

                  array_push($notDeliveredOrdersDiscount, $o->discountPrice);
                  $c2 = $c2 + $o->discountPrice;
                  $c3 = $o->itemsPrice - $c2;
                  array_push($notDeliveredOrdersAmountAfterCommisionDiscount, $c3);
               }
               $o->apnachotu_commision =  $o->apnachotu_commision.'%';
               
            }

            //return $comission;
            $totalOrdersPrice = array_sum($totalOrdersPrice);
            $vendorItemsPrice = array_sum($vendorItemsPrice);
            $notDeliveredOrdersCommision  = array_sum($notDeliveredOrdersCommision);
            $notDeliveredOrdersAmountAfterCommisionDiscount = array_sum($notDeliveredOrdersAmountAfterCommisionDiscount);
            $comission = array_sum($comission) + $notDeliveredOrdersCommision;
            $tip = array_sum($tip);
            $deliveryFee = array_sum($deliveryFee);
            $discountPrice = array_sum($discountPrice) + array_sum($notDeliveredOrdersDiscount);

            $finalVendorAmount = round($vendorItemsPrice - $comission - $discountPrice,2);
            $ac_profit = round($deliveryFee + $comission  - $notDeliveredOrdersAmountAfterCommisionDiscount,2);

            $gst = array_sum($gst);$servicetax = array_sum($servicetax);

            $order_counts['vendor'] = $v->name;
            $order_counts['settlements'] = array(
            "totalOrdersPrice"=> $totalOrdersPrice - $tip,
            "itemsPrice"=> $vendorItemsPrice,
            "deliveryFee"=> $deliveryFee,
            "discountPrice"=> $discountPrice,
            'comission' => round($comission, 2),
            "tip" => $tip,
            "finalVendorAmount"=> $finalVendorAmount,
            "apnachotuProfit"=> $ac_profit,
            "gst"=>$gst,
            "servicetax"=>$servicetax, 
            "currentPercentageToChotu"=>($v->percentage_to_chotu!=NULL)?$v->percentage_to_chotu.'%':'');

            array_push($holeday_orderamount,$totalOrdersPrice);
            array_push($holeday_itemsprice,$vendorItemsPrice);
            array_push($holeday_deiveryfee,$deliveryFee);
            array_push($holeday_discount,$discountPrice);
            array_push($holeday_commision,$comission);
            array_push($holeday_vendorSettlement,$finalVendorAmount);
            array_push($holeday_order_profit,$ac_profit);

            array_push($holeday_gst,$gst);
            array_push($holeday_servicetax,$servicetax);

            array_push($main_v_data,$order_counts);
         }

         $final_data = array("v_data"=>$main_v_data,
            "total_order_amount"=>array_sum($holeday_orderamount),
            "total_items_price"=>array_sum($holeday_itemsprice),
            "total_deiveryfee"=>array_sum($holeday_deiveryfee),
            "total_discount"=>array_sum($holeday_discount),
            "total_commision"=>array_sum($holeday_commision),
            "total_vendorSettlement"=>array_sum($holeday_vendorSettlement),
            "total_orders_profit"=>array_sum($holeday_order_profit),
            "total_gst"=>array_sum($holeday_gst),
            "total_servicetax"=>array_sum($holeday_servicetax)
         );
    
        return $final_data;
   }

   private function downloadReport($search_date,$data,$vendorList,$mis_orders_d_fee,$mis_fp,$mis_ip)
   {  

         $headers = array(
            'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Disposition' => 'attachment; filename=ReportMeta.csv',
            'Expires' => '0',
            'Pragma' => 'public',
         );
        
         $po_label = 'reports/'.$search_date.'-Report.xlsx';
         $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load("ReportMeta.xlsx");

         $styleArray = array(
                         'font'  => array(
                             'bold'  => true,
                             'color' => array('rgb' => '#FFFFFF'),
                             'size'  => 9,
                             'name'  => 'Verdana',
                             /*'startColor' => [
                                 'rgb' => 'FFFF00',
                             ]*/
                         ));


         if(count($data)>0)
         {    //MIS ORDERS
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->setActiveSheetIndex(0)->getStyle('A1:AA1')->getFill()->getStartColor()->setRGB('FABF8F')->applyFromArray($styleArray);
               foreach($data as $q)
               {
                    //$requestedOn = date("d-m-Y g:i A");
                  if($q->type=='mis')
                  {

                     $row = $objPHPExcel->getActiveSheet()->getHighestRow()+1;
                     $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, '#'.$q->id);

                     if($q->vendor!=null)
                     {
                        $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, $q->vendor->name);
                     }else{
                        $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, '');
                     }

                     $objPHPExcel->getActiveSheet()->setCellValue('C'.$row, $q->createdOn);
                     $objPHPExcel->getActiveSheet()->setCellValue('D'.$row, $q->updatedOn);
                     $objPHPExcel->getActiveSheet()->setCellValue('E'.$row, $q->type);
                     $objPHPExcel->getActiveSheet()->setCellValue('F'.$row, $q->location->name);
                     $objPHPExcel->getActiveSheet()->setCellValue('G'.$row, $q->orderBy->first_name.' '.$q->orderBy->last_name);
                     $objPHPExcel->getActiveSheet()->setCellValue('H'.$row, $q->orderBy->mobile);
                     $objPHPExcel->getActiveSheet()->setCellValue('I'.$row, $q->address);
                     $objPHPExcel->getActiveSheet()->setCellValue('J'.$row, $q->status->name);

                     $objPHPExcel->getActiveSheet()->setCellValue('K'.$row, $q->cancel_reason);
                     $objPHPExcel->getActiveSheet()->setCellValue('L'.$row, $q->delivery_time);

                     if($q->deliveryBoy!=null)
                     {  
                        $objPHPExcel->getActiveSheet()->setCellValue('M'.$row, $q->deliveryBoy->empId);
                        $objPHPExcel->getActiveSheet()->setCellValue('N'.$row, $q->deliveryBoy->first_name.' '.$q->deliveryBoy->last_name);
                        $objPHPExcel->getActiveSheet()->setCellValue('O'.$row, $q->deliveryBoy->mobile);
                     }else{
                        $objPHPExcel->getActiveSheet()->setCellValue('M'.$row, '');
                        $objPHPExcel->getActiveSheet()->setCellValue('N'.$row, '');
                        $objPHPExcel->getActiveSheet()->setCellValue('O'.$row, '');
                     }
                     
                     $objPHPExcel->getActiveSheet()->setCellValue('P'.$row, $q->category);

                     $objPHPExcel->getActiveSheet()->setCellValue('Q'.$row, $q->itemsPrice);
                     $objPHPExcel->getActiveSheet()->setCellValue('R'.$row, $q->deliveryFee);
                     $objPHPExcel->getActiveSheet()->setCellValue('S'.$row, $q->tip);
                     $objPHPExcel->getActiveSheet()->setCellValue('T'.$row, $q->discountPrice);
                     $objPHPExcel->getActiveSheet()->setCellValue('U'.$row, ($q->apnachotu_commision!=null)?$q->apnachotu_commision.'%':'');
                     $objPHPExcel->getActiveSheet()->setCellValue('V'.$row, $q->gstTax);
                     $objPHPExcel->getActiveSheet()->setCellValue('W'.$row, $q->serviceTax);
                     $objPHPExcel->getActiveSheet()->setCellValue('X'.$row, $q->finalPrice);
                     $objPHPExcel->getActiveSheet()->setCellValue('Y'.$row, $q->paymentMode);
                     $objPHPExcel->getActiveSheet()->setCellValue('Z'.$row, ($q->is_free_delivery==0)?'NO':'YES');
                     $objPHPExcel->getActiveSheet()->setCellValue('AA'.$row, $q->items_data);
                     $objPHPExcel->getActiveSheet()->setCellValue('AB'.$row, $q->extra_items);

                     //adding order history
                     $objPHPExcel->getActiveSheet()->setCellValue('AC'.$row, "");
                     foreach ($q->updatesHistory as $h) {
                        $prev_value = $objPHPExcel->getActiveSheet()->getCell('AC'.$row)->getValue();
                        $objPHPExcel->getActiveSheet()->setCellValue('AC'.$row, $prev_value.$h."\n");
                     }
                     $objPHPExcel->getActiveSheet()->setCellValue('AD'.$row, $this->diffBtwTwoDates($q->updatedOn,$q->createdOn));
                     //ends
                  }
                     
               } 
         }

         if(count($data)>0)
         {    //VENDOR ORDERS
            $objPHPExcel->setActiveSheetIndex(1);
            $objPHPExcel->setActiveSheetIndex(1)->getStyle('A1:AA1')->getFill()->getStartColor()->setRGB('FABF8F')->applyFromArray($styleArray);
               foreach($data as $q)
               {
                  if($q->type=='normal')
                  {

                     $row = $objPHPExcel->getActiveSheet()->getHighestRow()+1;
                     $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, '#'.$q->id);

                     if($q->vendor!=null)
                     {
                        $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, $q->vendor->name);
                     }else{
                        $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, '');
                     }

                     $objPHPExcel->getActiveSheet()->setCellValue('C'.$row, $q->createdOn);
                     $objPHPExcel->getActiveSheet()->setCellValue('D'.$row, $q->updatedOn);
                     $objPHPExcel->getActiveSheet()->setCellValue('E'.$row, $q->type);
                     $objPHPExcel->getActiveSheet()->setCellValue('F'.$row, $q->location->name);
                     $objPHPExcel->getActiveSheet()->setCellValue('G'.$row, $q->orderBy->first_name.' '.$q->orderBy->last_name);
                     $objPHPExcel->getActiveSheet()->setCellValue('H'.$row, $q->orderBy->mobile);
                     $objPHPExcel->getActiveSheet()->setCellValue('I'.$row, $q->address);
                     $objPHPExcel->getActiveSheet()->setCellValue('J'.$row, $q->status->name);

                     $objPHPExcel->getActiveSheet()->setCellValue('K'.$row, $q->cancel_reason);
                     $objPHPExcel->getActiveSheet()->setCellValue('L'.$row, $q->delivery_time);

                     if($q->deliveryBoy!=null)
                     {  
                        $objPHPExcel->getActiveSheet()->setCellValue('M'.$row, $q->deliveryBoy->empId);
                        $objPHPExcel->getActiveSheet()->setCellValue('N'.$row, $q->deliveryBoy->first_name.' '.$q->deliveryBoy->last_name);
                        $objPHPExcel->getActiveSheet()->setCellValue('O'.$row, $q->deliveryBoy->mobile);
                     }else{
                        $objPHPExcel->getActiveSheet()->setCellValue('M'.$row, '');
                        $objPHPExcel->getActiveSheet()->setCellValue('N'.$row, '');
                        $objPHPExcel->getActiveSheet()->setCellValue('O'.$row, '');
                     }
                     
                     $objPHPExcel->getActiveSheet()->setCellValue('P'.$row, $q->category);

                     $objPHPExcel->getActiveSheet()->setCellValue('Q'.$row, $q->itemsPrice);
                     $objPHPExcel->getActiveSheet()->setCellValue('R'.$row, $q->deliveryFee);
                     $objPHPExcel->getActiveSheet()->setCellValue('S'.$row, $q->tip);
                     $objPHPExcel->getActiveSheet()->setCellValue('T'.$row, $q->discountPrice);
                     $objPHPExcel->getActiveSheet()->setCellValue('U'.$row, ($q->apnachotu_commision!=null)?$q->apnachotu_commision.'%':'');
                     $objPHPExcel->getActiveSheet()->setCellValue('V'.$row, $q->gstTax);
                     $objPHPExcel->getActiveSheet()->setCellValue('W'.$row, $q->serviceTax);
                     $objPHPExcel->getActiveSheet()->setCellValue('X'.$row, $q->finalPrice);
                     $objPHPExcel->getActiveSheet()->setCellValue('Y'.$row, $q->paymentMode);
                     $objPHPExcel->getActiveSheet()->setCellValue('Z'.$row, ($q->is_free_delivery==0)?'NO':'YES');
                     $objPHPExcel->getActiveSheet()->setCellValue('AA'.$row, $q->items_data);
                     $objPHPExcel->getActiveSheet()->setCellValue('AB'.$row, $q->extra_items);

                     //adding order history
                     $objPHPExcel->getActiveSheet()->setCellValue('AC'.$row, "");
                     foreach ($q->updatesHistory as $h) {
                        $prev_value = $objPHPExcel->getActiveSheet()->getCell('AC'.$row)->getValue();
                        $objPHPExcel->getActiveSheet()->setCellValue('AC'.$row, $prev_value.$h."\n");
                     }
                     $objPHPExcel->getActiveSheet()->setCellValue('AD'.$row, $this->diffBtwTwoDates($q->updatedOn,$q->createdOn));
                     //ends

                  }
                     
               } 
         }

         //VENDOR SETTLEMENTS
         if(count($vendorList['v_data'])>0)
         {
            $objPHPExcel->setActiveSheetIndex(2);
            $objPHPExcel->setActiveSheetIndex(2)->getStyle('A1:P1')->getFill()->getStartColor()->setRGB('FABF8F')->applyFromArray($styleArray);
               foreach ($vendorList['v_data'] as $v) 
               {  
                   $row = $objPHPExcel->getActiveSheet()->getHighestRow()+1;
                   $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, $v['vendor']);
                   $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, $search_date);
                   $objPHPExcel->getActiveSheet()->setCellValue('C'.$row, $v['total_Orders']);
                   $objPHPExcel->getActiveSheet()->setCellValue('D'.$row, $v['Confirmed']);
                   $objPHPExcel->getActiveSheet()->setCellValue('E'.$row, $v['Cancelled']);
                   $objPHPExcel->getActiveSheet()->setCellValue('F'.$row, $v['Delivered']);
                   $objPHPExcel->getActiveSheet()->setCellValue('G'.$row, $v['Out_Of_Stock']);
                   $objPHPExcel->getActiveSheet()->setCellValue('H'.$row, $v['User_Location_Reached_but_not_delivered_to_User']);

                   $objPHPExcel->getActiveSheet()->setCellValue('I'.$row, $v['settlements']['totalOrdersPrice']);
                   $objPHPExcel->getActiveSheet()->setCellValue('J'.$row, $v['settlements']['itemsPrice']);
                   $objPHPExcel->getActiveSheet()->setCellValue('K'.$row, $v['settlements']['deliveryFee']);
                   $objPHPExcel->getActiveSheet()->setCellValue('L'.$row, $v['settlements']['discountPrice']);
                   $objPHPExcel->getActiveSheet()->setCellValue('M'.$row, $v['settlements']['comission']);
                   $objPHPExcel->getActiveSheet()->setCellValue('N'.$row, $v['settlements']['gst']);
                   $objPHPExcel->getActiveSheet()->setCellValue('O'.$row, $v['settlements']['servicetax']);
                   $objPHPExcel->getActiveSheet()->setCellValue('P'.$row, $v['settlements']['finalVendorAmount']);
                   $objPHPExcel->getActiveSheet()->setCellValue('Q'.$row, $v['settlements']['apnachotuProfit']);
                   $objPHPExcel->getActiveSheet()->setCellValue('R'.$row, $v['settlements']['currentPercentageToChotu']);
               }
         }


         //TOTAL TRANSACTIONS
         $objPHPExcel->setActiveSheetIndex(3);
         $objPHPExcel->setActiveSheetIndex(3)->getStyle('A1:I1')->getFill()->getStartColor()->setRGB('FABF8F')->applyFromArray($styleArray);
         
         $row = $objPHPExcel->getActiveSheet()->getHighestRow()+1;
         $objPHPExcel->getActiveSheet()->setCellValue('A'.$row, $vendorList['total_order_amount']+$mis_fp);
         $objPHPExcel->getActiveSheet()->setCellValue('B'.$row, $vendorList['total_items_price']+$mis_ip);
         $objPHPExcel->getActiveSheet()->setCellValue('C'.$row, $vendorList['total_deiveryfee']);
         $objPHPExcel->getActiveSheet()->setCellValue('D'.$row, $mis_orders_d_fee);
         $objPHPExcel->getActiveSheet()->setCellValue('E'.$row, $mis_orders_d_fee+$vendorList['total_deiveryfee']);
         $objPHPExcel->getActiveSheet()->setCellValue('F'.$row, $vendorList['total_discount']);
         $objPHPExcel->getActiveSheet()->setCellValue('G'.$row, $vendorList['total_commision']);
         $objPHPExcel->getActiveSheet()->setCellValue('H'.$row, $vendorList['total_vendorSettlement']+$mis_ip);
         $objPHPExcel->getActiveSheet()->setCellValue('I'.$row, $vendorList['total_gst']);
         $objPHPExcel->getActiveSheet()->setCellValue('J'.$row, $vendorList['total_servicetax']);
         $objPHPExcel->getActiveSheet()->setCellValue('K'.$row, $vendorList['total_orders_profit']+$mis_orders_d_fee);
         $objPHPExcel->setActiveSheetIndex(0);
         //ends
          
         $objWriter = new Xlsx($objPHPExcel);
         $objWriter->save($po_label);

         return Response::download($po_label);
    
   }

   private function diffBtwTwoDates($start, $end) 
  {

      //$end  = date("Y-m-d H:i:s", strtotime($end));

      $datetime1 = new \DateTime($start);//'2020-11-24 13:21:21.0');//start time
      $datetime2 = new \DateTime($end);//'2020-11-24 13:19:28');//end time
      $interval = $datetime1->diff($datetime2);
      return $interval->format('%d days %H hours %i mins %s secs');
      //00 years 0 months 0 days 08 hours 0 minutes 0 seconds
  }

}

