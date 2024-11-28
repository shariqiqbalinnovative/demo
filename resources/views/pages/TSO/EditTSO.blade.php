<?php
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
$distributors =$master->get_users_distributors($tso->user_id)->toArray();
?>
@extends('layouts.master')
@section('title', "SND || Update Order Booker")
@section('content')


<section id="multiple-column-form">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Update Order Booker</h4>
                </div>
                <div class="card-body">
                    <form id="subm" method="POST" action="{{ route('tso.update', $tso->id) }}" class="form">
                        @csrf
                        @method('patch')
                        <div class="row">
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Order Booker Code <strong>*</strong></label>
                                    <input readonly type="text" value="{{ $tso->tso_code }}" class="form-control" placeholder="Product Code"/>
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Name <strong>*</strong></label>
                                    <input name="name" type="text" value="{{ $tso->name  ?? ''}}" class="form-control" placeholder="Order Booker Name"/>
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Company Name <strong>*</strong></label>
                                    <input name="company_name" type="text" value="{{ $tso->company_name  ?? ''}}"  class="form-control" placeholder="Company Name"/>
                                </div>
                            </div>

                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Employee ID <strong>*</strong></label>
                                    <input name="emp_id" type="text" value="{{ $tso->emp_id  ?? ''}}" class="form-control" placeholder="Employee ID"/>
                                </div>
                            </div>


                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Email <strong>*</strong></label>
                                    <input type="email" name="email" value="{{ $tso->user->email  ?? ''}}" class="form-control" placeholder="info@email.com">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Phone <strong>*</strong></label>
                                    <input type="text" name="phone" value="{{ $tso->phone  ?? ''}}" class="form-control" placeholder="03331231231">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Cell Phone</label>
                                    <input type="text" name="cell_phone" value="{{ $tso->cell_phone  ?? ''}}" class="form-control" placeholder="03331231231">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Alt. Phone</label>
                                    <input type="text" name="alt_phone" value="{{ $tso->alt_phone  ?? ''}}" class="form-control" placeholder="03331231231">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>CNIC</label>
                                    <input type="text" name="cnic" value="{{ $tso->cnic  ?? ''}}" class="form-control" placeholder="CNIC No ">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Address</label>
                                    <input type="text" name="address" value="{{ $tso->address ?? ''}}" class="form-control" placeholder="abc street, abc, abc">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>City<strong>*</strong></label>
                                    <select name="city" class="form-control">
                                        <option value="">Select Option</option>
                                        @foreach($cities as $city)
                                        <option @if($tso->city== $city->id) selected @endif value="{{$city->id}}">{{$city->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>State</label>
                                    <input type="text" name="state" value="{{ $tso->state ?? ''}}" class="form-control" placeholder="Sindh">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Country</label>
                                    <input type="text" name="country" value="{{ $tso->country  ?? ''}}" class="form-control" placeholder="Pakistan">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <input type="text" name="notes" value="{{ $tso->notes  ?? ''}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Photo</label>
                                    <input type="file" name="image_path" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Login</label>
                                    <input type="text" name="user_name" value="{{$tso->user->email}}" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="password" name="password" value="" class="form-control">
                                </div>
                            </div>



                            <div class="col-md-3 col-12 mb-1 form-check">
                                {{-- <strong>Distributor</strong>
                                 @foreach ($master->get_all_distributors() as $key => $row)

                                <div class="form-check">
                                    <input @if(in_array($row->id,$distributors)) checked @endif class="form-check-input" value="{{ $row->id }}" type="checkbox" id="distributor{{ $row->id }}" name="distributor[]">
                                    <label class="form-check-label" for="distributor{{ $row->id }}">{{ $row->distributor_name }}</label>
                                </div>
                                @endforeach --}}

                                <div class="form-group">
                                    <label>Distributor</label>

                                    <select name="distributor[]" class="form-control" id="distributor" multiple>
                                        @foreach ($master->get_all_distributors() as $key => $row)
                                            <option value="{{ $row->id }}" {{in_array($row->id,$distributors) ? 'selected' : ''}}> {{ $row->distributor_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>User Role</label>
                                    <select name="role" class="select2 form-control form-control-lg">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Manager</label>
                                    <select name="manager" class="select2 form-control form-control-lg">
                                        @foreach ($users as $user)
                                            <option {{ $user->id == $tso->manager ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>KPO</label>
                                    <select name="kpo" class="select2 form-control form-control-lg">
                                        @foreach ($users as $user)
                                            <option {{ $user->id == $tso->kpo ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>KPO # 2</label>
                                    <select name="kpo_2" class="select2 form-control form-control-lg">
                                        @foreach ($users as $user)
                                            <option {{ $user->id == $tso->kpo_2 ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>KPO # 3</label>
                                    <select name="kpo_3" class="select2 form-control form-control-lg">
                                        @foreach ($users as $user)
                                            <option {{ $user->id == $tso->kpo_3 ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Department</label>
                                    <select name="department_id" class="select2 form-control form-control-lg">
                                        @foreach ($departments as $department)
                                            <option {{ $department->id == $tso->department_id ? 'selected' : '' }} value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Designation</label>
                                    <select name="designation_id" class="select2 form-control form-control-lg">
                                        @foreach ($designations as $designation)
                                            <option {{ $designation->id == $tso->designation_id ? 'selected' : '' }} value="{{ $designation->id }}">{{ $designation->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Spot Sale</label>
                                    <select name="spot_sale" class="select2 form-control form-control-lg">
                                        <option {{ $tso->spot_sale == 1 ? 'selected' : '' }} value="1">Yes</option>
                                        <option {{ $tso->spot_sale == 0 ? 'selected' : '' }} value="0">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Auto Payment</label>
                                    <select name="auto_payment" class="select2 form-control form-control-lg">
                                        <option {{ $tso->auto_payment == 1 ? 'selected' : '' }} value="1">Yes</option>
                                        <option {{ $tso->auto_payment == 0 ? 'selected' : '' }} value="0">No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Geography</label>
                                    <select name="geography_id" class="select2 form-control form-control-lg">
                                        <option value="1">Karachi</option>
                                        <option value="2">Islamabad</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Date Of Join</label>
                                    <input type="date" class="form-control" value="{{$tso->date_of_join}}" name="date_of_join">
                                </div>
                            </div>
                            <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Date of Leaving</label>
                                    <input type="date" class="form-control" value="{{$tso->date_of_leaving}}" name="date_of_leaving">
                                </div>
                            </div>

                            {{-- <div class="col-md-3 col-12 mb-1">
                                <div class="form-group">
                                    <label>Distributor</label>
                                    <select name="" class="select2 form-control form-control-lg">
                                        @foreach ($master->get_all_distributor_user_wise() as $distributor)
                                            <option {{ $distributor->id == $tso->distributor_id ? 'selected' : '' }} value="{{ $distributor->id }}">{{ $distributor->distributor_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div> --}}
                        </div>
                        <div class="row">
                            <div class="col-md-3 col-12">
                                <div class="form-group">
                                    <label class="control-label" for="shop_location">Location.</label>
                                    <input type="checkbox"  name="shop_location" id="shop_location" onclick="shopLocation()" value="1" {{$tso->location_name != null && $tso->latitude != null && $tso->longitude != null && $tso->radius != null ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                        <div class="row get_location" style="display: {{$tso->location_name != null && $tso->latitude != null && $tso->longitude != null && $tso->radius != null ? '' : 'none' }};">
                            <div class="col-md-12 map-tb">
                                <input type="text" name="map"  value="{{$tso->map}}" class="form-control controls" placeholder="Map name" id="pac-input"/>

                                <div id="map"></div>
                            </div>
                            <div class="col-12">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Location Title</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                            <th>Radius (KM)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input type="text" class="form-control"
                                                    placeholder="Location Title" id="location_name" name="location_name" value="{{$tso->location_name}}"/></td>
                                            <td><input type="number" step="any" class="form-control"
                                                    placeholder="Latitude" readonly name="latitude" id="lat" value="{{$tso->latitude}}"/></td>
                                            <td><input type="number" step="any" class="form-control"
                                                    placeholder="Longitude" readonly name="longitude" id="lon" value="{{$tso->longitude}}"/></td>
                                            <td><input type="number" step="any" class="form-control"
                                                    placeholder="Radius (KM)" id="radius" name="radius" value="{{$tso->radius}}"/></td>
                                        </tr>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 seprator">
                                <hr>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary mr-1">Update Order Booker</button>
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            </div>


                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
                <!-- Basic Floating Label Form section end -->

@endsection

@section('script')
<script>

    var latitude = parseFloat({{ isset($tso->latitude)?$tso->latitude:24.8607343}}); // Example latitude
    var longitude = parseFloat({{ isset($tso->longitude)?$tso->longitude:67.0011364}}); // Example longitude


    $(document).ready(function() {
           $('#distributor').select2();
       });

       function shopLocation(){
            console.log($('#shop_location').prop('checked'));
            var checked = $('#shop_location').prop('checked');
            if (checked) {
                $('.get_location').show();
                $('#location_name').attr("required", true);
                $('#lat').attr("required", true);
                $('#lon').attr("required", true);
                $('#radius').attr("required", true);
            }
            else{
                $('.get_location').hide();
                $('#location_name').attr("required", false);
                $('#lat').attr("required", false);
                $('#lon').attr("required", false);
                $('#radius').attr("required", false);
            }
        }
</script>

@endsection
