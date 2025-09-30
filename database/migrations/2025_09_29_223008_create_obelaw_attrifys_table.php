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
        Schema::create('obelaw_attrifys', function (Blueprint $table) {
            $table->id();
            $table->morphs('modelable');
            $table->string('key')->index();
            $table->json('value');
            $table->unique(['modelable_id', 'modelable_type', 'key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obelaw_attrifys');
    }
};
