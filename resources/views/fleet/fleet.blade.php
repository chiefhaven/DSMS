@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Fleet</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <a class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-block-addfleet" href="javascript:void(0)">
              <span class="nav-main-link-name">Add Fleet</span>
            </a>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block-content">
          @if(Session::has('message'))
            <div class="alert alert-success">
              {{Session::get('message')}}
            </div>
          @endif

          @if ($errors->any())
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
          @endif

      <div class="row">
        @forelse ($fleet as $fleet)
        <div class="col-md-6 col-xl-4">
            <div class="block block-rounded block-link-shadow text-center" href="javascript:void(0)">
                <div class="block-content block-content-full">
                    <img class="fleet-avatar" src="public/media/fleet/{{$fleet->fleet_image}}" width="100%" height="auto" alt="Fleet Image">
                </div>
                <div class="block-content block-content-full block-content-sm bg-body-light">
                    <h3 class="font-w600 mb-0">{{$fleet->car_brand_model}}</h3>
                    <p><strong>Reg #:</strong> {{$fleet->car_registration_number}}<br>
                    <strong>Description:</strong> {{$fleet->car_description}}</p>
                </div>
                <div class="block-content block-content-full" style="overflow-x: initial;">
                    <div class="row">
                        <div class="col-12">
                            <div class="font-size-sm text-muted mb-0">
                                <p>
                                    <strong>Instructor:</strong>
                                    @if($fleet->instructor)
                                        {{$fleet->instructor->fname}} {{$fleet->instructor->sname}}
                                    @else
                                        Not assigned instructor
                                    @endif
                                    <br>
                                    <strong>Active students:</strong> {{$fleet->student()->where('status', '!=', 'Finished')->count()}}
                                </p>
                            </div>
                        </div>
                        <div class="col-12 text-right">
                            <div class="dropdown d-inline-block">
                                  <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span class="d-sm-inline-block">Action</span>
                                  </button>
                                  <div class="dropdown-menu dropdown-menu-end p-0">
                                    <div class="p-2">
                                      <form method="get" action="{{ url('/editfleet', $fleet->id) }}">
                                        {{ csrf_field() }}
                                        <button class="dropdown-item" type="submit">Edit</button>
                                      </form>
                                      <form method="POST" action="{{ url('/deletefleet', $fleet->id) }}">
                                        {{ csrf_field() }}
                                        {{ method_field('DELETE') }}
                                        <button class="dropdown-item delete-confirm" type="submit">Delete</button>
                                      </form>
                                    </div>
                                  </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.0/sweetalert.min.js"></script>
        <script type="text/javascript">
            $('.delete-confirm').on('click', function (e) {
                e.preventDefault();
                var form = $(this).parents('form');
                swal({
                    title: 'Are you sure you want to delete {{$fleet->car_brand_model}}?',
                    text: 'Will not delete as there are students still assigned to it!',
                    icon: 'warning',
                    buttons: ["Cancel", "Yes!"],
                }).then(function(isConfirm){
                        if(isConfirm){
                                form.submit();
                        }
                });
            });

        </script>
        @empty
           <h1>No fleet added yet!</h1>
        @endforelse
      </div>

      </div>
    </div>

    @include('fleet.addfleetmodal')
  <!-- END Hero -->
@endsection
