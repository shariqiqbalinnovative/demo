<?php
use App\Models\ShopVisit;
use App\Models\SaleOrder;
use App\Models\Route;
use App\Models\Shop;
use App\Models\UsersLocation;
use App\Helpers\MasterFormsHelper;

$master = new MasterFormsHelper();
$user_allocate = $master->get_assign_user()->toArray();

?>
<div class="table-responsive">
    <table id="dataTable" class="table table-bordered">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Emp Code</th>
                <th>Emp Name </th>
                <th>CNIC</th>
                <th>Designation </th>
                <th>Distributor</th>
                <th> City</th>
                <th>Login Time </th>
                <th>Log out Time </th>
                <th> Total Shops </th>
                <th> Today Shops </th>
                <th> Visited Shops</th>
                <th> Productive Shops</th>
                <th> Order Amount</th>
                <th> Execute Amount</th>
                <th> Balance Amount</th>
                {{-- <th>	%age </th> --}}
            </tr>
        </thead>
        <tbody>
            @php
                $i = 1;
                $total = 0;
                $total_exe = 0;
                $total_bal = 0;
                $total_productive = 0;
                $total_shop = 0;
                $total_today_shop = 0;
                $total_visit_shop = 0;



                $fromDate = $date;
                $toDate = $to;
                $uniqueDays = [];

                $period = new DatePeriod(
                    new DateTime($fromDate),
                    new DateInterval('P1D'),  // Interval of 1 day
                    (new DateTime($toDate))->modify('+1 day')  // Inclusive of end date
                );

                foreach ($period as $new_date) {
                    $dayName = $new_date->format('l');  // Get day name
                    if (!in_array($dayName, $uniqueDays)) {
                        $uniqueDays[] = $dayName;  // Add only unique day names
                    }
                }
                // dd($uniqueDays);
            @endphp
            @foreach ($tsos as $tso)
                @php

                @endphp
                @if (in_array($tso['user_id'], $user_allocate))
                    @if (!empty($tso['attendence']))
                        @foreach ($tso['attendence'] as $row)
                            @php
                                // get Day

                                $date = Carbon\Carbon::parse($row['created_at'])->format('Y-m-d');
                                $timestamp = strtotime($date);
                                $day = date('l', $timestamp);

                                $route = Route::status()
                                    ->where('tso_id', $tso['id'])
                                    ->where('distributor_id', $tso['distributor_id'])
                                    ->pluck('id');

                                $shop_count = Shop::status()->where(['distributor_id' => $tso['distributor_id'] , 'tso_id' => $tso['id']])
                                ->whereIn('route_id', $route)->count();
                                $total_shop += $shop_count;

                                // dd($uniqueDays);
                                $today_shop = Shop::status()->whereIn('route_id', $route)
                                ->where(['distributor_id' => $tso['distributor_id'] , 'tso_id' => $tso['id']])
                                ->whereHas('Route', function ($query) use ($uniqueDays , $route) {
                                    $query->whereHas('RouteDay', function ($subQuery) use ($uniqueDays , $route) {
                                        $subQuery->whereIn('day', $uniqueDays)->whereIn('route_id' , $route);
                                    });
                                })->get()->count();
                                $total_today_shop +=$today_shop;

                                $shop_create = UsersLocation::where('user_id', $tso['user_id'])
                                    ->where('table_name', 'shops')
                                    ->whereDate('created_at', $date)
                                    ->count();

                                $total_visited = ShopVisit::where('user_id', $tso['user_id'])
                                    ->whereDate('visit_date', $date)
                                    ->groupBy('user_id')
                                    ->count('id');
                                $in = $row['in'] ?? '';
                                $out = $row['out'] ?? '';

                            @endphp
                            <tr>
                                <td title="{{ $row['id'] }}">{{ $i++ }}</td>
                                <td>{{ $tso['tso_code'] }}</td>
                                <td>{{ $tso['name'] }}</td>
                                <td>{{ $tso['cnic'] ?? '--'}}</td>
                                <td>{{ $tso['designation']['name'] ?? '' }}</td>
                                <td>{{ $master->get_distributor_name($tso['distributor_id']) ?? '' }}</td>
                                {{-- <td>{{ ($tso['distributor']['distributor_name']) ?? ''}}</td> --}}
                                <td>{{ $tso['cities']['name'] ?? '' }}</td>
                                <td>
                                    @if ($in != '')
                                        {{ date('d-m-Y h:i:s', strtotime($in)) }}
                                    @endif
                                </td>
                                <td>
                                    @if ($out != '')
                                        {{ date('d-m-Y h:i:s', strtotime($out)) }}
                                    @endif
                                </td>


                                <td>{{ $shop_count }}</td>
                                <td>{{ $today_shop }}</td>
                                @php

                                    $sales_count = DB::table('sale_orders')
                                        // ->where('dc_date', $date)
                                        ->whereBetween('dc_date', [$fromDate, $toDate])
                                        ->where('tso_id', $tso['id'])
                                        ->where('distributor_id', $tso['distributor_id']);
                                    $sales_amount = DB::table('sale_order_data')
                                        ->whereIn('so_id', $sales_count->pluck('id'))
                                        ->sum('total');
                                    $sales_amount_exe = DB::table('sale_orders')
                                        ->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
                                        ->whereIn('sale_order_data.so_id', $sales_count->pluck('id'))
                                        ->where('excecution', 1)
                                        ->sum('sale_order_data.total');
                                    $balance_amount = $sales_amount - $sales_amount_exe;
                                    $total += $sales_amount;
                                    $total_exe += $sales_amount_exe;
                                    $total_bal += $balance_amount;

                                    $productive_count = $sales_count->count() ?? 0;
                                    $total_productive += $productive_count;

                                @endphp

                                <td>{{ $total_visited + $productive_count + $shop_create ?? 0 }}</td>
                                <?php
                                $total_visit_shop += $total_visited + $productive_count + $shop_create ?? 0;
                                ?>
                                <td>{{ $productive_count ?? 0 }}</td>
                                <td>{{ number_format($sales_amount, 2) }}</td>
                                <td>{{ number_format($sales_amount_exe, 2) }}</td>
                                <td>{{ number_format($balance_amount, 2) }}</td>
                                {{-- <td></td> --}}

                            </tr>
                        @endforeach
                    @else
                        @php
                            // get Day
                            $date = $date;
                            $timestamp = strtotime($date);
                            $day = date('l', $timestamp);

                            $route = Route::status()
                                ->where('tso_id', $tso['id'])
                                ->where('day', $day)
                                ->where('distributor_id', $tso['distributor_id'])
                                ->pluck('id');
                            $shop_count = Shop::status()->where(['distributor_id' => $tso['distributor_id'] , 'tso_id' => $tso['id']])
                            ->whereIn('route_id', $route)->count();
                            $total_shop += $shop_count;
                            // dd($uniqueDays);
                            $today_shop = Shop::status()->whereIn('route_id', $route)
                            ->where(['distributor_id' => $tso['distributor_id'] , 'tso_id' => $tso['id']])
                            ->whereHas('Route', function ($query) use ($uniqueDays , $route) {
                                $query->whereHas('RouteDay', function ($subQuery) use ($uniqueDays , $route) {
                                    $subQuery->whereIn('day', $uniqueDays)->whereIn('route_id' , $route);
                                });
                            })->get()->count();
                            $total_today_shop +=$today_shop;

                            $shop_create = UsersLocation::where('user_id', $tso['user_id'])
                                ->where('table_name', 'shops')
                                ->whereDate('created_at', $date)
                                ->count();

                            $total_visited = ShopVisit::where('user_id', $tso['user_id'])
                                ->whereDate('visit_date', $date)
                                ->groupBy('user_id')
                                ->count('id');
                            $in = '';
                            $out = '';

                        @endphp
                        <tr>
                            <td>{{ $i++ }}</td>
                            <td>{{ $tso['tso_code'] }}</td>
                            <td>{{ $tso['name'] }}</td>
                            <td>{{ $tso['cnic']?? '--'}}</td>
                            <td>{{ $tso['designation']['name'] ?? '' }}</td>
                            <td>{{ $master->get_distributor_name($tso['distributor_id']) ?? '' }}</td>
                            {{-- <td>{{ ($tso['distributor']['distributor_name']) ?? ''}}</td> --}}
                            <td>{{ $tso['cities']['name'] ?? '' }}</td>
                            <td>
                                @if ($in != '')
                                    {{ date('d-m-Y h:i A', strtotime($in)) }}
                                @endif
                            </td>
                            <td>
                                @if ($out != '')
                                    {{ date('d-m-Y h:i A', strtotime($out)) }}
                                @endif
                            </td>


                            <td>{{ $shop_count }}</td>
                            <td>{{ $today_shop }}</td>
                            @php

                                $sales_count = DB::table('sale_orders')
                                    ->where('dc_date', $date)
                                    ->where('tso_id', $tso['id'])
                                    ->where('distributor_id', $tso['distributor_id']);
                                $sales_amount = DB::table('sale_order_data')
                                    ->whereIn('so_id', $sales_count->pluck('id'))
                                    ->sum('total');
                                $sales_amount_exe = DB::table('sale_orders')
                                    ->join('sale_order_data', 'sale_order_data.so_id', 'sale_orders.id')
                                    ->whereIn('sale_order_data.so_id', $sales_count->pluck('id'))
                                    ->where('excecution', 1)
                                    ->sum('sale_order_data.total');
                                $balance_amount = $sales_amount - $sales_amount_exe;
                                $total += $sales_amount;
                                $total_exe += $sales_amount_exe;
                                $total_bal += $balance_amount;

                                $productive_count = $sales_count->count() ?? 0;

                            @endphp

                            <td>{{ $total_visited + $productive_count + $shop_create ?? 0 }}</td>
                            <?php
                            $total_visit_shop += $total_visited + $productive_count + $shop_create ?? 0;
                            ?>
                            <td>{{ $productive_count ?? 0 }}</td>
                            <td>{{ number_format($sales_amount, 2) }}</td>
                            <td>{{ number_format($sales_amount_exe, 2) }}</td>
                            <td>{{ number_format($balance_amount, 2) }}</td>
                            {{-- <td></td> --}}

                        </tr>
                    @endif
                @endif
            @endforeach

            <tr style="background-color: darkgray;font-weight: bold" class="bold">
                <td>Total</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>{{ $total_shop }}</td>
                <td>{{ $total_today_shop }}</td>
                <td></td>
                <td> {{ $total_productive }}</td>
                <td>{{ number_format($total, 2) }}</td>
                <td>{{ number_format($total_exe, 2) }}</td>
                <td>{{ number_format($total_bal, 2) }}</td>
            </tr>
        </tbody>
    </table>
</div>
