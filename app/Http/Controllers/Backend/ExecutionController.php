<?php

namespace App\Http\Controllers\Backend;
use App\Helpers\MasterFormsHelper;
use App\Http\Controllers\Controller;
use App\Models\ReceiptVoucher;
use App\Models\SaleOrder;
use App\Models\SaleOrderData;
use App\Models\SalesReturn;
use App\Models\ShopOutsanding;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\Product;

use Illuminate\Http\Request;
use DB;

class ExecutionController extends Controller
{
    public function __construct()
    {
        $this->page = 'pages.Execution.';
        $this->master = new MasterFormsHelper();
    }
    /**
     * Sale Order Execution List
     */
    public function indexSaleOrder(Request $request)
    {
        $sales = $this->master->get_sales_orders($request);


        if ($request->ajax()):
            return view($this->page.'TableData',compact('sales'));
        endif;
        return view($this->page.'IndexSale');
    }
    /**
     * Payment Recovery Execution List
     */
    public function IndexPaymentRecovery(Request $request)
    {
        $receipts = $this->master->get_receipt_voucher($request);

        if ($request->ajax()):
            return view($this->page.'TableDataPaymentRecovery',compact('receipts'));
        endif;
        return view($this->page.'IndexPaymentRecovery');
    }
    /**
     * Sale Order Execution
     */
    public function saleOrderExecution($so_id)
    {
        // dd($so_id);
        try {
            $saleOrder = SaleOrder::where('id', $so_id)->first();
            $saleOrderData = SaleOrderData::where('so_id', $so_id)->get();
            // dd($saleOrderData);

            foreach ($saleOrderData as $key => $item) {
                $stock = Stock::create([
                    'parent_id' => $so_id,
                    'child_id' => $item->id,
                    'voucher_no' => $item->so_id,
                    'voucher_date' => $item->created_at,
                    'product_id' => $item->product_id,
                    'distributor_id' => $saleOrder->distributor_id,
                    'stock_received_type' => 1,
                    'stock_type' => 3,
                    'remarks' => '',
                    'qty' => $item->qty,
                    'amunt' => $item->total,
                    'status' => 1,
                    'username' => auth()->user()->name
                ]);
            }
            $saleOrder->excecution = 1;
            $saleOrder->save();
            $this->update_shop_oustanding($saleOrder->shop_id);
            return redirect()->back()->with('success', 'Sale Order Excecuted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'There Might be a Error: '. $th->getMessage());
        }
    }
    /**
     * Payment Recovery Execution
     */
    public function paymentRecoveryExecution($id)
    {
        // dd($so_id);
        try {
            $receiptVoucher = ReceiptVoucher::where('id', $id)->first();
            $receiptVoucher->execution = 1;
            $receiptVoucher->save();
            $this->update_shop_oustanding($receiptVoucher->shop_id , 2);
            return redirect()->back()->with('success', 'Payment Receipt Excecuted Successfully');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'There Might be a Error: '. $th->getMessage());
        }
    }
    /**
     * Sale Order Execution Bulk
     */
    public function bulkSaleOrderExecution(Request $request)
    {
        DB::beginTransaction();
        try {
            $so_ids = $request->checked_records;
            $message='';
            $count=1;
            foreach ($so_ids as $key => $so_id) {
                $saleOrder = SaleOrder::where('id', $so_id)->first();
                if ($saleOrder->excecution==false):
                $saleOrderData = SaleOrderData::where('so_id', $so_id)->get();

                $skip = false;

                foreach ($saleOrderData as $key => $item) {


                  $stock =   $this->master->get_InStock($item->product_id ,$item->flavour_id , $item->sale_type ,$saleOrder->distributor_id,$item->qty);

                    if ($stock == false):
                        $product_price = $this->master->product_price_by_uom_id($item->product_id ,  $item->sale_type);
                        if ($product_price && isset($product_price->pcs_per_carton) && $product_price->pcs_per_carton > 0 ) {
                            $req_ctn = ceil($item->qty / $product_price->pcs_per_carton);
                            $stock = $this->master->get_Stock($item->product_id ,$item->flavour_id , 7 ,$saleOrder->distributor_id);
                            if ($stock >= $req_ctn ) {

                                Stock::create([
                                    "distributor_id"      => $saleOrder->distributor_id,
                                    "voucher_no"      => '',
                                    "stock_received_type" => 1,
                                    "voucher_date"        => $item->created_at,
                                    "stock_type"          => 7,
                                    "remarks"             => '',
                                    "product_id"          => $item->product_id,
                                    "flavour_id"          => $item->flavour_id,
                                    "uom_id"              => 7,
                                    "qty"                 => $req_ctn,
                                ]);

                                $create_qty = $product_price->pcs_per_carton * $req_ctn;

                                Stock::create([
                                    "distributor_id"      => $saleOrder->distributor_id,
                                    "voucher_no"      => '',
                                    "stock_received_type" => 1,
                                    "voucher_date"        => $item->created_at,
                                    "stock_type"          => 6,
                                    "remarks"             => '',
                                    "product_id"          => $item->product_id,
                                    "flavour_id"          => $item->flavour_id,
                                    "uom_id"              => $item->sale_type,
                                    "qty"                 => $create_qty,
                                ]);

                            }
                        }
                        else {
                            $name = $count++ .'-'. $item->product->product_name.'('.$this->master->get_flavour_name($item->flavour_id) . ')' . ($req_ctn-$stock) . 'x' . $this->master->uom_name($item->sale_type);
                            $message .= $name . "\n";
                            $skip = true;
                            continue;
                            // DB::rollBack();
                            // return response(['status'=>'error','msg'=>$name.' Not In stock']);

                        }

                    endif;

                    $stock = Stock::create([
                        'parent_id' => $so_id,
                        'child_id' => $item->id,
                        'voucher_no' => $item->so_id,
                        'voucher_date' => $item->created_at,
                        'product_id' => $item->product_id,
                        'flavour_id' => $item->flavour_id,
                        'uom_id' => $item->sale_type,
                        'distributor_id' => $saleOrder->distributor_id,
                        'stock_received_type' => 1,
                        'stock_type' => 3,
                        'remarks' => '',
                        'qty' => $item->qty,
                        'rate' => $item->rate,
                        'discount_amount' => $item->discount_amount,
                        'tax_amount' => $item->tax_amount,
                        'amunt' => $item->total,
                        'status' => 1,
                        'username' => auth()->user()->name
                    ]);


                }
                if ($skip) {
                    continue;  // continue to the next iteration of the outer loop
                }
                $saleOrder->excecution = 1;
                $saleOrder->save();
                $this->update_shop_oustanding($saleOrder->shop_id);
            endif;
            }



            DB::commit();
            $response = [
                'status' => true,
                'msg' => 'Sale Order Excecuted Successfully',
                'stock' => $message
            ];
            return response($response);
        } catch (\Throwable $th) {
            DB::rollBack();
            $response = [
                'status' => true,
                'msg' => 'There Might be a Error: '. $th->getMessage() .'--'.$saleOrder->invoice_no
            ];

            return response($response);
        }
    }
    /**
     * Payment Recovery Execution Bulk
     */
    public function bulkPaymentRecoveryExecution(Request $request)
    {
        DB::beginTransaction();
        try {
            $ids = $request->checked_records;
            foreach ($ids as $key => $id) {
                $receiptVoucher = ReceiptVoucher::where('id', $id)->first();
                if ($receiptVoucher->execution==false):
                    // $remaning = ShopOutsanding::where('shop_id' , $receiptVoucher->shop_id)->first();
                    // $shop = Shop::where('id' , $receiptVoucher->shop_id)->first();
                    // if ($shop->debit_credit == 1) {
                    //     $remaning_amount = $remaning->so_amount + $remaning->sr_amount + $shop->balance_amount - $remaning->rv_amount;
                    // }
                    // else{
                    //     $remaning_amount = $remaning->so_amount + $remaning->sr_amount - $shop->balance_amount - $remaning->rv_amount;
                    // }
                    // if ($remaning_amount < 1 || $remaning_amount < $receiptVoucher->amount) {
                    //     DB::rollBack();
                    //     $response = [
                    //         'status' => true,
                    //         'msg' => 'Not recivable amount for this shop( '. $shop->company_name.')'
                    //     ];
                    //     return response($response);
                    // }
                $receiptVoucher->execution = 1;
                $receiptVoucher->save();
                $this->update_shop_oustanding($receiptVoucher->shop_id,2);
                endif;
            }

            DB::commit();
            $response = [
                'status' => true,
                'msg' => 'Payment Receipt Excecuted Successfully'
            ];
            return response($response);
        } catch (\Throwable $th) {

            DB::rollBack();
            $response = [
                'status' => true,
                'msg' => 'There Might be a Error: '. $th->getMessage()
            ];
            return response($response);
        }
    }

    public function update_shop_oustanding($shop_id,$type=1)
    {
      if ($type==1):
      $amount =   SaleOrder::where('excecution',1)->where('shop_id',$shop_id)->sum('total_amount');
      elseif($type==2):
      $amount =   ReceiptVoucher::where('execution',1)->where('shop_id',$shop_id)->sum('amount');
        elseif($type==3):
      $amount =  SalesReturn::whereHas('SalesOrder', function ($query) use ($shop_id ) {
                return $query->where('shop_id', '=', $shop_id);
            })->where('excute',1)->sum('amount');
      endif;

      $outstanding = new ShopOutsanding();
      $outstanding = $outstanding->firstOrNew(['shop_id'=>$shop_id]);
      $outstanding->shop_id = $shop_id;
      if ($type==1):
      $outstanding->so_amount = $amount;
      elseif($type==2):
      $outstanding->rv_amount = $amount;
      elseif($type==3):
      $outstanding->sr_amount = $amount;
      endif;
      $outstanding->save();
      return;

    }

    function bill_printing(Request $request)
    {
        if ($request->ajax()):
       $so_data = SaleOrder::with('shop:company_name,id','distributor:distributor_name,id','tso:name,id')
            ->when($request->distributor_id != null , function ($query) use ($request) {
                $query->where('distributor_id',$request->distributor_id);
            })
        //    ->where('distributor_id',$request->distributor_id)
           ->whereBetween('dc_date', [$request->from, $request->to])
           ->when($request->tso_id!=null , function ($query) use ($request){
                $query->where('tso_id',$request->tso_id);
            })->get();
            return view($this->page.'bill_printing_ajax',compact('so_data'));
        endif;
        return view($this->page.'bill_printing');
    }

    function multi_so_view(Request $request)
    {
        $ids = $request->ids;
        // dd($ids);
        if ($ids) {
            $sos = SaleOrder::with('shop:company_name,id,route_id','distributor:distributor_name,id','tso:name,id','saleOrderData')
                    ->whereIn('id',$ids)
                    ->get();

            return view($this->page.'multi_so_view',compact('sos'));
        }
        else{
            return redirect()->back()->with('catchError',"SO not selected");
        }
    }

}
