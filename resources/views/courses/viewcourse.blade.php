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

                <!-- Loading overlay -->
                <div v-if="isLoading" class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading course details...</p>
                </div>

                <template v-else>
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
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Name</td>
                                    <td>@{{ course.name }}, @{{ course.short_description }}</td>
                                </tr>
                                <tr>
                                    <td>Class</td>
                                    <td>@{{ course.class }}</td>
                                </tr>
                                <tr>
                                    <td>Duration</td>
                                    <td>@{{ lessonSummary }}</td>
                                </tr>
                                <tr>
                                    <td>Fees</td>
                                    <td>
                                        <p>@{{ formattedPrice(course.price) }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Lessons</td>
                                    <td class="lessonsDetails" @click="OpenCourseLessonsModal">
                                        <p class="text-primary">@{{ theoryCount }} Theory, @{{ practicalCount }} Practical</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                    </div>
                </template>

        </div>
        </div>
    </div>
</div>

<script>
    const viewCourse = createApp({
      setup() {
        const courseLessonsModal = ref(false);
        const courseLessons = ref([]);
        const theoryCount = ref(0);
        const practicalCount = ref(0);
        const courseId = {{ $courseId }};
        const showActions = ref(false);
        const showCourseLessons = ref(true);
        const lessonName = ref('');
        const isLoading = ref(false);

        const course = ref({
            id: null,
            name: '',
            short_description: '',
            price: 0,
            duration: 0,
            status: '',
            created_at: null,
            updated_at: null,
            class: '',
            instructor: null
        });

        const lessonSummary = computed(() => {
            const theory    = parseInt(theoryCount.value) || 0;
            const practical = parseInt(practicalCount.value) || 0;
            const total     = theory + practical;

            const parts = [];
            if (theory) parts.push(`${theory} Theory`);
            if (practical) parts.push(`${practical} Practical`);

            return parts.length
                ? `${total} Lessons`
                : 'No lessons assigned';
        });

        const OpenCourseLessonsModal = () => {
            courseLessonsModal.value = true;
        };

        const updateCourseLessons = () => {
            showActions.value = true;
            showCourseLessons.value = false;
        };

        onMounted(() => {
            fetchCourseDetails(courseId);
        });

        const closeForm = () => {
            courseLessonsModal.value = false;
            showActions.value = false;
            showCourseLessons.value = true;
        };

        const back = () => {
            courseLessonsModal.value = true;
            showCourseLessons.value = true;
            showActions.value = false;
        };

        const confirmRemoveLesson = (lesson) => {
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

        const removeLesson = async (lessonId) => {
            if (!lessonId) {
                notification('Invalid lesson provided for deletion.', 'error');
                return;
            }

            try {
                NProgress.start();
                await axios.delete(`/course-lesson/${courseId}/${lessonId}`);
                courseLessons.value = courseLessons.value.filter(l => l.id !== lessonId);
                notification('Lesson removed successfully.', 'success');
            } catch (error) {
                notification(
                    error.response?.data?.message || 'Failed to remove lesson. Please try again.',
                    'error'
                );
            } finally {
                NProgress.done();
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
                const payload = {
                    courseLessons: courseLessons.value.map((lesson) => ({
                        id: lesson.id,
                        lesson_quantity: lesson.pivot.lesson_quantity,
                        order: lesson.pivot.order,
                    })),
                    courseId: courseId,
                };

                const response = await axios.put('/update-course-lesson', payload);

                if (response.status === 200 || response.status === 201) {
                    notification('Lesson saved successfully.', 'success');
                    closeForm();
                    fetchCourseDetails(courseId);
                } else {
                    throw new Error('Unexpected response status');
                }
            } catch (error) {
                notification(
                    error.response?.data?.message || 'Failed to save the lesson. Please try again.',
                    'error'
                );
            } finally {
                NProgress.done();
            }
        };

        const lessonSearch = () => {
            const path = "{{ route('lesson-search') }}";

            $('#lesson').typeahead({
                minLength: 1,
                highlight: true,
                source: function (query, process) {
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        NProgress.start();
                        $.get(path, { search: query }, function (data) {
                            if (!data || data.length === 0) {
                                notification('No lesson found. Add one if needed.', 'error');
                            }
                            process(data);
                        }).fail(function () {
                            notification('There was an issue fetching the lessons. Please try again later.', 'error');
                        }).always(function () {
                            NProgress.done();
                        });
                    }, 300);
                },
                displayText: function (item) {
                    return item.name;
                },
                afterSelect: function (item) {
                    const existingLesson = courseLessons.value.find(l => l.id === item.id);

                    if (existingLesson) {
                        existingLesson.pivot.lesson_quantity += 1;
                    } else {
                        courseLessons.value.push({
                            ...item,
                            pivot: {
                                lesson_quantity: 1,
                                order: 0,
                                course_id: courseId,
                                lesson_id: item.id,
                            },
                        });
                    }

                    $('#lesson').val('');
                },
            });
        };

        const handleRowChanges = (index) => {
            const lesson = courseLessons.value[index];
            if (!lesson) return;

            if (lesson.pivot.lesson_quantity < 1) lesson.pivot.lesson_quantity = 1;
            if (lesson.pivot.order < 0) lesson.pivot.order = 0;
        };

        const fetchCourseDetails = async (courseId) => {
            isLoading.value = true;
            NProgress.start();

            try {
                const response = await axios.get(`/course-details/${courseId}`);

                if (response.data) {
                    const lessons = (response.data.course.lessons || [])
                        .sort((a, b) => (a.pivot?.order || 0) - (b.pivot?.order || 0));

                    courseLessons.value  = lessons;
                    course.value         = response.data.course || {};
                    theoryCount.value    = parseInt(response.data.theoryCount) || 0;
                    practicalCount.value = parseInt(response.data.practicalCount) || 0;
                } else {
                    throw new Error('Invalid response data');
                }
            } catch (error) {
                console.error('Error fetching course details:', error);
                notification('Failed to load lessons. Please try again later.', 'error');
            } finally {
                isLoading.value = false;
                NProgress.done();
            }
        };

        const notification = ($text, $icon) => {
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
        };

        return {
            OpenCourseLessonsModal,
            courseLessonsModal,
            closeForm,
            confirmRemoveLesson,
            removeLesson,
            courseLessons,
            course,
            theoryCount,
            practicalCount,
            lessonSummary,
            formattedPrice,
            updateCourseLessons,
            showActions,
            showCourseLessons,
            saveCourseLessons,
            back,
            lessonName,
            lessonSearch,
            handleRowChanges,
            isLoading,
        };
      }
    });

    viewCourse.mount('#viewCourse');
</script>

<style scoped>
    .lessonsDetails {
        transition: background-color 0.3s ease, transform 0.3s ease;
    }

    .lessonsDetails:hover {
        background-color: #f0f0f0;
        cursor: pointer;
        transform: scale(1.02);
    }

    td p {
        font-size: 14px;
        color: #333;
    }

    td p span {
        font-weight: bold;
        color: #0a0092;
    }
</style>

@endsection
