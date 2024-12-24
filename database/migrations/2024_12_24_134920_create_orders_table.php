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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number', 15);
            $table->string('ticket_number', 5);
            $table->integer('ticket_count');
            $table->unsignedBigInteger('ticket_id');
            $table->string('ticket_name', 10);
            $table->unsignedBigInteger('play_id');
            $table->string('play_code', 5);
            $table->float('admin_rate', 5, 2)->default(0);
            $table->float('leader_rate', 5, 2)->default(0);
            $table->float('user_rate', 5, 2)->default(0);
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->default(0);
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('play_id')->references('id')->on('plays')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
