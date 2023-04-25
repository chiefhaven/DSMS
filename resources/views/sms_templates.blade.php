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
          Student sms notifications
        </div>
        <div class="row">
          <form class="mb-5" action="{{url('/update-sms-templates')}}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="text-sm p-2">Available tags:<br> {first_name}, {middle_name}, {sir_name}</div>
            <div class="form-floating mb-4">
              <textarea class="form-control" id="new_registration" name="new_registration" value=""></textarea>
              <label class="form-label" for="example-school-name-input-floating">New registration</label>
            </div>

            <div class="text-sm p-2">Available tags:<br> {first_name}, {middle_name}, {sir_name}, {course}</div>
            <div class="form-floating mb-4">
                <textarea class="form-control" id="enrollment" name="enrollment" value=""></textarea>
                <label class="form-label" for="example-school-name-input-floating">Enrollment</label>
            </div>

            <div class="text-sm p-2">Available tags:<br> {first_name}, {middle_name}, {sir_name}, {invoice_total}, {total_paid}, {balance}</div>
            <div class="form-floating mb-4">
              <textarea type="text" class="form-control" id="balance_reminder" name="balance_reminder" value=""></textarea>
              <label class="form-label" for="example-slogan-input-floating">Balance reminder</label>
            </div>
            <br>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
          </form>
        </div>
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
