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
    <div class="block block-rounded block-bordered p-2" id="attendance">
          <div class="block-content">
          <div class="row">
            <p class="text-center">
                Adding attendance for
                <h2 class="text-center">{{ $studentName }}</h2>
            </p>
          <form ref="state.attendanceForm" class="mb-5" action="{{ url('/storeattendance') }}" method="post" @submit.prevent="handleButtonClick">
            @csrf
            <input class="" name="student" v-model="state.student" hidden>
            <div class="form-floating mb-4">
                @if($lessons->isNotEmpty())
                    <select class="form-select" id="lesson" name="lesson" v-model="state.lesson">
                        <option disabled selected value="">Select lesson</option>
                        @foreach ($lessons as $lesson)
                            <option value="{{ $lesson->name }}">{{ $lesson->name }}</option>
                        @endforeach
                </select>
                <label for="lesson">Lesson attended</label>
                @else

                    <p class="text-center text-danger">
                        <i class="fa fa-exclamation-triangle text-danger fa-4x"></i>
                    </p>
                    <p class="text-center">
                        No lessons added to course or no attendances left for {{ $studentName }}, please contact the administrator!
                    </p>
                @endif
            </div>
            @if($lessons->isNotEmpty())
                <p class="text-center text-danger">If some lessons are missing for any student, please ask the administrator to add them to the course the student is enrolled</p>
                <br>
                <div class="form-group">
                    <button type="submit" :disabled="state.isSubmitButtonDisabled" class="btn btn-primary">
                        <template v-if="state.isLoading">
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

<div id="time"></div>

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

    const app = createApp({
      setup() {
        const state = ref({
          isSubmitButtonDisabled: false,
          isLoading: false,
          buttonText: 'Save',
          attendanceForm: null,
          lesson: '',
          student: '{{ $student->fname }} {{ $student->mname }} {{ $student->sname }}'
        });

        onMounted(() => {
            state.value.attendanceForm = document.querySelector('#attendance form');
        });

        const handleButtonClick = async () => {
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

        function notification($text, $icon){
            Swal.fire({
                toast: true,
                position: "center",
                text: $text,
                showConfirmButton: false,
                timer: 5500,
                timerProgressBar: true,
                icon: $icon,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
              });
        }

        return {
          state,
          handleButtonClick
        };
      }
    });

    app.mount('#attendance');
</script>

@endsection
