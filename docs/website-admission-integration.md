# Website → CRM Admission Integration

How an admission submitted on the public Mumbai Cricket Academy website
(**Website**, `new_mca_website`) becomes a student in the coaching management
software (**CRM**, `coaching_academy_fees_attendance`).

This document lives in both repositories; keep the two copies in step.

---

## 1. Architecture

```
Browser                 Website (Laravel)                    CRM (Laravel)
   |                          |                                    |
   |  POST /join-academy      |                                    |
   |------------------------->|                                    |
   |                   JoinAcademyRequest                          |
   |                   validates the form                          |
   |                          |                                    |
   |                   Enquiry row created                         |
   |                   + website_admission_uuid                    |
   |                   + crm_sync_status = pending                 |
   |                          |                                    |
   |                   CrmAdmissionService                         |
   |                   Bearer token, multipart                     |
   |                          |  POST /api/v1/website/admissions   |
   |                          |----------------------------------->|
   |                          |                     VerifyWebsiteApiToken
   |                          |                     WebsiteAdmissionRequest
   |                          |                     WebsiteAdmissionImporter
   |                          |                     StudentRegistrar (transaction)
   |                          |<-----------------------------------|
   |                          |  201 created / 200 already synced   |
   |                   crm_sync_status updated                     |
   |<-------------------------|                                    |
   |  "success" or            |                              visible at
   |  "processing pending"    |                              /admin/students
```

Key points:

* The browser never talks to the CRM. The API token lives only in each
  application's environment file and is attached server-side.
* The Website stores the admission **before** calling the CRM, so a CRM outage
  can never lose a submission.
* `website_admission_uuid` is minted once per submission and reused on every
  retry. It is unique on the CRM's `students` table, so a retry returns the
  existing student instead of creating a second one.
* Student creation in the CRM goes through `StudentRegistrar`, the same service
  the admin "Add Student" form uses. There is one set of creation rules.

---

## 2. API endpoint

```http
POST /api/v1/website/admissions
Authorization: Bearer {WEBSITE_ADMISSION_API_TOKEN}
Accept: application/json
Content-Type: application/json  (or multipart/form-data when a photo is sent)
```

Route: `routes/api.php`, name `api.v1.website.admissions.store`.
Middleware: `website.api` (token check) + `throttle:website-admissions`
(60 requests per minute per IP).

### Authentication

A shared secret compared with `hash_equals()` (constant time). The CRM does not
use Sanctum or Passport — there are no API users, only this one
server-to-server caller — so a single-purpose middleware is the smallest thing
that does the job. An unset token never means "open": the request is refused
with 401 and an error is logged.

### Request fields

| Field | Type | Required | Notes |
|---|---|---|---|
| `website_admission_uuid` | uuid | yes | Idempotency key |
| `source_reference` | string(100) | no | e.g. `enquiry:42` |
| `full_name` | string(150) | yes | Split into `first_name` / `last_name` |
| `date_of_birth` | `Y-m-d` | yes | Must be before today |
| `gender` | `male\|female\|other` | no | Column default applies when absent |
| `blood_group` | string(5) | no | |
| `school_name` | string(255) | no | |
| `guardian_name` | string(255) | yes | |
| `guardian_phone` | string(20) | yes | |
| `guardian_email` | email | no | |
| `guardian_relation` | string(50) | no | |
| `email` / `phone` | | no | The student's own, if collected |
| `address` / `city` / `state` / `pincode` | | no | |
| `playing_role` | enum | no | Column default applies when absent |
| `batting_style` | enum | no | |
| `bowling_style` | enum | no | |
| `admission_date` | `Y-m-d` | no | Defaults to today |
| `medical_notes` | string(1000) | no | |
| `programme` | string(150) | no | Title, not an id — written to `notes` |
| `experience_level` | string(50) | no | Written to `notes` |
| `preferred_training_type` | string(50) | no | Written to `notes` |
| `message` | string(2000) | no | Written to `notes` |
| `batch_code` | string(50) | no | Resolved to a CRM batch; 422 if unknown |
| `terms_accepted` | boolean | yes | Must be truthy |
| `photo` | file | no | jpg/jpeg/png/webp, ≤ 2 MB |

### Responses

`201 Created` — a new student:

```json
{
  "success": true,
  "message": "Admission transferred to CRM successfully.",
  "data": {
    "student_id": 123,
    "admission_number": "STU0123",
    "website_admission_uuid": "…",
    "sync_status": "synced"
  }
}
```

`200 OK` — this UUID was already imported (idempotent replay): same shape, with
`"sync_status": "already_synced"` and the message
`"Admission was already transferred to CRM."`

`401` invalid or missing credentials · `422` validation failure (with an
`errors` object) · `429` rate limited · `500` unexpected CRM error.

Error responses carry no stack traces, SQL, tokens or file paths.

---

## 3. Field mapping

Website `enquiries` column → CRM `students` column.

| Website | CRM | Transformation |
|---|---|---|
| `full_name` | `first_name` + `last_name` | Split on the first space |
| `date_of_birth` | `date_of_birth` | — |
| `gender` | `gender` | Omitted when null → column default `male` |
| `blood_group` | `blood_group` | — |
| `school_name` | `school_name` | — |
| `guardian_name` | `guardian_name` | — |
| `guardian_phone` | `guardian_phone` | — |
| `guardian_email` | `guardian_email` | — |
| `guardian_relation` | `guardian_relation` | — |
| `city` / `state` / `pincode` | same | — |
| `playing_role` | `playing_role` | Omitted when null → column default `batter` |
| `batting_style` | `batting_style` | — |
| `bowling_style` | `bowling_style` | — |
| `medical_notes` | `medical_notes` | — |
| `programme_id` | `notes` | Resolved to the programme **title** |
| `experience_level` | `notes` | |
| `preferred_training_type` | `notes` | |
| `message` | `notes` | |
| `photo_path` | `photo` | Multipart upload; CRM stores its own copy |
| `created_at` | `admission_date` | Date only |
| `website_admission_uuid` | `website_admission_uuid` | |
| `id` | `source_reference` | As `enquiry:{id}` |
| — | `student_code` | `Student::nextCode()` |
| — | `admission_status` | Always `pending` (needs admin review) |
| — | `status` | `active` |
| — | `admission_source` | `website` |

### Fields with no CRM column

The CRM has no programme, experience-level or training-type concept. Rather
than adding columns the admin form cannot edit, these are written into the
student's `notes` — an ordinary editable field — so nothing the applicant typed
is lost.

### Optional-but-not-null fields

`gender` and `playing_role` are optional on the Website form but `NOT NULL`
(with defaults) in the CRM. When absent, the key is omitted so the column
default stands, and a line is appended to `notes`:

> Not supplied on the website form (CRM default applied, please confirm): Gender, Playing role.

This never fabricates a value silently — the admin is told to confirm it.

---

## 4. File uploads

* The Website stores the photo on its own disk under `admissions/` via
  `StorageHelper::upload()`.
* On sync, the file's **bytes** are attached with `Http::attach()` as
  `multipart/form-data`. No URL is sent — a localhost URL would be useless and
  a public one would make the CRM fetch an address we control.
* The CRM re-validates MIME type, extension and size, then stores its own copy
  under `students/` with a UUID filename via its own `StorageHelper`. The
  original filename is never trusted and only the relative path is saved.
* If the transaction fails, the newly uploaded file is deleted
  (`StudentRegistrar`).
* A photo missing from the Website's disk logs a warning and the admission
  syncs without it, rather than being stuck forever.

---

## 5. Environment variables

**CRM** (`.env`):

```env
WEBSITE_ADMISSION_API_TOKEN=
```

**Website** (`.env`):

```env
CRM_API_BASE_URL=https://cricmanager.mumbaicricketacademy.in
CRM_ADMISSION_API_TOKEN=
CRM_ADMISSION_TIMEOUT=30
CRM_ADMISSION_CONNECT_TIMEOUT=10
```

`CRM_ADMISSION_API_TOKEN` and `WEBSITE_ADMISSION_API_TOKEN` must be **the same
value**. Generate one with:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Never commit a real token, expose it to JavaScript, or put it in a log.

---

## 6. Sync statuses (Website)

Stored on `enquiries.crm_sync_status`:

| Status | Meaning | Auto-retried? |
|---|---|---|
| `pending` | Saved locally, not yet accepted by the CRM | Yes |
| `synced` | The CRM holds the student | Never sent again |
| `failed` | Timeout, network error, 429 or CRM 5xx | Yes |
| `invalid` | CRM rejected the data (422) | No — fix the data first |
| `blocked` | 401/403, or no token configured | No — an operator must act |

Supporting columns: `crm_student_id`, `crm_admission_number`,
`crm_sync_attempts`, `crm_last_error`, `crm_synced_at`.

---

## 7. Retrying

Three routes, all reusing the original UUID, so none can create a duplicate:

1. **Queued job** — `SyncAdmissionToCrm`, dispatched automatically after a
   retryable failure when a real queue is configured. 4 attempts, backing off
   1 / 5 / 15 minutes. Nothing is dispatched on the `sync` driver, where a job
   would only re-run inline and hit the same failure.
2. **Console command**

   ```bash
   php artisan crm:retry-admissions            # everything waiting
   php artisan crm:retry-admissions --id=42    # one admission
   php artisan crm:retry-admissions --limit=10
   ```

   Safe to run from cron.
3. **Admin panel** — a "Retry CRM Sync" button on the enquiry list and detail
   screens.

---

## 8. Local testing

```bash
# CRM
cd coaching_academy_fees_attendance
php artisan migrate
# set WEBSITE_ADMISSION_API_TOKEN in .env
php artisan serve --port=8901

# Website
cd new_mca_website
php artisan migrate
# set CRM_API_BASE_URL=http://127.0.0.1:8901 and the same token
php artisan serve --port=8931
```

Open `http://127.0.0.1:8931/join-academy`, submit the form, then check
`http://127.0.0.1:8901/admin/students`.

Direct API check:

```bash
curl -X POST http://127.0.0.1:8901/api/v1/website/admissions \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -F "website_admission_uuid=$(uuidgen)" \
  -F "full_name=Test Student" -F "date_of_birth=2014-01-01" \
  -F "guardian_name=Test Guardian" -F "guardian_phone=9820011223" \
  -F "terms_accepted=1"
```

Running the same command twice with the same UUID must return `200` /
`already_synced` the second time.

Automated tests:

```bash
cd coaching_academy_fees_attendance && php artisan test --testsuite=Feature
cd new_mca_website                  && php artisan test --testsuite=Feature
```

---

## 9. Production deployment order

1. **Back up** both databases.
2. Deploy CRM migrations: `php artisan migrate --force`.
3. Deploy the CRM API code.
4. Set the **same** token in both environments, then
   `php artisan config:clear` on both.
5. Verify the CRM API with one controlled `curl` (above). Expect `201`.
6. Deploy the Website integration code and its migration.
7. Submit one controlled admission through `/join-academy`.
8. Confirm it appears at `/admin/students` with the "Website" badge.
9. Open, edit and save that student in the CRM.
10. Replay the same UUID and confirm no duplicate is created.
11. Verify the photo is stored and viewable on the CRM.
12. Watch both application logs for `CRM admission sync` entries.

---

## 10. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Status `blocked`, log says "not configured" | Missing token or base URL | Set both, `php artisan config:clear` |
| `401` from the CRM | Tokens differ | Re-copy the value; check for trailing whitespace |
| Status `invalid` | CRM rejected the data | Read `crm_last_error`, correct the enquiry, retry |
| Status `failed`, repeated | CRM unreachable or 5xx | Check the CRM is up; run `crm:retry-admissions` |
| `429` | Rate limit (60/min per IP) | Retry shortly; the status stays retryable |
| Student created but no photo | File missing on the Website disk | Warning is logged; re-upload and retry |
| Applicant sees "Processing Pending" | The CRM did not accept the admission | Nothing is lost — retry; the applicant must not resubmit |
| Duplicate students | Should be impossible | Check the unique index on `students.website_admission_uuid` |

Log lines are prefixed `CRM admission sync` on the Website and carry only the
enquiry id, the UUID and the CRM's response — never the token or the
applicant's personal details.
