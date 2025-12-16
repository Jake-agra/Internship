## Quick context — what this project is

This is a PHP-based real estate web application (procedural + small helper classes). Key parts:
- Public site: root-level PHP files (e.g. `index.php`, `property.php`, `property_details.php`).
- Admin panel: `admin/` (add_property.php, users.php, properties.php, image_manager.php).
- Agent portal: `agent/`.
- Client portal: `client/`.
- Shared helpers and services: `includes/` (security, EmailService, smtp_config.php, header/nav/footer, route/auth helpers).
- Database: MySQL schema SQL in `Database/realestate.sql` and DB connection in `Database/connection.php`.
- Composer dependencies live in `composer/` (run `composer install` inside that folder).

## High-level architecture & important flows

- Single-site PHP pages render server-side and include helpers via `include('./includes/...')`. Templates reuse `includes/header.php`, `includes/nav.php`, `includes/footer.php`.
- Database access uses a global `$conn` from `Database/connection.php`. There is a thin `executeQuery()` helper; most pages use raw `$conn->query()` and `fetch_assoc()`/`fetch_all()`.
- Authentication/authorization is session-based. Session vars used:
  - `$_SESSION['user_id']`, `$_SESSION['user_email']`, `$_SESSION['user_role']`.
  - Helper functions are in `includes/route.php` (`isLoggedIn()`, `requireLogin()`, `getUserRole()`, etc.).
- CSRF and input validation are implemented in `includes/security.php` (class `SecurityValidator`) — use `generateCSRFToken()` and `validateCSRFToken()` where appropriate.
- Email sending uses PHPMailer via `includes/EmailService.php` and `includes/smtp_config.php`. `smtp_config.php` reads a repository `.env` if present and returns a config array.
- File uploads are validated by `SecurityValidator::validateFileUpload()` and uploaded files live under `uploads/properties/`.
- Important logs: security events are appended to `../logs/security.log` (create the `logs/` folder if missing).

## Developer workflows (how to run / common tasks)

- Local dev (typical): place the project in your local web root (XAMPP/WAMP) or use PHP built-in server. Ensure PHP >= 7.4 (8.x recommended).
- DB setup: import `Database/realestate.sql` into MySQL and edit credentials in `Database/connection.php`.
- Composer: run `cd composer; composer install` to ensure PHPMailer and phpdotenv are available. The app uses Composer's autoloader from `composer/vendor/autoload.php`.
- SMTP: edit root `.env` or `includes/smtp_config.php` environment variables (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `FROM_EMAIL`, `ADMIN_EMAIL`). Example values are in `includes/smtp_config.php`.
- Debugging tips:
  - `Database/connection.php` enables `display_errors` for development. Avoid leaving `display_errors` on in production.
  - To test email sending use `includes/EmailService.php` methods (e.g. call `sendContactConfirmation()` from a temporary script).
  - Check `logs/security.log` for security events and `php`/server error logs for runtime issues.

## Project-specific conventions and gotchas

- Mixed style: the codebase is primarily procedural PHP with a few helper classes (e.g. `SecurityValidator`, `EmailService`). Follow existing patterns when adding features: small, file-scoped helpers and includes rather than introducing heavy frameworks.
- Database access uses a global `$conn`. New code should either reuse `$conn` or add small helper wrappers — prefer prepared statements for user input to avoid SQL injection (see existing `executeQuery()` helper but verify binding semantics before use).
- Session/role checks: use `requireLogin()` and `getUserRole()` in `includes/route.php` or `SecurityValidator::validateAdminAccess()` for admin pages.
- CSRF: pages that accept POST must validate CSRF using the project's token helpers (see `includes/security.php` and `includes/route.php` helpers). Some pages use `generateCSRFToken()` already — mirror that exact pattern.
- File uploads: validate with `SecurityValidator::validateFileUpload()` and use `generateSecureFilename()` to store in `uploads/properties/`.
- Email config: the code reads `.env` in the repo root if present. Keep secrets out of the repo — add or update `.env` locally.

## Integration points & important files to look at (quick map)

- app entry & examples: `index.php` (featured properties + advanced search example that joins `properties`, `prices`, `locations`, `property_types`). Use this file as a query pattern example.
- DB connection: `Database/connection.php` (edit credentials here for local dev).
- Auth & routing helpers: `includes/route.php` (session helpers), `includes/security.php` (SecurityValidator class).
- Email: `includes/EmailService.php` and `includes/smtp_config.php` (env-based config). Composer autoload used at top of `EmailService.php`.
- Admin area: `admin/` — check `admin/add_property.php`, `admin/image_manager.php` for file upload and image handling patterns.
- Client/agent examples: `client/` and `agent/` folders demonstrate how role-based pages use `getUserRole()`.

## Examples for the agent to follow (copy/paste patterns)

- Safe property lookup (pattern used in `index.php`): join `properties` → `prices` → `locations` → `property_types` and then `fetch_all(MYSQLI_ASSOC)`.
- Sanitize/validate input: call `SecurityValidator::getInstance()->sanitizeInput($value)` and run `validateInput()` with explicit rules array for form submissions.
- CSRF usage: include token in forms and validate on POST using `SecurityValidator::validateCSRFToken($_POST['csrf_token'] ?? '')`.

## Property page & frontend patterns (from `property.php`)

- Prepared statements: `property.php` builds dynamic SQL and uses `$conn->prepare()` + `bind_param($types, ...$params)` then `get_result()` — follow this pattern when mixing dynamic filters with user input. Use types `s`, `i`, `d` consistently.
- Pagination: compute `$total_properties` via a `COUNT(*)` query (strip ORDER BY), then append `LIMIT {per_page} OFFSET {offset}` to main query. Mirror this approach for searchable lists.
- Sorting & filtering: sort values are switched server-side (`price_low`, `price_high`, `newest`, `featured`) — validate incoming `sort_by` values against allowed list before using.
- JS integrations on this page:
  - Map: uses Leaflet (external CDN) and placeholder coordinates; real geocoding should source lat/lng from `locations` table.
  - Bookmarks: client uses `fetch('bookmark_handler.php', { method: 'POST', headers: { 'X-CSRF-Token': ... }})` — backend handlers expect `csrf_token` in POST body; keep both header and body for compatibility.
  - Saved searches: stored in `localStorage` as `savedSearches` (simple JSON). Reuse this pattern for client-side search persistence.
  - UI behaviors: view-mode toggle (grid/list/map), auto-submit on filter change, price formatting on inputs — mimic existing UX when adding features.
- CSRF token helpers: the codebase provides wrapper helpers `csrf_token()` and `verify_csrf()` (defined in `includes/security.php`) and also exposes `generateCSRFToken()`/`validateCSRFToken()` methods. Standardize on `csrf_token()` in templates and `verify_csrf()` (or `SecurityValidator::getInstance()->validateCSRFToken()`) for server-side validation.
- Data assumptions: `property.php` sometimes reads `images`, `price`, `city` fields directly from the joined query; when adding fields, update SELECT joins to include necessary columns.


## What not to change without coordination

- Do not remove or change `Database/connection.php` error handling flags in production. The file intentionally enables `display_errors` during development.
- Avoid altering session key names (`user_id`, `user_role`, `user_email`) — these are referenced across many pages.

If any of these points are unclear or you'd like me to expand an example (e.g. prepare a PR that converts one raw query to prepared statements, add a small unit test script, or create a local `.env.example`), tell me which one and I'll iterate.
