
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
   <th>TSO</th>
   <th>CNIC</th>
   <th>Product</th>
   <th>Product Flavour</th>
   <th>Carton Size</th>
   <th>CIty </th>
   <th>QTY </th>
   <th>QTY in Ctn</th>
   <th>Amount </th>

    </tr>
</thead>
<tbody>
@php
 $total_qty = 0;
 $total_ctn_qty = 0;
 $total_val = 0;
 @endphp
    @foreach($data as $key => $row)
    @php
        $product_data = $master->get_product_by_id($row->product_id);

        $ctn_qty = 0;
        // $ctn_qty = $row->qty / $product_data->carton_size;


        $get_qty = '';
        $product_price = '';
        foreach (MasterFormsHelper::get_product_price($row->product_id) as $k => $productPrice) {

            $qty = MasterFormsHelper::get_sale_qty2($from , $to , $row->product_id, $row->flavour_id , $productPrice->uom_id , $row->tso_id , $distributor_id ,$city);

            $uom_name = $master->uom_name($productPrice->uom_id); // Get UOM name for each product_price UOM
            if ($qty > 0) {
                $get_qty .= ($get_qty ? ' , ' : '') . number_format($qty) . 'x' . $uom_name;
                $grand_total_qty[$productPrice->uom_id] = isset($grand_total_qty[$productPrice->uom_id]) ? $grand_total_qty[$productPrice->uom_id]+$qty : $qty;
                if ($productPrice->uom_id != 7) {
                    $ctn_qty += $qty / ($productPrice->pcs_per_carton??1);
                }
                else {
                    $ctn_qty += $qty;
                }
            }

            $total_ctn_qty += $ctn_qty;

            $product_price .= ($product_price ? ' , ' : '') . number_format($productPrice->trade_price , 2) . '(' . $uom_name . ')';

        }
    @endphp
    <tr class="text-center">
        <td>{{  ++ $key }}</td>
        <td>{{ $row->name }}</td>
        <td>{{ $row->cnic }}</td>
        <td>{{ $row->product_name }}</td>
        <td>{{ $master->get_flavour_name($row->flavour_id) }}</td>
        <td>1x{{ $product_data->carton_size ?? '--' }}</td>

        <td>{{ $row->city_name }}</td>
        <td>{{ $get_qty }}</td>
        {{-- <td>{{ $row->qty }}</td> --}}
        <td>{{ number_format($ctn_qty , 2)}}</td>
        <td>{{ number_format($row->total,2) }}</td>

    </tr>
    @php
    $total_qty +=$row->qty;
    $total_val +=$row->total;
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
        <td>{{ number_format($total_qty,2) }}</td>
        <td>{{ number_format($total_ctn_qty,2) }}</td>
        <td>{{ number_format($total_val,2) }}</td>
    </tr>

</tbody>
</table>
</div>
