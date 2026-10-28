<?php

namespace App\Console\Commands\File;

use App\Libs\File\FileReferences;
use App\Models\Common\FileReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class RebuildFileReferences extends Command
{
    protected $signature = 'files:rebuild-references';

    protected $description = 'Rebuild references for existing registered file consumers';

    public function handle(FileReferences $references): int
    {
        foreach (config('files.reference_models', []) as $class) {
            $model = new $class;
            if (! Schema::hasTable($model->getTable())) {
                continue;
            }
            $class::query()->chunkById(100, function ($models) use ($references): void {
                foreach ($models as $model) {
                    $references->syncModel($model);
                }
            });
            FileReference::where('reference_type', $model->getMorphClass())
                ->whereNotIn('reference_id', $model->newQueryWithoutScopes()->select($model->getKeyName()))
                ->delete();
        }
        $this->info('File references rebuilt.');

        return self::SUCCESS;
    }
}
