@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Expenses</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">

            @if(Session::has('message'))
            <div class="alert alert-info">
              {{Session::get('message')}}
            </div>
          @endif

          @role(['superAdmin', 'admin'])
            <div class="">
                <a class="btn btn-primary" href="/addexpense" data-bs-target="#modal-block-vcenter">
                    <i class="fa fa-file-invoice-dollar"></i>&nbsp; Add expense
                </a>
            </div>
            @endcan
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    <div class="block block-rounded block-bordered">
          <div class="block-content">
            <div class="col-md-12 mb-1">
                <form action="{{ url('/search-expense') }}" method="GET" enctype="multipart/form-data">
                    @csrf
                        <input type="text" class="col-md-5 block block-bordered p-2" id="search" name="search" placeholder="Search expense" required>
                        <button type="submit" class="p-2 btn btn-alt-primary">
                            <i class="fa fa-search opacity-50 me-1"></i> Search
                        </button>
                </form>
            </div>
            </div>
                <div class="m-4 table-responsive">
                @if( !$expenses->isEmpty())
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead class="thead-dark">
                          <tr>

                            <th class="text-center" style="width: 100px;">Actions</th>
                            <th style="min-width: 6rem;">Group</th>
                            <th style="min-width: 10rem;">Description</th>
                            <th>Amount</th>
                            <th style="min-width: 10rem;">Date Paid</th>
                            <th style="min-width: 10rem;">Payment method</th>
                          </tr>
                      </thead>
                      <tbody>
                        @foreach ($expenses as $expense)
                            <tr>
                                <td class="text-center">
                                    <div class="dropdown d-inline-block">
                                        <button type="button" class="btn btn-primary" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span class="d-sm-inline-block">Action</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end p-0">
                                        <div class="p-2">
                                        @role(['superAdmin', 'admin'])
                                            @role(['superAdmin'])
                                                <form class="dropdown-item nav-main-link" method="get" action="{{ url('expensedownload', $expense) }}">
                                                    {{ csrf_field() }}
                                                    <i class="nav-main-link-icon fa fa-download"></i>
                                                    <button class="btn download-confirm" type="submit">Download</button>
                                                </form>
                                            @endcan
                                            @role(['superAdmin'])
                                                <form class="dropdown-item nav-main-link btn-danger" method="POST" action="{{ url('expenses', $expense) }}">
                                                    {{ csrf_field() }}
                                                    {{ method_field('DELETE') }}
                                                    <i class="nav-main-link-icon fa fa-trash"></i>
                                                    <button class="btn delete-confirm" type="submit">Delete</button>
                                                </form>
                                            @endcan
                                        @endcan
                                        </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{$expense->group}}<br>
                                <div class="sm-text">
                                    {{$expense->student->count()}} Students paid for!
                                </div>
                                </td>
                                <td>
                                    {{$expense->description}}
                                </td>
                                <td>
                                    K{{number_format($expense->amount)}}
                                </td>
                                <td>
                                    {{$expense->created_at->format('j F, Y')}}
                                </td>
                                <td>
                                    {{$expense->payment_method_id}}
                                </td>
                            </tr>
                        @endforeach
                      </tbody>
                  </table>
                    {{ $expenses->links('pagination::bootstrap-4') }}
                @else
                    <p class="p-5">No matching records found!</p>
                @endif
                </div>
          </div>
      </div>
      <script type="text/javascript">
        $('.delete-confirm').on('click', function (e) {
            e.preventDefault();
            var form = $(this).parents('form');
            Swal.fire({
                title: 'Delete Expense',
                text: 'Do you want to delete this expense?',
                icon: 'error',
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed)
                    form.submit();
            });
        });

    </script>
<!-- END Hero -->


@endsection