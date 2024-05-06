<?php

namespace App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mst_career;
use App\Models\Sys_job_category;
use App\Models\Sys_job_type;
use App\Models\City;
use App\Models\Mst_page_data;
use App\Models\Trn_career_enquiry;
use Validator;
use Crypt;
use Auth;
use Illuminate\Support\Str;
use Image;

class CareerController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function listJobs(Request $request)
    {
    	$pageTitle = "Jobs";
    	$fetchDetail = Mst_career::orderBy('career_id','DESC')->get();
    	return view('admin.elements.career.list',compact('fetchDetail','pageTitle'));
    }
    
    public function addJob()
    {
    	$pageTitle = "Add New Job";
    	$fetchJobCat = Sys_job_category::orderBy('job_category_id','DESC')->get();
    	$fetchJobType = Sys_job_type::orderBy('job_type_id','DESC')->get();
    	$fetchCity = City::orderBy('city_name','ASC')->get();
    	return view('admin.elements.career.add',compact('pageTitle','fetchJobType','fetchJobCat','fetchCity'));
    }

    
    public function saveJob(Request $request, Mst_career $career)
    {
    	$validator = Validator::make($request->all(), [   
                'career_title' => 'required',
                'arabic_career_title' => 'required',
                'job_category_id' => 'required',
                'job_type_id' => 'required',
                'job_company_name' => 'required',
                'arabic_job_company_name' => 'required',
                'job_salary_range' => 'required',
                'job_location' => 'required',
                'job_description' => 'required|max:400',
                'arabic_job_description' => 'required|max:400',
                'job_icon' => 'required|mimes:png,jpg,jpeg'
        ]);
        if(!$validator->fails())
            {
            	$career->career_title = $request->career_title;
                //$career->ar_page_banner_title = $request->arabic_career_title;
                
                $career->ar_career_title = $request->arabic_career_title;
            	$career->job_category_id =  $request->job_category_id;
            	$career->job_type_id = $request->job_type_id;
            	$career->job_company_name =$request->job_company_name;
                $career->ar_job_company_name =$request->arabic_job_company_name;
            	$career->job_salary_range =  $request->job_salary_range;
            	$career->job_location = $request->job_location;
            	$career->job_description = $request->job_description;
                $career->ar_job_description = $request->arabic_job_description;



            	if ($request->hasFile('job_icon'))
                    {
                        $validator = Validator::make($request->all(), [   
                            'job_icon' => 'required|mimes:png,jpg,jpeg|max:1024',
                        ]);
                        if($validator->fails())
                        {
                            return redirect()->back()->withErrors($validator->errors())->withInput();
                        }
                                $photo = $request->file('job_icon'); 
                                $storyimagename = time() . '.' . $photo->getClientOriginalExtension();
                                $destinationPath = 'assets/uploads/career';
                                $thumb_img = Image::make($photo->getRealPath());
                                $thumb_img->save($destinationPath . '/' .$storyimagename, 80);
                                $career->job_icon = $storyimagename;
                    }
              
                $career->save();
                $lang_file_ar = file_get_contents(resource_path('lang/'.'ar'.'.json'));
                $far=json_decode($lang_file_ar,true);
                $data_ar = [
                    $career->career_title  => $career->ar_career_title,
                    $career->job_company_name => $career->ar_job_company_name,
                    $career->job_description => $career->ar_job_description
                
                ];
                $result_ar = array_merge($far,$data_ar);
                file_put_contents(resource_path('lang/'.'ar'.'.json'),json_encode($result_ar,JSON_PRETTY_PRINT));
                return redirect('admin/jobs')->with('status','Added New Job');
            }else{
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
    }
    
    public function editJob($id, Request $request)
    {
        $decrId = Crypt::decryptString($id);
        $pageTitle = "Edit Job";
        $fetchDetail = Mst_career::where('career_id','=',$decrId)->first();
        $fetchJobCat = Sys_job_category::orderBy('job_category_id','DESC')->get();
    	$fetchJobType = Sys_job_type::orderBy('job_type_id','DESC')->get();
    	$fetchCity = City::orderBy('city_name','ASC')->get();
        return view('admin.elements.career.edit',compact('pageTitle','fetchDetail','fetchJobCat','fetchJobType','fetchCity'));
    }

    
    public function updateJob(Request $request)
    {
        $getId = $request->career_id;
        $mstCareer = Mst_career::Find($getId);

                $mstCareer->career_title = $request->career_title;
                $mstCareer->ar_career_title = $request->arabic_career_title;
            	$mstCareer->job_category_id =  $request->job_category_id;
            	$mstCareer->job_type_id = $request->job_type_id;
            	$mstCareer->job_company_name =$request->job_company_name;
                $mstCareer->ar_job_company_name =$request->arabic_job_company_name;
            	$mstCareer->job_salary_range =  $request->job_salary_range;
            	$mstCareer->job_location = $request->job_location;
            	$mstCareer->job_description = $request->job_description;
                $mstCareer->ar_job_description = $request->arabic_job_description;

                if ($request->hasFile('job_icon'))
                {
                    $validator = Validator::make($request->all(), [   
                        'job_icon' => 'required|mimes:png,jpg,jpeg|max:1024',
                    ]);
                    if($validator->fails())
                    {
                        return redirect()->back()->withErrors($validator->errors())->withInput();
                    }
	                if ($request->file('job_icon')->isValid()) 
	                    {
	                        $catimage = Mst_career::where('career_id','=',$getId)->first();
	                        $proimg=$catimage->job_icon;  

	                            
	                                if($proimg!="")
	                                    {
	                                    	$path =  "assets/uploads/career/".$proimg;

	                                    	if(file_exists($path))
	                                    	{
	                                    		unlink('assets/uploads/career/'.$proimg);
	                                    	}
	                                    }
	                                    
	                                    $photo=$request->file('job_icon');
	                                    $certLogoImage = time() . '.' . $photo->getClientOriginalExtension();
	                                    $destinationPath = 'assets/uploads/career';
	                                    $thumb_img = Image::make($photo->getRealPath());
	                                    $thumb_img->save($destinationPath . '/' .$certLogoImage);
	                                    $mstCareer->job_icon = $certLogoImage; 

	                    }
            	}

            	
            	
        $mstCareer->update();
        $lang_file_ar = file_get_contents(resource_path('lang/'.'ar'.'.json'));
        $far=json_decode($lang_file_ar,true);
        $data_ar = [
            $mstCareer->career_title  => $mstCareer->ar_career_title,
            $mstCareer->job_company_name => $mstCareer->ar_job_company_name,
            $mstCareer->job_description => $mstCareer->ar_job_description
        
        ];
        $result_ar = array_merge($far,$data_ar);
        file_put_contents(resource_path('lang/'.'ar'.'.json'),json_encode($result_ar,JSON_PRETTY_PRINT));
        return redirect('admin/jobs')->with('status','Job Updated');
    }

    
    public function deleteJob($id)
    {
        $decrId = Crypt::decryptString($id);
        $fetchContent = Mst_career::Find($decrId);
        $careerJobIcon = $fetchContent->job_icon;
       
         if($careerJobIcon!="")
	        {
	            $path =  "assets/uploads/career/".$careerJobIcon;

	                if(file_exists($path))
	                    {
	                        unlink('assets/uploads/career/'.$careerJobIcon);
	                    }
	        }
        
        Mst_career::where('career_id','=',$decrId)->delete();
        return back()->with('status','Job Deleted');
    }

    
    public function listCategory(Request $request)
    {
        $pageTitle = "Job Categories";
        $fetchDetail = Sys_job_category::orderBy('job_category_id','DESC')->get();
        return view('admin.elements.career.category.list',compact('fetchDetail','pageTitle'));
    }

    
    public function addCategory()
    {
        $pageTitle = "Add New Job Category";
        return view('admin.elements.career.category.add',compact('pageTitle'));
    }

    
    public function saveCategory(Request $request, Sys_job_category $job_category)
    {
        $validator = Validator::make($request->all(), [   
                'category_title' => 'required|unique:sys_job_categories',
                
        ]);
        if(!$validator->fails())
            {
                $job_category->category_title = $request->category_title;
                $job_category->category_title_slug =  Str::slug($request->category_title);
                $job_category->save();
                return redirect('admin/job-category')->with('status','Added New Job Category');
            }else{
                return redirect()->back()->withErrors($validator->errors())->withInput();
            }
    }

    
    public function editJobCategory($id, Request $request)
    {
        $decrId = Crypt::decryptString($id);
        $pageTitle = "Edit Job Category";
        $fetchDetail = Sys_job_category::where('job_category_id','=',$decrId)->first();
        return view('admin.elements.career.category.edit',compact('pageTitle','fetchDetail'));
    }

    
    public function updateCategory(Request $request)
    {
        $getId = $request->job_category_id;
        $request->validate([
            'category_title' => 'required|unique:sys_job_categories,category_title,'.$getId.',job_category_id',
        ]);  
        $job_category = Sys_job_category::Find($getId);

                $job_category->category_title = $request->category_title;
                $job_category->category_title_slug =  Str::slug($request->category_title);
                $job_category->update();

              
        return redirect('admin/job-category')->with('status','Job Category Updated');
    }

    
    public function deleteCategory($id)
    {
        $decrId = Crypt::decryptString($id);
        Sys_job_category::where('job_category_id','=',$decrId)->delete();
        return back()->with('status','Job Category Deleted');
    }

    
    public function fetchBanner()
    {
        try{
        $fetchDetail = Mst_page_data::where('page_name','=','career')->first();
        $pageTitle = "Jobs Banner";
        return view('admin.elements.career.banner',compact('pageTitle','fetchDetail'));
        }
        catch (\Exception $e) {
        
            return Redirect::back()->withErrors(['Something went wrong',$e->getMessage()]);
           
         }catch (\Throwable $e) {
           
            return Redirect::back()->withErrors(['Something went wrong',$e->getMessage()]);
    
         }

    }

    public function listEnquiry()
    {
        try{
        $pageTitle = "Career Enquiry List";
        $fetchEnquiryList = Trn_career_enquiry::orderBy('career_enquiry_id','DESC')->get();
        return view('admin.elements.career.enquiry.list',compact('pageTitle','fetchEnquiryList'));
        }
        catch (\Exception $e) {
        
            return Redirect::back()->withErrors(['Something went wrong',$e->getMessage()]);
           
         }catch (\Throwable $e) {
           
            return Redirect::back()->withErrors(['Something went wrong',$e->getMessage()]);
    
         }

    }

    public function deleteEnquiry($id)
    {
        
        try
        {
            $decrId = Crypt::decryptString($id);
        Trn_career_enquiry::where('career_enquiry_id','=',$decrId)->delete();
        return back()->with('status','Enquiry Deleted');
        }
        catch (\Exception $e) {
        
            return Redirect::back()->withErrors(['Something went wrong',$e->getMessage()]);
           
         }catch (\Throwable $e) {
           
            return Redirect::back()->withErrors(['Something went wrong',$e->getMessage()]);
    
         }

    }










}