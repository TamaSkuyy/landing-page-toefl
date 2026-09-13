<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 32);
            $table->string('landing_source')->nullable()->index();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();
            $table->json('extra')->nullable();
            $table->string('status', 32)->default('new')->index();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->index();
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('landing_source')->nullable()->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 32);
            $table->unsignedBigInteger('amount');
            $table->string('payment_method')->nullable();
            $table->string('duitku_reference')->nullable()->unique();
            $table->string('status', 32)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamps();
            $table->index(['email', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('leads');
    }
};
