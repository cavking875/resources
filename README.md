# Laravel Migration App

This folder now contains a bootstrapped Laravel application with parity controllers/routes for migrating from the current front-controller router.

Current scope in this scaffold:
- `GET /health`
- `POST /auth/login`
- `POST /auth/logout`
- `POST /forecast`
- `POST /forecast/persist`
- `POST /imports/projects/validate`
- `POST /imports/projects`
- `GET /imports/projects/history`
- `GET /imports/projects/{id}`
- `GET /projects`
- `GET /projects/{id}`
- `GET /projects/{id}/allocations`
- `GET /projects/{id}/monthly-demand`
- `GET /projects/{id}/financials`
- `GET /projects/{id}/gap`
- `GET /projects/{id}/ai-recommendations`
- `POST /projects/{id}/ai-recommendations`
- `POST /projects/{id}/ai-recommendations/generate`
- `GET /staff`
- `GET /staff/{id}`
- `POST /staff`
- `POST /staff/{id}/availability`
- `POST /allocations/warnings`
- `POST /allocations`
- `POST /allocations/{id}`
- `POST /allocations/{id}/delete`
- `GET /dashboards/role-gap`
- `GET /dashboards/monthly-demand`
- `GET /dashboards/financial-summary`
- `GET /reports/ai-summary`
- `GET /exports/role-gap.csv`
- `GET /exports/monthly-demand.csv`
- `GET /scenarios`
- `POST /scenarios`
- `GET /scenarios/{id}`
- `POST /scenarios/{id}/projects`
- `GET /scenarios/{id}/analysis`
- `GET /maps/projects`
- `GET /maps/staff`
- `GET /maps/heatmap`
- `GET /maps/shared-resource-suggestions`
- `GET /settings/resource-roles`
- `GET /settings/resource-rules`
- `POST /settings/resource-rules`
- `GET /settings/phase-multipliers`
- `POST /settings/phase-multipliers`
- `GET /settings/complexity-multipliers`
- `POST /settings/complexity-multipliers`
- `GET /settings/ai-prompts`
- `POST /settings/ai-prompts`
- `GET /audit-logs`

## Runtime Verification

Run from `backend/laravel`:

```powershell
php artisan --version
php artisan route:list --path=api
php artisan test --filter=ApiHealthTest
php artisan test --filter=ApiAuthTest
```

From repository root, verify contract parity:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\check-laravel-parity.ps1
```

## Integration Notes

1. Ensure environment variables match existing backend:
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
2. Verify parity endpoints using `docs/api/scenarios.http` and smoke scripts.

## Notes

- Controllers in `app/Http/Controllers/Api/*` bridge to existing legacy services in `backend/src/*`.
- JSON/status behavior is kept aligned with the current API contract.
