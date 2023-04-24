@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Settings</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded">
    <ul class="nav nav-tabs nav-tabs-block" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" id="school_details" data-bs-toggle="tab" data-bs-target="#search-classic" role="tab" aria-controls="search-classic" aria-selected="true">
          School Details
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="search-photos-tab" data-bs-toggle="tab" data-bs-target="#search-photos" role="tab" aria-controls="search-photos" aria-selected="false">
          Invoice
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="search-customers-tab" data-bs-toggle="tab" data-bs-target="#search-customers" role="tab" aria-controls="search-customers" aria-selected="false">
          System
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="search-projects-tab" data-bs-toggle="tab" data-bs-target="#search-projects" role="tab" aria-controls="search-projects" aria-selected="false">
          Backup
        </button>
      </li>
    </ul>
    <div class="block-content tab-content overflow-hidden">
      <div class="tab-pane fade active show" id="search-classic" role="tabpanel" aria-labelledby="search-classic-tab">
        <div class="fs-3 fw-semibold pt-2 pb-4 mb-4 text-center border-bottom">
          SMS templates
        </div>
        <div class="row">
          <form class="mb-5" action="{{url('/settings-update')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="form-floating mb-4">
              <input type="text" class="form-control" id="school_name" name="school_name" value="{{$setting->school_name}}">
              <label class="form-label" for="example-school-name-input-floating">School name</label>
            </div>
            <div class="form-floating mb-4">
              <input type="text" class="form-control" id="slogan" name="slogan" value="{{$setting->slogan}}">
              <label class="form-label" for="example-slogan-input-floating">Slogan</label>
            </div>
            <br>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
          </form>
        </div>
      </div>
      <div class="tab-pane fade" id="search-photos" role="tabpanel" aria-labelledby="search-photos-tab">
        <div class="row">
          <form class="mb-5" id="invoiceSettings">
            <div class="row g-sm push">
              <div class="container">
                <div class="form-floating mb-4">
                    <textarea class="form-control" id="header" name="header" style="height: 200px" value="" placeholder="Description here">{{$invoice_setting->header}}</textarea>
                    <label class="form-label" for="example-textarea-floating">Header text</label>
                </div>
                <div class="form-group mb-4">
                    <label for="example-ltf-password">Logo</label>
                    <input type="file" class="form-control" id="invoice_logo" name="invoice_logo" placeholder="logo">
                </div>
                <div class="form-floating mb-4">
                    <input type="text" class="form-control" id="due" name="due" placeholder="due" value="{{$invoice_setting->invoice_due_days}}">
                    <label class="form-label" for="example-email-input-floating">Default invoice due (days)</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="text" class="form-control" id="prefix" name="prefix" placeholder="prefix" value="{{$invoice_setting->prefix}}">
                    <label class="form-label" for="example-email-input-floating">Invoice number prefix</label>
                </div>
              </div>
              <div class="mb-4">
                <div class="form-check">
                  <label class="form-check-label" for="block-form8-remember-me">User year in invoice numbering</label>
                  <input class="form-check-input" type="checkbox" value="" id="year" name="year" checked>
                </div>
              </div>
              <div class="form-floating mb-4">
                  <textarea class="form-control" id="terms" name="terms" style="height: 200px" value="" placeholder="Description here">{{$invoice_setting->terms}}</textarea>
                  <label class="form-label" for="example-textarea-floating">Invoice terms</label>
              </div>
              <div class="form-floating mb-4">
                  <textarea class="form-control" id="footer" name="footer" style="height: 200px" value="" placeholder="Description here">{{$invoice_setting->footer}}</textarea>
                  <label class="form-label" for="example-textarea-floating">Footer text</label>
              </div>
            </div>
            <br>
            <div class="form-group">
                <button class="btn btn-primary" id="invoicesettings-update">Update</button>
            </div>
          </form>
        </div>
      </div>
      <div class="tab-pane fade" id="search-customers" role="tabpanel" aria-labelledby="search-customers-tab">
        <div class="fs-3 fw-semibold pt-2 pb-4 mb-4 text-center border-bottom">
          Coming soon
        </div>
        <p>Email, SMS gateway settings.</p>
      </div>
    </div>
  </div>
  </div>
  <!-- END Hero -->

  <script type="text/javascript">

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $("#invoicesettings-update").click(function(e){

        e.preventDefault();

       var terms = $('#terms').val();
       var header = $('#header').val();
       var footer = $('#footer').val();
       var year = $('#year').val();
       var prefix = $('#prefix').val();
       var due = $('#due').val();
       var logo = $('#logo').val();

        $.ajax({
           type:'POST',
           url:"{{ url('/invoicesettings-update') }}",
           data:{
            terms:terms,
            header:header,
            footer:footer,
            year:year,
            prefix:prefix,
            due:due,
            invoice_logo:logo},
           success:function(response){
                if(response){
                    location.reload();
                }else{
                    printErrorMsg(data.error);
                }
           }
        });

    });

    function printErrorMsg (msg) {
        $(".print-error-msg").find("ul").html('');
        $(".print-error-msg").css('display','block');
        $.each( msg, function( key, value ) {
            $(".print-error-msg").find("ul").append('<li>'+value+'</li>');
        });
    }

</script>

@endsection
