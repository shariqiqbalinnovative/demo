<?php

namespace App\Http\Controllers\Backend;

use App\Models\Product;
use App\Models\Stock;
use App\Models\SalesReturn;
use App\Models\SalesReturnData;
use App\Models\SaleOrder;
use App\Models\SaleOrderData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class SalesReturnController extends Controller
{


    public function __construct()
    {
        $this->page = 'pages.SalesReturn.';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Product $product)
    {
        $sales_return = SalesReturn::all();
        // echo "<pre>"; print_r($sales_return); die;
        if ($request->ajax()) :
            return view($this->page . 'SalesReturnListAjax', compact('sales_return'));
        endif;
        return view($this->page . 'SalesReturnList');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {

        if ($request->ajax()) :
            $invoice_no = $request->so_no;
            $so_data = SaleOrder::where('invoice_no', $invoice_no)->first();


            return view($this->page . 'AddSalesReturnAjax', compact('so_data'));
        endif;
        return view($this->page . 'AddSalesReturn');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        DB::beginTransaction();
        try {
            $sales_return = new SalesReturn();
            $sales_return->so_id = $request->so_id;
            $main_discount = $sales_return->SalesOrder->discount_percent;
            $sales_return->voucher_no = $sales_return->UniqueNo();
            $sales_return->description = $request->description;
            $sales_return->user_id  = Auth::user()->id;
            $sales_return->save();
            $id = $sales_return->id;

            $total_amount = 0;
            foreach ($request->qty as $key => $row) :
                if ($row > 0) :
                    $sales_return_data = new SalesReturnData();

                    // calc
                    $sales_order_data  = SaleOrderData::where('id', $request->input('so_data_id')[$key])->first();
                    $rate = $sales_order_data->rate;
                    $amount = $rate * $row;
                    $discount = $sales_order_data->discount;
                    $tax = $sales_order_data->tax_percent;
                    ($discount > 0) ? $discount_amount = ($amount / 100) * $discount :  $discount_amount = 0;
                    $amount = $amount - $discount_amount;


                    ($tax > 0) ? $tax_amount = ($amount / 100) * $tax :  $tax_amount = 0;
                    $amont = $amount + $tax_amount;
                    //

                    $sales_return_data->sales_return_id = $id;
                    $sales_return_data->sales_order_data_id  = $request->input('so_data_id')[$key];
                    $sales_return_data->qty = $row;
                    $sales_return_data->save();
                    $total_amount += $amount;
                endif;
            endforeach;


            ($discount_amount > 0) ? $main_discount_amount = ($total_amount / 100) * $main_discount : $main_discount_amount = 0;
            $total_amount = $total_amount - $main_discount_amount;

            $sales_return->amount = $total_amount;
            $sales_return->save();
            DB::commit();
            return response()->json(['success' => 'Return Successfully Saved']);
        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError("Server Error!", ['error' => $th->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $so = SalesReturn::find($id);
        return view($this->page.'viewSalesReturn',compact('so'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        return  view($this->page . 'EditProduct', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        return;
        $product = $product->update($request->all());
        return response()->json(['success' => 'Updated successfully.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        SalesReturn::where('id', $id)->update(['status' => 0]);
        return response()->json(['success' => 'Deleted Successfully!']);
    }

    public function sales_return_list(Request $request, $excution)
    {
        $sales_return = SalesReturn::where('excute', false)->get();

        if ($request->ajax()) :
            return view($this->page . 'SalesReturnListAjax', compact('sales_return', 'excution'));
        endif;
        return view($this->page . 'SalesReturnList', compact('excution'));
    }


    public function sales_return_execution_submit(Request $request)
    {

        DB::beginTransaction();
        try {
            $data = SalesReturn::whereIn('id', $request->id)->where('excute', false)->get();
            // dd($request->id , $data);
            foreach ($data as $key => $return) :

                foreach ($return->SalesReturnData()->get() as $key => $row) :
                    $stock = new Stock();
                    $stock->voucher_no = $return->voucher_no;
                    $stock->voucher_date = date('Y-m-d');
                    $stock->product_id = $row->SalesOrderData->product_id;
                    $stock->flavour_id = $row->SalesOrderData->flavour_id;
                    $stock->uom_id = $row->SalesOrderData->sale_type;
                    $stock->distributor_id = $return->SalesOrder->distributor_id;
                    $stock->stock_type = 4;
                    $stock->qty = $row->qty;
                    $stock->parent_id = $return->id;
                    $stock->child_id = $row->id;
                    $stock->stock_received_type = 1;
                    $stock->save();
                endforeach;

                SalesReturn::where('id', $return->id)->update(['excute' => 1]);
                app('App\Http\Controllers\Backend\ExecutionController')->update_shop_oustanding($return->SalesOrder->shop_id, 3);

            endforeach;
            DB::commit();
            return response()->json(['success' => 'successfully execute.']);

        } catch (Exception $th) {
            DB::rollBack();
            return response()->json(['error'=>'Oops! There might be a error '. $th->getMessage()]);
            // return $this->sendError("Server Error!", ['error' => $th->getMessage()]);
        }
        // return redirect()->back()->with('success', 'successfully execute');
    }

    public function viewPaymentRecoveryDetail($id)
    {
        return view($this->page.'viewPaymentRecoveryDetail');
    }


}
