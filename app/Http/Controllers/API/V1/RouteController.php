<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\API\V1\BaseController;
use App\Models\User;
use App\Models\Distributor;
use App\Models\shop;
use App\Models\Route;
use Illuminate\Support\Facades\Auth;
use DB;

class RouteController extends BaseController
{
    public function getTsoDistributorWiseRoute(Request $request ,$distributor_id)
    {
        // dd(date('l'));
        $tso_id = User::find(Auth::user()->id)->tso->id;

       $routes=   Route::status()->where('tso_id',$tso_id)->where('distributor_id',$distributor_id)
    //    ->with('routeday')
       ->whereHas('RouteDay', function ($query) {
         $query->where('day',date('l'));
        })
        // ->with(['RouteDay' => function ($query) {
        //     $query->where('day', date('l')); // Load only the current day
        // }])
        ->withCount('shops')
        ->get()
        ->map(function($route) {
            $route->day = $route->RouteDay->pluck('day')->implode(', ');
            unset($route->RouteDay);
            return $route;
            // if ($route->routeday->isNotEmpty()) {
            //     $route->day = $route->routeday->first()->day; // Get the first true record
            // } else {
            //     $route->day = null; // Or you can set a default value
            // }
            // return $route;
        })
        ;


        return $this->sendResponse($routes,'Route Retrive Successfully');
    }

    public function getRoutePlan($distributor_id)
    {
          $tso_id =  User::find(Auth::user()->id)->tso->id;
          $routes = Route::status()->where('tso_id',$tso_id)->where('distributor_id',$distributor_id)->withCount('shops')->get()
          ->map(function($route) {
            $route->day = $route->RouteDay->pluck('day')->implode(', ');
            unset($route->RouteDay);
            return $route;

        });
         return $this->sendResponse($routes,'Route Retrive Successfully');
    }
}
