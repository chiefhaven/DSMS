<h2>Haven's Driving School Managment System for Malawi</h2>

## Student numbers

Successful enrollment assigns a unique student number: `YYYY` + three-digit
day of year + daily sequence (minimum three digits). For example, `2026314001`
is the first enrollment on 10 November 2026. Dates use the school's runtime timezone
(Africa/Blantyre, configured in `AppServiceProvider`).
Numbers stay unchanged on re-enrollment; UUIDs remain the internal record IDs.

After applying the student-number migration, run `php artisan students:backfill-numbers`
to number existing enrolled students by their earliest active invoice creation
date. The command is safe to rerun and leaves unenrolled students unnumbered.

## Resource routes

CRUD screens use Laravel resource routes (`index`, `create`, `store`, `show`,
`edit`, `update`, `destroy`) and named links/forms. Updates use `PUT`/`PATCH`;
deletions use `DELETE`. HTML forms submit through Laravel's `_method` field.
The resource identifier in the URL takes precedence over legacy body identifiers.

Old URLs remain compatibility endpoints. Reports, assignments, enrollment actions,
notifications, and other non-CRUD operations retain dedicated routes. Controllers
with unimplemented actions expose only their supported resource methods; settings
use singleton routes. API resource routes retain API authentication.

## Vehicle entry

Vehicle create/edit forms require separate make and model fields and engine
capacity (positive whole cc), including legacy submissions. Existing records may
remain blank until edited; saving requires an engine capacity. The combined
vehicle name is retained for existing lists, reports, and tracking integrations.
Older clients may still submit `car_brand_model`; omitted new fields are preserved.
Existing vehicle names are not automatically split into make/model.

Optional specifications include manufacture year, VIN/chassis and engine numbers,
fuel, transmission, colour, odometer (whole kilometres),
and seating capacity.
Insurance provider/expiry, roadworthiness expiry, last service date, and next
service mileage are also optional. Vehicle details group specifications into
Vehicle, Engine & drive, and Insurance & service cards with aligned labels and values.
Apply migration `2026_11_11_004500_add_vehicle_details_to_fleets` before using the forms.
Engine capacity also requires `2026_11_11_005700_add_engine_capacity_to_fleets`.

## Staff accounts

Instructor and administrator details have Profile and Account management tabs. Users with
`manage instructor accounts` for instructors, or `manage administrator accounts` for
administrators, can edit the linked account's username/email, set a
confirmed password (minimum eight characters), and enable/disable login.
Leaving the password blank preserves it. Disabling login retains the staff
profile and any assignments, revokes API tokens/remember-me access, and blocks existing
web sessions and authenticated API requests. Changing a password also revokes API
tokens and remember-me access. Managers cannot deactivate their own account.
Apply `2026_11_11_163500_add_is_active_to_users_table` before using this feature;
existing users default to enabled. Profile employment status remains separate
from login status.

Instructors with `access portal` can open their dashboard after login. Their
dashboard and attendance summary show their own information; management
summaries still require `access management`. Students with `access portal` can
also open the student dashboard. Apply
`2026_11_11_120000_seed_access_permissions` to grant the default role permissions.

Schedule access uses separate view, view-all, create, edit, delete, and restore
permissions. Viewing all schedules requires both view and view-all permissions.
Instructors without view-all access see only their own schedules;
editing and deleting also require an instructor profile and ownership. Apply
`2026_11_12_000000_seed_schedule_permissions` for the default role grants.

Role management separately controls viewing, creating, editing, and deleting
roles. Apply `2026_11_12_000100_seed_role_action_permissions`; existing
`manage system` grants retain these actions.

Student access separates viewing, viewing all, creation, editing, deletion,
assignment, status changes, reports, and letters. Users without `view all
students` need an instructor profile and can access only students assigned to
their vehicles or classrooms, including when performing permitted actions.
Apply `2026_11_12_000200_seed_student_permissions` for the default grants.
Existing `manage system` grants are carried over when the same role or direct
user grant also had `access operations`.

Course permissions separately control viewing, creation, editing, deletion, and
editing course lessons. Apply `2026_11_12_000300_seed_course_permissions`;
existing `manage administration` grants retain these actions.

Announcement form access and sending are separate permissions. Apply
`2026_11_12_000800_seed_announcement_permissions`; existing `manage system`
grants retain sending, while form access preserves the prior requirement for
both `manage system` and `manage administration` on the same role or direct
grant.

Notification template viewing and editing use separate permissions. Apply
`2026_11_12_001400_seed_notification_template_permissions`; roles and direct
users with `manage administration` receive both permissions. Other users must
be granted access explicitly; previously either route accepted any
authenticated user.

Bulk attendance viewing, creation, editing, and deletion use separate
permissions and also require `view students`. Apply
`2026_11_12_001500_seed_bulk_attendance_permissions`. Users without
`view all students` see only assigned students, and mixed-assignment groups are read-only.
The form's student and lesson lookups use the same student access scope.

Individual attendance permissions distinguish viewing, viewing all, recording,
editing, deleting, and downloading reports. Apply
`2026_11_12_001600_seed_attendance_permissions`. Without `view all attendances`,
staff can access only their own instructor records and reports; recording still
requires an instructor role and assignment. Student search also requires
`view students`. Without `view all students`, bulk records must be changed
through their bulk group so mixed groups remain read-only.
Student self-service remains available; student profiles retain their existing
aggregate attendance progress under `view students`.

Instructor bonus payment viewing and processing require separate permissions.
Apply `2026_11_12_001700_seed_instructor_payment_permissions`. Previous
`manage administration` grants receive viewing and `manage system` grants
receive processing. Payment listing previously accepted any authenticated
user; other roles now require an explicit viewing grant. The scheduled bonus
command continues using its super-admin account.

Instructor viewing, creation, editing, deletion, and account management use
separate permissions. Apply `2026_11_12_001900_seed_instructor_permissions`;
existing `manage administration` grants retain these actions. Editing an
instructor's login credentials also requires `manage instructor accounts`.
Changing department requires `edit fleet` or `edit classrooms` when the change
would remove the respective assignments. Student, attendance, and schedule
data on instructor profiles retain their own permissions and ownership scopes.
The legacy full instructor-data endpoint requires all relevant view permissions.
The detailed instructor JSON list also requires fleet, lesson, and account
access; classroom assignment uses a separate minimal instructor-options list.

Administrator viewing, creation, editing, deletion, account management, and
role assignment have separate permissions. Apply
`2026_11_12_002000_seed_administrator_permissions`; existing `manage administration`
grants retain these actions. Creating an administrator also requires role
assignment. Editing credentials requires account management, and changing roles
requires role assignment; both permissions can affect privileged accounts.
Profile edits preserve existing roles unless a role change is submitted.

Lesson viewing, creation, editing, and deletion use separate permissions.
Apply `2026_11_12_000400_seed_lesson_permissions`; existing `manage operations`
grants retain lesson viewing, while lesson mutations require explicit grants.
Student lesson and attendance APIs remain available to the student concerned;
staff access to another student's data requires student viewing and assignment
scope.

Classroom and fleet viewing, creation, editing, and deletion use separate
permissions. Apply `2026_11_12_000500_seed_classroom_fleet_permissions`;
existing `manage administration` grants retain these actions. Classroom edits
that unassign an instructor from a vehicle also require `edit fleet`.
Student and instructor API access to classroom and fleet details is limited to
their assignments unless they have the corresponding viewing permission.

Vehicle location viewing and recording use separate permissions. Apply
`2026_11_12_001800_seed_vehicle_location_permissions`: existing `manage administration`
grants retain viewing, while instructors retain recording. Existing instructor
profiles without the instructor role receive a direct recording grant. Recording
still requires an instructor profile and an assigned vehicle.

Expense type viewing, creation, editing, and deletion use separate permissions.
Apply `2026_11_12_000600_seed_expense_type_permissions`. Legacy grants are
carried over when the same role or direct grant held all permissions previously
required for the action.

Expense permissions separately control viewing, creation, editing, deletion,
review, approval, letters, and reports. Apply
`2026_11_12_001000_seed_expense_permissions`; grants are copied conservatively
from the legacy access and action permissions. Approved expenses cannot be
edited, deleted, or have students removed.

Expense payments use separate permissions for viewing, recording, deleting,
downloading receipts, and generating summary reports. Apply
`2026_11_12_000900_seed_expense_payment_permissions`; existing grants are
preserved when the same role or direct grant held the previously required
permissions.

Invoice viewing, creation, editing, deletion, and PDF downloads use separate
permissions. Apply `2026_11_12_001100_seed_invoice_permissions`. Creating an
invoice also enrolls the student and can record an initial payment; editing
changes enrollment details, while deleting unenrolls the student and deletes
their fee payments. The invoice permissions authorize those existing effects.

Fee payments use separate view, view-all, record, and delete permissions. Apply
`2026_11_12_001200_add_payment_recorder` and
`2026_11_12_001300_seed_fee_payment_permissions`. Without view-all, users see
and may delete only payments recorded by their account. Historical payments
without a reliably recorded user remain visible only to view-all users.

Settings permissions distinguish viewing from editing school, attendance, and
invoice settings. Apply `2026_11_12_000700_seed_settings_permissions`;
existing `manage administration` grants retain these actions.

## Form actions

Dashboard page and modal forms use the shared `<x-form-actions>` footer:
bottom-right alignment, Cancel first (secondary), Save second (primary), and
matching rounded buttons. Supply `cancel-url` for page navigation or `modal` for
Bootstrap dismissal. Vue forms retain their save/loading handlers in the default
slot; use `cancel-click` or the `cancel` slot when cancelling needs custom cleanup.
Dedicated actions such as sending announcements and downloading reports keep
their action-specific labels and behavior.

## Fuel estimates

Fleet details show a rough planning estimate, not measured fuel consumption.
The assumed base rate is `4 + 2 * engine litres` L/100 km, multiplied by fuel
factor: Petrol 1, Diesel 0.85, Hybrid 0.70, LPG 1.20. Missing engine/fuel details,
electric vehicles, and unsupported fuel types do not produce a fuel estimate.
The Fuel usage card shows estimated litres used over the selected period's recorded distance
(`km * rate / 100`), not the L/100 km rate. Missing vehicle details or tracking
show an unavailable status rather than zero. Traffic, idling, and vehicle
condition are not modelled.

The fleet list uses server-side DataTables for database search, sorting, and
pagination through authenticated AJAX requests to `/fleet`. The initial page
loads summary counts only; rows and active student counts are fetched per page.
Each row
retains vehicle details, instructor assignment, active student count, and View,
Edit, and Delete actions.
After vehicle identity, columns prioritize assignments, students, expiry dates,
odometer, and service details before insurer and fixed specifications.
Insurance and roadworthiness dates
are red when expired or due within 20 calendar days (including day 20), using
school-local time. Missing dates remain unhighlighted. Actions use primary buttons.
Insurer is a validated dropdown of Malawi general insurers: Prime, Britam, CIC,
General Alliance, NICO General, Reunion, and United General. New fleet records
default to Prime Insurance. Existing records are not backfilled; existing blank
values and legacy insurer names remain editable without being silently replaced.
The options are shared by form and validation through `Fleet::INSURERS`; the
database remains a string column for compatibility with historical records.

Location & distance supports Last hour, Today, Yesterday, and custom date/time.
Ranges use the school's runtime timezone (Africa/Blantyre), with an inclusive
start and exclusive end. Map, speed, distance, and fuel usage share the selected
range. Requests without a period retain today's default on web and API routes.
Route playback follows recorded timestamps with Play/Pause, restart, a seek
slider, and 1x/10x/60x/200x/300x speeds (default 200x). Playback shows progress distance, speed, and
fuel usage in school time; Full route restores the selected period's totals.
Live polling is suspended during playback (including pause/seek) and resumes
on Full route or a period change. Routes need two points with a positive duration.
The timeline gradient uses segment vehicle speeds: green at zero, yellow at
40 km/h, and red for the entire segment above 40 km/h.
Gradient stops follow recorded time, not point count. Equal timestamps are
excluded from speed calculations; stationary routes remain green. These colors
use a configured display threshold, not legal speed-limit warnings.

404 and 500 pages share a responsive, standalone error layout with Home and
Sign in links. They require no database queries, external fonts, scripts, or
compiled assets, so recovery navigation remains available during failures.
Production 500 pages show a generic message without exception details; debug
exception handling and HTTP status codes are unchanged.
Inline SVG artwork depicts a lost learner car on 404 and a car under repair on
500. Illustrations scale to the content width and are decorative for screen readers.
Error content sits directly on the page background without cards or shadows.
Artwork has a brief car entrance plus route tracing (404) or wrench motion
(500). Animations finish within four seconds and respect reduced-motion settings.
Compact spacing and viewport-scaled artwork keep standard desktop and mobile
error pages within the screen without hiding overflow or recovery links.

Bulk model-based engine capacities carry an `engine_capacity_reference` source
for provenance. They
are not confirmed specifications of the actual vehicle. Editing engine capacity,
make, or model clears an outdated reference; saving unchanged values retains it.
