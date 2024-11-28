<?php
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
?>

@extends('layouts.master')
@section('title', 'SND || Create New Sale Order')
@section('content')


    <section id="multiple-column-form">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Create Sales Order</h4>
                    </div>
                    <div class="card-body">
                        <form id="subm" method="POST" action="{{ route('sale.store') }}" class="form">
                            @csrf

                            <div class="col-md-12">
                                <section class="panel">
                                    <div class="sale_order_detsils">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="main_head">
                                                    <h2>Sales Order </br>Details</h2>
                                                </div>
                                            </div>
                                            <div class="col-md-10">
                                                <div class="panel-body">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <label class="control-label"> Invoice # </label>
                                                            <input readonly name="invoice_no" class=" form-control" tabindex="1" type="text"  id="onrecord" required="required" value="{{ strtoupper($invoice_no) }}">
                                                            <span id="ord"></span>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="control-label">  Order Date </label>
                                                            <input name="dc_date" class=" form-control" tabindex="10" type="date" id="dcdate">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label class="control-label">Distributor</label>
                                                                <select name="distributor_id" class="form-control select2" onchange="get_tso()" id="distribuotr_id"  tabindex="-1" aria-hidden="true">
                                                                    <option value="" selected="">select</option>
                                                                    @foreach ($master->get_all_distributor_user_wise() as $distributor)
                                                                        <option value="{{ $distributor->id }}">{{ $distributor->distributor_name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>


                                                        <div class="col-md-3 hide">
                                                            <label class=" control-label">  DC No. </label>
                                                            <input name="dc_no" class=" form-control" tabindex="10" type="number" id="number">
                                                            <span id="serial"></span>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <div class="tso2">
                                                                <label class="control-label">TSO</label>
                                                                <select class="form-control select2" name="tso_id" id="tso_id">
                                                                    <option value="">Select a TSO: </option>
                                                                    {{-- @foreach ($tsos as $tso)
                                                                        <option value="{{ $tso->id }}">{{ $tso->name }}</option>
                                                                    @endforeach --}}
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3 hide">
                                                            <div style=" ">
                                                                <label class="control-label"> LPO # </label>
                                                                <input name="lpo_no" class=" form-control" tabindex="10" type="number" id="lpo">
                                                            </div>
                                                        </div>


                                                        <div class="col-md-3">
                                                            <div class="cust2">
                                                                <label class="control-label">Shop</label>
                                                                <select class="form-control shop_id select2" id="shop_id" name="shop_id"  required="">
                                                                    <option value="">Select a Shop </option>
                                                                    {{-- @foreach ($shops as $shop)
                                                                        <option value="{{ $shop->id }}">{{ $shop->company_name }}</option>
                                                                    @endforeach --}}
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-3 hide">
                                                            <label class="control-label"> Total Amount </label>
                                                            <input id="total_amount"  class=" form-control" step="any" name="total_amount" type="number" tabindex="-1">
                                                        </div>

                                                        <!-- <div class="col-md-3">
                                                            <div id="subcustomer"></div>
                                                                <br>
                                                                {{-- Search Product<br>
                                                                <span id="dis">
                                                                    <select name="product" tabindex="-1" id="searchbox"
                                                                        accesskey="s" class="combobox form-control" aria-hidden="true">
                                                                        <option value="">Select a Product</option>
                                                                        @foreach ($products as $product)
                                                                            <option data-url="{{ route('sale-order.table-row', $product->id) }}" value="{{ $product->id }}">{{ $product->product_name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </span> --}}
                                                            </div>
                                                        </div> -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                </section>
                            </div>

                            {{-- <div class="col-md-12">
                                <div class="add_more text-right">
                                    <button type="button" class="btn btn-primary" id="add-more" onclick="addMore()">+ Add Row</button>
                                </div>
                            </div> --}}

                            <div class="item_details">
                                <div class="row">
                                    <div class="col-md-2">
                                        <div class="main_head">
                                            <h2>Item Details</h2>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <table width="100%" cellpadding="5" class=" table table-bordered table-striped" id="acart">
                                            <thead>
                                                <tr>
                                                    <th width="20%">
                                                        <div>Product Name</div>
                                                    </th>
                                                    <th>
                                                        <div>Flavour</div>
                                                    </th>
                                                    <th>
                                                        <div>Sale Type</div>
                                                    </th>
                                                    <th>
                                                        <div>QTY</div>
                                                    </th>
                                                    <th>
                                                        <div>Rate</div>
                                                    </th>
                                                    <th>
                                                        <div>Disc</div>
                                                    </th>
                                                    <th>
                                                        <div>Disc Amount</div>
                                                    </th>
                                                    <th>
                                                        <div>Trade Offer</div>
                                                    </th>

                                                    <th class="hide">
                                                        <div>Tax (%)</div>
                                                    </th>
                                                    <th>
                                                        <div>Scheme Product </div>
                                                    </th>
                                                    <th>
                                                        <div>Scheme Amount </div>
                                                    </th>
                                                    <th>
                                                        <div>Total </div>
                                                    </th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="appendRow">
                                                <tr>

                                                    <td>
                                                        <select  name="product_id[]" tabindex="-1" onchange="get_product_price(this); get_flavour(this); get_scheme_product(this); get_total_caton_and_qty();" required class="product_id combobox form-control" aria-hidden="true">
                                                            <option value="">Select a Product</option>
                                                            @foreach ($products as $product)
                                                                <option data-carton_size="{{$product->carton_size}}" data-product_price="{{$master->get_product_price($product->id)}}" data-flavour="{{$product->product_flavour}}" data-url="{{ route('sale-order.table-row', $product->id) }}" value="{{ $product->id }}">{{ $product->product_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </td>

                                                    <td>
                                                        <select name="flavour_id[]"  id="" class="form-control flavour">
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="sale_type[]" onchange="get_rate(this)" id="" class="form-control sale_type">
                                                            {{-- <option value="1">Per Piece</option>
                                                            <option value="2">Per Packet</option>
                                                            <option value="3">Per Carton</option> --}}
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input onkeyup="calc(this); get_scheme_product(this); get_total_caton_and_qty();" class="form-control data-quantity" type="number" min="0" name="qty[]" value="0">
                                                    </td>
                                                    <td>
                                                        <input onkeyup="calc(this)" step="any" class="form-control data-rate" type="number" min="0" name="rate[]" value="0">
                                                    </td>
                                                    <td>
                                                        <input onkeyup="calc(this)"  class="form-control data-discount" type="integer" min="0" name="data_discount[]" value="0">
                                                        {{-- <input class="data-discount-amount" type="hidden" name="data_discount_amount[]" value="0"> --}}
                                                    </td>
                                                    <td>
                                                        <input class="form-control data-discount-amount" onkeyup="calc(this , true)"   type="integer" name="data_discount_amount[]" value="0">
                                                    </td>
                                                    <td>
                                                        <input onkeyup="calc(this)" class="form-control trade_offer_amount" type="number" name="trade_offer_amount[]" value="0">
                                                    </td>
                                                    <td>
                                                        <select name="scheme_id[]" onchange="get_scheme_amount(this);" id="" class="form-control scheme_product">
                                                            <option value="">Select</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input readonly class="form-control scheme_amount" step="any" type="number" name="scheme_amount[]" value="0">
                                                    </td>
                                                    <td class="hide">
                                                        <input onkeyup="calc(this)"  class="form-control data-tax-percent" type="number" name="data_tax_percent[]" value="0">
                                                        <input class="data-tax-amount" type="hidden" name="data_tax_amount[]" value="0">
                                                    </td>
                                                    <td>
                                                        <input readonly class="form-control data-total" type="number"  name="data_total[]" value="0">
                                                    </td>
                                                    <td>
                                                        {{-- <button type="button" onclick="removeRow(1)" class="btn btn-danger btn-xs mt-2"><i class="fa-solid fa-trash"></i></button> --}}
                                                        <button type="button" class="btn btn-primary" id="add-more" onclick="addMore()">+ Add Row</button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <!-- Sales OrderDetails -->
                            <div class="Sales_Order_Details">
                                <section class="panel">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-2">
                                                <div class="main_head">
                                                    <h2>Sales Order <br> Details</h2>
                                                </div>
                                            </div>
                                            <div class="col-md-10">
                                                <div class="row">

                                                    <div class="col-md-3">
                                                        <label class="control-label">  Products Subtotal  </label>
                                                        <input step="any" id="products_subtotal" name="products_subtotal" type="number" class="total-box form-control" id="products_subtotal">
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="control-label">Bulk Discount</label>
                                                        <input id="discount_percent" name="discount_percent" type="text" class="form-control" tabindex="15" accesskey="d"  value="0">
                                                        <input id="discount_amount" name="discount_amount" type="hidden"  class="form-control" value="0">
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="control-label"> Execution </label>
                                                        <select name="excecution" class="form-control">
                                                            <option value="1">Yes</option>
                                                            <option value="0" selected="">No</option>
                                                        </select>
                                                    </div>


                                                    <div class="col-md-3">
                                                        <label class="control-label"> Execution Date  </label>
                                                        <input name="excecution_date" tabindex="10" type="date" id="date" class="form-control" autocomplete="new-password">
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="control-label">  Total Carton  </label>
                                                        <input type="number" name="total_carton" id="total_carton"  value="0" class="form-control">
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="control-label">  Pending Amount  </label>
                                                        <input id="pending_amount" name="pending_amount" class="form-control" type="number" min="0" value="0" id="pending_amount" readonly="">
                                                    </div>

                                                    <div class="col-md-3 hide">
                                                        <div class="invoicetax">
                                                            <label class="control-label"> Tax Applied </label>
                                                            <input id="tax_applied" name="tax_applied" type="text" id="tax" value="0" class="form-control" readonly="">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="control-label"> Payment  </label>
                                                        <select name="payment_type" class="form-control">
                                                            <option value="2" selected="">Credit</option>
                                                            <option value="1">Cash</option>
                                                        </select>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <label class="control-label">  Total Qty  </label>
                                                        <input name="total_pcs" type="number" id="total_pcs" value="0" class="form-control">
                                                    </div>


                                                    <div class="col-md-3 hide">
                                                        <div id="oldr">

                                                            <label class="control-label"> Old Receivable   </label>
                                                            <input id="old_receivable" name="old_receivable"  type="text" class="form-control" id="old_receivable" readonly="">
                                                        </div>
                                                    </div>


                                                    <div class="col-md-3 hide">
                                                        <label class="control-label"> Freight Charges </label>
                                                        <input id="freight_charges" name="freight_charges" accesskey="f" type="number" min="0"id="shipping" value="0" tabindex="15" class="form-control">
                                                    </div>


                                                    <div class="col-md-3 hide">
                                                        <label class="control-label">  Cost Center  </label>
                                                        <select name="cost_center" id="combobox" class="teacher form-control">
                                                            <option value="1">Embroidery</option>
                                                            <option value="4">Knitting</option>
                                                            <option value="12">Factory Expenses</option>
                                                        </select>
                                                    </div>


                                                    <div class="col-md-6">
                                                        <label class="control-label"> Notes </label>
                                                        <textarea name="notes" class="form-control"></textarea>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <label class="control-label"> Transport Details </label>
                                                        <textarea name="transport_details" class="form-control"></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="row">
                                <div class="col-md-4"></div>
                                <div class="col-md-4"></div>
                                <div class="col-md-4">
                                    <div class="total_button">

                                        <label class="control-label">Total</label>
                                        <input id="net_total" name="total" type="number" class="change form-control" id="total" readonly="readonly" required="">
                                    </div>

                                    <div class="button_create text-right">
                                        <input name="submit2" type="submit" id="submit2" accesskey="o" tabindex="15" class="btn btn-warning" value="Submit">
                                        <input name="Clear" type="reset" id="Reset" class="btn btn-info">
                                    </div>
                                </div>

                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
@section('script')
    <script>
        function calculation() {
            var $total = 0;
            $( '.data-total' ).each(function( index ) {
                // console.log(this);
                $total += parseFloat(this.value)
            });
            $('#products_subtotal').val($total);
            var freight_charges = $('#freight_charges').val();
            var discount_percent = $('#discount_percent').val();
            var discount_amount = ( parseFloat($total) / 100 ) * parseFloat(discount_percent)
            var $net_total = $total + parseFloat(freight_charges) - parseFloat(discount_amount);
            $('#total_amount').val($net_total);
            $('#discount_amount').val(discount_amount);
            $('#pending_amount').val($net_total);
            $('#net_total').val($net_total);
            console.log(discount_amount);
        }
        $(document).ready(function(){

            $('.select2').select2();
            $('#searchbox').on('change',  function(){

                // let rate = $(this).find(':selected').data('rate');
                // $(this).closest('tr').find('.data-rate').val(rate);
            //     console.log($(this).find(':selected').data('url'))
            //     var product_id = $(this).val()
            //     $.ajax({
            //     type: "get",
            //     url: $(this).find(':selected').data('url'),
            //     data: {}, // serializes the form's elements.
            //     cache: false,
            //     success: function(data)
            //     {
            //         $('#appendRow').append(data);

            //     }
            // });
            });

            $(document).on('keyup', '#freight_charges', function(){
                calculation();
            });
            $(document).on('keyup', '#discount_percent', function(){
                calculation();
            });

            $(document).on('change', '#tso_id', function(){
                var options = '<option value="">Select a Shop </option>';
                var id = $(this).val()
                $.ajax({
                    type: "get",
                    url: "{{ route('shop.by.tso') }}",
                    data: {id:id},
                    cache: false,
                    success: function(data)
                    {
                        data.res.forEach(element => {
                            options += `<option value="${element.id}">${element.company_name}</option>`
                        });
                        $('#shop_id').html(options);

                    }
                });
            });
        });
        let counter = 1;
        function addMore() {

            // <td>
            //         ${counter+1}
            //     </td>
            $('#appendRow').append(`
            <tr id="removeRow${counter}">

                <td>
                    <select onchange="get_product_price(this);get_flavour(this); get_scheme_product(this); get_total_caton_and_qty();" name="product_id[]" tabindex="-1" class="product_id combobox form-control"
                        required class="combobox form-control" aria-hidden="true">
                        <option value="">Select a Product</option>
                        @foreach ($products as $product)
                            <option data-carton_size="{{$product->carton_size}}" data-product_price="{{$master->get_product_price($product->id)}}" data-flavour="{{$product->product_flavour}}"data-url="{{ route('sale-order.table-row', $product->id) }}" value="{{ $product->id }}">{{ $product->product_name }}</option>
                        @endforeach
                    </select>
                </td>

                <td>
                    <select name="flavour_id[]"  id="" class="form-control flavour">
                    </select>
                </td>
                <td>
                    <select name="sale_type[]" onchange="get_rate(this)" id="" class="form-control sale_type">

                    </select>
                </td>
                <td>
                    <input onkeyup="calc(this); get_scheme_product(this); get_total_caton_and_qty();" class="form-control data-quantity" type="number" min="0" name="qty[]" value="0">
                </td>
                <td>
                    <input step="any" onkeyup="calc(this)" class="form-control data-rate" type="number" min="0" name="rate[]" value="0">
                </td>
                <td>
                    <input onkeyup="calc(this)" class="form-control data-discount" type="text" name="data_discount[]" value="0">
                </td>
                <td>
                    <input class="form-control data-discount-amount" onkeyup="calc(this , true)"   type="integer" name="data_discount_amount[]" value="0">
                </td>
                <td>
                    <input onkeyup="calc(this)" class="form-control trade_offer_amount" type="text" name="trade_offer_amount[]" value="0">
                </td>
                <td>
                    <select name="scheme_id[]" onchange="get_scheme_amount(this);" id="" class="form-control scheme_product">
                        <option value="">Select</option>
                    </select>
                </td>
                <td>
                    <input readonly class="form-control scheme_amount" step="any" type="number" name="scheme_amount[]" value="0">
                </td>
                <td class="hide">
                    <input onkeyup="calc(this)" class="form-control data-tax-percent" type="text" name="data_tax_percent[]" value="0">
                    <input class="data-tax-amount" type="hidden" name="data_tax_amount[]" value="0">
                </td>
                <td>
                    <input readonly class="form-control data-total" type="text" name="data_total[]" value="0">
                </td>
                <td>
                    <button type="button" onclick="removeRow(${counter})" class="btn btn-danger btn-xs"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
            `)
            ++counter;
        }
        function removeRow(params) {
            $('#removeRow' +params).remove();
            calculation();
        }

        function calc(val , discount_amount_type)
        {

            $this = $(val);


                var rate = $this.closest('tr').find('.data-rate').val();
                var qty = $this.closest('tr').find('.data-quantity').val();
                var tax = $this.closest('tr').find('.data-tax-percent').val();
                var trade_offer_amount = $this.closest('tr').find('.trade_offer_amount').val();
                var scheme_amount = $this.closest('tr').find('.scheme_amount').val();
                var total = parseFloat(qty) * parseFloat(rate);
                var tax_amount = (parseFloat(total)/100) * parseFloat(tax);
                $this.closest('tr').find('.data-tax-amount').val(tax_amount);
                // console.log(total);

                if (discount_amount_type) {
                    var discount_total =$this.closest('tr').find('.data-discount-amount').val();
                    var discount_percent = (parseFloat(discount_total)*100) / parseFloat(total) ;
                    // console.log(discount_amount , total , discount_percent);

                    $this.closest('tr').find('.data-discount').val(discount_percent);
                }
                else{
                    var discount_percent =$this.closest('tr').find('.data-discount').val();
                    var discount_total = (parseFloat(total)/100) * parseFloat(discount_percent);
                    $this.closest('tr').find('.data-discount-amount').val(discount_total);
                }
                console.log(total, discount_total, discount_percent);
                var data_total = $this.closest('tr').find('.data-total').val(parseFloat(total) - parseFloat(discount_total) + parseFloat(tax_amount) - parseFloat(trade_offer_amount) - parseFloat(scheme_amount));
                calculation();
        }

       function get_rate(val)
       {

        sale_type = $(val).closest('tr').find('.sale_type').val();
        console.log(sale_type , 'sale_type' , $(val).closest('tr').find('.sale_type option:selected').data('rate'));

        // if (sale_type == 1) {
        //     rate = $(val).closest('tr').find('.searchbox option:selected').data('rate');
        // }
        // else if(sale_type == 2)
        // {
        //     rate = $(val).closest('tr').find('.searchbox option:selected').data('packet_rate');
        // }
        // else
        // {
        //     rate = $(val).closest('tr').find('.searchbox option:selected').data('carton_rate');
        // }
        rate = $(val).closest('tr').find('.sale_type option:selected').data('rate');

        $(val).closest('tr').find('.data-rate').val(rate);
        calc(val);




       }

        function get_product_price(val)
        {
            let product_price = $(val).find(':selected').data('product_price');
            console.log(product_price);

            $(val).closest('tr').find('.sale_type').empty();
            product_price.forEach(price => {
                if (price.status === 1) {
                // Create an option element
                const option = document.createElement('option');
                option.value = price.uom_id;   // Set the value attribute to the price ID
                option.textContent = price.uom.uom_name; // Set the visible text
                $(option).attr('data-rate', price.trade_price);

                $(val).closest('tr').find('.sale_type').append(option);
            }
            });

            get_rate(val);

        }

       function get_flavour(val)
       {
        let flavours = $(val).find(':selected').data('flavour');

        console.log(flavours);

        $(val).closest('tr').find('.flavour').empty();

        // Loop through the flavours array
        flavours.forEach(flavour => {
            // Only add the option if status is 1
            if (flavour.status === 1) {
                // Create an option element
                const option = document.createElement('option');
                option.value = flavour.id;   // Set the value attribute to the flavour ID
                option.textContent = flavour.flavour_name; // Set the visible text

                $(val).closest('tr').find('.flavour').append(option);
            }
        });

       }

       function get_scheme_product(val)
       {
        var qty = $this.closest('tr').find('.data-quantity').val();
        var product_id = $this.closest('tr').find('.product_id').val();
        // $this.closest('tr').find('.scheme_product').empty();
        // $this.closest('tr').find('.scheme_amount').val(0);


        console.log(qty , product_id);

        $.ajax({
            type: "get",
            url: '{{ route('get_scheme_product') }}',
            data: {
                product_id: product_id,
                qty: qty,
            },
            dataType: 'json',
            success: function(data) {

                $this.closest('tr').find('.scheme_product').empty();
                $this.closest('tr').find('.scheme_amount').val(0);

                console.log(data);
                const option = document.createElement('option');
                option.value = '';   // Set the value attribute to the flavour ID
                option.textContent = 'Select'; // Set the visible text

                $(val).closest('tr').find('.scheme_product').append(option);

                $.each(data.scheme_product, function(key, value) {

                    const option = document.createElement('option');
                    option.value = value.scheme_id+','+value.scheme_data_id;   // Set the value attribute to the flavour ID
                    option.textContent = value.scheme_name + '--qty' + value.qty; // Set the visible text
                    $(option).attr('data-scheme_amount', value.scheme_amount);

                    $(val).closest('tr').find('.scheme_product').append(option);
                });
            }
        });
       }
       function get_scheme_amount(val)
       {
        var scheme_amount = $(val).closest('tr').find('.scheme_product option:selected').data('scheme_amount');
        console.log(scheme_amount);

        $(val).closest('tr').find('.scheme_amount').val(scheme_amount ?? 0);
        calc(val);
       }

    //    function get_total_caton_and_qty()
    //    {
    //         let total_carton = 0;
    //         $('.product_id').each(function( index ) {
    //             carton_size = $(this).find('option:selected').data('carton_size');
    //             qty = $(this).closest('tr').find('.data-quantity').val();
    //             carton_qty = parseFloat(qty / carton_size);
    //             total_carton += carton_qty;
    //             console.log(carton_size , qty , carton_qty , 'carton_size ');

    //         });
    //         $('#total_carton').val(total_carton.toFixed(2) ?? 0);

    //    }

       function get_total_caton_and_qty()
       {
            let total_carton = 0;
            let total_qty = 0;
            $('.product_id').each(function( index ) {
                carton_size = $(this).find('option:selected').data('carton_size');
                qty = $(this).closest('tr').find('.data-quantity').val();
                sale_type = $(this).closest('tr').find('.sale_type').val();
                if (sale_type == 7) {
                    total_carton += parseFloat(qty);
                }
                else
                {
                    total_qty += parseFloat(qty);
                }
                // carton_qty = parseFloat(qty / carton_size);
                // total_carton += carton_qty;
                // console.log(carton_size , qty , carton_qty , 'carton_size ');

            });
            $('#total_carton').val(total_carton.toFixed(2) ?? 0);
            $('#total_pcs').val(total_qty.toFixed(2) ?? 0);

       }
    </script>



@endsection
