
<?php
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
?>

    <?php echo MasterFormsHelper::PrintHead($from,$to,'Load Sheet',$tso_id);?>

@if(count($so_data)>0)
@php
$i =1;
$total = 0;
$total_qty = 0;
$grand_total_qty = [];
@endphp
<table class="table table-bordered" >
    <thead>
    <tr>

        <th>Sr No</th>
        <th>Product Name</th>
        <th>Flavour Name</th>
        <th>Total Sale Qty</th>
        <th>T.P Rate</th>
        <th>Total Amount</th>
    </tr>
    </thead>
<tbody >
@foreach($so_data as $data)
@php
    // dump($execution , $data->excecution);
    $get_qty = '';
    $product_price = '';
    foreach (MasterFormsHelper::get_product_price($data->product_id) as $k => $productPrice) {
        // dump($data->product_id, $data->flavour_id , $productPrice->uom_id , $tso_id , $distributor_id , $execution);
        $qty = MasterFormsHelper::get_sale_qty($from , $to , $data->product_id, $data->flavour_id , $productPrice->uom_id , $tso_id , $distributor_id , $execution);

        $uom_name = $master->uom_name($productPrice->uom_id); // Get UOM name for each product_price UOM
        if ($qty > 0) {
            $get_qty .= ($get_qty ? ' , ' : '') . number_format($qty) . 'x' . $uom_name;
            $grand_total_qty[$productPrice->uom_id] = isset($grand_total_qty[$productPrice->uom_id]) ? $grand_total_qty[$productPrice->uom_id]+$qty : $qty;
        }
        // dump($qty ,$stock->product->id, $stock->flavour_id , $productPrice->uom_id ,Request::get('distributor_id'));
        $product_price .= ($product_price ? ' , ' : '') . number_format($productPrice->trade_price , 2) . '(' . $uom_name . ')';

    }
@endphp
<tr>

    <td>{{$i}}</td>
    <td>{{$data->product_name}}</td>
    <td>{{ MasterFormsHelper::get_flavour_name($data->flavour_id) ?? '--'}}</td>
    <td>{{$get_qty ?? '--'}}</td>
    <td>{{$product_price}}</td>
    {{-- <td>{{$data->qty_summary}}</td> --}}
    <td>{{number_format($data->amount , 2)}}</td>
</tr>
@php
$i++;
$total_qty += $data->qty;
$total += $data->amount;
@endphp
@endforeach
<tr>
    <td colspan="3">Total</td>
    {{-- <td>{{$total_qty}}</td> --}}
    @php
        $total_qty_value = '';
        foreach ($grand_total_qty as $key => $qty) {
            $uom_name = $master->uom_name($key); // Get UOM name for each product_price UOM
            if ($qty > 0) {
                $total_qty_value .= ($total_qty_value ? '<br>' : '') . number_format($qty) . 'x' . $uom_name;
            }
        }
    @endphp
    <td>{!! $total_qty_value !!}</td>
    {{-- <td>{{$total_qty}}</td> --}}
    <td></td>
    <td>{{number_format($total , 2)}}</td>
</tr>
</tbody>
</table>
@else
<table class="table table-bordered" >
<tr>
    <td colspan="5" style="background:rgb(255, 170, 170) "> No Record Found</td>
</tr>
</table>

@endif

