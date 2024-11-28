<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRouteRequest;
use App\Http\Requests\UpdateRouteRequest;
use App\Helpers\MasterFormsHelper;
use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\RouteDay;
use App\Models\Distributor;
use App\Models\ActivityLog;
use App\Models\SubRoutes;
use App\Models\Shop;
use App\Models\TSO;
use DB;

class RouteController extends Controller
{
    public $master;
    public $page;
    public function __construct()
    {
        $this->page = 'pages.Routes.Route.';
        $this->master = new MasterFormsHelper();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request,Route $route)
    {

        $route =  $route::status()->get();
       if ($request->ajax()):
           return  view($this->page.'RouteListAjax',compact('route'));
        endif;
       return  view($this->page.'RouteList');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return  view($this->page.'AddRoute');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRouteRequest $request)
    {

        DB::beginTransaction();
        try {
            $requestData = $request->except(['day']);
            $data =   Route::create($requestData);

            foreach ($request->day as $day):
                RouteDay::create(['route_id'=>$data->id,'day'=>$day]);
            endforeach;

             // dd($route->RouteDay->pluck('day'));
            $route_log = $data->toArray();
            $log_data = array_merge(
                $route_log, // Merge the original array
                [
                    'route_id' => $data->id, // Add additional data
                    'route_days' => $data->RouteDay->pluck('day')->toArray(), // Pluck IDs and convert to array
                ]
            );
            MasterFormsHelper::activity_log_submit($data,$log_data,'route',2, 'Route Create');



            DB::commit();

            return response()->json(['success' => 'Route created successfully.']);
        } catch (\Throwable $th) {
            DB::rollBack();

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
    public function edit(Route $route)
    {
        $route_day=RouteDay::where('route_id',$route->id)->pluck('day')->toArray();
        return  view($this->page.'EditRoute',compact('route','route_day'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRouteRequest $request, Route $route)
    {
        DB::beginTransaction();
        try {
            $route_id = $route->id;
            $requestData = $request->except(['day']);
            $requestData['day'] = $request->day[0];
            $route->update($requestData);


            Shop::where('route_id',$route_id)->update(['tso_id'=>$request->tso_id , 'distributor_id'=>$request->distributor_id]);


            RouteDay::where('route_id',$route_id)->delete();
            foreach ($request->day as $day):
                RouteDay::create(['route_id'=>$route_id,'day'=>$day]);
            endforeach;

            // dd($route->RouteDay->pluck('day'));
            $route_log = $route->toArray();
                $log_data = array_merge(
                    $route_log, // Merge the original array
                    [
                        'route_id' => $route->id, // Add additional data
                        'route_days' => $route->RouteDay->pluck('day')->toArray(), // Pluck IDs and convert to array
                    ]
                );
            MasterFormsHelper::activity_log_submit($route,$log_data,'route',2 , 'Route Update');

            DB::commit();

            return response()->json(['success' => 'Updated successfully.']);

        } catch (\Throwable $th) {
            DB::rollBack();

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
        Route::where('id',$id)->update(['status'=>0]);
        return response()->json(['success'=>'Deleted Successfully!']);
    }

    public function route_log(Request $request)
    {

        if ($request->ajax()):
            $routeId = $request->route_id;
            // dd($routeId);
            $log_data = ActivityLog::where('table_type', Route::class)
            ->when($routeId != null , function ($query) use ($routeId){
                return $query->where('table_id', $routeId);
            })
            ->get();
            // dd($log_data->toArray());
           return  view($this->page.'RouteLogAjax',compact('log_data'));
        endif;
       return  view($this->page.'RouteLog');
    }


    public function TSODayWisePlanner(Request $request)
    {
       $distributor_id= $request->distribuotr_id;
       $tso_id= $request->tso_id;
       $tso = Route::status()->where('distributor_id',$distributor_id)->where('tso_id',$tso_id)->get();

       if ($request->ajax()):
        return view($this->page.'TSODayWisePlannerAjax',compact('tso'));
       endif;

        return view($this->page.'TSODayWisePlanner');
    }

    public function route_tso_wise(Request $request ,Route $route)
    {
      $day = $request->day;
      $ids = $request->ids;

      foreach($day as $key => $row):
        RouteDay::where('route_id',$ids[$key])->delete();
        foreach ($request->day[$key] as $day):
            RouteDay::create(['route_id'=>$ids[$key],'day'=>$day]);
        endforeach;
    //   $route->where('id',$ids[$key])->update(['day'=>$day[$key]]);
      endforeach;

      return redirect()->back()->with('success', 'Routes Updated');
    }


    public function route_transfer(Request $request)
    {
       $distributor_id= $request->distribuotr_id;
       $tso_id= $request->tso_id;
       $tso = Route::status()->where('distributor_id',$distributor_id)->where('tso_id',$tso_id)->get();

       if ($request->ajax()):
        return view($this->page.'RouteTransferAjax',compact('tso' , 'tso_id'));
       endif;

        return view($this->page.'RouteTransfer');
    }

    public function route_transfer_store(Request $request,Route $route)
    {
        // dd($request->all());
      $tso_ids = $request->tso_ids;
      $distributor_ids = $request->distributor_ids;
      $ids = $request->ids;
    // dd($tso_ids , $distributor_ids , $ids);
      foreach($ids as $key => $row):
        if (isset($tso_ids[$key])) {
            $tso = TSO::find($tso_ids[$key]);
            // dd($tso->toArray());
            Shop::where('route_id',$ids[$key])->update(['tso_id'=>$tso_ids[$key] , 'distributor_id'=>$distributor_ids[$key]]);
            Route::where('id',$ids[$key])->update(['tso_id'=>$tso_ids[$key] , 'distributor_id'=>$distributor_ids[$key]]);
        }
        // dd(Shop::where('route_id',$ids[$key])->get() , Route::where('id',$ids[$key])->get() ,$tso_ids[$key]) ;
      endforeach;

      return redirect()->back()->with('success', 'Routes Tranfer Successfully');
    }


    public function GetTsoByDistributor(Request $request)
    {
        $distributor = $request->distribuotr_id;
        $tso = $this->master->get_all_tso_by_distributor_id($distributor);
        return  response()->json(['tso'=>$tso]);
    }

    public function GetAllTsoByDistributor(Request $request)
    {
        $distributor = $request->distribuotr_id;
        $tso = $this->master->get_all_tso_by_distributor_id($distributor , false);
        return  response()->json(['tso'=>$tso]);
    }

    public function GetTsoByMultipleDistributor(Request $request)
    {
        // dd($request->distribuotr_id);
        $distributor = $request->distribuotr_id;
        $tso = $this->master->get_all_tso_by_distributor_ids($distributor);
        return  response()->json(['tso'=>$tso]);
    }
    public function GetRouteBYTSO(Request $request)
    {
        $tso_id = $request->tso_id;
        $route = $this->master->get_all_route_by_tso($tso_id);
        return  response()->json(['route'=>$route]);
    }
    public function get_sub_route(Request $request)
    {
        $route_id = $request->route_id;
        $route = SubRoutes::status()->where('route_id',$route_id)->get();
        return  response()->json(['route'=>$route]);
    }


    function get_distributor_by_city(Request $request){
        // dd($request->city);
        $distributor = new Distributor();
        if ($request->city) {
            $distributor = $distributor->where('city_id' , $request->city);
        }
        $distributor = $distributor->get();
        return  response()->json(['distributor'=>$distributor]);
    }

}
