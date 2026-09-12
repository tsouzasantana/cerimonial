<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->index('status');
            $table->index('event_date');
            $table->index(['status', 'event_date']);
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });

        Schema::table('contract_tasks', function (Blueprint $table) {
            $table->index('due_date');
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['event_date']);
            $table->dropIndex(['status', 'event_date']);
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropIndex(['status', 'due_date']);
        });

        Schema::table('contract_tasks', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropIndex(['status', 'due_date']);
        });
    }
};
