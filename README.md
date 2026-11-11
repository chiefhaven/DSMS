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

Bulk model-based engine capacities carry an `engine_capacity_reference` source
for provenance. They
are not confirmed specifications of the actual vehicle. Editing engine capacity,
make, or model clears an outdated reference; saving unchanged values retains it.
