<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'branch_id', 'year', 'month', 'module', 'status', 'revision', 'reviewed_by', 'reviewed_at', 'finalized_at', 'company_snapshot'])]
class PayrollRun extends Model
{
    use HasFactory;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    protected function casts(): array
    {
        return ['company_snapshot' => 'array', 'reviewed_at' => 'datetime', 'finalized_at' => 'datetime', 'year' => 'integer', 'month' => 'integer'];
    }
}
