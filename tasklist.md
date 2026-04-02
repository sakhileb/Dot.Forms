### Phase 1: Foundation & Setup

**1. Project Initialization**
- [x] Install Laravel with Jetstream (Livewire stack, Teams support).
- [x] Configure `.env` with database, mail, and AI API keys (e.g., OpenAI, Gemini).
- [x] Set up Tailwind CSS and compile assets (Vite).
- [x] Configure default team settings (e.g., max members per team, owner roles).

**2. Database & Models**
- [x] Create migration for `forms` table (title, description, team_id, user_id, settings JSON, is_published, etc.).
- [x] Create migration for `form_fields` table (form_id, type, label, placeholder, options JSON, validation_rules, order).
- [x] Create migration for `form_submissions` table (form_id, user_id, data JSON, submitted_at, ip_address, user_agent).
- [x] Create migration for `ai_suggestions` table (form_id, field_id, suggestion_type, content, applied_at).
- [x] Create Eloquent models: `Form`, `FormField`, `FormSubmission`, `AiSuggestion`.
- [x] Define relationships: Form belongs to Team/User, has many Fields/Submissions.

**3. Jetstream Teams Integration**
- [x] Extend Jetstream's Team model to have a `forms` relationship.
- [x] Add Team policy gates (e.g., `canCreateForm`, `canEditForm`, `canViewSubmissions`).
- [x] Modify Jetstream's team blade files to show "Forms" as a team tab.

---

### Phase 2: Form Builder (Livewire Components)

**4. Form Listing & Dashboard**
- [x] Create `Livewire/Forms/Index` component (list forms for current team).
- [x] Implement search, filter (by status: published/draft), and pagination.
- [x] Add "Create New Form" button (redirects to builder).
- [x] Add duplicate, archive, and delete form actions.

**5. Form Builder Interface**
- [x] Create `Livewire/Forms/Builder` component.
- [x] Build drag-and-drop field sidebar (input types: text, email, number, textarea, select, radio, checkbox, date, file).
- [x] Implement field settings modal (label, placeholder, required, helper text, conditional logic).
- [x] Add reorder fields via Livewire + Alpine.js drag-and-drop.
- [x] Create "Form Settings" panel (confirmation message, limit responses, schedule open/close).
- [x] Add save as draft, preview, and publish buttons.
- [x] Auto-save every 30 seconds using Livewire's `$wire.poll`.

**6. Form Rendering & Public View**
- [x] Create public route `/forms/{slug}`.
- [x] Build `Livewire/Forms/PublicView` component to render dynamic fields.
- [x] Add client-side validation (Livewire's `$rules` + Alpine for UX).
- [x] Implement file upload handling (store in S3/public disk, attach to submission).
- [x] Add reCAPTCHA/honeypot for spam prevention.

---

### Phase 3: Submissions & Data Management

**7. Submissions Management**
- [x] Create `Livewire/Forms/Submissions` component.
- [x] Display submissions in a table with column toggle (dynamic columns from form fields).
- [x] Add export to CSV/Excel (using Laravel Excel).
- [x] Implement single submission view (detail modal).
- [x] Add delete bulk submissions action.

**8. Notifications & Integrations**
- [x] Add form owner email notification on new submission (Laravel notifications).
- [x] Create Slack/Webhook integration settings per form.
- [x] Implement Zapier/Make webhook triggers (optional).

---

### Phase 4: AI-Powered Features (Core Differentiator)

**9. AI Form Generation**
- [x] Create `Livewire/Forms/AiBuilder` component.
- [x] Add textarea input: "Describe the form you want (e.g., 'Event registration with name, email, dietary restrictions')".
- [x] Build backend service: `AiFormGenerator` (call OpenAI API with structured prompt).
- [x] Parse AI response into form fields (title, field types, options).
- [x] Allow user to edit/confirm before saving to database.

**10. AI Field Suggestions**
- [x] In form builder, add "AI Suggest Fields" button based on form title/description.
- [x] Create `AiFieldSuggestion` Livewire component (shows suggested fields to add).
- [x] Implement "Enhance Field Labels" (convert "full_name" → "Full Name").

**11. Smart Validation & Logic**
- [x] AI-powered "Suggest Validation Rules" (e.g., detect email field → add email validation).
- [x] Build conditional logic UI (show/hide fields based on previous answers) – enhanced with AI suggestions for common rules.

**12. AI Analysis of Submissions**
- [x] Create `Livewire/Forms/AiAnalytics` component.
- [x] Add button: "Summarize 100 submissions" → AI generates insights (e.g., "80% chose Option A").
- [x] Implement sentiment analysis for open-text responses (positive/neutral/negative).
- [x] Generate AI recommendations to improve form (e.g., "Field X has 40% drop-off, consider rewording").

---

### Phase 5: User Experience & Polish

**13. Theming & Customization**
- [x] Allow per-form custom CSS (sanitized) and logo upload.
- [x] Add basic theme selector (light, dark, brand color) using Tailwind.
- [x] Implement responsive design (mobile-friendly form filling).

**14. Collaboration Features**
- [x] Add team member roles (viewer, editor, owner) for each form (extend Jetstream roles).
- [x] Show real-time cursor/editing indicator using Livewire presence channel (optional with Laravel Reverb).

**15. Performance & Security**
- [x] Implement rate limiting on form submissions.
- [x] Add GDPR compliance: data retention settings, export user data, consent checkbox.
- [x] Cache form structure with Laravel's cache (for public forms).

**16. Analytics Dashboard**
- [x] Create `Livewire/Dashboard/Analytics` (team-wide stats).
- [x] Show total submissions, completion rate, average time to complete.
- [x] Chart.js + Livewire for visual reports (bar charts, pie charts).

---

### Phase 6: Testing & Deployment

**17. Testing**
- [x] Write Pest tests for form creation, submission, AI generation (mock API calls).
- [x] Test team permissions (user without edit role cannot modify form).
- [x] Test file uploads, validation, and conditional logic.

**18. Deployment & Docs**
- [x] Configure queues for email notifications and AI API calls.
- [x] Set up scheduled job to close forms based on end date.
- [x] Write user documentation (markdown) for AI features.
- [ ] Deploy to Forge/Vapor or any Laravel-compatible host.

---

### Optional Advanced Features (Bonus)

- [x] AI-powered quiz scoring (auto-grade responses based on answer key).
- [x] Conversational form mode (AI chatbot asks one question at a time).
- [x] Integration with CRM (HubSpot, Pipedrive) via AI-mapped fields.
- [x] Version history for forms (revert to previous version).
