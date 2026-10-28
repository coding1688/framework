<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>

## Project multilingual field convention

- Preserve the existing database connection configuration; the user has configured it and explicitly forbids changing it.
- Backend seeders belong in `database/seeders/backend`, namespace `Database\Seeders\backend`, using names such as `AdminUserSeeder`, `AdminMenuSeeder`, and `AdminDeparturementSeeder` (intentional project spelling). Repeated seeding must preserve existing administrator credentials and privileges.

- Persist translatable business fields in JSON columns suffixed with `_i18n`, such as `name_i18n`, `title_i18n`, `description_i18n`, and `content_i18n`.
- Expose localized display fields (`name`, `title`, etc.) through Eloquent `Attribute` accessors. Use `App\Models\Concerns\HasLocalizedAttributes::localizedValue()` for fallback selection; the frontend must not implement its own language fallback.
- Cast translation columns to `array`, allow mass assignment only of the `_i18n` fields, and append the localized display attributes when serializing models. Editors submit the full translation dictionary and read it from `_i18n`, while lists display the accessor value.
- Validate locale keys against `config/platform.php`. Required names/titles use `en` as the source translation; other locales are optional. Fallback order: current locale, `en`, configured fallback locale, `zh_CN`, first nonempty translation, empty string.
- Actual personal names, identifiers, email addresses, and immutable audit actor snapshots are not translatable fields.
- Before launch, maintain the current schema directly in the original create-table migrations. Merge obsolete patch migrations; database resets are authorized by the user. Never modify database connection configuration. Keep initial data in seeders.

## Backend department governance

- The unique root is the board (`admin_departments.code=board`); it and the first super administrator are protected. Active board members have all permissions.
- Department creation offers create-Leader or no-Leader. Explicit no-Leader creation may leave a department temporarily empty; when creating a Leader, create both atomically. Once leaders are assigned, retain 1–2 active leaders. Never permit deletion, demotion, disabling or transfer of the last leader. Lock departments then administrators consistently before governance writes.
- Non-board writes require a department leader and the endpoint permission. Leaders manage accounts only within their subtree, never their own account or peer leaders, and delegate grants only to direct child departments. Board membership cannot be assigned by a subordinate leader.
- Department grants intersect every ancestor's grants. All employees inherit the department effective grants; only leaders may manage staff and child departments. Revoked ancestor grants must be pruned from descendants and must not silently return after a later regrant.
- Only the board edits global menus. Roles are retired; never add role-based authorization or role assignment.

## Backend editor conventions

- Default modals are at least 70% of viewport width. Administrator edit/create/password dialogs use 50% width and a single column; use near-full width on mobile. Administrator edit contains only email and nickname, Leader assignment belongs to departments, login is email-only, and deletion requires disabled status.
- Group translatable inputs into accessible language tabs, English first and selected initially. Retain all language values when switching tabs and expose hidden invalid inputs before native validation focuses them. English is the future translation API source; do not invent translated values for legacy data.
- Permission selection uses one three-column table: merged category cells on the left, page groups in the middle, and individual page/button permissions on the right. No search, explanatory header or category tabs. Category and page checkboxes support select-all and indeterminate states; selection is limited to server-provided permissions.

## Backend SPA navigation

- Backend uses Vue 3, Vue Router and Element Plus with one Blade entry. Preserve the shell during navigation; do not introduce workspace iframes. Language changes reload the entry.
- Keep server-protected direct page routes, history navigation, session-expiry redirects and server ACL enforcement. Fetch userinfo once in the shell; refresh identity on permission rejection and return to the workspace.
- Menus support recursive directories, pages and buttons. Preserve ancestor containers for authorized descendants without granting access to unauthorized parent pages; hide disabled branches and empty directories. Buttons never appear in navigation and must belong to a page.
- Allow system pages to be reparented for organization while retaining protected permission keys, types and paths. Preserve custom hierarchy on repeated seeding.

- Admin models live directly in `app/Models/Admin`, with singular StudlyCase table names: `AdminUser`, `AdminDepartment`, `AdminMenu`, `AdminLog`. Explicitly specify existing relationship foreign keys when the model name would infer a different key.

- Prefer one business controller per model: AdminUserController, AdminDepartmentController, AdminMenuController, AdminLogController. Keep domain-specific validation in separate Requests; AuthController and PageController handle authentication and workspace rendering separately.

- Services, Requests and Resources also map to individual business models (Administrator, Department, Menu, Log). Business services live in `Services/Backend/Admin`, accept validated arrays and return domain data; controllers handle HTTP responses. Shared audit, navigation and access helpers live in `Services/Backend/Common`.

- Business Controller, Service, Request and Resource names must use the full model class name, including its Admin prefix (e.g. AdminUserController, AdminUserService, AdminUserRequest, AdminUserResource).

- Every backend controller constructor-injects its corresponding service. Controllers only extract validated input/HTTP context, delegate, and build Resource/JSON/View responses; no queries, transactions, authentication workflows or inline validation. Requests own input rules, services own business workflows/transactions, resources own serialization. Services must not depend on FormRequest or return HTTP responses. AuthService and PageService live directly in Services/Backend.

- Backend middleware class names use the Backend prefix and Mid suffix: BackendAuthMid, BackendAclMid, BackendLocaleMid; a future logging middleware must be named BackendLogMid. Keep transactional business audit writes in services.

- Global helper functions use camelCase (apiSuccess, apiError, apiPaginate, apiResponse), never snake_case.

- Backend, Partner and Api each own BaseController, BaseService and BaseRequest in their corresponding layer/area directory. Concrete endpoint classes inherit their own area base class; Common helpers remain independent. BaseRequest centralizes shared input behavior; route middleware and business services enforce permissions.

- Visibility: board members see all users/departments; leaders see users only in their own department and departments in their subtree; ordinary employees see only themselves and their own department. Ordinary employees never receive administrator/department mutation permissions. Scope option endpoints and nested user relationships too.

- Department employee management is a scoped exception to the administrator list: board/department leaders may list all employees of a visible department. Only board members or ancestor-department leaders with departments.update may replace its 1–2 active leaders, selected strictly from that department. Ordinary employees cannot access this endpoint.

- Business string column capacities use powers of two (16/32/64/128/256/512), with matching request validation and frontend maxlength. Do not change array-count limits or framework infrastructure columns solely for this convention.

- Menu identifiers use directory / module.resource / module.resource.action, e.g. admin, admin.user, admin.user.list. The module-to-prefix mapping lives in platform.backend_ui.permission_prefixes; route URLs remain independent.

- Page/action menu identifiers match named routes: admin.user for the page, admin.user.list/create/update/delete for API actions. Auxiliary endpoints have unique route names and explicitly reuse their governing permission. Directory identifiers need not have a route.

- Backend browser endpoints (without /api/v1) explicitly name actions (list/create/update/detail/delete). Reads use GET, writes use POST; validation distinguishes controller actions rather than HTTP verbs. Route names mirror actions, e.g. admin.user.list.

- Business Controller and Service CRUD methods match explicit API actions: list, detail, create, update, delete. Keep Request action checks and Resource detail checks aligned.

- Attach backend.permission once to business route groups without parameters. BackendAclMid resolves named routes against admin_menus.permission or route_names aliases. Ordinary accounts deny missing/ambiguous matches; board retains all-access. Auxiliary route aliases are seeded on their governing permission, not embedded in routes.

- Backend responses use Accept headers for error response negotiation. Controller JsonResponse return types identify JSON endpoints for frontend route generation; do not add redundant attributes. Generate frontend endpoint URLs/methods through BackendRoutes and named Laravel routes; the public/mobile API remains independent.

## Frontend source and build boundaries

- Backend Vue/JavaScript source belongs in `resources/js/backend`; CSS source belongs in `resources/css/backend`. Blade entry views remain in `resources/views/backend`.
- `public/build/backend` contains only Vite-generated assets. Never edit generated assets or place Vue/components/application source under public. Build using `npm run build:backend` after backend frontend changes.
- Keep endpoint URLs generated by Laravel and retain backend-specific Vite hot files. Do not import source files from public.

- Vue module translations live beside pages at `pages/{module}/locales/{locale}.json`; shared UI translations live in `resources/js/backend/locales/{locale}/{module}.json`; use module-qualified keys for new labels, keep all six configured locale key sets aligned, and fall back to English only for legacy content that has not yet been translated. Laravel validation/response translations remain in `lang`. Do not inject a flattened PHP labels dictionary into the SPA.

- Organize Vue pages by backend module: `pages/admin/{user,department,menu,log}/List.vue`, `pages/auth`, and `pages/home`. Each module has its own route entry; module-owned components belong in `pages/admin/{module}/components`; only genuinely shared components remain in root `components`.

- Business Vue routes are registered from authenticated userinfo menus (route_name, path, localized title, icon, component). Keep only auth/home/profile bootstrap routes static. Resolve components through local Vite glob imports, clear routes on logout, and rebuild after identity refresh. admin_menus.permission remains the unique source for route_name; do not maintain duplicate frontend route tables.

- Backend JSON requests send locale through `X-Lang`; BackendLocaleMid gives this header priority over page query/session fallback. Do not use X-Locale.

- Keep module forms, dialogs and serialization in their module directory. Common dialog/composable code must not branch by business module. Translation namespaces mirror module paths (admin.department.*); common controls/messages stay in root locales. Route component globs must exclude module components.

- Backend imports use `@/` for resources/js/backend and `@styles/` for resources/css/backend. Keep Vite aliases and jsconfig paths aligned. Glob paths and their result-key parsing must be changed together.

- Write each module list directly in its own List.vue (filters, columns, actions, pagination and data loading). Do not introduce ManagementTable-style cross-module list wrappers or section-based business branching.
