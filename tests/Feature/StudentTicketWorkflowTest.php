<?php

namespace Tests\Feature;

use App\Models\StudentTicket;
use App\Models\User;
use App\Notifications\StudentTicketNotification;
use App\Services\StudentTicketNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentTicketWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array',
            'session.driver' => 'array',
            'app.maintenance.enabled' => false,
        ]);

        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('fname');
            $table->string('mname')->nullable();
            $table->string('sname');
            $table->string('trn')->nullable();
            $table->string('course_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('student_id');
            $table->decimal('invoice_balance', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('student_id');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->uuid('student_id')->nullable();
            $table->string('administrator_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        (require base_path('vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub'))->up();
        foreach (['student', 'admin', 'superAdmin', 'Super-Admin'] as $name) {
            Role::create(['name' => $name, 'guard_name' => 'web']);
        }
        (require base_path('database/migrations/2026_11_25_000700_seed_student_ticket_permission.php'))->up();
        (require base_path('database/migrations/2026_11_25_000800_seed_student_ticket_action_permissions.php'))->up();
        (require base_path('database/migrations/2026_11_25_000600_create_student_ticket_tables.php'))->up();

        Notification::fake();
        Storage::fake('local');
    }

    public function test_student_can_create_view_and_reply_to_a_ticket(): void
    {
        [$student, $studentProfile] = $this->createStudent('Taylor Student');
        [$admin] = $this->createUserWithRole('Admin', 'admin');

        $this->actingAs($student)
            ->get(route('student.tickets.index'))
            ->assertOk()
            ->assertSee('My tickets');
        $this->get(route('student.tickets.create'))
            ->assertOk()
            ->assertSee('Create a support ticket');

        $this->actingAs($student)
            ->post(route('student.tickets.store'), [
                'category' => 'payments',
                'subject' => 'Question about my invoice',
                'body' => 'Could you please explain this charge?',
                'attachments' => [
                    UploadedFile::fake()->createWithContent('invoice.pdf', '%PDF-1.4 test document'),
                ],
            ])
            ->assertRedirect();

        $ticket = StudentTicket::query()->firstOrFail();
        $attachment = $ticket->messages()->firstOrFail()->attachments()->firstOrFail();
        $this->assertSame($studentProfile->id, $ticket->student_id);
        $this->assertSame('invoice.pdf', $attachment->original_name);
        $this->assertTrue(Storage::disk('local')->exists($attachment->path));
        $this->assertDatabaseHas('student_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $student->id,
            'body' => 'Could you please explain this charge?',
        ]);
        Notification::assertSentTo($admin, StudentTicketNotification::class);

        $this->actingAs($student)
            ->get(route('student.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Question about my invoice')
            ->assertSee('Could you please explain this charge?')
            ->assertSee('invoice.pdf');
        $this->actingAs($student)
            ->get(route('tickets.attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('invoice.pdf');

        $this->actingAs($admin)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('student-tickets-table');
        $this->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Ticket management')
            ->assertSee('Could you please explain this charge?');

        $this->actingAs($admin)
            ->post(route('admin.tickets.reply', $ticket), ['body' => 'We will review this charge.'])
            ->assertRedirect();

        $this->assertDatabaseHas('student_ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'body' => 'We will review this charge.',
        ]);
        $this->assertDatabaseHas('student_tickets', [
            'id' => $ticket->id,
            'status' => 'waiting_on_student',
        ]);
        Notification::assertSentTo($student, StudentTicketNotification::class);
    }

    public function test_student_cannot_view_another_students_ticket(): void
    {
        [$student, $studentProfile] = $this->createStudent('Jordan One');
        [$otherStudent, $otherProfile] = $this->createStudent('Casey Two');

        $ticket = StudentTicket::query()->create([
            'student_id' => $otherProfile->id,
            'category' => 'general',
            'subject' => 'Private ticket',
            'status' => 'open',
        ]);
        $message = $ticket->messages()->create([
            'user_id' => $otherStudent->id,
            'body' => 'Ticket with a private file.',
        ]);
        $path = 'student-tickets/' . $ticket->id . '/private.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 private');
        $attachment = $message->attachments()->create([
            'original_name' => 'private.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 16,
        ]);

        $this->actingAs($student)->get(route('student.tickets.show', $ticket))->assertNotFound();
        $this->get(route('tickets.attachments.download', $attachment))->assertNotFound();
        $this->get(route('admin.tickets.index'))->assertForbidden();
        $this->assertNotSame($studentProfile->id, $otherProfile->id);
    }

    public function test_ticket_notification_email_failure_is_logged_without_failing_database_notification(): void
    {
        [, $studentProfile] = $this->createStudent('Taylor Student');
        [$admin] = $this->createUserWithRole('Admin', 'admin');
        $ticket = StudentTicket::query()->create([
            'student_id' => $studentProfile->id,
            'category' => 'general',
            'subject' => 'Need help',
            'status' => 'open',
        ]);
        $mailException = new \RuntimeException('SMTP unavailable');

        Notification::shouldReceive('sendNow')
            ->once()
            ->with($admin, \Mockery::type(StudentTicketNotification::class), ['database']);
        Notification::shouldReceive('sendNow')
            ->once()
            ->with($admin, \Mockery::type(StudentTicketNotification::class), ['mail'])
            ->andThrow($mailException);
        Log::shouldReceive('error')
            ->once()
            ->with('Ticket notification email failed.', \Mockery::on(function (array $context) use ($ticket, $admin, $mailException) {
                return $context['ticket_id'] === $ticket->id
                    && $context['recipient_id'] === $admin->id
                    && $context['exception'] === $mailException;
            }));

        app(StudentTicketNotificationService::class)->notifyAssignee(
            $admin,
            $ticket,
            'Ticket assigned',
            'You have been assigned this ticket.'
        );
    }

    public function test_admin_ticket_list_supports_server_side_datatables_search_and_status_filter(): void
    {
        [, $studentProfile] = $this->createStudent('Morgan Student');
        [$admin] = $this->createUserWithRole('Admin', 'admin');
        $matchingTicket = StudentTicket::query()->create([
            'student_id' => $studentProfile->id,
            'category' => 'lessons',
            'subject' => 'Need help with lessons',
            'status' => 'open',
        ]);
        StudentTicket::query()->create([
            'student_id' => $studentProfile->id,
            'category' => 'general',
            'subject' => 'General question',
            'status' => 'open',
        ]);
        StudentTicket::query()->create([
            'student_id' => $studentProfile->id,
            'category' => 'general',
            'subject' => 'Closed question',
            'status' => 'closed',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.tickets.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Need help', 'regex' => 'false'],
            'status' => 'open',
        ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
            ->assertJsonPath('draw', 1)
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.subject', $matchingTicket->subject);
        $this->assertStringContainsString('Actions', $response->json('data.0.actions'));
    }

    public function test_admin_ticket_list_shows_summary_counts_for_each_status_group(): void
    {
        [, $studentProfile] = $this->createStudent('Summary Student');
        [$admin] = $this->createUserWithRole('Admin', 'admin');

        foreach (['open', 'in_progress', 'waiting_on_student', 'resolved', 'closed'] as $status) {
            StudentTicket::query()->create([
                'student_id' => $studentProfile->id,
                'category' => 'general',
                'subject' => ucfirst(str_replace('_', ' ', $status)) . ' ticket',
                'status' => $status,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('Total tickets')
            ->assertSee('Waiting on student')
            ->assertViewHas('ticketSummary', [
                'total' => 5,
                'open' => 1,
                'in_progress' => 1,
                'waiting_on_student' => 1,
                'completed' => 2,
            ]);
    }

    public function test_admin_ticket_list_filters_by_created_date_range(): void
    {
        [, $studentProfile] = $this->createStudent('Date Filter Student');
        [$admin] = $this->createUserWithRole('Admin', 'admin');
        $olderDate = now()->subDays(10);
        $recentDate = now()->subDays(3);

        foreach ([
            ['subject' => 'Older ticket', 'date' => $olderDate],
            ['subject' => 'Recent ticket', 'date' => $recentDate],
        ] as $ticketData) {
            StudentTicket::query()->create([
                'student_id' => $studentProfile->id,
                'category' => 'general',
                'subject' => $ticketData['subject'],
                'status' => 'open',
                'created_at' => $ticketData['date'],
                'updated_at' => $ticketData['date'],
            ]);
        }

        $response = $this->actingAs($admin)->getJson(route('admin.tickets.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => '', 'regex' => 'false'],
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
        ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.subject', 'Recent ticket');

        $this->getJson(route('admin.tickets.index', [
            'date_from' => now()->toDateString(),
            'date_to' => now()->subDay()->toDateString(),
        ]), ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');
    }

    public function test_student_dashboard_shows_personal_training_and_ticket_summary(): void
    {
        [$student, $studentProfile] = $this->createStudent('Taylor Student');
        Role::findByName('student', 'web')->givePermissionTo(
            Permission::findOrCreate('access portal', 'web')
        );

        $courseId = (string) Str::uuid();
        DB::table('courses')->insert([
            'id' => $courseId,
            'name' => 'Road Safety Training',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('students')->where('id', $studentProfile->id)->update([
            'course_id' => $courseId,
            'trn' => '123456789',
            'status' => 'Active',
        ]);
        DB::table('invoices')->insert([
            'student_id' => $studentProfile->id,
            'invoice_balance' => 12500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('attendances')->insert([
            ['student_id' => $studentProfile->id, 'created_at' => now(), 'updated_at' => now()],
            ['student_id' => $studentProfile->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $studentProfile->tickets()->create([
            'category' => 'lessons',
            'subject' => 'Upcoming lesson question',
            'status' => 'open',
        ]);

        $this->actingAs($student)
            ->get(route('dashboard.home'))
            ->assertOk()
            ->assertSee('Road Safety Training')
            ->assertSee('Attendance records')
            ->assertSee('K12,500.00')
            ->assertSee('Open support tickets')
            ->assertSee('2')
            ->assertSee('1 total ticket');
    }

    public function test_admin_can_assign_and_update_ticket_status(): void
    {
        [$student, $studentProfile] = $this->createStudent('Morgan Student');
        [$admin] = $this->createUserWithRole('Morgan Admin', 'admin', null, '1503');
        $ticket = StudentTicket::query()->create([
            'student_id' => $studentProfile->id,
            'category' => 'general',
            'subject' => 'Need help',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.tickets.update', $ticket), [
                'status' => 'in_progress',
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('student_tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'assigned_to' => $admin->id,
        ]);
        Notification::assertSentTo($student, StudentTicketNotification::class);
        Notification::assertSentTo($admin, StudentTicketNotification::class);

        $this->actingAs($admin)
            ->patch(route('admin.tickets.update', $ticket), [
                'status' => 'closed',
                'assigned_to' => $admin->id,
            ])
            ->assertRedirect();
        $this->assertDatabaseHas('student_tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
            'assigned_to' => $admin->id,
        ]);
        $this->get(route('admin.tickets.index'))->assertOk()->assertSee('student-tickets-table');

        $this->patch(route('admin.tickets.update', $ticket), [
            'status' => 'open',
            'assigned_to' => $admin->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('student_tickets', [
            'id' => $ticket->id,
            'status' => 'open',
            'assigned_to' => $admin->id,
        ]);
    }

    public function test_ticket_actions_require_their_specific_permissions(): void
    {
        [, $studentProfile] = $this->createStudent('Robin Student');
        [$admin] = $this->createUserWithRole('Admin with ticket access', 'admin');
        $viewerRole = Role::create(['name' => 'ticket-viewer', 'guard_name' => 'web']);
        $viewerRole->givePermissionTo('view all student tickets');
        [$viewer] = $this->createUserWithRole('Ticket viewer', 'ticket-viewer');
        $ticket = StudentTicket::query()->create([
            'student_id' => $studentProfile->id,
            'category' => 'general',
            'subject' => 'Permission check',
            'status' => 'open',
        ]);

        $this->actingAs($viewer)
            ->get(route('admin.tickets.index'))
            ->assertOk();
        $this->get(route('admin.tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Assign to')
            ->assertDontSee('Reply to student');
        $this->post(route('admin.tickets.reply', $ticket), ['body' => 'Unauthorized reply'])
            ->assertForbidden();
        $this->patch(route('admin.tickets.update', $ticket), ['status' => 'closed'])
            ->assertForbidden();
        $this->patch(route('admin.tickets.update', $ticket), ['assigned_to' => $admin->id])
            ->assertForbidden();
    }

    private function createStudent(string $name): array
    {
        $studentId = (string) Str::uuid();
        [$firstName, $lastName] = explode(' ', $name, 2);
        DB::table('students')->insert([
            'id' => $studentId,
            'fname' => $firstName,
            'sname' => $lastName,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = \App\Models\Student::query()->findOrFail($studentId);
        [$user] = $this->createUserWithRole($name, 'student', $studentId);

        return [$user, $student];
    }

    private function createUserWithRole(
        string $name,
        string $roleName,
        ?string $studentId = null,
        ?string $userId = null
    ): array
    {
        $userId = $userId ?? (string) Str::uuid();
        DB::table('users')->insert([
            'id' => $userId,
            'name' => $name,
            'email' => Str::uuid() . '@example.test',
            'password' => bcrypt('password'),
            'student_id' => $studentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail($userId);
        $role = Role::findByName($roleName, 'web');
        DB::table(config('permission.table_names.model_has_roles'))->insert([
            'role_id' => $role->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
        ]);

        return [$user->fresh()];
    }
}
