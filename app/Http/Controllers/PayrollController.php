<?php

namespace App\Http\Controllers;

use App\Models\Administrator;
use App\Models\Attendance;
use App\Models\Instructor;
use App\Models\Payroll;
use App\Models\PayrollGroup;
use App\Models\Setting;
use App\Notifications\PayslipIssued;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Yajra\DataTables\Facades\DataTables;
use PDF;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view payroll')->only(['fetch', 'employees', 'bonusPreview', 'bonusPreviews', 'groups', 'showGroup', 'payslip']);
        $this->middleware('permission:view payroll|view salary grades')->only('index');
        $this->middleware('permission:process payroll')->only([
            'store', 'update', 'updateGroup', 'pay', 'payGroup', 'destroy', 'destroyGroup', 'emailPayslip',
            'payeSettings', 'updatePayeSettings',
        ]);
    }

    public function index()
    {
        return view('hrm.payroll', [
            'payeConfig' => $this->payeConfig(),
            'pensionConfig' => $this->pensionConfig(),
            'defaultPayrollMonth' => Payroll::max('payment_month'),
        ]);
    }

    public function payeSettings(): JsonResponse
    {
        return response()->json($this->payeConfig());
    }

    public function updatePayeSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'rate' => ['nullable', 'numeric', 'min:0'],
            'bands' => ['nullable', 'array'],
            'bands.*.up_to' => ['nullable', 'numeric', 'min:0'],
            'bands.*.rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bands = [];
        foreach (($validated['bands'] ?? []) as $band) {
            $rate = $band['rate'] ?? null;
            $upTo = $band['up_to'] ?? null;
            if ($rate === null || $rate === '') {
                continue;
            }
            $bands[] = [
                'up_to' => ($upTo === null || $upTo === '') ? null : (float) $upTo,
                'rate' => (float) $rate,
            ];
        }

        $settings = Setting::query()->firstOrCreate(['id' => 1], [
            'school_name' => config('app.name', 'School'),
            'slogan' => '',
            'company_description' => '',
            'logo' => '',
            'authorization_signature' => '',
            'favicon' => '',
            'district_id' => 0,
            'postal' => '',
            'time_zone' => config('app.timezone', 'UTC'),
            'email' => '',
            'phone_1' => '',
            'phone_2' => '',
            'address' => '',
        ]);
        $settings->paye_settings = [
            'enabled' => (bool) $validated['enabled'],
            'rate' => isset($validated['rate']) ? (float) $validated['rate'] : 0.0,
            'bands' => $bands,
        ];
        $settings->save();

        return response()->json([
            'message' => 'PAYE settings updated successfully.',
            'paye' => $this->payeConfig(),
        ]);
    }

    private function payeConfig(): array
    {
        return Setting::payeConfig();
    }

    public function pensionSettings(): JsonResponse
    {
        return response()->json($this->pensionConfig());
    }

    public function updatePensionSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'employee_rate' => ['nullable', 'numeric', 'min:0'],
            'employer_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $settings = Setting::query()->firstOrCreate(['id' => 1], [
            'school_name' => config('app.name', 'School'),
            'slogan' => '',
            'company_description' => '',
            'logo' => '',
            'authorization_signature' => '',
            'favicon' => '',
            'district_id' => 0,
            'postal' => '',
            'time_zone' => config('app.timezone', 'UTC'),
            'email' => '',
            'phone_1' => '',
            'phone_2' => '',
            'address' => '',
        ]);
        $settings->pension_settings = [
            'enabled' => (bool) $validated['enabled'],
            'employee_rate' => isset($validated['employee_rate']) ? (float) $validated['employee_rate'] : 0.0,
            'employer_rate' => isset($validated['employer_rate']) ? (float) $validated['employer_rate'] : 0.0,
        ];
        $settings->save();

        return response()->json([
            'message' => 'Pension settings updated successfully.',
            'pension' => $this->pensionConfig(),
        ]);
    }

    private function pensionConfig(): array
    {
        return Setting::pensionConfig();
    }

    private function applyPayrollMonthFilter($query, Request $request): void
    {
        $from = trim((string) $request->input('from_month', ''));
        $to = trim((string) $request->input('to_month', ''));

        if ($from !== '' || $to !== '') {
            if ($from !== '') {
                $query->where('payment_month', '>=', $from);
            }
            if ($to !== '') {
                $query->where('payment_month', '<=', $to);
            }
            return;
        }

        if ($request->filled('month')) {
            $query->where('payment_month', $request->input('month'));
        }
    }

    public function summary(Request $request): JsonResponse
    {
        $query = Payroll::query();

        $this->applyPayrollMonthFilter($query, $request);

        if ($request->filled('group')) {
            $query->where('payroll_group_id', $request->input('group'));
        }

        $count = (clone $query)->count();
        $basic = (float) (clone $query)->sum('basic_salary');
        $allowances = (float) (clone $query)->sum('allowances');
        $bonus = (float) (clone $query)->sum('bonus');
        $deductions = (float) (clone $query)->sum('deductions');
        $paye = (float) (clone $query)->sum('paye');
        $employeePension = (float) (clone $query)->sum('employee_pension');
        $employerPension = (float) (clone $query)->sum('employer_pension');
        $net = (float) (clone $query)->sum('net_pay');

        return response()->json([
            'count' => $count,
            'gross' => round($basic + $allowances + $bonus, 2),
            'total_deductions' => round($deductions + $paye + $employeePension, 2),
            'paye' => round($paye, 2),
            'employee_pension' => round($employeePension, 2),
            'employer_pension' => round($employerPension, 2),
            'net_pay' => round($net, 2),
            'paid_count' => (clone $query)->whereRaw("LOWER(status) = 'paid'")->count(),
            'pending_count' => (clone $query)->whereRaw("LOWER(status) = 'pending'")->count(),
            'final_count' => (clone $query)->whereRaw("LOWER(status) = 'final'")->count(),
        ]);
    }

    public function fetch(Request $request): JsonResponse
    {
        $payrolls = Payroll::with(['instructor', 'administrator', 'processor']);

        $this->applyPayrollMonthFilter($payrolls, $request);

        if ($request->filled('group')) {
            $payrolls->where('payroll_group_id', $request->input('group'));
        }

        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $term = '%' . $search . '%';
            $payrolls->where(function ($query) use ($term) {
                $query->where('payment_month', 'like', $term)
                    ->orWhere('status', 'like', $term)
                    ->orWhereHas('instructor', fn ($relation) => $relation->where(fn ($name) => $name
                        ->where('fname', 'like', $term)
                        ->orWhere('sname', 'like', $term)))
                    ->orWhereHas('administrator', fn ($relation) => $relation->where(fn ($name) => $name
                        ->where('fname', 'like', $term)
                        ->orWhere('sname', 'like', $term)));
            });
        }

        return DataTables::of($payrolls)
            ->filter(function () {
                // Search already applied above.
            })
            ->addColumn('employee', fn ($payroll) => e($payroll->employee_name))
            ->addColumn('type', fn ($payroll) => $payroll->employee_type)
            ->addColumn('month', function ($payroll) {
                try {
                    return Carbon::createFromFormat('Y-m', $payroll->payment_month)->format('F Y');
                } catch (\Exception $e) {
                    return $payroll->payment_month;
                }
            })
            ->addColumn('basic_salary', fn ($payroll) => 'K' . number_format((float) $payroll->basic_salary, 2))
            ->addColumn('allowances', fn ($payroll) => 'K' . number_format((float) $payroll->allowances, 2))
            ->addColumn('bonus', function ($payroll) {
                $amount = 'K' . number_format((float) $payroll->bonus, 2);

                return $payroll->instructor_id
                    ? $amount . ' <small class="text-muted">(' . (int) $payroll->bonus_attendances . ' att.)</small>'
                    : $amount;
            })
            ->addColumn('deductions', function ($payroll) {
                $total = (float) ($payroll->deductions ?? 0)
                    + (float) ($payroll->paye ?? 0)
                    + (float) ($payroll->employee_pension ?? 0);

                return 'K' . number_format($total, 2);
            })
            ->addColumn('net_pay', fn ($payroll) => '<strong>K' . number_format((float) $payroll->net_pay, 2) . '</strong>')
            ->addColumn('payment_date', fn ($payroll) => $payroll->payment_date ? $payroll->payment_date->format('d M, Y') : '-')
            ->addColumn('status', function ($payroll) {
                $class = match (strtolower($payroll->status)) {
                    'paid' => 'bg-success',
                    'final' => 'bg-primary',
                    default => 'bg-warning text-dark',
                };

                return '<span class="badge ' . $class . '">' . e(ucfirst($payroll->status)) . '</span>';
            })
            ->addColumn('processed_by', fn ($payroll) => e($payroll->processor->name ?? 'System'))
            ->addColumn('actions', function ($payroll) {
                $payslip = '<a class="dropdown-item btn" href="' . e(url('hrm/payroll/' . $payroll->id . '/payslip')) . '" target="_blank"><i class="fa fa-file-pdf me-3"></i> Download payslip</a>';
                $email = $pay = $edit = $delete = '';

                if (auth()->user()->can('process payroll')) {
                    $emailPayload = htmlspecialchars(json_encode([
                        'id' => $payroll->id,
                        'employee' => $payroll->employee_name,
                    ]), ENT_QUOTES, 'UTF-8');
                    $email = '<a class="dropdown-item btn" data-payslip=\'' . $emailPayload . '\' onclick="emailPayslip(this)"><i class="fa fa-envelope me-3"></i> Email payslip</a>';

                    $payload = htmlspecialchars(json_encode([
                        'id' => $payroll->id,
                        'employee' => $payroll->employee_name,
                        'employee_key' => $payroll->instructor_id
                            ? 'instructor:' . $payroll->instructor_id
                            : 'administrator:' . $payroll->administrator_id,
                        'payment_month' => $payroll->payment_month,
                        'basic_salary' => (float) $payroll->basic_salary,
                        'allowances' => (float) $payroll->allowances,
                        'deductions' => (float) $payroll->deductions,
                        'paye' => (float) ($payroll->paye ?? 0),
                        'employee_pension' => (float) ($payroll->employee_pension ?? 0),
                        'employer_pension' => (float) ($payroll->employer_pension ?? 0),
                        'status' => $payroll->status,
                        'payment_date' => $payroll->payment_date?->format('Y-m-d'),
                    ]), ENT_QUOTES, 'UTF-8');

                    if (strcasecmp($payroll->status, 'Final') === 0) {
                        $pay = '<a class="dropdown-item btn" data-payroll=\'' . $payload . '\' onclick="payPayroll(this)"><i class="fa fa-money-bill-wave me-3"></i> Pay</a>';
                    }
                    if (strcasecmp($payroll->status, 'Paid') !== 0) {
                        $edit = '<a class="dropdown-item btn" data-payroll=\'' . $payload . '\' onclick="openEditPayroll(this)"><i class="fa fa-pencil me-3"></i> Edit</a>';
                    }
                    if (strcasecmp($payroll->status, 'Paid') !== 0) {
                        $delete = '<div class="dropdown-divider"></div><a class="dropdown-item btn text-danger" onclick="openDeletePayroll(\'' . e($payroll->id) . '\')"><i class="fa fa-trash me-3"></i> Delete</a>';
                    }
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $payslip . $email . $pay . $edit . $delete . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['bonus', 'net_pay', 'status', 'actions'])
            ->only(['actions', 'employee', 'type', 'month', 'basic_salary', 'allowances', 'bonus', 'deductions', 'net_pay', 'payment_date', 'status', 'processed_by'])
            ->make(true);
    }

    public function employees(): JsonResponse
    {
        $instructors = Instructor::with('salaryGrade')
            ->whereRaw("LOWER(status) = 'active'")
            ->orderBy('fname')
            ->get()
            ->map(fn ($instructor) => [
                'value' => 'instructor:' . $instructor->id,
                'name' => trim($instructor->fname . ' ' . $instructor->sname),
                'label' => trim($instructor->fname . ' ' . $instructor->sname),
                'basic_salary' => $instructor->effective_basic_salary,
                'paye' => (bool) $instructor->paye,
                'pension_applicable' => (bool) ($instructor->pension_applicable ?? false),
            ]);

        $administrators = Administrator::with('salaryGrade')
            ->whereRaw("LOWER(status) = 'active'")
            ->orderBy('fname')
            ->get()
            ->map(fn ($administrator) => [
                'value' => 'administrator:' . $administrator->id,
                'name' => trim($administrator->fname . ' ' . $administrator->sname),
                'label' => trim($administrator->fname . ' ' . $administrator->sname),
                'basic_salary' => $administrator->effective_basic_salary,
                'paye' => (bool) $administrator->paye,
                'pension_applicable' => (bool) ($administrator->pension_applicable ?? false),
            ]);

        return response()->json($instructors->concat($administrators)->values());
    }

    public function bonusPreview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee' => ['required', 'string', 'regex:/^(instructor|administrator):\S+$/'],
            'payment_month' => ['required', 'date_format:Y-m'],
        ]);

        [$type, $id] = explode(':', $validated['employee'], 2);

        if ($type !== 'instructor') {
            return response()->json(['attendances' => 0, 'rate' => 0, 'bonus' => 0]);
        }

        $instructor = Instructor::findOrFail($id);

        return response()->json($this->instructorBonus($instructor, $validated['payment_month']));
    }

    public function bonusPreviews(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_month' => ['required', 'date_format:Y-m'],
        ]);

        return response()->json([
            'rate' => $this->bonusRate(),
            'attendances' => $this->instructorAttendanceCounts($validated['payment_month']),
        ]);
    }

    public function groups(Request $request): JsonResponse
    {
        $groups = PayrollGroup::withCount('payrolls')
            ->withCount(['payrolls as paid_count' => fn ($query) => $query->where('status', 'Paid')])
            ->withCount(['payrolls as final_count' => fn ($query) => $query->where('status', 'Final')])
            ->withCount(['payrolls as pending_count' => fn ($query) => $query->where('status', 'Pending')])
            ->withSum('payrolls', 'net_pay')
            ->with('processor:id,name');

        $search = trim((string) $request->input('search.value'));
        if ($search !== '') {
            $term = '%' . $search . '%';
            $groups->where(function ($query) use ($term) {
                $query->where('name', 'like', $term)
                    ->orWhere('payment_month', 'like', $term);
            });
        }

        return DataTables::of($groups)
            ->filter(function () {
                // Search already applied above.
            })
            ->addColumn('group', fn ($group) => '<strong>' . e($group->name) . '</strong>')
            ->addColumn('month', function ($group) {
                try {
                    return Carbon::createFromFormat('Y-m', $group->payment_month)->format('F Y');
                } catch (\Exception $e) {
                    return $group->payment_month;
                }
            })
            ->addColumn('employees', fn ($group) => (int) $group->payrolls_count)
            ->addColumn('total_net', fn ($group) => '<strong>K' . number_format((float) $group->payrolls_sum_net_pay, 2) . '</strong>')
            ->addColumn('payment_status', function ($group) {
                $total = (int) $group->payrolls_count;
                $paid = (int) $group->paid_count;
                $final = (int) $group->final_count;

                if ($total > 0 && $paid === $total) {
                    return '<span class="badge bg-success">Paid</span>';
                }

                if ($paid > 0) {
                    return '<span class="badge bg-warning text-dark">Partially paid (' . $paid . '/' . $total . ')</span>';
                }

                if ($total > 0 && $final === $total) {
                    return '<span class="badge bg-primary">Final</span>';
                }

                if ($final > 0) {
                    return '<span class="badge bg-info text-dark">Partially finalized (' . $final . '/' . $total . ')</span>';
                }

                return '<span class="badge bg-secondary">Pending</span>';
            })
            ->addColumn('created_by', fn ($group) => e($group->processor->name ?? 'System'))
            ->addColumn('created_on', fn ($group) => $group->created_at?->format('d M, Y H:i') ?? '-')
            ->addColumn('actions', function ($group) {
                $payload = htmlspecialchars(json_encode([
                    'id' => $group->id,
                    'name' => $group->name,
                    'count' => (int) $group->payrolls_count,
                ]), ENT_QUOTES, 'UTF-8');

                $view = '<a class="dropdown-item btn" data-group=\'' . $payload . '\' onclick="viewPayrollGroup(this)"><i class="fa fa-eye me-3"></i> View payrolls</a>';
                $isPending = (int) $group->payrolls_count > 0
                    && (int) $group->pending_count === (int) $group->payrolls_count;
                $edit = auth()->user()->can('process payroll') && $isPending
                    ? '<a class="dropdown-item btn" data-group=\'' . $payload . '\' onclick="editPayrollGroup(this)"><i class="fa fa-edit me-3"></i> Edit group</a>'
                    : '';
                $isFinal = (int) $group->payrolls_count > 0
                    && (int) $group->final_count === (int) $group->payrolls_count;
                $pay = auth()->user()->can('process payroll') && $isFinal
                    ? '<a class="dropdown-item btn" data-group=\'' . $payload . '\' onclick="payPayrollGroup(this)"><i class="fa fa-money-bill-wave me-3"></i> Pay group</a>'
                    : '';
                $export = auth()->user()->can('process payroll')
                    ? '<a class="dropdown-item" href="' . e(route('payroll.groups.export-standard-bank-csv', $group->id)) . '"><i class="fa fa-file-csv me-3"></i> Export Standard Bank CSV</a>'
                    : '';
                $payeExport = auth()->user()->can('process payroll')
                    ? '<a class="dropdown-item" href="' . e(route('payroll.groups.export-paye-csv', $group->id)) . '"><i class="fa fa-file-csv me-3"></i> Export PAYE CSV</a>'
                    : '';
                $delete = auth()->user()->can('process payroll') && (int) $group->paid_count === 0
                    ? '<a class="dropdown-item btn text-danger" data-group=\'' . $payload . '\' onclick="deletePayrollGroup(this)"><i class="fa fa-trash me-3"></i> Delete</a>'
                    : '';

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $edit . $pay . $view . $export . $payeExport . $delete . '
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['group', 'total_net', 'payment_status', 'actions'])
            ->only(['group', 'month', 'employees', 'total_net', 'payment_status', 'created_by', 'created_on', 'actions'])
            ->make(true);
    }

    public function showGroup(PayrollGroup $payroll_group): JsonResponse
    {
        $payroll_group->load([
            'processor:id,name',
            'payrolls' => fn ($query) => $query->with(['instructor', 'administrator'])->orderBy('created_at'),
        ]);

        $records = $payroll_group->payrolls->map(fn ($payroll) => [
            'employee_key' => $payroll->instructor_id
                ? 'instructor:' . $payroll->instructor_id
                : 'administrator:' . $payroll->administrator_id,
            'employee' => $payroll->employee_name,
            'type' => $payroll->employee_type,
            'basic_salary' => (float) $payroll->basic_salary,
            'allowances' => (float) $payroll->allowances,
            'bonus' => (float) $payroll->bonus,
            'bonus_attendances' => (int) $payroll->bonus_attendances,
            'deductions' => (float) $payroll->deductions,
            'paye' => (float) ($payroll->paye ?? 0),
            'employee_pension' => (float) ($payroll->employee_pension ?? 0),
            'employer_pension' => (float) ($payroll->employer_pension ?? 0),
            'pension_applicable' => (bool) ($payroll->instructor?->pension_applicable ?? $payroll->administrator?->pension_applicable ?? false),
            'net_pay' => (float) $payroll->net_pay,
            'status' => ucfirst($payroll->status),
            'payment_date' => $payroll->payment_date?->format('d M, Y'),
        ])->values();

        try {
            $monthLabel = Carbon::createFromFormat('Y-m', $payroll_group->payment_month)->format('F Y');
        } catch (\Exception $e) {
            $monthLabel = $payroll_group->payment_month;
        }

        $paid = $payroll_group->payrolls->filter(fn ($payroll) => strcasecmp($payroll->status, 'Paid') === 0)->count();
        $final = $payroll_group->payrolls->filter(fn ($payroll) => strcasecmp($payroll->status, 'Final') === 0)->count();
        $pending = $payroll_group->payrolls->filter(fn ($payroll) => strcasecmp($payroll->status, 'Pending') === 0)->count();

        return response()->json([
            'group' => [
                'id' => $payroll_group->id,
                'name' => $payroll_group->name,
                'payment_month' => $payroll_group->payment_month,
                'month' => $monthLabel,
                'created_by' => $payroll_group->processor->name ?? 'System',
                'created_on' => $payroll_group->created_at?->format('d M, Y H:i'),
            ],
            'summary' => [
                'employees' => $records->count(),
                'basic_salary' => (float) $payroll_group->payrolls->sum('basic_salary'),
                'allowances' => (float) $payroll_group->payrolls->sum('allowances'),
                'bonus' => (float) $payroll_group->payrolls->sum('bonus'),
                'deductions' => (float) $payroll_group->payrolls->sum('deductions'),
                'paye' => (float) $payroll_group->payrolls->sum('paye'),
                'employee_pension' => (float) $payroll_group->payrolls->sum('employee_pension'),
                'employer_pension' => (float) $payroll_group->payrolls->sum('employer_pension'),
                'net_pay' => (float) $payroll_group->payrolls->sum('net_pay'),
                'paid' => $paid,
                'final' => $final,
                'pending' => $pending,
            ],
            'records' => $records,
        ]);
    }

    public function destroyGroup(PayrollGroup $payroll_group): JsonResponse
    {
        if ($payroll_group->payrolls()->whereRaw('LOWER(status) = ?', ['paid'])->exists()) {
            return response()->json(['message' => 'Payroll groups containing Paid records cannot be deleted.'], 422);
        }

        DB::transaction(function () use ($payroll_group) {
            $payroll_group->payrolls()->delete();
            $payroll_group->delete();
        });

        return response()->json(['message' => 'Payroll group and its records deleted successfully.']);
    }

    public function exportStandardBankCsv(PayrollGroup $payroll_group)
    {
        abort_unless(auth()->user()?->can('process payroll'), 403);

        $payroll_group->load([
            'payrolls' => fn ($query) => $query->with(['instructor', 'administrator'])->orderBy('created_at'),
        ]);

        $fileName = 'standard-bank-payroll-'
            . Str::slug($payroll_group->name ?: $payroll_group->payment_month)
            . '-' . $payroll_group->payment_month . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ];

        $callback = function () use ($payroll_group) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");

            $bankDirectory = config('bank_directory', []);
            $myReference = $payroll_group->name ?: ('Payroll ' . $payroll_group->payment_month);

            foreach ($payroll_group->payrolls as $payroll) {
                $employee = $payroll->instructor ?? $payroll->administrator;
                $employeeName = $employee
                    ? trim(($employee->fname ?? '') . ' ' . ($employee->mname ? $employee->mname . ' ' : '') . ($employee->sname ?? ''))
                    : $payroll->employee_name;

                $bankName = (string) ($employee->bank_name ?? '');
                $bankBranch = (string) ($employee->bank_branch ?? '');
                $bankDetails = $bankDirectory[$bankName]['branches'][$bankBranch] ?? [];

                $accountNumber = (string) ($employee->bank_account_number ?? '');
                $sortCodeValue = (string) ($bankDetails['sort_code'] ?? ($bankDirectory[$bankName]['sort_code'] ?? ''));
                $bankReferenceValue = (string) ($bankDetails['reference_number'] ?? '');

                fputcsv($handle, [
                    $accountNumber !== '' ? '#' . ltrim($accountNumber, '#') : '',
                    number_format((float) $payroll->net_pay, 2, '.', ''),
                    $employee->bank_account_name ?? $employeeName,
                    $myReference,
                    $employeeName . ' ' . $payroll_group->payment_month,
                    $sortCodeValue !== '' ? '#' . ltrim($sortCodeValue, '#') : '',
                    $bankReferenceValue !== '' ? '#' . ltrim($bankReferenceValue, '#') : '',
                    '',
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    public function exportPayeCsv(PayrollGroup $payroll_group)
    {
        abort_unless(auth()->user()?->can('process payroll'), 403);

        $payroll_group->load([
            'payrolls' => fn ($query) => $query->with(['instructor', 'administrator'])->orderBy('created_at'),
        ]);

        $fileName = 'paye-payroll-'
            . Str::slug($payroll_group->name ?: $payroll_group->payment_month)
            . '-' . $payroll_group->payment_month . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ];

        $callback = function () use ($payroll_group) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, [
                'Employee name',
                'Employee type',
                'Payment month',
                'Basic salary',
                'Allowances',
                'Bonus',
                'Bonus attendances',
                'Gross earnings',
                'Other deductions',
                'PAYE',
                'Employee pension',
                'Employer pension',
                'Net pay',
            ]);

            foreach ($payroll_group->payrolls as $payroll) {
                $basic = (float) ($payroll->basic_salary ?? 0);
                $allowances = (float) ($payroll->allowances ?? 0);
                $bonus = (float) ($payroll->bonus ?? 0);
                $gross = round($basic + $allowances + $bonus, 2);

                fputcsv($handle, [
                    $payroll->employee_name,
                    $payroll->employee_type,
                    $payroll->payment_month,
                    number_format($basic, 2, '.', ''),
                    number_format($allowances, 2, '.', ''),
                    number_format($bonus, 2, '.', ''),
                    (int) ($payroll->bonus_attendances ?? 0),
                    number_format($gross, 2, '.', ''),
                    number_format((float) ($payroll->deductions ?? 0), 2, '.', ''),
                    number_format((float) ($payroll->paye ?? 0), 2, '.', ''),
                    number_format((float) ($payroll->employee_pension ?? 0), 2, '.', ''),
                    number_format((float) ($payroll->employer_pension ?? 0), 2, '.', ''),
                    number_format((float) ($payroll->net_pay ?? 0), 2, '.', ''),
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $fileName, $headers);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_month' => ['required', 'date_format:Y-m'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Pending', 'Final', 'Paid'])],
            'payment_date' => ['nullable', 'date', 'required_if:status,Paid'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.employee' => ['required', 'string', 'distinct', 'regex:/^(instructor|administrator):\S+$/'],
            'employees.*.basic_salary' => ['required', 'numeric', 'min:0'],
            'employees.*.allowances' => ['nullable', 'numeric', 'min:0'],
            'employees.*.deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rows = collect($validated['employees'])->map(function ($row) {
            [$type, $id] = explode(':', $row['employee'], 2);
            $row['type'] = $type;
            $row['id'] = $id;

            return $row;
        });

        $instructors = Instructor::findOrFail($rows->where('type', 'instructor')->pluck('id'))->keyBy(fn ($instructor) => (string) $instructor->getKey());
        $administrators = Administrator::findOrFail($rows->where('type', 'administrator')->pluck('id'))->keyBy(fn ($administrator) => (string) $administrator->getKey());

        $inactive = $rows
            ->map(function ($row) use ($instructors, $administrators) {
                $employee = $row['type'] === 'instructor'
                    ? $instructors[(string) $row['id']]
                    : $administrators[(string) $row['id']];

                return strcasecmp((string) $employee->status, 'active') === 0
                    ? null
                    : trim($employee->fname . ' ' . $employee->sname);
            })
            ->filter();

        if ($inactive->isNotEmpty()) {
            return response()->json([
                'message' => 'These employees are not active and cannot be added to payroll: '
                    . $inactive->implode(', ') . '.',
            ], 422);
        }

        $existing = Payroll::where('payment_month', $validated['payment_month'])
            ->where(function ($query) use ($instructors, $administrators) {
                $query->whereIn('instructor_id', $instructors->keys())
                    ->orWhereIn('administrator_id', $administrators->keys());
            })
            ->with(['instructor', 'administrator'])
            ->get();

        if ($existing->isNotEmpty()) {
            return response()->json([
                'message' => 'These employees already have payroll records for this month: '
                    . $existing->map(fn ($payroll) => $payroll->employee_name)->implode(', ') . '.',
            ], 422);
        }

        $rate = $this->bonusRate();
        $attendanceCounts = $this->instructorAttendanceCounts($validated['payment_month']);
        $monthLabel = Carbon::createFromFormat('Y-m', $validated['payment_month'])->format('F Y');
        $payeConfig = $this->payeConfig();
        $pensionConfig = $this->pensionConfig();

        $group = DB::transaction(function () use ($validated, $rows, $instructors, $administrators, $rate, $attendanceCounts, $monthLabel, $payeConfig, $pensionConfig) {
            $group = PayrollGroup::create([
                'name' => trim($validated['group_name'] ?? '') !== '' ? trim($validated['group_name']) : 'Payroll ' . $monthLabel,
                'payment_month' => $validated['payment_month'],
                'created_by' => Auth::id(),
            ]);

            foreach ($rows as $row) {
                $attendances = $row['type'] === 'instructor' ? (int) ($attendanceCounts[(string) $row['id']] ?? $attendanceCounts[$row['id']] ?? 0) : 0;
                $bonus = round($attendances * $rate, 2);
                $employee = $row['type'] === 'instructor'
                    ? $instructors[(string) $row['id']]
                    : $administrators[(string) $row['id']];
                $amounts = $this->payrollAmounts(
                    $row,
                    $bonus,
                    $row['type'] === 'instructor',
                    (bool) ($employee->paye ?? false),
                    $payeConfig,
                    (bool) ($employee->pension_applicable ?? false),
                    $pensionConfig
                );

                Payroll::create([
                    'instructor_id' => $row['type'] === 'instructor' ? $row['id'] : null,
                    'administrator_id' => $row['type'] === 'administrator' ? $row['id'] : null,
                    'payroll_group_id' => $group->id,
                    'payment_month' => $validated['payment_month'],
                    'basic_salary' => $row['basic_salary'],
                    'allowances' => $row['allowances'] ?? 0,
                    'bonus' => $bonus,
                    'bonus_attendances' => $attendances,
                    'deductions' => $row['deductions'] ?? 0,
                    'paye' => $amounts['paye'],
                    'employee_pension' => $amounts['employee_pension'] ?? 0,
                    'employer_pension' => $amounts['employer_pension'] ?? 0,
                    'net_pay' => $amounts['net_pay'],
                    'payment_date' => $validated['payment_date'] ?? null,
                    'status' => $validated['status'],
                    'created_by' => Auth::id(),
                ]);
            }

            return $group;
        });

        return response()->json([
            'message' => 'Payroll group created with ' . $rows->count() . ' employee record' . ($rows->count() === 1 ? '' : 's') . '.',
            'group' => $group,
        ]);
    }

    public function updateGroup(Request $request, PayrollGroup $payroll_group): JsonResponse
    {
        if (
            !$payroll_group->payrolls()->exists()
            || $payroll_group->payrolls()->whereRaw('LOWER(status) != ?', ['pending'])->exists()
        ) {
            return response()->json([
                'message' => 'Only payroll groups with all entries Pending can be edited.',
            ], 422);
        }

        $validated = $request->validate([
            'payment_month' => ['required', 'date_format:Y-m'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Pending', 'Final', 'Paid'])],
            'payment_date' => ['nullable', 'date', 'required_if:status,Paid'],
            'employees' => ['required', 'array', 'min:1'],
            'employees.*.employee' => ['required', 'string', 'distinct', 'regex:/^(instructor|administrator):\S+$/'],
            'employees.*.basic_salary' => ['required', 'numeric', 'min:0'],
            'employees.*.allowances' => ['nullable', 'numeric', 'min:0'],
            'employees.*.deductions' => ['nullable', 'numeric', 'min:0'],
        ]);

        $rows = collect($validated['employees'])->map(function ($row) {
            [$type, $id] = explode(':', $row['employee'], 2);
            $row['type'] = $type;
            $row['id'] = $id;

            return $row;
        });

        $currentPayrolls = $payroll_group->payrolls;
        $currentEmployeeKeys = $currentPayrolls->map(fn ($payroll) => $payroll->instructor_id
            ? 'instructor:' . $payroll->instructor_id
            : 'administrator:' . $payroll->administrator_id)->all();
        $instructors = Instructor::withTrashed()
            ->findOrFail($rows->where('type', 'instructor')->pluck('id'))
            ->keyBy('id');
        $administrators = Administrator::withTrashed()
            ->findOrFail($rows->where('type', 'administrator')->pluck('id'))
            ->keyBy(fn ($administrator) => (string) $administrator->getKey());

        $inactive = $rows
            ->map(function ($row) use ($instructors, $administrators, $currentEmployeeKeys) {
                if (in_array($row['employee'], $currentEmployeeKeys, true)) {
                    return null;
                }

                $employee = $row['type'] === 'instructor'
                    ? $instructors[$row['id']]
                    : $administrators[(string) $row['id']];

                return strcasecmp((string) $employee->status, 'active') === 0
                    ? null
                    : trim($employee->fname . ' ' . $employee->sname);
            })
            ->filter();

        if ($inactive->isNotEmpty()) {
            return response()->json([
                'message' => 'These employees are not active and cannot be added to payroll: '
                    . $inactive->implode(', ') . '.',
            ], 422);
        }

        $instructorIds = $rows->where('type', 'instructor')->pluck('id');
        $administratorIds = $rows->where('type', 'administrator')->pluck('id');
        $existing = Payroll::withTrashed()
            ->where('payment_month', $validated['payment_month'])
            ->where(function ($query) use ($instructorIds, $administratorIds) {
                $query->whereIn('instructor_id', $instructorIds)
                    ->orWhereIn('administrator_id', $administratorIds);
            })
            ->where(function ($query) use ($payroll_group) {
                $query->whereNull('payroll_group_id')
                    ->orWhere('payroll_group_id', '!=', $payroll_group->id);
            })
            ->with([
                'instructor' => fn ($query) => $query->withTrashed(),
                'administrator' => fn ($query) => $query->withTrashed(),
            ])
            ->get();

        if ($existing->isNotEmpty()) {
            return response()->json([
                'message' => 'These employees already have payroll records for this month: '
                    . $existing->map(fn ($payroll) => $payroll->employee_name)->implode(', ') . '.',
            ], 422);
        }

        $rate = $this->bonusRate();
        $attendanceCounts = $this->instructorAttendanceCounts($validated['payment_month']);
        $monthLabel = Carbon::createFromFormat('Y-m', $validated['payment_month'])->format('F Y');
        $payeConfig = $this->payeConfig();
        $pensionConfig = $this->pensionConfig();

        DB::transaction(function () use (
            $validated,
            $rows,
            $payroll_group,
            $currentPayrolls,
            $rate,
            $attendanceCounts,
            $monthLabel,
            $payeConfig,
            $pensionConfig,
            $instructors,
            $administrators
        ) {
            $payroll_group->update([
                'name' => trim($validated['group_name'] ?? '') !== ''
                    ? trim($validated['group_name'])
                    : 'Payroll ' . $monthLabel,
                'payment_month' => $validated['payment_month'],
            ]);

            $currentByEmployee = $currentPayrolls->keyBy(fn ($payroll) => $payroll->instructor_id
                ? 'instructor:' . $payroll->instructor_id
                : 'administrator:' . $payroll->administrator_id);
            $retainedPayrollIds = [];

            foreach ($rows as $row) {
                $employeeKey = $row['employee'];
                $isInstructor = $row['type'] === 'instructor';
                $attendances = $isInstructor ? (int) ($attendanceCounts[$row['id']] ?? 0) : 0;
                $bonus = round($attendances * $rate, 2);
                $payroll = $currentByEmployee->get($employeeKey) ?? new Payroll();
                $employee = $isInstructor
                    ? $instructors[$row['id']]
                    : $administrators[(string) $row['id']];
                $amounts = $this->payrollAmounts(
                    $row,
                    $bonus,
                    $isInstructor,
                    (bool) ($employee->paye ?? false),
                    $payeConfig,
                    (bool) ($employee->pension_applicable ?? false),
                    $pensionConfig
                );

                $payroll->instructor_id = $isInstructor ? $row['id'] : null;
                $payroll->administrator_id = $isInstructor ? null : $row['id'];
                $payroll->payroll_group_id = $payroll_group->id;
                $payroll->payment_month = $validated['payment_month'];
                $payroll->basic_salary = $row['basic_salary'];
                $payroll->allowances = $row['allowances'] ?? 0;
                $payroll->bonus = $bonus;
                $payroll->bonus_attendances = $attendances;
                $payroll->deductions = $row['deductions'] ?? 0;
                $payroll->paye = $amounts['paye'];
                $payroll->employee_pension = $amounts['employee_pension'] ?? 0;
                $payroll->employer_pension = $amounts['employer_pension'] ?? 0;
                $payroll->net_pay = $amounts['net_pay'];
                $payroll->payment_date = $validated['status'] === 'Paid' ? $validated['payment_date'] : null;
                $payroll->status = $validated['status'];
                $payroll->created_by = $payroll->created_by ?? Auth::id();
                $payroll->save();

                $retainedPayrollIds[] = $payroll->id;
            }

            $payroll_group->payrolls()->whereNotIn('id', $retainedPayrollIds)->delete();
        });

        return response()->json(['message' => 'Payroll group updated successfully.']);
    }

    public function pay(Request $request, Payroll $payroll): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
        ]);

        if (strcasecmp($payroll->status, 'Final') !== 0) {
            return response()->json(['message' => 'Only Final payroll records can be paid.'], 422);
        }

        $payroll->update([
            'status' => 'Paid',
            'payment_date' => $validated['payment_date'],
        ]);

        return response()->json(['message' => 'Payroll marked as Paid.']);
    }

    public function payGroup(Request $request, PayrollGroup $payroll_group): JsonResponse
    {
        $validated = $request->validate([
            'payment_date' => ['required', 'date_format:Y-m-d'],
        ]);
        $payrolls = $payroll_group->payrolls()->get();

        if (
            $payrolls->isEmpty()
            || $payrolls->contains(fn ($payroll) => strcasecmp($payroll->status, 'Final') !== 0)
        ) {
            return response()->json(['message' => 'Only groups with all entries Final can be paid as a group.'], 422);
        }

        DB::transaction(function () use ($payrolls, $validated) {
            foreach ($payrolls as $payroll) {
                $payroll->update([
                    'status' => 'Paid',
                    'payment_date' => $validated['payment_date'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Payroll group paid with ' . $payrolls->count() . ' record' . ($payrolls->count() === 1 ? '' : 's') . '.',
        ]);
    }

    public function update(Request $request, Payroll $payroll): JsonResponse
    {
        if (strcasecmp($payroll->status, 'Paid') === 0) {
            return response()->json(['message' => 'Paid payroll records cannot be edited.'], 422);
        }

        $validated = $this->validatePayroll($request);

        $bonus = $payroll->instructor
            ? $this->instructorBonus($payroll->instructor, $payroll->payment_month)
            : ['attendances' => (int) $payroll->bonus_attendances, 'rate' => 0, 'bonus' => (float) $payroll->bonus];
        $employee = $payroll->instructor ?? $payroll->administrator;
        $amounts = $this->payrollAmounts(
            $validated,
            (float) $bonus['bonus'],
            $payroll->instructor_id !== null,
            (bool) ($employee->paye ?? false),
            $this->payeConfig(),
            (bool) ($employee->pension_applicable ?? false),
            $this->pensionConfig()
        );

        $payroll->update([
            'basic_salary' => $validated['basic_salary'],
            'allowances' => $validated['allowances'] ?? 0,
            'bonus' => $bonus['bonus'],
            'bonus_attendances' => $bonus['attendances'],
            'deductions' => $validated['deductions'] ?? 0,
            'paye' => $amounts['paye'],
            'employee_pension' => $amounts['employee_pension'] ?? 0,
            'employer_pension' => $amounts['employer_pension'] ?? 0,
            'net_pay' => $amounts['net_pay'],
            'payment_date' => $validated['payment_date'] ?? null,
            'status' => $validated['status'],
        ]);

        return response()->json([
            'message' => 'Payroll record updated successfully.',
            'payroll' => $payroll->fresh(),
        ]);
    }

    public function destroy(Payroll $payroll): JsonResponse
    {
        if (strcasecmp($payroll->status, 'Paid') === 0) {
            return response()->json(['message' => 'Paid payroll records cannot be deleted.'], 422);
        }

        $payroll->delete();

        return response()->json(['message' => 'Payroll record deleted successfully.']);
    }

    public function payslip(Payroll $payroll)
    {
        [$pdf, $fileName] = $this->payslipPdf($payroll);

        return $pdf->download($fileName);
    }

    public function emailPayslip(Payroll $payroll): JsonResponse
    {
        $payroll->load(['instructor.user', 'administrator.User']);
        $user = $payroll->instructor?->user ?? $payroll->administrator?->User;

        if (!$user || !$user->email) {
            return response()->json([
                'message' => 'No account with an email address is linked to this employee.',
            ], 422);
        }

        [$pdf, $fileName, $monthLabel] = $this->payslipPdf($payroll);

        try {
            $user->notify(new PayslipIssued(
                $monthLabel,
                $pdf->output(),
                $fileName,
                number_format((float) $payroll->net_pay, 2)
            ));
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Payslip email delivery failed.', ['payroll_id' => $payroll->getKey()]);

            return response()->json([
                'message' => 'The payslip email could not be delivered. Verify the mail service settings and try again.',
            ], 502);
        }

        return response()->json(['message' => 'Payslip emailed to ' . $user->email . '.']);
    }

    private function payslipPdf(Payroll $payroll): array
    {
        $payroll->load([
            'instructor.designation', 'instructor.hrDepartment',
            'administrator.designation', 'administrator.hrDepartment',
            'processor', 'group',
        ]);
        $setting = Setting::first();

        try {
            $monthLabel = Carbon::createFromFormat('Y-m', $payroll->payment_month)->format('F Y');
        } catch (\Exception $e) {
            $monthLabel = $payroll->payment_month;
        }

        $pdf = PDF::loadView('pdf_templates.payslip', compact('payroll', 'setting', 'monthLabel'));
        $fileName = 'Payslip-' . str_replace(' ', '-', $payroll->employee_name) . '-' . $payroll->payment_month . '.pdf';

        return [$pdf, $fileName, $monthLabel];
    }

    private function validatePayroll(Request $request): array
    {
        return $request->validate([
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'allowances' => ['nullable', 'numeric', 'min:0'],
            'deductions' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['Pending', 'Final', 'Paid'])],
            'payment_date' => ['nullable', 'date', 'required_if:status,Paid'],
        ]);
    }

    private function grossSalary(array $row, float $bonus, bool $isInstructor = false): float
    {
        $basicSalary = (float) ($row['basic_salary'] ?? 0);
        $gross = $isInstructor
            ? max($basicSalary, $bonus)
            : $basicSalary + $bonus;

        return $gross + (float) ($row['allowances'] ?? 0);
    }

    private function payeAmount(float $taxableAmount, bool $payeApplicable, ?array $payeConfig = null): float
    {
        $payeConfig ??= $this->payeConfig();

        if (!$payeApplicable || !($payeConfig['enabled'] ?? true) || $taxableAmount <= 0) {
            return 0.0;
        }

        $bands = $payeConfig['bands'] ?? [];
        if (is_array($bands) && $bands !== []) {
            $remaining = $taxableAmount;
            $previousLimit = 0.0;
            $tax = 0.0;
            foreach ($bands as $band) {
                $limit = $band['up_to'] ?? null;
                $rate = (float) ($band['rate'] ?? 0);
                if ($limit === null) {
                    $tax += max($remaining, 0) * $rate;
                    $remaining = 0;
                    break;
                }
                $bandAmount = max(min($taxableAmount, (float) $limit) - $previousLimit, 0);
                $tax += $bandAmount * $rate;
                $previousLimit = (float) $limit;
                $remaining = $taxableAmount - $previousLimit;
                if ($remaining <= 0) {
                    break;
                }
            }
            return round(max($tax, 0), 2);
        }

        $rate = (float) ($payeConfig['rate'] ?? 0);

        return round(max($taxableAmount * $rate, 0), 2);
    }

    private function pensionAmounts(float $basicSalary, bool $pensionApplicable, ?array $pensionConfig = null): array
    {
        $pensionConfig ??= $this->pensionConfig();
        $basic = max($basicSalary, 0);

        if (!$pensionApplicable || !($pensionConfig['enabled'] ?? true) || $basic <= 0) {
            return ['employee' => 0.0, 'employer' => 0.0];
        }

        $employeeRate = (float) ($pensionConfig['employee_rate'] ?? 0);
        $employerRate = (float) ($pensionConfig['employer_rate'] ?? 0);

        return [
            'employee' => round(max($basic * $employeeRate, 0), 2),
            'employer' => round(max($basic * $employerRate, 0), 2),
        ];
    }

    private function payrollAmounts(array $row, float $bonus, bool $isInstructor = false, bool $payeApplicable = false, ?array $payeConfig = null, bool $pensionApplicable = false, ?array $pensionConfig = null): array
    {
        $gross = $this->grossSalary($row, $bonus, $isInstructor);
        $paye = $this->payeAmount($gross, $payeApplicable, $payeConfig);
        $pension = $this->pensionAmounts((float) ($row['basic_salary'] ?? 0), $pensionApplicable, $pensionConfig);
        $deductions = (float) ($row['deductions'] ?? 0);
        $netPay = round($gross - $deductions - $paye - $pension['employee'], 2);

        return [
            'gross' => round($gross, 2),
            'paye' => $paye,
            'employee_pension' => $pension['employee'],
            'employer_pension' => $pension['employer'],
            'net_pay' => $netPay,
        ];
    }

    private function instructorBonus(Instructor $instructor, string $month): array
    {
        $rate = $this->bonusRate();
        $attendances = (int) ($this->instructorAttendanceCounts($month, [$instructor->id])[$instructor->id] ?? 0);

        return [
            'attendances' => $attendances,
            'rate' => $rate,
            'bonus' => round($attendances * $rate, 2),
        ];
    }

    private function bonusRate(): float
    {
        return (float) (Setting::query()->value('bonus') ?? 0);
    }

    /**
     * Attendance counts per instructor for the given pay month, keyed by instructor id.
     */
    private function instructorAttendanceCounts(string $month, ?array $instructorIds = null): array
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return Attendance::whereNotNull('instructor_id')
            ->when($instructorIds !== null, fn ($query) => $query->whereIn('instructor_id', $instructorIds))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('instructor_id')
            ->selectRaw('instructor_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'instructor_id')
            ->all();
    }
}
