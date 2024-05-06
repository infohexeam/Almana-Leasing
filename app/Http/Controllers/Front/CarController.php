<?php

namespace App\Http\Controllers\Front;

use App\Coupon;
use App\CustomerCoupon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Modal;
use App\Models\Maker;
use App\Models\Currency;
use App\Models\Booking;
use App\Models\Model_category;
use App\Models\Rate_type;
use App\Models\Mode_rate;
use App\Models\Model_image;
use App\Models\City;
use App\Models\Setting;
use App\Models\Model_specification;
use App\Models\Specification;
use App\Models\City_location;
use App\Models\Country;
use App\Models\Customer;
use App\Models\MstAds;
use Illuminate\Support\Facades\DB;
use Crypt;
use Session;
use App\Helpers\Helper;
use App\Models\Promotion;
use Auth;
use  Carbon\Carbon;
use Illuminate\Contracts\Session\Session as SessionSession;
use Validator;
use Illuminate\Support\Facades\URL;
use Mail;
use Image;
use App\Models\Api\Notification;


class CarController extends Controller
{
    
    public function payment()
    {
        
	$orderid= "258745";
	$merchant="DB95927"; 
	$apipassword="afbc40219aa0e4eb35e3ebfd46d809e8"; 
	$amount="1";
	$returnUrl = URL::to('booking-success');
	$currency = "QAR";

	$url = "https://dohabank.gateway.mastercard.com/api/rest/version/57/merchant/DB95927/session";
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_USERPWD, "merchant.DB95927:afbc40219aa0e4eb35e3ebfd46d809e8");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$headers = array(
	   "Authorization: Basic bWVyY2hhbnQuREI5NTkyNzphZmJjNDAyMTlhYTBlNGViMzVlM2ViZmQ0NmQ4MDllOA==",
	   "Content-Type: application/json",
	);
	
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$data = <<<DATA
{

    "apiOperation": "CREATE_CHECKOUT_SESSION",

    "interaction": {

        "operation": "PURCHASE"

    },

    "order"      : {

        "amount"     : "$amount",

        "currency"   : "$currency",

        "description": "Car Booking",

        "id": "$orderid"

    }

}
DATA;


curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

//for debug only!
//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$resp = curl_exec($ch);

$response = json_decode($resp);

// exit;
curl_close($ch);

$sessionid = $response->session->id;

return view('front-end.elements.payment.payment-connect',compact('response','sessionid','amount','currency','orderid'));

        // $endpoint = "https://dohabank.gateway.mastercard.com/api/rest/version/57/merchant/DB95927/session";
        // $client = new \GuzzleHttp\Client();
        // $response = $client->request('POST', $endpoint,  [
        //     'apiOperation' => 'CREATE_CHECKOUT_SESSION', 
        //     // 'apiPassword' => "afbc40219aa0e4eb35e3ebfd46d809e8",
        //     // 'apiUsername'=>"merchant.DB95927",
        //     // 'merchant'=>"DB95927",
        //     'interaction["operation"]' => 'PURCHASE',
        //     'order["id"]' => "46456456",
        //     'order["amount"]'=>"100.00",
        //     'order["currency"]'=> "QAR",
        //     'allow_redirects' => false,
        //       'verify'          => false,
        //       'debug' => true
        // ]);
        
        // $statusCode = $response->getStatusCode();
        // $content = $response->getBody()->getContents();
        // print_r($content);
        
       


  }
    public function carList()
    {
     
    	$fetcCurrency = Currency::orderBy('currency_id','DESC')->get();
    	$vehicleType = Model_category::orderBy('model_cat_id','DESC')->get();
    	$fetchModels = Modal::orderBy('modal_id','DESC')->get();
    	return view('front-end.elements.car.list',compact('fetcCurrency','vehicleType','fetchModels'));
    }

    public function searchCar(Request $request, $id=NULL,$bid=NULL, $cid=1, $filter=NULL)
    {
        $ads = MstAds::get();
      //dd($request->input('city_loc_id'));
      $id = $request->id;
      $bid = $request->bid;
      //dd(Helper::checkOffer(56,17));
      if(Session::has('cur_type'))
      {    
        $fetcCurrency = Currency::orderBy('currency_id','DESC')->get();
        $fetchCity = City::orderBy('city_id','DESC')->get();
        
        $vehicleMaker = Maker::where('maker_name','!=','Not Defined')->orderBy('maker_id','DESC')->get();
        $data = array();
        $model = array();
  
     /* if(!Session::has('location'))
      {*/
      //dd($request->input('city_loc_id'));
          $locId = $request->input('city_loc_id')??Session::get('location');
          //dd($locId);
          $cityId = $request->input('city_id')??Session::get('city');
          $frmDate = $request->input('from_date')??Session::get('fromdate');
          $toDate = $request->input('to_date')??Session::get('todate');
          $pickupTime = $request->input('pickup_time')??Session::get('pickupTime');
          $returnTime = $request->input('return_time')??Session::get('returnTime');
          $curType = Session::get('cur_type');
          Session::put('location',$locId);
          Session::put('city',$cityId);
          Session::put('fromdate',$frmDate);
          Session::put('todate',$toDate);
          Session::put('pickupTime',$pickupTime);
          Session::put('returnTime',$returnTime);
          Session::put('currency_type',$curType);

     /* }else{
          $locId = Session::get('location');
          $cityId = Session::get('city');
          $frmDate =Session::get('fromdate');
          $toDate = Session::get('todate');
          $pickupTime = Session::get('pickupTime');
          $returnTime = Session::get('returnTime');
          $curType = Session::get('currency_type'); 
          
      }*/


          $resCity =City::where('city_id','=',$cityId)->first(); //Get City
          $resLoc = City_location::where('city_loc_id','=',$locId)->first(); //Get Location
          $parseFrmDt = Helper::parseCarbon($frmDate); //parse from date to carbon format
          $parseToDate = Helper::parseCarbon($toDate); //parse to date to carbon format
          $parsePickTime = Helper::parseCarbon($pickupTime); //parse picktime to carbon format
          $parseRetTime = Helper::parseCarbon($returnTime); //parse returntime to carbon format
          $diff = $parsePickTime->diffInHours($parseRetTime); //find hour difference based on time
          $diffDays = $parseFrmDt->diffInDays($parseToDate); //find days difference based on date
          //calculate the difference w.r.t to time
          $combinedfrom = date('Y-m-d H:i:s', strtotime("$frmDate $pickupTime"));
          $combinedto = date('Y-m-d H:i:s', strtotime("$toDate $returnTime"));
          $parsecombinefrom = Helper::parseCarbon($combinedfrom); 
          $parsecombineto = Helper::parseCarbon($combinedto); 
          $diffDays2 = $parsecombinefrom->diffInDays($parsecombineto);
          $mins            = $parseRetTime->diffInMinutes($parsePickTime, true);
          $totMins = ($mins/60);
          
          //get number of days
          if($totMins > 4 && $diff <= 12  && $diffDays2 >= 1)
          {
                $days=$parseFrmDt->diffInDays($parseToDate)+1; 
            
          }else{
             $days=$parseFrmDt->diffInDays($parseToDate); 
          }

          $data['From_Date'] = $frmDate;
          $data['To_date'] = $toDate;
          $data['pickup_Time'] =$pickupTime;
          $data['return_time'] = $returnTime;
          $data['Days'] = $days;
          $data['City'] = @$resCity->city_name;
          $data['city_id'] = @$resCity->city_id;
          $data['Location'] = @$resLoc->location_name;
          $data['location_id'] = @$resLoc->city_loc_id;
          Session::put('days',$days);
          Session::put('city_name',@$resCity->city_name);
          //dd(Session::get('city_name'));
          Session::put('location_name',@$resLoc->location_name);
           $model_images=Model_image::all();
    foreach($model_images as $image) {
        $model_image_ids[] = $image->model_id;
    }
   //dd($model_image_ids);
          if(!$days==0)
            {
              if (!$id==0 && !$bid==0) { //if both vehcile type and brand is selected

               if($id!=13 && $bid!=29)
                {
                
                  $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.modal_category','=',$id)->where('modals.makers','=',$bid)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                          $query->select('model_id')->from('model_images');
                          })->orderBy('modals.modal_id','DESC')->get();
                }else{
                
                  $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                          $query->select('model_id')->from('model_images');
                          })->orderBy('modals.modal_id','DESC')->get();
                }
                
              }elseif(!$id==0 && $bid==0) //if vehice type only is selected
              {

                if(!$id==0 && $id!= 13){
                  $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.modal_category','=',$id)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                          $query->select('model_id')->from('model_images');
                          })->orderBy('modals.modal_id','DESC')->get();
                }else{
                
                  $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                          $query->select('model_id')->from('model_images');
                          })->orderBy('modals.modal_id','DESC')->get();
                }
              }elseif($id==0 && !$bid==0) //if braND alone is selected
              {
                if(!$bid==0  && $bid!=29){

                   $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.makers','=',$bid)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                        $query->select('model_id')->from('model_images');
                        })->orderBy('modals.modal_id','DESC')->get();
               
                }else{
                  
                  $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                        $query->select('model_id')->from('model_images');
                        })->orderBy('modals.modal_id','DESC')->get();
               
                }
              }else{ //if not filter is selected
                
                $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',$model_image_ids)->orderBy('modals.modal_id','DESC')->get();
                        //dd('TEST');
                        //dd($modList);
              }
             
              $car=array();
              $spec=array();
              $cat_ids=array();
              foreach ($modList as $modList) { 
                  $modalId = $modList->modal_id;
                  $car['Model_id'] = $modList->modal_id;
                  $car['Model_name']=$modList->modal_name;
                  $maker=Maker::where('maker_id',$modList->makers)->first();
                  $car['Maker_name']=@$maker->maker_name;
                  $car['Model_category'] = $modList->category['model_cat_name']??'';
                  if(isset($modList->category['model_cat_id']))
                  {
                      array_push($cat_ids,$modList->category['model_cat_id']);
                      
                  }
                  
                  $car['Model_available']=$modList->rdy_count;
                  //image
                  $modImage = Model_image::where('model_id',$modList->modal_id)->where('model_image_flag','=',0)->get();
                      
                      foreach($modImage as $modImages)
                      {
                      $car['Model_image'] = $modImages->model_image;
                      }
                  //specifications
                  $resSpec = Model_specification::where('model_id','=',$modList->modal_id)->where('is_active','=',1)->get();
                  foreach ($resSpec as $resSpecs) {
                      $specification=Specification::where('spec_id',$resSpecs->spec_id)->first();
                    
                    // if($specification->active_flag==1)
                    // {
                    //     $gtspec['Spec_name'] = $resSpecs->specs['spec_name'];
                    //    $gtspec['Spec_Image'] = $resSpecs->specs['spec_icon'];
                    //    array_push($spec,$gtspec); 
                    // }
                   
                  }
                  $car['specifications'] = $spec;
                  //dd($car['specifications']);
                  $typoo = Helper::setType($days);
                 
                  //rate
                  if (in_array(4, $typoo) || in_array(5, $typoo) || in_array(6, $typoo) || in_array(7, $typoo) || in_array(8, $typoo))
                  {
                       $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }else{
                     $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }
                  
                   foreach ($rateType as $rateTypes) {
                    
                      $fetchrate = $rateTypes->rate; //get rate from the table //rate
                      $fetchOfferRate = Helper::checkOffer($rateTypes->model_rate_id,$modList->modal_id);
                      
                      if($curType)
                       {
                          $fetchCurrency = Currency::where('currency_id','=',$curType)->first();
                          $car['Currency_code'] = $fetchCurrency->currency_code;
                          $curConvertRate = $fetchCurrency->currency_conversion_rate;
                          if($fetchOfferRate<$fetchrate)
                          {
                            $rate = $fetchOfferRate*$curConvertRate;
                          }else{
                            $rate = $fetchrate*$curConvertRate;
                          }
                       
                        }

                       $car['Rate_code'] = $rateTypes->rates->rate_type_code;
                       Session::put('rate_type',$rateTypes->rates->rate_type_code);
                       $car['Total_Rates'] = Helper::showList($days,$rate);
                       $rt = Helper::showList($days,$rate);
                       $car['Total_Rate'] = $rt['totValue'];
                       Session::put('totvalue',$rt['totValue']);
                       $car['Rate_per_day'] = $rt['perDayRate'];
                       Session::put('dailyRT',$rt['perDayRate']);
                       $car['Main_Rate']= $fetchrate;
                       $car['Model_Year']= $rateTypes->model_year;
                        $car['Maker']= $rateTypes->maker_id;
                       $car['Offer_Rate'] = $fetchOfferRate;
                       
                     array_push($model,$car); 
                   
                     }
                }
                //dd($model);
             if(Session::has('sort_type'))
             {
              if(Session::get('sort_type')==1)
              {
                $model = $this->array_sort($model,'Offer_Rate', SORT_ASC);
              }
              if(Session::get('sort_type')==2)
              {
                $model = $this->array_sort($model,'Offer_Rate', SORT_DESC);
              }
             }
              $data['Models'] = $model;
            
            }else{
             if(is_null($model)){
              $data['message'] = "No Cars available";
             }
              
            }
            $vehicleType = Model_category::WhereIn('model_cat_id',$cat_ids)->orderBy('model_cat_id','DESC')->get();
            	$pageTitle = "Find Your Ideal Rental Car";
    	        $pageDescription = "Search for the best car rental deals in Doha. Specify your preferences and let Al Mana Leasing provide you with the perfect match.";
            
            return view('front-end.elements.car.search',compact('fetcCurrency','vehicleType','vehicleMaker','parseFrmDt','data','parseToDate','fetchCity','id','bid','ads','pageTitle','pageDescription'),$data);
          }else{
            return redirect('/');
          }
    }
    
    

    public function getLocation(Request $request)
    {
           // $sid        =   $request->city_id;
            //$location   = City_location::where("city_id",'=',$sid)->pluck("ar_location_name","location_name","city_loc_id");
            $sid        =   $request->city_id;
            $location   = City_location::where("city_id",'=',$sid)->pluck("location_name","city_loc_id");
            
            return response()->json($location);
    }
     public function getLocationProfile(Request $request)
    {
            $sid        =   $request->city_id;
            $location   = City_location::where("city_id",'=',$sid)->pluck("location_name","city_loc_id");
            
            
            
            return response()->json($location);
    }
    public function getLocationProfileArabic(Request $request)
    {
            $sid        =   $request->city_id;
            $location   = City_location::where("city_id",'=',$sid)->pluck("arabic_location_name	","city_loc_id");
            
            return response()->json($location);
    }
    

    public function carDetail(Request $request, $id)
    {
      if(Session::has('fromdate')||Session::has('todate'))
      {
        $carId = Crypt::decryptString($id);
        $fetchModel = Modal::where('modal_id','=',$carId)->first();
        $ModelImage = Model_image::where('model_id',$fetchModel->modal_id)->where('model_image_flag','=',0)->first();
        $modelSpec =  Model_specification::where('model_id','=',$fetchModel->modal_id)->where('is_active','=',1)->get();
        $getCont = Country::orderBy('country_id','ASC')->get();
        $fetchCity = City::orderBy('city_id','DESC')->get();
        $getTerms = Setting::where('id','=','1')->first();
        $getInfo = Setting::where('id','=','2')->first();
        return view('front-end.elements.car.detail',compact('carId','fetchModel','ModelImage','modelSpec','getCont','fetchCity','getTerms','getInfo'));
    }else{
      return redirect('/');
    }
    }

    public function getPersonalInfo(Request $request)
    {
      //Session::forget('modal_id');
      if(Session::has('fromdate')||Session::has('todate'))
      {
       

         
           if(empty($request->input('model_id')))
          {
            $carId=Session::get('model_id');
            $Total_Rate = Session::get('total_rate');
            $Rate_per_day = Session::get('rate_per_day');
          }
          else
          {
              $carId = $request->input('model_id'); 
              $Total_Rate = $request->input('total_rate');
              $Rate_per_day = $request->input('rate_per_day');
               Session::put('model_id',$carId);
          Session::put('total_rate',$Total_Rate);
          Session::put('rate_per_day',$Rate_per_day);
          }
         
         
      
      
     
      $fetchModel = Modal::where('modal_id','=',$carId)->first();
     //dd($fetchModel);
     if(!$fetchModel)
      {
           //dd($carId);
          return redirect()->route('app.index');
      }
      $ModelImage = Model_image::where('model_id',$fetchModel['modal_id'])->where('model_image_flag','=',0)->first();
      $modelSpec =  Model_specification::where('model_id','=',$fetchModel['modal_id'])->where('is_active','=',1)->get();
      $getCont = Country::orderBy('country_id','ASC')->get();
      $fetchCity = City::orderBy('city_id','DESC')->get();
      // $Total_Rate = $request->input('total_rate');
      // $Rate_per_day = $request->input('rate_per_day');
      $getTerms = Setting::where('id','=','1')->first();
      $getInfo = Setting::where('id','=','2')->first();
      return view('front-end.elements.car.detail',compact('carId','fetchModel','ModelImage','modelSpec','getCont','fetchCity','getTerms','getInfo','Total_Rate','Rate_per_day'));
       }else{
      return redirect('/');
    }

    }

    public function userInfoSave(Request $request)
    {
        $request->validate(
          [
            'customer_qatar_id' => 'min:11|max:11',
            'book_file' => 'required|mimes:pdf,doc,docx,png,jpeg,jpg'
          ],
          [
            'customer_qatar_id.min' => "The customer qatar id should be 11 characters.",
            'book_file.required' => "Id is required",
            'book_file.mimes' => "Id must be pdf,doc,docx,png,jpeg,jpg.",
          ]
        );
        
      if(Session::has('total_amount_applied'))
      {
        Session::forget('total_amount_applied');
      }
      if(Session::has('coupon_code'))
      {
        Session::forget('coupon_code');
      }
      
          if ($request->hasFile('book_file')) {
      $docFile = time() . '.' . $request->book_file->extension();
      $request->book_file->move('assets/uploads/booking', $docFile);
    }
        
      $data['book_file'] = $docFile ?? '';
      $data['fName'] = $request->first_name??@Session::get('cust_detail')['fName'];
      $data['lName'] = $request->last_name??@Session::get('cust_detail')['lName'];
      $data['MobileNumber'] = $request->mobile_number??@Session::get('cust_detail')['MobileNumber'];
      $data['custDob'] = $request->customer_dob??@Session::get('cust_detail')['custDob'];
      $data['custQatarId'] = $request->customer_qatar_id??@Session::get('cust_detail')['custQatarId'];
      $data['custPassNumber'] = $request->customer_passport_number??@Session::get('cust_detail')['custPassNumber'];
      $data['custLicNumber'] = $request->customer_license_number??@Session::get('cust_detail')['custLicNumbe'];
      $data['custLicDate'] = $request->customer_license_issued_date??@Session::get('cust_detail')['custLicDate'];
      $data['custLicCountry'] = $request->cust_lic_country??@Session::get('cust_detail')['custPassNumber'];
      $data['custNationality'] = $request->cust_nationality??@Session::get('cust_detail')['custLicCountry'];
      $data['address1'] = $request->cust_address_line_1??@Session::get('cust_detail')['address1'];
      $data['address2'] = $request->cust_address_line_2??@Session::get('cust_detail')['address2'];
      $data['custmrCity'] = $request->cust_city??@Session::get('cust_detail')['custmrCity'];
      $data['custLocation'] = $request->cust_location??@Session::get('cust_detail')['custLocation'];
      //dd($data['custLocation']);
      $data['custZipcode'] = $request->cust_zipcode??@Session::get('cust_detail')['custZipcode'];
      $data['rate_per_day'] = $request->rate_per_day??@Session::get('cust_detail')['rate_per_day'];
      $data['total_rate'] = $request->total_rate??@Session::get('cust_detail')['total_rate'];
      $request->session()->put('cust_detail', $data);
      //dd(Session::get('cust_detail')['custNationality']);
      $coupons=Coupon::where('start_date', '<=', date("Y-m-d"))->where('end_date', '>=', date("Y-m-d"))->where('is_active','=',1)->get();
      $modal_id=$request->model_id??Session::get('model_id');
      $fetchModel  = Modal::where('modal_id','=',$modal_id)->first();
       if(!$fetchModel)
      {
          return redirect()->route('app.index');
      }
      $ModelImage = Model_image::where('model_id',$modal_id)->where('model_image_flag','=',0)->first();

      $modelSpec =  Model_specification::where('model_id','=',$modal_id)->where('is_active','=',1)->get();

      if(Auth::guard('main_customer')->check())
      {
        return view('front-end.elements.car.payment-page',compact('data','fetchModel','modelSpec','ModelImage','coupons'),$data);

      }else{
        Session::put('login-confirmation','1');
        return redirect('user-login');
      }

    }
     public function userInfoEdit(Request $request)
    {
      
        $request->validate(
          [
            'customer_qatar_id' => 'min:11|max:11',
            'book_file' => 'required|mimes:pdf,doc,docx,png,jpeg,jpg'
          ],
          [
            'customer_qatar_id.min' => "The customer qatar id should be 11 characters.",
            'book_file.required' => "Id is required",
            'book_file.mimes' => "Id must be pdf,doc,docx,png,jpeg,jpg.",
          ]
        );
        
      if(Session::has('total_amount_applied'))
      {
        Session::forget('total_amount_applied');
      }
      if(Session::has('coupon_code'))
      {
        Session::forget('coupon_code');
      }
      
          if ($request->hasFile('book_file')) {
      $docFile = time() . '.' . $request->book_file->extension();
      $request->book_file->move('assets/uploads/booking', $docFile);
    }
        
      $data['book_file'] = $docFile ?? '';
      $data['fName'] = $request->first_name??@Session::get('cust_detail')['fName'];
      $data['lName'] = $request->last_name??@Session::get('cust_detail')['lName'];
      $data['MobileNumber'] = $request->mobile_number??@Session::get('cust_detail')['MobileNumber'];
      $data['custDob'] = $request->customer_dob??@Session::get('cust_detail')['custDob'];
      $data['custQatarId'] = $request->customer_qatar_id??@Session::get('cust_detail')['custQatarId'];
      $data['custPassNumber'] = $request->customer_passport_number??@Session::get('cust_detail')['custPassNumber'];
      $data['custLicNumber'] = $request->customer_license_number??@Session::get('cust_detail')['custLicNumbe'];
      $data['custLicDate'] = $request->customer_license_issued_date??@Session::get('cust_detail')['custLicDate'];
      $data['custLicCountry'] = $request->cust_lic_country??@Session::get('cust_detail')['custPassNumber'];
      $data['custNationality'] = $request->cust_nationality??@Session::get('cust_detail')['custLicCountry'];
      $data['address1'] = $request->cust_address_line_1??@Session::get('cust_detail')['address1'];
      $data['address2'] = $request->cust_address_line_2??@Session::get('cust_detail')['address2'];
      $data['custmrCity'] = $request->cust_city??@Session::get('cust_detail')['custmrCity'];
      $data['custLocation'] = $request->cust_location??@Session::get('cust_detail')['custLocation'];
      //dd($data['custLocation']);
      $data['custZipcode'] = $request->cust_zipcode??@Session::get('cust_detail')['custZipcode'];
      $data['rate_per_day'] = $request->rate_per_day??@Session::get('cust_detail')['rate_per_day'];
      $data['total_rate'] = $request->total_rate??@Session::get('cust_detail')['total_rate'];
      $request->session()->put('cust_detail', $data);
      //dd(Session::get('cust_detail')['custNationality']);
      $coupons=Coupon::where('start_date', '<=', date("Y-m-d"))->where('end_date', '>=', date("Y-m-d"))->where('is_active','=',1)->get();
      $modal_id=$request->model_id??Session::get('model_id');
      $fetchModel  = Modal::where('modal_id','=',$modal_id)->first();
       if(!$fetchModel)
      {
          return redirect()->route('app.index');
      }
      $ModelImage = Model_image::where('model_id',$modal_id)->where('model_image_flag','=',0)->first();

      $modelSpec =  Model_specification::where('model_id','=',$modal_id)->where('is_active','=',1)->get();

      if(Auth::guard('main_customer')->check())
      {
        return view('front-end.elements.car.payment-page-edit',compact('data','fetchModel','modelSpec','ModelImage','coupons'),$data);

      }else{
        Session::put('login-confirmation','1');
        return redirect('user-login');
      }

    }
    public function applyCoupon(Request $request)
    {
      $total_amount=$request->total_amount;
      if(Auth::guard('main_customer')->check())
      {
      $coupon_code=$request->coupon_code;
      
      //return $total_amount;
      $coupon=Coupon::where('coupon_code',$coupon_code);
      if(!$coupon->exists())
      {
       return response()->json(['status'=>0,'total_rate'=>$total_amount]);
      }
      else
      {
        if(date('Y-m-d')>$coupon->first()->end_date)
        {
          return response()->json(['status'=>5,'total_rate'=>$total_amount]);
        }
        if($coupon->first()->minimum_order_amount>=$total_amount)
        {
          return response()->json(['status'=>6,'total_rate'=>$total_amount]);
        }
        
      }
      $customer_coupon=CustomerCoupon::where('customer_id',Auth::guard('main_customer')->user()->id)->where('coupon_id',@$coupon->first()->id);
      if($customer_coupon->exists())
      {
        return response()->json(['status'=>1,'total_rate'=>$total_amount]);
      }
      else
      {
        /*$ccoupon=CustomerCoupon::where('customer_id',Auth::guard('main_customer')->user()->id)->where('coupon_id',@$coupon->first()->id);
        if($ccoupon->exists())
        {
          $ccoupon->delete();
        }*/

        $customer_coupon=new CustomerCoupon();
        $customer_coupon->coupon_id=$coupon->first()->id;
        $customer_coupon->customer_id=Auth::guard('main_customer')->user()->id;
        $customer_coupon->is_applied=1;
        $customer_coupon->save();
        if($coupon->first()->discount_type==1)
        {
          $total_amount_applied=$total_amount-$coupon->first()->discount_value;
        }
        if($coupon->first()->discount_type==2)
        {
          $total_amount_applied=$total_amount-($total_amount*$coupon->first()->discount_value)/100;
        }
        Session::put('total_amount_applied',$total_amount_applied);
        Session::put('coupon_code',$coupon->first()->coupon_code);
       
        return response()->json(['status'=>2,'total_rate'=>number_format($total_amount_applied,2)]);
      }
      return response()->json(['status'=>3,'total_rate'=>$total_amount]);
    }
    else
    {
      return response()->json(['status'=>4,'total_rate'=>$total_amount]);
    }

    }
    public function removeCoupon(Request $request)
    {
      $total_amount=$request->total_amount;
      if(Auth::guard('main_customer')->check())
      {
      $coupon_code=$request->coupon_code??null;
      
      //return $total_amount;
      $coupon=Coupon::where('coupon_code',$coupon_code);
     
      $customer_coupon=CustomerCoupon::where('customer_id',Auth::guard('main_customer')->user()->id)->where('coupon_id',@$coupon->first()->id);
        if($customer_coupon->exists())
        {
          $customer_coupon->delete();
        }

      
        if($coupon->first()->discount_type==1)
        {
          $real_amount=$total_amount;
        }
        if($coupon->first()->discount_type==2)
        {
          $real_amount=$total_amount;
        }
        Session::put('total_amount_applied',$real_amount);
        Session::forget('coupon_code');
       
        return response()->json(['status'=>2,'total_rate'=>number_format($real_amount,2)]);
      }
  
  

    }

    public function bookingSave(Request $request, Booking $booking)
    {
      if(Session::has('fromdate')||Session::has('todate'))
      {
      $custName = ucfirst(Auth::guard('main_customer')->user()->customer->cust_fname) . ' ' . ucfirst(Auth::guard('main_customer')->user()->customer->cust_lname);
      $referIdCheck = Booking::latest('book_id')->first(); //check if any booking exist in tb and fetch its reference id
      $date = date('Ymd');
      $i=1;
      if(is_null($referIdCheck))
      {
          $num=rand(10,99);
         $ref_number = $date.'000'.$i;
      }
      else
      {
          $orderid  = $referIdCheck->book_ref_id;
         // dd($orderid);
          $orderdate = mb_substr($orderid, 0, 8);
          $ids = substr($orderid, 8);
          if($orderdate == $date){
              $ids++;
               $num=rand(10,99);
               if($ids<10)
               {
                   $ref_number = $orderdate.'000'.$ids;
                   
               }
               else
               {
                   $ref_number = $orderdate.'00'.$ids;
                   
               }
              
             // dd($app_number,"hii");
          }
          else{
               $num=rand(10,99);
              $ref_number = $date.'000'.$i;
            
          }  
      }
      $ReferId=$ref_number;
        
        $currency = Session::get('cur_code');
        //$request->book_total_rate=session()->get('total_rate')??$request->book_total_rate;
        $datas= $request->except('_token');
        $coupon_code=$request->coupon_code;
      
        $currDetail = Booking::insertGetId($datas);
        $book_daily_rate=$request->book_daily_rate;
        $book_total_days=$request->book_total_days;
        $bookFile = $request->book_file;
        
        if(Session::has($coupon_code))
        {
            $coupon_discount=($book_daily_rate*$book_total_days)-Session::get('total_amount_applied');
            
        }
        else
        {
            $coupon_discount=0;
            
        }
        Booking::where('book_id','=',$currDetail)->update([
          'currency_id' => $currency,
          'drop_fee' => '0.00',
          'additional_package' => '0.00',
          'book_ref_id' => $ReferId,
          'book_file' => $bookFile,
          'book_status' =>1, //pending payment
          'book_total_rate'=>Session::get('total_amount_applied')??$request->book_total_rate,
          'coupon_code'=>Session::get('coupon_code')??NULL,
          'coupon_discount'=>$coupon_discount,
          'created_at' =>\Carbon\Carbon::now(),
          'updated_at' =>\Carbon\Carbon::now()

        ]);
        Session::put('booking_ref_id',$ReferId);
        Session::put('Name',$custName);
        $TotalAmnt = Session::get('total_amount_applied')??$request->book_total_rate;
       // Session::forget('total_rate');
        //Doha bank payment gateway 
        // 	$orderid= $this->generateRandomString(6);
	       // $merchant="DB95927"; 
        // 	$apipassword="afbc40219aa0e4eb35e3ebfd46d809e8"; 
	       // $amount=$TotalAmnt;
	       // $returnUrl = URL::to('booking-success');
	       // $currency = "QAR";
          Session::forget('total_amount_applied');
          Session::forget('fromdate');
          Session::forget('todate');
	       // $url = "https://dohabank.gateway.mastercard.com/api/rest/version/57/merchant/DB95927/session";
	
        // 	$ch = curl_init($url);
        // 	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // 	curl_setopt($ch, CURLOPT_USERPWD, "merchant.DB95927:afbc40219aa0e4eb35e3ebfd46d809e8");
        // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 	curl_setopt($ch, CURLOPT_POST, true);
        // 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // 	$headers = array(
        // 	   "Authorization: Basic bWVyY2hhbnQuREI5NTkyNzphZmJjNDAyMTlhYTBlNGViMzVlM2ViZmQ0NmQ4MDllOA==",
        // 	   "Content-Type: application/json",
        // 	);
	
	
// 	        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// $data = <<<DATA
// {

//     "apiOperation": "CREATE_CHECKOUT_SESSION",

//     "interaction": {

//         "operation": "PURCHASE"

//     },

//     "order"      : {

//         "amount"     : "$amount",

//         "currency"   : "$currency",

//         "description": "Car Booking",

//         "id": "$orderid"

//     }

// }
// DATA;


// curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

//for debug only!
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// $resp = curl_exec($ch);

// $response = json_decode($resp);

// exit;
// curl_close($ch);

// $sessionid = $response->session->id;
/*******************Skip cash****************************************/

         $skipCashKeyId="ec7afae2-a8b3-4bbd-9231-9eec675cca52";
         $skipCashClientId="c6c70e68-7478-4fa0-9d43-1c332d40d627";
         $skipCashSecretKey="gwaqDJ5zYwZGtAaURikpoJO9SF2UzECjppc9eqkH62lXh5sPnwv69s+5AaZwc6eQtghL76uoZPYTOTOBjVtPYFipbt6EGsD93tns4kC8BIWLZI7LpwbqrejuFuFlN0m6+SNgYLqWthmOxBXCKPi+W1TICI7yahEGmyXCLG7ZtBKGDV4v4rrlEhELnoGk3e5ODeO82izb0BgKfP2p4AU2NgWrheIV6M+MwcWiqyqa9YwgocKcOUQjMzi6hG2A9ibF2Z/Yo8/c0NihdrSwfPkj1Cd3g7hzw3/IdiQXdT+xUWRLgf/XEyyiUNRgOHQw3ADjHw59XqKsB+Isok9jkMgHRJalM2ebMZZ5voIcCPjYeMKfc8NLOIxWsibsvB4aq+eX0gyG6cXvSRDSCXaxDOJYaxEXdyrWPmZk4Mkos8hEv0MlrN4j1XtpXLoSLp2IkhLjm4Q3W8DHvcRyIEYj3Xx8UWpAK/OibXDfGpqiA/2JS6YDtukT3kEia0fqwXoETF4Ur7uCWXD5qsNHNCQjbxg28w==";
        
        //  $skipCashKeyId="e0c81353-ed49-4203-8724-313126b21681";
        //  $skipCashClientId="00d89469-0d43-4b41-8643-5d12fa46b6bf";
        //  $skipCashSecretKey="56NdvKtI6YTDZl9F3LbK4S3HmTL/K3ZpsM7ay7s28ON30haLo3t/nYm+6go4WCcXKn0/Krb4mVEnYoIa2jZv21CV9n1u6M6qmQX97cG1IEOLZCHenLZLXmk2bZoYza9Fli7ZZjiiyCfRgR+KZGgCzgU/sK1yRMqc9MaTTy5xw6pisLv+VwsW/0nKVhiMOY0qBSKFYaXvhb08ruQrn4rn4GnhKmR5rxYV/CudiqTeXmkKLQO8od2Q/zcRqr+T2e5gXLG8v7sI1wOspcvIadZGRiAJOEievXTXnSekteoLjibtGpsHcDRnARpWTBJQvUSLu6x0aOP7IuzMM/GtRbeRxgw+Mb4Ecpp1AKi9nY/1ZNku8LK9s6SfD1FthrZpH6f3v8Slv9qXLowev+YPcpLLTYMc8R5FdxlPGFEt9h6Y+oJe7uB9HzyCbnovAujWtJXjMxqTnYm4J/GHA+9Xt312xZpWrKSWIe6XLq0R516i9hG9em9x+gHzjG7UoW2kG0nXeGBQNP6DpwgILCS0qbYLqg==";
         
         $oAmount=1;
         $uid=Str::uuid()->toString();
        $skipCashUrl="https://api.skipcash.app/api/v1/payments";
        // $skipCashUrl="https://skipcashtest.azurewebsites.net/api/v1/payments";
         
         $formData = [
            "Uid" =>$uid ,
            "KeyId" =>$skipCashKeyId,
            "Amount" => "$TotalAmnt",
            "FirstName" =>ucfirst(Auth::guard('main_customer')->user()->customer->cust_fname),
            "LastName" => ucfirst(Auth::guard('main_customer')->user()->customer->cust_lname),
            "Phone" => Auth::guard('main_customer')->user()->customer->cust_mobile,
            "Email" => Auth::guard('main_customer')->user()->customer->email,
            "Street" => "Al Samriya St 10",
            "City" => "Doha",
            "State" => "AV",
            "Country" => "QA",
            "PostalCode" => "670307",
            "TransactionId" => $ReferId,
        ];
      
        $qry = http_build_query($formData," ", ",");//Combining all the request fields into key=value line separated by comma:
        $query=urldecode($qry);
         //dd($query,$TotalAmnt,$ReferId);
        $sha_signature = hash_hmac('sha256',$query,$skipCashSecretKey,true);//Encrypt using the algorithm HMACSHA256
        $signature=base64_encode($sha_signature);//Convert to base 64 format
        $headers = [
            'Authorization: '.$signature,
            'Content-Type: application/json;charset=UTF-8',
            'x-client-id: ' .$skipCashClientId
        ];
        
        $chh = curl_init();
        curl_setopt($chh, CURLOPT_URL,$skipCashUrl);
        curl_setopt($chh, CURLOPT_POST, true);
        curl_setopt($chh, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($chh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chh, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($chh, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($chh, CURLOPT_POSTFIELDS, json_encode($formData));

        $re = curl_exec($chh);
        

       $d = json_decode($re);
        
        curl_close($chh);
        
         
        //dd($d);
          $reslt = $d->resultObj;
          $payUrl = $reslt->payUrl;
          return redirect()->intended($payUrl);
            //dd($reslt);
        // return redirect()->to($payUrl);
         
        

/****************End skip cash***************************************/

//*****************Skip Cash*****************/




/************************************End Skipcash2*****************************************/





$sessionid=rand(1000,9999);
Session::put('orderId',$orderid);

//return view('front-end.elements.payment.payment-connect',compact('response','sessionid','amount','currency','orderid'));
   $bookingInfo = Booking::where('book_id','=',$currDetail)->first();
   $customer=Customer::where('customer_id','=',$bookingInfo->book_cust_id)->first();
               if($customer!=NULL)
                {
                    $customer_first_name=$customer->cust_fname;
                    $customer_last_name=$customer->cust_lname;
                    $customer_full_name=$customer->fname.''.$customer->cust_lname;
                    $customer_dob=$customer->cust_dob;
                    $customer_email=$customer->email;
                    $customer_address=$bookingInfo->book_bill_cust_address_1??''.' '.$bookingInfo->book_bill_cust_address_2;
                    $customer_license_no=$customer->cust_driving_license_no;
                    $customer_license_exp_date=$customer->cust_license_issued_date;
                    $customer_passport=$customer->cust_passport_number;
                    $customer_passport_expiry="2022-11-11";//$customer->cust_passport_number;
                    //$customer_license_exp_date=$customer->cust_license_issued_date;
                    $city_qry=City::where('city_id',$bookingInfo->book_bill_cust_city)->first();
                    
                    $statusName = "Under Process";
                    $bkmodelImage = $bookingInfo->model->modelImage->model_image;
                    
                  
             
             
                    if($city_qry)
                    {
                        $city=$city_qry->city_name;
                    }
                    else
                    {
                        $city="";
                    }
                     $location_qry=City_location::where('city_loc_id',$bookingInfo->book_bill_cust_location)->first();
                    if($location_qry)
                    {
                        $location=$location_qry->location_name;
                    }
                    else
                    {
                        $location="";
                    }
                    //dd($location);
                    $country_qry=Country::where('country_id',$customer->cust_nationality)->first();
                    if($country_qry)
                    {
                        $country=$country_qry->country_name;
                        
                    }
                    
                    $client1 = new \GuzzleHttp\Client();
                if($customer->cust_qatar_id!=NULL)
                {
                    $id_no=$customer->cust_qatar_id;
                    
                }
                else
                {
                    $id_no=$customer_passport;
                    
                }
                $api = $client1->get('http://130.61.97.192:201/F_CUSTOMERS?LookupText='.$customer_first_name.'&PageSize=1&Skip=0&FC_MOBILE='.$customer->cust_mobile_number.'&FC_ID_NO='.$id_no);
                
                
                $data = $api->getBody()->getContents();
                $response = json_decode($data, true);
                if($response){
                    $respItems = $response['Items'];
                    //dd($respItems);
                }
               
               
                if(sizeof($respItems))
                {
                    $custCode = $response['Items']['0']['FC_CUST_NO'];
                    $customer->cust_code=$custCode;
                    $customer->update();
                    
                }
                else
                {
                    $custCode=null;
                    
                }
                    
                }
                else
                {
                    $customer_first_name="Not exist";
                    $customer_last_name="Not exist";
                    $customer_full_name='Not exist';
                    
                }
                $modal=Modal::where('modal_id',$bookingInfo->book_car_model)->first();
                if($modal)
                {
                    $model_number=(int)$modal->model_number;
                    $model_category=(int)$modal->modal_category;
                    $makerid=(int)$modal->makers;
                    $modelName = $modal->modal_name;
                    $makerName = $modal->maker['maker_name'];
                    
                    
                }
                else
                {
                     $model_number=0;
                    $model_category=0;
                    $makerid=0;
                    
                }
                
        $data = array('customer_fname'=>$customer_first_name,'customer_lname'=>$customer_last_name,
             'booking_ref_id'=>$ReferId,'booking_status'=>$statusName,'to_mail'=>$customer_email,'booking_from_date'=>$bookingInfo->book_from_date,'booking_to_date'=>$bookingInfo->book_to_date,'booking_pickup_time'=>$bookingInfo->book_pickup_time,'booking_return_time'=>$bookingInfo->book_return_time,'booking_total'=>$bookingInfo->book_total_rate,
            'booking_city_name'=>$city,'booking_model_name'=>$modelName,
             'booking_maker_name'=>$makerName,'booking_model_image'=>$bkmodelImage);
             
             //send mail to customer
             
              //mail to reservation mail of almana
            //  $usersArray = ['info@almanaleasing.com', 'rentals@almanaleasing.com
            //     '];
        //         foreach($usersArray as $user){
        //      Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
        //     $message->to($user,'Almana Leasing')->subject
        //              ('NEW BOOKING INFORMATION');
        //  $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
        //       });
        //         }
            
             Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to($data['to_mail'],'Almana Leasing')->subject
                     ('BOOKING INFORMATION');
            $message->cc('info@almanaleasing.com', 'Almana Leasing');
            $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
             });
             
            
            
            //save notification
            $notfContent = "Your Booking with Reference Number: " . $ReferId . " is " . $statusName;
            Notification::create([
                    'customer_id' => $bookingInfo->book_cust_id,
                    'notification_title' => "Booking Status",
                    'notification_content' => $notfContent,
                    'notification_status' => 1
                ]);
                 
                
                
              
       return redirect('booking-success');
        }
        else{
      return redirect('/');
    }

    }
    
   
    public function bookingUpdate(Request $request, Booking $booking)
    {
      if(Session::has('fromdate')||Session::has('todate'))
      {
      $custName = ucfirst(Auth::guard('main_customer')->user()->customer->cust_fname) . ' ' . ucfirst(Auth::guard('main_customer')->user()->customer->cust_lname);
      $referIdCheck = Booking::latest('book_id')->first(); //check if any booking exist in tb and fetch its reference id
      $date = date('Ymd');
      $i=1;
     /* if(is_null($referIdCheck))
      {
          $num=rand(10,99);
         $ref_number = $date.$i;
      }
      else
      {
          $orderid  = $referIdCheck->book_ref_id;
         // dd($orderid);
          $orderdate = mb_substr($orderid, 0, 8);
          $ids = substr($orderid, 8);
          if($orderdate == $date){
              $ids++;
               $num=rand(10,99);
              $ref_number = $orderdate.$ids;
             // dd($app_number,"hii");
          }
          else{
               $num=rand(10,99);
              $ref_number = $date.$i;
            
          }  
      }*/
      $ReferId=Session::get('ref_no');
        
        $currency = Session::get('cur_code');
        //$request->book_total_rate=session()->get('total_rate')??$request->book_total_rate;
        $datas= $request->except('_token');
        $coupon_code=$request->coupon_code;
        //Session::put('ref_no',$refNo);
      
        $currDetail = Booking::Where('book_ref_id',$ReferId)->update($datas);
        $book_daily_rate=$request->book_daily_rate;
        $book_total_days=$request->book_total_days;
        $bookFile = $request->book_file;
        
        if(Session::has($coupon_code))
        {
            $coupon_discount=($book_daily_rate*$book_total_days)-Session::get('total_amount_applied');
            
        }
        else
        {
            $coupon_discount=0;
            
        }
        Booking::where('book_ref_id','=',$ReferId)->update([
          'currency_id' => $currency,
          'drop_fee' => '0.00',
          'additional_package' => '0.00',
          'book_ref_id' => $ReferId,
          'book_file' => $bookFile,
          'book_status' =>1, //pending payment
          'book_total_rate'=>Session::get('total_amount_applied')??$request->book_total_rate,
          'coupon_code'=>Session::get('coupon_code')??NULL,
          'coupon_discount'=>$coupon_discount,
          'created_at' =>\Carbon\Carbon::now(),
          'updated_at' =>\Carbon\Carbon::now()

        ]);
        Session::put('booking_ref_id',$ReferId);
        Session::put('Name',$custName);
        $TotalAmnt = Session::get('total_amount_applied')??$request->book_total_rate;
       // Session::forget('total_rate');
        //Doha bank payment gateway 
        // 	$orderid= $this->generateRandomString(6);
	       // $merchant="DB95927"; 
        // 	$apipassword="afbc40219aa0e4eb35e3ebfd46d809e8"; 
	       // $amount=$TotalAmnt;
	       // $returnUrl = URL::to('booking-success');
	       // $currency = "QAR";
          Session::forget('total_amount_applied');
          Session::forget('fromdate');
          Session::forget('todate');
	       // $url = "https://dohabank.gateway.mastercard.com/api/rest/version/57/merchant/DB95927/session";
	
        // 	$ch = curl_init($url);
        // 	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // 	curl_setopt($ch, CURLOPT_USERPWD, "merchant.DB95927:afbc40219aa0e4eb35e3ebfd46d809e8");
        // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // 	curl_setopt($ch, CURLOPT_POST, true);
        // 	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        // 	$headers = array(
        // 	   "Authorization: Basic bWVyY2hhbnQuREI5NTkyNzphZmJjNDAyMTlhYTBlNGViMzVlM2ViZmQ0NmQ4MDllOA==",
        // 	   "Content-Type: application/json",
        // 	);
	
	
// 	        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// $data = <<<DATA
// {

//     "apiOperation": "CREATE_CHECKOUT_SESSION",

//     "interaction": {

//         "operation": "PURCHASE"

//     },

//     "order"      : {

//         "amount"     : "$amount",

//         "currency"   : "$currency",

//         "description": "Car Booking",

//         "id": "$orderid"

//     }

// }
// DATA;


// curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

//for debug only!
// curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// $resp = curl_exec($ch);

// $response = json_decode($resp);

// exit;
// curl_close($ch);

// $sessionid = $response->session->id;
$sessionid=rand(1000,9999);
//Session::put('orderId',$orderid);

//return view('front-end.elements.payment.payment-connect',compact('response','sessionid','amount','currency','orderid'));
   $bookingInfo = Booking::where('book_ref_id','=',$ReferId)->first();
   $customer=Customer::where('customer_id','=',$bookingInfo->book_cust_id)->first();
               if($customer!=NULL)
                {
                    $customer_first_name=$customer->cust_fname;
                    $customer_last_name=$customer->cust_lname;
                    $customer_full_name=$customer->fname.''.$customer->cust_lname;
                    $customer_dob=$customer->cust_dob;
                    $customer_email=$customer->email;
                    $customer_address=$bookingInfo->book_bill_cust_address_1??''.' '.$bookingInfo->book_bill_cust_address_2;
                    $customer_license_no=$customer->cust_driving_license_no;
                    $customer_license_exp_date=$customer->cust_license_issued_date;
                    $customer_passport=$customer->cust_passport_number;
                    $customer_passport_expiry="2022-11-11";//$customer->cust_passport_number;
                    //$customer_license_exp_date=$customer->cust_license_issued_date;
                    $city_qry=City::where('city_id',$bookingInfo->book_bill_cust_city)->first();
                    
                    $statusName = "Under Process";
                    $bkmodelImage = $bookingInfo->model->modelImage->model_image;
                    
                  
             
             
                    if($city_qry)
                    {
                        $city=$city_qry->city_name;
                    }
                    else
                    {
                        $city="";
                    }
                     $location_qry=City_location::where('city_loc_id',$bookingInfo->book_bill_cust_location)->first();
                    if($location_qry)
                    {
                        $location=$location_qry->location_name;
                    }
                    else
                    {
                        $location="";
                    }
                    //dd($location);
                    $country_qry=Country::where('country_id',$customer->cust_nationality)->first();
                    if($country_qry)
                    {
                        $country=$country_qry->country_name;
                        
                    }
                    
                    $client1 = new \GuzzleHttp\Client();
                if($customer->cust_qatar_id!=NULL)
                {
                    $id_no=$customer->cust_qatar_id;
                    
                }
                else
                {
                    $id_no=$customer_passport;
                    
                }
                $api = $client1->get('http://130.61.97.192:201/F_CUSTOMERS?LookupText='.$customer_first_name.'&PageSize=1&Skip=0&FC_MOBILE='.$customer->cust_mobile_number.'&FC_ID_NO='.$id_no);
                
                
                $data = $api->getBody()->getContents();
                $response = json_decode($data, true);
                if($response){
                    $respItems = $response['Items'];
                    //dd($respItems);
                }
               
               
                if(sizeof($respItems))
                {
                    $custCode = $response['Items']['0']['FC_CUST_NO'];
                    $customer->cust_code=$custCode;
                    $customer->update();
                    
                }
                else
                {
                    $custCode=null;
                    
                }
                    
                }
                else
                {
                    $customer_first_name="Not exist";
                    $customer_last_name="Not exist";
                    $customer_full_name='Not exist';
                    
                }
                $modal=Modal::where('modal_id',$bookingInfo->book_car_model)->first();
                if($modal)
                {
                    $model_number=(int)$modal->model_number;
                    $model_category=(int)$modal->modal_category;
                    $makerid=(int)$modal->makers;
                    $modelName = $modal->modal_name;
                    $makerName = $modal->maker['maker_name'];
                    
                    
                }
                else
                {
                     $model_number=0;
                    $model_category=0;
                    $makerid=0;
                    
                }
                
        $data = array('customer_fname'=>$customer_first_name,'customer_lname'=>$customer_last_name,
             'booking_ref_id'=>$ReferId,'booking_status'=>$statusName,'to_mail'=>$customer_email,'booking_from_date'=>$bookingInfo->book_from_date,'booking_to_date'=>$bookingInfo->book_to_date,'booking_pickup_time'=>$bookingInfo->book_pickup_time,'booking_return_time'=>$bookingInfo->book_return_time,'booking_total'=>$bookingInfo->book_total_rate,
            'booking_city_name'=>$city,'booking_model_name'=>$modelName,
             'booking_maker_name'=>$makerName,'booking_model_image'=>$bkmodelImage);
             
             //send mail to customer
            
             Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to($data['to_mail'],'Almana Leasing')->subject
                     ('BOOKING INFORMATION');
            $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
             });
             
             //mail to reservation mail of almana
             
             //mail to reservation mail of almana
             $usersArray = ['info@almanaleasing.com', 'rentals@almanaleasing.com
                '];
                foreach($usersArray as $user){
             Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to($user,'Almana Leasing')->subject
                     ('NEW BOOKING INFORMATION');
         $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
              });
                }
            
            //save notification
            $notfContent = "Your Booking with Reference Number: " . $ReferId . " is " . $statusName;
            Notification::create([
                    'customer_id' => $bookingInfo->book_cust_id,
                    'notification_title' => "Booking Status",
                    'notification_content' => $notfContent,
                    'notification_status' => 1
                ]);
                 
                
                
         //Session::forgot('ref_no')  
         Session::forget('ref_no');
       return redirect()->route('user.reservations')->with('cancel-success','Reservation updated successfully for reference id '.Session::get('booking_ref_id'));
        }
        else{
      return redirect('/');
    }

    }

    public function bookingSuccess(Request $request)
    {
      $referenceId = Session::get('booking_ref_id');
      $Name = Session::get('Name');
      /*********************Skipcash return*******************************************************/
      if(!isset($_GET['id']))
      {
          return redirect()->to('/');
      }

      $id =  $_GET['id'];
      
$curl = curl_init();
$clientId = "c6c70e68-7478-4fa0-9d43-1c332d40d627";
$sanboxURL="https://api.skipcash.app/api/v1/payments";
//$sanboxURL="https://skipcashtest.azurewebsites.net/api/v1/payments";
curl_setopt_array($curl, array(
    CURLOPT_URL => $sanboxURL ."/". $id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "GET",
    CURLOPT_HTTPHEADER => array(
        'Content-Type:application/json', 'Accept: application/json', 'Authorization: ' . $clientId
    ),
));

$respo = curl_exec($curl);
curl_close($curl);
$respo = json_decode($respo, true);
//dd($respo);

      
      
      /**********************End skip cash return************************************************/
      
      /*$data = array('booking_ref_id'=>$referenceId,'to_mail'=>'testdeveloper@webprojects.hexeam.in');
             Mail::send('front-end/mail-templates/admin-booking-template', $data, function($message) use ($data){
                 $message->to($data['to_mail'], 'RENT SOLUTIONS')->subject
                    ('NEW BOOKING');
                $message->from('testdeveloper@webprojects.hexeam.in','RENT SOLUTIONS - NEW BOOKING RECIEVED');
        });*/
         $bookingInfo = Booking::where('book_ref_id','=',$referenceId)->first();
   $customer=Customer::where('customer_id','=',$bookingInfo->book_cust_id)->first();
               if($customer!=NULL)
                {
                    $customer_first_name=$customer->cust_fname;
                    $customer_last_name=$customer->cust_lname;
                    $customer_full_name=$customer->fname.''.$customer->cust_lname;
                    $customer_dob=$customer->cust_dob;
                    $customer_email=$customer->email;
                    $customer_address=$bookingInfo->book_bill_cust_address_1??''.' '.$bookingInfo->book_bill_cust_address_2;
                    $customer_license_no=$customer->cust_driving_license_no;
                    $customer_license_exp_date=$customer->cust_license_issued_date;
                    $customer_passport=$customer->cust_passport_number;
                    $customer_passport_expiry="2022-11-11";//$customer->cust_passport_number;
                    //$customer_license_exp_date=$customer->cust_license_issued_date;
                    $city_qry=City::where('city_id',$bookingInfo->book_bill_cust_city)->first();
                    
                    $statusName = "Under Process";
                    $bkmodelImage = $bookingInfo->model->modelImage->model_image;
                    
                  
             
             
                    if($city_qry)
                    {
                        $city=$city_qry->city_name;
                    }
                    else
                    {
                        $city="";
                    }
                     $location_qry=City_location::where('city_loc_id',$bookingInfo->book_bill_cust_location)->first();
                    if($location_qry)
                    {
                        $location=$location_qry->location_name;
                    }
                    else
                    {
                        $location="";
                    }
                    //dd($location);
                    $country_qry=Country::where('country_id',$customer->cust_nationality)->first();
                    if($country_qry)
                    {
                        $country=$country_qry->country_name;
                        
                    }
                    
                    $client1 = new \GuzzleHttp\Client();
                if($customer->cust_qatar_id!=NULL)
                {
                    $id_no=$customer->cust_qatar_id;
                    
                }
                else
                {
                    $id_no=$customer_passport;
                    
                }
                $api = $client1->get('http://130.61.97.192:201/F_CUSTOMERS?LookupText='.$customer_first_name.'&PageSize=1&Skip=0&FC_MOBILE='.$customer->cust_mobile_number.'&FC_ID_NO='.$id_no);
                
                
                $data = $api->getBody()->getContents();
                $response = json_decode($data, true);
                if($response){
                    $respItems = $response['Items'];
                    //dd($respItems);
                }
               
               
                if(sizeof($respItems))
                {
                    $custCode = $response['Items']['0']['FC_CUST_NO'];
                    $customer->cust_code=$custCode;
                    $customer->update();
                    
                }
                else
                {
                    $custCode=null;
                    
                }
                    
                }
                else
                {
                    $customer_first_name="Not exist";
                    $customer_last_name="Not exist";
                    $customer_full_name='Not exist';
                    
                }
                $modal=Modal::where('modal_id',$bookingInfo->book_car_model)->first();
                if($modal)
                {
                    $model_number=(int)$modal->model_number;
                    $model_category=(int)$modal->modal_category;
                    $makerid=(int)$modal->makers;
                    $modelName = $modal->modal_name;
                    $makerName = $modal->maker['maker_name'];
                    
                    
                }
                else
                {
                     $model_number=0;
                    $model_category=0;
                    $makerid=0;
                    
                }
            //   dd($respo['resultObj']);  
        if($respo['resultObj']['statusId']==2)
        {
            //dd($respo['resultObj']);
            $referenceId=$respo['resultObj']['transactionId'];
            $ReferId=$referenceId;
            //dd($respo['resultObj']);
            
                
       
            
            //save notification
            $notfContent = "Your Booking with Reference Number: " . $ReferId . " is " . $statusName;
            Notification::create([
                    'customer_id' => $bookingInfo->book_cust_id,
                    'notification_title' => "Booking Status",
                    'notification_content' => $notfContent,
                    'notification_status' => 1
                ]);
            Booking::where('book_ref_id','=',$referenceId)->update([
            'book_status' =>1, //confirmed status changed upon client request. if payment is success also default status needs to be pending
             'payment_session_id'=>$respo['resultObj']['visaId']
            ]);
             $data = array('customer_fname'=>$customer_first_name,'customer_lname'=>$customer_last_name,
             'booking_ref_id'=>$referenceId,'booking_status'=>$statusName,'to_mail'=>'dipinpnambiar@gmail.com','booking_from_date'=>$bookingInfo->book_from_date,'booking_to_date'=>$bookingInfo->book_to_date,'booking_pickup_time'=>$bookingInfo->book_pickup_time,'booking_return_time'=>$bookingInfo->book_return_time,'booking_total'=>$bookingInfo->book_total_rate,
            'booking_city_name'=>$city,'booking_model_name'=>$modelName,
             'booking_maker_name'=>$makerName,'booking_model_image'=>$bkmodelImage);
            
             //send mail to customer
            
            /*Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to($data['to_mail'],'Almana Leasing')->subject
                     ('BOOKING INFORMATION');
            $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
             });
             
             //mail to reservation mail of almana
             
             Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to('info@almanaleasing.com','Almana Leasing')->subject
                     ('NEW BOOKING INFORMATION');
         $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
              });*/
            if($respo['resultObj']['custom1']=='yes')
            {
                $res=['status'=>1,'booking_number'=>$referenceId,'message'=>'Success'];
                return response($res);
            }
            return view('front-end.elements.car.booking-success',compact('referenceId','Name'));
           
            
        }
        else
        {
            $referenceId=$respo['resultObj']['transactionId'];
             Booking::where('book_ref_id','=',$referenceId)->update([
            'book_status' =>3, //payment Failed 
            'payment_session_id'=>$respo['resultObj']['visaId']
            ]);
              $data = array('customer_fname'=>$customer_first_name,'customer_lname'=>$customer_last_name,
             'booking_ref_id'=>$referenceId,'booking_status'=>$statusName,'to_mail'=>'dipinpnambiar@gmail.com','booking_from_date'=>$bookingInfo->book_from_date,'booking_to_date'=>$bookingInfo->book_to_date,'booking_pickup_time'=>$bookingInfo->book_pickup_time,'booking_return_time'=>$bookingInfo->book_return_time,'booking_total'=>$bookingInfo->book_total_rate,
            'booking_city_name'=>$city,'booking_model_name'=>$modelName,
             'booking_maker_name'=>$makerName,'booking_model_image'=>$bkmodelImage);
            
             //send mail to customer
            
           /* Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to($data['to_mail'],'Almana Leasing')->subject
                     ('BOOKING INFORMATION');
            $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
             });
             
             //mail to reservation mail of almana
             
             Mail::send('front-end/mail-templates/booking-template', $data, function($message) use ($data){
            $message->to('info@almanaleasing.com','Almana Leasing')->subject
                     ('NEW BOOKING INFORMATION');
         $message->from('reservations@almanaleasing.com','ALMANA LEASING BOOKING INFORMATION');
              });*/
             
             if($respo['resultObj']['custom1']=='yes')
             {
            
                $res=['status'=>0,'booking_number'=>$referenceId,'message'=>'Failed'];
                return response($res);
            }
            
            return view('front-end.elements.car.booking-failed',compact('referenceId','Name'));
            
            
        }
             
             
    }
    
    public function orderCancel($id)
    {
        $decrId = Crypt::decryptString($id);
        $orderId = Session::get('orderId');
        $referenceId = Session::get('booking_ref_id');
        $Name = Session::get('Name');
       
        Booking::where('book_ref_id','=',$referenceId)->update([
            'book_status' =>3, //payment Failed 
            ]);
            return view('front-end.elements.car.booking-failed',compact('referenceId','Name'));
        
    }

     public  function generateRandomString($length = 6) {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    
    public function bookAgain(Request $request)
    {
       $data = array();
      $model = array();
      $carId = $request->model_id;
      $fetchModel = Modal::where('modal_id','=',$carId)->first();
      $ModelImage = Model_image::where('model_id',$fetchModel->modal_id)->where('model_image_flag','=',0)->first();
      $modelSpec =  Model_specification::where('model_id','=',$fetchModel->modal_id)->where('is_active','=',1)->get();
      $getCont = Country::orderBy('country_id','ASC')->get();
      $fetchCity = City::orderBy('city_id','DESC')->get();
        $getTerms = Setting::where('id','=','1')->first();
        $getInfo = Setting::where('id','=','2')->first();
      $locId = $request->input('city_loc_id');
      $cityId = $request->input('city_id');
      $frmDate = $request->input('from_date');
      $toDate = $request->input('to_date');
      $pickupTime = $request->input('pickup_time');
      $returnTime = $request->input('return_time');
      $curType = $request->input('cur_type'); //default currency value of qatar riyal is set to 0
          Session::put('location',$locId);
          Session::put('city',$cityId);
          Session::put('fromdate',$frmDate);
          Session::put('todate',$toDate);
          Session::put('pickupTime',$pickupTime);
          Session::put('returnTime',$returnTime);
          Session::put('currency_type',$curType);
       $resCity =City::where('city_id','=',$cityId)->first(); //Get City
          $resLoc = City_location::where('city_loc_id','=',$locId)->first(); //Get Location
          $parseFrmDt = Helper::parseCarbon($frmDate); 
          $parseToDate = Helper::parseCarbon($toDate);
          $parsePickTime = Helper::parseCarbon($pickupTime);
          $parseRetTime = Helper::parseCarbon($returnTime);
          $diff = $parsePickTime->diffInHours($parseRetTime);
          $combinedfrom = date('Y-m-d H:i:s', strtotime("$frmDate $pickupTime"));
          $combinedto = date('Y-m-d H:i:s', strtotime("$toDate $returnTime"));
          $parsecombinefrom = Helper::parseCarbon($combinedfrom); 
          $parsecombineto = Helper::parseCarbon($combinedto); 
          $diffDays2 = $parsecombinefrom->diffInDays($parsecombineto);
          $mins            = $parseRetTime->diffInMinutes($parsePickTime, true);
          $totMins = ($mins/60);
          
          //get number of days
          if($totMins > 4 && $diff <= 12  && $diffDays2 >= 1)
          {
            $days=$parseFrmDt->diffInDays($parseToDate)+1;  
          }else{
             $days=$parseFrmDt->diffInDays($parseToDate); 
          }
          
          
          $data['From_Date'] = $frmDate;
          $data['To_date'] = $toDate;
          $data['pickup_Time'] =$pickupTime ;
          $data['return_time'] = $returnTime;
          $data['Days'] = $days;
          $data['City'] = $resCity->city_name;
          $data['city_id'] = $resCity->city_id;
          $data['Location'] = $resLoc->location_name;
          $data['location_id'] = $resLoc->city_loc_id;
          Session::put('days',$days);
          Session::put('city_name',$resCity->city_name);
          Session::put('location_name',$resLoc->location_name);
           if(!$days==0)
            {
             
                $modList= Modal::where('modal_id','=',$carId)->first();
                $car=array();
                $typoo = Helper::setType($days);
                if (in_array(4, $typoo) || in_array(5, $typoo) || in_array(6, $typoo) || in_array(7, $typoo) || in_array(8, $typoo))
                  {
                       $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }else{
                     $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }
            
                foreach ($rateType as $rateTypes) {
                    
                      $fetchrate = $rateTypes->rate; //get rate from the table //rate
                      $fetchOfferRate = Helper::checkOffer($rateTypes->model_rate_id,$modList->modal_id); //get offer rate from table
                      if($curType)
                       {
                          $fetchCurrency = Currency::where('currency_id','=',$curType)->first();
                          $data['Currency_code'] = $fetchCurrency->currency_code;
                          $curConvertRate = $fetchCurrency->currency_conversion_rate;
                          if($fetchOfferRate<$fetchrate)
                          {
                            $rate = $fetchOfferRate*$curConvertRate;
                          }else{
                            $rate = $fetchrate*$curConvertRate;
                          }
                        }

                       $data['Rate_code'] = $rateTypes->rates->rate_type_code;
                       Session::put('rate_type',$rateTypes->rates->rate_type_code);
                       $data['Total_Rates'] = Helper::showList($days,$rate);
                       $rt = Helper::showList($days,$rate);
                       $data['Total_Rate'] = $rt['totValue'];
                       $data['Rate_per_day'] = $rt['perDayRate'];
                       $data['Main_Rate']= $fetchrate;
                       $data['Offer_Rate'] = $fetchOfferRate;
                       
                     // array_push($model,$car); 
                   
                     }
             
             
              // $data['Models'] = $model;
                 
            }else{
             
              return redirect()->back()->with('status','Date Should not be less than 1');
            }

      return view('front-end.elements.car.detail',compact('carId','fetchModel','ModelImage','modelSpec','getCont','fetchCity','getTerms','getInfo'),$data);
    }
     public function bookEdit(Request $request)
    {
       $data = array();
      $model = array();
      $carId = $request->model_id;
      
      $fetchModel = Modal::where('modal_id','=',$carId)->first();
      if($fetchModel==NULL)
      {
          return redirect()->back();
      }
      $ModelImage = Model_image::where('model_id',$fetchModel->modal_id)->where('model_image_flag','=',0)->first();
      $modelSpec =  Model_specification::where('model_id','=',$fetchModel->modal_id)->where('is_active','=',1)->get();
      $getCont = Country::orderBy('country_id','ASC')->get();
      $fetchCity = City::orderBy('city_id','DESC')->get();
        $getTerms = Setting::where('id','=','1')->first();
        $getInfo = Setting::where('id','=','2')->first();
      $locId = $request->input('city_loc_id');
      $cityId = $request->input('city_id');
      $frmDate = $request->input('from_date');
      $toDate = $request->input('to_date');
      $pickupTime = $request->input('pickup_time');
      $returnTime = $request->input('return_time');
      $refNo=$request->input('reference_number');
      $curType = $request->input('cur_type'); //default currency value of qatar riyal is set to 0
          Session::put('location',$locId);
          Session::put('city',$cityId);
          Session::put('fromdate',$frmDate);
          Session::put('todate',$toDate);
          Session::put('pickupTime',$pickupTime);
          Session::put('returnTime',$returnTime);
          Session::put('currency_type',$curType);
          Session::put('ref_no',$refNo);
       $resCity =City::where('city_id','=',$cityId)->first(); //Get City
          $resLoc = City_location::where('city_loc_id','=',$locId)->first(); //Get Location
          $parseFrmDt = Helper::parseCarbon($frmDate); 
          $parseToDate = Helper::parseCarbon($toDate);
          $parsePickTime = Helper::parseCarbon($pickupTime);
          $parseRetTime = Helper::parseCarbon($returnTime);
          $diff = $parsePickTime->diffInHours($parseRetTime);
          $combinedfrom = date('Y-m-d H:i:s', strtotime("$frmDate $pickupTime"));
          $combinedto = date('Y-m-d H:i:s', strtotime("$toDate $returnTime"));
          $parsecombinefrom = Helper::parseCarbon($combinedfrom); 
          $parsecombineto = Helper::parseCarbon($combinedto); 
          $diffDays2 = $parsecombinefrom->diffInDays($parsecombineto);
          $mins            = $parseRetTime->diffInMinutes($parsePickTime, true);
          $totMins = ($mins/60);
          
          //get number of days
          if($totMins > 4 && $diff <= 12  && $diffDays2 >= 1)
          {
            $days=$parseFrmDt->diffInDays($parseToDate)+1;  
          }else{
             $days=$parseFrmDt->diffInDays($parseToDate); 
          }
          
          
          $data['From_Date'] = $frmDate;
          $data['To_date'] = $toDate;
          $data['pickup_Time'] =$pickupTime ;
          $data['return_time'] = $returnTime;
          $data['Days'] = $days;
          $data['City'] = $resCity->city_name;
          $data['city_id'] = $resCity->city_id;
          $data['Location'] = $resLoc->location_name;
          $data['location_id'] = $resLoc->city_loc_id;
          Session::put('days',$days);
          Session::put('city_name',$resCity->city_name);
          Session::put('location_name',$resLoc->location_name);
           if(!$days==0)
            {
             
                $modList= Modal::where('modal_id','=',$carId)->first();
                $car=array();
                $typoo = Helper::setType($days);
                if (in_array(4, $typoo) || in_array(5, $typoo) || in_array(6, $typoo) || in_array(7, $typoo) || in_array(8, $typoo))
                  {
                       $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }else{
                     $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }
            
                foreach ($rateType as $rateTypes) {
                    
                      $fetchrate = $rateTypes->rate; //get rate from the table //rate
                      $fetchOfferRate = Helper::checkOffer($rateTypes->model_rate_id,$modList->modal_id); //get offer rate from table
                      if($curType)
                       {
                          $fetchCurrency = Currency::where('currency_id','=',$curType)->first();
                          $data['Currency_code'] = $fetchCurrency->currency_code;
                          $curConvertRate = $fetchCurrency->currency_conversion_rate;
                          if($fetchOfferRate<$fetchrate)
                          {
                            $rate = $fetchOfferRate*$curConvertRate;
                          }else{
                            $rate = $fetchrate*$curConvertRate;
                          }
                        }

                       $data['Rate_code'] = $rateTypes->rates->rate_type_code;
                       Session::put('rate_type',$rateTypes->rates->rate_type_code);
                       $data['Total_Rates'] = Helper::showList($days,$rate);
                       $rt = Helper::showList($days,$rate);
                       $data['Total_Rate'] = $rt['totValue'];
                       $data['Rate_per_day'] = $rt['perDayRate'];
                       $data['Main_Rate']= $fetchrate;
                       $data['Offer_Rate'] = $fetchOfferRate;
                       
                     // array_push($model,$car); 
                   
                     }
             
             
              // $data['Models'] = $model;
                 
            }else{
             
              return redirect()->back()->with('status','Date Should not be less than 1');
            }
            if($data['Total_Rate'] < $request->preAmount)
            {
                return redirect()->back()->with('error','Total price less than the previous booking amount for '.$refNo);
            }

      return view('front-end.elements.car.detail-edit',compact('carId','fetchModel','ModelImage','modelSpec','getCont','fetchCity','getTerms','getInfo'),$data);
    }

    public function changeCurrency(Request $request, $id)
    {
      $cId = Crypt::decryptString($id);
      if(Session::has('fromdate')||Session::has('todate'))
      {
       $fetcCurrency = Currency::orderBy('currency_id','DESC')->get();
      $fetchCity = City::orderBy('city_id','DESC')->get();
      $vehicleType = Model_category::orderBy('model_cat_id','DESC')->get();
       $vehicleMaker = Maker::orderBy('maker_id','DESC')->get();
      $getCurrency = Currency::where('currency_id','=',$cId)->first();
      $getCode = $getCurrency->currency_code;
      Session::put('cur_type',$cId);
      Session::put('cur_code',$getCode);
      $data = array();
      $model = array();

        $locId = Session::get('location');
          $cityId = Session::get('city');
          $frmDate =Session::get('fromdate');
          $toDate = Session::get('todate');
          $pickTime = Session::get('pickupTime');
          $retnTime = Session::get('returnTime');
          $curType = Session::get('cur_type'); //default currency value of qatar riyal is set to 0
          // Session::put('location',$locId);
          // Session::put('city',$cityId);
          // Session::put('fromdate',$frmDate);
          // Session::put('todate',$toDate);
          // Session::put('pickupTime',$pickupTime);
          // Session::put('returnTime',$returnTime);
          // Session::put('currency_type',$curType);
       $resCity =City::where('city_id','=',$cityId)->first(); //Get City
          $resLoc = City_location::where('city_loc_id','=',$locId)->first(); //Get Location
          $parseFrmDt = Helper::parseCarbon($frmDate); 
          $parseToDate = Helper::parseCarbon($toDate);
          $parsePickTime = Helper::parseCarbon($pickTime);
          $parseRetTime = Helper::parseCarbon($retnTime);
          $diff = $parsePickTime->diffInHours($parseRetTime);
          $diffDays = $parseFrmDt->diffInDays($parseToDate);
          $combinedfrom = date('Y-m-d H:i:s', strtotime("$frmDate $pickupTime"));
          $combinedto = date('Y-m-d H:i:s', strtotime("$toDate $returnTime"));
          $parsecombinefrom = Helper::parseCarbon($combinedfrom); 
          $parsecombineto = Helper::parseCarbon($combinedto); 
          $diffDays2 = $parsecombinefrom->diffInDays($parsecombineto);
          $mins            = $parseRetTime->diffInMinutes($parsePickTime, true);
          $totMins = ($mins/60);
          
          //get number of days
          if($totMins > 4 && $diff <= 12  && $diffDays2 >= 1)
          {
            $days=$parseFrmDt->diffInDays($parseToDate)+1;  
          }else{
             $days=$parseFrmDt->diffInDays($parseToDate); 
          }
         
          $data['From_Date'] = $frmDate;
          $data['To_date'] = $toDate;
          $data['pickup_Time'] =Session::get('pickupTime');
          $data['return_time'] = Session::get('returnTime');
          $data['Days'] = $days;
          $data['City'] = $resCity->city_name;
          $data['city_id'] = $resCity->city_id;
          $data['Location'] = $resLoc->location_name;
          $data['location_id'] = $resLoc->city_loc_id;
          Session::put('days',$days);
          Session::put('city_name',$resCity->city_name);
          Session::put('location_name',$resLoc->location_name);
           if(!$days==0)
            {
             
                $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                        $query->select('model_id')->from('model_images');
                        })->orderBy('modals.modal_id','DESC')->get();
              
              
              $car=array();
              foreach ($modList as $modList) { 
                  $modalId = $modList->modal_id;
                  $car['Model_id'] = $modList->modal_id;
                  $car['Model_name']=$modList->modal_name;
                  $maker=Maker::where('maker_id',$modList->makers)->first();
                  $car['Maker_name']=@$maker->maker_name;
                  $car['Model_category'] = $modList->category['model_cat_name']??'';
                  //image
                  $modImage = Model_image::where('model_id',$modList->modal_id)->where('model_image_flag','=',0)->get();
                      
                      foreach($modImage as $modImages)
                      {
                      $car['Model_image'] = $modImages->model_image;
                      }
                    //specifications
                    $resSpec = Model_specification::where('model_id','=',$modList->modal_id)->where('is_active','=',1)->get();
                    $spec=array();
                  foreach ($resSpec as $resSpecs) {
                    $specification=Specification::where('spec_id',$resSpecs->spec_id)->first();
                    
                    if($specification->active_flag==1)
                    {
                        $gtspec['Spec_name'] = $resSpecs->specs['spec_name'];
                       $gtspec['Spec_Image'] = $resSpecs->specs['spec_icon'];
                       array_push($spec,$gtspec); 
                    }
                  }
                  $car['specifications'] = $spec;
                  $typoo = Helper::setType($days);
                  //rate
                  if (in_array(4, $typoo) || in_array(5, $typoo) || in_array(6, $typoo) || in_array(7, $typoo) || in_array(8, $typoo))
                  {
                       $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }else{
                     $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }
               
                   foreach ($rateType as $rateTypes) {
                    
                      $fetchrate = $rateTypes->rate; //get rate from the table //rate
                      $fetchOfferRate = Helper::checkOffer($rateTypes->model_rate_id,$modList->modal_id); //get offer rate from table

                      if($curType)
                       {
                          $fetchCurrency = Currency::where('currency_id','=',$curType)->first();
                          $car['Currency_code'] = $fetchCurrency->currency_code;
                          $curConvertRate = $fetchCurrency->currency_conversion_rate;

                          $mainOffer = $fetchOfferRate*$curConvertRate;
                          $mainRat = $fetchrate*$curConvertRate;

                          if($fetchOfferRate<$fetchrate)
                          {
                            $rate = $fetchOfferRate*$curConvertRate;
                          }else{
                            $rate = $fetchrate*$curConvertRate;
                          }
                        }

                       $car['Rate_code'] = $rateTypes->rates->rate_type_code;
                       Session::put('rate_type',$rateTypes->rates->rate_type_code);
                       $car['Total_Rates'] = Helper::showList($days,$rate);
                       $rt = Helper::showList($days,$rate);
                       $car['Total_Rate'] = $rt['totValue'];
                       Session::put('totvalue',$rt['totValue']);
                       $car['Rate_per_day'] = $rt['perDayRate'];
                       Session::put('dailyRT',$rt['perDayRate']);
                        $car['Model_Year']= $rateTypes->model_year;
                        $car['Maker']= $rateTypes->maker_id;
                       $car['Main_Rate']= $mainRat;
                       $car['Offer_Rate'] = $mainOffer;
                       
                     array_push($model,$car); 
                   
                     }
                }
             
              $data['Models'] = $model;
                 
            }else{
             
              $data['message'] = "No Cars available";
            }

      return view('front-end.elements.car.search',compact('fetcCurrency','vehicleType','parseFrmDt','vehicleMaker','data','parseToDate','fetchCity'),$data);
       }else{
      return redirect('/');
    }
    }
    
    
   
    public function sortCar(Request $request, $sort, $id=NULL)
    {
       
        if(Session::has('cur_type'))
      {
          $ads = MstAds::get();
          
      $fetcCurrency = Currency::orderBy('currency_id','DESC')->get();
      $fetchCity = City::orderBy('city_id','DESC')->get();
      //$vehicleType = Model_category::orderBy('model_cat_id','DESC')->get();
      $vehicleMaker = Maker::where('maker_name','!=','Not Defined')->orderBy('maker_id','DESC')->get();
      $data = array();
      $model = array();
      $bid=$id;
  
    //   if($id==NULL)
    //   {
          
    //     $locId = $request->input('city_loc_id');
    //       $cityId = $request->input('city_id');
    //       $frmDate = $request->input('from_date');
    //       $toDate = $request->input('to_date');
    //       $pickupTime = $request->input('pickup_time');
    //       $returnTime = $request->input('return_time');
    //       // $curType = $request->input('cur_type'); //default currency value of qatar riyal is set to 0
    //       $curType = Session::get('cur_type');
    //       // dd($curType);
    //       Session::put('location',$locId);
    //       Session::put('city',$cityId);
    //       Session::put('fromdate',$frmDate);
    //       Session::put('todate',$toDate);
    //       Session::put('pickupTime',$pickupTime);
    //       Session::put('returnTime',$returnTime);
    //       Session::put('currency_type',$curType);

    //   }else{
       $locId = Session::get('location');
          $cityId = Session::get('city');
          $frmDate =Session::get('fromdate');
          $toDate = Session::get('todate');
          $pickupTime = Session::get('pickupTime');
          $returnTime = Session::get('returnTime');
          $curType = Session::get('currency_type');
          
    //   }
      
          
          $resCity =City::where('city_id','=',$cityId)->first(); //Get City
          $resLoc = City_location::where('city_loc_id','=',1)->first(); //Get Location
          $parseFrmDt = Helper::parseCarbon($frmDate); 
          $parseToDate = Helper::parseCarbon($toDate);
          $parsePickTime = Helper::parseCarbon($pickupTime);
          $parseRetTime = Helper::parseCarbon($returnTime);
          $diff = $parsePickTime->diffInHours($parseRetTime);
          $combinedfrom = date('Y-m-d H:i:s', strtotime("$frmDate $pickupTime"));
          $combinedto = date('Y-m-d H:i:s', strtotime("$toDate $returnTime"));
          $parsecombinefrom = Helper::parseCarbon($combinedfrom); 
          $parsecombineto = Helper::parseCarbon($combinedto); 
          $diffDays2 = $parsecombinefrom->diffInDays($parsecombineto);
          $mins            = $parseRetTime->diffInMinutes($parsePickTime, true);
          $totMins = ($mins/60);
          
          //get number of days
          if($totMins > 4 && $diff <= 12  && $diffDays2 >= 1)
          {
              
                $days=$parseFrmDt->diffInDays($parseToDate)+1; 
            
          }else{
             $days=$parseFrmDt->diffInDays($parseToDate); 
          }
        //   dd($diff,$diffDays,$days);
           
        //   dd($parseFrmDt,$parsePickTime,$parseToDate,$parseRetTime,$days,$diff);
          $data['From_Date'] = $frmDate;
          $data['To_date'] = $toDate;
          $data['pickup_Time'] =$pickupTime;
          $data['return_time'] = $returnTime;
          $data['Days'] = $days;
          $data['City'] = @$resCity->city_name;
          $data['city_id'] = @$resCity->city_id;
          $data['Location'] = @$resLoc->location_name;
          $data['location_id'] = @$resLoc->city_loc_id;
          Session::put('days',$days);
          Session::put('city_name',@$resCity->city_name);
          Session::put('location_name',@$resLoc->location_name);
        
   
          if(!$days==0)
            {
              if(!$id==NULL && $id!= 13){
                  
                $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.modal_category','=',$id)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                        $query->select('model_id')->from('model_images');
                        })->orderBy('modals.modal_id','DESC')->get();
              }else{
                
                $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                        $query->select('model_id')->from('model_images');
                        })->orderBy('modals.modal_id','DESC')->get();
              }
              
             
              $car=array();
               $spec=array();
               $cat_ids=array();
              foreach ($modList as $modList) { 
                  $modalId = $modList->modal_id;
                  $car['Model_id'] = $modList->modal_id;
                  $car['Model_name']=$modList->modal_name;
                   $maker=Maker::where('maker_id',$modList->makers)->first();
                  $car['Maker_name']=@$maker->maker_name;
                  $car['Model_category'] = $modList->category['model_cat_name']??'';
                  $car['Model_available']=$modList->rdy_count;
                  if(isset($modList->category['model_cat_id']))
                  {
                      array_push($cat_ids,$modList->category['model_cat_id']);
                      
                  }
                  
                  //image
                  $modImage = Model_image::where('model_id',$modList->modal_id)->where('model_image_flag','=',0)->get();
                      
                      foreach($modImage as $modImages)
                      {
                      $car['Model_image'] = $modImages->model_image;
                      }
                    //specifications
                    $resSpec = Model_specification::where('model_id','=',$modList->modal_id)->where('is_active','=',1)->get();
                   
                  foreach ($resSpec as $resSpecs) {
                     $specification=Specification::where('spec_id',$resSpecs->spec_id)->first();
                    
                    if($specification->active_flag==1)
                    {
                        $gtspec['Spec_name'] = $resSpecs->specs['spec_name'];
                       $gtspec['Spec_Image'] = $resSpecs->specs['spec_icon'];
                       array_push($spec,$gtspec); 
                    }
                  }
                  $car['specifications'] = $spec;
                  $typoo = Helper::setType($days);
                  

                  //rate
                  if (in_array(4, $typoo) || in_array(5, $typoo) || in_array(6, $typoo) || in_array(7, $typoo) || in_array(8, $typoo))
                  {
                       $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->orderBy('model_min_rate','ASC')->get(); 
                  }else{
                     $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->orderBy('model_min_rate','ASC')->get(); 
                  }
                 
                  
                  
               
                   foreach ($rateType as $rateTypes) {
                    
                      $fetchrate = $rateTypes->rate; //get rate from the table //rate
                      $fetchOfferRate = Helper::checkOffer($rateTypes->model_rate_id,$modList->modal_id); //get offer rate from table
                      if($curType)
                       {
                          $fetchCurrency = Currency::where('currency_id','=',$curType)->first();
                          $car['Currency_code'] = $fetchCurrency->currency_code;
                          $curConvertRate = $fetchCurrency->currency_conversion_rate;
                          if($fetchOfferRate<$fetchrate)
                          {
                            $rate = $fetchOfferRate*$curConvertRate;
                          }else{
                            $rate = $fetchrate*$curConvertRate;
                          }
                       
                        }


                       $car['Rate_code'] = $rateTypes->rates->rate_type_code;
                       Session::put('rate_type',$rateTypes->rates->rate_type_code);
                       $car['Total_Rates'] = Helper::showList($days,$rate);
                       $rt = Helper::showList($days,$rate);
                       $car['Total_Rate'] = $rt['totValue'];
                       Session::put('totvalue',$rt['totValue']);
                       $car['Rate_per_day'] = $rt['perDayRate'];
                       Session::put('dailyRT',$rt['perDayRate']);
                       $car['Main_Rate']= $fetchrate;
                        $car['Model_Year']= $rateTypes->model_year;
                        $car['Maker']= $rateTypes->maker_id;
                       $car['Offer_Rate'] = $fetchOfferRate;
                       
                     array_push($model,$car); 
                   
                     }
                }
               
        
                if($sort==1)
                {
                  $model = $this->array_sort($model,'Offer_Rate', SORT_ASC);
                  Session::put('sort_type',1);
                }
                else
                {
                  $model = $this->array_sort($model,'Offer_Rate', SORT_DESC);
                  Session::put('sort_type',2);
                }
              
              
              $data['Models'] = $model;
            //  dd($data);
                 
            }else{
             
              $data['message'] = "No Cars available";
            }
            $vehicleType = Model_category::WhereIn('model_cat_id',$cat_ids)->orderBy('model_cat_id','DESC')->get();
            $pageTitle = "Car Rental Options Sorted by Price";
            $pageDescription = "Find the most competitive car rental prices in Doha with Al Mana Leasing's easy-to-use search and sort feature.";


            return view('front-end.elements.car.search',compact('id','bid','fetcCurrency','vehicleType','vehicleMaker','parseFrmDt','data','parseToDate','fetchCity','ads','pageTitle','pageDescription'),$data);
          }else{
            return redirect('/');
          }
    }

  public function webCarsist(Request $request)
  {
    //get current date and time
          $curDt = Carbon::now();
          $frmdt  = $curDt->format('m/d/Y'); //current from date
          $toDat = $curDt->addDays(1);
          $formatToDat = $toDat->format('m/d/Y'); //to date

          $vehcleType = $request->input('vehicle_type');
          $brandType = $request->input('brand_type');
          $frmDate = $request->input('from_date');
          $toDate = $request->input('to_date');
          $pickupTime = "10:00";
          $returnTime = "10:00";
          $curType = Session::get('currency_type'); 

          $parseFrmDt = Helper::parseCarbon($frmDate); //parse from date to carbon format
          $parseToDate = Helper::parseCarbon($toDate); //parse to date to carbon format
          $parsePickTime = Helper::parseCarbon($pickupTime); //parse picktime to carbon format
          $parseRetTime = Helper::parseCarbon($returnTime); //parse returntime to carbon format
          $diff = $parsePickTime->diffInHours($parseRetTime); //find hour difference based on time
          $diffDays = $parseFrmDt->diffInDays($parseToDate); //find days difference based on date
          //calculate the difference w.r.t to time
          $combinedfrom = date('Y-m-d H:i:s', strtotime("$frmDate $pickupTime"));
          $combinedto = date('Y-m-d H:i:s', strtotime("$toDate $returnTime"));
          $parsecombinefrom = Helper::parseCarbon($combinedfrom); 
          $parsecombineto = Helper::parseCarbon($combinedto); 
          $diffDays2 = $parsecombinefrom->diffInDays($parsecombineto);
          $mins            = $parseRetTime->diffInMinutes($parsePickTime, true);
          $totMins = ($mins/60);
          
          //get number of days
          if($totMins > 4 && $diff <= 12  && $diffDays2 >= 1)
          {
                $days=$parseFrmDt->diffInDays($parseToDate)+1; 
            
          }else{
             $days=$parseFrmDt->diffInDays($parseToDate); 
          }

          $data['From_Date'] = $frmDate;
          $data['To_date'] = $toDate;
          $data['pickup_Time'] =$pickupTime;
          $data['return_time'] = $returnTime;
          $data['Days'] = $days;
          $data['City'] = $resCity->city_name;
          $data['city_id'] = $resCity->city_id;
          $data['Location'] = $resLoc->location_name;
          $data['location_id'] = $resLoc->city_loc_id;
          Session::put('days',$days);
          Session::put('city_name',$resCity->city_name);
          Session::put('location_name',$resLoc->location_name);
   
          if(!$days==0)
            {
                
                $modList= Modal::where('modals.modal_name','!=',NULL)->where('modals.active_flag','=',1)->whereIn('modal_id',function($query) {
                        $query->select('model_id')->from('model_images');
                        })->orderBy('modals.modal_id','DESC')->get();
             
             
              $car=array();
              $spec=array();
              foreach ($modList as $modList) { 
                  $modalId = $modList->modal_id;
                  $car['Model_id'] = $modList->modal_id;
                  $car['Model_name']=$modList->modal_name;
                   $maker=Maker::where('maker_id',$modList->makers)->first();
                  $car['Maker_name']=@$maker->maker_name;
                  $car['Model_category'] = $modList->category['model_cat_name']??'';
                  //image
                  $modImage = Model_image::where('model_id',$modList->modal_id)->where('model_image_flag','=',0)->get();
                      
                      foreach($modImage as $modImages)
                      {
                      $car['Model_image'] = $modImages->model_image;
                      }
                  //specifications
                  $resSpec = Model_specification::where('model_id','=',$modList->modal_id)->where('is_active','=',1)->get();
                  foreach ($resSpec as $resSpecs) {
                     $specification=Specification::where('spec_id',$resSpecs->spec_id)->first();
                    
                    if($specification->active_flag==1)
                    {
                        $gtspec['Spec_name'] = $resSpecs->specs['spec_name'];
                       $gtspec['Spec_Image'] = $resSpecs->specs['spec_icon'];
                       array_push($spec,$gtspec); 
                    }
                  }
                  $car['specifications'] = $spec;
                  $typoo = Helper::setType($days);
                 
                  //rate
                  if (in_array(4, $typoo) || in_array(5, $typoo) || in_array(6, $typoo) || in_array(7, $typoo) || in_array(8, $typoo))
                  {
                       $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }else{
                     $rateType = Mode_rate::where('model_id','=',$modList->model_number)->where('maker_id','=',$modList->makers)->where('rate_type_id','=',$typoo)->get(); 
                  }
                  
                   foreach ($rateType as $rateTypes) {
                    
                      $fetchrate = $rateTypes->rate; //get rate from the table //rate
                      $fetchOfferRate = Helper::checkOffer($rateTypes->model_rate_id,$modList->modal_id); //get offer rate from table
                      if($curType)
                       {
                          $fetchCurrency = Currency::where('currency_id','=',$curType)->first();
                          $car['Currency_code'] = $fetchCurrency->currency_code;
                          $curConvertRate = $fetchCurrency->currency_conversion_rate;
                          if($fetchOfferRate<$fetchrate)
                          {
                            $rate = $fetchOfferRate*$curConvertRate;
                          }else{
                            $rate = $fetchrate*$curConvertRate;
                          }
                       
                        }

                       $car['Rate_code'] = $rateTypes->rates->rate_type_code;
                       Session::put('rate_type',$rateTypes->rates->rate_type_code);
                       $car['Total_Rates'] = Helper::showList($days,$rate);
                       $rt = Helper::showList($days,$rate);
                       $car['Total_Rate'] = $rt['totValue'];
                       Session::put('totvalue',$rt['totValue']);
                       $car['Rate_per_day'] = $rt['perDayRate'];
                       Session::put('dailyRT',$rt['perDayRate']);
                       $car['Main_Rate']= $fetchrate;
                        $car['Model_Year']= $rateTypes->model_year;
                        $car['Maker']= $rateTypes->maker_id;
                       
                       //$maker=Maker::where('maker_name','!=','Not Defined')->where('maker_id',$rateTypes->maker_id)->first();
                        //$car['maker_name']=$maker->maker_name;
                       $car['Offer_Rate'] = $fetchOfferRate;
                       
                     array_push($model,$car); 
                   
                     }
                }
             
              $data['Models'] = $model;
              //dd($model);
            
            }else{
             
              $data['message'] = "No Cars available";
            }
  }
  function array_sort($array, $on, $order=SORT_ASC){

    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

































}