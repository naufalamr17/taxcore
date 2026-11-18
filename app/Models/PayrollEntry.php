<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payroll_run_id', 'employee_id', 'input', 'result', 'gross', 'tax', 'take_home', 'version'])]
class PayrollEntry extends Model
{
    use HasFactory;

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return ['input' => 'array', 'result' => 'array', 'gross' => 'integer', 'tax' => 'integer', 'take_home' => 'integer', 'version' => 'integer'];
    }
}
