# Solid Social Media — Project Intelligence

> **Read this file at the start of every Claude Code session.**
> It captures architecture, role permissions, upgrade guidelines, and project state.

---

## Quick Reference

- **Stack:** Custom PHP MVC, MySQL, vanilla JS, no Composer
- **Entry point:** `public/index.php` — front controller, all requests route through here
- **Config:** `config/env.php` — DB, API keys, environment toggle
- **Detailed context:** `PROJECT-CONTEXT.md` — integration details, posting flow, cron setup

---

## Role-Based Access Control (RBAC)

Three user roles control what each person can see and do:

| Feature | Admin | Editor | Reviewer |
|---------|:-----:|:------:|:--------:|
| Dashboard | Full | Full | Read-only |
| Generator (Plan & Generate) | Yes | Yes | No |
| Posts (create/edit/delete) | Yes | Yes | No |
| Posts (view) | Yes | Yes | Yes |
| Posts (publish/schedule) | Yes | Yes | No |
| Posts (approve/reject) | Yes | No | Yes |
| Calendar | Full | Full | View only |
| Reports | Full | Full | View only |
| Generate Report wizard | Yes | Yes | No |
| Report lock/unlock (share) | Yes | Yes | No |
| Report Settings (cost savings math) | Yes | No | No |
| Content Strategy | Yes | No | No |
| Art Direction | Yes | No | No |
| Branding / Wizard / Dark Logo | Yes | No | No |
| SMTP Settings | Yes | No | No |
| User Management | Yes | No | No |
| Reviews Queue | Yes | No | Yes |
| Activity Log | Yes | No | No |
| Memory | Yes | Yes | No |
| Docs | Yes | Yes | Yes |

**How it works:**
- `core/Controller.php` has `requireRole(...$roles)` — call after `requireAuth()` in every controller action
- `app/views/layouts/main.php` wraps nav items with `<?php if (in_array($role, [...])): ?>`
- The role is stored in `$_SESSION['role']` on login

---

## Upgrade Guidelines

When adding new features to this project, follow this checklist:

### 1. Role Check
- Determine which roles should access the new feature (see matrix above)
- Add `$this->requireRole('admin', 'editor')` (or appropriate roles) to the controller
- Add nav item with role conditional in `layouts/main.php`
- Update the RBAC matrix in this file

### 2. Multi-Tenant
- Every database table MUST have a `client_id` column
- Always filter queries by `$GLOBALS['client_id']`
- Never hardcode company names, colors, or URLs — pull from `BrandingService`

### 3. Onboarding Tour
- If the feature has a visible nav item, add a tour step for the appropriate role(s)
- Tour steps are defined in `app/views/components/tour.php`
- Admin tour gets the most steps, Reviewer the fewest

### 4. Documentation
- Update `PROJECT-CONTEXT.md` with new tables, services, routes
- Update `app/views/documentation/index.php` user-facing docs if the feature has a UI
- Update this file's RBAC matrix

### 5. Art Direction / Branding
- If the feature generates images, it MUST use `ArtDirectionService::buildImagePromptModifiers()` 
- If the feature displays brand colors, use CSS variables (`var(--primary)`, etc.)
- Watermark settings come from `art_direction_settings` table

### 6. Content Memory
- If the feature generates AI content, integrate with `ContentMemoryService` to prevent duplicates
- Pass `memoryContext` to AI prompts

### 7. Approval Workflow
- If the feature creates publishable content, check `ApprovalService::isApprovalRequired()`
- If required, route through `pending_review` status before publish

---

## Architecture Overview

```
public/index.php          → Front controller (loads all models/services, runs router)
config/env.php            → Constants: DB, API keys, URLs
config/routes.php         → All GET/POST route definitions
core/                     → Router, Controller (base), Model (base), Database (PDO singleton)

app/controllers/          → One per feature area
app/models/               → One per DB table (extend Model base)
app/services/             → Business logic (AI, branding, email, approval, etc.)
app/views/                → PHP templates, organized by feature
  layouts/main.php        → Master layout with sidebar, topbar, role-filtered nav
  components/tour.php     → Onboarding tour engine
  emails/                 → HTML email templates (invitation, etc.)

cron/run_scheduled_posts.php → Publishes due posts (runs every N minutes)
database/migrations/      → SQL migration files (run manually)
```

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | Auth, roles (admin/editor/reviewer), temp password, tour state, last_login_at |
| `posts` | All social media posts with status lifecycle |
| `social_post_logs` | Log of every posting attempt per platform |
| `branding_settings` | Per-client brand identity (logo, **dark_logo_url**, colors, favicon, etc.) |
| `art_direction_settings` | Per-client image generation style controls |
| `content_themes` | Reusable content themes with copy instructions |
| `theme_samples` | Example posts linked to themes |
| `theme_schedule` | Day-of-week → theme mapping |
| `content_memory` | Topic/angle deduplication hashes |
| `image_jobs` | Async Kie.ai image generation job queue |
| `smtp_settings` | Email provider config (SMTP/SendGrid/Mailgun/Emailit) |
| `approval_settings` | Per-client approval workflow toggle + min approvals |
| `post_reviews` | Individual approval/rejection records per post |
| `activity_logs` | **Phase 1** — admin audit trail of every user action |
| `report_settings` | **Phase 2** — per-client cost savings math (minutes/post, hourly rate) |
| `generated_reports` | **Phase 2/3** — saved reports with JSON snapshot + share token |

---

## Key Services

| Service | Purpose |
|---------|---------|
| `AIService` | Text generation (OpenRouter) via `chat($system, $user)`, image generation (Kie.ai), watermarking |
| `BrandingService` | Read/write branding settings (including `dark_logo_url`), provide brand context to AI |
| `ArtDirectionService` | Image style controls, prompt modifiers, presets, watermark config |
| `ContentStrategyService` | Theme CRUD, schedule, AI copy critique |
| `ContentMemoryService` | Deduplication tracking |
| `ZernioService` | Post to Facebook/LinkedIn via Zernio API |
| `WizardService` | Onboarding: website scan, theme suggestions, bulk save |
| `EmailService` | Send branded emails via SMTP/SendGrid/Mailgun/Emailit |
| `UserManagementService` | Create/invite/update/deactivate users |
| `ApprovalService` | Post review workflow |
| `ActivityLogService` | **Phase 1** — `logLogin/logLogout/logPostAction/logUserAction/logSettingsChange/logSystemAction`. Every write wrapped in try/catch so logging failures never break primary actions. |
| `ReportSettingsService` | **Phase 2** — cost savings math, `get($cid)` / `save($cid, $data)` / `calculate($postCount, $settings)` |
| `ReportGeneratorService` | **Phase 2** — `build($cid, $start, $end, $title)` assembles metrics + AI summary + tips, `generateEmailIntro($data, $name)` for personalized report emails |

---

## Post Status Lifecycle

```
draft → pending_review (if approval required) → draft (after approved) → scheduled → published
                                                                                   → failed
```

- `draft` — Created/edited, not yet scheduled
- `pending_review` — Submitted for approval (when approval workflow is enabled)
- `scheduled` — Has future date, cron will publish
- `published` — Successfully posted to platform(s)
- `failed` — All platform attempts failed

---

## Roadmap

### v2.0 — Complete ✅
- [x] Art Direction page (image style controls)
- [x] Content Strategy page (themes, schedule, AI critique)
- [x] Setup Wizard (AI website scan, theme suggestions)
- [x] Generator rework (multi-step Plan & Generate flow)
- [x] AIService integration (themes + art direction in prompts)
- [x] Watermark controls (enable/disable, position, opacity)
- [x] Favicon in branding
- [x] Branding save bug fix
- [x] User management (create, invite, roles)
- [x] RBAC (role-based nav and access)
- [x] SMTP settings (multi-provider: SMTP/SendGrid/Mailgun/Emailit)
- [x] Branded HTML email invitations
- [x] Temp password + forced change flow
- [x] Post approval workflow (configurable)
- [x] Onboarding tour (role-specific, spiral guide)
- [x] Wizard brand reveal animation

### v2.1 — Phase 1: Activity Log ✅ (shipped)
- [x] `activity_logs` table + indexed queries
- [x] `ActivityLogService` with try/catch safety on every write
- [x] Admin-only `/settings/activity-log` page with filters (user / action / date range / free-text search)
- [x] Session duration summary (approximate active time per user, clamped at 4h)
- [x] Hooks across Auth, Post CRUD, Review, Settings, User Management, cron publish

### v2.2 — Phase 2: Cost Savings + PDF Reports ✅ (shipped)
- [x] `report_settings` table + editable cost savings math (admin only)
- [x] Cost Savings card on `/reporting` with info-lightbox explaining the math
- [x] Generate Report wizard (date range, title, view/email delivery)
- [x] `ReportGeneratorService` with AI executive summary + helpful tips via OpenRouter (fallback on AI failure)
- [x] Branded HTML report at `/reports/view/{id}` with `@page` print CSS for browser Save-as-PDF
- [x] AI-personalized email delivery via `report_ready.php` template
- [x] Dark logo field on Branding page for light-background report pages

### v2.3 — Phase 3: Shared Reports + Charts ✅ (shipped)
- [x] `generated_reports` table with `share_token` for public sharing
- [x] Saved Reports library on `/reporting` + scrolling lightbox modal
- [x] Lock/Unlock toggle on report view (zoom-in/out confirmation lightbox)
- [x] Public `/shared/{token}` route via `SharedReportController` (no auth)
- [x] 4 Chart.js charts on public shared page (platform doughnut, topic bar, timeline line, status pie)
- [x] 2 QuickChart.io PNG charts embedded in Save-as-PDF output
- [x] Open Graph + Twitter Card meta tags for LinkedIn/Facebook share previews
- [x] Rate-limited public view counter (one count per session per 5 min)

### v2.x — UI polish shipped alongside Phases 1–3
- [x] Unified dark topbar with brand gradient (all pages)
- [x] Global `thead` brand gradient across all tables
- [x] Row hover image-thumbnail tooltip (Posts, Reports, Dashboard, Activity Log)
- [x] Row-click navigation on all tables
- [x] Alternating zebra rows with brand tint
- [x] Muted brand-color platform pills (facebook/linkedin) — replaces old corporate blues
- [x] Mobile stat grid always 2×2 (never 1-col)
- [x] Unified form-field theme via CSS custom properties
- [x] Custom dropdown widget replacing native `<select>` for brand-color hover/selection
- [x] Sidebar logout user-card particle effect on hover
- [x] Logout confirmation modal with zoom in/out animation + thin-stroke door SVG + constellation particles
- [x] Kanban as default Posts view with collapsible Scheduled/Drafts + Published buckets
- [x] Brand-gradient kanban column headers with white Facebook/LinkedIn icons
- [x] Emoji stripping on post titles for clean display (server-side + JS fallback)
- [x] Magical wand canvas particle system on generator empty state
- [x] Calendar month header brand gradient + glassy buttons
- [x] Animated atom-icon component for empty states
- [x] Saved Reports button + lightbox at top of Reports page

### Future (v3.0) — Centralized Multi-Tenant
- Fork repo for centralized version
- Master admin account overseeing all sub-accounts
- Per-client API key management (shared or BYOK)
- Client onboarding and billing
- 50-100 MSP support
- Optional server-side PDF rendering via mPDF for true file-attached emails (deferred from Phase 2)

---

---

## Post Generation Rules

Every AI-generated post MUST include:
- Opening hook with relevant emoji
- Line breaks between paragraphs (`\n\n`)
- Emojis at key bullet points
- Clear call-to-action on its own line
- Company contact info: phone (📞) and website (🌐) — pulled from `branding_settings`
- Two blank lines before hashtags
- Hashtags space-separated on the last line

The generator blocks content creation if Company Name, Phone, or Website are missing from branding. Users are redirected to the Branding page to complete their profile.

AI Critique behavior:
- If post content is <30% changed from the AI-generated original → show "Post is optimized" (don't critique our own output)
- If ≥30% changed → run full AI critique with strengths, suggestions, revised version
- The original content is stored in a hidden field for comparison

---

*Last updated: April 15, 2026 — Phases 1, 2, 3 all shipped and live in production.*
