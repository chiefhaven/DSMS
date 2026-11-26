<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\FleetUpdateSubmission;
use App\Models\Instructor;
use App\Services\FleetOdometerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FleetUpdateSubmissionController extends Controller
{
    private const EVIDENCE_UPLOADS = [
        'odometer_picture' => 'odometer_picture_path',
        'service_date_picture' => 'service_date_picture_path',
        'roadworthiness_picture' => 'roadworthiness_picture_path',
        'insurance_picture' => 'insurance_picture_path',
    ];

    private const EVIDENCE_FIELDS = [
        'odometer' => 'odometer_picture_path',
        'service-date' => 'service_date_picture_path',
        'cof-due' => 'roadworthiness_picture_path',
        'insurance-due' => 'insurance_picture_path',
    ];

    public function __construct()
    {
        $this->middleware('permission:approve fleet updates')->only(['approvals', 'approve', 'reject', 'evidence']);
    }

    public function index(Request $request, FleetOdometerService $odometerService)
    {
        $instructor = $this->practicalInstructor($request);
        $fleets = Fleet::with('instructor')
            ->where('instructor_id', $instructor->getKey())
            ->orderBy('car_registration_number')
            ->get();
        $currentOdometerByFleet = $fleets->mapWithKeys(fn (Fleet $fleet) => [
            $fleet->getKey() => $odometerService->currentReading($fleet),
        ]);
        $fleetIds = $fleets->modelKeys();
        $pendingByFleet = FleetUpdateSubmission::query()
            ->whereIn('fleet_id', $fleetIds)
            ->where('status', FleetUpdateSubmission::STATUS_PENDING)
            ->get()
            ->keyBy('fleet_id');
        $submissions = FleetUpdateSubmission::query()
            ->with(['fleet', 'reviewer'])
            ->where('submitted_by', $request->user()->getKey())
            ->latest()
            ->limit(20)
            ->get();

        return view('fleet.updates.index', compact('fleets', 'pendingByFleet', 'submissions', 'currentOdometerByFleet'));
    }

    public function store(Request $request, Fleet $fleet)
    {
        $instructor = $this->practicalInstructor($request);
        $validated = $request->validate([
            'odometer_km' => 'required|integer|min:0|max:4294967295',
            'odometer_reading_date' => 'required|date_format:Y-m-d|before_or_equal:today',
            'service_date' => 'required|date_format:Y-m-d',
            'roadworthiness_expiry' => 'required|date_format:Y-m-d',
            'insurance_expiry' => 'required|date_format:Y-m-d',
            'odometer_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'service_date_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'roadworthiness_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'insurance_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $storedEvidencePaths = [];

        try {
            foreach (self::EVIDENCE_UPLOADS as $input => $column) {
                if (!$request->hasFile($input)) {
                    continue;
                }

                $path = $request->file($input)->store('fleet-update-evidence', 'local');
                if (!$path) {
                    throw new \RuntimeException('Unable to store the uploaded fleet evidence image.');
                }
                $storedEvidencePaths[$column] = $path;
            }

            DB::transaction(function () use ($fleet, $instructor, $request, $validated, $storedEvidencePaths) {
                $lockedFleet = Fleet::whereKey($fleet->getKey())->lockForUpdate()->firstOrFail();
                abort_unless(
                    (string) $lockedFleet->instructor_id === (string) $instructor->getKey(),
                    404
                );

                $hasPendingSubmission = FleetUpdateSubmission::query()
                    ->where('fleet_id', $lockedFleet->getKey())
                    ->where('status', FleetUpdateSubmission::STATUS_PENDING)
                    ->exists();

                if ($hasPendingSubmission) {
                    throw ValidationException::withMessages([
                        'fleet' => 'An update for this vehicle is already awaiting approval.',
                    ]);
                }

                FleetUpdateSubmission::create(array_merge([
                    'fleet_id' => $lockedFleet->getKey(),
                    'submitted_by' => $request->user()->getKey(),
                    'odometer_km' => $validated['odometer_km'],
                    'odometer_reading_date' => $validated['odometer_reading_date'],
                    'service_date' => $validated['service_date'],
                    'roadworthiness_expiry' => $validated['roadworthiness_expiry'],
                    'insurance_expiry' => $validated['insurance_expiry'],
                    'status' => FleetUpdateSubmission::STATUS_PENDING,
                ], $storedEvidencePaths));
            });
        } catch (\Throwable $exception) {
            $this->deleteEvidenceFiles(array_values($storedEvidencePaths));
            throw $exception;
        }

        return redirect()->route('my-vehicle')
            ->with('message', 'Fleet update submitted. The current details will remain unchanged until approved.');
    }

    public function locations(Request $request, Fleet $fleet)
    {
        $instructor = $this->practicalInstructor($request);
        abort_unless(
            (string) $fleet->instructor_id === (string) $instructor->getKey(),
            404
        );

        return app(VehicleTrackerController::class)->showVehicleGeoData($request, $fleet->getKey());
    }

    public function approvals()
    {
        $approvalRequests = FleetUpdateSubmission::query()
            ->with(['fleet', 'submitter'])
            ->where('status', FleetUpdateSubmission::STATUS_PENDING)
            ->orderBy('created_at')
            ->paginate(20);

        return view('fleet.updates.approvals', compact('approvalRequests'));
    }

    public function evidence(FleetUpdateSubmission $submission, string $field)
    {
        $column = self::EVIDENCE_FIELDS[$field] ?? null;
        $path = $column ? $submission->{$column} : null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response(
            $path,
            basename($path),
            ['Cache-Control' => 'private, no-store'],
            'inline'
        );
    }

    public function approve(Request $request, FleetUpdateSubmission $submission)
    {
        $approved = DB::transaction(function () use ($request, $submission) {
            $lockedSubmission = FleetUpdateSubmission::whereKey($submission->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->status !== FleetUpdateSubmission::STATUS_PENDING) {
                return false;
            }

            $fleet = Fleet::whereKey($lockedSubmission->fleet_id)->lockForUpdate()->firstOrFail();
            $fleet->odometer_km = $lockedSubmission->odometer_km;
            $odometerReadingDate = $lockedSubmission->odometer_reading_date;
            $submittedAt = $lockedSubmission->created_at;
            $fleet->odometer_reading_date = $odometerReadingDate ?? $submittedAt->toDateString();
            $fleet->odometer_reading_at = (
                !$odometerReadingDate || $odometerReadingDate->isSameDay($submittedAt)
            )
                ? $submittedAt
                : $odometerReadingDate->copy()->addDay()->startOfDay();
            if ($lockedSubmission->service_date !== null) {
                $fleet->service_date = $lockedSubmission->service_date;
            }
            $fleet->roadworthiness_expiry = $lockedSubmission->roadworthiness_expiry;
            $fleet->insurance_expiry = $lockedSubmission->insurance_expiry;
            $fleet->save();

            $lockedSubmission->status = FleetUpdateSubmission::STATUS_APPROVED;
            $lockedSubmission->reviewed_by = $request->user()->getKey();
            $lockedSubmission->reviewed_at = now();
            $lockedSubmission->save();

            return true;
        });

        $message = $approved
            ? 'Fleet update approved and applied.'
            : 'This fleet update has already been reviewed.';

        return redirect()->route('fleet-update-approvals.index')->with('message', $message);
    }

    public function reject(Request $request, FleetUpdateSubmission $submission)
    {
        $validated = $request->validate([
            'review_note' => 'nullable|string|max:2000',
        ]);

        $rejected = DB::transaction(function () use ($request, $submission, $validated) {
            $lockedSubmission = FleetUpdateSubmission::whereKey($submission->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->status !== FleetUpdateSubmission::STATUS_PENDING) {
                return false;
            }

            $lockedSubmission->status = FleetUpdateSubmission::STATUS_REJECTED;
            $lockedSubmission->reviewed_by = $request->user()->getKey();
            $lockedSubmission->reviewed_at = now();
            $lockedSubmission->review_note = $validated['review_note'] ?? null;
            $lockedSubmission->save();

            return true;
        });

        $message = $rejected
            ? 'Fleet update rejected. Current fleet details were not changed.'
            : 'This fleet update has already been reviewed.';

        return redirect()->route('fleet-update-approvals.index')->with('message', $message);
    }

    private function practicalInstructor(Request $request): Instructor
    {
        $instructor = $request->user()->Instructor()->with('department')->first();
        abort_unless(
            $instructor && strtolower((string) $instructor->department?->name) === 'practical',
            404
        );

        return $instructor;
    }

    private function deleteEvidenceFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            if (Storage::disk('local')->exists($path) && !Storage::disk('local')->delete($path)) {
                Log::warning('Unable to remove a fleet evidence image after a failed submission.', [
                    'path' => $path,
                ]);
            }
        }
    }
}
