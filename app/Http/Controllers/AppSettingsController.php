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
use App\Models\ItemGroup;
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
ini_set('memory_limit','1024M');
ini_set('max_execution_time', 1800);

class AppSettingsController extends Controller
{
    
    /*public function __construct()
    {
     $this->middleware('jwt.auth', ['except' => []]);
    }*/



    public function dataImport(Request $request)
    {   
        //file extension validations based on image
        $validator = Validator::make($request->all(),['file' => 'required','id' => 'required']);
        if ($validator->fails()) {
            return $this->sendBadException(implode(',',$validator->errors()->all()),null);
        }

        $vendor = $request->id;

        $data = json_decode(file_get_contents($request->file('file')));
        foreach ($data as $value) {
             //return $value->courseType;
            foreach ($value->courseType as $group) {
                    $grp = new ItemGroup();
                    $grp->name=$group;
                    $grp->vendorId=$vendor;
                    $grp->createdOn=date('Y-m-d H:i:s');
                    $grp->is_active=1;
                    $grp->save();
            }

            foreach ($value->menu as $item) {
                    $it = new Item();
                    $it->name=trim($item->name);
                    $it->price=trim($item->price);
                    $it->rating=trim($item->rating);
                    $it->ratingCount=trim($item->ratingCount);
                    $it->orderPrice=trim($item->orderPrice);
                    $it->vendorId=$vendor;
                    $it->type=trim($item->type);
                    $it->count=trim($item->count);

                    $itemGrpId = ItemGroup::where([['vendorId',$vendor],['name',$value->courseType[(int)$item->courseType-1]]])->first();
                    $it->item_groupId=$itemGrpId->id;

                    $it->createdOn=date('Y-m-d H:i:s');
                    $it->is_active=1;
                    $it->save();
            }
        }
        return $this->sendResponse("data imported..!",null);  
    }

}
