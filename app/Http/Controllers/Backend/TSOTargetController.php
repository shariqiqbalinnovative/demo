<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Attendence;
use App\Models\Product;
use App\Models\TSO;
use App\Models\TSOTarget;
use App\Models\UserAttendence;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use DateTime;
use DateInterval;
use Auth;
use Illuminate\Support\Facades\Validator;


class TSOTargetController extends Controller
{
    public function __construct()
    {
        $this->page = 'pages.TSO.TSOTarget.';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $tso = TSO::all();
        if ($request->ajax()):
            $tso_id = $request->tso_id;
            $month = $request->month;
            $shop_type_id = $request->shop_type_id;
            $products = Product::where('status', 1)->get();
            $total_amount_target = TSOTarget::where(['tso_id' => $tso_id ])
            ->whereYear('month', '=', substr($month, 0, 4))
            ->whereMonth('month', '=', substr($month, 5, 2))
            ->whereNull('product_id')->value('amount');
            // dd($tso_id , $month , $total_amount_target);
            $shop_type = TSOTarget::where(['tso_id' => $tso_id , 'type' => 3])->whereYear('month', '=', substr($month, 0, 4))
            ->whereMonth('month', '=', substr($month, 5, 2))->get();
            return view($this->page.'TableTSOTarget',compact('shop_type_id','tso_id', 'month', 'products' , 'total_amount_target' , 'shop_type'));
        endif;
        return view($this->page.'AddTSOTarget', compact('tso'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validatedData =  Validator::make($request->all(),[
            'tso_id' => 'required',
            'month' => 'required',
            'total_amount_target' => 'required',
        ]);

        if ($validatedData->fails()):

        return response()->json(['error' => $validatedData->errors()]);
        endif;

        $requestData = $request->all();
        $month = date("m",strtotime($request->month));


        $date_fo_month = $request->month; // Assuming $request->month contains "2023-10"

        // Create a DateTime object from the month value
        $date = new DateTime($date_fo_month);

        // Add one day to the date
        $date->add(new DateInterval('P1D'));

        // Format the modified date
        $modifiedMonth = $date->format('Y-m-d');

        $total_amount_target = TSOTarget::where('tso_id', $request->tso_id)
        ->whereMonth('month', $month)
        ->where('product_id' , null)
        ->first();
        if (!$total_amount_target) {
            TSOTarget::create([
                'tso_id' => $request->tso_id,
                'month' => $modifiedMonth,
                'type' => 2,
                'amount' => $requestData['total_amount_target'],
            ]);
        }
        else{
            $total_amount_target->amount =  $requestData['total_amount_target'];
            $total_amount_target->save();
        }

        foreach ($request->product_name as $key => $value) {
            $previous_target = TSOTarget::where('tso_id', $request->tso_id)
            ->whereMonth('month', $month)
            ->where('product_id', $requestData['product_id'][$key])
            ->first();
            // dd($previous_target);
            if (!$previous_target) {
                TSOTarget::create([
                    'tso_id' => $request->tso_id,
                    'product_id' => $requestData['product_id'][$key],
                    'month' => $modifiedMonth,
                    'type' => $requestData['target_type'][$key] == 1 ? 1 : 2,
                    'amount' => $requestData['target_type'][$key] == 2 ? $requestData['amount'][$key] : null,
                    'qty' =>  $requestData['target_type'][$key] == 1 ? $requestData['quantity'][$key] : null,
                ]);
            }else{
                TSOTarget::where('tso_id', $request->tso_id)->where('product_id', $requestData['product_id'][$key])->update([
                    'type' => $requestData['target_type'][$key] == 1 ? 1 : 2,
                    'amount' => $requestData['target_type'][$key] == 2 ? $requestData['amount'][$key] : null,
                    'qty' =>  $requestData['target_type'][$key] == 1 ? $requestData['quantity'][$key] : null,
                ]);
            }
        }

        //////// ---------- Shop type target

       TSOTarget::where('tso_id', $request->tso_id)
                ->whereMonth('month', $month)
                ->where('type' , 3)
                // ->where('shop_type', $requestData['shop_type_id'][$key])
                ->delete();

        if (isset($request['shop_type_id'])) {
            foreach ($request['shop_type_id'] as $key => $value) {
                TSOTarget::create([
                    'tso_id' => $request->tso_id,
                    'shop_type' => $requestData['shop_type_id'][$key],
                    'month' => $modifiedMonth,
                    'type' => 3,
                    'shop_qty' =>  $requestData['target_type'][$key] == 1 ? $requestData['shop_qty'][$key] : null,
                ]);
                // $previous_target = TSOTarget::where('tso_id', $request->tso_id)
                // ->whereMonth('month', $month)
                // ->where('shop_type', $requestData['shop_type_id'][$key])
                // ->first();
                // // dd($previous_target);
                // if (!$previous_target) {
                //     TSOTarget::create([
                //         'tso_id' => $request->tso_id,
                //         'shop_type' => $requestData['shop_type_id'][$key],
                //         'month' => $modifiedMonth,
                //         'type' => 3,
                //         'shop_qty' =>  $requestData['target_type'][$key] == 1 ? $requestData['shop_qty'][$key] : null,
                //     ]);
                // }else{
                //     TSOTarget::where('tso_id', $request->tso_id)->where('shop_type', $requestData['shop_type_id'][$key])->update([
                //         'type' => 3,
                //         'shop_qty' =>  $requestData['target_type'][$key] == 1 ? $requestData['shop_qty'][$key] : null,
                //     ]);
                // }
            }
        }
        return response()->json(['success'=>'TSO Target Assigned Successfully']);

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
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function tso_summary_report()
    {
        return view('pages.Reports.TsoSummary');
    }

    public function tso_summary_report_data(Request $request){



    }
    public function addAttendence(Request $request)
    {
        $attendence = new UserAttendence;
        $attendence->user_id =$request->id;
        $attendence->date= Carbon::now();
        $attendence->attendence_status=1;
        $attendence->status=1;
        if($attendence->save())
        {
            return 1;
        }else{
            return 0;
        }

    }
    public function attendenceList(Request $request)
    {
       if($request->Ajax())
       {

            $from =$request->from;
            $to =$request->to;
            $startDate = date('Y-m-d 00:00:00', strtotime($from)); // "2023-08-04 00:00:00"
            $endDate = date('Y-m-d 23:59:59', strtotime($to));
                if(Auth::user()->user_type == 1){
                $Attendence =  UserAttendence::whereBetween('date',[$startDate,$endDate])->get();

                }else{
                $Attendence =  UserAttendence::where('user_id',Auth::user()->id)->whereBetween('date',[$from,$to])->get();
                }
                return view('pages.Attendence.indexAjax',compact('Attendence'));

         }
        return view('pages.Attendence.index');
    }
}
