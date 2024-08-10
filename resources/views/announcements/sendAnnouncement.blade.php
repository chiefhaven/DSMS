@extends('layouts.backend')

@section('content')
  <!-- Hero -->
  <div class="bg-body-light">
    <div class="content content-full">
      <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">Make announcement</h1>
        <nav class="flex-shrink-0 my-2 my-sm-0 ms-sm-3" aria-label="breadcrumb"></nav>
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
    <div class="block block-rounded" id="announcement">
    <div class="block-content">
        <div class="row">
            <div class="col-lg-12 col-xl-12">
                <form ref="state.announcementForm" class="mb-5" @submit.prevent="handleButtonClick">
                        @csrf
                    <div class="row">
                        <div class="col-4 col-sm-12 form-floating mb-4">
                            <select class="form-select" id="group" name="group"  v-model="state.group" @blur="getBalanceTemplate()">
                                <option>All students</option>
                                <option>Students with balance</option>
                            </select>
                            <label class="px-4" for="group">Group</label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mt-4">
                            <P>
                                Available tags:
                                {{--  {FIRST_NAME} {SIR_NAME} {BALANCE} {FEES_PAID} {FEES_TOTAL} {INVOICE_NUMBER}  --}}
                            </P>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 form-floating mb-4">
                            <textarea class="form-control" id="body" name="body" rows="5" placeholder="Announcement Body" v-model="state.body" required></textarea>
                            <label class="px-4" for="invoice_discount">Body</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" :disabled="state.isSubmitButtonDisabled">
                            <template v-if="state.isLoading">
                                Processing...
                            </template>
                            <template v-else>
                                @{{ state.buttonText }}
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>
  </div>
  <!-- END Hero -->
  <script setup>
    const { createApp, ref, onMounted } = Vue;

    const app = createApp({
      setup() {
        const state = ref({
          isSubmitButtonDisabled: false,
          isLoading: false,
          buttonText: 'Send',
          announcementForm: null,
          body: 'Body here',
          group: ''
        });

        onMounted(() => {
            state.value.announcementForm = document.querySelector('#announcement form');
          })

          const handleButtonClick = async () => {
            state.value.isSubmitButtonDisabled = true;
            state.value.isLoading = true;
            state.value.buttonText = "Processing...";

            if (state.value.announcementForm) {
              try {
                const response = await axios.post('/sendAnnouncement', {
                    body: state.value.body,
                    group: state.value.group
                });
                if (response.status === 200) {
                    notification('Announcement sent', 'success');
                } else {
                    notification('There is an error, announcement not sent', 'error');
                }
              } catch (error) {
                    notification('An error occurred, announcement not sent', 'error');
              } finally {
                    state.value.isSubmitButtonDisabled = false;
                    state.value.isLoading = false;
                    state.value.buttonText = "Send";
              }
            } else {
                notification('An error occurred, announcement not sent', 'error');
                state.value.isSubmitButtonDisabled = false;
                state.value.isLoading = false;
                state.value.buttonText = "Send";
            }
          };


        function getBalanceTemplate() {

            if(state.value.group == 'Students with balance')
                axios.post('/get-balance-template').then(response => {
                    if(response.status==200){
                        state.value.body = response.data
                    }
                    else if(error.response.data.errors){
                        notification('error.response.data.errors.message','error')
                    }
                    else{
                        return false
                    }
                });
            else
                state.value.body = ''


        }


        function notification($text, $icon){
            Swal.fire({
                toast: true,
                position: "top-right",
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
          handleButtonClick,
          getBalanceTemplate
        };
      }
    });

    app.mount('#announcement');
</script>

@endsection
