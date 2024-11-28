<?php
use App\Helpers\MasterFormsHelper;
$master = new MasterFormsHelper();
use App\Models\Shop;


?>
 <div class="row" id="table-bordered">

    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Shop  Visit / Merchandising <span class="badge badge-success"></span></h4>
            </div>
            <div class="table-responsive">
                <table id="dataTable" class="table table-bordered">
                    <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Shop Name</th>
                        <th>Visit Date</th>
                        <th>Remarks</th>
                        <th>Image</th>

                    </tr>
                    </thead>
                    <tbody id="data">

                        @foreach ($shopVisit as $key => $row)
                        <tr>
                            <td>{{ ++$key }}</td>
                            <td>{{ $row->shop->company_name ?? '' }}</td>
                            <td>{{ date('d-m-Y',strtotime($row->visit_date))  }}</td>
                            <td>{{  $row->remark }}</td>
                            <td>@if($row->merchandising_image)
                                <a target="_blank" href="{{ $row->merchandising_image }}">
                                    <img width="100" height="200" src="{{ $row->merchandising_image }}" alt="Italian Trulli">
                                </a>@endif</td>
                        </tr>
                        @endforeach

                </table>





        </div>
    </div>
   </div>
  </div>






