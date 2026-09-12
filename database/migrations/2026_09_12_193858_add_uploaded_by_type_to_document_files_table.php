<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_files', function (Blueprint $table) {
            $table->string('uploaded_by_type')->default('admin')->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('document_files', function (Blueprint $table) {
            $table->dropColumn('uploaded_by_type');
        });
    }
};
