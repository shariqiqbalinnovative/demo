
<?php
use App\Models\ShopVisit;
use App\Models\SaleOrder;
use App\Models\Route;
use App\Models\Shop;
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
?>
<div class="table-responsive">
<table id="dataTable" class="table table-bordered">
    <thead>
  <tr class="text-center">
   <th>S.NO</th>
   <th>Product</th>
   <th>Product Flavour</th>
   <th>Carton Size</th>
   <th>Opening Stock</th>
   <th>Stock In QTY </th>
   <th>Sale QTY </th>
   <th>Balance QTY </th>
   <th>Stock in Ctn</th>
   <th>Amount </th>

    </tr>
</thead>
<tbody>
@php
 $total_qty = 0;
 $total_ctn_qty = 0;
 $total_amount = 0;
 @endphp
    @foreach($data as $key => $row)
    @php
        $product_data = $master->get_product_by_id($row->product_id);

        $get_opening_qty = '';
        $get_booking_qty = '';
        $get_balance_qty = '';
        $get_in_qty = '';
        $get_ctn_qty = 0;
        $get_amount = 0;
        $product_price = '';
        foreach (MasterFormsHelper::get_product_price($row->product_id) as $k => $productPrice) {
            $qty = MasterFormsHelper::get_Stock_opening($row->product_id, $row->flavour_id , $productPrice->uom_id , $distributor_id , $from);

            $uom_name = $master->uom_name($productPrice->uom_id); // Get UOM name for each product_price UOM
            if ($qty > 0) {
                $get_opening_qty .= ($get_opening_qty ? ' , ' : '') . number_format($qty) . 'x' . $uom_name;
                // $grand_total_qty[$productPrice->uom_id] = isset($grand_total_qty[$productPrice->uom_id]) ? $grand_total_qty[$productPrice->uom_id]+$qty : $qty;

            }

            $qty1 = MasterFormsHelper::get_sale_qty($from , $to , $row->product_id, $row->flavour_id , $productPrice->uom_id , null ,$distributor_id , 1);
            if ($qty1 > 0) {
                $get_booking_qty .= ($get_booking_qty ? ' , ' : '') . number_format($qty1) . 'x' . $uom_name;
            }

            $qty2 = MasterFormsHelper::get_Stock($row->product_id, $row->flavour_id , $productPrice->uom_id ,$distributor_id);
            if ($qty2 > 0) {
                $get_balance_qty .= ($get_balance_qty ? ' , ' : '') . number_format($qty2) . 'x' . $uom_name;

                if ($productPrice->uom_id != 7) {
                    $get_ctn_qty += $qty2 / $productPrice->pcs_per_carton;
                }
                else {
                    $get_ctn_qty += $qty2;
                }
                $get_amount += $qty2 * $productPrice->trade_price;
            }

            $qty3 = MasterFormsHelper::get_Stock_by_stock_type($row->product_id, $row->flavour_id , $productPrice->uom_id ,$distributor_id,[0,1,2,4,6],$from , $to);
            if ($qty3 > 0) {
                $get_in_qty .= ($get_in_qty ? ' , ' : '') . number_format($qty3) . 'x' . $uom_name;
            }
        }
        $total_ctn_qty += $get_ctn_qty;
        $total_amount += $get_amount;
    @endphp
    <tr class="text-center">
        <td>{{  ++ $key }}</td>
        <td>{{ $row->product_name }}</td>
        <td>{{ $master->get_flavour_name($row->flavour_id) }}</td>
        <td>1x{{ $product_data->carton_size }}</td>
        <td>{{$get_opening_qty != '' ? $get_opening_qty : '--'}}</td>
        <td>{{$get_in_qty  != '' ? $get_in_qty : '--'}}</td>
        <td>{{$get_booking_qty != '' ? $get_booking_qty : '--'}}</td>
        <td>{{$get_balance_qty  != '' ? $get_balance_qty : '--'}}</td>
        <td>{{number_format($get_ctn_qty , 2)}}</td>
        <td>{{number_format($get_amount , 2)}}</td>

    </tr>
    @php
    @endphp
    @endforeach
    <tr style="background-color: lightgray;font-size: large;font-weight: bold" class="text-center">
        <td>Total</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        {{-- <td>{{ number_format($total_qty,2) }}</td> --}}
        <td>{{ number_format($total_ctn_qty,2) }}</td>
        <td>{{ number_format($total_amount,2) }}</td>
    </tr>

</tbody>
</table>
</div>
