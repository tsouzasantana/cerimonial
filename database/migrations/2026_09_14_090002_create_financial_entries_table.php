<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status')->default('pendente');
            $table->text('notes')->nullable();
            $table->string('updated_by_type')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entries');
    }
};
