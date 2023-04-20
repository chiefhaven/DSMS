@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Invoices</h1>
      </div>
    </div>
  </div>

  <div class="content content-full">
    @if(Session::has('message'))
      <div class="alert alert-info">
        {{Session::get('message')}}
      </div>
    @endif
    <div class="block block-rounded block-bordered">
          <div class="block-content">
            <div class="col-md-12 mb-1">
                <form action="{{ url('/search-invoice') }}" method="GET" enctype="multipart/form-data">
                    @csrf
                        <input type="text" class="col-md-5 block block-rounded block-bordered p-2" id="search" name="search" placeholder="Search invoice" required>
                        <button type="submit" class="block-rounded  p-2 btn btn-alt-primary">
                            <i class="fa fa-search opacity-50 me-1"></i> Search
                        </button>
                </form>
            </div>
            <div class="table-responsive">
            @if(!$invoices->isEmpty())
              <table class="table table-bordered table-striped table-vcenter">
                  <thead>
                      <tr>
                          <th>Date</th>
                          <th>Invoice No</th>
                          <th>Student</th>
                          <th>Course Price</th>
                          <th>Discount</th>
                          <th>Total</th>
                          <th>Paid</th>
                          <th>Balance</th>
                          <th>Due</th>
                          <th class="text-center">Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                    @foreach ($invoices as $invoice)
                      <tr>
                          <td class="font-w600">
                              {{$invoice->date_created->format('j F, Y')}}
                          </td>
                          <td class="font-w600">
                              <a href="{{ url('/view-invoice', $invoice->invoice_number) }}">{{$invoice->invoice_number}}</a>
                          </td>
                          <td class="text-capitalize">
                            {{$invoice->student->fname}} <b>{{$invoice->student->sname}}</b>
                          </td>
                          <td>
                            K{{number_format($invoice->course_price, 2)}}
                          </td>
                          <td>
                            K{{number_format($invoice->invoice_discount, 2)}}
                          </td>
                          <td>
                            K{{number_format($invoice->invoice_total, 2)}}
                          </td>
                          <td>
                            K{{number_format($invoice->invoice_amount_paid, 2)}}
                          </td>
                          <td>
                              K{{number_format($invoice->invoice_balance, 2)}}
                          </td>
                          <td>
                              {{$invoice->invoice_payment_due_date->format('j F, Y')}}
                          </td>
                          <td class="text-center">
                            <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                              <span class="d-sm-inline-block">Action</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-0">
                              <div class="p-2">
                                <a class="dropdown-item" href="{{ url('/view-invoice', $invoice->invoice_number) }}">
                                  View
                                </a>
                                <form method="POST" action="{{ url('/edit-invoice', $invoice->invoice_number) }}">
                                      {{ csrf_field() }}
                                      <button class="dropdown-item" type="submit">Edit</button>
                                    </form>
                                <a class="dropdown-item" href="javascript:void(0)">
                                  Add payment
                                </a>
                                <a class="dropdown-item" href="javascript:void(0)">
                                  Print Invoice
                                </a>
                                <form method="POST" action="{{ url('/invoice-delete', $invoice->id) }}">
                                      {{ csrf_field() }}
                                      {{ method_field('DELETE') }}
                                      <button class="dropdown-item delete-confirm" type="submit">Delete</button>
                                </form>
                                <form method="POST" action="{{ url('send-notification', $invoice->student->id) }}">
                                    {{ csrf_field() }}
                                    <button class="dropdown-item" type="submit">Send balance reminder</button>
                                  </form>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
              {{ $invoices->links('pagination::bootstrap-4') }}
            @else
                <p class="p-5">No matching records found!</p>
            @endif
            </div>
          </div>
      </div>
    </div>

<script type="text/javascript">
    $('.delete-confirm').on('click', function (e) {
        e.preventDefault();
        var form = $(this).parents('form');
        swal({
            title: 'Are you sure you want to delete invoice',
            text: 'This will un-enroll the student from Daron!',
            icon: 'warning',
            buttons: ["Cancel", "Yes!"],
        }).then(function(isConfirm){
                if(isConfirm){
                        form.submit();
                }
        });
    });

</script>

<!-- Vue app -->
<script>
    const { createApp } = Vue

    createApp({
        data() {
        return {
            count: 0,
            info: [],
        }
        },
        methods : {

            async read() {
                const { data } = window.axios.get('/api/invoices');
                // console.log(data)
            },
            // Creating function
            timeCreated: function(date){
                return moment(date).format('DD MMMM, YYYY');
            },

            formatPrice(value) {
                let val = (value/1).toFixed(2).replace(',', '.')
                return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            },

            view_invoice: function(invoice_number){
                const url = `api/invoice-view/Daron-2022-2`;
                axios.get(url)
                    .then((response) => {
                        res(this.invoice = response.data);
                    })
                    .catch((err) => {
                        rej(err);
                    });
            },

            invoice_edit: function(invoice_number){

            },

            invoice_delete: function(invoice_number){

            },
        },

        mounted () {
            axios
            .get('api/home')
            .then(response => (this.info = response.data))
        }

    }).mount('#invoices')
</script>

  <!-- END Hero -->

@endsection
