<?php

namespace App\Helpers;

use App\Models\Distributor;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductFlavour;
use App\Models\UOM;
use App\Models\TSO;
use App\Models\User;
use App\Models\Zone;
use App\Models\ShopType;
use App\Models\Shop;
use App\Models\Config;
use App\Models\Type;
use App\Models\Stock;
use App\Models\Route;
use App\Models\Rack;
use App\Models\ActivityLog;
use App\Models\UsersLocation;
use App\Models\SalesReturnData;
use App\Models\SaleOrder;
use App\Models\SaleOrderData;
use App\Models\ReceiptVoucher;
use App\Models\City;
use App\Models\UsersDistributors;
use App\Models\SubRoutes;
use Spatie\Permission\Models\Role;
use DB;
use Illuminate\Support\Facades\Storage;
use PDF;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class MasterFormsHelper
{


    public function __construct()
    {
    }
    public static function changeDateFormat($param1)
    {
        $date = date_create($param1);
        return date_format($date, "d-m-Y");
    }
    public static function changeDateFormat2($param1 , $format)
    {
        $date = date_create($param1);
        return date_format($date, $format);
    }
    public static function userType()
    {
        return Type::where('type', '!=', 'TSO')->get();
    }
    public function Days()
    {
        return $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
    }

    public static function get_all_distributors()
    {
        return Distributor::status()->get();
    }
    public static function get_distributor_name($id)
    {
        return Distributor::where('id' , $id)->status()->value('distributor_name');
    }
    public static function get_all_designation()
    {
        return DB::table('designations')->select('id', 'name')
            ->where('status', 1)
            ->get();
    }

    public function get_distributor_by_city($city_ids)
    {
        $distributor_ids = self::get_all_distributor_user_wise_pluck();
        return Distributor::status()
        ->whereIn('id',$distributor_ids)
        ->when($city_ids , function($q) use ($city_ids)
        {
            // $q->whereIn('city_id', $city_ids);
            if (is_array($city_ids)) {
                $q->whereIn('city_id', $city_ids);
            } else {
                $q->where('city_id', $city_ids);
            }

        })->get();
    }

    public static function get_all_distributor_user_wise()
    {
        return User::find(Auth::user()->id)->distributors()->select('distributors.id', 'distributor_name', 'distributor_code', 'city', 'contact_person', 'phone', 'distributor_sub_code', 'status')->sort()->get();
    }

    public static function get_all_distributor_user_wise_pluck()
    {
        return User::find(Auth::user()->id)->distributors()->pluck('distributors.id');
    }
    public function get_all_tso()
    {
        return TSO::all();
    }

    public function get_all_tso_by_distributor_id($id , $active = true)
    {
        $data = TSO::status();
        if ($active) {
            $data = $data->Active();
        }
        $data = $data->whereHas('UserDistributor', function ($query) use ($id) {
            $query->where('distributor_id', $id)
                ->groupBy('user_id');
        })->get();
        return  $data;

        // return $tso = TSO::whereHas()->status()->whereIn('distributor_id',$id)->select('name','id')->get();

    }

    public function get_all_tso_by_distributor_ids($id)
    {
        // dd($id);
        return  TSO::status()->whereHas('UserDistributor', function ($query) use ($id) {
            $query->whereIn('distributor_id', $id)
                ->groupBy('user_id');
        })->get();

        // return $tso = TSO::whereHas()->status()->whereIn('distributor_id',$id)->select('name','id')->get();

    }

    public static function get_all_product()
    {
        return Product::status()->get();
    }
    public static function get_product_by_id($id)
    {
        return Product::status()->where('id' , $id)->first();
    }
    public static function get_product_name_by_id($id)
    {
        return Product::status()->where('id' , $id)->value('product_name');
    }
    public static function get_all_sheme_product()
    {
        return Product::where('status', 1)->where('product_type_id', 2)->get();
    }
    public function get_all_route_by_tso($id)
    {
        $tso = [];
        if ($id != null) :
            $tso = TSO::find($id)->route()->where('status', 1)->select('day', 'id', 'route_name')->get();
        endif;
        return $tso;
    }

    public function get_all_routes()
    {

        $routes = Route::status()->get();
        return $routes;
    }
    public function get_all_sub_routes_by_route($id)
    {

        $sub_routes = SubRoutes::status()->where('route_id',$id)->get();
        return $sub_routes;
    }

    public function get_all_zone()
    {
        return  Zone::status()->get();
    }

    public function get_all_racks()
    {
        return  Rack::status()->get();
    }

    public function get_all_shop_type()
    {
        return  ShopType::status()->get();
    }
    public function shop_type_name($id)
    {
        return  ShopType::status()->where('id' , $id)->value('shop_type_name');
    }

    public function getReturnQty($id)
    {
        return SalesReturnData::where('sales_order_data_id', $id)->sum('qty');
    }

    public function get_distributor_level_wise()
    {
        return Distributor::status()
            ->where('level3', 0)
            ->orderBy('level1', 'ASC')
            ->orderBy('level2', 'ASC')
            ->get();
    }



    public static function InStock($product_id, $distributor, $qty)
    {
        $data = false;
        $in =  Stock::whereIn('stock_type', [0 ,1, 2, 4])->where('status', 1)->where('product_id', $product_id)->where('distributor_id', $distributor)->sum('qty');
        $out =  Stock::whereIn('stock_type', [3,5])->where('status', 1)->where('product_id', $product_id)->where('distributor_id', $distributor)->sum('qty');
        $qty = $in - $out - $qty;

        if ($qty >= 0) :
            $data = true;
        endif;
        return $data;
    }

    public static function qtyInStock($product_id, $distributor, $qty)
    {
        $data = false;
        $in =  Stock::whereIn('stock_type', [0, 1, 2, 4])->where('status', 1)->where('product_id', $product_id)->where('distributor_id', $distributor)->sum('qty');
        $out =  Stock::whereIn('stock_type', [3,5])->where('status', 1)->where('product_id', $product_id)->where('distributor_id', $distributor)->sum('qty');
        $qty = $in - $out - $qty;

        return $qty;
    }

    public static function get_Stock_opening($product_id, $flavour_id , $uom_id , $distributor , $from)
    {
        $data = false;
        $in =  Stock::whereIn('stock_type', [0,1,2,4,6])->where('status', 1)
        ->where('voucher_date' , '<', $from)
        ->where('product_id', $product_id)->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)
        ->when($distributor != null, function($q) use ($distributor){
            $q->where('distributor_id', $distributor);
        })->sum('qty');
        $out =  Stock::whereIn('stock_type', [3,5,7])->where('status', 1)
        ->where('voucher_date' , '<', $from)
        ->where('product_id', $product_id)->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)
        ->when($distributor != null, function($q) use ($distributor){
            $q->where('distributor_id', $distributor);
        })->sum('qty');
        $qty = $in - $out;

        return $qty;
    }

    public static function get_InStock($product_id, $flavour_id , $uom_id ,$distributor , $qty)
    {
        $data = false;
        $in =  Stock::whereIn('stock_type', [0,1,2,4,6])->where('status', 1)->where('product_id', $product_id)->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)->where('distributor_id', $distributor)->sum('qty');
        $out =  Stock::whereIn('stock_type', [3,5,7])->where('status', 1)->where('product_id', $product_id)->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)->where('distributor_id', $distributor)->sum('qty');
        $qty = $in - $out - $qty;

        if ($qty >= 0) :
            $data = true;
        endif;
        return $data;
    }
    public static function get_Stock($product_id, $flavour_id , $uom_id ,$distributor)
    {
        $data = false;
        $in =  Stock::whereIn('stock_type', [0,1,2,4,6])->where('status', 1)->where('product_id', $product_id)->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)
        ->when($distributor != null , function($q) use ($distributor) {
            $q->where('distributor_id', $distributor);
        })
        ->sum('qty');
        $out =  Stock::whereIn('stock_type', [3,5,7])->where('status', 1)->where('product_id', $product_id)->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)
        ->when($distributor != null , function($q) use ($distributor) {
            $q->where('distributor_id', $distributor);
        })
        ->sum('qty');
        $qty = $in - $out;

        return $qty;
    }

    public static function get_Stock_by_stock_type($product_id, $flavour_id , $uom_id ,$distributor , $stock_type , $from = null , $to = null)
    {
        $data = false;
        $qty =  Stock::whereIn('stock_type', $stock_type)->where('status', 1)->where('product_id', $product_id)
        ->where('flavour_id' , $flavour_id)->where('uom_id' , $uom_id)
        ->when($distributor != null , function($q) use ($distributor) {
            $q->where('distributor_id', $distributor);
        })
        ->when($from != null && $to != null, function($q) use ($from , $to){
            $q->whereBetween('voucher_date', [$from, $to]);
        })
        ->sum('qty');

        return $qty;
    }
    public static function get_stock_type_wise_data($product_id, $flavour_id ,$distributor , $stock_type)
    {
        $main_qty = '';
        $main_amount = 0;
        foreach (self::get_product_price($product_id) as $k => $productPrice) {
            $qty = self::get_Stock_by_stock_type($product_id, $flavour_id , $productPrice->uom_id , $distributor , $stock_type);

            $uom_name = self::uom_name($productPrice->uom_id); // Get UOM name for each product_price UOM
            if ($qty > 0) {
                $main_qty .= ($main_qty ? ' , ' : '') . number_format($qty) . 'x' . $uom_name;
                $value = $qty * $productPrice->trade_price;
                $main_amount += $value;
            }
        }

        return ['main_qty' => $main_qty , 'main_amount' => $main_amount];

    }

    public static function get_users_distributors($id)
    {
        return User::find($id)->distributors()->pluck('distributor_id');
    }
    public  function get_assign_user()
    {
        $distributors = $this->get_users_distributors(Auth::user()->id);
        return UsersDistributors::whereIn('distributor_id',$distributors)->groupBy('user_id')->pluck('user_id');
    }

    public function get_tso_distribuor_wise()
    {

        return  TSO::whereIn('user_id', $this->get_assign_user())->Status()->get();
    }
    public static function get_tso_name($id)
    {
        return TSO::where('id' , $id)->status()->value('name');
    }
    public function get_route_distribuor_wise()
    {
        return  Route::status()->join('users_distributors as b', 'routes.distributor_id', '=', 'b.distributor_id')
            ->where('b.user_id', Auth::user()->id)
            ->select('routes.*')
            ->get();
    }
    public function get_shop_distribuor_wise()
    {
        return  Shop::status()->join('users_distributors as b', 'shops.distributor_id', '=', 'b.distributor_id')
            ->where('b.user_id', Auth::user()->id)
            ->select('shops.*')
            ->get();
    }
    public function get_shop_distribuor_wise_count()
    {
        return  Shop::status()->join('users_distributors as b', 'shops.distributor_id', '=', 'b.distributor_id')
            ->where('b.user_id', Auth::user()->id)
            ->select('shops.*');
    }

    public function get_sales_orders($request)
    {
        $from = $request->from;
        $to = $request->to;


        $sales = SaleOrder::join('users_distributors as b', 'b.distributor_id', '=', 'sale_orders.distributor_id')
            ->Status()
            ->where('b.user_id', auth()->user()->id)
            ->whereBetween('dc_date', [$from, $to])
            ->when($request->execution != null, function ($query) use ($request) {
                $query->where('excecution', $request->execution);
            })
            ->when($request->distributor_id != null, function ($query) use ($request) {
                $query->where('sale_orders.distributor_id', $request->distributor_id);
            })
            ->when($request->tso_id != null, function ($query) use ($request) {
                $query->where('sale_orders.tso_id', $request->tso_id);


            })->when($request->city != null, function ($query) use ($request) {
                $query->whereHas('tso.cities',function ($quer) use ($request){
                    $quer->where('id',$request->city);
                });


            })
            ->select('sale_orders.*')
            ->get();

        return $sales;
    }

    public static function get_sale_qty($from , $to , $product_id , $flavour_id , $uom_id , $tso , $distributor , $execution)
    {
        $qty = DB::table('sale_orders')->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
        ->where('sale_orders.status', 1)
        ->where('sale_order_data.product_id', $product_id)
        ->where('sale_order_data.flavour_id', $flavour_id)
        ->where('sale_order_data.sale_type', $uom_id)
        ->whereBetween('sale_orders.dc_date', [$from, $to]);
        if (isset($tso)) {
            $qty = $qty->where('sale_orders.tso_id', $tso);
        }
        if (isset($distributor)) {
            $qty = $qty->where('sale_orders.distributor_id', $distributor);
        }
        if (isset($execution)) {
            $qty = $qty->where('sale_orders.excecution', $execution);
        }
        $qty = $qty->sum('sale_order_data.qty');
        return $qty;
    }

    public static function get_sale_qty2($from , $to , $product_id , $flavour_id , $uom_id , $tso , $distributor , $city , $execution = null)
    {
        // dd($from , $to , $product_id , $flavour_id , $uom_id , $tso , $distributor , $city);
        $qty = DB::table('sale_orders')->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
        ->join('tso', 'tso.id' , 'sale_orders.tso_id')
        ->where('sale_orders.status', 1)
        ->where('sale_order_data.product_id', $product_id)
        ->where('sale_order_data.flavour_id', $flavour_id)
        ->where('sale_order_data.sale_type', $uom_id)
        ->whereBetween('sale_orders.dc_date', [$from, $to]);
        if (isset($tso)) {
            $qty = $qty->where('sale_orders.tso_id', $tso);
        }
        if (isset($city)) {
            $qty = $qty->where('tso.city', $city);
        }
        if (isset($distributor)) {
            $qty = $qty->where('sale_orders.distributor_id', $distributor);
        }
        if (isset($execution)) {
            $qty = $qty->where('sale_orders.excecution', $execution);
        }
        $qty = $qty->sum('sale_order_data.qty');
        return $qty;
    }

    public function get_receipt_voucher($request)
    {
        $from = $request->from;
        $to = $request->to;

        $rvs = ReceiptVoucher::join('users_distributors as b', 'b.distributor_id', '=', 'receipt_vouchers.distributor_id')
            ->where('b.user_id', auth()->user()->id)
            ->whereBetween('issue_date', [$from, $to])
            ->when($request->execution != null, function ($query) use ($request) {
                $query->where('execution', $request->execution);
            })
            ->select('receipt_vouchers.*')
            ->get();

        return $rvs;
    }

    public static function users_location_submit($obj, $lat, $lan, $table , $activity_type)
    {
        $user_location = new UsersLocation();
        $user_location->latitude = $lat;
        $user_location->longitude = $lan;
        $user_location->user_id = Auth::user()->id;
        $user_location->table_name = $table;
        $user_location->activity_type = $activity_type;
        $obj->usersLocation()->save($user_location);
    }

    public static function activity_log_submit($obj, $data, $table , $type , $title = null)
    {
        // dd(json_encode($data));
        if (!$obj || !is_object($obj)) {
            throw new \Exception('Invalid object provided for activity logging.');
        }
        $data = json_encode($data);
        $activity_log = new ActivityLog();

        $activity_log->title = $title;
        $activity_log->description = $data;
        $activity_log->table_name = $table;
        $activity_log->type = $type;
        $activity_log->activity_type = request()->method();
        $activity_log->date = date('Y-m-d');
        $activity_log->url = url()->full();

        $activity_log->user_name = Auth::user()->name;

        $obj->activityLog()->save($activity_log);
    }

    public static function get_user_type($id)
    {
        return Type::where('id', $id)->value('type');
    }

    public static function get_status_value()
    {
        return ['False', 'True'];
    }
    public static function get_active_value()
    {
        return ['Deactivate', 'Activate'];
    }
    public function cities()
    {
        return City::All();
    }

    public function PrintHead($from, $to, $report, $tso_id)
    {
        $data = '
     <div class="row">

       <div class="col-md-4">&nbsp
       </div>
                <div class="col-md-4" style="text-align:center">
                <h3>' . $report . '</h3>
                </div>
        <div class="col-md-4" style="text-align:right">
        Print Date: ' . date('Y-m-d') . '
        </div>
    </div>
    <div class="row">
    <div class="col-md-4">&nbsp
       </div>
       <div class="col-md-4" style="text-align:center">
       <h4>' . self::get_tso_name($tso_id) . '</h4>
       </div>
    </div>
    <div class="row">

    <div class="col-md-4">&nbsp
    </div>
             <div class="col-md-4" style="text-align:center">
           From: ' . $from . ' &nbsp  To: ' . $to . '
             </div>
     <div class="col-md-4" >
     &nbsp
     </div>
 </div>
    ';

        return $data;
    }

    public static function getAllPermissionList()
    {
        $permissions = Permission::query()
            ->select('main_module', 'name')
            ->groupBy('main_module')
            ->get()
            ->map(function ($permission) {
                return [
                    'main_module' => $permission->main_module,
                    'permissions' => $permission->where('main_module', $permission->main_module)->pluck('name','id')
                ];
            });
        // dd($permissions);
        return $permissions;
        // return Permission::select('id', 'name')->get();
    }

    public static function sidebarModules()
    {
        return [
            'User-Management',
            'Execution',
            'KPO',
            'Product',
            'Shop',
            'TSO',
            'Distributor',
            'Route',
            'Reports',
            'Setting',
            'Sub-Routes'
        ];
    }

    public function stock_unique_no($type)
    {
        $count =  Stock::where('stock_type',$type)->groupBy('stock_type')->count();
       return $number = sprintf('%03d',$count);
    }



    public function get_all_role()
    {
       return Role::all();
    }

    public static function Order_list_total_amount($type ,$id,$from=null,$to=null)
    {
        if ($type==0):
          return  DB::table('sale_order_data')->where('so_id',$id)->sum('total');
        else:
         return     DB::table('sale_orders as a')
            ->join('sale_order_data as b', 'a.id', '=', 'b.so_id')
            ->where('a.tso_id', $id)
            ->where('a.status', 1)
            ->whereBetween('a.dc_date', [$from, $to])
            ->groupBy('a.tso_id')
            ->sum('b.total');
        endif;

    }

    public static function sendSmsNotification($destinationnum , $text ,  $language = 'English', $responseType = 'text')
    {
        // $apiUrl = env('BIZSMS_API_URL');
        // $username = env('BIZSMS_USERNAME');
        // $password = env('BIZSMS_PASSWORD');
        // $masking = env('BIZSMS_MASKING');
        // $text = $text;
        // $destinationNumber = $destinationnum;

        // $url = "{$apiUrl}?username={$username}&pass={$password}&text={$text}&masking={$masking}&destinationnum={$destinationNumber}&language={$language}&responsetype={$responseType}";

        $url = env('BIZSMS_API_URL');
        $params = [
            'username' => env('BIZSMS_USERNAME'),
            'pass' => env('BIZSMS_PASSWORD'),
            'text' => $text,
            'masking' => env('BIZSMS_MASKING'),
            'destinationnum' => $destinationnum,
            'language' => $language,
            'responsetype' => $responseType
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);

        if (isset($error_msg)) {
            return $error_msg;
        }
        dd($response);
        return $response;
    }

    public static function correctPhoneNumber($number) {
        // Remove any non-numeric characters
        $number = preg_replace('/\D/', '', $number);

        // Check if the number starts with '92'
        if (substr($number, 0, 2) !== '92') {
            // If it starts with '0', replace the leading '0' with '92'
            if (substr($number, 0, 1) === '0') {
                $number = '92' . substr($number, 1);
            } else {
                // Otherwise, just prepend '92'
                $number = '92' . $number;
            }
        }
        return $number;
    }

    public function uom_name($id)
    {
        $data = UOM::find($id);
        return $data->uom_name ?? '--';
    }
    public function get_uom($id)
    {
        $data = UOM::find($id);
        return $data ?? '--';
    }

    public function product_uom($id)
    {
        $product = Product::find($id);
        $uom_id = $product->uom_id;

        $data = UOM::find($uom_id);
        return $data->uom_name ?? '--';
    }

    public function product_packing_uom($id)
    {
        $product = Product::find($id);
        $uom_id = $product->packing_uom_id;

        $data = UOM::find($uom_id);
        return $data->uom_name ?? '--';
    }

    public function get_product_price($id)
    {
        // dd($id);
        $data = ProductPrice::with('uom')->where('product_id' , $id)->where('status', 1)->get();
        return $data;
    }

    public function product_price_by_uom_id($product_id , $uom_id)
    {
        // dd($id);
        $data = ProductPrice::with('uom')->where('product_id' , $product_id)->where('uom_id' , $uom_id)->where('status', 1)->first();
        return $data ?? '--';
    }

    public static function get_flavour_name($id)
    {
        // dd($id);
        $data = ProductFlavour::where('id',$id)->value('flavour_name');
        // dd($data);
        return $data;
    }

    public static function get_trade_price($product_id , $uom_id)
    {
        $data = ProductPrice::where('product_id' , $product_id)->where('uom_id' , $uom_id)->value('trade_price');
        return $data;
    }

    public static function get_config($config_key)
    {
        $config=Config::where('config_key',$config_key)->first();
        return (!empty($config))?$config->config_value:'';
    }

    public static function get_tso_max_limit()
    {
        $max_limit = self::get_config('tso_max_limit');
        $tso_count = TSO::Status()->Active()->count();
        // $tso_count = 100;
        // dd($max_limit  , $tso_count , $max_limit > $tso_count);
        if ($max_limit > $tso_count) {
            return true;
        }
        else
        {
            return false;
        }

    }


}
