<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController as BaseController;
use App\Models\Product;
use App\Models\SchemeProduct;
use App\Models\SchemeProductData;
use App\Models\ProductFlavour;
use App\Models\ProductPrice;
use App\Http\Requests\StoreProductRequest;
use DB;

class ProductController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $products = Product::with(['stocks' => function ($query) {
            $query->select('product_id',
                DB::raw('(SUM(CASE WHEN stock_type IN (0, 1, 2, 4) THEN qty ELSE 0 END) - SUM(CASE WHEN stock_type IN (3, 5) THEN qty ELSE 0 END)) as net_quantity')
            )
            ->groupBy('product_id');
        },'Category','Brand' , 'product_flavour' , 'product_price.uom','uom' , 'packing_uom'])->Status()
        ->where('product_type_id',"!=",4)
        ->when($request->search != null, function ($query) use ($request) {
            $query->where('product_name', 'Like', '%'.$request->search.'%');
        })
        // ->latest()->get();
        ->latest()->paginate($request->limit??100);



        $pdfUrl =  asset('storage/app/public/product_pdf_list.pdf');
        // Share on WhatsApp
        $whatsappLink = 'https://api.whatsapp.com/send?text=' . urlencode($pdfUrl);

        return response()->json([ 'success' => true , 'data' => $products, 'pdf_url' => $pdfUrl, 'whatsappLink' => $whatsappLink ,'message' => 'Products retrieved successfully.']);
        // return $this->sendResponse($products,'Products retrieved successfully.');
    }

    public function list()
    {

        $products = Product::with(['product_flavour' => function($query) {
                $query->select('id', 'flavour_name', 'product_id');
            }])
            ->select('product_name','retail_price','retail_price_packing' , 'retail_price_carton' ,'trade_price','trade_price_packing' , 'trade_price_carton'  ,DB::raw('COALESCE(image, "-") as image'), DB::raw('COALESCE(description, "-") as description'))
            ->Status()
            ->latest()
            ->get();
        return $this->sendResponse($products, 'Products retrieved successfully.');
    }

    // public function get_scheme_product()
    // {
    //   $product =   Product::where('status',1)->where('product_type_id',4)->get();
    //   return $this->sendResponse($product,'Products retrieved successfully.');
    // }

    public function get_scheme_product(Request $request)
    {
        $product_id = $request->product_id;
        $qty = $request->qty;

        // $scheme_product = SchemeProduct::Status()
        // ->with(['SchemeProductData' => function($query) use ($product_id , $qty) {
        //     // Filter the SchemeProductData where the product_id matches in the comma-separated values
        //     $query->whereRaw("FIND_IN_SET(?, product_id)", [$product_id])
        //     ->where('qty' , '<=' , $qty);
        // }])
        // ->get();

        $scheme_product = SchemeProduct::Status()->Active()
        ->join('scheme_product_data' , 'scheme_product_data.scheme_id' , 'scheme_product.id')
        ->whereRaw("FIND_IN_SET(?, scheme_product_data.product_id)", [$product_id])
        ->where('scheme_product_data.qty' , '<=' , $qty)
        ->select('scheme_product.scheme_name','scheme_product.id as scheme_id', 'scheme_product_data.id as scheme_data_id' , 'scheme_product_data.qty' , 'scheme_product_data.scheme_amount')
        ->get();

        // dd($request->all() , $scheme_product->toArray());

        return $this->sendResponse($scheme_product,'Products retrieved successfully.');
    }

    // $schemeProducts = SchemeProductData::all();

    // // Use flatMap to explode and gather unique product IDs
    // $product_ids = $schemeProducts->flatMap(function ($schemeProduct) {
    //     $ids = explode(',', $schemeProduct->product_id);
    //     $qty = $schemeProduct->qty;
    //     // return $data;
    //     // Return an array of product IDs paired with their quantities
    //     return collect($ids)->map(function ($id) use ($qty) {
    //         return ['product_id' => trim($id), 'qty' => $qty]; // Use trim to remove any whitespace
    //     });
    // });

    // // Group by product ID and sum the quantities
    // $groupedProductData = $product_ids->groupBy('product_id')->map(function ($group) {
    //     return [
    //         'product_id' => $group->first()['product_id'],
    //         'qty' => $group->first()['qty'] // Sum the quantities for the same product ID
    //     ];
    // })->values(); // Reset array keys

    public function get_all_scheme_product(Request $request)
    {
        // $product_id = SchemeProductData::groupby('product_id')->pluck('product_id');
        $data = null;

        $schemeProducts = SchemeProductData::all();

        // Use flatMap to explode and gather unique product IDs
        $product_ids = $schemeProducts->flatMap(function ($schemeProduct) {
            return explode(',', $schemeProduct->product_id);
        })->unique()->values();

        foreach ($product_ids as $key => $value) {
            // dump($va);
            $product_qty = SchemeProduct::Status()->Active()
            ->join('scheme_product_data' , 'scheme_product_data.scheme_id' , 'scheme_product.id')
            ->whereRaw("FIND_IN_SET(?, scheme_product_data.product_id)", [$value])
            ->select('scheme_product_data.qty')
            ->groupBy('scheme_product_data.qty')
            ->get();
            // dd($product_qty);
            // $data[$key]['product_id']= $value;
            // $data[$value] = $value;

            foreach ($product_qty as $key2 => $value2) {
                # code...
                // dd($value2->qty);
                $product_data = SchemeProduct::Status()->Active()
                ->join('scheme_product_data' , 'scheme_product_data.scheme_id' , 'scheme_product.id')
                ->whereRaw("FIND_IN_SET(?, scheme_product_data.product_id)", [$value])
                ->where('scheme_product_data.qty' , $value2->qty)
                ->select('scheme_product.scheme_name','scheme_product_data.product_id','scheme_product.id as scheme_id' , 'scheme_product_data.id as scheme_data_id' , 'scheme_product_data.qty' , 'scheme_product_data.scheme_amount')
                ->get()->toArray();

                $data[$value][$value2->qty] = $product_data;
                // $data[$key]['qty_list'][$key2]['qty'] = $value2->qty;
                // $data[$key]['qty_list'][$key2]['scheme_product'] = $product_data;
            }


            // $product_data = SchemeProduct::Status()->Active()
            // ->join('scheme_product_data' , 'scheme_product_data.scheme_id' , 'scheme_product.id')
            // ->whereRaw("FIND_IN_SET(?, scheme_product_data.product_id)", [$value])
            // ->select('scheme_product.scheme_name','scheme_product_data.product_id','scheme_product.id as scheme_id' , 'scheme_product_data.id as scheme_data_id' , 'scheme_product_data.qty' , 'scheme_product_data.scheme_amount')
            // ->get()->toArray();
            // $data[$key]['product_id']= $value;
            // $data[$key]['scheme_product']= $product_data;

        }
        return $this->sendResponse($data,'Scheme Products retrieved successfully.');


        // dd($data) ;
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
            $input = $request->except('flavour_name' , 'uom_id' , 'retail_price' , 'trade_price');
            $fileName ='';
            if ($request->file('image')) {
                $file = $request->file('image');
                $fileName = $file->getClientOriginalName();
                $file->storeAs('uploads', $fileName, 'public'); // 'uploads' is the directory to store files.
            }
            $input['image'] = $fileName;
            $input['product_code'] = Product::UniqueNo();
            $product = Product::create($input);


            $product_id = $product->id;

            $flavour_data= $request->input('flavour_name');
            if ($flavour_data) {
                foreach ($flavour_data as $flavour_name) {
                    ProductFlavour::create([
                        'product_id' => $product_id,
                        'flavour_name' => $flavour_name,
                    ]);
                }
            }


            $product_price_data= $request->input('retail_price');
            if ($product_price_data) {
                foreach ($product_price_data as $key => $price) {
                    ProductPrice::create([
                        'product_id' => $product_id,
                        'uom_id' => $request->uom_id[$key],
                        'retail_price' => $request->retail_price[$key],
                        'trade_price' => $request->trade_price[$key],
                    ]);
                }
            }

            DB::commit();
            return $this->sendResponse($product, 'Product created successfully.');

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error'=>'Oops! There might be a error '. $th->getMessage() . '-' . $th->getLine()]);
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
        $product = Product::with(['Category' ,'Brand' , 'product_flavour'])->find($id);

        if (is_null($product)) {
            return $this->sendError('Product not found.');
        }

        return $this->sendResponse($product, 'Product retrieved successfully.');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        DB::beginTransaction();
        try {
            $input = $request->except('flavour_name');
            $fileName ='';
            if ($request->file('image')) {
                $file = $request->file('image');
                $fileName = $file->getClientOriginalName();
                $file->storeAs('uploads', $fileName, 'public'); // 'uploads' is the directory to store files.
            }
            $product = Product::find($id);
            // if(!empty($fileName))
            //     {
            //     $product->image = $fileName;
            //     }
            // $product->product_name = $input['product_name'];
            // $product->sales_price = $input['sales_price'];
            // $product->description = $input['description'];
            // $product->save();
            $product->update($input);

            $product_id = $id;

            $flavour_data= $request->input('flavour_name');
            if ($flavour_data) {
                foreach ($flavour_data as $flavour_name) {
                    ProductFlavour::create([
                        'product_id' => $product_id,
                        'flavour_name' => $flavour_name,
                    ]);
                }
            }

            DB::commit();
            return $this->sendResponse($product, 'Product updated successfully.');



        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error'=>'Oops! There might be a error '. $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
       // $product->delete();

        return $this->sendResponse([], 'Product deleted successfully.');
    }
}
