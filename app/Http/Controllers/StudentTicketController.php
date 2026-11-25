<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentTicket;
use App\Models\StudentTicketMessage;
use App\Models\StudentTicketMessageVote;
use App\Models\User;
use App\Services\StudentTicketNotificationService;
use App\Services\StudentTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
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

        $ticket->load(['messages.user.roles', 'messages.attachments', 'messages.votes', 'assignee']);
        $this->addResponseTimeLabels($ticket);

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

    public function vote(Request $request, StudentTicket $ticket, StudentTicketMessage $message): RedirectResponse|JsonResponse
    {
        $student = $this->studentFor($request->user(), 'view student tickets');
        abort_unless($ticket->student_id === $student->id, 404);
        abort_unless($message->ticket_id === $ticket->id, 404);

        $message->loadMissing('user.roles');
        abort_unless($message->user && !$message->user->hasRole('student'), 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
        ]);

        StudentTicketMessageVote::updateOrCreate(
            ['message_id' => $message->id, 'student_id' => $student->id],
            ['rating' => $data['rating']]
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Your rating has been saved.',
                'rating' => (int) $data['rating'],
            ]);
        }

        return back()->with('success', 'Your rating has been saved.');
    }

    private function addResponseTimeLabels(StudentTicket $ticket): void
    {
        $latestStudentMessageAt = null;

        foreach ($ticket->messages as $message) {
            if ($message->user?->hasRole('student')) {
                $latestStudentMessageAt = $message->created_at;
                continue;
            }

            if ($message->user && $latestStudentMessageAt && $message->created_at) {
                $seconds = max(
                    0,
                    $message->created_at->getTimestamp() - $latestStudentMessageAt->getTimestamp()
                );
                $message->setAttribute('response_time_label', $this->formatResponseTime($seconds));
            }
        }
    }

    private function formatResponseTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);
        $days = intdiv($hours, 24);
        $minutes %= 60;
        $hours %= 24;
        $parts = [];

        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0) {
            $parts[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . 'm';
        }

        return implode(' ', $parts);
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
