<?php

use App\Http\Controllers\Backend\DashboardController;
use Backend\PermissionController;
use Backend\RackController;
use Backend\UserController;
use Backend\SettingController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // return view('welcome');
    return redirect('login');
});


Auth::routes();

Route::get('clear-cache', function () {
    Artisan::call('optimize');
    echo 'Optimize successfully';
});
Route::get('migrate', function () {
    echo Artisan::call('migrate');
    echo 'All migration run successfully';
});
Route::get('cache-cleared', function () {
    echo Artisan::call('cache:forget spatie.permission.cache');
    echo Artisan::call('cache:clear');
    echo 'cache cleared';
});

// Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth','track']], function() {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('home');
    // Route::resource('roles', RoleController::class);
    Route::get('user/viewProfile/{id}', 'Backend\UserController@viewProfile')->name('user.viewProfile');
    Route::resource('users', UserController::class);
    Route::post('users/upload_profile', [App\Http\Controllers\Backend\UserController::class , 'upload_profile']);


    Route::resource('permission', PermissionController::class);
    Route::resource('rack', RackController::class);
    Route::get('issue_rack', [App\Http\Controllers\Backend\RackController::class , 'issue_rack'])->name('issue_rack');
    Route::get('issue_rack_list', [App\Http\Controllers\Backend\RackController::class , 'issue_rack'])->name('issue_rack_list');
    Route::post('issue_rack_to_shop', [App\Http\Controllers\Backend\RackController::class , 'issue_rack_to_shop'])->name('issue_rack_to_shop');
    Route::get('user/profileEdit', 'Backend\UserController@profileEdit')->name('user.profileEdit');
    // Route::resource('products', ProductController::class);

    Route::group(['namespace' => 'Backend','prefix'=>'product'],function () {
        Route::resource('scheme_product', 'SchemeProductController');
        Route::post('scheme_product_active/{id}', 'SchemeProductController@scheme_product_active')->name('scheme_product.active');
        Route::post('scheme_product_inactive/{id}', 'SchemeProductController@scheme_product_inactive')->name('scheme_product.inactive');
	    Route::get('/get_scheme_product', 'SchemeProductController@get_scheme_product')->name('get_scheme_product');
        Route::resource('category', 'CategoryController');
        Route::resource('type', 'ProductTypeController');
        Route::resource('brand', 'BrandController');
	    Route::resource('uom', 'UOMController');
	    Route::resource('product', 'ProductController');
	    Route::get('/import_product', 'ProductController@import_product')->name('product.import_product');
	    Route::post('/import_product_store', 'ProductController@import_product_store')->name('product.import_product_store');
	    Route::get('/ProductMasterData', 'ProductController@ProductMasterData');
        Route::get('/topRank','UserController@topRank')->name('topRank');
        Route::get('/dashboarData','DashboardController@dashboarData')->name('dashboarData');
    });

    Route::group(['namespace' => 'Backend', 'prefix'=>'tso'],function () {
        Route::get('viewProfile/{id}', 'TSOController@viewProfile')->name('tso.viewProfile');
        Route::resource('tso', 'TSOController');
        Route::post('tso_active/{id}', 'TSOController@tso_active')->name('tso.active');
        Route::post('tso_inactive/{id}', 'TSOController@tso_inactive')->name('tso.inactive');

        Route::get('tso_status_request', 'TSOController@tso_status_request')->name('tso.status_request');
        Route::post('tso_status_request_post', 'TSOController@tso_status_request_post')->name('tso.status_request_post');


        Route::get('ImportTSO', 'TSOController@ImportTSO')->name('tso.ImportTSO');
	    Route::post('import_tso_store', 'TSOController@import_tso_store')->name('tso.import_tso_store');


        Route::get('activity', 'TSOController@activity')->name('activity');
        Route::get('import_shops/{id}', 'TSOController@import_shop')->name('tso.import_shops');
        Route::post('import_shops_insert', 'TSOController@import_shops_insert')->name('tso.import_shops_insert');
        Route::get('tso_log', 'TSOController@tso_log')->name('tso_log');
        Route::resource('tso-target', 'TSOTargetController');
        // Route::get('tso-target', 'TSOController');

        Route::get('addAttendence','TSOTargetController@addAttendence')->name('addAttendence');
        Route::get('attendenceList','TSOTargetController@attendenceList')->name('attendenceList');
    });
    Route::group(['namespace' => 'Backend', 'prefix'=>'kpo'],function () {
        Route::resource('sale', 'SaleOrderController');
        // Route::delete('sale/{id}' , 'SaleOrderController@destroy' )->name('sale.destroy');
        Route::resource('receipt-voucher', 'ReceiptVoucherController');
        Route::get('product-table-row/{product_id}', 'SaleOrderController@productTableRow')->name('sale-order.table-row');
        Route::get('get-tso-by-distributor', 'SaleOrderController@getTsoByDistributor')->name('tso.by.distributor');
        Route::get('get-shop-by-tso', 'SaleOrderController@getShopByTso')->name('shop.by.tso');

    });

    Route::group(['namespace' => 'Backend', 'prefix'=>'kpo'],function () {
        Route::resource('sales_return', 'SalesReturnController');
        Route::get('sales_return_list/{excution?}', 'SalesReturnController@sales_return_list')->name('sales_return.sales_return_list');
        Route::post('sales_return_execution_submit', 'SalesReturnController@sales_return_execution_submit')->name('sales_return.sales_return_execution_submit');
        Route::get('getSoData', 'SalesReturnController@getSoData')->name('sales_return.getSoData');
        Route::get('viewPaymentRecoveryDetail/{id}', 'SalesReturnController@viewPaymentRecoveryDetail')->name('sales_return.viewPaymentRecoveryDetail');

    });

    Route::group(['namespace' => 'Backend', 'prefix'=>'execution'],function () {
        Route::get('sale-order-execution', 'ExecutionController@indexSaleOrder')->name('sale-order.execution.index');
        Route::get('sale-order-execution/{product_id}', 'ExecutionController@saleOrderExecution')->name('sale-order.execution');
        Route::post('sale-order-execution-bulk', 'ExecutionController@bulkSaleOrderExecution')->name('sale-order.execution.bulk');
        Route::get('payment-recovery-execution', 'ExecutionController@IndexPaymentRecovery')->name('payment-recovery.execution.index');
        Route::get('payment-recovery-execution/{id}', 'ExecutionController@paymentRecoveryExecution')->name('payment-recovery.execution');
        Route::post('payment-recovery-execution-bulk', 'ExecutionController@bulkPaymentRecoveryExecution')->name('payment-recovery.execution.bulk');
        Route::get('bill_printing', 'ExecutionController@bill_printing')->name('execution.bill_printing');
        Route::post('multi_so_view', 'ExecutionController@multi_so_view')->name('execution.multi_so_view');
    });
    Route::group(['namespace' => 'Backend', 'prefix'=>'settings'],function () {
        Route::resource('designation', 'DesignationController');
        Route::resource('department', 'DepartmentController');
        Route::resource('role', 'RoleController');

        Route::get('config', 'SettingController@config')->name('config.index');
        Route::post('config_store', 'SettingController@config_store')->name('config.store');

    });

    Route::group(['namespace' => 'Backend','prefix'=>'shop'],function () {
        Route::resource('shop','ShopController');
        Route::get('export-shops', 'ShopController@exportShops')->name('export.shops');

        Route::post('shop_active/{id}', 'ShopController@shop_active')->name('shop.active');
        Route::get('shop_status_request', 'ShopController@shop_status_request')->name('shop.status_request');
        Route::post('shop_status_request_post', 'ShopController@shop_status_request_post')->name('shop.status_request_post');
        Route::post('shop_inactive/{id}', 'ShopController@shop_inactive')->name('shop.inactive');
        Route::resource('shoptype', 'ShopTypeController');
	    Route::resource('priceType', 'PriceTypeController');
	    Route::get('get_shop_by_route', 'ShopController@get_shop_by_route')->name('get_shop_by_route');
        Route::get('get_shop_by_tso', 'ShopController@get_shop_by_tso')->name('get_shop_by_tso');
	    Route::get('shopVisitList', 'ShopController@shopVisitList')->name('shop.shopVisitList');
	    Route::get('ImportShop', 'ShopController@ImportShop')->name('shop.ImportShop');
	    Route::post('import_shops_store', 'ShopController@import_shops_store')->name('shop.import_shops_store');

    });
    Route::group(['namespace' => 'Backend','prefix'=>'distributor'],function () {
	    Route::resource('/distributor', 'DistributorController');
	    Route::resource('zone', 'ZoneController');
	    Route::get('ImportDistributor', 'DistributorController@ImportDistributor')->name('distributor.ImportDistributor');
	    Route::post('import_distributors_store', 'DistributorController@import_distributors_store')->name('distributor.import_distributors_store');
	    Route::resource('stockManagement', 'StockManagementController');
	    Route::get('importStock', 'StockManagementController@importStock')->name('stockManagement.importStock');
	    Route::get('import_stock_list', 'StockManagementController@import_stock_list')->name('stockManagement.import_stock_list');
	    Route::post('uploadStockFIle', 'StockManagementController@uploadStockFIle')->name('stockManagement.uploadStockFIle');

	    Route::get('getDistributorByCity', 'DistributorController@getDistributorByCity')->name('distributor.getDistributorByCity');

    });

    Route::group(['namespace' => 'Backend','prefix'=>'stock'],function () {
        Route::resource('stock', 'StockTransferController');
    });

    Route::group(['namespace' => 'Backend','prefix'=>'route'],function () {
        Route::resource('route', 'RouteController');
        Route::get('route_log', 'RouteController@route_log')->name('route_log');
        Route::get('route_transfer', 'RouteController@route_transfer')->name('route.transfer');
        Route::put('route_transfer_store', 'RouteController@route_transfer_store')->name('route.transfer_store');
        Route::get('TSODayWisePlanner', 'RouteController@TSODayWisePlanner')->name('route.TSODayWisePlanner');
        Route::get('GetTsoByDistributor', 'RouteController@GetTsoByDistributor')->name('route.GetTsoByDistributor');
        Route::get('GetAllTsoByDistributor', 'RouteController@GetAllTsoByDistributor')->name('route.GetAllTsoByDistributor');
        Route::get('GetTsoByMultipleDistributor', 'RouteController@GetTsoByMultipleDistributor')->name('route.GetTsoByMultipleDistributor');
        Route::get('GetRouteBYTSO', 'RouteController@GetRouteBYTSO')->name('route.GetRouteBYTSO');
        Route::get('get_sub_route', 'RouteController@get_sub_route')->name('route.get_sub_route');
        Route::put('route_tso_wise', 'RouteController@route_tso_wise')->name('route.route_tso_wise');

	    Route::get('get_distributor_by_city', 'RouteController@get_distributor_by_city')->name('route.get_distributor_by_city');

    });
    Route::group(['namespace' => 'Backend','prefix'=>'subroutes'],function () {
        Route::resource('subroutes', 'SubRouteController');

    });

    Route::group(['namespace' => 'Backend','prefix'=>'report'],function () {


        Route::get('shop_ledger_report', 'ReportController@shop_ledger_report')->name('shop_ledger_report');
        Route::get('receipt_voucher_summary', 'ReportController@receipt_voucher_summary')->name('receipt_voucher_summary');
        Route::get('order_summary', 'ReportController@order_summary')->name('order_summary');
        Route::get('order_list', 'ReportController@order_list')->name('order_list');
        Route::get('product_avail', 'ReportController@product_avail')->name('product_avail');
        Route::get('product_productivity', 'ReportController@product_productivity')->name('product_productivity');
        Route::get('load_Sheet', 'ReportController@load_Sheet')->name('load_Sheet');
        Route::get('order_vs_execution', 'ReportController@order_vs_execution')->name('order_vs_execution');
        Route::get('tso_target','ReportController@tso_target')->name('tso_target');
        Route::get('racks_report','ReportController@racks_report')->name('racks.report');
        Route::get('scheme_product','ReportController@scheme_product')->name('scheme_product');
        Route::get('attendence_report','ReportController@attendence_report')->name('attendence_report');
        Route::get('attendence_report_detail','ReportController@attendence_report_detail')->name('attendence_report_detail');
        Route::get('day_wise_attendence_report','ReportController@day_wise_attendence_report')->name('day_wise_attendence_report');

        Route::get('item_wise_sale','ReportController@item_wise_sale')->name('item_wise_sale');
        Route::get('stock_report','ReportController@stock_report')->name('stock_report');


        Route::get('top_tso_report/{id?}','ReportController@top_tso_report')->name('top_tso_report');
        Route::get('top_product_report/{id?}','ReportController@top_product_report')->name('top_product_report');
        Route::get('top_distributor_report/{id?}','ReportController@top_distributor_report')->name('top_distributor_report');
        Route::get('top_shop_report/{id?}','ReportController@top_shop_report')->name('top_shop_report');
        Route::get('sales_report','ReportController@sales_report')->name('sales_report');
        Route::get('unit_sold_report','ReportController@unit_sold_report')->name('unit_sold_report');
        Route::get('top_shop_balance_report','ReportController@top_shop_balance_report')->name('top_shop_balance_report');

    });


    Route::group(['namespace' => 'Backend','prefix'=>'opening'],function () {

        Route::get('add_opening_form', 'OpeningController@add_opening_form')->name('add_opening_form');
        Route::post('insert_opening', 'OpeningController@insert_opening')->name('insert_opening');

    });

    Route::get('/notification/redirect/{id}', [DashboardController::class, 'notification_redirect'])->name('notification.redirect');


});
