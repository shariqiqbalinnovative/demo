<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchemeProduct;
use App\Models\SchemeProductData;
use App\Http\Requests\StoreSchemeProductRequest;
use Auth;
use DB;

class SchemeProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, SchemeProduct $scheme_product)
    {
        $scheme_product =   $scheme_product->where('status', 1)->select('id', 'scheme_name', 'description' , 'active' , 'date')->get();
        if ($request->ajax()) :
            return view('pages.Products.SchemeProduct.SchemeProductListAjax', compact('scheme_product'));
        endif;
        return view('pages.Products.SchemeProduct.SchemeProductList');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('pages.Products.SchemeProduct.AddSchemeProduct');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSchemeProductRequest $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {

            $scheme_product = SchemeProduct::create([
                'scheme_name' => $request->scheme_name,
                'description' => $request->description,
            ]);

            $scheme_id = $scheme_product->id;

            foreach ($request->product_id as $key => $value) {
                $product_ids = $request->product_id[$key];
                // $product_ids = implode(',' , $request->product_id[$key]);
                $scheme_product_data = SchemeProductData::create([
                    'scheme_id' => $scheme_id,
                    'product_id' => $product_ids,
                    'sale_type' =>$request->sale_type[$key],
                    'qty' => $request->qty[$key],
                    'scheme_amount' => $request->scheme_amount[$key],
                ]);
            }

            // dd($scheme_product , $scheme_product->id);
            DB::commit();
            return response()->json(['success' => 'Scheme Created Successfully']);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollback();
            dd($th->getMessage());
            return response()->json(['catchError' => $th->getMessage()]);
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $scheme_product = SchemeProduct::find($id);
        return view('pages.Products.SchemeProduct.EditSchemeProduct' , compact('scheme_product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(StoreSchemeProductRequest $request, SchemeProduct $scheme_product)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {

            $scheme_product->update([
                'scheme_name' => $request->scheme_name,
                'description' => $request->description,
            ]);

            $scheme_id = $scheme_product->id;
            // dd($scheme_id);
            SchemeProductData::where('scheme_id', $scheme_id)->delete();

            foreach ($request->product_id as $key => $value) {
                // dd($key);
                // $product_ids = implode(',' , $request->product_id[$key]);
                $product_ids = $request->product_id[$key];
                $scheme_product_data = SchemeProductData::create([
                    'scheme_id' => $scheme_id,
                    'product_id' => $product_ids,
                    'sale_type' =>$request->sale_type[$key],
                    'qty' => $request->qty[$key],
                    'scheme_amount' => $request->scheme_amount[$key],
                ]);
            }

            // dd($scheme_product , $scheme_product->id);
            DB::commit();
            return response()->json(['success' => 'Scheme update Successfully']);
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollback();
            dd($th->getMessage());
            return response()->json(['catchError' => $th->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        SchemeProduct::where('id', $id)->update(['status' => 0]);
        SchemeProductData::where('scheme_id' , $id)->update(['status' => 0]);
        return response()->json(['success' => 'Deleted Successfully!']);
    }

    public function scheme_product_active($id)
    {
        SchemeProduct::where('id', $id)->update(['active' => 1]);
        return response()->json(['success' => 'Activate Successfully!']);
    }

    public function scheme_product_inactive($id)
    {
        SchemeProduct::where('id', $id)->update(['active' => 0]);
        return response()->json(['success' => 'Deactivate Successfully!']);
    }

    public function get_scheme_product(Request $request)
    {
        $product_id = $request->product_id;
        $qty = $request->qty;

        $scheme_product = SchemeProduct::Status()->Active()
        ->join('scheme_product_data' , 'scheme_product_data.scheme_id' , 'scheme_product.id')
        ->whereRaw("FIND_IN_SET(?, scheme_product_data.product_id)", [$product_id])
        ->where('scheme_product_data.qty' , '<=' , $qty)
        ->select('scheme_product.scheme_name','scheme_product.id as scheme_id', 'scheme_product_data.id as scheme_data_id' , 'scheme_product_data.qty' , 'scheme_product_data.scheme_amount')
        ->get();

        // return $this->sendResponse($scheme_product,'Products retrieved successfully.');
        $response = [
            'success' => true,
            'scheme_product'    => $scheme_product,
        ];
        return response()->json($response, 200);

    }

}
