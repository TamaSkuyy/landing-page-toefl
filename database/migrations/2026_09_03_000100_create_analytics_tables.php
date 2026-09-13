<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('event_type', 64)->index();
            $table->json('event_data');
            $table->string('referral_source', 2048)->nullable();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['event_type', 'created_at'], 'analytics_type_created_idx');
        });

        if (DB::getDriverName() === 'mysql') {
            $this->addMysqlGeneratedColumns();
        } else {
            Schema::table('user_analytics', function (Blueprint $table) {
                $table->string('landing_source')->nullable()->index();
                $table->unsignedTinyInteger('scroll_depth')->nullable()->index();
                $table->string('section_id')->nullable()->index();
                $table->string('cta_zone')->nullable()->index();
                $table->string('cta_action')->nullable()->index();
                $table->string('payment_status')->nullable()->index();
                $table->unsignedBigInteger('payment_amount')->nullable()->index();
                $table->index(['event_type', 'cta_zone'], 'analytics_type_zone_idx');
            });
        }

        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->uuid('visitor_id')->nullable()->index();
            $table->string('landing_source')->nullable()->index();
            $table->string('referral_source', 2048)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedTinyInteger('max_scroll_depth')->default(0);
            $table->boolean('is_engaged')->default(false);
            $table->boolean('is_bounce')->default(true);
            $table->timestamp('started_at')->useCurrent()->index();
            $table->timestamp('last_seen_at')->useCurrent();
        });

        Schema::create('user_analytics_archive', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->uuid('visitor_id')->nullable();
            $table->string('event_type', 64);
            $table->json('event_data');
            $table->string('referral_source', 2048)->nullable();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at');
            $table->string('landing_source')->nullable();
            $table->unsignedTinyInteger('scroll_depth')->nullable();
            $table->string('section_id')->nullable();
            $table->string('cta_zone')->nullable();
            $table->string('cta_action')->nullable();
            $table->string('payment_status')->nullable();
            $table->unsignedBigInteger('payment_amount')->nullable();
        });

        Schema::create('analytics_sessions_archive', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->uuid('visitor_id')->nullable();
            $table->string('landing_source')->nullable();
            $table->string('referral_source', 2048)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->unsignedTinyInteger('max_scroll_depth')->default(0);
            $table->boolean('is_engaged')->default(false);
            $table->boolean('is_bounce')->default(true);
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
        });
    }

    private function addMysqlGeneratedColumns(): void
    {
        $columns = [
            "landing_source VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.landing_source'))) STORED",
            "scroll_depth TINYINT UNSIGNED GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.depth')) AS UNSIGNED)) STORED",
            "section_id VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.section'))) STORED",
            "cta_zone VARCHAR(64) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.zone'))) STORED",
            "cta_action VARCHAR(64) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.action'))) STORED",
            "payment_status VARCHAR(64) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.status'))) STORED",
            "payment_amount BIGINT UNSIGNED GENERATED ALWAYS AS (CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.amount')) AS UNSIGNED)) STORED",
        ];

        foreach ($columns as $definition) {
            DB::statement("ALTER TABLE user_analytics ADD COLUMN {$definition}");
        }

        DB::statement('CREATE INDEX analytics_landing_source_idx ON user_analytics (landing_source)');
        DB::statement('CREATE INDEX analytics_scroll_depth_idx ON user_analytics (scroll_depth)');
        DB::statement('CREATE INDEX analytics_section_id_idx ON user_analytics (section_id)');
        DB::statement('CREATE INDEX analytics_cta_action_idx ON user_analytics (cta_action)');
        DB::statement('CREATE INDEX analytics_payment_status_idx ON user_analytics (payment_status)');
        DB::statement('CREATE INDEX analytics_payment_amount_idx ON user_analytics (payment_amount)');
        DB::statement('CREATE INDEX analytics_type_zone_idx ON user_analytics (event_type, cta_zone)');
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_sessions_archive');
        Schema::dropIfExists('user_analytics_archive');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('user_analytics');
    }
};
