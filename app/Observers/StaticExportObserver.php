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
 */
class StaticExportObserver
{
    public function saved(Model $model): void
    {
        app(StaticSiteExporter::class)->exportAll();
    }

    public function deleted(Model $model): void
    {
        app(StaticSiteExporter::class)->exportAll();
    }
}
