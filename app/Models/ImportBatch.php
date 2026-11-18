<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'branch_id', 'user_id', 'module', 'status', 'records', 'errors', 'row_count', 'failure'])]
class ImportBatch extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['records' => 'array', 'errors' => 'array', 'row_count' => 'integer'];
    }
}
