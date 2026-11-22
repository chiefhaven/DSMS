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
        $this->middleware('permission:process payroll')->only(['store', 'update', 'destroy', 'destroyGroup', 'emailPayslip']);
    }

    public function index()
    {
        return view('hrm.payroll');
    }

    public function fetch(Request $request): JsonResponse
    {
        $payrolls = Payroll::with(['instructor', 'administrator', 'processor']);

        if ($request->filled('month')) {
            $payrolls->where('payment_month', $request->input('month'));
        }

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
            ->addColumn('deductions', fn ($payroll) => 'K' . number_format((float) $payroll->deductions, 2))
            ->addColumn('net_pay', fn ($payroll) => '<strong>K' . number_format((float) $payroll->net_pay, 2) . '</strong>')
            ->addColumn('payment_date', fn ($payroll) => $payroll->payment_date ? $payroll->payment_date->format('d M, Y') : '-')
            ->addColumn('status', function ($payroll) {
                $class = strcasecmp($payroll->status, 'Paid') === 0 ? 'bg-success' : 'bg-warning text-dark';

                return '<span class="badge ' . $class . '">' . e(ucfirst($payroll->status)) . '</span>';
            })
            ->addColumn('processed_by', fn ($payroll) => e($payroll->processor->name ?? 'System'))
            ->addColumn('actions', function ($payroll) {
                $payslip = '<a class="dropdown-item btn" href="' . e(url('hrm/payroll/' . $payroll->id . '/payslip')) . '" target="_blank"><i class="fa fa-file-pdf me-3"></i> Download payslip</a>';
                $email = $edit = $delete = '';

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
                        'status' => $payroll->status,
                        'payment_date' => $payroll->payment_date?->format('Y-m-d'),
                    ]), ENT_QUOTES, 'UTF-8');

                    $edit = '<a class="dropdown-item btn" data-payroll=\'' . $payload . '\' onclick="openEditPayroll(this)"><i class="fa fa-pencil me-3"></i> Edit</a>';
                    $delete = '<div class="dropdown-divider"></div><a class="dropdown-item btn text-danger" onclick="openDeletePayroll(\'' . e($payroll->id) . '\')"><i class="fa fa-trash me-3"></i> Delete</a>';
                }

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $payslip . $email . $edit . $delete . '
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
                'label' => trim($instructor->fname . ' ' . $instructor->sname) . ' — Instructor',
                'basic_salary' => $instructor->effective_basic_salary,
            ]);

        $administrators = Administrator::with('salaryGrade')
            ->whereRaw("LOWER(status) = 'active'")
            ->orderBy('fname')
            ->get()
            ->map(fn ($administrator) => [
                'value' => 'administrator:' . $administrator->id,
                'label' => trim($administrator->fname . ' ' . $administrator->sname) . ' — Administrator',
                'basic_salary' => $administrator->effective_basic_salary,
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

                if ($total > 0 && $paid === $total) {
                    return '<span class="badge bg-success">Paid</span>';
                }

                if ($paid > 0) {
                    return '<span class="badge bg-warning text-dark">Partially paid (' . $paid . '/' . $total . ')</span>';
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
                $delete = auth()->user()->can('process payroll')
                    ? '<a class="dropdown-item btn text-danger" data-group=\'' . $payload . '\' onclick="deletePayrollGroup(this)"><i class="fa fa-trash me-3"></i> Delete</a>'
                    : '';

                return '
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" data-bs-toggle="dropdown"><i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>
                        <div class="dropdown-menu dropdown-menu-end">
                            ' . $view . $delete . '
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
            'employee' => $payroll->employee_name,
            'type' => $payroll->employee_type,
            'basic_salary' => (float) $payroll->basic_salary,
            'allowances' => (float) $payroll->allowances,
            'bonus' => (float) $payroll->bonus,
            'bonus_attendances' => (int) $payroll->bonus_attendances,
            'deductions' => (float) $payroll->deductions,
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

        return response()->json([
            'group' => [
                'id' => $payroll_group->id,
                'name' => $payroll_group->name,
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
                'net_pay' => (float) $payroll_group->payrolls->sum('net_pay'),
                'paid' => $paid,
                'pending' => $records->count() - $paid,
            ],
            'records' => $records,
        ]);
    }

    public function destroyGroup(PayrollGroup $payroll_group): JsonResponse
    {
        DB::transaction(function () use ($payroll_group) {
            $payroll_group->payrolls()->delete();
            $payroll_group->delete();
        });

        return response()->json(['message' => 'Payroll group and its records deleted successfully.']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_month' => ['required', 'date_format:Y-m'],
            'group_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Pending', 'Paid'])],
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

        $instructors = Instructor::findOrFail($rows->where('type', 'instructor')->pluck('id'))->keyBy('id');
        $administrators = Administrator::findOrFail($rows->where('type', 'administrator')->pluck('id'))->keyBy(fn ($administrator) => (string) $administrator->getKey());

        $inactive = $rows
            ->map(function ($row) use ($instructors, $administrators) {
                $employee = $row['type'] === 'instructor'
                    ? $instructors[$row['id']]
                    : $administrators[$row['id']];

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

        $group = DB::transaction(function () use ($validated, $rows, $rate, $attendanceCounts, $monthLabel) {
            $group = PayrollGroup::create([
                'name' => trim($validated['group_name'] ?? '') !== '' ? trim($validated['group_name']) : 'Payroll ' . $monthLabel,
                'payment_month' => $validated['payment_month'],
                'created_by' => Auth::id(),
            ]);

            foreach ($rows as $row) {
                $attendances = $row['type'] === 'instructor' ? (int) ($attendanceCounts[$row['id']] ?? 0) : 0;
                $bonus = round($attendances * $rate, 2);

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
                    'net_pay' => $this->netPay($row, $bonus),
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

    public function update(Request $request, Payroll $payroll): JsonResponse
    {
        $validated = $this->validatePayroll($request);

        $bonus = $payroll->instructor
            ? $this->instructorBonus($payroll->instructor, $payroll->payment_month)
            : ['attendances' => (int) $payroll->bonus_attendances, 'rate' => 0, 'bonus' => (float) $payroll->bonus];

        $payroll->update([
            'basic_salary' => $validated['basic_salary'],
            'allowances' => $validated['allowances'] ?? 0,
            'bonus' => $bonus['bonus'],
            'bonus_attendances' => $bonus['attendances'],
            'deductions' => $validated['deductions'] ?? 0,
            'net_pay' => $this->netPay($validated, $bonus['bonus']),
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
            'status' => ['required', Rule::in(['Pending', 'Paid'])],
            'payment_date' => ['nullable', 'date', 'required_if:status,Paid'],
        ]);
    }

    private function netPay(array $validated, float $bonus): float
    {
        return round(
            (float) $validated['basic_salary']
            + (float) ($validated['allowances'] ?? 0)
            + $bonus
            - (float) ($validated['deductions'] ?? 0),
            2
        );
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
