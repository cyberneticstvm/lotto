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
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('play_id');
            $table->date('play_date');
            $table->string('p1', 5)->nullable();
            $table->string('p2', 5)->nullable();
            $table->string('p3', 5)->nullable();
            $table->string('p4', 5)->nullable();
            $table->string('p5', 5)->nullable();
            $table->string('p6', 5)->nullable();
            $table->string('p7', 5)->nullable();
            $table->string('p8', 5)->nullable();
            $table->string('p9', 5)->nullable();
            $table->string('p10', 5)->nullable();
            $table->string('p11', 5)->nullable();
            $table->string('p12', 5)->nullable();
            $table->string('p13', 5)->nullable();
            $table->string('p14', 5)->nullable();
            $table->string('p15', 5)->nullable();
            $table->string('p16', 5)->nullable();
            $table->string('p17', 5)->nullable();
            $table->string('p18', 5)->nullable();
            $table->string('p19', 5)->nullable();
            $table->string('p20', 5)->nullable();
            $table->string('p21', 5)->nullable();
            $table->string('p22', 5)->nullable();
            $table->string('p23', 5)->nullable();
            $table->string('p24', 5)->nullable();
            $table->string('p25', 5)->nullable();
            $table->string('p26', 5)->nullable();
            $table->string('p27', 5)->nullable();
            $table->string('p28', 5)->nullable();
            $table->string('p29', 5)->nullable();
            $table->string('p30', 5)->nullable();
            $table->string('p31', 5)->nullable();
            $table->string('p32', 5)->nullable();
            $table->string('p33', 5)->nullable();
            $table->string('p34', 5)->nullable();
            $table->string('p35', 5)->nullable();
            $table->foreign('play_id')->references('id')->on('plays')->onDelete('cascade');
            $table->unique(['play_id', 'play_date']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
