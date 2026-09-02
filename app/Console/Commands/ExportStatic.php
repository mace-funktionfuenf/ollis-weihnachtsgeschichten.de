<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\StaticSiteExporter;
use Illuminate\Console\Command;

class ExportStatic extends Command
{
    protected $signature = 'export:static';

    protected $description = 'Render every content page to public/cache/**/index.html';

    public function handle(StaticSiteExporter $exporter): int
    {
        $exporter->purge();
        $paths = $exporter->exportAll();

        $this->info(count($paths).' pages exported to public/cache/.');

        return self::SUCCESS;
    }
}
