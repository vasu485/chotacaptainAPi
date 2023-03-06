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
use App\Models\ItemGroup;
use App\Models\Item;
use DB;
use JWTAuth;
use Exception;
use Storage;
use Validator;
use Tymon\JWTAuthExceptions\JWTException;
date_default_timezone_set('Asia/Kolkata');
ini_set('max_execution_time', 300);

class ItemGroupController extends Controller
{ 
    public function __construct()
    {
        $this->middleware('jwt.auth', ['except' => ['']]);
    }

    /* items group apis start */
    public function createGroup(Request $request)
    {
		 //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string','vendorId' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $itemgrp = new ItemGroup();
        $itemgrp->name      = trim($request->name);
        $itemgrp->vendorId      = trim($request->vendorId);
        $itemgrp->is_active = true;
        $itemgrp->createdOn = date('Y-m-d H:i:s');
        $itemgrp->save();

        return $this->sendResponse("ItemGroup saved successfully...!",null);
    }

    public function getGroup($id)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $ItemGroup = ItemGroup::find($id);
        if($ItemGroup==null){
            return $this->sendBadException('ItemGroup not found',null);
        }
        $ItemGroup->is_active=($ItemGroup->is_active==1)?true:false;

        return $this->sendResponse("ItemGroup data...!",$ItemGroup);
    }

    public function getVendorGroups($id)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $ItemGroup = ItemGroup::where('vendorId',$id)->get();

        foreach ($ItemGroup as $value) {
            $value->is_active=($value->is_active==1)?true:false;
        }

        return $this->sendResponse("Vendor itemGroup data...!",$ItemGroup);
    }

    public function updateGroup(Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['id' =>'required|numeric','name' => 'required|string','vendorId' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $itemgrp = ItemGroup::find($request->id);
        if($itemgrp==null){
            return $this->sendBadException('ItemGroup not found',null);
        }

        $itemgrp->name      = trim($request->name);
        $itemgrp->vendorId      = trim($request->vendorId);
        $itemgrp->is_active = ($request->is_active==true)?true:false;;
        $itemgrp->save();

        return $this->sendResponse("ItemGroup updated successfully...!",null);
    }

    /* items apis start */
    public function createItem(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string','vendorId' => 'required|numeric','price' => 'required','item_groupId' => 'required|numeric','rating' => 'required','type' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $g = ItemGroup::find($request->item_groupId);
        if($g==null){
            return $this->sendBadException('ItemGroup not found',null);
        }

        $item = new Item();
        $item->name      = trim($request->name);
        $item->vendorId      = trim($request->vendorId);
        $item->item_groupId      = trim($request->item_groupId);
        $item->price      = trim($request->price);
        $item->rating      = trim($request->rating);
        $item->type      = trim($request->type);
        $item->is_active = true;
        $item->createdOn = date('Y-m-d H:i:s');
        $item->save();

        $data = array('itemId'=>$item->id);

        $request->vendorId=$g->vendorId;

        $this->updateNewItemPriceBasedOnVendorOriginalItemTax($request);

        return $this->sendResponse("Item saved successfully...!",$data);
    }

    public function createItemForMeatVeg(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string','vendorId' => 'required|numeric','price_quantity' => 'required|Array','item_groupId' => 'required|numeric','rating' => 'required','type' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $g = ItemGroup::find($request->item_groupId);
        if($g==null){
            return $this->sendBadException('ItemGroup not found',null);
        }

        $item = new Item();
        $item->name      = trim($request->name);
        $item->vendorId      = trim($request->vendorId);
        $item->item_groupId      = trim($request->item_groupId);
        $item->price      = 0;
        $item->rating      = trim($request->rating);
        $item->type      = trim($request->type);
        $item->is_active = true;
        $item->createdOn = date('Y-m-d H:i:s');
        $item->price_quantity  = json_encode($request->price_quantity);
        $item->save();

        $data = array('itemId'=>$item->id);

        $request->vendorId=$g->vendorId;

        $this->updateNewItemPriceBasedOnVendorOriginalItemTax($request);

        return $this->sendResponse("Item saved successfully...!",$data);
    }

    public function updateItem(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['id' => 'required|numeric','name' => 'required|string','vendorId' => 'required|numeric','price' => 'required','item_groupId' => 'required|numeric','rating' => 'required','type' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $g = ItemGroup::find($request->item_groupId);
        if($g==null){
            return $this->sendBadException('ItemGroup not found',null);
        }

        $item = Item::find($request->id);
        if($item==null){
            return $this->sendBadException('Item not found',null);
        }
        $item->name      = trim($request->name);
        $item->vendorId      = trim($g->vendorId);
        $item->item_groupId      = trim($request->item_groupId);
        $item->price      = trim($request->price);
        $item->rating      = trim($request->rating);
        $item->type      = trim($request->type);
        $item->is_active = ($request->is_active==true)?true:false;
        $item->update();

        $request->vendorId=$g->vendorId;

        $this->updateNewItemPriceBasedOnVendorOriginalItemTax($request);

        return $this->sendResponse("Item updated successfully...!",null);
    }

    public function updateItemForMeatVeg(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['id' => 'required|numeric','name' => 'required|string','vendorId' => 'required|numeric','price_quantity' => 'required|Array','item_groupId' => 'required|numeric','rating' => 'required','type' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }

        $g = ItemGroup::find($request->item_groupId);
        if($g==null){
            return $this->sendBadException('ItemGroup not found',null);
        }

        $item = Item::find($request->id);
        if($item==null){
            return $this->sendBadException('Item not found',null);
        }
        $item->name      = trim($request->name);
        $item->vendorId      = trim($request->vendorId);
        $item->item_groupId      = trim($request->item_groupId);
        $item->price_quantity      = json_encode($request->price_quantity);
        $item->rating      = trim($request->rating);
        $item->type      = trim($request->type);
        $item->is_active = ($request->is_active==true)?true:false;
        $item->update();

        $request->vendorId=$g->vendorId;
        
        $this->updateNewItemPriceBasedOnVendorOriginalItemTax($request);

        return $this->sendResponse("Item updated successfully...!",null);
    }

    public function getItem($id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $item = Item::find($id);
        if($item==null){
            return $this->sendBadException('Item not found',null);
        }
        $item->is_active=($item->is_active==1)?true:false;
        if($item->price_quantity!=null)
        {
            $item->price_quantity = json_decode($item->price_quantity);
        }else{
            $item->price_quantity = null;
        }

        $item->image = ($item->image!=null)?request()->getSchemeAndHttpHost().'/images/items/'.$item->image:null;

        return $this->sendResponse("Item data...!",$item);
    }

    public function getItemsFromGroup($id)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $itemGroup = ItemGroup::find($id);
        if($itemGroup==null){
            return $this->sendBadException('ItemGroup not found',null);
        }
        //$itemGroup->is_active=($itemGroup->is_active==1)?true:false;

        $items = Item::where('item_groupId',$id)->get();
        foreach ($items as $value) {
            if($value->price_quantity!=null)
            {
                $value->price_quantity=json_decode($value->price_quantity);
            }else{
                $value->price_quantity= null;
            } 
            $value->image = ($value->image!=null)?request()->getSchemeAndHttpHost().'/images/items/'.$value->image:null;
        }

        return $this->sendResponse("Vendor itemGroup data...!",array("itemGroup"=>$itemGroup,"items"=>$items));
    }

    /**
     * itemImageUpdate
     * @return upload pic response
     */
    public function itemImageUpdate(Request $request)
    {   
        //file extension validations based on image
        $validator = Validator::make($request->all(),
                            ['image' => 'required|mimes:jpeg,png,jpg|max:10000','image.required' => 'Please upload an image',
                             'image.mimes' => 'Only jpeg,png,jpg images are allowed',
                             'image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                             'itemId'=> 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $item = Item::find($request->itemId);
        if($item==null)
        {
            return $this->sendBadException('item not found',null);
        }

        //auth user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to add image.",'data' => null],401);
        }

        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $destination_file_name = time().".".$extension;

        if($extension==null || $extension=="" || $extension==" ")
        {
            return $this->sendBadException('Image properties are not valid',null);
        }

        $fileName = sha1(date('YmdHis') . str_random(30)).$file->getClientOriginalName();
        $destinationPath = public_path().'/images/items/' ;
        $file->move($destinationPath,$fileName);

        
        $item->image = $fileName;
        $item->update();  

        return $this->sendResponse("Image updated..!",array("url"=>request()->getSchemeAndHttpHost().'/images/items/'.$fileName));  
    }


    // update new price based on new tax from vendor cron job 
    public function updateNewItemPriceBasedOnVendorOriginalItemTax(Request $request)
    {

        $v = Vendor::find($request->vendorId);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }
        $percentage = $v->original_item_tax;
        //return  $percentage;
        /*if($percentage==null){
            return $this->sendBadException('Sorry,Cant update item prices, because vendor original_item_tax is zero',null);
        }*/

        $grps = ItemGroup::where('vendorId',$v->id)->get();
        if(count($grps)==0){
            return $this->sendBadException('ItemGroup not found',null);
        }

        foreach($grps as $g)
        {
            $items = Item::where([['item_groupId',$g->id],['vendorId',$v->id]])->get();
            
            if(count($items)>0)
            {
                foreach($items as $item)
                {
                    $item = Item::find($item->id);

                    if($item->price!=0 && $item->price_quantity==null)
                    {   
                        // pencentage calculator start 
                        if($percentage!=0)
                        {
                            $new_updated_price = $this->priceCalculationFromTax($percentage,$item->price);
                        }else{
                            $new_updated_price = $item->price;
                        }

                        //return $new_updated_price;
                           
                           $item->updated_item_price      = (int)trim($new_updated_price);
                           $percentage_amount = round(($percentage / 100) * $item->price);
                        // ends

                        // price history update 
                            $exist_history = ($item->item_price_history==null)?[]:json_decode($item->item_price_history);

                            $item_price_history = array('price'=>$item->price,'tax_percentage'=>$percentage,'tax_percentage_amount'=>$percentage_amount,'new_updated_price'=>$new_updated_price,'updatedOn'=>date('Y-m-d H:i:s'));

                            array_push($exist_history,$item_price_history);

                            $item->item_price_history = json_encode($exist_history);
                            $item->updatedOn = date('Y-m-d H:i:s');
                            $item->update();
                        // ends

                    }
                    elseif ($item->price==0 && $item->price_quantity!=null) 
                    {
                       $price_quantity = json_decode($item->price_quantity);
                       $exist_history = ($item->item_price_history==null)?[]:json_decode($item->item_price_history);
                       $updatedItems = array();
                       foreach($price_quantity as $itemPrice)
                       {

                        // pencentage calculator start
                            if($percentage!=0)
                            {
                                $new_updated_price = $this->priceCalculationFromTax($percentage,$itemPrice->price);
                            }else{
                                $new_updated_price = $itemPrice->price;
                            }

                           //$new_updated_price = $this->priceCalculationFromTax($percentage,$itemPrice->price);
                           $itemPrice->updated_item_price      = (int)trim($new_updated_price);
                           $percentage_amount = round(($percentage / 100) * $itemPrice->price);
                        // ends

                        // price history update 
                            $item_price_history = array('price'=>$itemPrice->price,'quantity'=>$itemPrice->quantity,'tax_percentage'=>$percentage,'tax_percentage_amount'=>$percentage_amount,'new_updated_price'=>$new_updated_price,'updatedOn'=>date('Y-m-d H:i:s'));

                            array_push($exist_history,$item_price_history);
                            array_push($updatedItems,$itemPrice);
                        // ends
                       } 
                       $item->item_price_history = json_encode($exist_history);  
                       $item->price_quantity = json_encode($updatedItems); 
                       $item->updatedOn = date('Y-m-d H:i:s');
                       $item->update(); 
                    }

                }
            }
        }

        return $this->sendResponse("Item1 prices updated successfully...!",null);
    }

    private function priceCalculationFromTax($percentage,$itemPrice)
    {

        $new_round_value = round($itemPrice + ($percentage / 100) * $itemPrice);
        //echo "new price with round : ".$new_round_value."<br>";

        $lastDigit = $new_round_value % 10;
        //echo "last digit : ".$lastDigit."<br>";

        if($lastDigit!=0 && $lastDigit!=5)
        {
            $add = $this->addDigitbasedonLastDigit($lastDigit); 
        }else{
            $add = 0; 
        }
        

        //echo "add to new price : ".$add."<br>";

        $new_price = $new_round_value + $add;

        //echo "new item price with adding tax and round : ".$new_price;

        return $new_price;

    }

    private function addDigitbasedonLastDigit($var2)
    {
          switch ($var2) 
          {
            case ($var2 == 1 || $var2 == 6):
                $var3 = 4;
                break;
            case ($var2 == 2 || $var2 == 7):
                $var3 = 3;
                break;
            case ($var2 == 3 || $var2 == 8):
                $var3 = 2;
                break;
            case ($var2 == 4 || $var2 == 9):
                $var3 = 1;
                break;
            default:
                $var3 = 0;
                break;
          }
          return $var3;
    }

}
