<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            $table->string('type');
            $table->string('url');
            $table->string('status')->default('pending_approval');
            $table->text('rejected_reason')->nullable();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['form_id', 'type']);
        });

        // Backfill: any form with an already-configured URL is grandfathered
        // as active -- a one-time exception so no live external integration
        // breaks the moment this ships. proposed_by/reviewed_by are set to
        // the form's own user_id (best-effort attribution; there's no real
        // historical "who approved this" data to recover).
        $slots = [
            'webhook_url' => 'webhook',
            'slack_webhook_url' => 'slack',
            'zapier_webhook_url' => 'zapier',
            'make_webhook_url' => 'make',
            'crm_webhook_url' => 'crm',
        ];

        DB::table('forms')->orderBy('id')->select(['id', 'user_id', 'settings'])
            ->each(function ($form) use ($slots) {
                $settings = json_decode((string) $form->settings, true) ?: [];

                foreach ($slots as $settingsKey => $type) {
                    $url = $settings[$settingsKey] ?? null;

                    if (! is_string($url) || $url === '') {
                        continue;
                    }

                    DB::table('form_integrations')->insert([
                        'form_id' => $form->id,
                        'type' => $type,
                        'url' => $url,
                        'status' => 'active',
                        'proposed_by' => $form->user_id,
                        'reviewed_by' => $form->user_id,
                        'reviewed_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_integrations');
    }
};
