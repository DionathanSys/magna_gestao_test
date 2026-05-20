# AGENTS.md

## Stack And Shape
- This is a Laravel 12 app with Filament 4. The main UI is Filament, not controller-heavy MVC.
- There are two Filament panels:
- `/admin` is wired in `app/Providers/Filament/AdminPanelProvider.php` and discovers `app/Filament/{Resources,Pages,Widgets}`.
- `/bugio` is wired in `app/Providers/Filament/BugioPanelProvider.php` and discovers `app/Filament/Bugio/{Resources,Pages,Widgets}`.
- Filament resources are split by convention into `Resource.php`, `Schemas/`, `Tables/`, `Actions/`, `Pages/`, and sometimes `RelationManagers/`. Follow that layout instead of inlining large resource definitions.
- Root `routes/web.php` only has a few closure routes for landing, PDF rendering, PDF import, and a test toggle. Most product behavior lives in Filament resources, Livewire, models, observers, and services.

## Commands
- `composer dev` starts the full local loop: `artisan serve`, `queue:listen --tries=1`, `artisan pail`, and `npm run dev` concurrently.
- `composer test` clears config first, then runs `php artisan test`.
- Use `php artisan test --filter <Name>` for focused PHP tests.
- Use `vendor/bin/pint` for PHP formatting/linting.
- Use `npm run build` for frontend verification. Vite only builds `resources/css/app.css` and `resources/js/app.js`.

## Queue And Import Gotchas
- Queue work is not optional here. `config/queue.php` defaults to `database`, and many Filament actions/services dispatch jobs for imports, linking, alerts, and Bugio flows.
- If an import or bulk action appears to "do nothing", check whether a queue worker is running before changing code.
- `app/Services/Import/BaseImportService.php` always enqueues `ProcessImportRowJob` batches plus `FinalizeImportJob`.
- Import uploads are read from `Storage::disk('public')`; Filament file actions also target the `public` disk.

## Domain Wiring To Respect
- `AppServiceProvider` enables `Model::unguard()` globally and registers `ViagemObserver` and `DocumentoFreteObserver`.
- Changing `viagens.resultado_periodo_id` or `documentos_frete.viagem_id` has automatic cascade behavior through observers. Do not duplicate that linking/unlinking logic in new code unless you need a truly separate path.
- `App\Services\ViagemNumberService` owns prefixed trip-number allocation via the `viagem_sequences` table inside a DB transaction. Use it instead of inventing a new numbering flow.
- Filament settings pages such as `ViagemSettings`, `VeiculoSettings`, `PneuSettings`, and `ChecklistSettings` persist through `inerba/filament-db-config` into the `db_config` table. `config/db-config.php` caches those settings forever by default.

## Verification Limits
- Automated coverage is sparse right now: the custom test files in `tests/Unit/ViagemNumberServiceTest.php` and `tests/Feature/CriarViagemNumberTest.php` are empty, and the rest are default example tests.
- Prefer targeted manual verification for Filament flows, imports, observers, and queue-backed features after running the relevant command(s).

## Side Effects And Source Quality
- `composer` autoload/update scripts run `artisan config:clear`, `clear-compiled`, `package:discover`, and `filament:upgrade`; `post-update-cmd` also force-publishes Laravel assets. Expect Composer operations to mutate generated/bootstrap state.
- `routes/console.php` schedules `email:diario` twice daily and exposes `php artisan test:email`.
- `README.md` is the default Laravel stub. Treat code and config as the source of truth; use checked-in docs only when they still match the implementation.
