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

           success: function(response) {
            if (response) {
                    $("#invoiceSettings")[0].reset();
                    Swal.fire("Done!","It was succesfully updated!","success");
                }
            else{
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