@extends('admin.layouts.app')
@section('content')
<div class="row" style="min-height: 70vh;">
   <div class="col-md-12">
      <div class="card">
         <div class="card-header">
            <h3 class="mb-0 card-title">{{ $pageTitle }}</h3>
         </div>
         @if (count($errors) > 0)
         <div class="alert alert-danger" id="err_msg">
            <ul>
               @foreach ($errors->all() as $error)
               <li>{{ $error }}<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></li>
               @endforeach
            </ul>
         </div>
         @endif
         @if(session('status'))
                     <div class="alert alert-success" id="err_msg">
                        <p>{{session('status')}}<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></p>
                     </div>
                     @endif 
         <form action="{{route('admin.job-category.save')}}" method="POST"
            enctype="multipart/form-data">
            @csrf
            <div class="card-body">
               <div class="row">

                  <div class="col-md-6">
                     <div class="form-group">
                        <label class="form-label">{{ __('Category Title') }}</label>
                           <input type="text" name="category_title" required="required" class="form-control">
                     </div>
                  </div>
                  
                  
                
                  
                  <div class="col-md-12">
                     <div class="form-group">
                        <center>
                        <button type="submit" class="btn btn-raised btn-primary">
                        <i class="fa fa-check-square-o"></i> Add</button>
                        <button type="reset" class="btn btn-raised btn-success">
                        Reset</button>
                        <a class="btn btn-danger" href="{{route('admin.job-category.list')}}">Cancel</a>
                        </center>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         
      </form>
   </div>
</div>
</div>
@endsection