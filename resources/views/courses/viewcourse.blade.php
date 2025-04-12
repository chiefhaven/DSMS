@extends('layouts.backend')

@section('content')
<!-- Hero -->
<div id="viewCourse">
    <div class="bg-body-light">
    <div class="content content-full">
        <div class="d-flex flex-sm-row justify-content-sm-between align-items-sm-center">
        <h1 class="flex-grow-1 fs-3 fw-semibold my-2 my-sm-3">@{{course.name}}</h1>
        </div>
    </div>
    </div>

    <div class="bg-image">
        @include('/courses/includes/courseLessonsModal')

        <div class="">
            <div class="content content-full row">
                <div class="col-sm-4" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
                <div class="py-5">
                    <div class="block-content block-content-full">
                        <i class="fa fa-fw fa-book fa-2xl fa-haven-size text-large"></i>
                    </div>
                </div>
                </div>
                <div class="col-sm-7" style="background: #ffffff; margin: 0 10px; border-radius: 5px; border: thin solid #cdcdcd;">
                <div class="py-5">
                    <p><strong>General Information</strong></p>
                <div class="table-responsive">
                    <table class="table table-bordered ">
                        <thead>

                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    Name
                                </td>
                                <td>
                                    @{{ course.name }}, @{{ course.short_description }}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Class
                                </td>
                                <td>
                                    @{{ course.class }}
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Duration
                                </td>
                                <td>
                                    @{{ course.duration }} days
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    Fees
                                </td>
                                <td>
                                    <p>@{{ formattedPrice(course.price) }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                  Lessons
                                </td>
                                <td class="lessonsDetails" @click="OpenCourseLessonsModal">
                                  <p class="text-primary">
                                    Theory @{{ theoryCount }},
                                    Practicals @{{ practicalCount }}
                                  </p>
                                </td>
                              </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                </div>
        </div>
        </div>
    </div>
</div>

<script>

    const { createApp, ref, reactive, onMounted, computed } = Vue

    const viewCourse = createApp({
      setup() {
        const courseLessonsModal = ref(false);
        const courseLessons = ref([]);
        const theoryCount = ref();
        const practicalCount = ref();
        const courseId = {{ $courseId }};
        const showActions = ref(false);
        const showCourseLessons = ref(true);
        const lessonName = ref('');
        const lesson_quantities = ref([]);
        const lesson_orders = ref([]);

        const course = ref({
            id: "1",
            name: "course name",
            short_description: "description",
            price: 0,
            duration: 0,
            practicals: 0,
            theory: 0,
            status: "status",
            created_at: null,
            updated_at: "timestamp",
            class: "class",
            instructor: null
        });

        const OpenCourseLessonsModal = (lesson) => {
            courseLessonsModal.value = true;
        };

        const updateCourseLessons = () => {
            showActions.value = true;
            showCourseLessons.value = false;
        };

        onMounted(() => {
            NProgress.start();
            fetchCourseDetails(courseId);
        });


        const closeForm = () => {
            courseLessonsModal.value = false;
            showActions.value = false;
            showCourseLessons.value = true;
        }

        const back = () => {
            courseLessonsModal.value = true;
            showCourseLessons.value = true;
            showActions.value = false;
        }

        const confirmRemoveLesson = async(lesson) => {
            Swal.fire({
                title: 'Delete Lesson?',
                text: 'Do you want to remove this lesson? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    removeLesson(lesson);
                }
            });
        };

        const removeLesson = async(lesson) => {
            if (!lesson) {
                notification('Invalid lesson provided for deletion.', 'error');
                return;
            }

            // Perform the delete action
            try {

                courseLessons.value = courseLessons.value.filter((l) => l.id !== lesson);

            } catch (error) {
                notification('error.response.data.message', 'error');
            }
        };

        const formattedPrice = (price) => {
            return new Intl.NumberFormat('en-MW', {
                style: 'currency',
                currency: 'MWK',
            }).format(price);
        };


        const saveCourseLessons = async () => {
            NProgress.start();

            try {

                // Prepare the payload
                const payload = {
                    courseLessons: courseLessons.value.map((lesson) => ({
                        id: lesson.id,
                        lesson_quantity: lesson.pivot.lesson_quantity,
                        order: lesson.pivot.order,
                    })),
                    courseId: courseId,
                };

                // Determine if this is an update or a new lesson
                const endpoint = courseId
                    ? `/update-course-lesson`
                    : '/storelesson';
                const method = courseId ? 'put' : 'post';

                // Send the request
                const response = await axios[method](endpoint, payload);

                if (response.status === 200 || response.status === 201) {
                    // Handle success
                    notification('Lesson saved successfully.', 'success');
                    closeForm();
                    fetchCourseDetails(courseId);

                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                console.error('Error saving lesson:', error);

                notification(
                    error.response?.data?.message || 'Failed to save the lesson. Please try again.',
                    'error'
                );
            } finally {
                NProgress.done();
            }
        };

        function onLessonChange(event) {
            addLesson(); // Adds a new lesson
        }

        const addLesson = (quantity = 1, order = 0) => {
            // Ensure lesson_quantities and lesson_orders are arrays
            if (!Array.isArray(lesson_quantities.value)) {
                lesson_quantities.value = [];
            }
            if (!Array.isArray(lesson_orders.value)) {
                lesson_orders.value = [];
            }

            // Add the new quantity and order to their respective arrays
            lesson_quantities.value.push(quantity);
            lesson_orders.value.push(order);
        };

        const lessonSearch = () => {
            NProgress.start();
            const path = "{{ route('lesson-search') }}";

            $('#lesson').typeahead({
                minLength: 1, // Start searching after typing 1 character
                highlight: true, // Highlight matching results

                // Fetch the data from the server
                source: function (query, process) {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        $.get(path, { search: query }, function (data) {
                            if (!data || data.length === 0) {
                                notification('No lesson found. Add one if needed.', 'error');
                            }

                            process(data); // Pass data to typeahead
                            NProgress.done();
                        }).fail(function () {
                            console.error('Error fetching lessons');
                            notification(
                                "There was an issue fetching the lessons. Please try again later.",
                                'error'
                            );
                        });
                    }, 300); // Delay of 300ms
                },

                // Define how to display suggestions
                displayText: function (item) {
                    return item.name; // Display the lesson name in the dropdown
                },

                // Handle the event when a suggestion is selected
                afterSelect: function (item) {

                    // Check if the lesson already exists in the courseLessons array
                    const existingLesson = courseLessons.value.find(
                        (lesson) => lesson.id === item.id
                    );

                    if (existingLesson) {
                        // Increment the quantity if the lesson already exists
                        existingLesson.pivot.lesson_quantity += 1;

                        // Ensure lesson_quantities is initialized and update it
                        if (!lesson_quantities.value[existingLesson.id]) {
                            lesson_quantities.value[existingLesson.id] = 0;
                        }

                        lesson_quantities.value[existingLesson.id] += 1;

                    } else {
                        // Add a new lesson to the courseLessons array
                        courseLessons.value.push({
                            ...item,
                            pivot: {
                                lesson_quantity: 1,
                                course_id: courseId,
                                lesson_id: item.id,
                            },
                        });

                        // Initialize lesson quantity for the new lesson
                        lesson_quantities.value[item.id] = 1;
                        lesson_orders.value[item.id] = 0;
                    }

                    // Clear the search input after adding the lesson
                    $('#lesson').val('');
                },

            });
        };

        const validateQuantity = (index) => {
            if (lesson_quantities.value[index] < 1 && lesson_orders.value[index] < 1) {
                lesson_quantities.value[index] = 1; // Reset to minimum allowed value
                lesson_orders.value[index] = 0; // Reset to minimum allowed value
            }
        };

        const handleRowChanges = (index) => {
            // Validate the quantity to ensure it's not less than the minimum allowed value
            validateQuantity(index);

            // Retrieve the lesson from the selectedProducts array using the index
            const lesson = courseLessons.value[index];
            if (lesson) {
                // Update the lesson's quantity
                lesson.pivot.lesson_quantity = lesson_quantities.value[index];
                lesson.pivot.order = lesson_orders.value[index];
            }
        };

        const fetchCourseDetails = async (courseId) => {
            // Start the loading progress bar
            NProgress.start();

            try {
                // Send GET request to fetch course details
                const response = await axios.get(`/course-details/${courseId}`);

                // Check if response contains valid data
                if (response.data) {

                    courseLessons.value = (response.data.course.lessons || []).sort(
                        (a, b) => (a.pivot?.order || 0) - (b.pivot?.order || 0)
                    );

                    course.value = response.data.course || {};

                    theoryCount.value = response.data.theoryCount || 0;

                    practicalCount.value = response.data.practicalCount || 0;

                    lesson_quantities.value = (response.data.course.lessons || []).map(
                        lesson => lesson.pivot?.lesson_quantity || 0
                    );

                    lesson_orders.value = (response.data.course.lessons || []).map(
                        lesson => lesson.pivot?.order || 0
                    );

                } else {
                    throw new Error('Invalid response data');
                }
            } catch (error) {
                // Log error for debugging and show error notification
                console.error('Error fetching course details:', error);
                notification('Failed to load lessons. Please try again later.', 'error');
            } finally {
                // Ensure the NProgress loading bar finishes
                NProgress.done();
            }
        };

        const notification = ($text, $icon) =>{
            Swal.fire({
                toast: true,
                position: "top-end",
                html: $text,
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
            OpenCourseLessonsModal,
            courseLessonsModal,
            closeForm,
            confirmRemoveLesson,
            courseLessons,
            course,
            theoryCount,
            practicalCount,
            formattedPrice,
            updateCourseLessons,
            showActions,
            showCourseLessons,
            saveCourseLessons,
            back,
            lessonName,
            onLessonChange,
            lessonSearch,
            lesson_quantities,
            lesson_orders,
            handleRowChanges,
            removeLesson
        }
      }
    })

   viewCourse.mount('#viewCourse')

</script>

<style scoped>
    /* Style the table row */
    .lessonsDetails {
      transition: background-color 0.3s ease, transform 0.3s ease;
    }

    /* Change background color when hovering over the row */
    .lessonsDetails:hover {
      background-color: #f0f0f0;
      cursor: pointer; /* Makes it clear that it's clickable */
      transform: scale(1.02); /* Slightly zooms in the row on hover */
    }

    /* Style the text inside the table */
    td p {
      font-size: 14px;
      color: #333;
    }

    td p span {
      font-weight: bold;
      color: #0a0092; /* Add a color for emphasis */
    }
  </style>

@endsection
