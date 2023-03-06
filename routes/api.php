<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
| PROD-SERVER- 139.59.45.85
*/

Route::post('test-fcm', 'FCMNotificationController@TestFCM');

//cron apis
Route::get('updateVendorsOpenClose', 'VendorController@updateVendorsOpenClose');
Route::get('searchNearByDeliveryBoyFromVendor', 'CronJobController@searchNearByDeliveryBoyFromVendor');
Route::get('insertCommisionIfNull', 'CronJobController@insertCommisionIfNull');
Route::get('updateBoyDecisionsFromCronIfBotNotWorkSometimes', 'CronJobController@updateBoyDecisionsFromCronIfBotNotWorkSometimes');

Route::get('updatePrimeCustomersData', 'CronJobController@updatePrimeCustomersData');
Route::post('testPrimeCustomersData', 'CronJobController@testPrimeCustomersData');


Route::group(['prefix' => 'v1/'], function () { //,'middleware' => ['api_logs']

    Route::get('getTodayTotalReport/{date}', 'CronJobController@getTodayTotalReport');

    Route::group(['prefix' => 'user/'], function () 
    {
        Route::post('signup', 'UserController@signup');
        Route::post('login', 'UserController@login');
        Route::get('logout', 'UserController@logout');
        Route::post('liveLocation', 'UserController@saveUserLiveLocation');
        Route::put('deviceToken/add', 'UserController@addDeviceToken');

        Route::get('profile/{id?}', 'UserController@getAuthenticatedUser')->where(['id' => '[0-9]+']);
        Route::put('profileUpdate', 'UserController@authenticatedUserProfileUpdate');
        Route::post('profileImageUpload', 'UserController@userImageUpdate');
        Route::post('mobile/verify', 'UserController@mobileVerify');
        Route::post('mobile/verifyAdmin', 'UserController@mobileVerifyEncryptOTP');
        Route::post('checkUserExistedOrNot', 'UserController@checkUserExistedOrNot');
        Route::put('change_role', 'UserController@changeUserRole');
        Route::GET('partners', 'UserController@getPartners');

        //pswd related apis
        /*Route::post('changePwd', 'UserController@changePassword');
        Route::post('forgotPwd', 'UserController@forgotPwd');
        Route::post('resetPwd', 'UserController@resetPwd');
        Route::post('checkResetToken', 'UserController@checkResetToken');*/

        //saved items apis
        /*Route::post('savedItems', 'LikeCommentController@getUserSavedItems');
        Route::delete('savedItems', 'LikeCommentController@deleteSavedItems');*/


        //notifications
        /*Route::group(['prefix' => 'notification/','middleware' => ['jwt.auth']], function () {
            Route::post('readOrUnread', 'NotificationConroller@readOrUnreadNotifications');
            Route::put('markAllReadOrDelete', 'NotificationConroller@markAllReadOrDelete');
            Route::post('list', 'NotificationConroller@userNotificationsList');
            Route::post('stopFromUser', 'NotificationConroller@stopNotificationsFromUser');
        });*/
         //user addresses
        Route::group(['prefix' => 'address/','middleware' => ['jwt.auth']], function () {
            Route::post('add', 'UserAddressController@addAddress');
            Route::get('get_all', 'UserAddressController@getAddress');
            Route::get('single/{id}', 'UserAddressController@singleAddress');
            Route::put('update', 'UserAddressController@updateAddress');
            Route::delete('delete/{id}', 'UserAddressController@deleteAddress');
        });

    });

    Route::group(['prefix' => 'meta/'], function () 
    {
        Route::get('{name}/{id?}', 'MetaController@getMetaData');

        Route::group(['prefix' => 'crud/'], function () 
        {
            Route::post('categories/create', 'MetaController@createCategories');
            Route::put('categories/edit', 'MetaController@updateCategories');
            //Route::delete('categories/delete', 'MetaController@getMetaData');

            Route::post('sub_categories/create', 'MetaController@createSubCategories');
            Route::put('sub_categories/edit', 'MetaController@updateSubCategories');

            Route::post('tags/create', 'MetaController@createTag');
            Route::put('tags/edit', 'MetaController@updateTag');

            Route::post('offer/create', 'MetaController@createOffer');
            Route::put('offer/update', 'MetaController@updateOffer');
            Route::delete('offer/delete/{id}', 'MetaController@deleteOffer');
        });
    });

    Route::group(['prefix' => 'vendors/'], function () {
        Route::post('getall', 'VendorController@getVendors');
        Route::get('get/{id}', 'VendorController@singleVendor');
        Route::post('create', 'VendorController@createVendor');
        Route::put('edit', 'VendorController@editVendor');
        Route::delete('delete/{id}', 'VendorController@deleteVendor');
        Route::post('imageUpload', 'VendorController@vendorImageUpdate');
        Route::post('orders', 'OrderController@getVendorOrders');
        Route::post('update_status', 'VendorController@vendorUpdateStatus');
        Route::post('displaySequence', 'VendorController@displaySequence');
        Route::post('report', 'VendorController@vendorReport');
    });

    Route::post('vendorOffer/create', 'MetaController@createVendorOffer');
    Route::get('vendorOffer/all/{id}', 'MetaController@getAllVendorOffers');
    Route::delete('vendorOffer/remove/{id}', 'MetaController@deleteVendorOffer');

    Route::group(['prefix' => 'announcement/'], function () {
        Route::get('getall', 'MetaController@getAnnouncements');
        Route::get('get/{id}', 'MetaController@getSingleAnnouncement');
        Route::post('create', 'MetaController@createAnnouncement');
        Route::delete('delete/{id}', 'MetaController@deleteAnnouncement');
    });

    Route::group(['prefix' => 'item_group/'], function () {
        Route::get('getall/{vendorId}', 'ItemGroupController@getVendorGroups');
        Route::get('get/{id}', 'ItemGroupController@getGroup');
        Route::post('create', 'ItemGroupController@createGroup');
        Route::put('edit', 'ItemGroupController@updateGroup');
    });

    Route::group(['prefix' => 'item/'], function () {
        Route::get('getall/{groupId}', 'ItemGroupController@getItemsFromGroup');
        Route::get('get/{id}', 'ItemGroupController@getItem');
        Route::post('create', 'ItemGroupController@createItem');
        Route::post('create/meatVeg', 'ItemGroupController@createItemForMeatVeg');
        Route::put('update/meatVeg', 'ItemGroupController@updateItemForMeatVeg');
        Route::put('edit', 'ItemGroupController@updateItem');
        Route::post('imageUpload', 'ItemGroupController@itemImageUpdate');
    });

    Route::group(['prefix' => 'order/'], function () {
        Route::post('getall', 'OrderController@getUserOrders');
        Route::get('get/{id}', 'OrderController@getOrder');
        Route::post('create', 'OrderController@createOrder');
        Route::post('mis-create', 'OrderController@misOrderCreate');
        Route::put('update', 'OrderController@updateOrder');
        Route::put('cancel', 'OrderController@cancelOrder');
        Route::put('payment_update', 'OrderController@updateOrderPaymentFromUser');
    });

    Route::get('statuses', 'OrderController@getStatus');

    Route::group(['prefix' => 'delivery_boy/'], function () {
        Route::post('acceptOrRejectOrder', 'DeliveryBoyController@boyAcceptOrRejectOrder');
        Route::post('getOrders', 'DeliveryBoyController@getBoyOrders');
        Route::post('onlineStatusUpdate', 'DeliveryBoyController@saveBoyActiveStatus');
        Route::post('orderfeedback/get', 'DeliveryBoyController@getOrderFeedback');
        Route::put('orderfeedback/update', 'DeliveryBoyController@updateOrderFeedback');
        Route::post('login_history', 'DeliveryBoyController@boyLoginHistory');
        Route::get('notifications/{version?}', 'DeliveryBoyController@boyNotifications');
        Route::post('order_settlements', 'DeliveryBoyController@getBoyOrderSettlements');
        Route::post('check_version', 'DeliveryBoyController@check_version');

        Route::get('wallet/get/{id}', 'DeliveryBoyController@getWallet');
        Route::post('wallet/create', 'DeliveryBoyController@createWallet');
        Route::post('wallet/requestForApproval', 'DeliveryBoyController@WalletRequestForApproval');
        Route::get('wallet/paid_requests', 'DeliveryBoyController@getWalletPaidRequestForAdminApproval');
        Route::post('wallet/approve', 'DeliveryBoyController@approveWallet');
    });

    // For Admin Dashboard Apis
	Route::prefix('/admin')->group(base_path('routes/admin.php'));

    //Restarents data import
    Route::post('dataImport', 'AppSettingsController@dataImport');

}); //ends v1


/*sample email sending function*/
Route::get('send-email', function () {
        $data = array('otp' => "1234");
        Mail::send('emails.forgotpwd',$data, function ($message) {
            $message->from('noreply.apnachotu@gmail.com', 'Apnachotu');
            $message->to('sheshukurnool@gmail.com')->subject('Mobile Verify OTP from APNACHOTU');
        });
    if (Mail::failures()) {
        return "email sending failure";
    }
    return "Your email has been sent successfully";
});

