<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\StaticSiteExporter;
use Illuminate\Database\Eloquent\Model;

/**
 * Regenerates the static HTML export whenever content changes in the admin.
 *
 * The site is small (~125 pages total), so a full rebuild on every save is
 * simpler and safer than tracking which archive/index pages reference the
 * changed item - a full rebuild can never miss an invalidation.
 *
 * Skipped under the test suite: `exportAll()` writes to the real
 * public/cache/ on disk regardless of which DB connection is active, and
 * every Feature test runs against an isolated in-memory sqlite DB (see
 * phpunit.xml). Without this guard, saving a Post/Page/Product fixture in a
 * test overwrites the real static export with that test's fixture data -
 * discovered 2026-09-10 when a fresh feature's export looked empty right
 * after a test run had quietly wiped it.
 */
class StaticExportObserver
{
    public function saved(Model $model): void
    {
        $this->export();
    }

    public function deleted(Model $model): void
    {
        $this->export();
    }

    private function export(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        app(StaticSiteExporter::class)->exportAll();
    }
}
