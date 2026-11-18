<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Services\PayrollImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CommitPayrollImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $batchId) {}

    public function handle(PayrollImportService $imports): void
    {
        $imports->commit(ImportBatch::findOrFail($this->batchId));
    }

    public function failed(?Throwable $exception): void
    {
        ImportBatch::whereKey($this->batchId)->where('status', 'Queued')->update(['status' => 'Failed', 'failure' => 'Impor dibatalkan seluruhnya. Periksa duplikasi, periode final, izin, dan riwayat pajak.']);
        report($exception);
    }
}
