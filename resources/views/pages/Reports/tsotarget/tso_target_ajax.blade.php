<?php
use App\Models\SaleOrder;
use Illuminate\Support\Facades\DB;
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
?>
<table class="table table-bordered" id="print_data">
    <thead>
        <th>S/no</th>
        {{-- <th>Month</th> --}}
        <th>Distributer</th>
        <th>TSO</th>
        @if ($target_type == 1)
            <th>Product Name</th>
            <th>Target Qty</th>
        @elseif ($target_type == 2)
            <th>Target Amount</th>
        @elseif ($target_type == 3)
            <th>Shop Type</th>
            <th>Shop Target</th>
        @endif

        @if ($summary == '1')
            <th>Acheived Target</th>
        @endif
    </thead>

    <tbody>
        @php
            $i = 1;
            $total_target = 0;
            $total_achive = 0;
        @endphp
        @foreach ($tso_target as $target)
            @php
            if ($target_type == 1) {

                $achived = DB::table('sale_orders')
                    ->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
                    ->where('sale_orders.tso_id', $target->tso_id)
                    ->where('sale_order_data.product_id', $target->product_id)

                    ->whereBetween(DB::raw('MONTH(sale_orders.created_at)'), [$monthfrom, $monthto])
                    ->select(
                        DB::raw('sum(sale_order_data.qty) as achieved_qty'),
                        DB::raw('sum(sale_orders.total_amount) as achieved_amount'),
                    )
                    ->groupBy('sale_order_data.product_id')
                    ->first();
            }
            elseif ($target_type == 2){
                $achived = DB::table('sale_orders')
                    // ->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
                    ->where('sale_orders.tso_id', $target->tso_id)
                    ->whereBetween(DB::raw('MONTH(sale_orders.created_at)'), [$monthfrom, $monthto])
                    ->select(
                        // DB::raw('sum(sale_order_data.qty) as achieved_qty'),
                        DB::raw('sum(sale_orders.total_amount) as achieved_amount'),
                        // DB::raw('sum(sale_order_data.total) as achieved_amount'),
                    )
                    ->first();
            }
            elseif ($target_type == 3)
            {
                $achived = DB::table('shops')
                ->where('shops.tso_id', $target->tso_id)
                ->whereBetween(DB::raw('MONTH(shops.created_at)'), [$monthfrom, $monthto])
                ->select(
                        // DB::raw('sum(sale_order_data.qty) as achieved_qty'),
                        DB::raw('count(*) as achieved_shop'),
                        // DB::raw('sum(sale_order_data.total) as achieved_amount'),
                    )
                    ->first();
            }
            $total_target += $target->type == 1 ? $target->qty : $target->amount ?? $target->type == 3 ? $target->shop_qty : 0;
            $total_achive += $target->type == 2 && $achived ? $achived->achieved_amount : $achived->achieved_qty ?? $achived->achieved_shop??0;

            @endphp
            <tr>
                <td>{{ $i++ }}</td>
                {{-- <td>  {{$month = date('M', strtotime($target->created_at));}}</td> --}}
                <td>{{ $target->distributor_name }}</td>
                <td>{{ $target->tso_name }}</td>
                @if ($target_type == '1')
                    <td>{{ $target->product_name }}</td>
                    <td>{{ $target->type == 1 ? $target->qty : '' }}</td>
                @elseif ($target_type == '2')
                    <td>{{ $target->type == 2 ? $target->amount : '' }}</td>
                @elseif ($target_type == '3')
                    <td>{{ $master->shop_type_name($target->shop_type) }}</td>
                    <td>{{ $target->type == 3 ? $target->shop_qty : '' }}</td>
                @endif
                @if ($summary == '1')
                    <td>{{ $target->type == 2 && $achived ? $achived->achieved_amount : $achived->achieved_qty ?? $achived->achieved_shop ?? 0 }}
                    </td>
                @endif

            </tr>
        @endforeach
        <tr>
            <td colspan="{{$target_type == 2 ? 3 : 4}}">Total</td>
            <td>{{$total_target}}</td>
            @if ($summary == '1')
                <td>{{$total_achive}}</td>
            @endif
        </tr>
    </tbody>
</table>
