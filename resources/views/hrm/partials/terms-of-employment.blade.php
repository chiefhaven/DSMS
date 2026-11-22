
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
<h4>ADMINISTRATOR EMPLOYMENT TERMS (SAMPLE)</h4>
<ol>
    <li><strong>Position and duties:</strong> The Administrator shall perform administrative, coordination, and operational duties as assigned, and comply with institutional policies and lawful instructions.</li>
    <li><strong>Working hours:</strong> Standard working hours apply as scheduled by management, with reasonable additional hours when required for official duties.</li>
    <li><strong>Compensation and benefits:</strong> Remuneration, allowances, and statutory deductions shall be administered in accordance with approved payroll and HR policies.</li>
    <li><strong>Confidentiality:</strong> The Administrator shall maintain confidentiality of student, staff, financial, and organizational information during and after employment.</li>
    <li><strong>Conduct and compliance:</strong> The Administrator is expected to uphold professional conduct, data protection, and workplace health and safety requirements.</li>
    <li><strong>Leave and attendance:</strong> Leave, attendance, and time-off requests are subject to approval and applicable HR procedures.</li>
    <li><strong>Termination:</strong> Either party may terminate employment in accordance with the employment type, notice period, and applicable labor regulations; company property must be returned upon separation.</li>
</ol>
HTML;

    $instructorTermsTemplate = <<<'HTML'
<h4>INSTRUCTOR EMPLOYMENT TERMS (SAMPLE)</h4>
<ol>
    <li><strong>Position and duties:</strong> The Instructor shall deliver assigned lessons, assess student progress, maintain accurate attendance/lesson records, and comply with institutional policies and lawful instructions.</li>
    <li><strong>Working hours and schedules:</strong> Teaching and duty hours shall follow assigned schedules, with reasonable flexibility for lessons, assessments, and official activities as required.</li>
    <li><strong>Compensation and benefits:</strong> Remuneration, allowances, and statutory deductions shall be administered in accordance with approved payroll and HR policies, including any applicable attendance or performance-based terms.</li>
    <li><strong>Professional conduct and safety:</strong> The Instructor shall uphold professional conduct, student safety, vehicle/classroom safety procedures, and all applicable training and road safety standards.</li>
    <li><strong>Confidentiality:</strong> The Instructor shall maintain confidentiality of student, staff, and organizational information during and after employment.</li>
    <li><strong>Leave and attendance:</strong> Leave, attendance, and time-off requests are subject to approval and applicable HR procedures.</li>
    <li><strong>Termination:</strong> Either party may terminate employment in accordance with the employment type, notice period, and applicable labor regulations; company property and records must be returned upon separation.</li>
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