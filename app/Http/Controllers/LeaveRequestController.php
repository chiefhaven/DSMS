<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class LeaveRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorizeReview();

        $leaveTypes = collect();
        $leaveRequests = collect();
        if ($this->leaveTablesReady()) {
            $leaveTypes = LeaveType::query()->orderBy('name')->get();
            $leaveRequests = LeaveRequest::query()
                ->with(['instructor', 'administrator', 'leaveType', 'reviewer'])
                ->latest()
                ->get();
        }

        return view('hrm.leaves', compact('leaveTypes', 'leaveRequests'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->instructor_id || $user->administrator_id), 403, 'Employee profile not found.');
        abort_unless($this->leaveTablesReady(), 503, 'Leave management is not available yet. Please run migrations.');

        $validated = $request->validate([
            'leave_type_id' => 'nullable|uuid|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:2000',
        ]);

        $start = Carbon::parse($validated['start_date']);
        $end = Carbon::parse($validated['end_date']);
        $days = $start->diffInDays($end) + 1;

        $payload = [
            'leave_type_id' => $validated['leave_type_id'] ?? null,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $days,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ];

        if ($user->instructor_id) {
            $payload['instructor_id'] = $user->instructor_id;
            $payload['administrator_id'] = null;
        } else {
            $payload['instructor_id'] = null;
            $payload['administrator_id'] = $user->administrator_id;
        }

        LeaveRequest::create($payload);

        return redirect()->route('instructor.my-hrm')
            ->with('success', 'Leave request submitted successfully.')
            ->with('hrm_active_tab', 'leave');
    }

    public function storeType(Request $request)
    {
        $this->authorizeReview();
        abort_unless($this->leaveTablesReady(), 503, 'Leave management is not available yet. Please run migrations.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        LeaveType::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('leaves.index')->with('success', 'Leave type added successfully.');
    }

    public function updateType(Request $request, LeaveType $leaveType)
    {
        $this->authorizeReview();
        abort_unless($this->leaveTablesReady(), 503, 'Leave management is not available yet. Please run migrations.');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $leaveType->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('leaves.index')->with('success', 'Leave type updated successfully.');
    }

    public function destroyType(LeaveType $leaveType)
    {
        $this->authorizeReview();
        abort_unless($this->leaveTablesReady(), 503, 'Leave management is not available yet. Please run migrations.');

        $leaveType->delete();

        return redirect()->route('leaves.index')->with('success', 'Leave type deleted successfully.');
    }

    public function review(Request $request, LeaveRequest $leaveRequest)
    {
        $this->authorizeReview();
        abort_unless($this->leaveTablesReady(), 503, 'Leave management is not available yet. Please run migrations.');

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:2000',
        ]);

        $leaveRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
        ]);

        return redirect()->route('leaves.index')->with('success', 'Leave request ' . $validated['status'] . ' successfully.');
    }

    public function cancel(Request $request, LeaveRequest $leaveRequest)
    {
        $user = $request->user();
        $ownsAsInstructor = $user && $user->instructor_id && (string) $leaveRequest->instructor_id === (string) $user->instructor_id;
        $ownsAsAdministrator = $user && $user->administrator_id && (string) $leaveRequest->administrator_id === (string) $user->administrator_id;
        abort_unless($ownsAsInstructor || $ownsAsAdministrator, 403);
        abort_unless($leaveRequest->status === 'pending', 422, 'Only pending leave requests can be cancelled.');

        $leaveRequest->update(['status' => 'cancelled']);

        return redirect()->route('instructor.my-hrm')
            ->with('success', 'Leave request cancelled.')
            ->with('hrm_active_tab', 'leave');
    }

    private function authorizeReview(): void
    {
        $user = auth()->user();
        $allowed = $user && (
            $user->hasRole('superAdmin')
            || $user->hasRole('administrator')
            || $user->administrator_id
            || $user->can('process payroll')
            || $user->can('edit instructors')
            || $user->can('manage administrator accounts')
        );
        abort_unless($allowed, 403, 'You are not authorized to review leave requests.');
    }

    private function leaveTablesReady(): bool
    {
        return Schema::hasTable('leave_types') && Schema::hasTable('leave_requests');
    }
}
