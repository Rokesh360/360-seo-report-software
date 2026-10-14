<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('website');
            $table->string('email')->nullable();
            $table->date('date_of_campaign')->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('status')->default(true);   // 1 = active, 0 = inactive
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
