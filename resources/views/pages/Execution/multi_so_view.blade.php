@php
    use App\Helpers\MasterFormsHelper;
    $master = new MasterFormsHelper;
@endphp
@extends('layouts.master')
@section('title', 'SND || Create New Sale Order')
<style>

</style>
@section('content')
    <div class="row mb">
        <div class="col-md-12">
            <div class="right" style="float: right">
                <button  id="print" type="button"  class="btn btn-success btn-sm  right">Print</button>
            </div>
        </div>
    </div>


    <div style="" id="content" class="container print">
        @foreach($sos as $key => $so)
        <div class="card ptb" style="page-break-before: always">
            <div class="logo_snd">
                <h1 class="subHeadingLabelClass">{{$so->distributor->distributor_name}}</h1>
                <h4 class="subHeadingLabelClass">{{$so->distributor->address ?? '--'}}</h4>
                {{-- <a class="navbar-brand" href="{{ url('dashboard') }}">
                    <span class="brand-logo">
                        <img src="{{ url('/public/assets/images/logo2.png') }}">
                    </span>
                </a> --}}
            </div>
            <br>
            <div class="row align-items-center">
                <div class="col-lg-7 col-md-7 col-sm-7 col-xs-7 well">
                    <table class="table table table-bordered saleOrder_table">
                        <tr>
                            <th>
                                <div class="head_free">
                                    <h4>Sales Order</h4>
                                </div>
                            </th>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Sale Order:</th>
                            <td>{{  $so->invoice_no }}</td>
                        </tr>
                        <tr>
                            <th style="width: 30%;">Sale Order Date:</th>
                            <td>{{ date("d-m-Y", strtotime($so->dc_date))  }}</td>
                        </tr>

                    </table>
                </div>
                <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5 well">
                    <table class="table table table-bordered saleOrder_table">
                        <tr>
                            <th>Distributor:</th>
                            <td>{{  $so->distributor->distributor_name }}</td>
                        </tr>
                        <tr>
                            <th>TSO:</th>
                            <td>{{  $so->tso->name }}</td>
                        </tr>

                        <tr>
                            <th>Route:</th>
                            <td>{{  $so->shop->route->route_name }}</td>
                        </tr>

                        <tr>
                            <th>Sub Route:</th>
                            <td>{{  $so->shop->route->sub_route->name ?? '' }}</td>
                        </tr>

                        <tr>
                            <th>Shop:</th>
                            <td>{{  $so->shop->company_name }}</td>
                        </tr>
                        <tr>
                            <th>Invoice Type:</th>
                            <td>Cash</td>
                        </tr>
                    </table>
                </div>
            </div>
            {{-- <h1 class="for-print">Sales Order</h1>
            <hr>

            <div class="row">

                <div class="col-md-6 well">

                    <table class="table table table-bordered saleOrder_table">
                        <tr>
                            <th>Order Number:</th>
                            <td>{{  $so->invoice_no }}</td>
                        </tr>
                        <tr>
                            <th>Order Date:</th>
                            <td>{{ date("d-m-Y", strtotime($so->dc_date))  }}</td>
                        </tr>

                    </table>
                </div>
                <div class="col-md-6 well">
                    <table class="table table table-bordered saleOrder_table">
                        <tr>
                            <th>Distributor:</th>
                            <td>{{  $so->distributor['distributor_name'] }}</td>
                        </tr>
                        <tr>
                            <th>TSO:</th>
                            <td>{{  $so->tso['name']}}</td>
                        </tr>


                        <tr>
                            <th>Route:</th>
                            <td>{{  $so->shop->route->route_name  }}</td>
                        </tr>

                        <tr>
                            <th>Sub Route:</th>
                            <td>{{  $so->shop->route->sub_route->name ?? '' }}</td>
                        </tr>
                        <tr>
                            <th>Shop:</th>
                            <td>{{  $so->shop['company_name'] }}</td>
                        </tr>

                    </table>
                </div>
            </div> --}}
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <h4>Order Details</h4>
                    <table class="table table table-bordered saleOrder_table">
                        <thead>
                        <tr>
                            <th>Sr No</th>
                            <th>Product</th>
                            <th>Flavour</th>
                            <th>QTY</th>
                            <th>Sale Type</th>
                            <th>Rate</th>
                            <th>Disc %</th>
                            <th>Disc Amount</th>
                            <th>Trade Offer</th>
                            <th>Scheme Product</th>
                            <th>Scheme Amount</th>
                            <th>Net Amount</th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php $total_amount =0;
                         $sheme_product = [];
                        ?>
                        @foreach($so['saleOrderData'] as $key => $row)
                                <?php $total_amount += $row->total;?>
                            <tr>
                                <td>{{ ++$key }}</td>
                                <td>{{ $row->product->product_name ?? '--' }}</td>
                                <td>{{ $row->product_flavour->flavour_name ?? '' }}</td>
                                <td>{{ $row->qty }}</td>
                                @php
                                    $sale_type = $master->uom_name($row->sale_type)
                                @endphp
                                <td>{{$sale_type}}</td>
                                <td>{{ $row->rate }}</td>
                                <td>{{ number_format($row->discount,2) }}</td>
                                <td>{{ number_format($row->discount_amount,2) }}</td>
                                <td class="hide">{{ number_format($row->tax_amount,2) }}</td>
                                <td>{{ number_format($row->trade_offer_amount,2) }}</td>
                                <td>{{ $row->scheme->scheme_name ?? '--' }}</td>
                                <td>{{ number_format($row->scheme_amount,2) }}</td>
                                <td>{{ number_format($row->total,2) }}</td>
                            </tr>
                                @php  $sheme_product[] = ($row->sheme_product_id); @endphp
                        @endforeach
                        {{-- <tr class="bold" style="">
                            <td class="text-center" colspan="6">Total</td>
                            <td colspan="1">{{ number_format($total_amount,2) }}</td>
                        </tr> --}}
                        <tr class="bold" >
                            <td style="border-bottom:none !important;"></td>
                            <td style="border-bottom:none !important;"></td>
                            <td style="border-bottom:none !important;" class="text-right">Total Amount</td>
                            <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($total_amount,2) }}</td>
                            <td style="border-bottom:none !important; width: 98px; text-align: left !important;" class="text-right">Bulk Discount</td>
                            <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($so->discount_amount,2) }} ({{$so->discount_percent}}%)</td>
                            <td style="border-bottom:none !important;width: 134px; text-align: left !important;" class="text-right">Total Net Amount</td>
                            <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($total_amount - $so->discount_amount ,2) }}</td>
                        </tr>
                        @if($so->discount_amount >0)
                            <tr class="bold hide" style="">
                                {{-- <td class="text-center" colspan="6">Bulk Discount</td>
                                <td colspan="1">{{ number_format($so->discount_amount,2) }}</td> --}}
                            </tr>

                            <tr class="bold hide" style="">
                                {{-- <td class="text-center" colspan="6">Net Total</td>
                                <td colspan="1">{{ number_format($total_amount - $so->discount_amount ,2) }}</td> --}}
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <br>
            @if (!empty($sheme_product))
                <div class="container" style="display: none;">
                    <div class="row">
                        <div class="col-sm-6">
                            <h4>Free Units Detail</h4>
                            <table class="table table table-bordered saleOrder_table">
                                <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Pieces</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($so->saleOrderData as $key => $row)
                                    @if($row->sheme_product_id!=0 && $row->offer_qty>0)
                                        <tr>
                                            <td>{{ $row->SchmeProduct->product_name }}</td>
                                            <td>{{ $row->offer_qty }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <!-- Add more rows as needed -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        @endforeach

    </div>

@endsection

