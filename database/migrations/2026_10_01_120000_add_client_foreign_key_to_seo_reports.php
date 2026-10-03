<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('seo_reports', 'client_id')) {
            Schema::table('seo_reports', function (Blueprint $table) {
                $table->foreignId('client_id')->nullable()->after('id');
            });
        }

        $hasClientForeignKey = collect(Schema::getForeignKeys('seo_reports'))
            ->contains(fn (array $foreignKey) =>
                $foreignKey['columns'] === ['client_id'] && $foreignKey['foreign_table'] === 'clients'
            );

        if (! $hasClientForeignKey) {
            Schema::table('seo_reports', function (Blueprint $table) {
                $table->foreign('client_id')
                    ->references('id')
                    ->on('clients')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        $foreignKey = collect(Schema::getForeignKeys('seo_reports'))
            ->first(fn (array $foreignKey) =>
                $foreignKey['columns'] === ['client_id'] && $foreignKey['foreign_table'] === 'clients'
            );

        if ($foreignKey) {
            Schema::table('seo_reports', function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey['name']);
            });
        }
    }
};