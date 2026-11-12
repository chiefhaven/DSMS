<?php

namespace Tests\Feature;

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class StudentFormTest extends TestCase
{
    public function test_add_form_preserves_fields_and_marks_trn_optional(): void
    {
        $html = $this->renderForm(false);
        $xpath = $this->xpath($html);

        $this->assertSame(1, $xpath->query('//form[@method="POST" and @enctype="multipart/form-data"]')->length);
        foreach (['fname', 'mname', 'sname', 'gender', 'date_of_birth', 'trn', 'signature', 'phone', 'email', 'address', 'district', 'username', 'password'] as $field) {
            $this->assertSame(1, $xpath->query('//*[@name="'.$field.'"]')->length, $field);
        }
        $this->assertSame(0, $xpath->query('//input[@name="trn"]/@required')->length);
        $this->assertSame(0, $xpath->query('//input[@name="mname"]/@required')->length);
        $this->assertStringContainsString('(optional)', $xpath->query('//label[@for="mname"]')->item(0)->textContent);
        $this->assertSame(1, $xpath->query('//input[@name="signature"]/@required')->length);
        $this->assertSame('', $xpath->query('//input[@name="date_of_birth"]')->item(0)->getAttribute('value'));
        $this->assertStringContainsString('(optional)', $xpath->query('//label[@for="trn"]')->item(0)->textContent);
    }

    public function test_edit_form_restores_input_and_displays_inline_errors(): void
    {
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $this->app['session']->flashInput([
            'fname' => 'Revised name',
            'trn' => '',
            'gender' => 'male',
            'district' => 'Blantyre',
        ]);
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['fname' => 'Please enter a first name.']));
        $html = $this->renderForm(true, $errors);
        $xpath = $this->xpath($html);

        $this->assertSame('First Last', $xpath->query('//h1/em')->item(0)->textContent);
        $this->assertSame('Revised name', $xpath->query('//input[@name="fname"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $xpath->query('//input[@name="trn"]')->item(0)->getAttribute('value'));
        $this->assertSame('male', $xpath->query('//select[@name="gender"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('Blantyre', $xpath->query('//select[@name="district"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame(0, $xpath->query('//input[@name="signature"]/@required')->length);
        $this->assertSame(0, $xpath->query('//input[@name="mname"]/@required')->length);
        $this->assertStringContainsString('(optional)', $xpath->query('//label[@for="mname"]')->item(0)->textContent);
        $this->assertSame(0, $xpath->query('//input[@name="password" or @name="username"]')->length);
        $this->assertSame('student-example', $xpath->query('//input[@name="student_id"]')->item(0)->getAttribute('value'));
        $this->assertSame('PUT', $xpath->query('//input[@name="_method"]')->item(0)->getAttribute('value'));
        $this->assertSame(route('students.update', 'student-example'), $xpath->query('//form')->item(0)->getAttribute('action'));
        $this->assertStringContainsString('Please enter a first name.', $html);
        $this->assertStringContainsString('Current file: signature.png', $html);
    }

    private function renderForm(bool $editing, ?ViewErrorBag $errors = null): string
    {
        return view('students.partials.student-form', [
            'editing' => $editing,
            'student' => (object) [
                'id' => 'student-example',
                'fname' => 'First',
                'mname' => null,
                'sname' => 'Last',
                'gender' => 'female',
                'date_of_birth' => '1999-08-07',
                'trn' => '12345',
                'phone' => '+265123456789',
                'address' => 'Example street',
                'district_id' => 'lilongwe',
                'signature' => 'signature.png',
                'user' => (object) ['email' => 'student@example.com'],
            ],
            'district' => collect([
                (object) ['id' => 'lilongwe', 'name' => 'Lilongwe'],
                (object) ['id' => 'blantyre', 'name' => 'Blantyre'],
            ]),
            'errors' => $errors ?? new ViewErrorBag,
        ])->render();
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        $document->loadHTML($html);

        return new \DOMXPath($document);
    }
}
