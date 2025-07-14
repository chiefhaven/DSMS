<?php
    namespace App\Http\Controllers;

    use App\Models\Invoice;
    use App\Models\Course;
    use App\Models\Fleet;
    use App\Models\Student;
    use App\Models\Instructor;
    use App\Models\District;
    use App\Models\Lesson;
    use App\Models\Attendance;
    use App\Models\ExpenseTypeOption;
    use App\Models\PaymentMethod;
    use App\Models\Invoice_Setting;
    use App\Models\TrainingLevel;
    use Carbon\Carbon;
    use SimpleSoftwareIO\QrCode\Facades\QrCode;
    use Auth;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\Log;

    class havenUtils extends Controller
    {
        static function student($studentName){

            $student_name = explode(" ", $studentName);
            $studentnameCount = count($student_name);

            if($studentnameCount = 2){

                $student = Student::where('fname', $student_name[0])->where('sname',$student_name[2])->first();

                if(!is_null($student)){

                    return $student;
                }

                else{

                    return null;
                }
            }

            elseif($studentnameCount = 3){

                $student = Student::where('fname', $student_name[0])->where('mname',$student_name[1])->where('sname',$student_name[2])->first();

                if(!is_null($student)){

                    return $student;
                }

                else{

                    return null;
                }
            }

            else{

                return null;

            }

        }

        public function getLessons(){
            $lessons = Lesson::get();
            return response()->json($lessons);
        }

        public function autocompleteLessonSearch(Request $request){
            // Retrieve the search term from the request
            $searchTerm = $request->input('search');

            if (empty($searchTerm)) {
                // Return an empty JSON response if no input is provided
                return response()->json([]);
            }

            // Fetch lessons matching the search term
            $datas = \DB::table('lessons')
                ->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                ->get();

            return response()->json($datas);
        }


        static function studentID_InvoiceNumber($invoiceNumber){

                $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();
                return $invoice;

        }

        static function instructorID($instructorName){

            $instructor_name = explode(" ", $instructorName);

            $instructor = Instructor::where('fname', $instructor_name[0])->where('sname',$instructor_name[1])->firstOrFail();
            return $instructor->id;
        }


        static function courseID($courseName){

            $course = Course::where('name', $courseName)->firstOrFail();
            return $course->id;
        }

        static function trainingLevelID($trainingName){
            $trainingLevel = TrainingLevel::where('name', $trainingName)->first();
            return $trainingLevel->id;
        }

        static function invoiceDiscountedPrice($courseName, $discount){

            $course = Course::where('name', $courseName)->firstOrFail();
            $discount = $discount;
            $total_price = $course->price - $discount;
            return $total_price;
        }

        static function coursePrice($courseName){

            $course = Course::where('name', $courseName)->firstOrFail();
            return $course->price;
        }



        static function invoiceTotal($courseName, $invoiceDiscount){

            $course = Course::where('name', $courseName)->firstOrFail();

            $invoiceTotal = $course->price-$invoiceDiscount;
            return $invoiceTotal;
        }

        static function invoiceBalance($paidAmount, $invoiceTotal){

            $invoiceBalance = $invoiceTotal - $paidAmount;
            return $invoiceBalance;
        }

        static function invoicePaid($invoiceNumber, $paid_amount){

            $invoicePaid = Invoice::find($invoiceNumber)->invoice_amount_paid + $paid_amount;
            return $invoicePaid;
        }


        static function selectDistrict($district){

            $district = District::where('name', $district)->firstOrFail();
            return $district->id;
        }

        static function lessonID($lessonName){

            $lesson = Lesson::where('name', $lessonName)->firstOrFail();
            return $lesson->id;
        }

        static function attendancePercent($studentID){
            $course_id = Student::where('id', $studentID)->value('course_id');

            $attendanceCount = 0;
            $attendancePercent = 0;

            if (!is_null($course_id)) {
                $courseDuration = self::courseDuration($course_id);
                $attendanceCount = Attendance::where('student_id', $studentID)->count();

                if ($attendanceCount > 0 && $courseDuration > 0) {
                    $attendancePercent = ($attendanceCount / $courseDuration) * 100;
                }
            }

            return [
                'attendancePercent' => number_format((int)$attendancePercent),
                'attendanceCount' => $attendanceCount
            ];
        }


        //check for course Duration a students is enrolled in based on current invoice
        static function courseDuration($course_id){

            $course = Course::where('id', $course_id)->first();
            //$courseDuration = $course->duration;
            $courseDuration = $course->lessons->sum('pivot.lesson_quantity');
            return $courseDuration;
        }

        //Generate invoice number
        public static function generateInvoiceNumber(){
            // Fetch invoice settings once to avoid multiple queries
            $invoiceSettings = Invoice_Setting::find(1);
            $prefix = $invoiceSettings->prefix ?? 'Invoice';
            $useYear = $invoiceSettings->year ?? 0;

            // Build the query for the latest invoice
            $query = Invoice::whereMonth('created_at', Carbon::now());

            if ($useYear) {
                $query->whereYear('created_at', Carbon::now());
            }

            $latestInvoice = $query->orderBy('id', 'desc')->first();

            // Extract and increment the invoice number
            if ($latestInvoice) {
                $highestInvoiceNumber = $latestInvoice->invoice_number;
                $invoiceNumberOnly = (int)substr(strrchr($highestInvoiceNumber, '-'), 1);
                $newInvoiceNumber = sprintf("%05d", $invoiceNumberOnly + 1);
            } else {
                $newInvoiceNumber = sprintf("%05d", 1);
            }

            // Generate the invoice number based on settings
            if ($useYear) {
                $invoiceNumber = "{$prefix}-" . date('Y') . '-' . date('m') . "-{$newInvoiceNumber}";
            } else {
                $invoiceNumber = "{$prefix}-{$newInvoiceNumber}";
            }

            return $invoiceNumber;
        }


        //Get a payment method
        static function paymentMethod($paymentMethod){

            $paymentMethod = PaymentMethod::where('name', $paymentMethod)->firstOrFail()->id;
            return $paymentMethod;
        }

        static function fleetID($carRegistrationNumber){



                $fleet = Fleet::where('car_registration_number', $carRegistrationNumber)->first();

                if(!is_null($fleet)){

                    return $fleet->id;
                }

                else{

                    return null;
                }

        }

        static function qrCode($link){
            $qrCode = base64_encode(QrCode::format('svg')->size(120)->errorCorrection('H')->generate($link));
            return $qrCode;
        }

        static function checkStudentInstructor($studentId){
            // Ensure there is an authenticated user
            $user = Auth::user();

            if (!$user || !$user->instructor_id) {
                return false;
            }

            // Retrieve the instructor's fleet
            $instructorFleet = Fleet::where('instructor_id', $user->instructor_id)->first();
            if (!$instructorFleet) {
                return false; // Instructor does not have a fleet assigned
            }

            // Retrieve the student and validate their fleet
            $student = Student::find($studentId);
            return $student && $instructorFleet->id == $student->fleet_id;
        }

        static function getExpenceTypeOption($optionId){
            $expenseTypeOption = ExpenseTypeOption::find($optionId);

            if (!$expenseTypeOption) {
                return false;
            }

            return $expenseTypeOption->name;
        }


        static function checkClassRoom($studentId){
            // Ensure there is an authenticated user and retrieve their classroom IDs
            $user = Auth::user();

            if (!$user || !$user->instructor || !$user->instructor->classrooms()->exists()) {
                return false; // Instructor does not have a classroom assigned
            }

            // Get all classroom IDs associated with the instructor
            $classroomIds = $user->instructor->classrooms->pluck('id');

            // Retrieve the student and validate their classroom
            $student = Student::find($studentId);
            if (!$student) {
                Log::warning("Student not found with ID: $studentId");
                return false;
            }

            // Check if the student's classroom ID exists in the instructor's classrooms
            return $classroomIds->contains($student->classroom_id);
        }

        static function invoiceQrCode($id){
            $invoice = Invoice::with('student')->find($id);
            $student = $invoice->student;

            if(!isset($student)){
                abort(404);
            }
            return $student;
        }

        static function docsQrCode($id){
            $student = Student::find($id);
            if(!isset($student)){
                abort(404);
            }
            return $student;
        }

        public function checkInstructorClassFleetAssignment(Request $request)
        {
            $data = $request->all();

            // Validate the incoming request data
            $request->validate([
                'instructor' => 'required|exists:instructors,id'
            ]);

            // Check if the instructor has a fleet assigned
            $fleetAssigned = Fleet::where('instructor_id', $data['instructor'])->exists();

            return response()->json($fleetAssigned);
        }

        public function getStudentLessons(Request $request)
        {
            $studentId = $request->input('studentId');

            // Validate the student ID
            if (!$studentId) {
                return response()->json(['error' => 'Something went wrong'], 400);
            }

            // Retrieve the lessons for the student
            $student = Student::find($studentId);

            if ($student && $student->course) {
                // Group attendance records by lesson_id and count occurrences
                $lessonsCount = $student->attendance
                ->groupBy('lesson_id')
                ->map(fn($group) => $group->count());

                // Filter and map lessons
                $lessons = $student->course->lessons
                ->filter(function ($lesson) use ($lessonsCount) {

                    // Get attendance count or default to 0
                    $attendanceCount = $lessonsCount->get($lesson->id, 0);

                    // Include lessons with lesson_quantity > attendanceCount
                    return $lesson->pivot->lesson_quantity > $attendanceCount;
                })
                ->map(function ($lesson) use ($lessonsCount) {
                    // Add an 'attended' flag
                    $lesson->attended = $lessonsCount->has($lesson->id);
                    return $lesson;
                })
                ->sortBy('pivot.order') // Sort lessons by the order field in the pivot table
                ->values(); // Reset collection keys

            } else {
                return response()->json(['error' => 'Student or course not found'], 404);
            }

            return response()->json($lessons);
        }

        public function StudentLessonAttendancesCount(Request $request)
        {
            $studentId = $request->query('studentId');
            $lessonId = $request->query('lessonID');

            Log::info('StudentLessonAttendancesCount called', [
                'studentId' => $studentId,
                'lessonId' => $lessonId,
            ]);

            if (!$studentId || !$lessonId) {
                Log::warning('Missing parameters in StudentLessonAttendancesCount', [
                    'studentId' => $studentId,
                    'lessonId' => $lessonId,
                ]);
                return response()->json(['message' => 'Missing parameters.'], 400);
            }

            $count = Attendance::where('student_id', $studentId)
                        ->where('lesson_id', $lessonId)
                        ->count();

            Log::info('Attendance count result', [
                'studentId' => $studentId,
                'lessonId' => $lessonId,
                'count' => $count,
            ]);

            return response()->json(['count' => $count]);
        }
    }
