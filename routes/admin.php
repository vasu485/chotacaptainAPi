<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
|
*/

Route::get('/', function () {
    return "<center><h1><b>Welcome Admin</b></h1></center>";
});

Route::group([], function ()  //'middleware' => ['']
{
    /*  Auth Apis */
    Route::post('login', 'Admin\AdminController@login');

    Route::post('pushGroupFCMNotifications', 'FCMNotificationController@pushGroupFCMNotifications');
    Route::post('add_primecustomer', 'Admin\AdminController@add_primecustomer');

	Route::post('getAllOrders', 'Admin\AdminController@getAllOrders');
	Route::post('miscSettlementOrders', 'Admin\AdminController@getParticularDayMiscSettlementOrders');
	
	/* vendors */
	Route::post('vendor/currentDaySettlementOrders', 'Admin\AdminController@getCurrentDaySettlementOrders');
	Route::post('vendor/makeSettlementPayment', 'Admin\AdminController@vendorMakeSettlementPayment');
	Route::post('vendor/getAllSettlements', 'Admin\AdminController@getAllSettlements');
	Route::post('vendor/settlements', 'Admin\AdminController@getSingleVendorSettlements');

	/* delivery boys */
	Route::get('getBoys', 'Admin\AdminController@getAllDeliveryBoys');
	Route::get('getBoys/{id}', 'Admin\AdminController@getAllDeliveryBoys');
	Route::get('deliveryBoy/single/{id}', 'Admin\AdminController@getSingleDeliveryBoy');
	Route::put('deliveryBoy/edit', 'Admin\AdminController@editDeliveryBoy');

	Route::post('deliveryBoy/currentDaySettlementOrders', 'Admin\AdminController@getBoyCurrentDaySettlementOrders');
	Route::post('deliveryBoy/betweenDatesSettlementOrders', 'Admin\AdminController@getBoyBetweenDatesSettlementOrders');
	Route::post('deliveryBoy/makeSettlementPayment', 'Admin\AdminController@boyMakeSettlementPayment');
	Route::post('deliveryBoy/getAllSettlements', 'Admin\AdminController@getBoysAllSettlements');
	Route::post('deliveryBoy/settlements', 'Admin\AdminController@getSingleBoySettlements');
	Route::post('deliveryBoy/addCommision', 'Admin\AdminController@addDeliveryBoyCommision');
	Route::post('deliveryBoy/getCommisions', 'Admin\AdminController@getDeliveryBoyCommisions');

	/* locations */
	Route::get('locations', 'Admin\AdminController@getLocations');
	Route::get('partnerlocations', 'Admin\AdminController@getPartnerLocations');
	Route::put('locations/update', 'Admin\AdminController@updateLocations');
	Route::post('locations/create', 'Admin\AdminController@createLocations');
	Route::post('Partnerlocations/create', 'Admin\AdminController@createPartner');

	Route::post('delivery_boys/currentOrdersStatus', 'Admin\AdminController@deliveryBoysCurrentOrdersStatus');

	Route::get('terms_conditions/get', 'MetaController@getTermsConditions');
	Route::put('terms_conditions/update', 'MetaController@updateTermsConditions');

	Route::post('getUserOrdersHistory', 'OrderController@getUserOrdersHistory');

	Route::post('updateNewItemPriceBasedOnVendorOriginalItemTax', 'ItemGroupController@updateNewItemPriceBasedOnVendorOriginalItemTax');

	Route::get('paymentMethods', 'Admin\AdminController@getPaymentMethods');
	Route::put('paymentMethods/edit', 'Admin\AdminController@editPaymentMethods');
    
});
