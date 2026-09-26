<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('plan_expires_at')->nullable();
            $table->string('plan_period', 10)->nullable(); // monthly, yearly
            $table->timestamp('credits_reset_at')->nullable();
            $table->string('locale', 10)->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('kind', 20)->default('plan'); // plan, license
            $table->string('status', 20)->default('pending'); // pending, paid, failed
            $table->string('period', 10)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unique(['gateway', 'reference']);
        });

        Schema::table('deployments', function (Blueprint $table) {
            $table->string('storage_path')->nullable(); // files live on the "published" disk under this prefix
            $table->string('takedown_reason')->nullable();
        });

        Schema::create('abuse_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subdomain');
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('status', 20)->default('open'); // open, actioned, dismissed
            $table->timestamps();
        });

        // Licence server tables (only used when STUDIO_LICENSE_SERVER=true).
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('type', 20)->default('regular'); // regular, extended
            $table->string('source', 20)->default('direct'); // direct, envato, manual
            $table->string('purchase_ref')->nullable()->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('domain')->nullable();
            $table->string('instance_id')->nullable();
            $table->string('status', 20)->default('active'); // active, revoked
            $table->timestamp('supported_until')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_check_at')->nullable();
            $table->string('last_version', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->text('notes')->nullable();
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('abuse_reports');
        Schema::table('deployments', fn (Blueprint $t) => $t->dropColumn(['storage_path', 'takedown_reason']));
        Schema::table('payments', function (Blueprint $t) {
            $t->dropUnique(['gateway', 'reference']);
            $t->dropColumn(['kind', 'status', 'period', 'meta', 'paid_at']);
        });
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['plan_expires_at', 'plan_period', 'credits_reset_at', 'locale', 'terms_accepted_at']));
    }
};
