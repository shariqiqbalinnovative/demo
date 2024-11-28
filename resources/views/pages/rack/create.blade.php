@extends('layouts.master')
@section('title', 'SND || Caregory')
@section('content')
    <section id="multiple-column-form">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">ADD NEW Rack</h4>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('rack.store') }}" id="subm" class="form">
                            @csrf
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Rack Code</label>
                                        <input type="text" name="rack_code" class="form-control"
                                            placeholder="Price Type Name" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Bar Code</label>
                                        <input type="text" name="bar_code" class="form-control"
                                            placeholder="Price Type Name" />
                                    </div>
                                </div>
                                <div class="col-md-12">
                                 
                                    <div class="form-group">
                                        @php
                                        $generator = new Picqer\Barcode\BarcodeGeneratorHTML();
                                    @endphp
                                      
                                    {!! $generator->getBarcode('0001245259636', $generator::TYPE_CODE_128) !!}
                                      
                                      
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-1">Create Item</button>
                                    <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Basic Floating Label Form section end -->

@endsection