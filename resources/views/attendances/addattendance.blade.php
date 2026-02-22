@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-sm-row justify-content-sm-between align-attendances-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Add Attendance</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb">
          <ol class="breadcrumb">
                <div class="flex-grow-1 fs-3 my-2 my-sm-3"  id="time" name="date" value="{{ $date }}" disabled></div>
          </ol>
        </nav>
      </div>
    </div>
  </div>

  <div class="content content-full">
    @if(Session::has('message'))
    <div class="alert alert-success">
      {{Session::get('message')}}
    </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @php
        $studentName = $student->fname.' '.$student->mname.' '.$student->sname;
    @endphp
    <div class="block block-rounded block-bordered p-5" id="attendance" v-cloak>
          <div class="block-content">
          <div class="row">
            <div class="d-flex justify-content-center align-items-start">
                <div class="w-100" style="max-width: 600px;">
                    <form ref="state.attendanceForm"
                        action="{{ url('/storeattendance') }}" method="post" @submit.prevent="submitAttendance">

                        @csrf
                        <input name="student" v-model="state.student" hidden>

                        <!-- Student + Remaining Lessons -->
                        <div class="text-center mb-4">
                            For student
                            <h2 class="fw-semibold mb-1">
                                @{{ state.student }}
                            </h2>

                            <div class="d-inline-flex align-items-center gap-2 px-2 py-2 rounded-pill
                                        bg-light border">
                                <span class="fs-6 fw-bold small">
                                    @{{ state.remainingLessons }}
                                </span>
                                <span class="text-muted fs-8 small">
                                    lessons remaining
                                </span>
                            </div>
                        </div>

                        <!-- Lesson Select -->
                        <div class="form-floating mb-4">
                            @if($lessons->isNotEmpty())
                                <select
                                    class="form-select border-primary shadow-sm text-center"
                                    id="lesson"
                                    name="lesson"
                                    v-model="state.lesson"
                                    style="min-height: 50px;"
                                >
                                    <option disabled selected value="">Select lesson</option>
                                    @foreach ($lessons as $lesson)
                                        <option value="{{ $lesson->name }}">{{ $lesson->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <div class="text-center my-4">
                                    <i class="fa fa-exclamation-triangle text-danger fa-4x mb-3"></i>
                                    <p class="text-danger fw-semibold mb-1">
                                        No lessons added to course or no attendances left for {{ $studentName }}.
                                    </p>
                                    <p class="text-muted small">
                                        Please contact the administrator.
                                    </p>
                                </div>
                            @endif
                        </div>

                        <!-- Info / Warning -->
                        @if($lessons->isNotEmpty())
                            <p class="fs-10 text-center small mb-3">
                                If some lessons are missing for any student, please ask the administrator to add them to the course the student is enrolled.
                            </p>

                            <!-- Submit Button -->
                            <div class="form-group text-center mt-3">
                                <button
                                    type="submit"
                                    :disabled="state.isSubmitButtonDisabled"
                                    class="btn btn-primary rounded-pill px-4 py-2 shadow-sm"
                                    style="min-width: 150px; font-weight: 600; font-size: 1rem;"
                                >
                                    <template v-if="state.isLoading">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                        Processing...
                                    </template>
                                    <template v-else>
                                        @{{ state.buttonText }}
                                    </template>
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
        </div>
      </div>
    </div>

  <!-- END Hero -->

  <script type="text/javascript">
      var path = "{{ route('attendance-student-search') }}";
      $('#student').typeahead({
          source:  function (query, process) {
          return $.get(path, { query: query }, function (data) {
                  return process(data);
              });
          }
      });
  </script>


<script type="text/javascript">
  function showTime() {
    var date = new Date(),
        utc = new Date(Date.UTC(
          date.getFullYear(),
          date.getMonth(),
          date.getDate(),
          date.getUTCHours(),
          date.getMinutes(),
          date.getSeconds()
        ));

    document.getElementById('time').innerHTML = utc.toLocaleString();
  }

  setInterval(showTime, 1000);
</script>

<script setup>

    const addAttendance = createApp({
      setup() {
        const state = ref({
          isSubmitButtonDisabled: false,
          isLoading: false,
          buttonText: 'Submit',
          attendanceForm: null,
          lesson: '',
          student: '{{ $student->fname }} {{ $student->mname }} {{ $student->sname }}',
          attendanceCount: '{{ $attendanceCount }}',
          studentCourseDuration: '{{ $studentCourseDuration }}',
          remainingLessons: '{{ $studentCourseDuration - $attendanceCount }}'
        });

        onMounted(() => {
            state.value.attendanceForm = document.querySelector('#attendance form');
            attendanceLimitReachingEnd()
        });

        const attendanceLimitReachingEnd = async () => {

            if (state.value.remainingLessons <= 5 && state.value.remainingLessons > 0) {
                notification(
                    `${state.value.student}` + ' is approaching the attendance limit for their course',
                    {
                        remaining: state.value.remainingLessons,
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        unit: 'lessons',
                        criticalAt: 10
                    }
                );
            }

            return false;
        };

        const submitAttendance = async () => {
          state.value.isSubmitButtonDisabled = true;
          state.value.isLoading = true;
          state.value.buttonText = "Processing...";

          if (state.value.attendanceForm) {
            state.value.attendanceForm.submit();
          }
          else{
            notification('An error occured, attendance not entered', 'error')
          }

        }

        function notification(message, options = {}) {

            if (typeof options === 'string') {
                options = { icon: options };
            }

            const remaining = options.remaining;

            let html = message;

            if (remaining !== undefined) {
                const color =
                    remaining <= (options.criticalAt ?? 10)
                        ? '#d33'
                        : remaining <= 30
                        ? '#f39c12'
                        : '#28a745';

                html = `
                    <div style="font-size:16px;margin-bottom:8px;">
                        ${message}
                    </div>
                    <div style="font-size:56px;font-weight:700;">
                        ${remaining}
                    </div>
                    <div style="font-size:14px;color:#666;">
                        ${options.unit ?? 'lessons'} remaining.
                    </div>
                `;
            }

            return Swal.fire({
                icon: options.icon ?? null,
                html: html,
                position: options.position ?? 'center',
                toast: options.toast ?? false,

                showConfirmButton: options.showConfirmButton ?? true,
                confirmButtonText: options.confirmButtonText ?? 'OK',

                timer: options.timer ?? null,
                timerProgressBar: !!options.timer
            });
        }

        return {
          state,
          submitAttendance
        };
      }
    });

    addAttendance.mount('#attendance');
</script>

@endsection
