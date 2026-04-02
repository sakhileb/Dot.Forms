<div style="padding: 32px 24px; background: var(--bg);" x-data="{
    dragging: null,
    collectKeys() {
        return Array.from(this.$refs.fieldList.querySelectorAll('[data-key]')).map((el) => el.dataset.key);
    }
}" wire:poll.20s="refreshPresence">
    <div style="display: flex; flex-direction: column; gap: 28px; max-width: 1400px; margin: 0 auto;">
        <!-- Header -->
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <div>
                <h1 style="font-family: 'Sora', sans-serif; font-size: 32px; font-weight: 800; color: var(--ink); margin: 0; margin-bottom: 8px;">
                    {{ $form->title ?: 'Form Builder' }}
                </h1>
                <p style="font-size: 14px; color: var(--muted); margin: 0;">Design fields, configure settings, and publish your form.</p>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                <a href="{{ $submissionsUrl }}" style="padding: 11px 18px; background: white; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-weight: 600; color: var(--ink); text-decoration: none; cursor: pointer; transition: all 0.2s;">
                    📊 Submissions
                </a>
                <a href="{{ $previewUrl }}" target="_blank" style="padding: 11px 18px; background: white; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-weight: 600; color: var(--ink); text-decoration: none; cursor: pointer; transition: all 0.2s;">
                    👁️ Preview
                </a>
                <a href="{{ $aiSuggestionsUrl }}" style="padding: 11px 18px; background: white; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-weight: 600; color: var(--ink); text-decoration: none; cursor: pointer; transition: all 0.2s;">
                    ✨ AI Fields
                </a>
                <a href="{{ $aiAnalyticsUrl }}" style="padding: 11px 18px; background: white; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-weight: 600; color: var(--ink); text-decoration: none; cursor: pointer; transition: all 0.2s;">
                    📈 Analytics
                </a>
                <button wire:click="saveDraft" style="margin-left: auto; padding: 11px 18px; background: white; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-weight: 600; color: var(--ink); cursor: pointer; transition: all 0.2s;">
                    💾 Save Draft
                </button>
                <button wire:click="publish" style="padding: 11px 18px; background: var(--yellow); color: var(--ink); border: none; border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; transition: all 0.2s;">
                    🚀 Publish
                </button>
            </div>
        </div>
        <!-- Status Messages -->
        @if (session('status'))
            <div style="padding: 14px 16px; background: linear-gradient(135deg, #ECFDF5 0%, #F0FDF4 100%); border: 1px solid #DCFCE7; border-radius: 8px; font-size: 14px; color: #166534; display: flex; align-items: center; gap: 10px;">
                <span>✅</span>
                {{ session('status') }}
            </div>
        @endif

        @if (count($activeEditors) > 0)
            <div style="padding: 14px 16px; background: linear-gradient(135deg, #F0F9FF 0%, #F0F4FF 100%); border: 1px solid #BAE6FD; border-radius: 8px; font-size: 14px; color: #0C4A6E;">
                👥 Currently editing: {{ collect($activeEditors)->pluck('name')->implode(', ') }}
            </div>
        @endif

        <!-- Main Layout -->
        <div style="display: grid; grid-template-columns: 280px 1fr 320px; gap: 24px; align-items: start;">
            <!-- Left Sidebar - Field Types -->
            <aside style="background: white; border: 1px solid #E5E5E5; border-radius: 12px; padding: 20px; height: fit-content;">
                <h3 style="font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin: 0 0 16px 0; letter-spacing: 0.5px;">
                    Field Types
                </h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    @foreach ($fieldTypes as $type)
                        <button wire:click="addField('{{ $type }}')" style="padding: 12px 14px; background: var(--yellow); color: var(--ink); border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; text-align: left;">
                            + {{ \Illuminate\Support\Str::headline($type) }}
                        </button>
                    @endforeach
                </div>
            </aside>

            <!-- Main Editor -->
            <section style="display: flex; flex-direction: column; gap: 20px;">
                <!-- Form Metadata -->
                <div style="background: white; border: 1px solid #E5E5E5; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; letter-spacing: 0.5px;">
                            Form Title
                        </label>
                        <input type="text" wire:model.live="title" style="width: 100%; padding: 11px 14px; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; transition: all 0.2s;" />
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; letter-spacing: 0.5px;">
                            Description
                        </label>
                        <textarea wire:model.live="description" rows="3" style="width: 100%; padding: 11px 14px; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 14px; font-family: 'Inter', sans-serif; resize: vertical; transition: all 0.2s;"></textarea>
                    </div>
                </div>

                <!-- Fields List -->
                <div style="background: white; border: 1px solid #E5E5E5; border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 16px;">
                    <h3 style="font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: var(--ink); margin: 0;">
                        Form Fields
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 12px;" x-ref="fieldList">
                        @forelse ($fields as $index => $field)
                            <article
                                wire:key="builder-field-{{ $field['key'] }}"
                                data-key="{{ $field['key'] }}"
                                draggable="true"
                                @dragstart="dragging = $el.dataset.key"
                                @dragover.prevent
                                @drop.prevent="
                                    if (dragging && dragging !== $el.dataset.key) {
                                        const items = Array.from($refs.fieldList.querySelectorAll('[data-key]'));
                                        const from = items.find((x) => x.dataset.key === dragging);
                                        const to = $el;
                                        if (from && to) {
                                            const fromIndex = items.indexOf(from);
                                            const toIndex = items.indexOf(to);
                                            if (fromIndex < toIndex) {
                                                to.after(from);
                                            } else {
                                                to.before(from);
                                            }
                                            $wire.reorderFields(collectKeys());
                                        }
                                    }
                                    dragging = null;
                                "
                                style="padding: 16px; background: var(--bg); border: 1px solid #E5E5E5; border-radius: 10px; display: flex; gap: 16px; transition: all 0.2s; cursor: grab;"
                            >
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 12px;">
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                        <div>
                                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                                Label
                                            </label>
                                            <input type="text" wire:model.live="fields.{{ $index }}.label" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                                        </div>
                                        <div>
                                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                                Type
                                            </label>
                                            <select wire:model.live="fields.{{ $index }}.type" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;">
                                                @foreach ($fieldTypes as $type)
                                                    <option value="{{ $type }}">{{ \Illuminate\Support\Str::headline($type) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                            Placeholder
                                        </label>
                                        <input type="text" wire:model.live="fields.{{ $index }}.placeholder" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                                    </div>

                                    @if (in_array($field['type'], ['select', 'radio', 'checkbox'], true))
                                        <div>
                                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                                Options (comma-separated)
                                            </label>
                                            <input type="text" wire:model.live="fields.{{ $index }}.options" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                                        </div>
                                    @endif

                                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer;">
                                        <input type="checkbox" wire:model.live="fields.{{ $index }}.required" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--yellow);" />
                                        <span>Mark as required</span>
                                    </label>
                                </div>

                                <div style="display: flex; flex-direction: column; gap: 8px; width: fit-content;">
                                    <button type="button" wire:click="moveFieldUp({{ $index }})" style="padding: 8px 12px; background: white; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--ink); cursor: pointer; transition: all 0.2s;">
                                        ⬆️
                                    </button>
                                    <button type="button" wire:click="moveFieldDown({{ $index }})" style="padding: 8px 12px; background: white; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--ink); cursor: pointer; transition: all 0.2s;">
                                        ⬇️
                                    </button>
                                    <button type="button" wire:click="openFieldSettings({{ $index }})" style="padding: 8px 12px; background: white; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--ink); cursor: pointer; transition: all 0.2s;">
                                        ⚙️
                                    </button>
                                    <button type="button" wire:click="removeField({{ $index }})" style="padding: 8px 12px; background: var(--red-light); border: 1px solid #FFCDD2; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--red); cursor: pointer; transition: all 0.2s;">
                                        🗑️
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div style="padding: 40px 24px; text-align: center; border: 2px dashed #E5E5E5; border-radius: 10px; background: var(--bg);">
                                <p style="font-size: 14px; color: var(--muted); margin: 0;">
                                    ✨ Add a field from the left sidebar to start building your form.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <!-- Right Sidebar - Settings -->
            <aside style="background: white; border: 1px solid #E5E5E5; border-radius: 12px; padding: 20px; height: fit-content; display: flex; flex-direction: column; gap: 20px; max-height: calc(100vh - 200px); overflow-y: auto;">
                <div>
                    <h3 style="font-family: 'Sora', sans-serif; font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin: 0 0 16px 0; letter-spacing: 0.5px;">
                        ⚙️ Form Settings
                    </h3>

                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Confirmation Message
                            </label>
                            <textarea wire:model.live="settings.confirmation_message" rows="2" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif; resize: vertical;"></textarea>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Theme
                            </label>
                            <select wire:model.live="settings.theme" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;">
                                <option value="light">☀️ Light</option>
                                <option value="dark">🌙 Dark</option>
                                <option value="brand">🎨 Brand</option>
                            </select>
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Brand Color
                            </label>
                            <input type="text" wire:model.live="settings.brand_color" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" placeholder="#4f46e5" />
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Logo
                            </label>
                            <input type="file" wire:model="logoUpload" accept="image/*" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px;" />
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Form logo" style="margin-top: 8px; height: 40px; width: auto; border-radius: 6px;">
                            @endif
                        </div>

                        <hr style="border: none; border-top: 1px solid #E5E5E5; margin: 0;">

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Response Limit
                            </label>
                            <input type="number" min="1" wire:model.live="settings.limit_responses" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Opens At
                            </label>
                            <input type="datetime-local" wire:model.live="settings.open_at" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Closes At
                            </label>
                            <input type="datetime-local" wire:model.live="settings.close_at" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                        </div>

                        <hr style="border: none; border-top: 1px solid #E5E5E5; margin: 0;">

                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer;">
                            <input type="checkbox" wire:model.live="settings.consent_required" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--yellow);" />
                            <span>Require GDPR Consent</span>
                        </label>

                        @if ($settings['consent_required'] ?? false)
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                    Consent Label
                                </label>
                                <input type="text" wire:model.live="settings.consent_label" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                            </div>
                        @endif

                        <hr style="border: none; border-top: 1px solid #E5E5E5; margin: 0;">

                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer;">
                            <input type="checkbox" wire:model.live="settings.quiz_enabled" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--yellow);" />
                            <span>Enable Quiz Mode</span>
                        </label>

                        @if ($settings['quiz_enabled'] ?? false)
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                    Answer Key (JSON)
                                </label>
                                <textarea wire:model.live="settings.quiz_answer_key_json" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px; font-family: 'monospace'; resize: vertical;" placeholder='{&quot;1&quot;:&quot;Option A&quot;,&quot;2&quot;:&quot;42&quot;}'></textarea>
                            </div>
                        @endif

                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--ink); cursor: pointer;">
                            <input type="checkbox" wire:model.live="settings.conversational_mode" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--yellow);" />
                            <span>Conversational Mode</span>
                        </label>

                        <hr style="border: none; border-top: 1px solid #E5E5E5; margin: 0;">

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                CRM Provider
                            </label>
                            <select wire:model.live="settings.crm_provider" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;">
                                <option value="none">None</option>
                                <option value="hubspot">HubSpot</option>
                                <option value="pipedrive">Pipedrive</option>
                                <option value="generic">Generic</option>
                            </select>
                        </div>

                        @if (($settings['crm_provider'] ?? 'none') !== 'none')
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                    CRM Webhook URL
                                </label>
                                <input type="url" wire:model.live="settings.crm_webhook_url" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                            </div>
                        @endif

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Data Retention (days)
                            </label>
                            <input type="number" min="1" wire:model.live="settings.retention_days" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" />
                        </div>

                        <hr style="border: none; border-top: 1px solid #E5E5E5; margin: 0;">

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Slack Webhook
                            </label>
                            <input type="url" wire:model.live="settings.slack_webhook_url" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" placeholder="https://hooks.slack.com/..." />
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Zapier Webhook
                            </label>
                            <input type="url" wire:model.live="settings.zapier_webhook_url" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" placeholder="https://hooks.zapier.com/..." />
                        </div>

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Make Webhook
                            </label>
                            <input type="url" wire:model.live="settings.make_webhook_url" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 13px; font-family: 'Inter', sans-serif;" placeholder="https://hook.make.com/..." />
                        </div>

                        <hr style="border: none; border-top: 1px solid #E5E5E5; margin: 0;">

                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; letter-spacing: 0.5px;">
                                Custom CSS
                            </label>
                            <textarea wire:model.live="settings.custom_css" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 6px; font-size: 12px; font-family: 'monospace'; resize: vertical;" placeholder=".form-card { border-radius: 12px; }"></textarea>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    <div wire:poll.30s="autoSave" class="hidden" aria-hidden="true"></div>

    <!-- Field Settings Modal -->
    @if ($showFieldSettingsModal && $editingFieldIndex !== null)
        <div style="position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 50;">
            <div style="background: white; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,.2);">
                <h3 style="font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: var(--ink); margin: 0 0 20px 0;">
                    ⚙️ Field Settings
                </h3>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; letter-spacing: 0.5px;">
                            Helper Text
                        </label>
                        <textarea wire:model.live="fields.{{ $editingFieldIndex }}.helper_text" rows="2" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 13px; font-family: 'Inter', sans-serif; resize: vertical;"></textarea>
                    </div>

                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 700; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; letter-spacing: 0.5px;">
                            Conditional Logic
                        </label>
                        <textarea wire:model.live="fields.{{ $editingFieldIndex }}.conditional_logic" rows="3" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E5E5; border-radius: 8px; font-size: 13px; font-family: 'Inter', sans-serif; resize: vertical;" placeholder="Example: show when field_2 equals 'yes'"></textarea>
                    </div>

                    <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 12px; border-top: 1px solid #E5E5E5;">
                        <button wire:click="closeFieldSettings" style="padding: 10px 18px; background: #F5F5F5; color: var(--ink); border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
