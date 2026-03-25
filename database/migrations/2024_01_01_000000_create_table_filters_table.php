<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('persistent-table-filters.table_name', 'table_filters');

        Schema::create($table, function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('resource');          // e.g. 'campaigns', 'orders'
            $table->string('label');             // user-facing name
            $table->json('filters');             // the saved state
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'resource']);
        });
    }

    public function down(): void
    {
        $table = config('persistent-table-filters.table_name', 'table_filters');
        Schema::dropIfExists($table);
    }
};
