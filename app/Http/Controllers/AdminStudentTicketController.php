<?php

namespace App\Http\Controllers;

use App\Models\StudentTicket;
use App\Models\User;
use App\Services\StudentTicketNotificationService;
use App\Services\StudentTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AdminStudentTicketController extends Controller
{
    public function __construct(
        private StudentTicketService $tickets,
        private StudentTicketNotificationService $notifications
    ) {
    }

    public function index(Request $request): View|JsonResponse
    {
        $dateToRules = ['nullable', 'date_format:Y-m-d'];
        if ($request->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(StudentTicket::STATUSES))],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => $dateToRules,
        ]);

        if ($request->ajax()) {
            $search = mb_substr(trim((string) $request->input('search.value', '')), 0, 100);
            $matchingCategories = collect(StudentTicket::CATEGORIES)
                ->filter(fn ($label) => stripos($label, $search) !== false)
                ->keys()
                ->all();
            $matchingStatuses = collect(StudentTicket::STATUSES)
                ->filter(fn ($label) => stripos($label, $search) !== false)
                ->keys()
                ->all();

            $query = StudentTicket::query()
                ->with(['student', 'assignee'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));

            $dataTable = DataTables::eloquent($query)
                ->filter(function ($query) use ($search, $matchingCategories, $matchingStatuses) {
                    if ($search === '') {
                        return;
                    }

                    $query->where(function ($query) use ($search, $matchingCategories, $matchingStatuses) {
                        $query->where('subject', 'like', '%' . $search . '%')
                            ->orWhere('id', 'like', '%' . $search . '%')
                            ->orWhere('category', 'like', '%' . $search . '%')
                            ->orWhereIn('category', $matchingCategories)
                            ->orWhere('status', 'like', '%' . $search . '%')
                            ->orWhereIn('status', $matchingStatuses)
                            ->orWhereHas('student', function ($studentQuery) use ($search) {
                                $studentQuery->where('fname', 'like', '%' . $search . '%')
                                    ->orWhere('mname', 'like', '%' . $search . '%')
                                    ->orWhere('sname', 'like', '%' . $search . '%');
                            })
                            ->orWhereHas('assignee', fn ($assigneeQuery) => $assigneeQuery->where('name', 'like', '%' . $search . '%'));
                    });
                }, false)
                ->addColumn('ticket', fn (StudentTicket $ticket) => '#' . strtoupper(substr($ticket->id, 0, 8)))
                ->addColumn('student', fn (StudentTicket $ticket) => trim(($ticket->student?->fname ?? '') . ' ' . ($ticket->student?->sname ?? '')))
                ->editColumn('category', fn (StudentTicket $ticket) => StudentTicket::CATEGORIES[$ticket->category] ?? $ticket->category)
                ->editColumn('status', fn (StudentTicket $ticket) => '<span class="badge bg-' . match ($ticket->status) {
                    'open' => 'primary',
                    'in_progress' => 'warning',
                    'waiting_on_student' => 'info',
                    'resolved' => 'success',
                    'closed' => 'secondary',
                    default => 'secondary',
                } . '">' . e(StudentTicket::STATUSES[$ticket->status] ?? $ticket->status) . '</span>')
                ->addColumn('assigned_to', fn (StudentTicket $ticket) => $ticket->assignee?->name ?? 'Unassigned')
                ->editColumn('updated_at', fn (StudentTicket $ticket) => $ticket->updated_at?->format('j M Y, H:i'))
                ->addColumn('actions', function (StudentTicket $ticket) {
                    $user = auth()->user();
                    $canManage = $user->can('manage student tickets');
                    $canAssign = $user->can('assign student tickets') || $canManage;
                    $canReply = $user->can('reply to student tickets') || $canManage;
                    $isClosed = in_array($ticket->status, ['resolved', 'closed'], true);
                    $canChangeStatus = $isClosed
                        ? ($user->can('reopen student tickets') || $canManage)
                        : ($user->can('close student tickets') || $canManage);

                    if (!$canAssign && !$canReply && !$canChangeStatus) {
                        return '';
                    }

                    $actions = '<div class="dropdown d-inline-block">'
                        . '<button class="btn btn-sm btn-primary rounded-pill px-3 text-nowrap" type="button" data-bs-toggle="dropdown" aria-expanded="false">'
                        . '<i class="fa fa-angle-down me-1" aria-hidden="true"></i> Actions</button>'
                        . '<div class="dropdown-menu dropdown-menu-end p-2">';

                    if ($canAssign) {
                        $actions .= '<a class="dropdown-item" href="' . e(route('admin.tickets.show', $ticket)) . '#ticket-management">'
                            . '<i class="fa fa-user me-2" aria-hidden="true"></i> Assign</a>';
                    }
                    if ($canReply) {
                        $actions .= '<a class="dropdown-item" href="' . e(route('admin.tickets.show', $ticket)) . '#ticket-reply">'
                            . '<i class="fa fa-reply me-2" aria-hidden="true"></i> Reply</a>';
                    }
                    if ($canChangeStatus) {
                        $status = $isClosed ? 'open' : 'closed';
                        $label = $isClosed ? 'Re-open' : 'Close';
                        $tone = $isClosed ? 'success' : 'danger';
                        $icon = $isClosed ? 'unlock' : 'lock';
                        $actions .= '<form method="POST" action="' . e(route('admin.tickets.update', $ticket)) . '">'
                            . csrf_field() . method_field('PATCH')
                            . '<input type="hidden" name="status" value="' . $status . '">'
                            . '<button class="dropdown-item text-' . $tone . '" type="submit">'
                            . '<i class="fa fa-' . $icon . ' me-2" aria-hidden="true"></i> ' . $label . '</button></form>';
                    }

                    return $actions . '</div></div>';
                })
                ->only(['ticket', 'student', 'subject', 'category', 'status', 'assigned_to', 'updated_at', 'actions'])
                ->rawColumns(['status', 'actions']);

            if ($request->user()->can('view student ticket summaries')) {
                $dataTable->with('ticketSummary', $this->ticketSummary($filters));
            }

            return $dataTable->toJson();
        }

        $canViewTicketSummaries = $request->user()->can('view student ticket summaries');

        return view('tickets.admin.index', [
            'statuses' => StudentTicket::STATUSES,
            'selectedStatus' => $filters['status'] ?? '',
            'selectedDateFrom' => $filters['date_from'] ?? '',
            'selectedDateTo' => $filters['date_to'] ?? '',
            'canViewTicketSummaries' => $canViewTicketSummaries,
            'ticketSummary' => $canViewTicketSummaries ? $this->ticketSummary($filters) : null,
        ]);
    }

    private function ticketSummary(array $filters): array
    {
        $query = StudentTicket::query()
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
        $statusCounts = $query->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $total = (int) $statusCounts->sum();
        $completed = (int) $statusCounts->get('resolved', 0) + (int) $statusCounts->get('closed', 0);

        return [
            'total' => $total,
            'open' => (int) $statusCounts->get('open', 0),
            'in_progress' => (int) $statusCounts->get('in_progress', 0),
            'waiting_on_student' => (int) $statusCounts->get('waiting_on_student', 0),
            'completed' => $completed,
            'resolve_rate' => $total === 0 ? 0 : (int) round($completed / $total * 100),
        ];
    }

    public function show(StudentTicket $ticket): View
    {
        $ticket->load(['student.User', 'assignee', 'messages.user', 'messages.attachments']);
        $admins = User::permission(['assign student tickets', 'manage student tickets'])
            ->orderBy('name')
            ->get()
            ->unique('id');

        return view('tickets.admin.show', [
            'ticket' => $ticket,
            'admins' => $admins,
            'statuses' => StudentTicket::STATUSES,
            'categories' => StudentTicket::CATEGORIES,
        ]);
    }

    public function update(Request $request, StudentTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(array_keys(StudentTicket::STATUSES))],
            'assigned_to' => ['sometimes', 'nullable', 'string', 'max:36'],
        ]);

        $previousStatus = $ticket->status;
        $previousAssigneeId = $ticket->assigned_to;
        $nextStatus = $data['status'] ?? $previousStatus;
        $assigneeId = array_key_exists('assigned_to', $data)
            ? $data['assigned_to']
            : $previousAssigneeId;
        $assigneeChanged = (string) $assigneeId !== (string) $previousAssigneeId;
        $statusChanged = $nextStatus !== $previousStatus;
        $assignee = null;

        if ($statusChanged) {
            if ($nextStatus === 'closed') {
                $this->authorizePermission($request, 'close student tickets');
            } elseif ($nextStatus === 'open' && in_array($previousStatus, ['closed', 'resolved'], true)) {
                $this->authorizePermission($request, 'reopen student tickets');
            } else {
                $this->authorizePermission($request, 'manage student tickets');
            }
        }

        if ($assigneeChanged) {
            $this->authorizePermission($request, 'assign student tickets');
        }

        if ($assigneeId) {
            $assignee = $assigneeChanged
                ? User::permission(['assign student tickets', 'manage student tickets'])->whereKey($assigneeId)->first()
                : $ticket->assignee;
            if (!$assignee) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'Choose an administrator who can be assigned student tickets.',
                ]);
            }
        }

        $ticket->status = $nextStatus;
        $ticket->assigned_to = $assigneeId;
        $ticket->save();
        $ticket->load(['student.User', 'assignee']);

        if ($statusChanged || $assigneeChanged) {
            $statusLabel = StudentTicket::STATUSES[$ticket->status];
            $body = 'Your ticket "' . $ticket->subject . '" is now ' . $statusLabel . '.';
            if ($assigneeChanged) {
                $body .= $assignee
                    ? ' It has been assigned to ' . $assignee->name . '.'
                    : ' It is currently unassigned.';
            }

            $this->notifications->notifyStudent($ticket, 'Support ticket updated', $body);

            if ($assignee && $assigneeChanged) {
                $this->notifications->notifyAssignee(
                    $assignee,
                    $ticket,
                    'Student ticket assigned to you',
                    'You have been assigned "' . $ticket->subject . '".'
                );
            }
        }

        return back()->with('success', 'Ticket updated.');
    }

    public function reply(Request $request, StudentTicket $ticket): RedirectResponse
    {
        $this->authorizePermission($request, 'reply to student tickets');

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $this->tickets->reply(
            $ticket,
            $request->user(),
            $data['body'],
            $request->file('attachments', []),
            'waiting_on_student'
        );
        $ticket->refresh()->load('student.User');

        $this->notifications->notifyStudent(
            $ticket,
            'New reply from the school',
            'An administrator replied to "' . $ticket->subject . '".'
        );

        return back()->with('success', 'Your reply has been sent.');
    }

    private function authorizePermission(Request $request, string $permission): void
    {
        abort_unless(
            $request->user()->can($permission) || $request->user()->can('manage student tickets'),
            403
        );
    }
}
