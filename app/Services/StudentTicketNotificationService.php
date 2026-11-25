<?php

namespace App\Services;

use App\Models\StudentTicket;
use App\Models\User;
use App\Notifications\StudentTicketNotification;

class StudentTicketNotificationService
{
    public function notifyManagers(StudentTicket $ticket, string $title, string $body): void
    {
        $ticket->loadMissing('assignee');

        $managers = $ticket->assignee
            ? collect([$ticket->assignee])
            : User::permission(['assign student tickets', 'manage student tickets'])->get()->unique('id');

        foreach ($managers as $manager) {
            $this->send($manager, $ticket, $title, $body, 'admin.tickets.show');
        }
    }

    public function notifyStudent(StudentTicket $ticket, string $title, string $body): void
    {
        $ticket->loadMissing('student.User');
        $studentUser = $ticket->student?->User;

        if ($studentUser) {
            $this->send($studentUser, $ticket, $title, $body, 'student.tickets.show');
        }
    }

    public function notifyAssignee(
        User $assignee,
        StudentTicket $ticket,
        string $title,
        string $body
    ): void {
        $this->send($assignee, $ticket, $title, $body, 'admin.tickets.show');
    }

    private function send(
        User $recipient,
        StudentTicket $ticket,
        string $title,
        string $body,
        string $route
    ): void {
        $recipient->notify(new StudentTicketNotification(
            $title,
            $body,
            route($route, $ticket),
            $ticket->id
        ));
    }
}
