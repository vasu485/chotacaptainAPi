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
use App\Models\Notification;
use App\Models\Location;
use App\Models\Offer;
use App\Models\VendorOffer;
use App\Models\Announcement;
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

class MetaController extends Controller
{
    
    public function __construct()
    {
       $this->middleware('jwt.auth', ['except' => ['getMetaData','getAnnouncements','getSingleAnnouncement','getTermsConditions']]);
    }


    /*
    * get meta data
    */
    public function getMetaData($name,$id=null)
    {
		switch ($name) {
		    case "categories":
		        $meta = Category::whereNotin('id',[2])->get();
		        return $this->sendResponse("Categories",$meta);
		        break;
		    case "categories_p":
		        $meta = Category::where('is_popular',1)->get();
		        return $this->sendResponse("Popular Categories",$meta);
		        break;
		    case "sub_categories":
		        $meta = SubCategory::where('categoryId',$id)->get();
		        return $this->sendResponse("Sub Categories",$meta);
		        break;
		    case "tags":
		        $meta = VendorTags::all();
		        return $this->sendResponse("Tags",$meta);
		        break;
            case "locations":
                $meta = Location::where('is_active',1)->get();
                return $this->sendResponse("Locations",$meta);
                break;
            case "offers":
                $meta = Offer::pluck('categoryId')->toArray();
                $meta = array_values(array_unique($meta));
                $catArray = array();
                foreach ($meta as $value) {
                    $cat = Category::where('id',$value)->select('id','name as group','is_popular')->first();
                    $cat['items'] = Offer::where('categoryId',$value)->select('id','title','description','categoryId')->get();
                    array_push($catArray, $cat);
                }
                return $this->sendResponse("Offers",$catArray);
                break;
		    default:
		        return $this->sendBadException('Invalid data',null);
		}
    }

    public function createCategories(Request $request)
    {
		 //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string','icon' => 'required|string','is_popular'=>'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }

        $cat = new Category();
        $cat->name      = trim($request->name);
        $cat->icon      = trim($request->icon);
        $cat->is_popular = ($request->is_popular==true)?true:false;
        $cat->createdOn = date('Y-m-d H:i:s');
        $cat->save();

        return $this->sendResponse("Category saved successfully...!",null);
    }

    public function updateCategories(Request $request)
    {
    	//start validations
        $validator = Validator::make($request->all(), ['id' =>'required|numeric','name' => 'required|string','is_popular'=>'required|boolean']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $cat = Category::find($request->id);
        if($cat==null){
            return $this->sendBadException('Category not found',null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update.",'data' => null],401);
        }

        if(isset($request->name) && $request->name!=null){
            $cat->name = trim($request->name);
        }
        if(isset($request->icon) && $request->icon!=null){
            $cat->icon = trim($request->icon);
        }
        $cat->is_popular = ($request->is_popular==true)?true:false;
        $cat->createdOn = date('Y-m-d H:i:s');
        $cat->update();

        return $this->sendResponse("Category updated successfully...!",null);
    }

    public function createSubCategories(Request $request)
    {
		 //start validations
        $validator = Validator::make($request->all(), ['categoryId' => 'required|numeric','name' => 'required|string','type' => 'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }

        $catfind = Category::find($request->categoryId);
        if($catfind==null){
            return $this->sendBadException('Category not found',null);
        }

        $cat = new SubCategory();
        $cat->name      = trim($request->name);
        $cat->categoryId      = trim($request->categoryId);
        $cat->type      = trim($request->type);
        $cat->createdOn = date('Y-m-d H:i:s');
        $cat->save();

        return $this->sendResponse("Sub Category saved successfully...!",null);
    }

    public function updateSubCategories(Request $request)
    {
    	//start validations
        $validator = Validator::make($request->all(), ['id' =>'required|numeric','categoryId' => 'required|numeric','name' => 'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $category = Category::find($request->categoryId);
        if($category==null){
            return $this->sendBadException('Category not found',null);
        }

        $sub_category = SubCategory::find($request->id);
        if($sub_category==null){
            return $this->sendBadException('SubCategory not found',null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update.",'data' => null],401);
        }

        $sub_category->name      = trim($request->name);
        $sub_category->categoryId      = trim($request->categoryId);
        $sub_category->type      = trim($request->type);
        $sub_category->createdOn = date('Y-m-d H:i:s');
        $sub_category->update();

        return $this->sendResponse("SubCategory updated successfully...!",null);
    }

    public function createTag(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['name' => 'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }

        $tag = new VendorTags();
        $tag->name      = trim($request->name);
        $tag->save();

        return $this->sendResponse("Tag saved successfully...!",null);
    }

    public function updateTag(Request $request)
    {
        //start validations
        $validator = Validator::make($request->all(), ['id' =>'required|numeric','name' => 'required|string']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $tag = VendorTags::find($request->id);
        if($tag==null){
            return $this->sendBadException('Tag not found',null);
        }

        //logged user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to update.",'data' => null],401);
        }

        if(isset($request->name) && $request->name!=null){
            $tag->name = trim($request->name);
        }
        $tag->update();

        return $this->sendResponse("Tag updated successfully...!",null);
    }

    public function createOffer(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['title' => 'required|string','description' => 'required|string','categoryId' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised to create.",'data' => null],401);
        }

        $cat = Category::find($request->categoryId);
        if($cat==null){
            return $this->sendBadException('Category not found',null);
        }

        $offer = new Offer();
        $offer->title      = trim($request->title);
        $offer->description      = trim($request->description);
        $offer->categoryId      = trim($request->categoryId);
        $offer->save();

        return $this->sendResponse("Offer created successfully...!",null);
    }

    public function updateOffer(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['id' => 'required|numeric','title' => 'required|string','description' => 'required|string','categoryId' => 'required|numeric']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $cat = Category::find($request->categoryId);
        if($cat==null){
            return $this->sendBadException('Category not found',null);
        }

        $offer = Offer::find($request->id);
        $offer->title      = trim($request->title);
        $offer->description      = trim($request->description);
        $offer->categoryId      = trim($request->categoryId);
        $offer->update();

        return $this->sendResponse("Offer Updated successfully...!",null);
    }

    public function deleteOffer($id)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }
        $offer = Offer::destroy($id);
        return $this->sendResponse("Offer Deleted successfully...!",null);
    }

    public function createVendorOffer(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['minOrder' => 'required','offerPercentage' => 'required','vendorId' => 'required|numeric','maxOfferAmount' => 'required']);
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

        $vo = VendorOffer::where([['vendorId',$v->id],['is_active',1]])->count();
        if($vo>0){
            return $this->sendResponse('Vendor Offer already created',null);
        }

        $offer = new VendorOffer();
        $offer->minOrder      = trim($request->minOrder);
        $offer->offerPercentage      = trim($request->offerPercentage);
        $offer->maxOfferAmount      = trim($request->maxOfferAmount);
        $offer->vendorId      = trim($request->vendorId);
        $offer->is_active      = true;
        $offer->createdOn      = date('Y-m-d H:i:s');
        $offer->save();
        return $this->sendResponse("Vendor Offer created successfully...!",null);
    }

    public function getAllVendorOffers($id)
    {

        $user = JWTAuth::parseToken()->authenticate();

        $v = Vendor::find($id);
        if($v==null){
            return $this->sendBadException('Vendor not found',null);
        }
        $voffers = VendorOffer::where('vendorId',$v->id)->orderByRaw("createdOn DESC, is_active DESC")->get();
        return $this->sendResponse("Vendor Offers",$voffers);
    }

    public function deleteVendorOffer($id)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $offer = VendorOffer::find($id);
        if($offer==null){
            return $this->sendBadException('VendorOffer not found',null);
        }
        $offer->is_active      = false;
        $offer->update();
        return $this->sendResponse("Vendor Offer deleted successfully...!",null);
    }

    public function createAnnouncement(Request $request)
    {   
        //file extension validations based on image
        /*$validator = Validator::make($request->all(),
                            ['image' => 'required|mimes:jpeg,png,jpg|max:10000','image.required' => 'Please upload an image',
                             'image.mimes' => 'Only jpeg,png,jpg images are allowed',
                             'image.max' => 'Sorry! Maximum allowed size for an image is 10MB',
                             'title'=> 'required','description'=> 'required']);*/
        $validator = Validator::make($request->all(),
                            ['title'=> 'required','description'=> 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        //auth user
        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $ann = new Announcement();
        $ann->title = $request->title;
        $ann->description = $request->description;

        $file = $request->file('image');
        if(isset($file))
        {
            $extension = $file->getClientOriginalExtension();
            $destination_file_name = time().".".$extension;

            if($extension==null || $extension=="" || $extension==" ")
            {
                return $this->sendBadException('Image properties are not valid',null);
            }

            $fileName = sha1(date('YmdHis') . str_random(30)).$file->getClientOriginalName();
            $destinationPath = public_path().'/images/annoucement/' ;
            $file->move($destinationPath,$fileName);

            $ann->image = '/images/annoucement/'.$fileName;
        }

        $ann->createdOn      = date('Y-m-d H:i:s');
        $ann->save();  

        return $this->sendResponse("Announcement Created Successfully..!",null);  
    }

    public function getAnnouncements()
    {
        $v = Announcement::orderBy('createdOn','desc')->get();
        foreach ($v as $value) {
            $value->image=($value->image!=null)?request()->getSchemeAndHttpHost().$value->image:null;
        }
        return $this->sendResponse("Announcements",$v);
    }

    public function getSingleAnnouncement($id)
    {   
        $value = Announcement::find($id);
        if($value==null){
            return $this->sendBadException('Announcement not found',null);
        }

        $value->image=($value->image!=null)?request()->getSchemeAndHttpHost().$value->image:null;

        return $this->sendResponse("Single Announcement",$value);
    }

    public function deleteAnnouncement($id)
    {

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role=='USER'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised.",'data' => null],401);
        }

        $value = Announcement::find($id);
        if($value==null){
            return $this->sendBadException('Announcement not found',null);
        }
        $value->delete();
        return $this->sendResponse("Announcement deleted successfully...!",null);
    }

    public function getTermsConditions()
    {
        $t = TermsCondition::first();
        return $this->sendResponse("getTermsConditions",$t);
    }

    public function updateTermsConditions(Request $request)
    {
         //start validations
        $validator = Validator::make($request->all(), ['id' => 'required|numeric','heading' => 'required|string','content' => 'required','tax' => 'required','gstTax'=>'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }
        //ends validations

        $user = JWTAuth::parseToken()->authenticate();
        if($user->user_role!='ADMIN' && $user->user_role!='EDITOR' && $user->user_role!='OPERATOR'){
            return response()->json(["status"=>"error","message"=>"Sorry, You are not authorised",'data' => null],401);
        }

        $cat = TermsCondition::find($request->id);
        if($cat==null){
            return $this->sendBadException('TermsCondition not found',null);
        }

        $cat->heading      = trim($request->heading);
        $cat->content      = trim($request->content);
        $cat->tax          = trim($request->tax);
        $cat->gstTax          = trim($request->gstTax);
        $cat->updatedOn    = date('Y-m-d H:i:s');
        $cat->update();

        return $this->sendResponse("TermsCondition Updated Successfully...!",null);
    }


}
