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
                @if(!$expenses->isEmpty())
                  <table class="table table-bordered table-striped table-vcenter">
                      <thead class="thead-dark">
                          <tr>
                            <th class="text-center" style="width: 100px;">Actions</th>
                            <th style="min-width: 14rem;">Group</th>
                            <th style="min-width: 10rem;">Status</th>
                            <th style="min-width: 7rem;">Type</th>
                            <th style="min-width: 10rem;">Description</th>
                            <th style="min-width: 10rem;">Posted by</th>
                            <th style="min-width: 10rem;">Amount per student</th>
                            <th style="min-width: 10em;">Approved Amount</th>
                            <th style="min-width: 10rem;">Approved by</th>
                            <th style="min-width: 10rem;">Date Approved</th>
                            <th style="min-width: 10rem;">Last edited</th>
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
                                        @role(['superAdmin|admin'])
                                            @if($expense->group_type != 'TRN')
                                                @if($expense->approved == true)
                                                    <form class="dropdown-item nav-main-link" method="get" action="{{ url('expensedownload', $expense) }}">
                                                        {{ csrf_field() }}
                                                        <i class="nav-main-link-icon fa fa-download"></i>
                                                        <button class="btn download-confirm" type="submit">Download</button>
                                                    </form>
                                                @else
                                                    <p class="text-danger">Downlod not available, list not approved yet</p>
                                                @endif
                                            @else
                                                <p class="text-danger">Go to individual student profile to downlaod TRN reference</p>
                                            @endif
                                            @if ($expense->approved == '0')
                                                <a class="dropdown-item nav-main-link" href="{{ url('editexpense', $expense) }}">
                                                    {{ csrf_field() }}
                                                    <i class="nav-main-link-icon fa fa-pen"></i>
                                                    <div class="btn">Edit</div>
                                                </a>
                                            @endcan
                                            @role(['superAdmin'])
                                                <a class="dropdown-item nav-main-link" href="{{ url('review-expense', $expense) }}">
                                                    {{ csrf_field() }}
                                                    <i class="nav-main-link-icon fa fa-magnifying-glass"></i>
                                                    <div class="btn">Review</div>
                                                </a>
                                                @if ($expense->approved == '0')
                                                    <form class="dropdown-item nav-main-link" method="POST" action="{{ url('expenses', $expense) }}">
                                                            {{ csrf_field() }}
                                                            {{ method_field('DELETE') }}
                                                            <i class="nav-main-link-icon text-danger fa fa-trash"></i>
                                                            <button class="btn delete-confirm text-danger" type="submit">Delete</button>
                                                    </form>
                                                @endcan
                                            @endcan
                                        @endcan
                                        </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    {{$expense->group}}<br>
                                    <div class="sm-text" style="font-size: 12px">
                                        {{$expense->students->count()}} Students paid for!
                                    </div>
                                </td>
                                <td>
                                    @if ($expense->approved == '1')
                                        <div class="text-center p-1 text-success">
                                            <i class="fa fa-check" aria-hidden="true"></i> Approved
                                        </div>
                                    @else
                                        <div class="text-center p-1 text-danger">
                                            <i class="fa fa-times" aria-hidden="true"></i> Unapproved
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{$expense->group_type}}
                                </td>
                                <td>
                                    {{$expense->description}}
                                </td>
                                @role(['superAdmin'])
                                    <td>
                                        @if ($expense->administrator)
                                            {{$expense->administrator->fname}} {{$expense->administrator->sname}}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endcan
                                <td>
                                    K{{number_format($expense->amount)}}
                                </td>
                                <td>
                                    K{{number_format($expense->approved_amount)}}
                                </td>
                                <td>
                                    @if ($expense->approved==true)
                                        {{App\Models\Administrator::find($expense->approved_by)->fname}}
                                        {{App\Models\Administrator::find($expense->approved_by)->sname}}
                                    @endif
                                </td>
                                <td>
                                    @if ($expense->approved==true)
                                        {{$expense->date_approved->format('j F, Y')}}
                                    @endif
                                </td>
                                <td>
                                    @if ($expense->edited_by)
                                        @if ($expense->edited_by != Auth::user()->administrator->id)
                                            {{App\Models\Administrator::find($expense->edited_by)->fname}}
                                            {{App\Models\Administrator::find($expense->edited_by)->sname}}
                                        @else
                                            By: You
                                        @endif

                                        <div class="sm-text" style="font-size: 12px">
                                            {{$expense->updated_at->format('j F, Y H:m:s')}}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    Cash
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
