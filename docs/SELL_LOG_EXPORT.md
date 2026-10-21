# Sell log export (queued)

The Excel button on **Sell Log** and the report pages (Needs Return, Reported Issues, Solved, Archived) no longer builds the file inside the web request. It queues a background job and the page downloads the file when it is ready.

## Flow

1. `POST /manager/orders/export` (`manager.orders.export`, `can:manage-sell-log`, `throttle:10,1`) validates the filters (`App\Http\Requests\ExportOrdersRequest`) and creates a `data_exports` row. If the same manager already has an export queued or processing (less than 30 minutes old), that one is returned instead of starting another.
2. `App\Jobs\GenerateOrdersExportJob` streams the rows with OpenSpout (`App\Services\Exports\XlsxExportWriter`) to `storage/app/exports/{user_id}/…xlsx`. Memory stays flat: the full local sell log (~82k rows) peaks around 55 MB.
3. The page polls `GET /manager/orders/exports/{id}` every 3 s, then opens `GET /manager/orders/exports/{id}/download`. Only the manager who requested the export can poll or download it (`App\Policies\DataExportPolicy`) because the file contains account passwords. A pending export survives a page reload (browser `localStorage`).

## Filters

| Form field | Effect |
|---|---|
| `status` | Report status: `needs_return`, `has_problem`, `solved`, `archived`; `all` = no report filter |
| `start_date`, `end_date` | `Y-m-d`, inclusive whole days on `orders.created_at` |
| `searchOrder` | Seller name, account email, buyer phone or name (contains) |
| `id` | Store profile |

## Operations

- **A queue worker is required.** On the VPS this is the `accounts-queue` container. On cron-only hosts, `QUEUE_WORKER_VIA_SCHEDULER=true` drains the queue every minute. With `QUEUE_CONNECTION=sync` the export runs inside the request (fine locally, slow for large ranges).
- The job timeout is 900 s (overrides the worker's `--timeout`). The queue's `retry_after` is 90 s, so an export running longer is redelivered; the redelivered copy is discarded and the original run finishes (`GenerateOrdersExportJob::failed`).
- `exports:prune` runs hourly (scheduler) and deletes exports and files older than 24 hours.
- Migration: `2026_10_21_160000_create_data_exports_table`.
