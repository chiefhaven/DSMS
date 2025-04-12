@extends('layouts.backend')

@section('content')
<!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">{{$userAdmin->administrator->fname}} {{$userAdmin->administrator->sname}}</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
            <div class="dropdown d-inline-block">
              <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="d-none d-sm-inline-block">Action</span>
              </button>
              <div class="dropdown-menu dropdown-menu-end p-0">
                <div class="p-2">
                  <form method="POST" action="/edit-administrator/{{$userAdmin->id}}">
                    {{ csrf_field() }}
                    <button class="dropdown-item" type="submit">Edit profile</button>
                  </form>
                </div>
              </div>
            </div>
          </ol>
        </nav>
      </div>
    </div>
  </div>

<div class="content content-full">
    <div class="block block-rounded">
      <ul class="nav nav-tabs nav-tabs-block" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" id="administrator-details-tab" data-bs-toggle="tab" data-bs-target="#administrator-details" role="tab" aria-controls="administrator-details" aria-selected="true">
            administrator Details
          </button>
        </li>
      </ul>
      <div class="block-content tab-content">
        <div class="tab-pane fade active show" id="administrator-details" role="tabpanel" aria-labelledby="administrator-details-tab">
          <div class="content content-full row">
            <div class="col-6" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
              <div class="py-6 px-4">
                <img class="img-avatar img-avatar96 img-avatar-thumb" src="/public/media/avatars/avatar2.jpg" alt="">
                <h1 class="my-2">{{$userAdmin->administrator->fname}} {{$userAdmin->administrator->sname}}</h1>
                <p>
                    Address: {{$userAdmin->administrator->address}} <br>Phone: {{$userAdmin->administrator->phone}}<br>Email: {{$userAdmin->email}}
                </p>
              </div>
            </div>
            </div>
         </div>
    </div>
  </div>
  </div>
</div>


@endsection
