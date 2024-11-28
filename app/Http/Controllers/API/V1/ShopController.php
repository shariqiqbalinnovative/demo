<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopType;
use App\Models\ShopVisit;
use App\Models\User;
use App\Models\Route;
use App\Helpers\MasterFormsHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Jobs\SendSmsJob;


class ShopController extends BaseController
{
    public function addShop(Request $request)
    {
        $request->validate([
            'contact_person' => 'required',
            'company_name' => 'required',
            'mobile_no' => 'required|unique:shops,mobile_no',
         //   'phone' => 'required',
        //    'alt_phone' => 'required',
        //    'cnic' => 'required',
        //    'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
       //     'shop_type_id' => 'required',
       //     'email' => 'required',
            // 'tso_id' => 'required',
        //    'payment_mode' => 'required',
        //    'note' => 'required',
            'class'=> 'required',
            'route_id'=> 'required',
        ]);
        DB::beginTransaction();
        try {

            $route_data=  Route::where('id',$request->route_id)->first();
            $request['shop_code'] = Shop::UniqueNo();
            $request['tso_id'] = $route_data->tso_id;

            $request['distributor_id'] = $route_data->distributor_id;
            $request['mobile_no'] = MasterFormsHelper::correctPhoneNumber($request['mobile_no']);
            $fileName = '';
            if ($request->file('image')) {
                $file = $request->file('image');
                $fileName = time() . '-' . $file->getClientOriginalName();

                // delete previous image
                // if (Storage::disk('public')->exists($product->image)) {
                //     Storage::disk('public')->delete($product->image);
                // }

                $file->storeAs('shop_image', $fileName, 'public'); // 'uploads' is the directory to store files.
            }
            $data['image'] = $fileName;
            $data = $request->only(['shop_code','distributor_id','tso_id','note','contact_person','company_name','mobile_no','phone','alt_phone','cnic','address','latitude','longitude','shop_type_id','email','payment_mode','route_id','class','balance_amount' , 'debit_credit']);

            $shop = Shop::create($data);
            MasterFormsHelper::users_location_submit($shop,$request->latitude,$request->longitude,'shops', 'Create Shop');

            // SendSmsJob::dispatch( $request['mobile_no'] , "Dear $request->contact_person,\n Welcome to Smile Food Pakistan");

            DB::commit();
            return $this->sendResponse([], 'Shop Add Successfully.');
        } catch (Exception $th) {
            DB::rollBack();
            return $this->sendError('Server Error.', ['error'=>$th->getMessage()]);
        }
    }

    public function userWiseShopList(Request $request)
    {

        $request->validate([
            'route_id' => 'required|exists:routes,id'
        ]);
        $shops = Shop::join('tso','shops.tso_id','tso.id')
        ->leftjoin('sale_orders', function ($join) {
            $join->on('sale_orders.shop_id', 'shops.id')
                ->where('dc_date', date('Y-m-d'));
        })
        ->leftjoin('shop_visits', function ($join) {
            $join->on('shop_visits.shop_id', 'shops.id')
                ->where('visit_date', date('Y-m-d'));
        })
        ->leftjoin('shops_outstandings','shops_outstandings.shop_id','shops.id')
        ->when($request->search != null, function ($query) use ($request) {
            $query->where('shops.company_name', 'Like', '%'.$request->search.'%');
        })
        ->select('shops.*',DB::raw('count(sale_orders.shop_id) as productive'),
        DB::raw('count(shop_visits.shop_id) as visited'),
        DB::raw('(shops_outstandings.so_amount + shops_outstandings.sr_amount +
        case
            when shops.debit_credit = 1 then shops.balance_amount
            when shops.debit_credit = 2 then -shops.balance_amount
        end
         - shops_outstandings.rv_amount) as outstandings'))
        ->groupBy('shops.id')
        ->where('tso.user_id',Auth::user()->id);

        if(!empty($request->route_id))
        {
            $shops->where('shops.route_id',$request->route_id);
        }

        if(!empty($request->id))
        {
            $shops->where('shops.id',$request->id);
        }


        if(!empty($request->cat_id))
        {
            $shops->where('shops.shop_type_id',$request->cat_id);
        }

        $shops = $shops->paginate($request->limit??5);

        return $this->sendResponse([$shops], 'Shop List Successfully Retrive.');
    }

    public function shopTypeList()
    {
        return $this->sendResponse([ShopType::latest()->get()], 'Shop Type List Successfully Retrive.');
    }

    public function visitShopAdd(Request $request)
    {
        $request->validate([
            'shop_id' => 'required',

            'remark' => 'required',
            'visit_date' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            // 'user_id'=> 'required',
        ]);
        $request['user_id'] = Auth::id();

        $marchadising ='';
        if ($request->file('merchandising_image')) {
            $file = $request->file('merchandising_image');
            $marchadising = time() . $file->getClientOriginalName();
            $file->storeAs('visitshope', $marchadising, 'public'); // 'uploads' is the directory to store files.
        }

        if(!empty($marchadising))
            {
               $marchadising = $marchadising;
            }
        $data =$request->only('user_id','shop_id','visit_reason_id','remark','visit_date','latitude','longitude','type');
        $data['merchandising_image'] = $marchadising;
        $visit= ShopVisit::create($data);
        MasterFormsHelper::users_location_submit($visit,$request->latitude,$request->longitude,'shop_visits', 'Shop Visit');
        return $this->sendResponse([], 'Shop Visit Successfully Inserted.');
    }

    public function visitShopList(Request $request)
    {
        $type = $request->type ?? 0;
        $shopVisit = ShopVisit::with('shop:id,company_name,shop_code')->where('shop_visits.user_id',Auth::id())
        ->where('type',$type)
        ->latest()->paginate($request->limit??5);
        return $this->sendResponse([$shopVisit], 'Shop Type List Successfully Retrive.');
    }

    public function updateCordinates(Request $request , $id)
    {

        $request->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $shop = Shop::find($request->id)->update($request->only('latitude', 'longitude'));
        $shop = new Shop();
        $shop  = $shop ->find($id);
        return response()->json(['data'=>$shop,'success' => 'Cordinates Updated successfully.']);
    }
}
