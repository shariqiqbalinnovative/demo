<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Attendence;
use App\Models\TSO;
use App\Models\ReceiptVoucher;
use App\Models\TSOTarget;
use App\Models\SaleOrder;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Rack;
use App\Models\AssignRack;
use App\Models\Distributor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\MasterFormsHelper;
use Auth;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

class ReportController extends Controller
{
    public $page;
    public function __construct()
    {

        $this->master = new MasterFormsHelper();
        $this->page = 'pages.Reports.';

    }


    public function shop_ledger_report(Request $request)
    {
        $date = $request->from;
        $from_date=$request->from;
        $to_date=$request->to;
            if ($request->ajax()) :

                $saleOrders = SaleOrder::select('dc_date', 'shop_id', 'invoice_no', 'notes', 'transport_details', 'total_amount')
                ->with('shop')
                ->where('excecution', 1)
                ->when(!empty($from_date) && !empty($to_date), function ($query) use ($from_date, $to_date) {
                    return $query->whereBetween('dc_date', [$from_date, $to_date]);
                })
                ->when($request->distributor_id != null, function ($query) use ($request) {
                    return $query->where('distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {
                    return $query->where('tso_id', $request->tso_id);
                })
                ->when($request->shop_id != null, function ($query) use ($request) {
                    return $query->where('shop_id', $request->shop_id);
                })
                ->orderBy('shop_id')
                ->get();


                $receiptVouchers = ReceiptVoucher::select('id as rec_id', 'issue_date', 'shop_id', 'amount', 'remarks', 'detail')
                    ->where('status', 1)
                    ->where('execution', 1)
                    ->when(!empty($from_date) && !empty($to_date), function ($query) use ($from_date, $to_date) {
                        return $query->whereBetween('issue_date', [$from_date, $to_date]);
                    })
                    ->when($request->distributor_id != null, function ($query) use ($request) {
                        return $query->where('distributor_id', $request->distributor_id);
                    })
                    ->when($request->tso_id != null, function ($query) use ($request) {
                        return $query->where('tso_id', $request->tso_id);
                    })
                    ->when($request->shop_id != null, function ($query) use ($request) {
                        return $query->where('shop_id', $request->shop_id);
                    })
                    ->with('shop')
                    ->orderBy('shop_id')
                    ->get();

                    $shops= Shop::where('status',1)
                    ->when($request->shop_id != null, function ($query) use ($request) {
                        return $query->where('id', $request->shop_id);
                    })
                    ->get();



                return view($this->page . 'Shop.shop_ledger_report_ajax', compact('saleOrders','shops','receiptVouchers', 'date','from_date','to_date'));
            else :
                return view($this->page . 'Shop.shop_ledger_report');
            endif;

    }

    public function receipt_voucher_summary(Request $request)
    {
        $date = $request->from;
        $from_date=$request->from;
        $to_date=$request->to;
            if ($request->ajax()) :

                $receipt_vouchers = ReceiptVoucher::where('status', 1)
                ->when($request->distributor_id != null, function ($query) use ($request) {
                    return $query->where('distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {
                    return $query->where('tso_id', $request->tso_id);
                })
                ->when(($request->from != '' && $request->to != ''), function ($query) use ($request) {
                    return $query->whereBetween('issue_date', [$request->from, $request->to]);
                })
                ->with('tso','distributor','route','deliveryMan','shop')
                ->get();



                return view($this->page . 'ReceiptVoucher.receipt_voucher_summary_ajax', compact('receipt_vouchers', 'date','from_date','to_date'));
            else :
                return view($this->page . 'ReceiptVoucher.receipt_voucher_summary');
            endif;

    }

    public function order_summary(Request $request)
    {


        $date = $request->from;
        $to = $request->to;

            if ($request->ajax()) :

                $tsos =  TSO::status()->active()
                    ->join('users_distributors','users_distributors.user_id','tso.user_id')
                    ->whereIn('users_distributors.distributor_id', $this->master->get_users_distributors(Auth::user()->id))
                    ->when($request->distributor_id != null, function ($query) use ($request) {

                        $query->where('users_distributors.distributor_id', $request->distributor_id);
                    })->when($request->tso_id != null, function ($query) use ($request) {

                        $query->where('tso.id', $request->tso_id);
                    })->when($request->designation != null, function ($query) use ($request) {

                        $query->where('tso.designation_id', $request->designation);
                    })->when($request->city != null, function ($query) use ($request) {

                        $query->where('tso.city', $request->city);
                    })->with(['attendence' => function ($query) use ($request) {

                    $query->whereRaw('DATE(`in`) BETWEEN ? AND ?', [$request->from, $request->to]);
                    if ($request->tso_id!=null)
                    $query->groupBy(DB::raw('DATE(`in`)'));


                    }, 'designation', 'distributor', 'cities'])->select('tso.*','users_distributors.distributor_id')->get()->toArray();


                return view($this->page . 'orderSummary.order_summary_ajax', compact('tsos', 'date','to'));
            else :
                return view($this->page . 'orderSummary.order_summary');
            endif;

    }
    public function order_list(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $type = $request->report_type;

        if ($request->ajax()) :
            $order_list =   DB::table('sale_orders as a')
                ->join('sale_order_data', 'sale_order_data.so_id', 'a.id')
                ->join('shops', 'shops.id', 'a.shop_id')
                ->join('routes', 'routes.id', 'shops.route_id')
                ->join('distributors as b', 'a.distributor_id', 'b.id')
                ->join('tso as c', function ($join) use ($request) {
                    $join->on('c.id', '=', 'a.tso_id')->where('c.active', 1);
                    if ($request->city != null)
                        $join->where('c.city', $request->city);
                    if ($request->designation != null)
                        $join->where('designation_id', $request->designation);
                })
                ->join('users_distributors','c.user_id','=','users_distributors.user_id')
                ->when($request->distributor_id == null, function ($query) use ($request) {

                    $query->whereIn('users_distributors.distributor_id' ,MasterFormsHelper::get_users_distributors(Auth::user()->id));
                })
                ->join('cities', 'cities.id', 'c.city')
                ->when($request->distributor_id != null, function ($query) use ($request) {
                    $query->where('a.distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {
                    $query->where('a.tso_id', $request->tso_id);
                })

                ->where('a.status', 1)
                ->where('c.status',1)
                ->whereBetween('a.dc_date', [$from, $to])
                ->when($request->report_type == 0, function ($query) use ($request) {
                    $query->select('a.id', 'b.distributor_name', 'c.name as tso', 'c.id as tso_id', 'c.user_id', 'a.dc_date', 'shops.company_name as shop_name', DB::raw('SUM(sale_order_data.total) as total_amount'), 'routes.route_name', 'cities.name as city', 'a.invoice_no')
                    ->groupBy('a.id');
                })
                ->when($request->report_type == 1, function ($query) use ($request) {
                    $query->select('a.id', 'b.distributor_name', 'c.name as tso', 'c.id as tso_id', 'c.user_id', 'a.dc_date', 'shops.company_name as shop_name', DB::raw('SUM(sale_order_data.total) as total_amount'), 'routes.route_name', 'cities.name as city', 'a.invoice_no')
                    ->groupBy('a.tso_id');
                })
                // ->select('b.distributor_name','c.name as tso','a.dc_date', 'shops.company_name as shop_name', 'SUM(sale_order_data.total) as total_amount', 'routes.route_name', 'cities.name as city','a.invoice_no')

                ->get();
            //    dd($order_list);
            return view($this->page . 'orderList.order_list_ajax', compact('order_list','type'));
        endif;
        return view($this->page . 'orderList.order_list');
    }


    public function product_avail(Request $request)
    {
        if ($request->ajax()) :

            $data =   DB::table('sale_orders as a')
                ->join('sale_order_data as d', 'd.so_id', 'a.id')
                ->join('shops', 'shops.id', 'a.shop_id')
                ->join('products', 'd.product_id', 'products.id')
                ->join('routes', function ($join) use ($request) {
                    $join->on('routes.id', '=', 'shops.route_id');
                    if ($request->route_id != null)
                        $join->where('routes.id', $request->route_id);
                })
                ->join('distributors as b', 'a.distributor_id', 'b.id')
                ->join('tso as c', function ($join) use ($request) {
                    $join->on('c.id', '=', 'a.tso_id');
                    if ($request->city != null)
                        $join->where('c.city', $request->city);
                })
                ->join('users_distributors','c.user_id','=','users_distributors.user_id')
                ->when($request->distributor_id == null, function ($query) use ($request) {

                    $query->whereIn('users_distributors.distributor_id' ,MasterFormsHelper::get_users_distributors(Auth::user()->id));
                })
                ->join('cities', 'cities.id', 'c.city')
                ->when($request->distributor_id != null, function ($query) use ($request) {
                    $query->where('a.distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {
                    $query->where('a.tso_id', $request->tso_id);
                })
                ->when($request->shop_id != null, function ($query) use ($request) {
                    $query->where('a.shop_id', $request->shop_id);
                })
                ->when($request->product_id != null, function ($query) use ($request) {
                    $query->where('d.product_id', $request->product_id);
                })
                ->where('d.availability', '>', 0)
                ->where('a.status', 1)
                ->where('c.status', 1)
                ->select(
                    'a.id',
                    'b.distributor_name',
                    'c.name as tso',
                    'c.id as tso_id',
                    'c.user_id',
                    'a.dc_date',
                    'shops.company_name as shop_name',
                    DB::raw('SUM(d.total) as total_amount'),
                    'routes.route_name',
                    'cities.name as city',
                    'products.product_name',
                    'd.availability',
                    'd.foc'
                )
                ->groupBy('a.shop_id')
                ->groupBy('d.product_id')
                ->orderBy('d.id', 'DESC')
                ->get();
            return view($this->page . 'productAvail.product_avail_ajax', compact('data'));
        endif;

        return view($this->page . 'productAvail.product_avail');
    }

    public function product_productivity(Request $request)
    {
        if ($request->ajax()) :
            $from = $request->from;
            $to = $request->to;
            $data =   DB::table('sale_orders as s')
            ->join('sale_order_data as sd' , 'sd.so_id' , 's.id')
            ->join('products as p' ,'p.id','sd.product_id')
            ->when($request->product_id != null, function ($query) use ($request) {
                $query->where('sd.product_id', $request->product_id);
            })
            ->when($request->distributor_id != null, function ($query) use ($request) {
                $query->where('s.distributor_id', $request->distributor_id);
            })
            ->when($request->tso_id != null, function ($query) use ($request) {
                $query->where('s.tso_id', $request->tso_id);
            })
            ->when($request->from != null && $request->to != null, function ($query) use ($request) {
                $query->whereBetween('s.dc_date', [$request->from, $request->to]);
            })
            ->select('sd.product_id' , 'sd.flavour_id' , 'sd.sale_type','s.distributor_id' , 's.tso_id',
            DB::raw('COUNT(DISTINCT s.shop_id) as distinct_shop_count'),
            DB::raw('COUNT(s.shop_id) as shop_count'))
            ->groupby('sd.product_id' , 'sd.flavour_id' , 'sd.sale_type','s.distributor_id' , 's.tso_id')
            ->get();
            // dd($data);
            return view($this->page . 'productProductivity.product_productivity_ajax', compact('data' , 'from' , 'to'));
        endif;

        return view($this->page . 'productProductivity.product_productivity');
    }

    function load_Sheet(Request $request)
    {
        $from = $request->from ?? date('Y-m-d');
        $to =  $request->to ?? date('Y-m-d');

        if ($request->ajax()) :


            $tso_id =  $request->tso_id;
            $distributor_id =  $request->distributor_id;
            $execution = $request->execution;


            $so_data = DB::table('sale_orders')->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
                ->join('products', 'products.id', 'sale_order_data.product_id')
                ->join('uom', 'uom.id', 'sale_order_data.sale_type')
                ->where('sale_orders.status', 1)
                ->whereBetween('sale_orders.dc_date', [$from, $to])
                ->select(DB::raw('sum(sale_order_data.qty) as qty'), 'products.id as product_id' ,'products.product_name' , 'sale_order_data.flavour_id' , DB::raw('sum(sale_order_data.total) as amount')
                ,'sale_orders.excecution')
                // ->select('products.id as product_id' ,'products.product_name' , 'sale_order_data.flavour_id' ,
                // DB::raw('sum(sale_order_data.total) as amount'))
                // ->addSelect([
                //     'qty_summary' => DB::table('sale_order_data')
                //         ->join('uom', 'uom.id', '=', 'sale_order_data.sale_type')
                //         ->select(DB::raw("GROUP_CONCAT(CONCAT(SUM(sale_order_data.qty), 'x', uom.uom_name) SEPARATOR ', ')"))
                //         ->whereColumn('sale_order_data.product_id', 'products.id')
                //         ->whereColumn('sale_order_data.flavour_id', 'sale_order_data.flavour_id')
                //         ->whereBetween('sale_orders.dc_date', [$from, $to])
                //         ->groupBy('sale_order_data.product_id', 'sale_order_data.flavour_id','sale_order_data.sale_type')
                // ])
                ->groupby('sale_order_data.product_id' , 'sale_order_data.flavour_id');
            if (!empty($tso_id)) {
                $so_data = $so_data->where('sale_orders.tso_id', $tso_id);
            }

            if (!empty($distributor_id)) {
                $so_data = $so_data->where('sale_orders.distributor_id', $distributor_id);
            }
            if (isset($execution)) {
                $so_data = $so_data->where('sale_orders.excecution', $execution);
            }
            $so_data  = $so_data->get();




            return view($this->page . 'loadsheet.load_sheet_ajax', compact('so_data', 'from', 'to','tso_id','distributor_id','execution'));
        endif;
        return view($this->page . 'loadsheet.load_sheet', compact('from', 'to'));
    }



    public function order_vs_execution(Request $request)
    {

        if ($request->ajax()) :

            $from = $request->from;
            $to = $request->to;
            $data =   DB::table('sale_orders as a')
                ->join('sale_order_data as d', 'd.so_id', 'a.id')
                ->join('shops', 'shops.id', 'a.shop_id')
                ->join('products', 'd.product_id', 'products.id')
                ->join('routes', function ($join) use ($request) {
                    $join->on('routes.id', '=', 'shops.route_id');
                    if ($request->route_id != null)
                        $join->where('routes.id', $request->route_id);
                })
                ->join('distributors as b', 'a.distributor_id', 'b.id')
                ->join('tso as c', function ($join) use ($request) {
                    $join->on('c.id', '=', 'a.tso_id')->where('c.active', 1);
                    if ($request->city != null)
                        $join->where('c.city', $request->city);
                })
                ->join('users_distributors','c.user_id','=','users_distributors.user_id')
                ->when($request->distributor_id == null, function ($query) use ($request) {

                    $query->whereIn('users_distributors.distributor_id' ,MasterFormsHelper::get_users_distributors(Auth::user()->id));
                })
                ->join('cities', 'cities.id', 'c.city')
                ->when($request->distributor_id != null, function ($query) use ($request) {
                    $query->where('a.distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {
                    $query->where('a.tso_id', $request->tso_id);
                })
                ->when($request->shop_id != null, function ($query) use ($request) {
                    $query->where('a.shop_id', $request->shop_id);
                })
                ->when($request->product_id != null, function ($query) use ($request) {
                    $query->where('d.product_id', $request->product_id);
                })
                ->when($request->execution != null, function ($query) use ($request) {
                    $query->where('a.excecution', $request->execution);
                })
                ->whereBetween('a.dc_date', [$from, $to])
                ->where('a.status', 1)
                ->where('c.status', 1)
                ->select(
                    'a.id',
                    'b.distributor_name',
                    'c.name as tso',
                    'c.id as tso_id',
                    'c.user_id',
                    'a.dc_date',
                    'shops.company_name as shop_name',
                    'routes.route_name',
                    'cities.name as city',
                    'products.product_name',
                    'd.total',
                    'd.qty',
                    'a.excecution',
                    'a.invoice_no'
                )
                ->orderBy('a.invoice_no', 'DESC')
                ->get();

            return view($this->page . 'OrderVSExecution.order_vs_execution_ajax', compact('data'));
        endif;

        return view($this->page . 'OrderVSExecution.order_vs_execution');
    }


    public function tso_target(Request $request)
    {

        if ($request->ajax()) :

            $monthfrom = date('m', strtotime($request->from));
            $monthto = date('m', strtotime($request->to));

            $summary = $request->summary;
            $target_type = $request->target_type;
            $tso_target = TSOTarget::leftjoin('products', 'products.id', 'tso_targets.product_id')
                ->join('tso', 'tso.id', 'tso_targets.tso_id')
                ->join('users_distributors','tso.user_id','=','users_distributors.user_id')
                ->when($request->distributor_id == null, function ($query) use ($request) {

                    $query->whereIn('users_distributors.distributor_id' ,MasterFormsHelper::get_users_distributors(Auth::user()->id));
                })
                ->join('distributors', 'distributors.id', 'tso.distributor_id');
            if (!empty($request->tso_id)) {
                $tso_target->where('tso_targets.tso_id', $request->tso_id);
            }
            if (!empty($target_type)) {
                $tso_target->where('tso_targets.type', $target_type);
            }

            // if (!empty($request->distributor_id)) {
            //     $tso_target->where('tso.distributor_id', $request->distributor_id);
            // }

            $tso_target = $tso_target
                ->whereBetween(DB::raw('MONTH(tso_targets.month)'), [$monthfrom, $monthto])

                ->select(
                    'distributors.distributor_name',
                    'tso_targets.*',
                    DB::raw('SUM(tso_targets.qty) as tso_targets_qty'),
                    'products.product_name',
                    'tso.name as tso_name',
                    'tso_targets.product_id',
                    'tso_targets.tso_id',
                    'tso_targets.qty',

                )
                ->groupBy('tso_targets.product_id' , 'tso_targets.shop_type', 'tso_targets.tso_id', 'distributors.distributor_name', 'products.product_name', 'tso.name')
                ->get();
                // dd($tso_target->toArray());
            return view($this->page . 'tsotarget.tso_target_ajax', compact('tso_target', 'summary','monthfrom', 'monthto' ,'target_type'));
        endif;
        return view($this->page . 'tsotarget.tso_target');
    }

    public function racks_report(Request $request)
    {


        if ($request->ajax()) :

            $tso_id = $request->tso_id;
            $rack_id = $request->rack_id;
            $shop_id = $request->shop_id;
            $distributor_id = $request->distributor_id;

            $monthfrom = date('m', strtotime($request->from));
            $monthto = date('m', strtotime($request->to));
            // dd($monthfrom , $request->from);
            $racks_details = AssignRack::join('racks' , 'racks.id' , 'assign_racks.rack_id')
            ->join('shops' , 'shops.id' , 'assign_racks.shop_id')
            ->join('tso' , 'tso.id' , 'assign_racks.tso_id')
            ->join('users_distributors','tso.user_id','=','users_distributors.user_id')
            ->join('distributors', 'distributors.id', 'tso.distributor_id')

            ->when($distributor_id == null, function ($query) use ($request) {

                $query->whereIn('users_distributors.distributor_id' ,MasterFormsHelper::get_users_distributors(Auth::user()->id));
            })
            ->when($tso_id , function($query) use ($tso_id) {
                $query->where('assign_racks.tso_id' , $tso_id);
            })
            ->when($shop_id , function($query) use ($shop_id) {
                $query->where('assign_racks.shop_id' , $shop_id);
            })
            ->when($rack_id , function($query) use ($rack_id) {
                $query->where('assign_racks.rack_id' , $rack_id);
            })
            ->whereBetween(DB::raw('MONTH(assign_racks.created_at)'), [$monthfrom, $monthto])
            ->select('distributors.distributor_name','assign_racks.*' , 'tso.name as tso_name' , 'shops.company_name as shop_name' , 'racks.rack_code')
            ->groupBy('assign_racks.id')
            ->get();

            return view($this->page . 'rack.rack_ajax' , compact('racks_details'));
        endif;
        return view($this->page . 'rack.rack' );
    }


    public function scheme_product(Request $request)
    {
        if ($request->ajax()) :
            $from =$request->from;
            $to =$request->to;

            $data =   DB::table('sale_orders as a')
                ->join('sale_order_data as d', 'd.so_id', 'a.id')
                ->join('shops', 'shops.id', 'a.shop_id')
                ->join('products', 'd.sheme_product_id', 'products.id')
                ->join('routes', function ($join) use ($request) {
                    $join->on('routes.id', '=', 'shops.route_id');
                    if ($request->route_id != null)
                        $join->where('routes.id', $request->route_id);
                })
                ->join('distributors as b', 'a.distributor_id', 'b.id')
                ->join('tso as c', function ($join) use ($request) {
                    $join->on('c.id', '=', 'a.tso_id');
                    if ($request->city != null)
                        $join->where('c.city', $request->city);
                })

                ->join('users_distributors','c.user_id','=','users_distributors.user_id')
                ->when($request->distributor_id == null, function ($query) use ($request) {

                    $query->whereIn('users_distributors.distributor_id' ,MasterFormsHelper::get_users_distributors(Auth::user()->id));
                })
                ->join('cities', 'cities.id', 'c.city')
                ->when($request->distributor_id != null, function ($query) use ($request) {
                    $query->where('a.distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {
                    $query->where('a.tso_id', $request->tso_id);
                })
                ->when($request->shop_id != null, function ($query) use ($request) {
                    $query->where('a.shop_id', $request->shop_id);
                })
                ->when($request->product_id != null, function ($query) use ($request) {
                    $query->where('d.product_id', $request->product_id);
                })
                ->where('d.offer_qty', '>', 0)
                ->whereBetween('a.dc_date', [$from, $to])
                ->where('a.status', 1)
                ->where('c.status', 1)
                ->select(
                    'a.id',
                    'b.distributor_name',
                    'c.name as tso',
                    'c.id as tso_id',
                    'c.user_id',
                    'a.dc_date',
                    'shops.company_name as shop_name',
                    DB::raw('SUM(d.offer_qty) as total_amount'),
                    'routes.route_name',
                    'cities.name as city',
                    'a.dc_date',
                    'products.product_name',
                    'd.offer_qty'
                )
                ->groupBy('a.shop_id')
                ->groupBy('d.sheme_product_id')
                ->orderBy('d.id', 'DESC')
                ->get();
            return view($this->page . 'ShcemeProductReport.scheme_product_ajax', compact('data'));
        endif;

        return view($this->page . 'ShcemeProductReport.scheme_product');
    }

    // public function day_wise_attendence_report(Request $request)
    // {
    //     if ($request->ajax()) :
    //         $monthYear = explode('-', $request->from_date);
    //         $from_date = $request->from_date;
    //         $to_date = $request->to_date;
    //         // dd($request->from , $monthYear);
    //        $attendences =  TSO::status()->select('id', 'name', 'tso_code', 'distributor_id', 'designation_id', 'city')
    //             ->when($request->distributor_id != null, function ($query) use ($request) {

    //                 $query->where('distributor_id', $request->distributor_id);
    //             })->when($request->tso_id != null, function ($query) use ($request) {

    //                 $query->where('id', $request->tso_id);
    //             })->when($request->designation != null, function ($query) use ($request) {

    //                 $query->where('designation_id', $request->designation);
    //             })->when($request->city != null, function ($query) use ($request) {

    //                 $query->where('city', $request->city);
    //             })->with(['designation:id,name', 'distributor:id,distributor_name', 'cities:id,name'])->get()->toArray();
    //             // dd($attendences);
    //         return view($this->page . 'attendenceReport.day_wise_attendence_report_ajax', compact('attendences', 'monthYear' , 'from_date' , 'to_date'));
    //     endif;
    //     return view($this->page . 'attendenceReport.day_wise_attendence_report');
    // }



    public function day_wise_attendence_report(Request $request)
    {
        if ($request->ajax()) :
            $monthYear = explode('-', $request->from_date);
            $from_date = $request->from_date;
            $to_date = $request->to_date;
            // dd($request->from , $monthYear);
           $attendences =  TSO::status()->where('active' , 1)
                ->join('users_distributors','users_distributors.user_id','tso.user_id')
                ->whereIn('users_distributors.distributor_id', $this->master->get_users_distributors(Auth::user()->id))
                ->select('tso.id', 'tso.emp_id','tso.name', 'tso.tso_code','tso.cnic', 'users_distributors.distributor_id', 'tso.designation_id', 'tso.city')
                ->when($request->distributor_id != null, function ($query) use ($request) {

                    $query->where('users_distributors.distributor_id', $request->distributor_id);
                })->when($request->tso_id != null, function ($query) use ($request) {

                    $query->where('tso.id', $request->tso_id);
                })->when($request->designation != null, function ($query) use ($request) {

                    $query->where('tso.designation_id', $request->designation);
                })->when($request->city != null, function ($query) use ($request) {

                    $query->where('tso.city', $request->city);
                })->with(['designation:id,name', 'distributor:id,distributor_name', 'cities:id,name'])->get()->toArray();

            return view($this->page . 'attendenceReport.day_wise_attendence_report_ajax', compact('attendences', 'monthYear' , 'from_date' , 'to_date'));
        endif;
        return view($this->page . 'attendenceReport.day_wise_attendence_report');
    }


    public function attendence_report(Request $request)
    {
        if ($request->ajax()) :
            $monthYear = explode('-', $request->from);
            // dd($request->from , $monthYear);
           $attendences =  TSO::status()->select('id', 'name', 'tso_code', 'distributor_id', 'designation_id', 'city')
                ->when($request->distributor_id != null, function ($query) use ($request) {

                    $query->where('distributor_id', $request->distributor_id);
                })->when($request->tso_id != null, function ($query) use ($request) {

                    $query->where('id', $request->tso_id);
                })->when($request->designation != null, function ($query) use ($request) {

                    $query->where('designation_id', $request->designation);
                })->when($request->city != null, function ($query) use ($request) {

                    $query->where('city', $request->city);
                })->with(['designation:id,name', 'distributor:id,distributor_name', 'cities:id,name'])->get()->toArray();

            return view($this->page . 'attendenceReport.attendence_report_ajax', compact('attendences', 'monthYear'));
        endif;
        return view($this->page . 'attendenceReport.attendence_report');
    }

    public function attendence_report_detail(Request $request)
    {
        // dd($request->all());
        $tsoName = TSO::find($request->id)->name ?? '';
        $attendences = Attendence::where('tso_id', $request->id);
        // ->where('distributor_id', $request->distributor_id);
        if ($request->date) {
            $attendences = $attendences->whereMonth('in', '=', $request->date[1])
                ->whereYear('in', '=', $request->date[0]);
        }
        else if($request->from_date && $request->to_date ){
            $from = Carbon::parse($request->from_date);
            $to = Carbon::parse($request->to_date);
            $attendences = $attendences->whereBetween('in', [$from, $to]);
        }

        $attendences = $attendences->get();
        return view($this->page . 'attendenceReport.attendence_report_detail', compact('attendences', 'tsoName'));
    }


    public function item_wise_sale(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $distributor_id = $request->distributor_id;
        $tso_id = $request->tso_id;
        $city = $request->city;

        if ($request->ajax()) :
            if ($request->type == 'Detail') {
                $data =   DB::table('sale_orders as a')
                ->join('sale_order_data as b', 'b.so_id', 'a.id')
                ->join('tso as c', 'c.id' , 'a.tso_id')
                ->join('products as d', 'd.id' , 'b.product_id')
                ->join('cities as e', 'e.id' , 'c.city')
                ->whereBetween('a.dc_date', [$from, $to])
                ->when($request->distributor_id != null, function ($query) use ($request) {

                    $query->where('a.distributor_id', $request->distributor_id);
                })
                ->when($request->tso_id != null, function ($query) use ($request) {

                    $query->where('a.tso_id', $request->tso_id);
                })
                ->when($request->city != null, function ($query) use ($request) {

                    $query->where('e.id', $request->city);
                })
                ->when($request->product_id != null, function ($query) use ($request) {

                    $query->where('d.id', $request->product_id);
                })
                ->where('a.status',1)
                ->where('c.status',1)
                ->select('c.name','c.cnic','a.tso_id','d.product_name','b.product_id','b.flavour_id',DB::raw('sum(b.total) as total'),DB::raw('sum(b.qty) as qty'),'e.name as city_name')
                ->groupBy('a.tso_id','b.product_id','b.flavour_id')
                // ->groupBy('a.tso_id','b.product_id')
                ->orderBy('a.tso_id')
                ->get();

                return view($this->page . 'itemWiseSales.Item_wise_list_ajax',compact('data','from','to','distributor_id','tso_id','city'));
            }
            else{
                $data = DB::table('products')
                ->join('product_flavours','product_flavours.product_id' , 'products.id')
                ->where('products.status',1)
                ->select('products.*' , 'product_flavours.*' , 'product_flavours.id as flavour_id')
                ->get();
                return view($this->page . 'itemWiseSales.Item_wise_summary_list_ajax',compact('data','from','to','distributor_id'));
            }
        endif;
        return view($this->page . 'itemWiseSales.Item_wise_list');
    }

    public function stock_report(Request $request)
    {
        if ($request->ajax()) :

            $result = Product::join('stocks','stocks.product_id','products.id')
            ->leftJoin('distributors', 'distributors.id', '=', 'stocks.distributor_id')
           ->select(
                    DB::raw('SUM(CASE WHEN stock_type = 0 THEN stocks.qty ELSE 0 END) AS purchase_qty'),
                    DB::raw('SUM(CASE WHEN stock_type = 1 THEN stocks.qty ELSE 0 END) AS opening_qty'),
                    DB::raw('SUM(CASE WHEN stock_type = 2 THEN stocks.qty ELSE 0 END) AS transfer_received_qty'),
                    DB::raw('SUM(CASE WHEN stock_type = 3 THEN stocks.qty ELSE 0 END) AS sales_qty'),
                    DB::raw('SUM(CASE WHEN stock_type = 4 THEN stocks.qty ELSE 0 END) AS sales_return_qty'),
                    DB::raw('SUM(CASE WHEN stock_type = 5 THEN stocks.qty ELSE 0 END) AS transfer_qty'),
                   'products.product_name','products.packing_size','distributors.max_discount','products.sales_price',
                   'products.id as product_id','stocks.flavour_id','distributors.id as distributor_id'
            );
            // $result = Product::join('stocks','stocks.product_id','products.id')
            // ->leftJoin('distributors', 'distributors.id', '=', 'stocks.distributor_id')
            // ->select('products.product_name','products.packing_size','distributors.max_discount','products.sales_price');
                if(!empty($request->distributor_id))
                {
                    $result->where('distributor_id',$request->distributor_id);
                }

                $result = $result->where('products.status',1)->where('stocks.status',1)->where('products.product_type_id',1)->groupBy('products.id','stocks.flavour_id')->get();
                // dd($result->toArray());
                return view($this->page . 'stock.stock_report_Ajax',compact('result'));
        endif;

        return view($this->page . 'stock.stock_report');
    }

    public function top_tso_report(Request $request , $id){
        // dd($request);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $tso = TSO::find($id);
        $sales =SaleOrder::
        // join('tso','tso.id','sale_orders.tso_id')
        // ->join('sale_order_data', 'sale_order_data.so_id', '=', 'sale_orders.id')
        // ->where('tso.status',1)
        where('status',1)
        ->where('tso_id' , $id)
        ->whereBetween('dc_date', [$currentMonthStart, $currentMonthEnd])
        // ->where('excecution' , 1)
        ->orderby('dc_date' , 'desc')
        ->get();

        return view($this->page . 'dashboard.top_tso_report' , compact('sales' , 'tso'));
    }

    public function top_distributor_report(Request $request , $id){
        // dd($request);
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        $distributor = Distributor::find($id);
        $sales =SaleOrder::
        // join('tso','tso.id','sale_orders.tso_id')
        // ->join('sale_order_data', 'sale_order_data.so_id', '=', 'sale_orders.id')
        // ->where('tso.status',1)
        where('status',1)
        ->whereBetween('dc_date', [$currentMonthStart, $currentMonthEnd])
        ->where('distributor_id' , $id)
        // ->where('excecution' , 1)
        ->orderby('dc_date' , 'desc')
        ->get();

        // dd($sales->toArray());
        return view($this->page . 'dashboard.top_distributor_report' , compact('sales' , 'distributor'));
    }
    public function top_product_report(Request $request , $id){
        // dd($request);

        $product = Product::find($id);


        if ($request->ajax()) {
            # code...
            $from = $request->from;
            $to = $request->to;
            $sales = SaleOrder::join('sale_order_data as sod', 'sod.so_id', '=', 'sale_orders.id')
            ->where('sod.product_id' , $id)
            // ->where('sale_orders.excecution' , 1)
            ->whereBetween('sale_orders.dc_date', [$from, $to])
            ->where('sale_orders.status' , 1)
            ->select('sale_orders.*' ,'sod.total', 'sod.qty')
            ->orderby('sale_orders.dc_date' , 'desc')
            ->groupBy('sod.id')
            ->get();

            return view($this->page . 'dashboard.ajax.top_product_report' , compact('sales' , 'product' , 'id'));

        }


        // dd($sales->toArray());
        return view($this->page . 'dashboard.top_product_report' , compact('product' , 'id'));
        // return view($this->page . 'dashboard.top_product_report' , compact('sales' , 'product' , 'id'));
    }
    public function top_shop_report(Request $request , $id){
        // dd($request);
        $shop = Shop::find($id);
        $sales = null;
        if ($request->type == 'top_sale') {
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();

            $sales =SaleOrder::
            // join('tso','tso.id','sale_orders.tso_id')
            // ->join('sale_order_data', 'sale_order_data.so_id', '=', 'sale_orders.id')
            // ->where('tso.status',1)
            where('status',1)
            ->whereBetween('dc_date', [$currentMonthStart, $currentMonthEnd])
            ->where('shop_id' , $id)
            // ->where('excecution' , 1)
            ->orderby('dc_date' , 'desc')
            ->get();
        }
        elseif($request->type == 'non_productive'){
            $sales =SaleOrder::
            // join('tso','tso.id','sale_orders.tso_id')
            // ->join('sale_order_data', 'sale_order_data.so_id', '=', 'sale_orders.id')
            // ->where('tso.status',1)
            where('status',1)
            // ->whereBetween('dc_date', [$currentMonthStart, $currentMonthEnd])
            ->where('shop_id' , $id)
            // ->where('excecution' , 1)
            ->orderby('dc_date' , 'desc')
            ->get();
        }


        // dd($sales->toArray());
        return view($this->page . 'dashboard.top_shop_report' , compact('sales' , 'shop' , 'request'));
    }

    public function sales_report(Request $request)
    {
        // dd($request);
        $sales = null;
        if ($request->ajax()){

            if ($request->type == 'today') {
                $sales = SaleOrder::join('users_distributors as b', 'b.distributor_id', '=', 'sale_orders.distributor_id')
                ->where('b.user_id', auth()->user()->id)
                ->whereDate('sale_orders.dc_date', '=', date('Y-m-d'))
                ->select('sale_orders.*')
                // ->get()
                ;
            }
            else if($request->type  == 'yesterday'){
                $yesterdayDate = Carbon::yesterday();
                $sales = SaleOrder::join('users_distributors as b', 'b.distributor_id', '=', 'sale_orders.distributor_id')
                ->where('b.user_id', auth()->user()->id)
                ->whereDate('sale_orders.dc_date', '=', $yesterdayDate->toDateString())
                ->select('sale_orders.*')
                // ->get()
                ;
            }
            else if($request->type  == 'last_Month'){
                $previousMonthStart = Carbon::now()->subMonth()->startOfMonth();
                $previousMonthEnd = Carbon::now()->subMonth()->endOfMonth();

                $sales = SaleOrder::join('users_distributors as b', 'b.distributor_id', '=', 'sale_orders.distributor_id')
                ->where('b.user_id', auth()->user()->id)
                ->whereBetween('sale_orders.dc_date', [$previousMonthStart, $previousMonthEnd])
                ->select('sale_orders.*')
                // ->get()
                ;

            }
            else if($request->type  == 'current_Month'){
                $currentMonthStart = Carbon::now()->startOfMonth();
                $currentMonthEnd = Carbon::now()->endOfMonth();

                $sales = SaleOrder::join('users_distributors as b', 'b.distributor_id', '=', 'sale_orders.distributor_id')
                ->where('b.user_id', auth()->user()->id)
                ->whereBetween('sale_orders.dc_date', [$currentMonthStart, $currentMonthEnd])
                ->select('sale_orders.*')
                // ->get()
                ;

            }
            // dd($request->all() , $request->type , $sales);
            return DataTables::of($sales)
                ->addIndexColumn()
                ->editColumn('dc_date', function($row) {
                    return date('d-m-Y', strtotime($row->dc_date));
                })
                ->editColumn('distributor', function($row) {
                    return $row->distributor->distributor_name;
                })
                ->editColumn('tso', function($row) {
                    return $row->tso->name;
                })
                ->editColumn('city', function($row) {
                    return $row->tso->cities->name;
                })
                ->editColumn('shop', function($row) {
                    return $row->shop->company_name;
                })
                ->editColumn('excecution', function($row) {
                    return $row->excecution ? 'YES' : 'NO' ;
                })
                ->editColumn('action', function($row) {
                    $action = '<div class="dropdown">
                    <i class="fa-solid fa-ellipsis-vertical dropdown-toggle action_cursor"
                        id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false"></i>
                    <div class="dropdown-menu dropdown-menu_sale_order_list"
                        aria-labelledby="dropdownMenuButton">';

                    // Add 'View' action if permission exists
                    if (auth()->user()->can('Sale_Order_VIew')) {
                        $action .= '<a target="_blank" data-url="' . route('sale.show', $row->id) . '"
                                        data-title="View Sale Order"
                                        class="dropdown-item_sale_order_list dropdown-item launcher">View</a>';
                    }

                    // Add 'Edit' and 'Delete' actions if conditions are met
                    if (!$row->excecution) {
                        if (auth()->user()->can('Sale_Order_Edit')) {
                            $action .= '<a href="' . route('sale.edit', $row->id) . '"
                                            class="dropdown-item_sale_order_list dropdown-item">Edit</a>';
                        }

                        if (auth()->user()->can('Sale_Order_Delete')) {
                            $action .= '<a href="javascript:void(0);" data-url="' . route('sale.destroy', $row->id) . '"
                                            id="delete-user" class="dropdown-item_sale_order_list dropdown-item">Delete</a>';
                        }
                    }

                    $action .= '</div>
                    </div>';

                    return $action;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        // dd($sales);
        return view($this->page . 'dashboard.sales_report' , compact('sales' , 'request'));

    }


    public function unit_sold_report(Request $request)
    {
        $sales = null;
        if ($request->type == 'currentMonth') {
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();

            $sales = SaleOrder::join('tso','tso.id','sale_orders.tso_id')
            ->join('sale_order_data', 'sale_order_data.so_id', '=', 'sale_orders.id')
            ->join('products','products.id','sale_order_data.product_id')
            ->join('shops','shops.id','sale_orders.shop_id')
            ->select('shops.company_name','sale_orders.dc_date' ,'shops.id','sale_order_data.product_id','sale_order_data.product_id', DB::raw('sum(sale_order_data.qty) as product_count'),'products.product_name')
            ->whereBetween('sale_orders.dc_date', [$currentMonthStart, $currentMonthEnd])
            ->where('sale_orders.status', 1)
            ->where('tso.status',1)
            ->groupBy('products.id' , 'sale_orders.shop_id')
            ->get();

            // dd($sales->toArray());
        }

        return view($this->page . 'dashboard.unit_sold_report' , compact('sales' , 'request'));

    }
    public function top_shop_balance_report(Request $request)
    {
        $shops= Shop::where('status',1)
                // ->when($request->shop_id != null, function ($query) use ($request) {
                //     return $query->where('id', $request->shop_id);
                // })
                ->get();
            return view($this->page . 'dashboard.top_shop_balance_report' , compact('request' , 'shops'));

    }

}
