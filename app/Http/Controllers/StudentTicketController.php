<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTicket;
use App\Models\User;
use App\Services\StudentTicketNotificationService;
use App\Services\StudentTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentTicketController extends Controller
{
    public function __construct(
        private StudentTicketService $tickets,
        private StudentTicketNotificationService $notifications
    ) {
    }

    public function index(Request $request): View
    {
        $student = $this->studentFor($request->user(), 'view student tickets');
        $tickets = StudentTicket::query()
            ->where('student_id', $student->id)
            ->latest('updated_at')
            ->paginate(15);

        return view('tickets.student.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        $this->studentFor($request->user(), 'create student tickets');

        return view('tickets.student.create', [
            'categories' => StudentTicket::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $this->studentFor($request->user(), 'create student tickets');
        $data = $request->validate([
            'category' => ['required', Rule::in(array_keys(StudentTicket::CATEGORIES))],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:5', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
        ]);

        $ticket = $this->tickets->create(
            $student,
            $request->user(),
            $data,
            $request->file('attachments', [])
        );

        $category = StudentTicket::CATEGORIES[$ticket->category];
        $this->notifications->notifyManagers(
            $ticket,
            'New student support ticket',
            $student->fname . ' ' . $student->sname . ' submitted "' . $ticket->subject . '" (' . $category . ').'
        );

        return redirect()
            ->route('student.tickets.show', $ticket)
            ->with('success', 'Your ticket has been submitted.');
    }

    public function show(Request $request, StudentTicket $ticket): View
    {
        $student = $this->studentFor($request->user(), 'view student tickets');
        abort_unless($ticket->student_id === $student->id, 404);

        $ticket->load(['messages.user', 'messages.attachments', 'assignee']);

        return view('tickets.student.show', compact('ticket'));
    }

    public function reply(Request $request, StudentTicket $ticket): RedirectResponse
    {
        $student = $this->studentFor($request->user(), 'reply to student tickets');
        abort_unless($ticket->student_id === $student->id, 404);

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
            'open'
        );
        $ticket->refresh();

        $this->notifications->notifyManagers(
            $ticket,
            'New reply on a student ticket',
            $student->fname . ' ' . $student->sname . ' replied to "' . $ticket->subject . '".'
        );

        return back()->with('success', 'Your reply has been sent.');
    }

    private function studentFor(User $user, string $permission): Student
    {
        abort_unless($user->hasRole('student'), 403);
        abort_unless($user->can($permission), 403);

        $student = $user->Student;
        abort_unless($student, 403, 'Your account is not linked to a student profile.');

        return $student;
    }
}
