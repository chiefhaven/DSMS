<?php

namespace Tests\Feature;

use App\Services\StudentNumber;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentNumberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.student_numbers_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        DB::setDefaultConnection('student_numbers_test');
        Schema::create('students', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('student_number')->nullable()->unique();
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('student_number_sequences', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('student_id');
            $table->timestamp('created_at');
            $table->timestamp('deleted_at')->nullable();
        });
        foreach (['first', 'second', 'third', 'fourth'] as $id) {
            DB::table('students')->insert(['id' => $id]);
        }
    }

    public function test_numbers_increment_daily_and_remain_stable(): void
    {
        $numbers = app(StudentNumber::class);
        $day = Carbon::parse('2026-11-10', date_default_timezone_get());

        $this->assertSame('2026314001', $numbers->assign('first', $day));
        $this->assertSame('2026314002', $numbers->assign('second', $day));
        $this->assertSame('2026315001', $numbers->assign('third', $day->copy()->addDay()));
        $this->assertSame('2026314001', $numbers->assign('first', $day->copy()->addYear()));
        $this->assertSame('2027001001', $numbers->assign('fourth', Carbon::parse('2027-01-01', date_default_timezone_get())));
        $this->assertSame(2, DB::table('student_number_sequences')->where('date', '2026-11-10')->value('last_number'));
    }

    public function test_number_allocation_rolls_back_with_failed_enrollment(): void
    {
        DB::beginTransaction();
        app(StudentNumber::class)->assign('first', Carbon::parse('2026-11-10', date_default_timezone_get()));
        DB::rollBack();

        $this->assertNull(DB::table('students')->where('id', 'first')->value('student_number'));
        $this->assertSame(0, DB::table('student_number_sequences')->count());
        $this->assertSame('2026314001', app(StudentNumber::class)->assign('second', Carbon::parse('2026-11-10', date_default_timezone_get())));
    }

    public function test_backfill_uses_first_active_enrollment_and_is_idempotent(): void
    {
        DB::table('invoices')->insert([
            ['student_id' => 'first', 'created_at' => '2026-11-10 10:00:00', 'deleted_at' => null],
            ['student_id' => 'first', 'created_at' => '2026-11-11 10:00:00', 'deleted_at' => null],
            ['student_id' => 'second', 'created_at' => '2026-11-10 11:00:00', 'deleted_at' => null],
            ['student_id' => 'third', 'created_at' => '2026-11-09 10:00:00', 'deleted_at' => '2026-11-10 10:00:00'],
        ]);

        $this->artisan('students:backfill-numbers')->assertSuccessful();
        $this->assertSame('2026314001', DB::table('students')->where('id', 'first')->value('student_number'));
        $this->assertSame('2026314002', DB::table('students')->where('id', 'second')->value('student_number'));
        $this->assertNull(DB::table('students')->where('id', 'third')->value('student_number'));
        $this->assertNull(DB::table('students')->where('id', 'fourth')->value('student_number'));
        $this->artisan('students:backfill-numbers')->assertSuccessful();
        $this->assertSame(2, DB::table('student_number_sequences')->value('last_number'));
    }

    public function test_enrollment_uses_the_school_day_at_midnight(): void
    {
        $this->assertSame(
            '2026315001',
            app(StudentNumber::class)->assign('first', Carbon::parse('2026-11-10 22:30:00', 'UTC'))
        );
    }
}
