<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(UsersTableSeeder::class);
        $this->call(AccountsTableSeeder::class);
        $this->call(StudentsTableSeeder::class);
        $this->call(CoursesTableSeeder::class);
        $this->call(EnrollmentsTableSeeder::class);
        $this->call(InvoicesTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(InstructorSeeder::class);
        $this->call(AdministratorSeeder::class);
        $this->call(AttendanceSeeder::class);
        $this->call(CourseLessonSeeder::class);
        $this->call(LessonSeeder::class);
        $this->call(InstructorLessonSeeder::class);
        $this->call(DistrictSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(PaymentMethodSeeder::class);
        $this->call(InvoiceSettingSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(FleetSeeder::class);
        $this->call(ExpenseSeeder::class);

    }
}
