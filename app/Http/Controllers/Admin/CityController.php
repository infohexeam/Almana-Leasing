<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
use App\Models\City_location;
use App\Models\Customer;
use Validator;
use Crypt;
use Auth;

class CityController extends Controller
{
    public function index()
    {
        if (Auth::check())
        {   
        	$pageTitle = "City";
        	$cityDetail = City::orderBy('city_id','DESC')->get();
        	return view('admin.elements.city.index',compact('pageTitle','cityDetail'));
        }else{
            return redirect('/login');
        }
    }

    public function add(Request $request)
    {
        if (Auth::check())
        { 
        	$pageTitle = "Add City";
        	return view('admin.elements.city.add',compact('pageTitle'));
        }else{
            return redirect('/login');
        }
    }

    public function save(Request $request, City $city)
    {
        // dd($request->ar_city_name);
        if (Auth::check())
        { 
        	$validator = Validator::make($request->all(), [   
                'city_name' => 'required|unique:cities|max:50',
                
            ],
            [ 	'city_name.unique' => "City Name already taken",
            	'city_name.required' => "City Name is required",
            	'city_name.max' => "City Name Should not Exceed Maxlength 50",
                
            ]);
            if(!$validator->fails())
            {
                $city->country_id  = $request->country_id;
                $city->city_name = $request->city_name;
                $city->ar_city_name = $request->ar_city_name;
                $city->save();
                // // $data= $request->except('_token');
                // $currDetail = City::firstOrCreate($data);
                $lang_file_en = file_get_contents(resource_path('lang/'.'en'.'.json'));
                $lang_file_ar = file_get_contents(resource_path('lang/'.'ar'.'.json'));
                $fen=json_decode($lang_file_en,true);
                $far=json_decode($lang_file_ar,true);
                $data = [
                    $city->city_name  => $city->ar_city_name
                ];
                
                $result_ar = array_merge($far,$data);
                file_put_contents(resource_path('lang/'.'ar'.'.json'),json_encode($result_ar,JSON_PRETTY_PRINT));
                return redirect('/admin/city')->with('status','City added Successfully');
            }else{
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
        }else{
            return redirect('/login');
        }
    }

    public function edit($id,Request $request)
    {
        if (Auth::check())
        {
        	$curId = Crypt::decryptString($id);
        	$pageTitle = "Edit City";
        	$resUpdate = City::where('city_id','=',$curId)->first();
        	return view('admin.elements.city.edit',compact('pageTitle','curId','resUpdate'));
        }else{
            return redirect('/login');
        }
    }

    public function update(Request $request, City $city)
    {
        
    	$curId = $request->input('city_id');
    	$validator = Validator::make($request->all(), [   
            'city_name' => 'unique:cities,city_name,'.$curId.',city_id|max:50'
        ]);
        if(!$validator->fails())
            {
            	$city = City::Find($curId);
            	$city->city_name = $request->input('city_name');
                $city->ar_city_name = $request->ar_city_name;
            	$city->update();
            	return redirect('/admin/city')->with('status','City Updated');
            }else{
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
    	
    }

    public function delete($id)
    {
        if (Auth::check())
        {
        	$decryptId = Crypt::decryptString($id);
        	$checkExistnce = Customer::where('cust_city',$decryptId)->get();
        	if(!$checkExistnce->isEmpty())
    	    {
    	        return back()->with('status','Unable to delete. City Linked with Customers.');
    	    }else{
    	        City::where('city_id',$decryptId)->delete();
        	    return back()->with('status','Deleted City');
    	    }
        	
        }else{
            return redirect('/login');
        }
    }
    
    //location
    public function locindex()
    {
        if (Auth::check())
        {   
        	$pageTitle = "Location";
        	$locDetail = City_location::orderBy('city_loc_id','DESC')->get();
        	return view('admin.elements.location.index',compact('pageTitle','locDetail'));
        }else{
            return redirect('/login');
        }
    }

    public function locadd(Request $request)
    {
        if (Auth::check())
        { 
        	$pageTitle = "Add Location";
        	$city = City::orderBy('city_name','ASC')->get();
        	return view('admin.elements.location.add',compact('pageTitle','city'));
        }else{
            return redirect('/login');
        }
    }

    public function locsave(Request $request, City_location $location)
    {
        // dd($request->ar_location_name);
        if (Auth::check())
        { 
        	$validator = Validator::make($request->all(), [   
                'location_name' => 'required|unique:city_locations|max:150',
                'ar_location_name' => 'required|max:150',
                'city_id' => 'required',
                
            ],
            [ 	'location_name.unique' => "Location Name already taken",
            	'location_name.required' =>"Location Name is required",
                'location_name.max' => "Location Name City Name Should not Exceed Maxlength 50",
                'ar_location_name.required' => "Location Name (Arabic) is required",
                'city_id.required'=>"City Field is required"
            ]);
            if(!$validator->fails())
            {
                $location->city_id = $request->city_id;
                $location->location_name = $request->location_name;
                $location->ar_location_name  = $request->ar_location_name;
                $location->save();
                $lang_file_en = file_get_contents(resource_path('lang/'.'en'.'.json'));
                $lang_file_ar = file_get_contents(resource_path('lang/'.'ar'.'.json'));
                $fen=json_decode($lang_file_en,true);
                $far=json_decode($lang_file_ar,true);
                $data_ar = [
                    $location->location_name  => $location->ar_location_name
                ];
                $data_en = [
                    $location->location_name  => $location->location_name
                ];
                $result_en = array_merge($fen,$data_en);
                $result_ar = array_merge($far,$data_ar);
                file_put_contents(resource_path('lang/'.'en'.'.json'),json_encode($result_en,JSON_PRETTY_PRINT));
                file_put_contents(resource_path('lang/'.'ar'.'.json'),json_encode($result_ar,JSON_PRETTY_PRINT));
                return redirect('/admin/location')->with('status','Location added Successfully');
            }else{
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
        }else{
            return redirect('/login');
        }
    }

    public function locedit($id, Request $request)
    {
        if (Auth::check())
        {
        	$curId = Crypt::decryptString($id);
        	$pageTitle = "Edit Location";
        	$resUpdate = City_location::where('city_loc_id','=',$curId)->first();
        	$city = City::orderBy('city_name','ASC')->get();
        	return view('admin.elements.location.edit',compact('pageTitle','curId','resUpdate','city'));
        }else{
            return redirect('/login');
        }
    }

    public function locupdate(Request $request, City_location $city_location)
    {
        
    	$curId = $request->input('city_loc_id');
    	$validator = Validator::make($request->all(), [   
            'location_name' => 'unique:city_locations,location_name,'.$curId.',city_loc_id|max:150',
            'city_id'=>"required",
        ],
        ['city_id.required'=>'City Field is required"']
    );
        if(!$validator->fails())
            {
            	$city_location = City_location::Find($curId);
            	$city_location->location_name = $request->input('location_name');
                $city_location->ar_location_name = $request->ar_location_name;
            	$city_location->city_id = $request->input('city_id');
            	$city_location->save();
            	return redirect('/admin/location')->with('status','Location Updated');
            }else{
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
    	
    }

    public function locdelete($id)
    {
        if (Auth::check())
        {
        	$decryptId = Crypt::decryptString($id);
        	$checkExistnce = Customer::where('cust_state',$decryptId)->get();
        	if(!$checkExistnce->isEmpty())
    	    {
    	        return back()->with('status','Unable to delete. Location Linked with Customers.');
    	    }else{
    	        City_location::where('city_loc_id',$decryptId)->delete();
        	    return back()->with('status','Deleted Location');
    	    }
    	    
        	
        }else{
            return redirect('/login');
        }
    }
}
