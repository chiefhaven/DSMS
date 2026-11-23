
@php
    $employee = $employee ?? null;
    $staffType = $staffType ?? null;

    if (!$staffType && $employee instanceof \App\Models\Administrator) {
        $staffType = 'administrator';
    } elseif (!$staffType && $employee instanceof \App\Models\Instructor) {
        $staffType = 'instructor';
    }

    $showAdminTemplate = $staffType === 'administrator' || !$staffType;
    $showInstructorTemplate = $staffType === 'instructor' || !$staffType;

    $termsFieldId = 'terms_of_employment_' . uniqid();
    $termsEditorId = 'terms_editor_' . $termsFieldId;

    $adminTemplateId = 'terms-template-admin-' . $termsFieldId;
    $instructorTemplateId = 'terms-template-instructor-' . $termsFieldId;

    $termsValue = old('terms_of_employment', $employee?->terms_of_employment);

    $adminTermsTemplate = <<<'HTML'
<h4>ADMINISTRATOR EMPLOYMENT TERMS</h4>
<ol>
    <li><strong>Appointment and duties:</strong> The Employee is employed in an administrative capacity and shall perform the duties of the role as assigned from time to time, including administrative, coordination, customer-service, and operational support duties. The Employee shall carry out duties diligently, follow lawful and reasonable instructions, and comply with approved policies, procedures, and standards.</li>
    <li><strong>Commencement and probation:</strong> Employment commences on the agreed start date and may be subject to a probationary period as specified in the appointment/offer. Performance, conduct, and suitability may be reviewed during and after probation in accordance with applicable policy.</li>
    <li><strong>Place of work and working hours:</strong> The Employee shall work at the assigned location(s) and during the working hours/schedule determined by the Employer. Reasonable additional hours, schedule adjustments, or attendance at official activities may be required to meet operational needs, subject to applicable law and policy.</li>
    <li><strong>Remuneration and deductions:</strong> Salary, allowances, and any other benefits shall be paid as agreed and approved. The Employer may make statutory and authorized deductions from remuneration (including taxes and other lawful contributions) in accordance with applicable laws and payroll requirements.</li>
    <li><strong>Leave, attendance, and timekeeping:</strong> Leave, public holidays, attendance, punctuality, and time-off requests are governed by applicable law and the Employer’s HR policies and require prior approval where applicable. Accurate records and timely communication of absence are required.</li>
    <li><strong>Confidentiality and data protection:</strong> The Employee shall keep confidential all non-public information relating to students, staff, finances, operations, systems, and business affairs during and after employment. Personal and sensitive data must be handled lawfully, securely, and only for authorized purposes.</li>
    <li><strong>Conduct, policies, and compliance:</strong> The Employee shall maintain professional conduct, act with integrity, avoid conflicts of interest, and comply with workplace rules, safeguarding expectations, anti-harassment standards, and all applicable laws and regulations.</li>
    <li><strong>Health, safety, and use of resources:</strong> The Employee shall comply with health and safety requirements, take reasonable care for their own safety and that of others, and use the Employer’s property, systems, and resources responsibly and for authorized purposes only.</li>
    <li><strong>Performance, training, and changes to duties:</strong> The Employee is expected to meet performance standards, participate in required training, and cooperate with reasonable changes to duties, processes, or reporting lines consistent with the role and business needs.</li>
    <li><strong>Disciplinary action, suspension, and termination:</strong> Misconduct, poor performance, or breach of policy may result in disciplinary action in accordance with applicable procedures. Either party may terminate employment in line with the contract type, notice period, and applicable labor laws. The Employer may suspend or place the Employee on other lawful measures where appropriate.</li>
    <li><strong>Return of property and final obligations:</strong> Upon termination or when requested, the Employee shall promptly return all Employer property, documents, records, devices, access credentials, and confidential materials. Outstanding obligations, including confidentiality, survive termination as applicable.</li>
    <li><strong>Entire terms and amendments:</strong> These terms are supplemented by the offer/appointment letter, policies, and applicable law. Any variation should be made in writing or through an authorized process, and where any provision is unenforceable it shall not affect the validity of the remaining provisions.</li>
</ol>
HTML;

    $instructorTermsTemplate = <<<'HTML'
<h4>INSTRUCTOR EMPLOYMENT TERMS</h4>
<ol>
    <li><strong>Appointment and duties:</strong> The Employee is employed as an Instructor and shall deliver assigned theory and/or practical lessons, prepare for sessions, assess student progress, provide guidance, and maintain accurate attendance, lesson, and assessment records. The Employee shall perform duties diligently, follow lawful and reasonable instructions, and comply with approved policies and standards.</li>
    <li><strong>Commencement and probation:</strong> Employment commences on the agreed start date and may be subject to a probationary period as specified in the appointment/offer. Performance, conduct, and suitability may be reviewed during and after probation in accordance with applicable policy.</li>
    <li><strong>Schedules, lessons, and working hours:</strong> Teaching schedules, duty rosters, and assigned locations are determined by the Employer. The Employee shall attend lessons and duties punctually and may be required to work reasonable additional or adjusted hours for lessons, assessments, tests, or official activities, subject to applicable law and policy.</li>
    <li><strong>Remuneration and deductions:</strong> Salary, allowances, and any other benefits shall be paid as agreed and approved. Where applicable, remuneration may include attendance-, lesson-, or performance-related components in accordance with approved payroll/HR policies. The Employer may make statutory and authorized deductions as required by law.</li>
    <li><strong>Professional conduct, student welfare, and safety:</strong> The Employee shall maintain professional boundaries and conduct, treat students fairly and respectfully, and uphold safeguarding, road safety, vehicle/classroom safety, and all applicable training standards. Unsafe practices, misconduct, or behavior that endangers students or others must be avoided and reported as required.</li>
    <li><strong>Qualifications, licensing, and compliance:</strong> The Employee shall hold and maintain any required qualifications, permits, or licenses relevant to the role, promptly notify the Employer of any suspension/restriction affecting fitness to instruct or drive, and comply with applicable laws, regulations, and institutional requirements.</li>
    <li><strong>Leave, attendance, and records:</strong> Leave, attendance, punctuality, and time-off requests are governed by applicable law and HR policies and require prior approval where applicable. The Employee must keep complete, accurate, and timely records of lessons, attendance, assessments, incidents, and other required documentation.</li>
    <li><strong>Confidentiality and data protection:</strong> The Employee shall keep confidential all non-public information relating to students, staff, operations, systems, and business affairs during and after employment. Student and staff data must be handled lawfully, securely, and only for authorized purposes.</li>
    <li><strong>Health, safety, and use of property:</strong> The Employee shall comply with health and safety requirements, take reasonable care for their own safety and that of others, promptly report incidents/defects/hazards, and use vehicles, equipment, materials, systems, and other Employer property responsibly and for authorized purposes only.</li>
    <li><strong>Performance, training, and changes to duties:</strong> The Employee is expected to meet performance standards, participate in required training/evaluations, and cooperate with reasonable changes to duties, schedules, or assignments consistent with the role and business needs.</li>
    <li><strong>Disciplinary action, suspension, and termination:</strong> Misconduct, poor performance, breach of safety standards, or breach of policy may result in disciplinary action in accordance with applicable procedures. Either party may terminate employment in line with the contract type, notice period, and applicable labor laws. The Employer may suspend or take other lawful measures where appropriate.</li>
    <li><strong>Return of property and final obligations:</strong> Upon termination or when requested, the Employee shall promptly return all Employer property, records, devices, teaching materials, access credentials, and confidential materials. Outstanding obligations, including confidentiality and record handover, survive termination as applicable.</li>
</ol>
HTML;
@endphp

<div class="block block-rounded border mb-4 mt-4">

    <div class="block-header block-header-default">
        <h3 class="block-title">Employment Contract</h3>
    </div>

    <div class="block-content block-content-full">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

            <div>
                <h4 class="fs-5 fw-semibold mb-1">Terms of Employment</h4>
                <p class="text-muted fs-sm mb-0">
                    Define the employee's contractual obligations and conditions of employment.
                </p>
            </div>

            @if ($showAdminTemplate || $showInstructorTemplate)
                <div class="d-flex flex-wrap gap-2">

                    @if ($showAdminTemplate)
                        <button type="button"
                                class="btn btn-sm btn-alt-primary js-load-terms-template"
                                data-target="{{ $termsFieldId }}"
                                data-template-id="{{ $adminTemplateId }}">
                            Administrator Template
                        </button>
                    @endif

                    @if ($showInstructorTemplate)
                        <button type="button"
                                class="btn btn-sm btn-alt-primary js-load-terms-template"
                                data-target="{{ $termsFieldId }}"
                                data-template-id="{{ $instructorTemplateId }}">
                            Instructor Template
                        </button>
                    @endif

                </div>
            @endif

        </div>

        <template id="{{ $adminTemplateId }}">
            {!! $adminTermsTemplate !!}
        </template>

        <template id="{{ $instructorTemplateId }}">
            {!! $instructorTermsTemplate !!}
        </template>

        <div class="mb-4">

            <label for="{{ $termsEditorId }}" class="form-label fw-semibold">
                Contract / Employment Terms
            </label>

            <input type="hidden"
                   id="{{ $termsFieldId }}"
                   name="terms_of_employment"
                   value="{{ $termsValue }}"
                   class="@error('terms_of_employment') is-invalid @enderror">

            <div id="{{ $termsEditorId }}"
                 class="js-rich-terms-editor bg-white"
                 data-input="{{ $termsFieldId }}">{!! $termsValue !!}</div>

            @error('terms_of_employment')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

        </div>

        <h4 class="fs-5 fw-semibold mb-1">Contract Signing</h4>

        <p class="text-muted fs-sm mb-3">
            Record the employee's contract signing status and date.
        </p>

        <div class="row g-3 align-items-center">

            <div class="col-md-6">

                <div class="form-check form-switch p-3 ps-5 h-100">

                    <input type="hidden" name="contract_signed" value="0">

                    <input class="form-check-input @error('contract_signed') is-invalid @enderror"
                           type="checkbox"
                           value="1"
                           id="contract_signed_{{ $termsFieldId }}"
                           name="contract_signed"
                           {{ old('contract_signed', $employee?->contract_signed) ? 'checked' : '' }}>

                    <label class="form-check-label fw-semibold"
                           for="contract_signed_{{ $termsFieldId }}">
                        Signed by Employee
                    </label>

                    @error('contract_signed')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                </div>

            </div>

            <div class="col-md-6">

                <div class="form-floating">
                    <input type="text"
                           class="form-control js-datepicker @error('contract_signed_at') is-invalid @enderror"
                           id="contract_signed_at_{{ $termsFieldId }}"
                           name="contract_signed_at"
                           placeholder="YYYY-MM-DD"
                           autocomplete="off"
                           value="{{ old('contract_signed_at', isset($employee?->contract_signed_at) ? \Illuminate\Support\Carbon::parse($employee->contract_signed_at)->format('Y-m-d') : '') }}">
                    <label for="contract_signed_at_{{ $termsFieldId }}">Date signed</label>
                    @error('contract_signed_at')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

            </div>

        </div>

    </div>
</div>

@once

    <style>
        .ql-toolbar.ql-snow + .js-rich-terms-editor,
        .js-rich-terms-editor .ql-toolbar.ql-snow {
            display: none;
        }

        .js-rich-terms-editor .ql-container.ql-snow {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            font-family: inherit;
            font-size: 0.95rem;
            height: 7em;
        }

        .js-rich-terms-editor .ql-editor {
            height: 100%;
            line-height: 1.7;
        }

        .js-rich-terms-editor .ql-editor.ql-blank::before {
            color: #adb5bd;
            font-style: normal;
        }

        .js-rich-terms-editor .ql-container.ql-snow:focus-within {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
        }
    </style>

    @push('scripts')
        <script>
            (function () {

                const termsEditors = {};

                function isQuillEmpty(quill) {
                    const text = quill.getText().replace(/\s+/g, '').trim();
                    return text.length === 0;
                }

                function initTermsEditors(root = document) {

                    if (typeof Quill === 'undefined') {
                        return;
                    }

                    root.querySelectorAll('.js-rich-terms-editor').forEach(function (editorEl) {

                        if (editorEl.dataset.quillInitialized === 'true') {
                            return;
                        }

                        const input = document.getElementById(editorEl.dataset.input || '');

                        if (!input) {
                            return;
                        }

                        const quill = new Quill(editorEl, {
                            theme: 'snow',
                            placeholder: 'Enter the terms and conditions of employment...',
                            modules: {
                                toolbar: false
                            }
                        });

                        quill.on('text-change', function () {
                            input.value = isQuillEmpty(quill) ? '' : quill.root.innerHTML;
                        });

                        input.value = isQuillEmpty(quill) ? '' : quill.root.innerHTML;

                        editorEl.dataset.quillInitialized = 'true';
                        termsEditors[editorEl.dataset.input] = quill;

                        const form = editorEl.closest('form');

                        if (form && form.dataset.termsEditorSyncBound !== 'true') {

                            form.addEventListener('submit', function () {

                                form.querySelectorAll('.js-rich-terms-editor').forEach(function (el) {

                                    const hidden = document.getElementById(el.dataset.input || '');
                                    const editor = termsEditors[el.dataset.input];

                                    if (hidden && editor) {
                                        hidden.value = isQuillEmpty(editor) ? '' : editor.root.innerHTML;
                                    }

                                });

                            });

                            form.dataset.termsEditorSyncBound = 'true';
                        }

                    });
                }

                document.addEventListener('click', function (event) {

                    const button = event.target.closest('.js-load-terms-template');

                    if (!button) {
                        return;
                    }

                    const targetId = button.dataset.target || '';
                    const template = document.getElementById(button.dataset.templateId || '');

                    if (!template) {
                        return;
                    }

                    const editor = termsEditors[targetId];

                    if (editor) {

                        const hasContent = !isQuillEmpty(editor);

                        if (hasContent && !window.confirm('Replace the current terms with the selected template?')) {
                            return;
                        }

                        editor.setText('');
                        editor.clipboard.dangerouslyPasteHTML(template.innerHTML);

                        const input = document.getElementById(targetId);

                        if (input) {
                            input.value = editor.root.innerHTML;
                        }

                        return;
                    }

                    const textarea = document.getElementById(targetId);

                    if (textarea) {

                        const hasContent = textarea.value.trim().length > 0;

                        if (hasContent && !window.confirm('Replace the current terms with the selected template?')) {
                            return;
                        }

                        textarea.value = template.innerHTML;
                    }

                });

                function initDatePickers(root = document) {
                    if (window.jQuery && $.fn.datepicker) {
                        $(root).find('.js-datepicker').each(function () {
                            if (!$(this).data('datepicker')) {
                                $(this).datepicker({ format: 'yyyy-mm-dd', autoclose: true, todayHighlight: true });
                            }
                        });
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', function () {
                        initTermsEditors();
                        initDatePickers();
                    });
                } else {
                    initTermsEditors();
                    initDatePickers();
                }

            })();
        </script>
    @endpush

@endonce