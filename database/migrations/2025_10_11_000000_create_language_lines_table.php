<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('language_lines', function (Blueprint $t) {
            $t->id();
            $t->string('group');
            $t->string('key');
            $t->json('text'); // {"en":"Service","ar":"خدمة"}
            $t->timestamps();
            $t->unique(['group','key']);
            $t->index('group');
        });
    }
    public function down(): void {
        Schema::dropIfExists('language_lines');
    }
};
