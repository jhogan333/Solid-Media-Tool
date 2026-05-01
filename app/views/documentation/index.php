<?php
$firstName = $_SESSION['first_name'] ?? '';
$brand = (new BrandingService())->get($GLOBALS['client_id']);
$companyName = htmlspecialchars($brand['company_name'] ?? 'Your Company');
$nameGreet = $firstName ? htmlspecialchars($firstName) : 'there';
$userRole = $_SESSION['role'] ?? 'reviewer';
?>

<style>
/* ---- Documentation Layout ---- */
.docs-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 32px;
    align-items: start;
}
@media (max-width: 1024px) {
    .docs-layout { grid-template-columns: 1fr; }
    .docs-toc-card { display: none; }
}

/* ---- Table of Contents ---- */
.docs-toc-card {
    position: sticky;
    top: 96px;
}
.docs-toc { list-style: none; padding: 0; margin: 0; }
.docs-toc li { margin-bottom: 2px; }
.docs-toc a {
    display: block;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
    border-radius: 6px;
    border-left: 2px solid transparent;
    transition: all 0.15s ease;
    text-decoration: none;
}
.docs-toc a:hover {
    color: var(--primary);
    background: rgba(var(--primary-rgb), 0.05);
}
.docs-toc a.active {
    color: var(--primary);
    background: rgba(var(--primary-rgb), 0.08);
    border-left-color: var(--primary);
    font-weight: 600;
}
.docs-toc .toc-section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    padding: 16px 14px 6px;
    display: block;
}
.docs-toc .toc-section-label:first-child { padding-top: 0; }

/* ---- Content ---- */
.docs-content section { scroll-margin-top: 96px; }
.docs-section {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 32px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
}
.docs-section h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.docs-section h2 .doc-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    background: rgba(var(--primary-rgb), 0.1);
    color: var(--primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}
.docs-section .section-lead {
    font-size: 14px;
    color: var(--text-muted);
    margin-bottom: 20px;
    line-height: 1.5;
}
.docs-section h3 {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    margin: 20px 0 8px;
}
.docs-section p, .docs-section li {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.7;
}
.docs-section ul, .docs-section ol {
    padding-left: 20px;
    margin-bottom: 14px;
}
.docs-section li { margin-bottom: 6px; }
.docs-section strong { color: var(--text); }

/* Field reference table */
.field-table {
    width: 100%;
    border-collapse: collapse;
    margin: 12px 0 20px;
    font-size: 13px;
}
.field-table th {
    text-align: left;
    padding: 10px 14px;
    background: var(--bg-input);
    color: var(--text);
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-bottom: 1px solid var(--border);
}
.field-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-light);
    color: var(--text-secondary);
    vertical-align: top;
}
.field-table tr:last-child td { border-bottom: none; }
.field-table code {
    background: var(--bg-input);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 12px;
    font-family: 'SF Mono', 'Fira Code', monospace;
    color: var(--primary);
}

/* Tip callout */
.doc-tip {
    background: rgba(var(--primary-rgb), 0.05);
    border-left: 3px solid var(--primary);
    border-radius: 0 8px 8px 0;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.6;
}
.doc-tip strong { color: var(--primary); }
.doc-warn {
    background: rgba(239,68,68,0.05);
    border-left: 3px solid var(--danger);
    border-radius: 0 8px 8px 0;
    padding: 14px 18px;
    margin: 16px 0;
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.6;
}
.doc-warn strong { color: var(--danger); }

/* Workflow diagram */
.workflow-diagram {
    width: 100%;
    overflow-x: auto;
    padding: 20px 0;
}
.workflow-diagram svg {
    display: block;
    margin: 0 auto;
    max-width: 100%;
    height: auto;
}

/* Hero banner */
.docs-hero {
    background: linear-gradient(165deg, var(--primary) 0%, color-mix(in srgb, var(--primary) 40%, #1a1a2e) 60%, #1a1a2e 100%);
    border-radius: var(--radius-lg);
    padding: 40px 36px;
    margin-bottom: 28px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.docs-hero h1 { font-size: 26px; font-weight: 800; margin-bottom: 6px; }
.docs-hero .hero-sub { font-size: 15px; color: rgba(255,255,255,0.6); line-height: 1.6; max-width: 600px; }
.docs-hero .hero-version {
    position: absolute;
    top: 20px; right: 24px;
    background: rgba(255,255,255,0.12);
    padding: 4px 12px;
    border-radius: 100px;
    font-size: 12px; font-weight: 600;
    color: rgba(255,255,255,0.7);
}

kbd {
    background: var(--bg-input);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 12px;
    font-family: inherit;
    font-weight: 600;
    color: var(--text);
}

/* Doc illustration */
.doc-illustration {
    width: 100%;
    max-height: 240px;
    object-fit: cover;
    border-radius: var(--radius-md);
    margin: 16px 0;
    border: 1px solid var(--border);
}

/* Role badge in docs */
.doc-role {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 100px;
    font-size: 11px;
    font-weight: 600;
    margin-right: 4px;
}
.doc-role-admin { background: rgba(var(--primary-rgb),0.1); color: var(--primary); }
.doc-role-editor { background: rgba(59,130,246,0.1); color: #3b82f6; }
.doc-role-reviewer { background: rgba(34,197,94,0.1); color: #22c55e; }
</style>

<!-- Hero -->
<div class="docs-hero">
    <span class="hero-version">v2.0</span>
    <h1><?= $companyName ?> Social — Documentation</h1>
    <p class="hero-sub">Welcome, <?= $nameGreet ?>. Everything you need to know about creating, scheduling, and managing social media content for <?= $companyName ?>.</p>
</div>

<div class="docs-layout">
    <!-- Table of Contents -->
    <div class="docs-toc-card card" style="padding:20px">
        <ul class="docs-toc" id="docsToc">
            <li><span class="toc-section-label">Getting Started</span></li>
            <li><a href="#introduction">Introduction</a></li>
            <li><a href="#roles">Roles &amp; Permissions</a></li>
            <li><a href="#workflow">How It Works</a></li>
            <li><a href="#wizard">Setup Wizard</a></li>

            <li><span class="toc-section-label">Content</span></li>
            <li><a href="#dashboard">Dashboard</a></li>
            <li><a href="#generator">Content Generator</a></li>
            <li><a href="#posts">Posts Manager</a></li>
            <li><a href="#editor">Post Editor</a></li>
            <li><a href="#calendar">Calendar</a></li>
            <li><a href="#reviews">Reviews &amp; Approvals</a></li>

            <li><span class="toc-section-label">Reports</span></li>
            <li><a href="#reporting">Reports Overview</a></li>
            <li><a href="#cost-savings">Cost Savings</a></li>
            <li><a href="#generate-report">Generate Report</a></li>
            <li><a href="#saved-reports">Saved Reports</a></li>
            <li><a href="#share-reports">Public Sharing</a></li>

            <li><span class="toc-section-label">Strategy &amp; Design</span></li>
            <li><a href="#strategy">Content Strategy</a></li>
            <li><a href="#art-direction">Art Direction</a></li>
            <li><a href="#memory">Content Memory</a></li>

            <li><span class="toc-section-label">Administration</span></li>
            <li><a href="#branding">Branding &amp; Dark Logo</a></li>
            <li><a href="#users">User Management</a></li>
            <li><a href="#smtp">Email Settings</a></li>
            <li><a href="#activity-log">Activity Log</a></li>

            <li><span class="toc-section-label">Reference</span></li>
            <li><a href="#statuses">Post Statuses</a></li>
            <li><a href="#platforms">Platforms</a></li>
            <li><a href="#shortcuts">Keyboard Shortcuts</a></li>
            <li><a href="#dark-mode">Dark Mode</a></li>
        </ul>
    </div>

    <!-- Documentation Content -->
    <div class="docs-content">

        <!-- Introduction -->
        <section id="introduction" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-rocket"></i></span> Introduction</h2>
            <p class="section-lead">A quick overview of what this platform does and who it's built for.</p>
            <p><?= $companyName ?> Social is an AI-powered social media content management system. It streamlines the entire content lifecycle: from AI-driven generation and brand-consistent image creation, through team review and approval, to scheduled cross-platform publishing.</p>
            <h3>Key Capabilities</h3>
            <ul>
                <li><strong>AI Content Generation</strong> — Create a full week of on-brand posts or individual posts with a single click. The AI uses your content themes and brand voice.</li>
                <li><strong>AI Image Generation</strong> — Generate photorealistic images styled to your art direction settings, automatically watermarked with your logo.</li>
                <li><strong>Content Strategy</strong> — Define recurring content themes with copy instructions. Assign themes to days of the week for consistent scheduling.</li>
                <li><strong>Approval Workflow</strong> — Optionally require team review before posts can be published. Configurable approval thresholds.</li>
                <li><strong>Multi-Platform Publishing</strong> — Publish to Facebook and LinkedIn simultaneously via Zernio integration.</li>
                <li><strong>Content Memory</strong> — Automatic deduplication ensures your content never repeats the same topic or angle.</li>
                <li><strong>Team Management</strong> — Invite editors and reviewers with role-based access controls.</li>
                <li><strong>Full Branding</strong> — Customize colors, logo, login background, and favicon to match your company identity.</li>
            </ul>
        </section>

        <!-- Roles & Permissions -->
        <section id="roles" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-user-shield"></i></span> Roles &amp; Permissions</h2>
            <p class="section-lead">Three roles control what each team member can see and do.</p>

            <table class="field-table">
                <thead><tr><th>Feature</th><th><span class="doc-role doc-role-admin">Admin</span></th><th><span class="doc-role doc-role-editor">Editor</span></th><th><span class="doc-role doc-role-reviewer">Reviewer</span></th></tr></thead>
                <tbody>
                    <tr><td><strong>Dashboard</strong></td><td>Full access</td><td>Full access</td><td>View only</td></tr>
                    <tr><td><strong>Generator</strong></td><td>Yes</td><td>Yes</td><td>No</td></tr>
                    <tr><td><strong>Posts</strong> (create/edit)</td><td>Yes</td><td>Yes</td><td>No</td></tr>
                    <tr><td><strong>Posts</strong> (approve/reject)</td><td>Yes</td><td>No</td><td>Yes</td></tr>
                    <tr><td><strong>Calendar</strong></td><td>Full</td><td>Full</td><td>View only</td></tr>
                    <tr><td><strong>Reports</strong></td><td>Full</td><td>Full</td><td>View only</td></tr>
                    <tr><td><strong>Generate Report</strong></td><td>Yes</td><td>Yes</td><td>No</td></tr>
                    <tr><td><strong>Report Settings</strong> (cost math)</td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>Share / Unshare reports</strong></td><td>Yes</td><td>Yes</td><td>No</td></tr>
                    <tr><td><strong>Content Strategy</strong></td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>Art Direction</strong></td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>Branding / Wizard / Dark Logo</strong></td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>User Management</strong></td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>SMTP Settings</strong></td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>Reviews Queue</strong></td><td>Yes</td><td>No</td><td>Yes</td></tr>
                    <tr><td><strong>Activity Log</strong></td><td>Yes</td><td>No</td><td>No</td></tr>
                    <tr><td><strong>Memory</strong></td><td>Yes</td><td>Yes</td><td>No</td></tr>
                </tbody>
            </table>
        </section>

        <!-- Workflow -->
        <section id="workflow" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-project-diagram"></i></span> How It Works</h2>
            <p class="section-lead">The high-level content workflow from idea to published post.</p>

            <div class="workflow-diagram">
                <svg viewBox="0 0 900 280" xmlns="http://www.w3.org/2000/svg" fill="none">
                    <rect x="10" y="80" width="140" height="120" rx="14" fill="rgba(var(--primary-rgb),0.08)" stroke="var(--primary)" stroke-width="1.5"/>
                    <text x="80" y="125" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" font-weight="700" fill="var(--text)">Generate</text>
                    <text x="80" y="145" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">AI creates posts</text>
                    <text x="80" y="160" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">for <?= $companyName ?></text>
                    <circle cx="80" cy="60" r="18" fill="var(--primary)"/>
                    <text x="80" y="65" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" font-weight="700" fill="#fff">1</text>

                    <line x1="155" y1="140" x2="195" y2="140" stroke="var(--primary)" stroke-width="2" stroke-dasharray="6,3"/>
                    <polygon points="195,135 205,140 195,145" fill="var(--primary)"/>

                    <rect x="210" y="80" width="140" height="120" rx="14" fill="rgba(var(--primary-rgb),0.08)" stroke="var(--primary)" stroke-width="1.5"/>
                    <text x="280" y="125" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" font-weight="700" fill="var(--text)">Review &amp; Edit</text>
                    <text x="280" y="145" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">Refine copy, swap</text>
                    <text x="280" y="160" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">images, AI critique</text>
                    <circle cx="280" cy="60" r="18" fill="var(--primary)"/>
                    <text x="280" y="65" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" font-weight="700" fill="#fff">2</text>

                    <line x1="355" y1="140" x2="395" y2="140" stroke="var(--primary)" stroke-width="2" stroke-dasharray="6,3"/>
                    <polygon points="395,135 405,140 395,145" fill="var(--primary)"/>

                    <rect x="410" y="80" width="140" height="120" rx="14" fill="rgba(var(--primary-rgb),0.08)" stroke="var(--primary)" stroke-width="1.5"/>
                    <text x="480" y="120" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" font-weight="700" fill="var(--text)">Approve</text>
                    <text x="480" y="140" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">Team reviews</text>
                    <text x="480" y="155" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">(if workflow</text>
                    <text x="480" y="170" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">enabled)</text>
                    <circle cx="480" cy="60" r="18" fill="var(--primary)"/>
                    <text x="480" y="65" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" font-weight="700" fill="#fff">3</text>

                    <line x1="555" y1="140" x2="595" y2="140" stroke="var(--primary)" stroke-width="2" stroke-dasharray="6,3"/>
                    <polygon points="595,135 605,140 595,145" fill="var(--primary)"/>

                    <rect x="610" y="80" width="140" height="120" rx="14" fill="rgba(var(--primary-rgb),0.08)" stroke="var(--primary)" stroke-width="1.5"/>
                    <text x="680" y="125" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" font-weight="700" fill="var(--text)">Schedule</text>
                    <text x="680" y="145" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">Set date &amp; time</text>
                    <text x="680" y="160" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">or publish now</text>
                    <circle cx="680" cy="60" r="18" fill="var(--primary)"/>
                    <text x="680" y="65" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" font-weight="700" fill="#fff">4</text>

                    <line x1="755" y1="140" x2="795" y2="140" stroke="var(--primary)" stroke-width="2" stroke-dasharray="6,3"/>
                    <polygon points="795,135 805,140 795,145" fill="var(--primary)"/>

                    <rect x="810" y="80" width="80" height="120" rx="14" fill="rgba(var(--primary-rgb),0.08)" stroke="var(--primary)" stroke-width="1.5"/>
                    <text x="850" y="130" text-anchor="middle" font-family="Inter,sans-serif" font-size="13" font-weight="700" fill="var(--text)">Track</text>
                    <text x="850" y="150" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" fill="var(--text-muted)">Reports</text>
                    <circle cx="850" cy="60" r="18" fill="var(--primary)"/>
                    <text x="850" y="65" text-anchor="middle" font-family="Inter,sans-serif" font-size="12" font-weight="700" fill="#fff">5</text>

                    <path d="M 680 205 L 680 250 Q 680 260 670 260 L 90 260 Q 80 260 80 250 L 80 205" stroke="var(--text-muted)" stroke-width="1.5" stroke-dasharray="5,4" fill="none"/>
                    <polygon points="75,208 80,198 85,208" fill="var(--text-muted)"/>
                    <text x="380" y="255" text-anchor="middle" font-family="Inter,sans-serif" font-size="11" font-weight="600" fill="var(--text-muted)">Content Memory feeds back to keep content fresh</text>
                </svg>
            </div>

            <p>Each time content is generated, the <strong>Content Memory</strong> engine logs the topic, keywords, and angle used. This feedback loop ensures that future posts cover new ground and avoid repetition.</p>

            <?php if (file_exists(APP_ROOT . '/public/uploads/docs/docs-workflow.jpg')): ?>
            <img src="<?= BASE_URL ?>/uploads/docs/docs-workflow.jpg" alt="Content workflow illustration" class="doc-illustration">
            <?php endif; ?>
        </section>

        <!-- Setup Wizard -->
        <section id="wizard" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-hat-wizard"></i></span> Setup Wizard</h2>
            <p class="section-lead">Get up and running in minutes with guided onboarding.</p>
            <p>The Setup Wizard walks you through five steps to configure your account:</p>
            <ol>
                <li><strong>Company Basics</strong> — Enter your company name, website URL, phone number, and a brief description of your business.</li>
                <li><strong>Website Scan</strong> — The AI scans your website to automatically extract company details, services, and brand information.</li>
                <li><strong>Brand Identity</strong> — Upload your logo, set your brand colors (primary and secondary), and configure your visual identity.</li>
                <li><strong>Content Themes</strong> — The AI suggests content themes tailored to your business. Select the ones you want to use.</li>
                <li><strong>Review &amp; Complete</strong> — Review all your settings and apply them with one click.</li>
            </ol>
            <div class="doc-tip"><strong>Tip:</strong> The wizard can be re-run at any time from the Settings menu. Your existing values will be shown as defaults.</div>
        </section>

        <!-- Dashboard -->
        <section id="dashboard" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-th-large"></i></span> Dashboard</h2>
            <p class="section-lead">Your home base — a snapshot of <?= $companyName ?>'s content at a glance.</p>
            <p>The Dashboard is the first screen you see after logging in. It provides a real-time summary of your content pipeline and quick links to the most common tasks.</p>

            <h3>Stat Cards</h3>
            <p>Five cards at the top display your key metrics. Each shows a weekly trend on hover:</p>
            <table class="field-table">
                <thead><tr><th>Card</th><th>What It Shows</th></tr></thead>
                <tbody>
                    <tr><td><strong>Total Posts</strong></td><td>The total number of posts created, regardless of status.</td></tr>
                    <tr><td><strong>Scheduled</strong></td><td>Posts with a future date waiting to be published.</td></tr>
                    <tr><td><strong>Published</strong></td><td>Posts successfully published to a social platform.</td></tr>
                    <tr><td><strong>Drafts</strong></td><td>Posts saved as drafts still needing review or scheduling.</td></tr>
                    <tr><td><strong>Failed</strong></td><td>Posts where publishing failed. Click to jump to the Reports page for details.</td></tr>
                </tbody>
            </table>

            <h3>Quick Actions</h3>
            <p>Three shortcut cards let you jump directly to: the Content Generator, the Calendar, and Reports.</p>

            <h3>Recent Posts</h3>
            <p>A table of the most recently created posts. Click the edit icon on any row to open that post in the editor.</p>

            <div class="doc-tip"><strong>Tip:</strong> If you've just started and have no posts yet, the Dashboard shows a prompt to generate your first post.</div>
        </section>

        <!-- Content Generator -->
        <section id="generator" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-magic"></i></span> Content Generator</h2>
            <p class="section-lead">Create on-brand content using AI — one post or a full week at a time.</p>

            <h3>Plan &amp; Generate (Full Week)</h3>
            <p>The left panel lets you generate a full week of content. The AI uses your content themes and weekly schedule to create varied posts for each day.</p>
            <ul>
                <li>Posts are generated as <strong>drafts</strong> — nothing is published automatically.</li>
                <li>Each post gets a unique topic, angle, and type based on your theme schedule.</li>
                <li>The Content Memory prevents any topic or angle from being repeated.</li>
                <li>Results appear as editable cards with inline title and content editing.</li>
            </ul>

            <h3>Single Post Generation</h3>
            <p>For more control, specify a topic and post type to generate exactly what you need.</p>

            <h3>Working with Results</h3>
            <p>Each generated post card offers these actions:</p>
            <ul>
                <li><strong>Edit title &amp; content</strong> — Click directly to modify inline.</li>
                <li><strong>Generate Image</strong> — Create an AI image styled to your Art Direction settings, automatically watermarked.</li>
                <li><strong>Save as Draft</strong> — Saves the post to your library for scheduling.</li>
            </ul>

            <div class="doc-warn"><strong>Required:</strong> Company Name, Phone, and Website must be set in Branding before the generator will work. You'll be prompted to complete your profile if any are missing.</div>

            <?php if (file_exists(APP_ROOT . '/public/uploads/docs/docs-ai-gen.jpg')): ?>
            <img src="<?= BASE_URL ?>/uploads/docs/docs-ai-gen.jpg" alt="AI content generation illustration" class="doc-illustration">
            <?php endif; ?>
        </section>

        <!-- Posts Manager -->
        <section id="posts" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-edit"></i></span> Posts Manager</h2>
            <p class="section-lead">Browse, filter, and manage all posts in one place.</p>

            <h3>Two Views (Kanban is default)</h3>
            <p>Toggle between <strong>Kanban View</strong> (the default — cards organized by platform and status) and <strong>Table View</strong> (classic list) using the buttons at the top right.</p>

            <h3>Kanban Buckets</h3>
            <p>Each platform column (Facebook, LinkedIn) splits into two sub-sections you can click to collapse/expand:</p>
            <ul>
                <li><strong>Scheduled &amp; Drafts</strong> (blue accent) — upcoming scheduled posts sorted soonest-first, plus drafts and in-progress posts. Drafts have a dashed orange border with an "Incomplete" badge so you can see at a glance they still need scheduling. Failed posts appear here too with a red accent and "Tap to retry" hint.</li>
                <li><strong>Published</strong> (green accent) — successfully posted content, sorted newest-first.</li>
            </ul>
            <p>Your collapsed/expanded state persists across reloads via browser storage, so you can hide the Published section if you only want to focus on the pipeline.</p>

            <h3>Smart Date Labels</h3>
            <p>Every card shows both an absolute date and a relative label ("In 3 days", "Tomorrow", "2 days ago", "In 2w"). The relative label is color-coded per status so the timeline is scannable at a glance.</p>

            <h3>Clean Titles</h3>
            <p>Emojis are automatically stripped from post titles in the display (both kanban cards and the table view) so the UI stays calm and readable. The actual emojis in your post content are preserved and still go out to Facebook/LinkedIn — only the display titles are cleaned.</p>

            <h3>Hover Tooltips</h3>
            <p>Hover any card (or any row in Table View) to see a floating tooltip with the post's thumbnail image, title, topic, type, and status. The tooltip appears after a 90ms grace period so flicking past rows doesn't spam tooltips.</p>

            <h3>Filtering</h3>
            <table class="field-table">
                <thead><tr><th>Filter</th><th>What It Does</th></tr></thead>
                <tbody>
                    <tr><td><strong>Platform</strong></td><td>Show posts for a specific social network.</td></tr>
                    <tr><td><strong>Status</strong></td><td>Filter by Draft, Pending Review, Scheduled, Published, or Failed.</td></tr>
                    <tr><td><strong>Search</strong></td><td>Real-time search by post title.</td></tr>
                </tbody>
            </table>

            <h3>Actions</h3>
            <ul>
                <li><strong>New Post</strong> — Opens the Generator.</li>
                <li><strong>Edit</strong> — Click any row or card to open that post in the editor.</li>
                <li><strong>Delete</strong> — Removes the post after confirmation.</li>
            </ul>
        </section>

        <!-- Post Editor -->
        <section id="editor" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-pen-fancy"></i></span> Post Editor</h2>
            <p class="section-lead">Fine-tune every detail of a post before it goes live.</p>

            <h3>Content Panel (Left)</h3>
            <table class="field-table">
                <thead><tr><th>Field</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Image Preview</strong></td><td>Shows the current post image. Click to regenerate.</td></tr>
                    <tr><td><strong>Title</strong></td><td>The headline. Editable directly.</td></tr>
                    <tr><td><strong>Content</strong></td><td>The full post body/caption. Supports multi-line editing.</td></tr>
                    <tr><td><strong>First Comment</strong></td><td>Optional first comment posted alongside the main content (e.g., for LinkedIn hashtags).</td></tr>
                </tbody>
            </table>

            <h3>Settings Panel (Right)</h3>
            <table class="field-table">
                <thead><tr><th>Field</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Platforms</strong></td><td>Select one or more social networks (Facebook, LinkedIn).</td></tr>
                    <tr><td><strong>Post Type</strong></td><td>Content category: Educational, Promotional, Engagement, Storytelling, Behind the Scenes.</td></tr>
                    <tr><td><strong>Schedule Date/Time</strong></td><td>When the post should go live. Date-time picker.</td></tr>
                    <tr><td><strong>Topic / Keywords / Angle</strong></td><td>Metadata tracked by Content Memory for deduplication.</td></tr>
                </tbody>
            </table>

            <h3>AI Tools</h3>
            <ul>
                <li><strong>Regenerate Text</strong> — Rewrite the copy with a custom instruction while keeping the topic.</li>
                <li><strong>Regenerate Image</strong> — Generate a new AI image from a custom prompt.</li>
                <li><strong>AI Critique</strong> — Get AI-powered feedback on your post content with strengths, suggestions, and a revised version. Only triggers when you've changed 30% or more of the AI-generated original.</li>
            </ul>

            <h3>Publishing Actions</h3>
            <ul>
                <li><strong>Save</strong> — Saves all changes as a draft.</li>
                <li><strong>Schedule</strong> — Sets the post for future automatic publishing.</li>
                <li><strong>Post Now</strong> — Publishes immediately to selected platforms.</li>
                <li><strong>Submit for Review</strong> — Sends the post to the review queue (when approval workflow is enabled).</li>
            </ul>

            <h3>Publishing Logs</h3>
            <p>After a post is published (or fails), a log section appears showing each platform attempt with its timestamp, status, and any error messages.</p>
        </section>

        <!-- Calendar -->
        <section id="calendar" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-calendar-alt"></i></span> Calendar</h2>
            <p class="section-lead">A visual overview of your content schedule.</p>

            <p>The Calendar displays a month-at-a-glance view of all posts, plotted on their scheduled dates.</p>
            <ul>
                <li><strong>Navigate months</strong> — Use the arrows or the "Today" button.</li>
                <li><strong>Color-coded dots</strong> — Each post appears as a dot. Colors indicate status:
                    <ul>
                        <li><span style="color:var(--text-muted)">Grey</span> = Draft</li>
                        <li><span style="color:var(--info)">Blue</span> = Scheduled</li>
                        <li><span style="color:var(--success)">Green</span> = Published</li>
                        <li><span style="color:var(--danger)">Red</span> = Failed</li>
                    </ul>
                </li>
                <li><strong>Hover</strong> — Shows a tooltip with the post thumbnail, title, time, and platform.</li>
                <li><strong>Click</strong> — Opens a modal with full post details and a link to the editor.</li>
            </ul>
            <div class="doc-tip"><strong>Tip:</strong> Spot gaps in your schedule? Head to the Generator to fill them in.</div>

            <?php if (file_exists(APP_ROOT . '/public/uploads/docs/docs-calendar.jpg')): ?>
            <img src="<?= BASE_URL ?>/uploads/docs/docs-calendar.jpg" alt="Calendar planning illustration" class="doc-illustration">
            <?php endif; ?>
        </section>

        <!-- Reporting -->
        <section id="reporting" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-chart-bar"></i></span> Reports Overview</h2>
            <p class="section-lead">Track content performance, see your cost savings, and generate branded reports for clients.</p>

            <h3>Interactive Stat Cards</h3>
            <p>The stat cards at the top are <strong>clickable filters</strong>. Click any card to automatically filter the posts table to that status and scroll down to the results.</p>

            <h3>Failed Posts</h3>
            <p>If any posts have failed to publish, a dedicated red-themed section appears at the top showing the post title, platform, error message, and date. You can retry or delete failed posts directly.</p>

            <h3>Filters</h3>
            <p>Use the filter bar to narrow results by date range, platform, status, or post type. Click <strong>Apply</strong> to filter, or <strong>Download CSV</strong> to export all data.</p>

            <h3>Analytics</h3>
            <ul>
                <li><strong>Topic Distribution</strong> — Visual tag cloud showing which topics have been covered and how many times.</li>
                <li><strong>Platform Breakdown</strong> — Horizontal bar chart showing post distribution across platforms, tinted in your brand color.</li>
            </ul>

            <?php if (file_exists(APP_ROOT . '/public/uploads/docs/docs-analytics.jpg')): ?>
            <img src="<?= BASE_URL ?>/uploads/docs/docs-analytics.jpg" alt="Analytics dashboard illustration" class="doc-illustration">
            <?php endif; ?>
        </section>

        <!-- Cost Savings -->
        <section id="cost-savings" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-piggy-bank"></i></span> Cost Savings</h2>
            <p class="section-lead">The Reports page shows how much labor your automation replaces in real dollars.</p>

            <p>Near the top of the Reports page you'll see the <strong>Estimated Savings</strong> card, showing a dollar figure and an hours-saved breakdown. This number is calculated automatically from three inputs:</p>
            <ul>
                <li><strong>Post count</strong> — the total of published + scheduled posts</li>
                <li><strong>Minutes per post</strong> — time a human social media manager would typically spend producing one polished branded post (research, copy, image sourcing/branding, upload to each platform). Default is 30 minutes.</li>
                <li><strong>Hourly rate</strong> — average social media manager wage for your market. Default is <strong>$29/hour</strong> (roughly the US national average).</li>
            </ul>
            <p>Click the <strong>info icon</strong> next to the savings value to open a lightbox that shows the complete math breakdown.</p>

            <h3>Tuning the Math (Admin only)</h3>
            <p>Admins can click the <strong>Edit math</strong> link to open the Report Settings lightbox and adjust the minutes-per-post and hourly rate to match their local market and workflow. Changes apply immediately to the Reports page and flow through to all future generated reports.</p>
            <div class="doc-tip"><strong>Tip:</strong> The savings number only counts posts that are published or scheduled — drafts don't count, because drafts don't actually save anyone any time.</div>
        </section>

        <!-- Generate Report -->
        <section id="generate-report" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-file-lines"></i></span> Generate Report</h2>
            <p class="section-lead">Build a branded performance report for any date range — perfect for client deliverables.</p>

            <p>Click the <strong>Generate Report</strong> button in the Cost Savings card to open the report wizard. You'll be asked four things:</p>
            <ol>
                <li><strong>Date range</strong> — Last 7 days, Last 30 days, This month, Last month, or a custom range.</li>
                <li><strong>Report title</strong> — Optional. Leave blank to auto-generate something like "SolidTech Social Report — Apr 2026".</li>
                <li><strong>Delivery</strong> — Either <em>View &amp; save as PDF</em> (opens the report in a branded page you can print-to-PDF from your browser) or <em>Email to me or others</em> (sends a branded email with a "View Report" link).</li>
                <li><strong>Confirm</strong> — Click Generate. The report takes 5–15 seconds to build because an AI call is happening in the background to produce the executive summary and helpful tips.</li>
            </ol>

            <h3>What's In a Report</h3>
            <ul>
                <li><strong>Cover page</strong> with your logo, report title, date range, and total posts.</li>
                <li><strong>Key metrics</strong> — total posts, published, scheduled, failure rate.</li>
                <li><strong>Cost savings callout</strong> — big dollar number with the full math breakdown.</li>
                <li><strong>Chart visuals</strong> — posts by platform and top topics (rendered as high-quality PNG charts that print perfectly).</li>
                <li><strong>Post cards grid</strong> — a branded card for every post in the range (thumbnail, platform pills, title, content, topic, date).</li>
                <li><strong>AI executive summary</strong> — 2–3 paragraphs of plain-English analysis tailored to the actual numbers.</li>
                <li><strong>Helpful tips</strong> — 5–8 practical tips for getting the most out of the tool, tailored to your data.</li>
                <li><strong>Footer</strong> on every page with your dark-variant logo, report title, and date range.</li>
            </ul>

            <h3>Saving as a PDF</h3>
            <p>On the report view page, click the <strong>Save as PDF</strong> button in the top toolbar. This opens your browser's print dialog. Choose "Save as PDF" as the destination and click Save. The output is a polished multi-page PDF with proper page breaks, suitable for attaching to emails or sharing with clients.</p>

            <div class="doc-tip"><strong>Tip:</strong> The report is generated once and stored permanently. Re-opening a report later shows exactly the same numbers as the day it was generated — perfect for historical comparisons.</div>
        </section>

        <!-- Saved Reports -->
        <section id="saved-reports" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-folder-open"></i></span> Saved Reports</h2>
            <p class="section-lead">Every generated report is saved automatically. Access them from two places.</p>

            <p>The <strong>Saved Reports</strong> button sits next to the Generate Report button near the top of the Reports page. The count badge on the button shows how many reports exist. Clicking it opens a lightbox with a scrolling list.</p>
            <ul>
                <li>Each row shows an <strong>eye icon</strong>, report title, date range, creation timestamp, view count, and a Public/Private badge.</li>
                <li>Click any row to open that report.</li>
                <li>A full table of the same reports also appears at the bottom of the Reports page with delete and share actions.</li>
            </ul>
            <div class="doc-tip"><strong>Tip:</strong> If you generate many reports, the library keeps the 50 most recent in the lightbox.</div>
        </section>

        <!-- Public Sharing -->
        <section id="share-reports" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-share-nodes"></i></span> Public Sharing</h2>
            <p class="section-lead">Reports are private by default. You can make any report publicly shareable with a single click.</p>

            <h3>Making a Report Public</h3>
            <p>When viewing a saved report, look for the <strong>lock button</strong> in the toolbar at the top (it sits between the title and the Save as PDF button). Clicking it opens a confirmation lightbox that zooms in with a smooth animation.</p>
            <ul>
                <li>If the report is currently <strong>private</strong>, you'll see a red lock icon and a <strong>Make public</strong> button. Clicking it mints a random 32-character share token and gives you a public URL.</li>
                <li>If the report is currently <strong>public</strong>, you'll see a green unlock icon and the shareable URL with a Copy button. A <strong>Make private</strong> button lets you revoke access at any time.</li>
            </ul>

            <h3>What Public Means</h3>
            <p>A public report lives at a URL like <code>https://your-domain.com/shared/abc123...</code>. Anyone with that URL can view the full report <strong>without signing in</strong>. The moment you click "Make private", the old URL stops working immediately.</p>

            <h3>Public View Differences</h3>
            <p>The public page renders the same report data but with a few extras:</p>
            <ul>
                <li><strong>4 interactive Chart.js charts</strong> — posts by platform (doughnut), top topics (bar), posting timeline (line), and status breakdown (pie).</li>
                <li><strong>LinkedIn/Facebook share preview cards</strong> — when you paste the URL into social media, the preview card shows your logo, report title, and an AI-generated highlight.</li>
                <li><strong>No sidebar or navigation</strong> — it's a standalone branded page designed for non-technical recipients.</li>
                <li><strong>A prominent "Save as PDF" button</strong> at the top so recipients can download a copy.</li>
            </ul>

            <h3>Security</h3>
            <p>Share tokens are 32-character random hex strings (128 bits of entropy), making them functionally impossible to guess. Even if someone tried every token at 1 million attempts per second, it would take longer than the age of the universe to find a valid one. Revoking a token is instant — the server returns 404 the moment the token is cleared.</p>
            <div class="doc-tip"><strong>Tip:</strong> The view counter on shared reports is rate-limited to one count per browser session every 5 minutes, so bots and refreshes can't inflate it.</div>
        </section>

        <!-- Reviews & Approvals -->
        <section id="reviews" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-clipboard-check"></i></span> Reviews &amp; Approvals</h2>
            <p class="section-lead">Team-based content review before publishing.</p>
            <p>When the approval workflow is enabled (configured in User Management), posts must be reviewed before they can be scheduled or published.</p>

            <h3>How It Works</h3>
            <ol>
                <li>An editor creates a post and clicks <strong>Submit for Review</strong>.</li>
                <li>The post enters <code>pending_review</code> status and appears in the Reviews Queue.</li>
                <li>Reviewers (and admins) can <strong>Approve</strong> or <strong>Request Changes</strong> with feedback comments.</li>
                <li>Once the required number of approvals is reached, the post moves back to <code>draft</code> status, ready for scheduling.</li>
                <li>If changes are requested, the editor revises the post and resubmits.</li>
            </ol>

            <h3>Review Queue</h3>
            <p>Each pending post is displayed as a card showing:</p>
            <ul>
                <li>Post thumbnail and content preview</li>
                <li>Approval progress bar (e.g., "1 of 2 approvals")</li>
                <li>Review chips showing who approved or requested changes</li>
                <li>Action buttons: Approve, Request Changes, View Post</li>
            </ul>

            <?php if (file_exists(APP_ROOT . '/public/uploads/docs/docs-team.jpg')): ?>
            <img src="<?= BASE_URL ?>/uploads/docs/docs-team.jpg" alt="Team collaboration illustration" class="doc-illustration">
            <?php endif; ?>

            <h3>Configuration</h3>
            <p>Go to <strong>Users &rarr; Approval Workflow</strong> to:</p>
            <ul>
                <li>Enable or disable the approval requirement</li>
                <li>Set the minimum number of approvals needed (1-5)</li>
            </ul>
        </section>

        <!-- Content Strategy -->
        <section id="strategy" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-chess"></i></span> Content Strategy</h2>
            <p class="section-lead">Define content themes and weekly schedules to guide AI generation.</p>

            <h3>Content Themes</h3>
            <p>Themes are reusable content categories that instruct the AI on what to write about and how. Each theme includes:</p>
            <table class="field-table">
                <thead><tr><th>Field</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Name</strong></td><td>Theme title (e.g., "Cybersecurity Tips", "Cloud & Digital Transformation").</td></tr>
                    <tr><td><strong>Description</strong></td><td>What this theme covers and its intended audience.</td></tr>
                    <tr><td><strong>Copy Instructions</strong></td><td>Detailed writing guidelines the AI follows when generating posts for this theme.</td></tr>
                    <tr><td><strong>Required Elements</strong></td><td>Checkboxes for: Website, CTA, Hashtags, Emojis. These are enforced in generated content.</td></tr>
                    <tr><td><strong>Default Hashtags</strong></td><td>Hashtags automatically appended to posts using this theme.</td></tr>
                    <tr><td><strong>Sample Posts</strong></td><td>Example posts the AI uses as style references.</td></tr>
                </tbody>
            </table>

            <h3>Weekly Schedule</h3>
            <p>Assign a theme to each day of the week. When the Generator creates a full week of content, it uses this schedule to decide which theme applies to each day.</p>

            <h3>AI Copy Critique</h3>
            <p>Click the <strong>Analyze</strong> button on any theme's sample post to get AI-powered feedback. The critique includes strengths, suggestions, and a rewritten version.</p>
        </section>

        <!-- Art Direction -->
        <section id="art-direction" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-camera"></i></span> Art Direction</h2>
            <p class="section-lead">Control how AI-generated images look and feel.</p>

            <h3>Image Style Settings</h3>
            <table class="field-table">
                <thead><tr><th>Setting</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Default Style</strong></td><td>Photorealistic, Mixed (Photo + Graphics), or Technical Diagram.</td></tr>
                    <tr><td><strong>Realism Level</strong></td><td>Slider from Stylized (1) to Hyper-Real (10).</td></tr>
                    <tr><td><strong>Color Temperature</strong></td><td>Cold (blue undertones), Neutral, or Warm (golden tones).</td></tr>
                    <tr><td><strong>Contrast</strong></td><td>Subtle, Balanced, Punchy, or Maximum.</td></tr>
                    <tr><td><strong>Mood</strong></td><td>Professional, Dramatic, Moody Dark, or Clean Bright.</td></tr>
                    <tr><td><strong>Brand Color Bleed</strong></td><td>How much your brand color influences the image palette (0-100%).</td></tr>
                    <tr><td><strong>Avoid List</strong></td><td>Comma-separated styles to never use (e.g., "cartoon, childish, flat").</td></tr>
                </tbody>
            </table>

            <h3>Quick Presets</h3>
            <p>Four one-click presets: <strong>Corporate IT</strong>, <strong>Tech Magazine</strong>, <strong>Dark &amp; Dramatic</strong>, <strong>Clean Professional</strong>. Each loads optimized values for all settings.</p>

            <h3>Watermark &amp; Overlay</h3>
            <p>When enabled, all generated images are automatically watermarked with:</p>
            <ul>
                <li>Your company logo (positioned bottom-left or bottom-right)</li>
                <li>Your website URL</li>
                <li>A gradient overlay for text legibility (adjustable opacity)</li>
            </ul>

            <h3>Prompt Preview</h3>
            <p>A live preview shows the exact prompt that will be sent to the AI image generator, updated in real-time as you change settings.</p>
        </section>

        <!-- Content Memory -->
        <section id="memory" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-brain"></i></span> Content Memory</h2>
            <p class="section-lead">The engine that keeps your content fresh and varied.</p>

            <p>Every time content is generated or saved, the Content Memory logs three data points:</p>
            <table class="field-table">
                <thead><tr><th>Data Point</th><th>Purpose</th></tr></thead>
                <tbody>
                    <tr><td><strong>Topic</strong></td><td>The subject matter. Prevents overuse of the same topic.</td></tr>
                    <tr><td><strong>Keywords</strong></td><td>Specific terms used. Helps diversify vocabulary.</td></tr>
                    <tr><td><strong>Angle</strong></td><td>The creative approach (e.g., "myth-busting", "customer testimonial"). Ensures varied perspectives.</td></tr>
                </tbody>
            </table>

            <h3>Memory Dashboard</h3>
            <ul>
                <li><strong>Stat Cards</strong> — Total memories, unique topics, and total angles used.</li>
                <li><strong>Topic Pills</strong> — Visual list of every topic with usage counts.</li>
                <li><strong>Recent Angles</strong> — The latest creative approaches used.</li>
                <li><strong>Memory Log</strong> — A table showing every entry with its linked post.</li>
            </ul>

            <div class="doc-tip"><strong>How it works:</strong> When the AI generates new content, it checks memory first. If a topic or angle was recently used, it avoids it and chooses a fresh direction. This happens automatically.</div>
        </section>

        <!-- Branding -->
        <section id="branding" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-palette"></i></span> Branding</h2>
            <p class="section-lead">Customize the platform to match your company identity.</p>

            <p>Everything set here applies globally: sidebar colors, buttons, login screen, stat cards, and more.</p>

            <h3>Brand Identity</h3>
            <table class="field-table">
                <thead><tr><th>Setting</th><th>What It Controls</th></tr></thead>
                <tbody>
                    <tr><td><strong>Company Name</strong></td><td>Browser tab, login screen, greetings, and generated content.</td></tr>
                    <tr><td><strong>Phone / Website</strong></td><td>Included in every AI-generated post for contact information.</td></tr>
                    <tr><td><strong>Primary Color</strong></td><td>Main brand color. Drives the sidebar, buttons, icons, and accent elements.</td></tr>
                    <tr><td><strong>Secondary Color</strong></td><td>Complementary color for secondary accents.</td></tr>
                    <tr><td><strong>Particles Effect</strong></td><td>Toggle the animated constellation on the login screen.</td></tr>
                </tbody>
            </table>

            <h3>Uploads</h3>
            <ul>
                <li><strong>Logo</strong> — Appears in the sidebar and login screen. Formats: JPG, PNG, GIF, WebP, SVG (max 2 MB). This is your <em>primary</em> logo (usually a light/white variant for dark backgrounds).</li>
                <li><strong>Dark Logo</strong> — <span style="color:var(--primary);font-weight:600">New</span> A dark variant of your logo for use on light backgrounds (generated reports, PDFs, shared report pages). Optional — if you don't upload one, the system will automatically darken your primary logo via a CSS filter, which works but isn't as crisp as a purpose-built dark version. Upload if possible.</li>
                <li><strong>Favicon</strong> — Browser tab icon. Formats: PNG, ICO (max 1 MB).</li>
                <li><strong>Login Background</strong> — Replaces the gradient on the login page. Recommended: 1920x1080+.</li>
            </ul>

            <h3>API Keys</h3>
            <p>Configure your API credentials for:</p>
            <ul>
                <li><strong>OpenRouter</strong> — Powers AI text generation and content critique.</li>
                <li><strong>Kie.ai</strong> — Powers AI image generation.</li>
                <li><strong>Zernio</strong> — Social media publishing to Facebook and LinkedIn.</li>
            </ul>
            <p>Use the <strong>Test Connection</strong> button to verify each API key is working.</p>

            <h3>Live Preview</h3>
            <p>The preview card shows how your login screen will look with current settings. Colors and company name update in real time.</p>

            <?php if (file_exists(APP_ROOT . '/public/uploads/docs/docs-branding.jpg')): ?>
            <img src="<?= BASE_URL ?>/uploads/docs/docs-branding.jpg" alt="Brand identity illustration" class="doc-illustration">
            <?php endif; ?>
        </section>

        <!-- User Management -->
        <section id="users" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-users-cog"></i></span> User Management</h2>
            <p class="section-lead">Manage your team and control who can do what.</p>

            <h3>Inviting Users</h3>
            <ol>
                <li>Click <strong>Invite User</strong> and enter their email, name, and role.</li>
                <li>If SMTP is configured, a branded invitation email is sent with login credentials.</li>
                <li>If SMTP is not configured, a temporary password is displayed for you to share manually.</li>
                <li>On first login, the invited user is prompted to change their password.</li>
            </ol>

            <h3>Managing Users</h3>
            <ul>
                <li><strong>Edit</strong> — Change a user's name, email, or role.</li>
                <li><strong>Deactivate</strong> — Disables the account (prevents login). Can be reactivated later.</li>
                <li><strong>Resend Invite</strong> — Generates a new temporary password and resends the invitation email.</li>
            </ul>

            <h3>Approval Workflow</h3>
            <p>At the bottom of the page, toggle the approval workflow on or off and set the minimum number of approvals required (1-5) before a post can be published.</p>
        </section>

        <!-- SMTP -->
        <section id="smtp" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-envelope"></i></span> Email Settings</h2>
            <p class="section-lead">Configure email delivery for user invitations and report notifications.</p>

            <p>Choose from four email providers:</p>
            <table class="field-table">
                <thead><tr><th>Provider</th><th>Configuration</th></tr></thead>
                <tbody>
                    <tr><td><strong>SMTP</strong></td><td>Host, port, username, password, encryption (TLS/SSL/None), from name/email.</td></tr>
                    <tr><td><strong>SendGrid</strong></td><td>API key, from name/email.</td></tr>
                    <tr><td><strong>Mailgun</strong></td><td>API key, domain, from name/email.</td></tr>
                    <tr><td><strong>Emailit</strong></td><td>API key, from name/email.</td></tr>
                </tbody>
            </table>

            <p>Use <strong>Test Connection</strong> to verify your configuration before saving. A test email will be sent to confirm delivery.</p>
            <div class="doc-tip"><strong>Tip:</strong> The same configuration powers both user invitation emails and the "Report ready" emails sent from the Generate Report wizard.</div>
        </section>

        <!-- Activity Log -->
        <section id="activity-log" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-history"></i></span> Activity Log</h2>
            <p class="section-lead">Admin-only audit trail of everything that happens in the system.</p>

            <p>The Activity Log (Settings &rarr; Activity Log, admin-only) records every meaningful user action. Use it to answer questions like "who deleted that post?", "when did someone last log in?", or "which admin changed that user's role?"</p>

            <h3>What Gets Logged</h3>
            <ul>
                <li><strong>Authentication</strong> — successful logins, failed logins (with reason), logouts, self-service password resets, password changes</li>
                <li><strong>Posts</strong> — created, updated, deleted, scheduled, posted-now, retried, approved, changes-requested</li>
                <li><strong>Publishing</strong> — auto-publish success or failure from the cron job (actor shows as "System (cron)")</li>
                <li><strong>Users</strong> — created, updated, deactivated/activated, deleted/restored, permanently deleted, <strong>role changes</strong> (with old and new role captured), admin-initiated password resets, invite sends</li>
                <li><strong>Settings</strong> — branding, art direction, SMTP, report settings saves (with fields-changed metadata)</li>
                <li><strong>Reports</strong> — generated, deleted, shared, unshared</li>
            </ul>

            <h3>Filters</h3>
            <p>The filter bar at the top of the Activity Log page lets you narrow the view by:</p>
            <ul>
                <li><strong>User</strong> — show only events for a specific team member</li>
                <li><strong>Action</strong> — filter to a specific event type (login_success, post_deleted, role_changed, etc.)</li>
                <li><strong>Date range</strong> — from/to date inputs</li>
                <li><strong>Free-text search</strong> — searches the description + user name (wildcards are auto-escaped so you can safely search for text containing <code>%</code> or <code>_</code>)</li>
            </ul>
            <p>Every filter state is bookmarkable — the page uses GET parameters so you can share a specific log query URL with another admin.</p>

            <h3>User Activity Summary</h3>
            <p>At the top of the Activity Log page, cards show each team member's <strong>approximate active time</strong> over the last 30 days, calculated from their login events through their last observed activity. Session durations are clamped at 4 hours each to prevent idle-browser inflation. The card also shows login count and role badge.</p>

            <h3>Event Details</h3>
            <p>Each event row has an info button that opens a lightbox with the full metadata JSON (e.g. old vs new role for role changes, platform list for published posts, reason for failed logins) plus the event's IP address and user agent for deeper forensics.</p>

            <div class="doc-tip"><strong>Tip:</strong> Logging failures never break the main flow. If the activity_logs table were somehow unavailable, the app would continue working normally — the log entry would just be silently dropped with a PHP error_log note.</div>
        </section>

        <!-- Post Statuses -->
        <section id="statuses" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-tags"></i></span> Post Statuses</h2>
            <p class="section-lead">Understanding the post lifecycle.</p>
            <table class="field-table">
                <thead><tr><th>Status</th><th>Color</th><th>Meaning</th></tr></thead>
                <tbody>
                    <tr><td><strong>Draft</strong></td><td><span style="color:var(--text-muted)">Grey</span></td><td>Saved but not scheduled. Still being worked on.</td></tr>
                    <tr><td><strong>Pending Review</strong></td><td><span style="color:#f59e0b">Orange</span></td><td>Submitted for team approval. Cannot be scheduled until approved.</td></tr>
                    <tr><td><strong>Scheduled</strong></td><td><span style="color:var(--info)">Blue</span></td><td>Has a date and time. Will be published automatically by the cron job.</td></tr>
                    <tr><td><strong>Published</strong></td><td><span style="color:var(--success)">Green</span></td><td>Successfully posted to the target platform(s).</td></tr>
                    <tr><td><strong>Failed</strong></td><td><span style="color:var(--danger)">Red</span></td><td>Publishing failed. Check the error in Reports and retry.</td></tr>
                </tbody>
            </table>

            <h3>Status Flow</h3>
            <p><code>Draft</code> &rarr; <code>Pending Review</code> (if approval enabled) &rarr; <code>Draft</code> (after approved) &rarr; <code>Scheduled</code> &rarr; <code>Published</code> or <code>Failed</code></p>
        </section>

        <!-- Platforms -->
        <section id="platforms" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-share-alt"></i></span> Platforms</h2>
            <p class="section-lead">Supported social networks.</p>
            <table class="field-table">
                <thead><tr><th>Platform</th><th>Notes</th></tr></thead>
                <tbody>
                    <tr><td><strong>Facebook</strong></td><td>Supports longer captions, images, and link previews. Connected via Zernio.</td></tr>
                    <tr><td><strong>LinkedIn</strong></td><td>Professional tone. Ideal for educational and thought-leadership content. Connected via Zernio.</td></tr>
                </tbody>
            </table>
            <p>Posts can target one or both platforms simultaneously. Each platform is published independently, so a post can succeed on one and fail on another.</p>
        </section>

        <!-- Keyboard Shortcuts -->
        <section id="shortcuts" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-keyboard"></i></span> Keyboard Shortcuts</h2>
            <p class="section-lead">Navigate faster with keyboard shortcuts.</p>
            <p>Press <kbd>?</kbd> anywhere in the app to open the shortcuts modal.</p>
            <table class="field-table">
                <thead><tr><th>Shortcut</th><th>Action</th></tr></thead>
                <tbody>
                    <tr><td><kbd>N</kbd></td><td>New post (opens Generator)</td></tr>
                    <tr><td><kbd>G</kbd></td><td>Go to Generator</td></tr>
                    <tr><td><kbd>P</kbd></td><td>Go to Posts</td></tr>
                    <tr><td><kbd>C</kbd></td><td>Go to Calendar</td></tr>
                    <tr><td><kbd>R</kbd></td><td>Go to Reports</td></tr>
                    <tr><td><kbd>/</kbd></td><td>Focus search (on Posts page)</td></tr>
                    <tr><td><kbd>Esc</kbd></td><td>Close any open modal</td></tr>
                </tbody>
            </table>
        </section>

        <!-- Dark Mode -->
        <section id="dark-mode" class="docs-section">
            <h2><span class="doc-icon"><i class="fas fa-moon"></i></span> Dark Mode</h2>
            <p class="section-lead">Easy on the eyes, any time of day.</p>
            <p>Click the moon/sun icon in the top-right corner to toggle between light and dark mode. Your preference is saved automatically and persists across sessions.</p>
            <p>All brand colors are maintained in both modes — the sidebar, stat cards, and accent elements reflect <?= $companyName ?>'s identity regardless of theme.</p>
        </section>

    </div>
</div>

<!-- TOC Active Tracking -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tocLinks = document.querySelectorAll('.docs-toc a');
    var sections = [];

    tocLinks.forEach(function(link) {
        var id = link.getAttribute('href').replace('#', '');
        var section = document.getElementById(id);
        if (section) sections.push({ id: id, el: section, link: link });
    });

    function updateActive() {
        var current = sections[0];
        var offset = 120;
        for (var i = 0; i < sections.length; i++) {
            if (sections[i].el.getBoundingClientRect().top <= offset) {
                current = sections[i];
            }
        }
        tocLinks.forEach(function(l) { l.classList.remove('active'); });
        if (current) current.link.classList.add('active');
    }

    tocLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('href').replace('#', '');
            var target = document.getElementById(id);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                history.replaceState(null, '', '#' + id);
            }
        });
    });

    window.addEventListener('scroll', updateActive, { passive: true });
    updateActive();
});
</script>
