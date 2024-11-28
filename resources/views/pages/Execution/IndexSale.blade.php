
<?php
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
?>
@extends('layouts.master')
@section('title', 'SND || Departments')
@section('content')

<div class="card">
    <form id="list_data" method="get" action="{{ route('sale-order.execution.index') }}">

        <div class="form-row">

            <div class="col-md-2 mb-2">
                <label class="control-label" for="start-date">From</label>
                <input type="date" value="{{ date('Y-m-01') }}" class="form-control" name="from" placeholder="Start date"
                    required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="control-label" for="end-date">To</label>
                <input type="date" value="{{ date('Y-m-t') }}" class="form-control" name="to" placeholder="End date"
                    required>
            </div>


            <div class="col-md-2 mb-2">
                <div class="form-group">
                    <label class="control-label">City</label>
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
                    <label class="control-label">Distributor Name</label>
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
                    <label class="control-label">TSO Name</label>
                    <select class="form-control" id="tso_id" name="tso_id">
                        <option value="">select</option>
                    </select>
                </div>
            </div>

            <input type="hidden" value="0" name="execution" />
            <div class="col-md-2 mb-3">
                <div class="gers">
                    <button type="button" onclick="get_ajax_data()" class="btn btn-primary btn-xs">Generate</button>
                </div>
            </div>
        </div>
    </form>

</div>

    <div class="row" id="table-bordered">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Sale Order Executioin List</h4>
                    @can('Sale_Order_Execute')
                        <button class="m-1 btn btn-primary" disabled id="bulk-execution-check-btn" data-url="{{ route('sale-order.execution.bulk') }}" type="button">Execute </button>
                    @endcan
                </div>

                <div class="table-responsive">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><input type="checkbox" class="bulk-execution-check-all" id="bulk-execution-check-all"> </th>
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
            </div>
        </div>
    </div>
    <!-- Basic Floating Label Form section end -->



@endsection
@section('script')
    <script>
        $(document).ready(function() {
            get_ajax_data();
            var checked = [];
            $(document).on('change', '.bulk-execution-check', function() {
                $('#bulk-execution-check-btn').prop('disabled', false);
                if (this.checked) {
                    // alert('checked');
                    checked.push($(this).val());
                    console.log(checked);
                } else {
                    // alert('no checked');
                    checked.pop();
                    console.log(checked);
                }

            });
            $(document).on('change', '.bulk-execution-check-all', function() {
                $('#bulk-execution-check-btn').prop('disabled', false);
                if (this.checked) {
                    $('.bulk-execution-check').prop('checked', true);
                    $(".bulk-execution-check:checked").each(function() {
                        checked.push($(this).val());
                    });
                } else {
                    checked = [];
                    $('.bulk-execution-check').prop('checked', false);
                }

            });
            $('#bulk-execution-check-btn').on('click', function() {
                $.ajax({
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: $(this).data('url'),
                    data: {
                        'checked_records': checked
                    },
                    success: function(response, textStatus, xhr) {
                        console.log(response.status);
                        if (response.status) {
                            let status = response.status;
                            alert(response.msg);
                            if (response.stock != '') {
                                alert(response.stock);
                            }
                            get_ajax_data();

                            // if (response.status != 'error') {
                            //     location.reload();
                            // }

                        }
                    }
                });
            });
        });
    </script>
@endsection
