<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
            $table->string('role')->default('AUDITOR');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('npwp', 16)->unique();
            $table->string('address')->nullable();
            $table->string('city');
            $table->string('signer_name');
            $table->string('signer_npwp', 16);
            $table->timestamps();
        });
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code', 6);
            $table->string('nitku', 22)->unique();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });
        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'user_id']);
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('nik', 16);
            $table->string('position');
            $table->string('ptkp', 4);
            $table->date('joined_at');
            $table->date('left_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'nik']);
        });
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->string('module');
            $table->string('status')->default('Draft');
            $table->unsignedInteger('revision')->default(1);
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->json('company_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'year', 'month', 'module']);
            $table->index(['company_id', 'year', 'month']);
        });
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->json('input');
            $table->json('result');
            $table->bigInteger('gross');
            $table->bigInteger('tax');
            $table->bigInteger('take_home');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject');
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['company_id', 'created_at']);
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->string('permission');
            $table->boolean('allowed');
            $table->timestamps();
            $table->unique(['role', 'permission']);
        });
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('module');
            $table->string('status')->default('Preview');
            $table->json('records');
            $table->json('errors');
            $table->unsignedInteger('row_count');
            $table->text('failure')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['import_batches', 'role_permissions', 'audit_events', 'payroll_entries', 'payroll_runs', 'employees', 'company_user', 'branches', 'companies'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'role', 'is_active']);
        });
    }
};
