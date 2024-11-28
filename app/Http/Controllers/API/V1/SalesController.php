<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\SaleOrder;
use App\Models\SaleOrderData;
use App\Models\User;
use App\Models\Shop;
use App\Helpers\MasterFormsHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendSmsJob;

class SalesController extends BaseController
{
    public function orderCreate(Request $request)
    {
        date_default_timezone_set("Asia/Karachi");
        $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'notes' => 'required',
            'discount_percent' => 'required',
            'payment_type' => 'required',
            'total_pcs' => 'required',
            'discount_amount' => 'required',
            'total_amount' => 'required',
            'products_subtotal' => 'required',
            'user_id' => 'required|exists:users,id',

            'product_id' => 'required|array',
            'product_id.*' => 'required|gt:0|exists:products,id',  // Validate each item

            'flavour_id' => 'required|array',
            'flavour_id.*' => 'required|gt:0|exists:product_flavours,id',

            'sale_type' => 'required|array',
            'sale_type.*' => 'required|gt:0',

            'rate' => 'required|array',
            'rate.*' => 'required|gt:0',

            'qty' => 'required|array',
            'qty.*' => 'required|gt:0',

            'discount' => 'required|array',
            'discount_amount_data' => 'required|array',
            'total' => 'required|array',
            'total.*' => 'required|gt:0',

            'latitude' => 'required',
            'longitude' => 'required',
        ]);
        DB::beginTransaction();
        try {

            $shop_data=  Shop::where('id',$request->shop_id)->first();
            $distributor_id = $shop_data->distributor_id;

            $request['invoice_no'] = SaleOrder::UniqueNo();
            $tso = User::find($request->user_id)->tso;
            $request['cost_center'] = 0;
            $request['transport_details'] = 0;
            $request['excecution'] =$request->type;
            $request['tso_id'] = $tso->id;
            $request['distributor_id'] = $distributor_id;
            $request['dc_date'] = date('Y-m-d');
            // dd($request->all());
            $signature ='';
            if ($request->file('signature_image')) {
                $file = $request->file('signature_image');
                $signature = time() . '-' . $file->getClientOriginalName();
                $file->storeAs('sales', $signature, 'public'); // 'uploads' is the directory to store files.
            }

            if(!empty($signature))
                {
                   $signature = $signature;
                }

                $marchadising ='';
                if ($request->file('merchandising_image')) {
                    $file = $request->file('merchandising_image');
                    $marchadising =  time() . '-' .$file->getClientOriginalName();
                    $file->storeAs('sales', $marchadising, 'public'); // 'uploads' is the directory to store files.
                }

                if(!empty($marchadising))
                    {
                       $marchadising = $marchadising;
                    }
               $data = $request->only('user_id','tso_id','dc_date','distributor_id','transport_details','cost_center','invoice_no','shop_id','notes','discount_percent','payment_type','total_pcs','discount_amount','total_amount','products_subtotal','excecution');
               $data['signature_image'] = $signature;
               $data['merchandising_image'] = $marchadising;

            $saleOrder = SaleOrder::create($data);
            MasterFormsHelper::users_location_submit($saleOrder,$request->latitude,$request->longitude,'sale_orders', 'Create Sale Order');
            // dd($saleOrder);
            $request['discount_amount'] = $request->discount_amount_data;
            $saleOrder->products_subtotal = 1000;
            $saleOrder->save();

            $total_amount = 0;
            $total_qty = 0;
            // dd($request->all() ,$saleOrder , $saleOrder->total_amount , $saleOrder->products_subtotal);
            foreach ($request->product_id as $key => $product_id) {
                $total = ($request->rate[$key] * $request->qty[$key]);
                $scheme_amount = $request->sheme_amount[$key] ?? 0;
                $trade_offer_amount = $request->trade_offer_amount[$key] ?? 0;
                $discount_amount = isset($request->discount[$key]) && ($request->discount[$key]!=0) ? (( $total / 100 ) * $request->discount[$key]) : 0;
                $total = $total -$discount_amount - $scheme_amount - $trade_offer_amount;
                $saleOrder->saleOrderData()->create([
                    'product_id' => $request->product_id[$key],
                    'flavour_id' => $request->flavour_id[$key],
                    'sale_type' => $request->sale_type[$key],
                    'rate' => $request->rate[$key],
                    'qty' => $request->qty[$key],
                    'foc' => $request->foc[$key] ?? 0,
                    'availability' => $request->availability[$key] ?? 0,
                    'discount' => $request->discount[$key] ?? 0,
                    'discount_amount' => $discount_amount,
                    'total' => $total,
                    'sheme_product_id' => $request->shceme_product_id[$key] ?? 0,
                    'offer_qty' => $request->offer[$key] ?? 0,
                    'scheme_id' => $request->scheme_id[$key] ?? 0,
                    'scheme_data_id' => $request->scheme_data_id[$key] ?? 0,
                    'scheme_amount' => $scheme_amount,
                    'trade_offer_amount' => $trade_offer_amount,

                ]);
                $total_amount+= $total;
                $total_qty += $request->qty[$key];
            }
            $total_amount = $request->total_amount??$total_amount;
            SaleOrder::find($saleOrder->id)->update(['total_amount'=>$total_amount , 'total_pcs'=>$total_qty]);

            // $shop_data['mobile_no'] = MasterFormsHelper::correctPhoneNumber($shop_data['mobile_no']);
            // $text = "New Order has been Booked";
            // $text .= "\nShop Name : $shop_data->company_name";
            // $text .= "\nContact Person : $shop_data->contact_person";
            // $text .= "\n Invoice No : $saleOrder->invoice_no";
            // SendSmsJob::dispatch( $shop_data['mobile_no'] , $text);

            DB::commit();
            return $this->sendResponse([], 'Sale Order Create Successfully.');
        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError("Server Error!",['error'=> $th->getMessage() . ' ' . $th->getLine()]);
        }
    }

    public function orderUpdate(Request $request)
    {


        $request->validate([
            'id' => 'required',
            'product_id' => 'required|array|exists:products,id',
            'flavour_id' => 'required|array|exists:product_flavours,id',

        ]);
        DB::beginTransaction();
        try {

     $sale_oder =  SaleOrder::find($request->id);
     $sale_oder->shop_id = $request->shop_id;
     if($sale_oder->excecution ==  0):

               $sale_oder->save();
            //    $request['discount_amount'] = $request->discount_amount_data;

               SaleOrderData::where('so_id',$request->id)->delete();
                $total_amount = 0;
                $total_qty = 0;
                foreach ($request->product_id as $key => $product_id):
                    $total = ($request->rate[$key] * $request->qty[$key]);
                    $scheme_amount = $request->sheme_amount[$key] ?? 0;
                    $trade_offer_amount = $request->trade_offer_amount[$key] ?? 0;
                    $discount_amount = ($request->discount[$key]!=0) ? (( $total / 100 ) * $request->discount[$key]) : 0;
                    $total = $total -$discount_amount - $scheme_amount - $trade_offer_amount;
                    $sale_oder->saleOrderData()->create([
                        'product_id' => $request->product_id[$key],
                        'flavour_id' => $request->flavour_id[$key],
                        'sale_type' => $request->sale_type[$key],
                        'rate' => $request->rate[$key],
                        'qty' => $request->qty[$key],
                        'foc' => $request->foc[$key] ?? 0,
                        'availability' => $request->availability[$key] ?? 0,
                        'discount' => $request->discount[$key],
                        'discount_amount' => $discount_amount,
                        'total' => $total,
                        'sheme_product_id' => $request->shceme_product_id[$key] ?? 0,
                        'offer_qty' => $request->offer[$key] ?? 0,
                        'scheme_id' => $request->scheme_id[$key] ?? 0,
                        'scheme_data_id' => $request->scheme_data_id[$key] ?? 0,
                        'scheme_amount' => $scheme_amount,
                        'trade_offer_amount' => $trade_offer_amount,
                    ]);
                    $total_amount+= $total;
                    $total_qty += $request->qty[$key];
                endforeach;
                $total_amount = $request->total_amount??$total_amount;
                SaleOrder::find($request->id)->update(['total_amount'=>$total_amount, 'discount_percent' => $request->discount_percent, 'discount_amount' => $request->discount_amount, 'total_pcs'=>$total_qty]);
            else:

            return $this->sendError('Sale excecution.', ['error'=>'Can not update because Sale is excecuted ']);
            endif;


            DB::commit();
            return $this->sendResponse([], 'Sale Order Update Successfully.');

        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError("Server Error!",['error'=> $th->getMessage()]);
        }


    }

    public function orderList(Request $request)
    {
        $sale_oder = SaleOrder::with('shop:id,title,company_name')
        ->where('user_id',$request->user_id);
        // if(!empty($request->type)){
            $sale_oder->where('excecution',$request->type);
        // }
        $sale_oder =  $sale_oder->latest()
        ->paginate($request->limit??50);
        return $this->sendResponse($sale_oder,'SaleOder List Successfully Retrive');
    }
    public function OrderDetails(Request $request)
    {
        if($request->id)
        {
         $id = $request->id;
        $sale_oder = SaleOrder::with('saleOrderData','shop:id,title,company_name')->find($id);
        return $this->sendResponse( $sale_oder,'SaleOder Data');
        }

    }
}
