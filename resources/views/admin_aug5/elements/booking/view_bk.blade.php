@extends('admin.layouts.app')
@section('content')
<div class="container">
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <h3 class="mb-0 card-title">{{$pageTitle}}</h3>
        </div>
        <div class="card-body">
          @if ($message = Session::get('status'))
          <div class="alert alert-success">
            <p>{{ $message }}</p>
          </div>
          @endif
        </div>
        <div class="col-lg-12">
          @if ($errors->any())
          <div class="alert alert-danger">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul>
              @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
          @endif
          <div class="row" >
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">Booking Details</div>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-borderless">
                      <tbody class="col-lg-12 col-xl-12 p-0">
                        <tr>
                          <td><strong>Booking Date :</strong> {{ \Carbon\Carbon::parse(@$order->created_at)->format('M d, Y')}}</td>
                          <td><strong>{{ __('Reference Code') }}:</strong> @if($bkDetail->book_ref_id!=""){{$bkDetail->book_ref_id}}@else Not Specified @endif</td>
                           <td><strong>Booking Status :</strong> {{$bkDetail->status['status']}}</td>
                        </tr>
                       
                        <tr>
                          <td><strong>{{ __('Maker') }}:</strong> @if($bkDetail->model['modal_name']!=""){{$bkDetail->model->maker['maker_name']}}@else Not Specified @endif</td>
                           <td><strong>{{ __('Model') }}:</strong>@if($bkDetail->model['modal_name']!=""){{$bkDetail->model['modal_name']}}@else Not Specified @endif</td>
                            <td><strong>{{ __('Rate Type') }}:</strong>@if($bkDetail->book_car_rate_type!=""){{$bkDetail->rates['rate_type_name']}}@else Not Specified @endif</td>
                         
                        </tr>
                      

                        <tr>
                          <td><strong>From Date :</strong> @if($bkDetail->book_from_date!=""){{$bkDetail->book_from_date}}@else Not Specified @endif</td>
                           <td colspan="2"><strong>To Date :</strong> @if($bkDetail->book_to_date!=""){{$bkDetail->book_to_date}}@else Not Specified @endif</td>
                        </tr>
                       
                        <tr>
                          <td><strong>Pickup Time:</strong> @if($bkDetail->book_pickup_time!=""){{ $bkDetail->book_pickup_time->toTimeString() }}@else Not Specified @endif</td>
                           <td colspan="2"><strong>Return Time:</strong> @if($bkDetail->book_return_time!=""){{$bkDetail->book_return_time->toTimeString()}}@else Not Specified @endif</td>
                        </tr>
                        <tr>
                         
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>{{-- card body end --}}
                </div><!-- COL END -->
              </div>
            </div>
            <div class="row" >
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Customer Details</div>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-borderless">
                        <tbody class="col-lg-12 col-xl-12">
                          <tr>
                            <td><strong>Name :</strong> @if($bkDetail->book_bill_cust_fname!=""){{$bkDetail->book_bill_cust_fname}} @endif &nbsp;
                              @if($bkDetail->book_bill_cust_lname!=""){{$bkDetail->book_bill_cust_lname}} @endif
                            </td>
                             <td><strong>Mobile Number :</strong>@if($bkDetail->book_bill_cust_mobile!=""){{$bkDetail->book_bill_cust_mobile}}@else Not Specified @endif</td>
                              <td><strong>Qatar Id :</strong>@if($bkDetail->book_bill_cust_qatar_id!=""){{$bkDetail->book_bill_cust_qatar_id}}@else Not Specified @endif</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>{{-- card body end --}}
                  </div><!-- COL END -->
                </div>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="card">
                    <div class="card-header">
                      <div class="card-title">Payment Details </div>
                    </div>
                    <div class="card-body">
                      <div class="table-responsive">
                        <table class="table table-borderless">
                          <tbody class="col-lg-12 col-xl-6 p-0">
                             <td><strong>{{ __('Total Days') }}:</strong>@if($bkDetail->book_total_days!=""){{$bkDetail->book_total_days}}@else Not Specified @endif</td>
                           <td><strong>{{ __('Daily Rate') }}:</strong>@if($bkDetail->book_daily_rate!=""){{$bkDetail->book_daily_rate}}@else Not Specified @endif</td>
                            <tr>
                              <td><strong>Drop Fee :</strong> @if($bkDetail->drop_fee!=""){{$bkDetail->drop_fee}}@else Not Specified @endif</td>
                            </tr>
                            <tr>
                              <td><strong>Additional Package :</strong> @if($bkDetail->additional_package!=""){{$bkDetail->additional_package}}@else Not Specified @endif</td>
                            </tr>
                            <tr>
                              <td><strong>{{ __('Total Rate') }}:</strong>@if($bkDetail->book_total_rate!=""){{$bkDetail->book_total_rate}}@else Not Specified @endif @if(!is_null($bkDetail->coupon_code))<span class="badge badge-sm badge-success">Coupon Applied-{{$bkDetail->coupon_code}}</span>@endif</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  </div><!-- COL END -->
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-header">
                        <div class="card-title">Shipping Address</div>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive">
                          <table class="table table-borderless">
                            <tbody class="col-lg-12 col-xl-12">
                              
                              <tr>
                                <td colspan="2"><strong>Billing Address Line 1 :</strong>@if($bkDetail->book_bill_cust_address_1!=""){{$bkDetail->book_bill_cust_address_1}}@else Not Specified @endif</td>
                              </tr>
                              <tr>
                                <td colspan="2"><strong>Billing Address Line 2 :</strong>@if($bkDetail->book_bill_cust_address_1!=""){{$bkDetail->book_bill_cust_address_2}}@else Not Specified @endif</td>
                              </tr>
                              <tr>
                                <td colspan="2"><strong>Location :</strong>@if($bkDetail->book_bill_cust_location!=""){{$bkDetail->state['location_name']}}@else Not Specified @endif , @if($bkDetail->book_bill_cust_city!=""){{$bkDetail->city['city_name']}}@else Not Specified @endif</td>
                                
                              </tr>
                              
                              <tr>
                                 <td><strong>State :</strong>@if($bkDetail->book_bill_cust_state!=""){{$bkDetail->state['location_name']}}@else Not Specified @endif</td>
                                 <td><strong>Zipcode :</strong>@if($bkDetail->book_bill_cust_zipcode!=""){{$bkDetail->book_bill_cust_zipcode}}@else Not Specified @endif</td>
                                  
                                
                              </tr>
                              <tr>
                                <td><strong>Nationality :</strong>@if($bkDetail->book_bill_cust_nationality!=""){{$bkDetail->country['country_name']}}@else Not Specified @endif</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      </div><!-- COL END -->
                    </div>
                  </div>
                </div>
                <br>
                <center>
                <button type="button" class="btn btn-cyan" onclick="history.back()">Cancel</button>
                </center>
                </br>
              {{--   </div>
            </div> --}}
          </div>
        </div>
      </div>
      @endsection
      <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
      <script type="text/javascript">
      $('#print').click(function(){
      $('#print'). hide();
      });
      </script>