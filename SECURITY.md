# Security Policy

## Financial and fleet data access

Global dashboard summaries and invoice API reads require an authenticated
`superAdmin`, `admin`, or `financeAdmin` identity. The legacy seeded role
`Super-Admin` is also accepted. Public registration does not assign a role.
The web dashboard, invoice listing, search, details, and PDFs enforce the same
financial role boundary. Existing stricter controller checks still apply.

Fleet location and history reads, including all MbiraSync position routes,
require `superAdmin`, `Super-Admin`, or `admin`. API callers authenticate with
Sanctum; web callers use session authentication. Instructors cannot read these
global feeds or arbitrary device histories. Instructor location uploads retain
their existing instructor-to-fleet relationship checks. MbiraSync's configured
token authenticates outbound requests only and does not authorize app callers.

## Supported Versions

Use this section to tell people about which versions of your project are
currently being supported with security updates.

| Version | Supported          |
| ------- | ------------------ |
| 5.1.x   | :white_check_mark: |
| 5.0.x   | :x:                |
| 4.0.x   | :white_check_mark: |
| < 4.0   | :x:                |

## Reporting a Vulnerability

Use this section to tell people how to report a vulnerability.

Tell them where to go, how often they can expect to get an update on a
reported vulnerability, what to expect if the vulnerability is accepted or
declined, etc.
