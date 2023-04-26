@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Notifications</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded">
    <ul class="nav nav-tabs nav-tabs-block" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" id="school_details" data-bs-toggle="tab" data-bs-target="#search-classic" role="tab" aria-controls="search-classic" aria-selected="true">
         Notification templates
        </button>
      </li>
    </ul>
    <div class="block-content tab-content overflow-hidden">
      <div class="tab-pane fade active show" id="search-classic" role="tabpanel" aria-labelledby="search-classic-tab">
        <div class="fs-4 fw-semibold pt-2 pb-4 mb-4 border-bottom">
          Student sms notifications templates
        </div>
        <div class="row">
            <div class="form-floating mb-4">
                <p><label class="form-label" for="example-school-name-input-floating">New registration</label></p>
                <button class="btn content content-full shadow-sm p-3 mb-5 bg-white rounded" data-bs-toggle="modal" data-bs-target="#new-registration">
                    {{ $templates[0]->body }}
                </button>
            </div>

            <div class="form-floating mb-4">
                <p><label class="form-label" for="example-school-name-input-floating">Enrollment</label></p>
                <button class="btn content content-full shadow-sm p-3 mb-5 bg-white rounded" data-bs-toggle="modal" data-bs-target="#new-enrollment">
                    {{ $templates[1]->body }}
                </button>
            </div>

            <div class="form-floating mb-4">
                <p><label class="form-label" for="example-school-name-input-floating">Balance</label></p>
                <button class="btn content content-full shadow-sm p-3 mb-5 bg-white rounded" data-bs-toggle="modal" data-bs-target="#balance">
                    {{ $templates[2]->body }}
                </button>
            </div>
        </div>
      </div>
    </div>
  </div>
  </div>
  <!-- END Hero -->

<div class="modal" id="new-registration" tabindex="-1" aria-labelledby="new-registration" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header bg-primary-dark">
                    <h3 class="block-title">New registration sms notification template</h3>
                    <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="block-content">
                    <form class="mb-5" action="{{url('/update-notification-templates')}}" method="post" enctype="multipart/form-data">
                        @csrf

                    <input type="text" class="form-control" id="type" name="type" value="{{ $templates[0]->type }}" hidden>

                    <div class="col-12 form-floating mb-4">
                        <div class="text-sm p-2">Available tags:<br> {first_name}, {middle_name}, {sir_name}</div>
                        <textarea rows="15" cols="50" style="height: 150px" class="form-control" id="body" name="body">{{ $templates[0]->body }}</textarea>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="new-enrollment" tabindex="-1" aria-labelledby="new-enrollment" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header bg-primary-dark">
                    <h3 class="block-title">Enrollment sms notification template</h3>
                    <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="block-content">
                    <form class="mb-5" action="{{url('/update-notification-templates')}}" method="post" enctype="multipart/form-data">
                        @csrf

                    <input type="text" class="form-control" id="type" name="type" value="{{ $templates[1]->type }}" hidden>

                    <div class="col-12 form-floating mb-4">
                        <div class="text-sm p-2">Available tags:<br> {first_name}, {middle_name}, {sir_name}, {course}</div>
                        <textarea rows="15" cols="50" style="height: 150px" class="form-control" id="body" name="body">{{ $templates[1]->body }}</textarea>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal" id="balance" tabindex="-1" aria-labelledby="balance" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="block block-rounded block-themed block-transparent mb-0">
                <div class="block-header bg-primary-dark">
                    <h3 class="block-title">Balance sms notification template</h3>
                    <div class="block-options">
                    <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-fw fa-times"></i>
                    </button>
                    </div>
                </div>
                <div class="block-content">
                    <form class="mb-5" action="{{url('/update-notification-templates')}}" method="post" enctype="multipart/form-data">
                        @csrf

                    <input type="text" class="form-control" id="type" name="type" value="{{ $templates[2]->type }}" hidden>

                    <div class="col-12 form-floating mb-4">
                        <div class="text-sm p-2">Available tags:<br> {FIRST_NAME}, {MIDDLE_NAME}, {SIR_NAME}, {INVOICE_TOTAL}, {INVOICE_PAID}, {BALANCE} {DUE_DATE}</div>
                        <textarea rows="15" cols="50" style="height: 150px" class="form-control" id="body" name="body">{{ $templates[2]->body }}</textarea>
                    </div>
                    <div class="block-content block-content-full text-end bg-body">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
