@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$course->name}}</h1>
      </div>
    </div>
  </div>

<div class="bg-image">
    <div class="">
        <div class="content content-full row">
            <div class="col-sm-4" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
              <div class="py-5">
                <div class="block-content block-content-full">
                    <i class="fa fa-fw fa-book fa-2xl fa-haven-size text-large"></i>
                </div>
              </div>
            </div>
            <div class="col-sm-7" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
              <div class="py-5">
                <p><strong>General Information</strong></p>
               <div class="table-responsive">
                  <table class="table table-bordered ">
                      <thead>

                      </thead>
                      <tbody>
                          <tr>
                              <td>
                                  Name
                              </td>
                              <td>
                                  {{$course->name}}, {{$course->duration}}  days
                              </td>
                          </tr>
                          <tr>
                            <td>
                                Class
                            </td>
                            <td>
                                {{$course->class}}
                            </td>
                        </tr>
                          <tr>
                              <td>
                                  Duration
                              </td>
                              <td>
                                  {{$course->duration}} days
                              </td>
                          </tr>
                          <tr>
                              <td>
                                  Fees
                              </td>
                              <td>
                                  K{{number_format($course->price, 2)}}
                              </td>
                          </tr>
                      </tbody>
                  </table>
                  </div>
              </div>
            </div>
       </div>
    </div>
</div>
@endsection
