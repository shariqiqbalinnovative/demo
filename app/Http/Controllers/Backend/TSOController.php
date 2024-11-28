<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTSORequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Distributor;
use App\Models\SubRoutes;
use App\Models\TSO;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\UsersLocation;
use App\Models\Shop;
use App\Models\Route;
use App\Models\City;
use Auth;
use App\Models\RouteDay;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\MasterFormsHelper;
use App\Http\Middleware\TsoMaxLimit;
use Illuminate\Support\Facades\Hash;
use App\Notifications\RequestNotification;

class TSOController extends Controller
{
    protected $page;
    public function __construct()
    {
        // Apply middleware only to specific methods
        $this->middleware('tso_max_limit')->only(['store' , 'tso_active']);

        $this->page = 'pages.TSO.';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if ($request->ajax()) :
            return view($this->page . 'TableTSO');
        endif;
        return view($this->page . 'IndexTSO');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tso_code = TSO::UniqueNo();
        $departments = Department::all();
        $designations = Designation::all();
        $distributors = Distributor::where('status', 1)->get();
        $users = User::all();
        $roles = Role::all();
        $cities = City::whereIn('state_id',['2729','2728','2727','2726','2725','2724','2723'])->get();
        return  view($this->page . 'AddTSO', compact('users', 'designations', 'departments', 'roles', 'tso_code', 'distributors','cities'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreTSORequest $request)
    {


        DB::beginTransaction();
        try {
            // dd('asdsad');
            $inputs = $request->except('_token', 'email', 'password', 'user_name', 'role','distributor','shop_location');

            $input_user = $request->only('name', 'email', 'password');
            $input_user['username'] = $request->name;
            $input_user['user_type'] = 5;

            $fileName ='';
            if ($request->file('image_path')) {
                $file = $request->file('image_path');
                $fileName = time() . '-'. $file->getClientOriginalName();
                $file->storeAs('profile', $fileName, 'public'); // 'uploads' is the directory to store files.
            }
            $input_user['image'] = $fileName;


            $user = User::create($input_user);

            $user->distributors()->attach($request->distributor);

            if ($request->role != '') :
                $user->assignRole('tso');
            endif;
            if ($request->shop_location != 1) {
                $inputs['location_name'] = null;
                $inputs['latitude'] = null;
                $inputs['longitude'] = null;
                $inputs['radius'] = null;
            }
            $inputs['user_id'] = $user->id;
            $inputs['tso_code'] = TSO::UniqueNo();
            $inputs['distributor_id'] = $request->distributor[0];
            $tso = TSO::create($inputs);
            // dd($tso->toArray());
            $tso_log = $tso->toArray();
            $log_data = array_merge(
                $tso_log, // Merge the original array
                [
                    'tso_id' => $tso->id, // Add additional data
                    'distributors_id' => $user->distributors->pluck('id')->toArray(), // Pluck IDs and convert to array
                ]
            );
            MasterFormsHelper::activity_log_submit($tso,$log_data,'tso',1 , 'Order Booker Create');


            DB::commit();

            $tso_code = TSO::UniqueNo();

            return response()->json(['success' => 'TSO Created Successfully' , 'code' => $tso_code ]);
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
    }

    public function viewProfile($id)
    {
        // dd($id);
        $tso = TSO::find($id);
        $user_data = User::find($tso->user_id);

        return view('pages.TSO.viewProfile' , compact('user_data'));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $tso = TSO::findOrFail($id);
        $departments = Department::all();
        $designations = Designation::all();
        $distributors = Distributor::where('status', 1)->get();
        $users = User::all();
        $roles = Role::all();
        $cities = City::whereIn('state_id',['2729','2728','2727','2726','2725','2724','2723'])->get();

        return  view($this->page . 'EditTSO', compact('tso', 'users', 'designations', 'departments', 'roles', 'distributors','cities'));
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
        DB::beginTransaction();
        try {
            $tso = TSO::findOrFail($id);

            $inputs = $request->except('_token', 'email', 'password', 'user_name', 'role','distributor','shop_location');
            // dd($inputs  , $request->email);
            // dd($request->password , $request->email);
            if ($request->password != null || $request->email != null) {
                // dd('asdsad');
                $user = User::find($tso->user_id);
            //    $user->password = $request->password ?? $user->password;
                $fileName ='';
                if ($request->file('image_path')) {
                    $file = $request->file('image_path');
                    $fileName = time() .'-'. $file->getClientOriginalName();
                    $file->storeAs('profile', $fileName, 'public'); // 'uploads' is the directory to store files.

                    $user->image = $fileName;
                }


                $user->email = $request->email ?? $user->email;
                $user->name = $request->name ?? $user->name;
                if ($request->password != null) {
                    $user->password = $request->password;
                }
                $user->update();
                $user->distributors()->sync($request->distributor);
            }
            if ($request->shop_location != 1) {
                $inputs['location_name'] = null;
                $inputs['latitude'] = null;
                $inputs['longitude'] = null;
                $inputs['radius'] = null;
            }
            $inputs['distributor_id'] = $request->distributor[0];
            $tso->update($inputs);

            // $activity_log = [
            //     'user_name' => Auth::user()->name,
            //     '' => Auth::user()->name,
            // ];
            // ActivityLog::create($activity_log);
            // dd($user->distributors->pluck('id') , url()->full() ,  request()->method());
            // dd($tso->toArray());
            $tso_log = $tso->toArray();
            $log_data = array_merge(
                $tso_log, // Merge the original array
                [
                    'tso_id' => $tso->id, // Add additional data
                    'distributors_id' => $user->distributors->pluck('id')->toArray(), // Pluck IDs and convert to array
                ]
            );
            // dd($log_data);
            MasterFormsHelper::activity_log_submit($tso,$log_data,'tso',1 , 'Order Booker Update');

            DB::commit();
            return response()->json(['success' => 'TSO Updated Successfully']);
        } catch (Exception $th) {
            //throw $th;
            DB::rollback();
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
        $status = TSO::where('id',$id)->value('status');
        $status =  ($status==0) ? 1 : 0;
        $tso = TSO::where('id', $id)->update(['status' => $status]);
        return response()->json(['success' => 'TSO Deleted Successfully']);
    }

    public function tso_active($id)
    {
        DB::beginTransaction();
        try {
            if (Auth::user()->hasAnyRole(['CEO','Super Admin'])) {
                TSO::where('id', $id)->update(['active' => 1 , 'status_username' => Auth::user()->name , 'status_user_id' => Auth::user()->id]);
                $message = 'Activate Successfully!';
                $heading = 'Activated!';
                $log_messaage = 'Approve Activate Order Booker';
            }
            else {
                TSO::where('id', $id)->update(['active' => 2 , 'status_username' => Auth::user()->name , 'status_user_id' => Auth::user()->id]);
                $message = 'Activate Request Submitted!';
                $heading = 'Request Submitted!';
                $log_messaage = 'Order Booker Activate Request';
            }
            $tso = TSO::find($id);
            $user = User::find($tso->user_id);
            $tso_log = $tso->toArray();
            $log_data = array_merge(
                $tso_log, // Merge the original array
                [
                    'tso_id' => $tso->id, // Add additional data
                    'distributors_id' => $user->distributors->pluck('id')->toArray(), // Pluck IDs and convert to array
                ]
            );
            // dd($tso , TSO::find($id));
            MasterFormsHelper::activity_log_submit($tso,$log_data,'tso',1 , $log_messaage);

            DB::commit();
            return response()->json(['success' => $message ,'heading'=>$heading]);
        } catch (Exception $th) {
            //throw $th;
            DB::rollback();
            return response()->json(['catchError' => $th->getMessage()]);
        }
    }

    public function tso_inactive($id)
    {
        DB::beginTransaction();
        try {
            if (Auth::user()->hasAnyRole(['CEO','Super Admin'])) {
                $log_messaage = 'Approve Deactivate Order Booker';
                $message = 'Deactivate Successfully!';
                $heading = 'Deactivated!';
                TSO::where('id', $id)->update(['active' => 0, 'status_username' => Auth::user()->name , 'status_user_id' => Auth::user()->id]);
            }
            else
            {
                TSO::where('id', $id)->update(['active' => 3, 'status_username' => Auth::user()->name , 'status_user_id' => Auth::user()->id]);
                $message = 'Deactivate Request Submitted!';
                $heading = 'Request Submitted!';
                $log_messaage = 'Order Booker Deactivate Request';
            }
            $tso = TSO::find($id);
            $user = User::find($tso->user_id);
            $tso_log = $tso->toArray();
            // dd($tso , $log_data);
            $log_data = array_merge(
                $tso_log, // Merge the original array
                [
                    'tso_id' => $tso->id, // Add additional data
                    'distributors_id' => $user->distributors->pluck('id')->toArray(), // Pluck IDs and convert to array
                ]
            );
            MasterFormsHelper::activity_log_submit($tso,$log_data,'tso',1, $log_messaage);
            DB::commit();
            return response()->json(['success' => $message ,'heading'=>$heading]);
        } catch (Exception $th) {
            //throw $th;
            DB::rollback();
            return response()->json(['catchError' => $th->getMessage()]);
        }
    }

    public function tso_status_request(Request $request)
    {

        if ($request->ajax()) :
            $status_request = $request->status_request;
            // dd($status_request);
            return view($this->page . 'ApprovalStatusTSOAjax' , compact('status_request'));
        endif;
        return view($this->page . 'ApprovalStatusTSO');
    }

    public function tso_status_request_post(Request $request)
    {
        // dd($request->all());
        DB::beginTransaction();
        try {
            $ids = $request->checked_records;
            $status = $request->status;
            // dd($ids , $status);
            if ($status == 3) {

                if ($request->approve_reject == 1) {
                    $message = 'Deactivate Successfully!';
                    $m = 'Deactivate Request Approve';
                    $active = 0;
                    $log_messaage = 'Approve Deactivate Order Booker';

                }
                else
                {
                    $message = 'Deactivate Request Rejected!';
                    $m = 'Deactivate Request Reject';
                    $active = 1;
                    $log_messaage = 'Reject Deactivate Order Booker';
                }

                foreach (TSO::whereIn('id', $ids)->get() as $key => $row) {
                    $user = User::find(Auth::id());
                    $user2 = User::find($row->status_user_id);

                    $msg = 'TSO (' .$row->name. ') '. $m .' by '.$user->name;
                    $user2->notify(new RequestNotification($user2 , $user , $msg , route('tso.index')));
                }


                TSO::whereIn('id', $ids)->update(['active' => $active, 'status_username' => Auth::user()->name ,  'status_user_id' => Auth::user()->id , 'remarks' => $request->remarks]);

            }
            else
            {
                // Instantiate the middleware
                if (($response = app()->make('App\Http\Middleware\TsoMaxLimit')->handle($request, function () {})) instanceof \Illuminate\Http\JsonResponse) {
                    return $response; // Return response if middleware blocks
                }

                if ($request->approve_reject == 1) {
                    $message = 'Activate Successfully!';
                    $m = 'Activate Request Approve';
                    $log_messaage = 'Approve Activate Order Booker';
                    $active = 1;
                }
                else
                {
                    $message = 'Activate Request Rejected!';
                    $m = 'Activate Request Reject';
                    $log_messaage = 'Reject Activate Order Booker';
                    $active = 0;
                }


                foreach (TSO::whereIn('id', $ids)->get() as $key => $row) {
                    $user = User::find(Auth::id());
                    $user2 = User::find($row->status_user_id);

                    $msg = 'TSO (' .$row->name. ') '. $m .' by '.$user->name;
                    $user2->notify(new RequestNotification($user2 , $user , $msg , route('tso.index')));
                }

                TSO::whereIn('id', $ids)->update(['active' => $active, 'status_username' => Auth::user()->name , 'status_user_id' => Auth::user()->id , 'remarks' => $request->remarks]);

            }

            foreach ($ids as $key => $id) {
                # code...
                $tso = TSO::find($id);
                $user = User::find($tso->user_id);
                $tso_log = $tso->toArray();
                // dd($tso , $log_data);
                $log_data = array_merge(
                    $tso_log, // Merge the original array
                    [
                        'tso_id' => $tso->id, // Add additional data
                        'distributors_id' => $user->distributors->pluck('id')->toArray(), // Pluck IDs and convert to array
                    ]
                );
                MasterFormsHelper::activity_log_submit($tso,$log_data,'tso',1,$log_messaage);
            }

            // dd($message);
            DB::commit();
            return response()->json(['success' => $message]);
            // return response()->json(['success' => 'Deactivate  Request Submitted!']);
        } catch (Exception $th) {
            //throw $th;
            DB::rollback();
            return response()->json(['catchError' => $th->getMessage()]);
        }
    }


    public function activity(Request $request)
    {
        if ($request->ajax()) {
            $user = TSO::findOrFail($request->tso_id)->user_id;
            $tsoActivities = UsersLocation::where('user_id', $user)->whereDate('created_at', $request->date)
            // ->select('users_locations.id as u_id','users_locations.latitude','users_locations.longitude','users_locations.location_title','users_locations.table_name','users_locations.created_at')
            ->orderBy('created_at','asc')->with('location')->get();
            // dd($tsoActivities);
            return view($this->page . 'Activity.activityAjax', compact('tsoActivities'));
        }
        // dd($request->all());

        return view($this->page . 'Activity.activity');
    }


    function getAddress($latitude, $longitude)
    {
        //google map api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?key=".env('MAP_KEY')."&latlng=48.283273,14.295041&sensor=false";

        // send http request
        $geocode = file_get_contents($url);
        $json = json_decode($geocode);
        $address = isset($json->results[0]) ? $json->results[0]->formatted_address : '';
        return $address;
    }

    public function tso_log(Request $request)
    {

        if ($request->ajax()) :
            $tsoId = $request->tso_id;
            // dd($tsoId);
            // $data = TSO::with()->get();
            $log_data = ActivityLog::where('table_type', TSO::class)
            ->when($tsoId != null , function ($query) use ($tsoId){
                return $query->where('table_id', $tsoId);
            })
            ->get(); // Get all TSOs along with their activity logs

            // dd($log_data->toArray());

            return view($this->page . 'TSOLogAjax' , compact('log_data'));
        endif;
        return view($this->page . 'TSOLog');
    }




    public function ImportTSO()
    {
        return view($this->page.'ImportTSO');
    }
    public function import_tso_store(Request $request)
    {
        DB::beginTransaction();
        try {
            $file = Excel::toArray([], $request->file('file'));
            $file = $file[0];
            $userRole = [];
            $tsoExistis = [];
            $DistributorNotExist = [];
            $tsoNotMatch = [];
            $designationNotExist = [];
            $departmentNotExist = [];
            $managerNotExist = [];


            // $explode = explode(',' ,  $file[1][0]);
            // dd($file , $file[1][0] , $explode , trim($explode[0]));

            if($file[0][0] == trim("Distributor Name") && $file[0][1] == trim("Name") && $file[0][2] == trim("Company Name") && $file[0][3] == trim("Employee ID") &&
            $file[0][4] == trim("Email") && $file[0][5] == trim("Phone") && $file[0][6] == trim("Cell Phone") && $file[0][7] == trim("CNIC")
            && $file[0][8] == trim("Address") && $file[0][9] == trim("City") && $file[0][12] == trim("Password")){
                foreach ($file as $key => $value) {
                    if($key == 0) continue ;
                    $distributor_ids=[];
                    $explode_distributor = explode(',',$value[0]);
                    foreach ($explode_distributor as $key1 => $value1) {
                        $s_id = Distributor::where('distributor_name' , trim($value1))->value('id');
                        if(!$s_id) array_push($DistributorNotExist,$value1) ;
                        if(!$s_id) continue ;

                        $distributor_ids[] = $s_id;
                    }
                    if(empty($distributor_ids)) array_push($tsoNotMatch,$value[1]) ;
                    if(empty($distributor_ids)) continue ;
                    // dd($distributor_ids , empty($distributor_ids));

                    $designation_id = null;
                    $department_id = null;
                    $manager = null;
                    if (trim($value[16])) {
                        $designation_id =  Designation::where('name',trim($value[16]))->value('id');
                        if(!$designation_id) array_push($designationNotExist,$value[16]) ;
                        if(!$designation_id) continue ;
                    }
                    if (trim($value[15])) {
                        $department_id =  Department::where('name',trim($value[15]))->value('id');
                        if(!$department_id) array_push($departmentNotExist,$value[15]) ;
                        if(!$department_id) continue ;
                    }
                    if (trim($value[14])) {
                        $manager =  User::where('name',trim($value[14]))->value('id');
                        if(!$manager) array_push($managerNotExist,$value[14]) ;
                        if(!$manager) continue ;
                    }

                    $city_id =  City::where('name',$value[9])->value('id');


                    $input_user['name'] = $value[1];
                    $input_user['email'] = $value[4];
                    $input_user['password'] = $value[12];
                    $input_user['username'] = $value[1];
                    $input_user['user_type'] = 5;
                    $user = User::create($input_user);

                    $user->distributors()->attach($distributor_ids);


                    if (trim($value[13])) {
                        $role = Role::where('name' , trim($value[13]))->value('name');
                        if(!$role) array_push($userRole,$value[13]) ;
                        if(!$role) continue ;
                    }

                    $user->assignRole($role);

                    $date_of_join = null;
                    if (trim($value[17])) {
                        $unixTimestamp = ($value[17] - 25569) * 86400; // 25569 is the difference between Excel's base date and Unix epoch
                        $date_of_join = gmdate("Y-m-d", $unixTimestamp); // Format as day/month/year
                    }

                    $date_of_leaving = null;
                    if (trim($value[18])) {
                        $unixTimestamp = ($value[18] - 25569) * 86400; // 25569 is the difference between Excel's base date and Unix epoch
                        $date_of_leaving = gmdate("Y-m-d", $unixTimestamp); // Format as day/month/year
                    }


                    $inputs['tso_code'] = TSO::UniqueNo();
                    $inputs['name'] = $value[1];
                    $inputs['company_name'] = $value[2];
                    $inputs['emp_id'] = $value[3];
                    $inputs['phone'] = $value[5];
                    $inputs['cell_phone'] = $value[6];
                    $inputs['address'] = $value[8];
                    $inputs['cnic'] = $value[7];
                    $inputs['city'] = $city_id;
                    $inputs['state'] = $value[10];
                    $inputs['country'] = $value[11];
                    $inputs['manager'] = $manager;
                    $inputs['department_id'] = $department_id;
                    $inputs['designation_id'] = $designation_id;
                    $inputs['date_of_join'] = $date_of_join;
                    $inputs['date_of_leaving'] = $date_of_leaving;

                    $inputs['user_id'] = $user->id;
                    $inputs['distributor_id'] = $distributor_ids[0];
                    $tso = TSO::create($inputs);


                }

                DB::commit();
                if($tsoExistis){Session::flash('exists',$tsoExistis);}
                if($DistributorNotExist){Session::flash('DistributorNotExist',$DistributorNotExist);}
                if($userRole){Session::flash('userRole',$userRole);}
                if($tsoNotMatch){Session::flash('tsoNotMatch',$tsoNotMatch);}
                if($designationNotExist){Session::flash('designationNotExist',$designationNotExist);}
                if($departmentNotExist){Session::flash('departmentNotExist',$departmentNotExist);}
                if($managerNotExist){Session::flash('managerNotExist',$managerNotExist);}

                return redirect()->back();

            }else{
                // dd('catch error');
                return redirect()->back()->with('catchError',"Format Not Match");
            }

        } catch (Exception $th) {
            //throw $th;
            DB::rollback();
            return response()->json(['catchError' => $th->getMessage()]);
        }
    }



    function import_shop(Request $request,$id)
    {
        return view($this->page.'import_shops',compact('id'));
    }

    function import_shops_insert(Request $request)
    {


        DB::beginTransaction();
        try {
            $file = Excel::toArray([], $request->file('file'));
            $file = $file[0];
            $shopExistis = [];


            if($file[0][1] == trim("SHOP NAME") && $file[0][2] == trim("City") && $file[0][3] == trim("State") && $file[0][6] == trim("Route") && $file[0][7] == trim("Day")){
                foreach ($file as $key => $value) {


                    if($key == 0) continue ;
                    $tso_id = $request->tso_id;;
                    $distributor_id = Distributor::where('distributor_code',$value[9])->value('id');
                    $shop_data = Shop::where('company_name',ucfirst(trim($value[1])))->where('distributor_id',$distributor_id)->where('tso_id', $tso_id)->first();

                    $distributor_id = Distributor::where('distributor_code',$value[9])->value('id');
                    if(empty($shop_data)):
                        $route_data = Route::where('route_name',strtolower(trim($value[6])))->where('distributor_id',$distributor_id)->where('tso_id',$tso_id)->first();

                        if (empty($route_data)):

                            $route = new Route();
                            $route->tso_id = $request->tso_id;;
                            $route->distributor_id =  $distributor_id;
                            $route->route_name = ucfirst(trim($value[6]));
                            $route->day = trim(ucfirst(($value[7])));
                            $route->save();
                            $route_id = $route->id;

                            RouteDay::create(['route_id'=>$route_id,'day'=>trim(ucfirst(($value[7])))]);
                        else:
                            $route_id = $route_data->id;
                        endif;

                        $sub_route_data = SubRoutes::where('name',strtolower(trim($value[10])))->where('route_id',$route_id)->first();


                        if (empty($sub_route_data)):

                            $sub_route = new SubRoutes();
                            $sub_route->name = ucfirst(trim($value[10]));
                            $sub_route->route_id = $route_id;
                            $sub_route->save();
                            $sub_route_id = $sub_route->id;


                        else:
                            $sub_route_id = $sub_route_data->id;
                        endif;
                         $city_id =  City::where('name',$value[2])->value('id');
                        $shop = new Shop();
                        $shop->company_name = trim(ucfirst($value[1]));
                        $shop->shop_code = $shop->UniqueNo();
                        $shop->city =  $city_id;
                        $shop->state = ucfirst($value[3]);
                        $shop->distributor_id = $distributor_id;
                        $shop->tso_id = $tso_id;
                        $shop->route_id = $route_id;
                        $shop->contact_person = $value[4];
                        $shop->phone = $value[5];
                        $shop->class = $value[8];
                        $shop->sub_route_id = $sub_route_id;

                        $shop->save();


                    else:
                        array_push($shopExistis,$value[1]);


                    endif;


                }
                DB::commit();
                if($shopExistis){Session::flash('exists',$shopExistis);}

              return redirect()->back();
            }else{
                return redirect()->back()->with('catchError',"Format Not Match");
            }
        } catch (Exception $th) {
            //throw $th;
            DB::rollback();
            return response()->json(['catchError' => $th->getMessage()]);
        }
    }
}
