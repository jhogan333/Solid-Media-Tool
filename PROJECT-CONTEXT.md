# SolidTech Social Media Manager — Project Context

> **Read this file at the start of every new Claude session.**
> Also read `CLAUDE.md` for role permissions, upgrade guidelines, and roadmap.

---

## Environments

| | DEV (XAMPP) | PROD (SiteGround) |
|---|---|---|
| **Root path** | `C:\xampp\htdocs\Solid-SocialMedia\` | `/home/<user>/public_html/social-media/` |
| **Base URL** | `http://localhost/Solid-SocialMedia/public` | `https://social.solidtech.com` |
| **DB name** | `solidtech_social` | `solidtech_social_prod` |
| **DB user** | `root` (no password) | `solidtech_prod_user` |
| **Switch env** | Set `APP_ENV` to `'local'` in `config/env.php` | Set `APP_ENV` to `'production'` in `config/env.php` |

---

## Architecture Overview

- **Framework:** Custom PHP MVC (no Composer, no external framework)
- **Core:** `core/` — Router, Controller base (with RBAC), Model base, Database (PDO/MySQL)
- **Config:** `config/env.php` — all constants (DB, API keys, env toggle)
- **Public entry:** `public/index.php` — front controller, loads all models/services, runs router
- **Cron jobs:** `cron/run_scheduled_posts.php` — scheduled post publisher

### Controllers
PostController, GeneratorController, CalendarController, BrandingController, ArtDirectionController, ContentStrategyController, WizardController, UserController, SmtpController, ReviewController, AuthController, DashboardController, ReportingController, MemoryController, DocumentationController

### Models
User, Post, BrandingSetting, ContentMemory, ArtDirectionSetting, ContentTheme, ThemeSample, ThemeSchedule, SmtpSetting, ApprovalSetting, PostReview

### Services
AIService, ZernioService, BrandingService, ContentMemoryService, ArtDirectionService, ContentStrategyService, WizardService, EmailService, UserManagementService, ApprovalService, ModalService

---

## Key Integrations

### Zernio (Social Media Posting API)
- **Service:** `app/services/ZernioService.php`
- Posts to Facebook and LinkedIn via API
- `postNow()` used by "Post Now" button and cron job
- Image handling: localhost images uploaded to temp host for dev

### OpenRouter (AI Content Generation)
- **Service:** `app/services/AIService.php` — `generateWeekContent()`, `generateSinglePost()`, `regenerateText()`
- Theme-aware: accepts theme data (name, instructions, samples, required elements) per post
- Memory-aware: excludes recent topics and angles via ContentMemoryService

### Kie.ai (AI Image Generation)
- **Service:** `app/services/AIService.php` — `generateImage()`
- Art direction modifiers appended to every image prompt via ArtDirectionService
- Watermarking: logo + website + gradient overlay (configurable position, opacity, enable/disable)

---

## User Roles & Access (RBAC)

Three roles: **admin**, **editor**, **reviewer**

| Feature | Admin | Editor | Reviewer |
|---------|:-----:|:------:|:--------:|
| Dashboard | Full | Full | Read-only |
| Generator | Yes | Yes | No |
| Posts (create/edit) | Yes | Yes | No |
| Posts (view) | Yes | Yes | Yes |
| Posts (approve/reject) | Yes | No | Yes |
| Calendar | Full | Full | View only |
| Reports | Full | Full | No |
| Content Strategy | Yes | No | No |
| Art Direction | Yes | No | No |
| Branding / Wizard | Yes | No | No |
| User Management | Yes | No | No |
| SMTP Settings | Yes | No | No |
| Reviews Queue | Yes | No | Yes |
| Memory | Yes | Yes | No |
| Docs | Yes | Yes | Yes |

RBAC enforced by `Controller::requireRole()` and nav filtering in `layouts/main.php`.

---

## Content Strategy System

- **Themes** (`content_themes`): Named categories with copy instructions, required elements (phone/website/CTA/hashtags/emojis), default hashtags, image style override
- **Theme Samples** (`theme_samples`): 1-3 example posts per theme for AI to mimic
- **Schedule** (`theme_schedule`): Day-of-week → theme mapping
- **AI Critique**: Analyzes post copy and returns strengths, suggestions, revised version

## Art Direction System

- **Settings** (`art_direction_settings`): Image style, realism level, color temperature, contrast, mood, brand color bleed, illustration limit, avoid list
- **Watermark controls**: Enable/disable, website text override, logo position, gradient opacity
- **Presets**: Corporate IT, Tech Magazine, Dark & Dramatic, Clean Professional
- **Prompt modifiers**: Assembled by `ArtDirectionService::buildImagePromptModifiers()` and appended to every image generation prompt

## Approval Workflow

- **Settings** (`approval_settings`): Toggle approval required, min approvals count (1-5)
- **Flow**: Editor creates post → submits for review (`pending_review`) → Reviewers approve/request changes → When min approvals met → post moves back to `draft` for scheduling
- **Optional**: Admin can enable/disable via User Management page

---

## Post Status Lifecycle

```
draft → pending_review (if approval required) → draft (after approved) → scheduled → published
                                                                                   → failed
```

---

## User Invitation Flow

1. Admin creates user (email, name, role) on User Management page
2. System generates random 12-char temp password
3. If SMTP configured: sends branded HTML email with login URL + temp password
4. If SMTP not configured: shows temp password to admin for manual sharing
5. User logs in → forced password change lightbox (non-dismissable)
6. After password change → onboarding tour starts (role-specific)
7. Tour uses spiral favicon as guide avatar, brand-colored Next button

---

## Setup Wizard

- 5 steps: Company Basics → Website Scan → Brand Identity → Theme Suggestions → Review
- AI scans website to extract services, about text, contact info, keywords
- AI suggests tailored content themes
- Brand reveal animation on completion (typewriter text, orbiting ring, particles, 5-7 sec)
- Re-runnable from Branding page

---

## Scheduling & Posting Flow

1. User creates/edits post, selects platform(s), sets future date/time
2. If approval required: "Submit for Review" → reviewers approve → back to draft
3. Clicks "Schedule" → status = `scheduled`
4. Cron job runs every minute → queries due posts → calls ZernioService → updates status
5. Logs to `social_post_logs` table

---

## Database Tables

| Table | Purpose |
|-------|---------|
| `users` | Auth, roles (admin/editor/reviewer), temp password, tour state, client_id, last_login_at |
| `posts` | Social media posts with full status lifecycle |
| `social_post_logs` | Log of every posting attempt per platform (platform, account_id, zernio_post_id, status, response, error_message) |
| `branding_settings` | Per-client brand identity (logo_url, **dark_logo_url** for light report pages, colors, favicon, login_bg) |
| `art_direction_settings` | Per-client image generation style controls (realism level, subject exclusions, watermark config) |
| `content_themes` | Reusable content themes with copy instructions |
| `theme_samples` | Example posts linked to themes |
| `theme_schedule` | Day-of-week → theme mapping |
| `content_memory` | Topic/angle deduplication hashes |
| `image_jobs` | Async Kie.ai image generation job queue (polled by the generator) |
| `smtp_settings` | Email provider config (SMTP/SendGrid/Mailgun/Emailit) |
| `approval_settings` | Per-client approval workflow toggle + min approvals |
| `post_reviews` | Individual approval/rejection records per post |
| `activity_logs` | **Phase 1** — audit trail: client_id, user_id, user_name snapshot, user_role snapshot, action, entity_type, entity_id, description, metadata JSON, ip_address, user_agent, created_at. Indexed on (client_id, created_at), (user_id, created_at), action, (entity_type, entity_id). |
| `report_settings` | **Phase 2** — per-client cost savings math: minutes_per_post, hourly_rate, currency_symbol. Editable in admin Report Settings lightbox. |
| `generated_reports` | **Phase 2 + 3** — saved reports: title, date_range_start, date_range_end, report_data JSON snapshot, share_token (32-hex or NULL), shared_at, view_count, created_at. Indexed on (client_id, created_at) and share_token. |

---

## File Structure

```
Solid-SocialMedia/
  CLAUDE.md                — Project intelligence for AI sessions (READ THIS)
  PROJECT-CONTEXT.md       — This file
  config/
    env.php                — Environment config, API keys, DB credentials
    routes.php             — All GET/POST route definitions
  core/
    Router.php             — URL routing with parameterized paths
    Controller.php         — Base class: view(), json(), requireAuth(), requireRole()
    Model.php              — Base class: find(), create(), update(), delete()
    Database.php           — PDO singleton
  app/
    controllers/           — One per feature area (19 controllers after Phases 1–3)
    models/                — One per DB table (14 models after Phases 1–3)
    services/              — Business logic (14 services after Phases 1–3)
    views/
      layouts/main.php     — Master layout: role-filtered nav, password lightbox, tour,
                             logout confirmation modal, custom dropdown enhancer
      auth/login.php       — Login page (glassmorphism, particles)
      dashboard/           — Dashboard overview with row-hover image tooltips
      generator/           — Content generator with Plan & Generate lightbox
                             + magical wand canvas particle empty state
      editor/              — Post editor with AI critique, post kanban with
                             collapsible Scheduled/Drafts + Published buckets
      calendar/            — Calendar view with brand-gradient month header
      reporting/           — Reports, Cost Savings card, Generate Report wizard,
                             Saved Reports lightbox, share/unshare flow
      reports/pdf/view.php — Standalone branded report page with @page print CSS,
                             lock/unlock toggle, QuickChart.io PNG charts
      shared/report.php    — Public shared report (no auth) with 4 Chart.js charts + OG tags
      shared/not_found.php — 404 page for invalid/revoked share tokens
      branding/            — Brand settings + wizard button + dark logo upload
      art-direction/       — Art direction controls
      content-strategy/    — Theme management + weekly schedule
      wizard/              — Setup wizard with brand reveal animation
      users/               — User management + approval settings
      smtp/                — Email provider configuration
      reviews/             — Post review queue for approvers
      activity-log/        — Phase 1 admin audit trail with filters + session summary
      emails/              — HTML email templates (invitation, report_ready)
      components/tour.php  — Onboarding tour engine (3 role-specific tours)
      memory/              — Content memory viewer
      documentation/       — User-facing docs
  cron/
    run_scheduled_posts.php — Publishes due posts, hooks activity log on publish/fail
  database/
    migrations/            — 001 initial, 002 art direction + themes,
                             003 users + SMTP + reviews, 004 activity_logs,
                             005 report_settings + generated_reports + dark_logo_url
  public/
    index.php              — Front controller (autoloads all models + services)
    css/app.css            — Main stylesheet (~2200 lines)
    js/app.js              — Modal, toast, theme toggle, sidebar constellation canvas
    uploads/               — Uploaded files (logos, generated images, dark logos)
    favicon.ico            — Browser favicon (from spiral.png)
    favicon-*.png          — Favicon sizes (16, 32, 48)
    apple-touch-icon.png   — iOS icon (180x180)
  img/
    spiral.png             — SolidTech spiral logo source
  storage/
    cron.log               — Cron job execution log
```

---

## Routes (as of Phase 3)

**Public (no auth):**
- `GET  /login`, `POST /login`, `POST /login-ajax`, `POST /forgot-password`, `GET /logout`
- `GET  /shared/{token}` — public shared report view (32-hex token required)

**Auth required:**
- `GET  /dashboard`, `/generator`, `/posts`, `/posts/edit/{id}`, `/calendar`, `/reviews`, `/branding`, `/art-direction`, `/content-strategy`, `/users`, `/smtp`, `/memory`, `/docs`, `/reporting`
- Post CRUD: `POST /posts/save`, `/posts/update/{id}`, `/posts/delete/{id}`, `/posts/schedule/{id}`, `/posts/post-now/{id}`, `/posts/retry/{id}`
- Generator: `POST /generator/week`, `/generator/single`, `/generator/regenerate-text`, `/generator/regenerate-image`, `/generator/start-image-job`, `GET /generator/check-image-jobs`
- Reviews: `POST /reviews/approve/{id}`, `/reviews/request-changes/{id}`
- Users: `POST /users/create`, `/users/update/{id}`, `/users/deactivate/{id}`, `/users/activate/{id}`, `/users/delete/{id}`, `/users/restore/{id}`, `/users/permanent-delete/{id}`, `/users/resend-invite/{id}`
- SMTP: `POST /smtp/save`, `/smtp/test`

**Phase 1 — Activity Log (admin-only):**
- `GET  /settings/activity-log` — filtered log + session summary

**Phase 2 — Reports:**
- `POST /reports/generate` — build + save + optional email delivery
- `GET  /reports/view/{id}` — authed report view (`ReportsController::show`)
- `GET  /reports/library` — JSON list (for future AJAX uses)
- `POST /reports/settings/save` — admin cost savings math
- `POST /reports/delete/{id}` — delete a saved report

**Phase 3 — Share tokens:**
- `POST /reports/share/{id}` — mint + store share token, return public URL
- `POST /reports/unshare/{id}` — revoke share token

---

## Known Issues

1. **Calendar shows single platform** — tooltip reads `post.platform` (singular). Needs update to show all platforms from `platforms` JSON column.
2. **Instagram & X/Twitter** — Checkbox UI exists but disabled. Needs Zernio account IDs.
3. **Image URL for dev** — Zernio can't reach localhost images; `resolveImageUrl()` uploads to temp host. Not needed in production.
4. **Server-side PDF attachment** — Reports are delivered via email as HTML + "View Report" link, not as PDF attachment. Users save as PDF via browser print. A future mPDF integration could add true `.pdf` attachments.

---

*Last updated: April 15, 2026 — Phases 1, 2, 3 all complete and live in production.*
