@php
    $contractModalId = 'employment-contract-modal-' . ($employee->id ?? 'employee');
    $employmentDate = $employee->employment_date ? \Illuminate\Support\Carbon::parse($employee->employment_date)->format('d-m-Y') : 'Not set';
    $endDate = $employee->end_date ? \Illuminate\Support\Carbon::parse($employee->end_date)->format('d-m-Y') : 'Not set';
    $contractSigned = (bool) ($employee->contract_signed ?? $employee->terms_signed ?? $employee->employment_contract_signed ?? false);
    $contractSignedAt = $employee->contract_signed_at ?? $employee->terms_signed_at ?? null;
    $contractSignedAt = $contractSignedAt ? \Illuminate\Support\Carbon::parse($contractSignedAt)->format('d-m-Y') : null;

    $authUser = auth()->user();
    $ownsStaffProfile = $authUser
        && (($employee instanceof \App\Models\Instructor && $authUser->instructor_id && (string) $authUser->instructor_id === (string) $employee->id)
            || ($employee instanceof \App\Models\Administrator && $authUser->administrator_id && (string) $authUser->administrator_id === (string) $employee->id));
    $canManagePayroll = $authUser && ($authUser->can('process payroll') || $authUser->can('view payroll'));
    $canViewPayrollHistory = $canManagePayroll || $ownsStaffProfile;
    $canDownloadPayslip = $canManagePayroll || $ownsStaffProfile;
    $visiblePayrolls = $employee->relationLoaded('payrolls')
        ? $employee->payrolls->filter(fn ($payroll) => $canManagePayroll || $ownsStaffProfile && in_array(strtolower((string) $payroll->status), ['final', 'paid'], true))
        : collect();
@endphp
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                    <h2 class="fs-6 fw-semibold mb-0">Employment details</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#{{ $contractModalId }}">
                        View contract
                    </button>
                </div>
                <dl class="row g-2 mb-0">
                    <dt class="col-sm-5 text-muted fw-normal">Employment date</dt>
                    <dd class="col-sm-7 mb-1">{{ $employmentDate }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Employment type</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->employment_type ?: 'Not set' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">End date</dt>
                    <dd class="col-sm-7 mb-1">{{ $endDate }}</dd>

                    @if ($staffStatus)
                        <dt class="col-sm-5 text-muted fw-normal">Staff status</dt>
                        <dd class="col-sm-7 mb-0">{{ ucfirst($staffStatus) }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="modal fade" id="{{ $contractModalId }}" tabindex="-1" aria-labelledby="{{ $contractModalId }}-label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary">
                        <h5 class="modal-title text-white" id="{{ $contractModalId }}-label">Employment contract</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="fw-semibold mb-2">Contract details</h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-normal" style="width: 35%;">Employee</th>
                                        <td>{{ trim(($employee->fname ?? '') . ' ' . ($employee->sname ?? '')) ?: 'Not set' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Employment type</th>
                                        <td>{{ $employee->employment_type ?: 'Not set' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Employment date</th>
                                        <td>{{ $employmentDate }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">End date</th>
                                        <td>{{ $endDate }}</td>
                                    </tr>
                                    @if ($staffStatus)
                                        <tr>
                                            <th class="text-muted fw-normal">Staff status</th>
                                            <td>{{ ucfirst($staffStatus) }}</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <th class="text-muted fw-normal">HR department</th>
                                        <td>{{ $employee->hrDepartment?->name ?? 'Not assigned' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="text-muted fw-normal">Designation</th>
                                        <td>{{ $employee->designation?->name ?? 'Not assigned' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-semibold mb-2">Terms of employment</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-borderless table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <th class="text-muted fw-normal" style="width: 35%;">Terms status</th>
                                        <td>
                                            @if ($contractSigned)
                                                <span class="badge bg-success">Signed by employee</span>
                                                @if ($contractSignedAt)
                                                    <span class="text-muted fs-sm ms-1">({{ $contractSignedAt }})</span>
                                                @endif
                                            @else
                                                <span class="badge bg-warning text-dark">Not signed</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @php
                            $savedTerms = trim((string) ($employee->terms_of_employment ?? ''));
                        @endphp
                        @if ($savedTerms !== '')
                            <div class="border rounded p-3 bg-body-light">
                                <h6 class="fs-sm fw-semibold mb-2">Contract / employment terms</h6>
                                <div class="fs-sm">{!! $savedTerms !!}</div>
                            </div>
                        @elseif ($employee instanceof \App\Models\Administrator)
                            <div class="border rounded p-3 bg-body-light">
                                <h6 class="fs-sm fw-semibold mb-2">Administrator contract terms (template)</h6>
                                <ol class="fs-sm mb-0 ps-3">
                                    <li class="mb-1"><strong>Position and duties:</strong> The Administrator shall perform administrative, coordination, and operational duties as assigned, and comply with institutional policies and lawful instructions.</li>
                                    <li class="mb-1"><strong>Working hours:</strong> Standard working hours apply as scheduled by management, with reasonable additional hours when required for official duties.</li>
                                    <li class="mb-1"><strong>Compensation and benefits:</strong> Remuneration, allowances, and statutory deductions shall be administered in accordance with approved payroll and HR policies.</li>
                                    <li class="mb-1"><strong>Confidentiality:</strong> The Administrator shall maintain confidentiality of student, staff, financial, and organizational information during and after employment.</li>
                                    <li class="mb-1"><strong>Conduct and compliance:</strong> The Administrator is expected to uphold professional conduct, data protection, and workplace health and safety requirements.</li>
                                    <li class="mb-1"><strong>Leave and attendance:</strong> Leave, attendance, and time-off requests are subject to approval and applicable HR procedures.</li>
                                    <li class="mb-0"><strong>Termination:</strong> Either party may terminate employment in accordance with the employment type, notice period, and applicable labor regulations; company property must be returned upon separation.</li>
                                </ol>
                                <p class="text-muted fs-sm mb-0 mt-2">Template shown because no custom terms are saved. Edit the employee to save role-specific terms.</p>
                            </div>
                        @elseif ($employee instanceof \App\Models\Instructor)
                            <div class="border rounded p-3 bg-body-light">
                                <h6 class="fs-sm fw-semibold mb-2">Instructor contract terms (template)</h6>
                                <ol class="fs-sm mb-0 ps-3">
                                    <li class="mb-1"><strong>Position and duties:</strong> The Instructor shall deliver assigned lessons, assess student progress, maintain accurate attendance/lesson records, and comply with institutional policies and lawful instructions.</li>
                                    <li class="mb-1"><strong>Working hours and schedules:</strong> Teaching and duty hours shall follow assigned schedules, with reasonable flexibility for lessons, assessments, and official activities as required.</li>
                                    <li class="mb-1"><strong>Compensation and benefits:</strong> Remuneration, allowances, and statutory deductions shall be administered in accordance with approved payroll and HR policies, including any applicable attendance or performance-based terms.</li>
                                    <li class="mb-1"><strong>Professional conduct and safety:</strong> The Instructor shall uphold professional conduct, student safety, vehicle/classroom safety procedures, and all applicable training and road safety standards.</li>
                                    <li class="mb-1"><strong>Confidentiality:</strong> The Instructor shall maintain confidentiality of student, staff, and organizational information during and after employment.</li>
                                    <li class="mb-1"><strong>Leave and attendance:</strong> Leave, attendance, and time-off requests are subject to approval and applicable HR procedures.</li>
                                    <li class="mb-0"><strong>Termination:</strong> Either party may terminate employment in accordance with the employment type, notice period, and applicable labor regulations; company property and records must be returned upon separation.</li>
                                </ol>
                                <p class="text-muted fs-sm mb-0 mt-2">Template shown because no custom terms are saved. Edit the employee to save role-specific terms.</p>
                            </div>
                        @else
                            <div class="text-muted fs-sm">No terms of employment recorded for this employee.</div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-alt-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3">
                <h2 class="fs-6 fw-semibold mb-3">Organization & banking</h2>
                <dl class="row g-2 mb-0">
                    <dt class="col-sm-5 text-muted fw-normal">HR department</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->hrDepartment?->name ?? 'Not assigned' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Designation</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->designation?->name ?? 'Not assigned' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Salary grade</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->salaryGrade?->name ?? $employee->salaryGrade?->code ?? 'Not assigned' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Bank</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->bank_name ?: 'Not set' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Bank branch</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->bank_branch ?: 'Not set' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Account name</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->bank_account_name ?: 'Not set' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Account number</dt>
                    <dd class="col-sm-7 mb-1">{{ $employee->bank_account_number ?: 'Not set' }}</dd>

                    <dt class="col-sm-5 text-muted fw-normal">Account type</dt>
                    <dd class="col-sm-7 mb-0">{{ $employee->bank_account_type ?: 'Not set' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    @if ($canViewPayrollHistory)
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-header bg-body-light border-bottom p-3 d-flex justify-content-between align-items-center gap-2">
                    <h2 class="fs-6 fw-semibold mb-0">Payroll history</h2>
                </div>
                @if ($visiblePayrolls->isEmpty())
                    <div class="card-body text-muted">No payroll records found.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter align-middle mb-0 fs-sm">
                            <thead class="bg-light">
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Basic salary</th>
                                    <th class="text-end">Allowances</th>
                                    <th class="text-end">Bonus</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Deductions</th>
                                    <th class="text-end">Net pay</th>
                                    <th>Status</th>
                                    <th>Payment date</th>
                                    @if ($canDownloadPayslip)
                                        <th class="text-end">Payslip</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($visiblePayrolls as $payroll)
                                    @php
                                        $gross = (float) ($payroll->basic_salary ?? 0) + (float) ($payroll->allowances ?? 0) + (float) ($payroll->bonus ?? 0);
                                        $totalDeductions = (float) ($payroll->deductions ?? 0) + (float) ($payroll->paye ?? 0) + (float) ($payroll->employee_pension ?? 0);
                                    @endphp
                                    <tr>
                                        <td>{{ $payroll->payment_month }}</td>
                                        <td class="text-end">K{{ number_format((float) $payroll->basic_salary, 2) }}</td>
                                        <td class="text-end">K{{ number_format((float) $payroll->allowances, 2) }}</td>
                                        <td class="text-end">K{{ number_format((float) $payroll->bonus, 2) }}</td>
                                        <td class="text-end">K{{ number_format($gross, 2) }}</td>
                                        <td class="text-end">K{{ number_format($totalDeductions, 2) }}</td>
                                        <td class="text-end fw-semibold">K{{ number_format((float) $payroll->net_pay, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ match (strtolower((string) $payroll->status)) {
                                                'paid' => 'success',
                                                'final' => 'primary',
                                                default => 'warning',
                                            } }}">
                                                {{ $payroll->status }}
                                            </span>
                                        </td>
                                        <td>{{ $payroll->payment_date?->format('d-m-Y') ?? 'Not paid' }}</td>
                                        @if ($canDownloadPayslip)
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-alt-primary" href="{{ route('payroll.payslip', $payroll->id) }}" title="Download payslip">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
