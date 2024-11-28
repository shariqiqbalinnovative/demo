
@php
    use App\Helpers\MasterFormsHelper;

    $master = new MasterFormsHelper;
@endphp


<style>
.table-bordered {
    border: 1px solid #000000 !important;
}

</style>

<div style=""  id="content" class="container print">
    <hr>
    <div class="model_content_custom">
        <div class="head_main">
            <h1 class="for-print">Sales Order</h1>
        </div>
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
                <table class="table table-bordered saleOrder_table">
                    <tr>
                        <th>Distributor:</th>
                        <td>{{  $so->distributor->distributor_name }}</td>
                    </tr>
                    <tr>
                        <th>Order Booker:</th>
                        <td>{{  $so->tso->name }}</td>
                    </tr>

                    <tr>
                        <th>Route:</th>
                        <td>{{  $so->shop->route->route_name }}</td>
                    </tr>

                    {{-- <tr>
                        <th>Sub Route:</th>
                        <td>{{  $so->shop->route->sub_route->name ?? '' }}</td>
                    </tr> --}}

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
        <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                <table class="table table table-bordered Order_Details">
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

                    <?php
                    $total_amount =0;
                    $sheme_product = [];
                    $sheme_qty = [];
                        ?>
                    @foreach($so->saleOrderData as $key => $row)
                        <?php $total_amount += $row->total; ?>
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $row->product->product_name ?? '' }}</td>
                            <td>{{ $row->product_flavour->flavour_name ?? '' }}</td>
                            <td>{{ number_format($row->qty) }} </td>
                            @php
                                // $sale_type = $row->sale_type == 1 ? $master->product_uom($row->product->id) ?? ''  : $row->sale_type == 2 ? $master->product_packing_uom($row->product->id) ?? '--' : 'Carton';
                                // $sale_type = $row->sale_type == 1 ? $row->product->uom->uom_name ?? ''  : $row->sale_type == 2 ? $row->product->packing_uom->uom_name ?? '--' : 'Carton';
                                // dump($row->product->id);
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
                    <tr class="bold" >
                        <td></td>
                        <td></td>
                        <td class="text-right">Total Amount</td>
                        <td style="background: #FAFAFA;" colspan="1">{{ number_format($total_amount,2) }}</td>
                        <td style="width: 98px; text-align: left !important;" class="text-right">Bulk Discount</td>
                        <td style="background: #FAFAFA;" colspan="1">{{ number_format($so->discount_amount,2) }} ({{$so->discount_percent}}%)</td>
                        <td style="width: 134px; text-align: left !important;" class="text-right">Total Net Amount</td>
                        <td style="background: #FAFAFA;" colspan="1">{{ number_format($total_amount - $so->discount_amount ,2) }}</td>
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
        @if (!empty($sheme_product))
            <div class="container" style="display: none;">
                <div class="row align-items-end">
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <br>
                        <div class="head_free">
                            <h4>Free Units Detail</h4>
                        </div>
                        <br>
                        <table class="table table table-bordered Order_Details">
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
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <div class="notes">
                            <h2>Note</h2>
                            <p>Lorem ipsum dolor sit amet.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>


