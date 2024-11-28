@php
    use App\Models\Attendence;

@endphp
<style>
    .table-container {
        height: 400px; /* Adjust the height as needed */
        overflow: auto;
    }

    /* Fix the table heading */
    #table_data thead {
        position: sticky;
        top: 0;
        background-color: white; /* Set to match your table's styling */
        z-index: 1;
    }


</style>
<div class="table-responsive table-container">
    <table id="table_data" class="table table-bordered">
        <thead>
            <thead>
                <tr class="text-center">
                    <th  colspan="2"></th>
                    <th style="background-color: rgb(147 190 147)" colspan="3">Opening</th>
                    <th style="background-color: #c0c0d7" colspan="3">Purchase</th>
                    <th style="background-color: #edc7a3" colspan="3">Market Return</th>
                    <th style="background-color: #9999dd" colspan="3">Transfer Received</th>
                    <th style="background-color: #8080803b" colspan="3">Available Sales For Sales</th>
                    <th style="background-color: #edc7a3" colspan="3">Stock Transfer</th>
                    <th style="background-color: #9999dd" colspan="3">Sales (Execution Only) </th>
                    <th style="background-color: #12c52a" colspan="3">Closing  </th>
                </tr>
            </thead>
            <tr>
                <th>S.No</th>
                <th>Prouct Name</th>
                {{-- opening --}}
                <th>PCS </th>
                <th>CTN </th>
                <th>Value </th>

                 {{-- Purchase --}}
                <th>PCS </th>
                <th>CTN </th>
                <th>Value </th>

                 {{-- Transfer Recived --}}
                <th>PCS </th>
                <th>CTN </th>
                <th>Value </th>

                  {{-- Return --}}
                  <th>PCS </th>
                  <th>CTN </th>
                  <th>Value </th>


                 {{-- Avaiable --}}
                <th>PCS </th>
                <th>CTN </th>
                <th>Value </th>

                 {{-- stock transfer --}}
                <th>PCS </th>
                <th>CTN </th>
                <th>Value </th>

                  {{-- Sales --}}
                  <th>PCS </th>
                  <th>CTN </th>
                  <th>Value </th>


                 {{-- Closing --}}


                <th>PCS </th>
                <th>CTN </th>
                <th>Value </th>


            </tr>
        </thead>
        <tbody>
            @php
            $i =1;
            @endphp
            @foreach($result as $item)
            @php
                            // get packing from products
                $packing  = ($item->packing_size >0) ? $item->packing_size : 1;

                            // get discount percentage from distributor

                $discount_amount = ($item->sales_price / 100 ) * $item->max_discount;
                $price = $item->sales_price - $discount_amount;


            @endphp
            <tr>
                <td>{{$i}}</td>
                <td>{{$item->product_name}}</td>

                     {{-- opening --}}
                @php
                    $open_amount = $item->opening_qty * $price;
                    $purchase_amount = $item->purchase_qty * $price;
                    $return_amount = $item->sales_return_qty * $price;
                    $transfer_received_amount = $item->transfer_received_qty * $price;
                @endphp


                <td>{{ number_format($item->opening_qty,2)}}</td>
                <td>{{number_format($item->opening_qty / $packing,2)}}</td>
                <td>{{number_format($item->opening_qty * $price,2)}}</td>

                  {{-- Purchase --}}

                <td>{{ number_format($item->purchase_qty,2)}}</td>
                <td>{{number_format($item->purchase_qty / $packing,2)}}</td>
                <td>{{number_format($item->purchase_qty * $price,2)}}</td>

                  {{-- Return --}}

                <td>{{ number_format($item->sales_return_qty,2)}}</td>
                <td>{{number_format($item->sales_return_qty / $packing,2)}}</td>
                <td>{{number_format($item->sales_return_qty * $price,2)}}</td>

                {{-- Transfer Recived --}}

                <td>{{ number_format($item->transfer_received_qty,2)}}</td>
                <td>{{number_format($item->transfer_received_qty / $packing,2)}}</td>
                <td>{{number_format($item->transfer_received_qty * $price,2)}}</td>


                  {{-- Available --}}

                  @php

                      $available_qty = $item->opening_qty + $item->purchase_qty + $item->sales_return_qty + $item->transfer_received_qty;
                      $available_amount = $open_amount + $purchase_amount + $return_amount + $transfer_received_amount;

                  @endphp

                <td>{{ number_format($available_qty,2)}}</td>
                <td>{{number_format($available_qty / $packing,2)}}</td>
                <td>{{number_format($available_qty * $price,2)}}</td>


                 {{-- Transfer --}}

                 <td>{{ number_format($item->transfer_qty,2)}}</td>
                 <td>{{number_format($item->transfer_qty / $packing,2)}}</td>
                 <td>{{number_format($item->transfer_qty * $item->sales_price,2)}}</td>


                  {{-- Sales --}}

                  <td>{{ number_format($item->sales_qty,2)}}</td>
                  <td>{{number_format($item->sales_qty / $packing,2)}}</td>
                  <td>{{number_format($item->sales_qty * $item->sales_price,2)}}</td>


                @php
                    $sales_amount = $item->sales_qty * $item->sales_price;
                    $transfer_amount = $item->transfer_qty * $item->sales_price;
                    $closing_qty = $available_qty - $item->sales_qty - $item->transfer_qty;
                    $closing_amount = $available_amount - $sales_amount - $transfer_amount;
                @endphp
                <td>{{$closing_qty}}</td>
                <td>{{number_format($closing_qty / $packing,2)}}</td>
                <td>{{number_format($closing_amount,2)}}</td>

            </tr>
            @php
            $i++;
            @endphp
            @endforeach

        </tbody>
    </table>
</div>
