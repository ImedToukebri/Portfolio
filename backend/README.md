<!--
Project README (Portfolio - backend)
Generated: ultra-detailed developer README targeted to this repository.
-->

# Portfolio — Backend (Laravel)

This repository contains the backend API for the Portfolio project, built with Laravel 12 and Fortify for authentication. It exposes RESTful endpoints for user authentication and projects management (including image uploads).

This README is intentionally detailed: it covers local setup, database and storage configuration, API reference with example requests (Postman-friendly), troubleshooting notes, and suggested next steps.

--

**Table of contents**

-   Project overview
-   Requirements
-   Quick start (run locally)
-   Environment configuration
-   Database: migrations & seeders
-   Filesystem & image uploads
-   API reference (endpoints, example requests)
-   Postman testing walkthrough (register, login, upload, logout)
-   Troubleshooting (common issues & fixes)
-   Development notes & tips
-   Contributing
-   License

--

**Project overview**

-   Framework: Laravel 12
-   Auth: Laravel Fortify + Sanctum (token-based API auth)
-   Main API resources:
    -   Authentication: `/api/register`, `/api/login`, `/api/logout`
    -   Projects CRUD: `/api/projects` (protected by `auth:sanctum`)
-   Image uploads are stored on the `public` disk (in `storage/app/public/projects`) and the DB stores the relative path (e.g. `projects/1600000000_file.jpg`).

Files you will find here (important ones):

-   `routes/api.php` — API route definitions
-   `app/Http/Controllers/Auth` — Register & Login controllers
-   `app/Http/Controllers/Api/ProjectController.php` — Projects endpoints (index, store, show, update, destroy)
-   `app/Models/Project.php` — Eloquent model (fillable includes `image`)
-   `database/migrations/*_create_projects_table.php` — projects table migration (has `image` column)
-   `config/filesystems.php` — `public` disk configuration (links `public/storage` → `storage/app/public`)
-   `bootstrap/app.php` & `bootstrap/providers.php` — make sure routes & service providers are wired (FortifyServiceProvider should be registered)

--

**Requirements**

-   PHP 8.2+
-   Composer
-   Node (optional — for frontend assets)
-   A database (MySQL, SQLite, Postgres, etc.) configured in `.env`

On Windows (PowerShell) commands are provided in the sections below.

--

**Quick start (run locally)**

1. Install dependencies:

```powershell
cd \Path\To\Portfolio\backend
composer install
npm install        # optional — only if you need to build front-end assets
```

2. Copy `.env` and generate app key (if not present):

```powershell
copy .env.example .env
php artisan key:generate
```

3. Configure the `.env` database settings (DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD).

4. Run migrations:

```powershell
php artisan migrate
```

5. Create the storage symlink so uploaded files are publicly accessible:

```powershell
php artisan storage:link
```

6. Start the local server:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
# or simply
php artisan serve
```

The API will be available at `http://127.0.0.1:8000/api` by default.

--

**Environment configuration**

-   `APP_URL` — should match where you run the server (e.g. `http://127.0.0.1:8000`).
-   `FILESYSTEM_DISK` — set to `public` to store files in `storage/app/public` and serve via `public/storage` URL.

Example `.env` lines to check or change:

```
APP_URL=http://127.0.0.1:8000
FILESYSTEM_DISK=public
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=portfolio_db
DB_USERNAME=root
DB_PASSWORD=yourpassword
```

If you change `.env`, run:

```powershell
php artisan config:clear
```

--

**Database: migrations & seeders**

-   Projects migration includes the `image` column (string), `link`, and `user_id` foreign key.
-   The `projects.image` column stores the relative path created by the controller (example: `projects/170xxx_file.jpg`).

Run migrations with:

```powershell
php artisan migrate --force
```

--

**Filesystem & image uploads**

-   `config/filesystems.php` defines a `public` disk that maps to `storage/app/public` and sets `public_path('storage') => storage_path('app/public')` in the `links` array. That means after `php artisan storage:link` uploaded files will be publicly accessible at `{APP_URL}/storage/{path}`.
-   Current upload behavior (in `ProjectController::store`):
    -   Validates `image` with `image|mimes:jpeg,png,jpg,gif|max:2048`.
    -   Stores the file using `$file->storeAs('projects', $filename, 'public')` and saves the returned path (e.g. `projects/170xxx_filename.jpg`) in the `image` column.

Serving files in the browser:

If you upload `projects/170xxx_my.jpg`, you can retrieve it at:

```
http://127.0.0.1:8000/storage/projects/170xxx_my.jpg
```

--

**API Reference (important endpoints)**

Authentication (public):

-   POST `/api/register` — create a user

    -   Body (JSON):
        ```json
        {
            "name": "Imed",
            "email": "imed@example.com",
            "password": "12345678",
            "password_confirmation": "12345678"
        }
        ```
    -   Response: `201 Created` with user JSON (note: by default this controller does not return a token).

-   POST `/api/login` — create API token

    -   Body (JSON):
        ```json
        {
            "email": "imed@example.com",
            "password": "12345678"
        }
        ```
    -   Response: `200 OK` with `{ "user": {...}, "token": "<plain-text-token>" }`.

-   POST `/api/logout` — revoke current token (protected)
    -   Header: `Authorization: Bearer <token>`
    -   Response: `200 OK` { "message": "Logged out successfully" }

Projects (protected — require `Authorization: Bearer <token>` header):

-   GET `/api/projects` — list projects
-   POST `/api/projects` — create a project (multipart form-data)

    -   Fields:
        -   `title` (string, required)
        -   `description` (string, optional)
        -   `link` (url, optional)
        -   `image` (file, optional) — upload as `form-data` file field
    -   Response: `201 Created` with project JSON

-   GET `/api/projects/{id}` — show project
-   PUT/PATCH `/api/projects/{id}` — update project (current `update` accepts image as string; see note below)
-   DELETE `/api/projects/{id}` — delete project

Important: All `/api/projects` routes are registered as `Route::apiResource('projects', ProjectController::class)` inside a `Route::middleware('auth:sanctum')` group, so they require an authenticated token from Sanctum.

--

**Postman testing walkthrough (step-by-step)**

1. Register

-   Request
    -   Method: POST
    -   URL: `http://127.0.0.1:8000/api/register`
    -   Body: raw JSON (see example above)

2. Login (get token)

-   Request
    -   Method: POST
    -   URL: `http://127.0.0.1:8000/api/login`
    -   Body: raw JSON
-   Response
    -   Save the `token` value from the response for subsequent requests.

3. Create a project with image

-   Request

    -   Method: POST
    -   URL: `http://127.0.0.1:8000/api/projects`
    -   Authorization: Header `Authorization: Bearer <token>`
    -   Body: choose `form-data` (not raw). Add keys:
        -   `title` (Text) — e.g. `My Project`
        -   `description` (Text) — optional
        -   `link` (Text) — optional
        -   `image` (File) — select an image file on your computer

-   Response
    -   `201 Created` with the created project JSON. The `image` property will contain the stored path (`projects/<filename>`).

4. Retrieve the image in browser

-   If successful and `php artisan storage:link` was run, open:
    -   `http://127.0.0.1:8000/storage/{image}`

Example (Postman tip): Use the token saved from login in the `Authorization` tab as `Bearer Token`.

--

**Troubleshooting (common issues & fixes)**

-   404 on `/api/register` or other API routes:

    -   Ensure `routes/api.php` exists and contains the endpoints (this project registers them there).
    -   Verify `bootstrap/app.php` includes the `api` routes in `withRouting()` (should set `api: __DIR__.'/../routes/api.php'`).
    -   Ensure `App\Providers\FortifyServiceProvider::class` is registered in `bootstrap/providers.php` so Fortify routes and bindings are available.

-   Uploaded file not accessible in browser:

    -   Run `php artisan storage:link` to create the `public/storage` symlink.
    -   Confirm `FILESYSTEM_DISK=public` in `.env` and run `php artisan config:clear` if you changed it.

-   `Auth::attempt` failing on login / tokens not generated:

    -   Make sure migrations ran and the user exists.
    -   Verify passwords are hashed with `Hash::make` during registration.

-   `image` uploading silently failing or returning null:
    -   Use `form-data` in Postman (not JSON) and set the `image` key to type `File`.
    -   Confirm the validation in `ProjectController` allows the mime/type and size.

--

**Development notes & suggestions**

-   Registration currently returns the created user but not a token. If you prefer automatic login on register, modify `RegisteredUserController::store` to create a token and return it alongside the user. I can add that change for you.
-   `ProjectController::update` currently accepts `image` only as a string. If you want to support replacing the image with a new uploaded file, update the validation and handling to accept `image` as file (same logic as `store`) and consider deleting the old file when replaced.
-   Consider adding file cleanup (delete file from disk) when a project is deleted.
-   Add rate limiting and request validation messages for production readiness.

--

**Contributing**

If you want me to implement the suggested improvements (auto-token on register, update uploads for `update()`, cleanup on delete) I can prepare patches. Tell me which feature you want next and I'll implement it.

--

**License**

This repository follows the same license as Laravel: MIT. See the `LICENSE` file (if present) for details.

--

If any step fails locally I can help debug the exact error — tell me the command you ran and paste the error output and I'll continue.
