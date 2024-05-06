<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\User;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Modal;
use App\Models\Setting;
use App\Models\Status;
use Validator;
use DB;
use Carbon;
use Illuminate\Support\Facades\Hash;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
     public function dash()
    {
        $pageTitle = "Dashboard";
         $dtToday = Carbon\Carbon::now();
        $bookToday = Booking::where('created_at','$dtToday')->count(); 
        // $users = Booking::select(\DB::raw("COUNT(*) as count"))
        //             ->whereYear('created_at', date('Y'))
        //             ->groupBy(\DB::raw("Month(created_at)"))
        //             ->pluck('count');
        $users = Booking::select(\DB::raw("COUNT(*) as count"),DB::raw("Month(created_at) as months"))
                    ->whereYear('created_at', date('Y'))
                    ->groupBy(\DB::raw("Month(created_at)"))
                    ->get()->toArray();
        $bkDetail = Booking::orderBy('book_id','DESC')->limit(6)->get();
        $bkStatus = Status::orderBy('status_id','ASC')->get();
        $custmList = Customer::orderBy('customer_id','ASC')->get();
        $bookStatus = Status::orderBy('status_id','ASC')->get();
        $bookModel = Modal::orderBy('modal_id','ASC')->get();

        $return = [];
         for($i=1; $i<=12; $i++)
            {
                $months = array_column($users,'months');
                $key = array_search($i, $months);
                if($key !== false)
                {
                $return[] = $users[$key]['count'];
                }else {
                $return[] = 0;
                }
            }
        return view('admin.dashboard',compact('bookToday','dtToday','return','pageTitle','bkDetail','bkStatus','custmList','bookStatus','bookModel'));
    }

    public function profile()
    {
        if (Auth::check())
        {
            $pageTitle = "Profile Change Password";
            return view('admin.elements.profile.index',compact('pageTitle'));

        }else{
            return redirect('/login');
        }
    }

    public function chart()
    {

//         $users = Booking::select(DB::raw("COUNT(*) as count"))
// ->orderBy("created_at")
// ->groupBy(DB::raw("year(created_at)"))
// ->get()->toArray();
// $users = array_column($users, 'count');


        $users = Booking::select(\DB::raw("COUNT(*) as count"),DB::raw("Month(created_at) as months"))
                    ->whereYear('created_at', date('Y'))
                    ->groupBy(\DB::raw("Month(created_at)"))
                    ->get()->toArray();

        $return = [];
      //  // for($i=1;$i<=12;$i++)
        // {
        //     $key = array_search((string)$i, array_column($users, 'months'));
        //      echo $key."string";
        //         exit();
        //     if ($key) {
        //         $return[] = $users[$key]['count'];
        //     }else{
        //         $return[] = 0;
        //     }
      //  // }

        for($i=1; $i<=12; $i++)
            {
                $months = array_column($users,'months');
                $key = array_search($i, $months);
                if($key !== false)
                {
                $return[] = $users[$key]['count'];
                }else {
                $return[] = 0;
                }
            }
        return view('chart',compact('return'));
    }

    public function change_password(Request $request)
    {
        $usrId = $request->input('user_id');
        $current_password = $request->input('current_password');
        $passCheck = User::where('id','=',$usrId)->first();
        $oldPass = $passCheck->password;
        $newPass = $request->input('password');
        
        $validator = Validator::make($request->all(), [   
            
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            
        ]);

        if(!$validator->fails())
        {
            if (Hash::check($current_password,$oldPass)) //if current password and old pass is equal
                {
            
                if (!Hash::check($newPass,$oldPass)) //if both password are not same then update
                    {

                       User::where('id','=',$usrId)->update([
                        'password' => Hash::make($request->input('password')),
                        ]);
                        $request->session()->invalidate();

                            $request->session()->regenerateToken();
                            return redirect('/login')->with('status','Please login again');
            }else{
                return redirect()->back()->with('status-error','Your new password cannot be same as your current password');
            } 
            }else{
                return redirect()->back()->with('status-error','Invalid Current password ');
            } 
            
        }else{
            return redirect()->back()->withInput()->withErrors($validator->errors());
        }
    }

    public function getTerms(Request $request)
    {
        if(Auth::check())
        {
            $pageTitle = "Terms and Conditions";
            $fetchTerms = Setting::where('id','=','1')->first();
            return view('admin.elements.settings.terms',compact('fetchTerms','pageTitle'));
        }else{
            return redirect('/login');
        }
    }

    public function saveTerms(Request $request)
    {
        if (Auth::check()) {
            Setting::where('id','=','1')->update([
                'st_description' => $request->input('st_description'),
                'ar_st_description' => $request->input('ar_st_description'),
                'st_description_line_2' => $request->input('st_description_line_2'),
                'ar_st_description_line_2' => $request->input('ar_st_description_line_2')

            ]);

              //localisation
             
              $lang_file_en = file_get_contents(resource_path('lang/'.'en'.'.json'));
              $lang_file_ar = file_get_contents(resource_path('lang/'.'ar'.'.json'));
              $fen=json_decode($lang_file_en,true);
              $far=json_decode($lang_file_ar,true);
              $data_ar = [
                   'st_description' => $request->input('ar_st_description'),
                  'st_description_line_2' => $request->input('ar_st_description_line_2'),


              ];
              $data_en = [
                'st_description' => $request->input('st_description'),
                'st_description_line_2' => $request->input('st_description_line_2'),
               


              ];
              $result_en = array_merge($fen,$data_en);
              $result_ar = array_merge($far,$data_ar);
              file_put_contents(resource_path('lang/'.'en'.'.json'),json_encode($result_en,JSON_PRETTY_PRINT));
              file_put_contents(resource_path('lang/'.'ar'.'.json'),json_encode($result_ar,JSON_PRETTY_PRINT));
              

            return back()->with('status','Terms and conditions updated');
        }else{
            return redirect('/login');
        }
    }

    public function getAdditionalInfo(Request $request)
    {
        if (Auth::check()) {
            $pageTitle = "Additional Information";
            $fetchInfo = Setting::where('id','=','2')->first();
            return view('admin.elements.settings.additional-info',compact('fetchInfo','pageTitle'));
        }else{
            return redirect('/login');
        }
    }

    public function saveAdditionalInfo(Request $request)
    {
        if (Auth::check()) {
            Setting::where('id','=','2')->update([
                'st_description' => $request->input('st_description'),
                'ar_st_description' => $request->input('ar_st_description'),
            ]);
             //localisation
             
             $lang_file_en = file_get_contents(resource_path('lang/'.'en'.'.json'));
             $lang_file_ar = file_get_contents(resource_path('lang/'.'ar'.'.json'));
             $fen=json_decode($lang_file_en,true);
             $far=json_decode($lang_file_ar,true);
             $data_ar = [
                  'st_description' => $request->input('ar_st_description'),
                


             ];
             $data_en = [
               'st_description' => $request->input('st_description'),
              
               


             ];
             $result_en = array_merge($fen,$data_en);
             $result_ar = array_merge($far,$data_ar);
             file_put_contents(resource_path('lang/'.'en'.'.json'),json_encode($result_en,JSON_PRETTY_PRINT));
             file_put_contents(resource_path('lang/'.'ar'.'.json'),json_encode($result_ar,JSON_PRETTY_PRINT));
            return back()->with('status','Additional Information Updated');
        }else{
            return redirect('/login');
        }
    }
}
