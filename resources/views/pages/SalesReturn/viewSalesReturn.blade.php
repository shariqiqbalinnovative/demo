

<div style=""  id="content" class="container print">
    <hr>
    <div class="model_content_custom">
        <div class="head_main">
            <h1 class="for-print">View Sales Return</h1>
        </div>
        <div class="logo_snd">
            <a class="navbar-brand" href="{{ url('dashboard') }}">
                <span class="brand-logo">
                    <img src="{{ url('/public/assets/images/logo2.png') }}">
                </span>
            </a>
        </div>
        <br>
        <div class="row align-items-center">

            <div class="col-lg-7 col-md-7 col-sm-7 col-xs-7 well">
                <table class="table table table-bordered saleOrder_table">
                    <tr>
                        <th>
                            <div class="head_free">
                                <h4>Sales Return</h4>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Sale Return:</th>
                        <td>{{  $so->voucher_no }}</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Sale Return Date:</th>
                        <td>{{ date("d-m-Y", strtotime($so->created_at))  }}</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Sale Order No:</th>
                        <td>{{  $so->SalesOrder->invoice_no }}</td>
                    </tr>
                    <tr>
                        <th style="width: 30%;">Sale Order Date:</th>
                        <td>{{ date("d-m-Y", strtotime($so->SalesOrder->dc_date))  }}</td>
                    </tr>

                </table>
            </div>
            <div class="col-lg-5 col-md-5 col-sm-5 col-xs-5 well">
                <table class="table table-bordered saleOrder_table">
                    <tr>
                        <th>Distributor:</th>
                        <td>{{  $so->SalesOrder->distributor->distributor_name }}</td>
                    </tr>
                    <tr>
                        <th>TSO:</th>
                        <td>{{  $so->SalesOrder->tso->name }}</td>
                    </tr>

                    <tr>
                        <th>Route:</th>
                        <td>{{  $so->SalesOrder->shop->route->route_name }}</td>
                    </tr>

                    <tr>
                        <th>Sub Route:</th>
                        <td>{{  $so->SalesOrder->shop->route->sub_route->name ?? '' }}</td>
                    </tr>

                    <tr>
                        <th>Shop:</th>
                        <td>{{  $so->SalesOrder->shop->company_name }}</td>
                    </tr>
                    <tr>
                        <th>Invoice Type:</th>
                        <td>Cash</td>
                    </tr>
                </table>
            </div>

        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <table class="table table table-bordered Order_Details">
                    <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Product</th>
                        <th>Flavour</th>
                        <th>Sale Type</th>
                        <th>QTY</th>
                        <th>Return QTY</th>
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

                    <?php
                    $total_amount =0;
                    $sheme_product = [];
                    $sheme_qty = [];

                        ?>
                    @foreach($so->SalesReturnData as $key => $row)
                        <?php $total_amount += $row->SalesOrderData->total;
                        ?>
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $row->SalesOrderData->product->product_name ?? '' }}</td>
                            <td>{{ $row->SalesOrderData->product_flavour->flavour_name }}</td>
                            <td>{{ $row->SalesOrderData->SaleTypeName }}</td>
                            <td>{{ $row->SalesOrderData->qty }}</td>
                            <td>{{ $row->qty }}</td>
                            <td>{{ $row->SalesOrderData->rate }}</td>
                            {{-- <td>{{ number_format($row->SalesOrderData->discount_amount,2) }}</td>
                            <td></td>
                            <td>{{ number_format($row->SalesOrderData->tax_amount,2) }}</td> --}}

                            <td>{{ number_format($row->SalesOrderData->discount,2) }}</td>
                            <td>{{ number_format($row->SalesOrderData->discount_amount,2) }}</td>
                            <td class="hide">{{ number_format($row->SalesOrderData->tax_amount,2) }}</td>
                            <td>{{ number_format($row->SalesOrderData->trade_offer_amount,2) }}</td>
                            <td>{{ $row->SalesOrderData->scheme->scheme_name ?? '--' }}</td>
                            <td>{{ number_format($row->SalesOrderData->scheme_amount,2) }}</td>
                            <td>{{ number_format($row->SalesOrderData->total,2) }}</td>
                        </tr>
                    @php  $sheme_product[] = ($row->SalesOrderData->sheme_product_id); @endphp
                    @endforeach
                    <tr class="bold" >
                        <td style="border-bottom:none !important;"></td>
                        <td style="border-bottom:none !important;"></td>
                        <td style="border-bottom:none !important;" class="text-right">Total Amount</td>
                        <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($total_amount,2) }}</td>
                        {{-- <td style="border-bottom:none !important;" class="text-right">Tax Amount</td>
                        <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($so->discount_amount,2) }}</td> --}}
                        <td style="border-bottom:none !important; width: 98px; text-align: left !important;" class="text-right">Bulk Discount</td>
                        <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($so->SalesOrder->discount_amount,2) }} ({{$so->SalesOrder->discount_percent}}%)</td>

                        <td style="border-bottom:none !important;" class="text-right">Total Net Amount</td>
                        <td style="border: 1px solid #B6B6B6 !important; background: #FAFAFA;" colspan="1">{{ number_format($so->SalesOrder->total_amount - $so->discount_amount ,2) }}</td>
                    </tr>
                    @if($so->discount_amount >0)
                        <tr  class="bold hide" style="display: none">

                        </tr>

                        <tr class="bold hide" style="display: none">

                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


