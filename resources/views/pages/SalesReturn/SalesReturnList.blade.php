@extends('layouts.master')
@section('title', 'Sales Return')
@section('content')

<form id="list_data" method="get" action="{{isset($excution) ? route('sales_return.sales_return_list', ['excution' => 'execution']) : route('sales_return.index') }}">
        <div class="row" id="table-bordered">
            <form method="post" action="{{ route('sales_return.sales_return_execution_submit') }}">
                @csrf
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Sales Return List</h4>
                            @if (isset($excution))
                            @can('Sale_Return_Execute')
                                    <button type="button" disabled id="bulk-execution-check-btn" data-url="{{ route('sales_return.sales_return_execution_submit') }}"  class="btn btn-primary mr-1 text-right right">Execution</button>
                            @endcan
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        @if (isset($excution))
                                            <th>
                                                {{-- <input type="checkbox" class="checkbox" class="check" /> --}}
                                                <input type="checkbox" class="bulk-execution-check-all" id="bulk-execution-check-all">
                                            </th>
                                        @endif
                                        <th>Sr No</th>
                                        <th>Sales Return No</th>
                                        <th>SO NO</th>
                                        <th>Distributor</th>
                                        <th>TSO</th>
                                        <th>Shop</th>
                                        <th>Execution</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>

                                <tbody id="data">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </form>
    <!-- Basic Floating Label Form section end -->


@endsection
@section('script')
    <script>
        // var $j = jQuery.noConflict();
        // $j(document).ready(function() {
        //     get_ajax_data();
        // });


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
                        'id': checked
                    },
                    success: function(response, textStatus, xhr) {
                        // console.log(data);
                        if (data.catchError) {
                            $(".print-error-msg").find("ul").html('');
                            $(".print-error-msg").css('display', 'block');
                            $(".print-error-msg").find("ul").append('<li>' + data.catchError + '</li>');
                            window.scrollTo(0, 0);
                            return;
                        }
                        if ($.isEmptyObject(data.error)) {

                            $(".alert-success").find("ul").html('<li>' + data.success + '</li>');
                            $("#subm").trigger("reset");
                            get_ajax_data();
                            $('#unique_code').val(data.code);

                        } else {
                            printErrorMsg(data.error);
                        }
                    }
                });
            });
        });
    </script>
@endsection
