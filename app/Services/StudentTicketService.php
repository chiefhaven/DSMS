<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentTicket;
use App\Models\StudentTicketMessage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StudentTicketService
{
    public function create(Student $student, User $author, array $data, array $files): StudentTicket
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($student, $author, $data, $files, &$storedPaths) {
                $ticket = StudentTicket::create([
                    'student_id' => $student->id,
                    'category' => $data['category'],
                    'subject' => $data['subject'],
                    'status' => 'open',
                ]);

                $message = $ticket->messages()->create([
                    'user_id' => $author->id,
                    'body' => $data['body'],
                ]);

                $this->storeAttachments($message, $ticket->id, $files, $storedPaths);

                return $ticket;
            });
        } catch (Throwable $exception) {
            $this->removeStoredFiles($storedPaths);
            throw $exception;
        }
    }

    public function reply(
        StudentTicket $ticket,
        User $author,
        string $body,
        array $files,
        string $newStatus
    ): StudentTicketMessage {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($ticket, $author, $body, $files, $newStatus, &$storedPaths) {
                $lockedTicket = StudentTicket::query()
                    ->whereKey($ticket->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $message = $lockedTicket->messages()->create([
                    'user_id' => $author->id,
                    'body' => $body,
                ]);

                $this->storeAttachments($message, $lockedTicket->id, $files, $storedPaths);

                $lockedTicket->status = $newStatus;
                $lockedTicket->save();

                return $message;
            });
        } catch (Throwable $exception) {
            $this->removeStoredFiles($storedPaths);
            throw $exception;
        }
    }

    private function storeAttachments(
        StudentTicketMessage $message,
        string $ticketId,
        array $files,
        array &$storedPaths
    ): void {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('student-tickets/' . $ticketId, 'local');
            if (!$path) {
                throw new RuntimeException('Unable to store a student ticket attachment.');
            }

            $storedPaths[] = $path;

            $message->attachments()->create([
                'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?? 0,
            ]);
        }
    }

    private function removeStoredFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if (!Storage::disk('local')->delete($path)) {
                Log::warning('Failed to remove a student ticket attachment after a database error.', [
                    'path' => $path,
                ]);
            }
        }
    }
}
