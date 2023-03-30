<div class="modal" id="modal-block-addfleet" tabindex="-1" aria-labelledby="modal-block-addfleet" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="block block-rounded block-themed block-transparent mb-0">
          <div class="block-header bg-primary-dark">
            <h3 class="block-title">Add to fleet</h3>
            <div class="block-options">
              <button type="button" class="btn-block-option" data-bs-dismiss="modal" aria-label="Close">
                <i class="fa fa-fw fa-times"></i>
              </button>
            </div>
          </div>
          <div class="block-content">
            <form class="mb-5" action="{{ url('/storefleet') }}" method="post" enctype="multipart/form-data" onsubmit="return true;">
                  @csrf 
              <div class="row">
                  <div class="col-6 form-floating mb-4">
                      <input type="text" class="form-control" id="car_brand_model" name="car_brand_model" required>
                      <label for="car_brand_model">Car brand/Model</label>
                  </div>
                  <div class="col-6 form-floating mb-4">
                      <input type="text" class="form-control" id="reg_number" name="reg_number" required>
                      <label>Car number plate</label>
                  </div>
              </div>
              <div class="col-12 form-floating mb-4">
                  <textarea class="form-control" id="car_description" name="car_description" style="height: 100px"></textarea>
                  <label>Car description</label>
              </div>
              <div class="col-12 form-floating mb-4">
                  <input type="file" class="form-control" id="fleet_image" name="fleet_image">
                  <label>Car Image</label>
              </div>
              <div class="form-floating mb-4">
                <select class="form-select" id="instructor" name="instructor">
                  @foreach ($instructor as $instructor)
                     <option value="{{$instructor->fname}} {{$instructor->sname}}">{{$instructor->fname}} {{$instructor->sname}}</option>
                  @endforeach
                </select>
                <label for="lesson">Lesson instructor</label>
              </div>
              <div class="block-content block-content-full text-end bg-body">
                  <button type="submit" class="btn btn-primary">Save</button>
                  <button type="button" class="btn btn-sm btn-alt-secondary" data-bs-dismiss="modal">Close</button>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
      var pathInstructor = "{{ route('instructorSearch') }}";
      $('#instructor').typeahead({
          source:  function (query, process) {
          return $.get(pathInstructor, { query: query }, function (data) {
                  return process(data);
              });
          }
      });
  </script>