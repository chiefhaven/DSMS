<div class="row">
        <div class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-primary" href="javascript:void(0)">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fa fa-2x fa-arrow-alt-circle-up text-primary-lighter"></i>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            {{number_format($salesPercentThisMonth, 2)}} %
                        </p>
                        <p class="text-white-75 mb-0">
                            Earnings
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-success" href="javascript:void(0)">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="far fa-2x fa-user text-success-light"></i>
                    </div>
                    <div class="ml-3 text-right">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            {{$studentCountThisMonth}}
                        </p>
                        <p class="text-white-75 mb-0">
                            Students
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xl-4">
            <div class="block block-rounded block-link-shadow bg-danger" href="javascript:void(0)">
                <div class="block-content block-content-full d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fa fa-2x fa-chart-line text-black-50"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-white font-size-h3 font-w300 mb-0">
                            {{$invoiceConutThisMonth}}
                        </p>
                        <p class="text-white-75 mb-0">
                            Sales
                        </p>
                    </div>
                </div>
            </div>
        </div>
      </div>

      <div class="row items-push">
          <div class="col-xl-6">
            <div class="block block-rounded block-bordered block-mode-loading-refresh h-100 mb-0">
              <div class="block-header border-bottom">
                <h3 class="block-title">Students</h3>
                <div class="block-options">
                  <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                    <i class="fa fa-sync"></i>
                  </button>
                  <button type="button" class="btn-block-option">
                    <i class="fa fa-wrench"></i>
                  </button>
                </div>
              </div>
              <div class="block-content">
                <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                  <thead>
                    <tr class="text-uppercase">
                      <th class="fw-bold text-center" style="width: 120px;">Photo</th>
                      <th class="fw-bold">Name</th>
                      <th class="d-none d-sm-table-cell fw-bold text-center">Course</th>
                      <th class="fw-bold text-center" style="width: 60px;"></th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($student as $student)
                    <tr>
                      <td class="text-center">
                        <img class="img-avatar img-avatar32 img-avatar-thumb" src="media/avatars/avatar2.jpg" alt="">
                      </td>
                      <td>
                        <div class="fw-semibold fs-base">{{$student->fname}} {{$student->sname}}</div>
                        <div class="text-muted">
                            @if(isset($student->user->email))

                              {{$student->user->email}}

                            @else
                              
                            @endif
                        </div>
                      </td>
                      <td class="d-none d-sm-table-cell fs-base text-center">
                        
                          @if(isset($student->course->name))
                          <span class="badge bg-dark">

                             {{$student->course->name}}
                              <div class="text-muted">{{$student->course->duration}} days</div>
                          </span>

                          @else
                            <a href="">
                              <span class="badge bg-danger">
                                Not enrolled yet
                              </span>
                            </a>
                          @endif
                      </td>
                      <td class="text-center">
                        <a href="{{ url('/viewstudent', $student->id) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="" class="js-bs-tooltip-enabled" data-bs-original-title="View Colleague">
                          <i class="fa fa-fw fa-user-circle"></i>
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-xl-6">
            <div class="block block-rounded block-bordered block-mode-loading-refresh h-100 mb-0">
              <div class="block-header border-bottom">
                <h3 class="block-title">Invoices</h3>
                <div class="block-options">
                  <button type="button" class="btn-block-option" data-toggle="block-option" data-action="state_toggle" data-action-mode="demo">
                    <i class="fa fa-sync"></i>
                  </button>
                  <button type="button" class="btn-block-option">
                    <i class="fa fa-wrench"></i>
                  </button>
                </div>
              </div>
              <div class="block-content">
                <table class="table table-striped table-hover table-borderless table-vcenter fs-sm">
                  <thead>
                    <tr class="text-uppercase">
                      <th class="fw-bold">Invoice No</th>
                      <th class="d-none d-sm-table-cell fw-bold">Date</th>
                      <th class="fw-bold">Student</th>
                      <th class="fw-bold">Balance</th>
                      <th class="fw-bold text-center" style="width: 60px;">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($invoice as $invoice)
                    <tr>
                      <td>
                        <span class="fw-semibold"><a href="{{ url('/view-invoice', $invoice->invoice_number) }}">{{$invoice->invoice_number}}</a></span>
                      </td>
                      <td class="d-none d-sm-table-cell">
                        <span class="fs-sm text-muted">{{$invoice->date_created->format('j F, Y')}}</span>
                      </td>
                      <td>
                        <span class="fw-semibold text-warning">{{$invoice->student->fname}} {{$invoice->student->sname}}</span>
                      </td>
                      <td>
                        <span class="fw-semibold text-warning">K{{number_format($invoice->invoice_balance, 2)}}</span>
                      </td>
                      <td class="text-center">
                        <a href="{{ url('/view-invoice', $invoice->invoice_number) }}" data-bs-toggle="tooltip" data-bs-placement="left" title="" class="js-bs-tooltip-enabled" data-bs-original-title="Manage">
                          <i class="fa fa-fw fa-life-ring"></i>
                        </a>
                      </td>
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>