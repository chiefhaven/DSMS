<div class="tab-pane fade active show" id="system" role="tabpanel" aria-labelledby="system-tab">
    <div class="fs-3 fw-semibold pt-2 pb-4 mb-4 border-bottom">
        System settings
      </div>
      <div class="row">
        <form class="mb-5 form-inline" action="{{url('/attendance-time-update')}}" method="post" enctype="multipart/form-data">
          @csrf
          <div class="row haven-floating">
            <div class="mb-4 col-md-4 form-floating">
                <input type="time" class="form-control @error('timestart') is-invalid @enderror" id="timestart" name="timestart" value="{{$setting->attendance_time_start->format('H:i')}}">
                <label class="form-label" for="example-school-name-input-floating">Time start</label>
                <p class="muted small"><em>Allowable start time for attendance entry</em></p>
            </div>
            <div class="mb-4 col-md-4 form-floating">
                <input type="time" class="form-control @error('timestop') is-invalid @enderror" id="timestop" name="timestop" value="{{$setting->attendance_time_stop->format('H:i')}}">
                <label class="form-label" for="example-school-name-input-floating">Time stop</label>
                <p class="muted small"><em>Allowable finish time for attendance entry</em></p>
            </div>
            <div class="mb-4 col-md-4 form-floating">
                <input type="number" class="form-control @error('time_between_attendances') is-invalid @enderror" id="time_between_attendances" name="time_between_attendances" value="{{$setting->time_between_attendances}}">
                <label class="form-label">Time attendance</label>
                <p class="muted small"><em>Time in minutes an Instructor is allowed to enter attendances</em></p>
            </div>
          </div>
          <div class="row haven-floating">
            <div class="mb-4 col-md-6 form-floating">
                <input type="number" class="form-control @error('fees_threshold') is-invalid @enderror" id="fees_threshold" name="fees_threshold" value="{{$setting->fees_balance_threshold}}" placeholder="Lessons threshold (%)">
                <label class="form-label" for="fees_threshold">Fees balance threshold (%)</label>
                <p class="muted small"><em>Fees percent at which student is allowed to continue with attending lessons</em></p>
            </div>
            <div class="mb-4 col-md-6 form-floating">
                <input type="number" class="form-control @error('lesson_threshold') is-invalid @enderror" id="lesson_threshold" name="lesson_threshold" value="{{$setting->attendance_threshold}}" placeholder="Lessons threshold (%)">
                <label class="form-label">Lessons threshold (%)</label>
                <p class="muted small"><em>Attendance percent at which student is allowed to continue with attending lessons when they have balance</em></p>
            </div>
          </div>
          <br>
          <div class="form-group">
              <button type="submit" class="btn btn-primary">Update</button>
          </div>
        </form>
      </div>
  </div>
