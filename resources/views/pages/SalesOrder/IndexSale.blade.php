<?php
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
?>


@extends('layouts.master')
@section('title', 'Sale Order')
@section('content')

<div class="card">

    <form id="list_data" method="get" action="{{ route('sale.index') }}">
        <div class="form-row">
            <div class="col-md-2 mb-2">
                <label  class="control-label" for="start-date">From</label>
                <input type="date" value="{{ date('Y-m-01') }}" class="form-control" name="from" placeholder="Start date"
                    required>
            </div>
            <div class="col-md-2 mb-2">
                <label  class="control-label" for="end-date">To</label>
                <input type="date" value="{{ date('Y-m-t') }}" class="form-control" name="to" placeholder="End date"
                    required>
            </div>

            <div class="col-md-2 mb-2">
                <div class="form-group">
                    <label class="control-label" >City</label>
                    <select required class="form-control" name="city"
                            id="" required>
                        <option value="">select</option>
                        @foreach ($master->cities() as $row)
                            <option value="{{ $row->id }}">{{ $row->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-2 mb-2">
                <div class="form-group">
                    <label class="control-label" >Distributor Name</label>
                    <select onchange="get_tso()" class="form-control" name="distributor_id" id="distribuotr_id">
                        <option value="">select</option>
                        @foreach ($master->get_all_distributor_user_wise() as $row)
                            <option value="{{ $row->id }}">{{ $row->distributor_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-2 mb-2">
                <div class="form-group">
                    <label class="control-label" >TSO Name</label>
                    <select class="form-control" id="tso_id" name="tso_id">
                        <option value="">select</option>
                    </select>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="generate text-left">
                    <button type="button" onclick="get_ajax_data()" class="btn btn-primary btn-xs">Generate</button>
                </div>
            </div>
        </div>

    </form>
</div>

    <div class="row" id="table-bordered">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Sale Order List</h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="form-control" id="CheckUnCheck"></th>
                                <th>Sr No</th>
                                <th>Inoivce No.</th>
                                <th>Inoivce Date.</th>
                                <th>Distributor</th>
                                <th>TSO</th>
                                <th>City</th>
                                <th>Shop</th>
                                <th>Execution</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="data">
                        </tbody>
                    </table>
                </div>
                <div class="create">
                @can('Create_Sales_Order')
                        <a href="{{ route('sale.create') }}" class="btn btn-success mr-1">Create Sale Order</a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <!-- Basic Floating Label Form section end -->

@endsection
@section('script')
    <script>
        $(document).ready(function() {
            get_ajax_data();
        });
    </script>
@endsection
