<?php
$csrfToken = $_SESSION['csrf_token'] ?? '';
$posts = $posts ?? [];
$pendingCount = count($posts);
?>

<input type="hidden" id="csrf-token" value="<?= htmlspecialchars($csrfToken) ?>">

<style>
.review-card {
    display: flex;
    gap: 20px;
    padding: 20px;
    border-bottom: 1px solid var(--border-light);
    transition: opacity 0.3s ease;
}
.review-card:last-child { border-bottom: none; }

.review-thumb {
    width: 120px;
    height: 120px;
    border-radius: var(--radius-sm);
    object-fit: cover;
    flex-shrink: 0;
    background: var(--bg-input);
}
.review-thumb-placeholder {
    width: 120px;
    height: 120px;
    border-radius: var(--radius-sm);
    flex-shrink: 0;
    background: var(--bg-input);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    font-size: 28px;
}

.review-body { flex: 1; min-width: 0; }
.review-title { font-weight: 700; font-size: 15px; color: var(--text); margin-bottom: 4px; }
.review-content-preview {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 8px;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.review-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
.review-meta .text-small {
    font-size: 12px;
    color: var(--text-muted);
}

.approval-progress {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--text-secondary);
    margin-bottom: 12px;
}
.approval-bar {
    width: 80px;
    height: 6px;
    background: var(--bg-input);
    border-radius: 100px;
    overflow: hidden;
}
.approval-bar-fill {
    height: 100%;
    border-radius: 100px;
    background: var(--success);
    transition: width 0.3s ease;
}

.review-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}
.review-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 100px;
    font-size: 12px;
    font-weight: 500;
}
.review-chip-approved {
    background: rgba(34,197,94,0.1);
    color: var(--success);
}
.review-chip-changes {
    background: rgba(245,158,11,0.1);
    color: var(--warning);
}
.review-chip-comment {
    font-weight: 400;
    color: var(--text-muted);
    margin-left: 2px;
}

.review-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-approve {
    background: rgba(34,197,94,0.1);
    color: var(--success);
    border: 1px solid rgba(34,197,94,0.25);
}
.btn-approve:hover {
    background: var(--success);
    color: #fff;
}

.btn-changes {
    background: rgba(245,158,11,0.08);
    color: var(--warning);
    border: 1px solid rgba(245,158,11,0.2);
}
.btn-changes:hover {
    background: var(--warning);
    color: #fff;
}

.changes-form {
    display: none;
    margin-top: 12px;
    padding: 12px;
    background: var(--bg-input);
    border-radius: var(--radius-sm);
}
.changes-form.active { display: block; }
.changes-form textarea {
    width: 100%;
    min-height: 80px;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--bg-card);
    color: var(--text);
    font-family: inherit;
    font-size: 13px;
    resize: vertical;
    margin-bottom: 8px;
}
.changes-form textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb),0.1);
}
.changes-form-actions {
    display: flex;
    gap: 8px;
}
</style>

<!-- Section Header -->
<div class="section-header">
    <h3 class="section-title">
        Review Queue
        <span class="badge badge-scheduled" style="margin-left:8px;font-size:12px;vertical-align:middle" id="pending-count"><?= $pendingCount ?></span>
    </h3>
</div>

<!-- Posts List -->
<?php if (empty($posts)): ?>
    <div class="card">
        <div class="empty-state" style="padding:48px 24px">
            <span class="atom-icon atom-icon-lg" aria-hidden="true">
                <span class="atom-nucleus"></span>
                <span class="atom-orbit atom-orbit-1"><span class="atom-electron"></span></span>
                <span class="atom-orbit atom-orbit-2"><span class="atom-electron"></span></span>
                <span class="atom-orbit atom-orbit-3"><span class="atom-electron"></span></span>
            </span>
            <p style="font-size:16px;font-weight:600;margin-top:16px;color:var(--text)">All caught up!</p>
            <p style="color:var(--text-muted);font-size:13px;max-width:320px;margin:4px auto 0">No posts are waiting for review right now. When editors submit posts for approval, they will appear here.</p>
        </div>
    </div>
<?php else: ?>
    <div id="review-list">
        <?php foreach ($posts as $post):
            $rs = $post['review_status'] ?? [];
            $reviews = $rs['reviews'] ?? [];
            $approvalCount = (int)($rs['approval_count'] ?? 0);
            $minRequired = (int)($rs['min_required'] ?? 1);
            $isFullyApproved = !empty($rs['is_fully_approved']);
            $pct = $minRequired > 0 ? min(100, round(($approvalCount / $minRequired) * 100)) : 0;
            $contentPreview = mb_strlen($post['content'] ?? '') > 200
                ? mb_substr($post['content'], 0, 200) . '...'
                : ($post['content'] ?? '');
        ?>
        <div class="card mb-2" id="review-card-<?= (int)$post['id'] ?>" style="padding:0;overflow:hidden">
            <div class="review-card">
                <?php if (!empty($post['image_url'])): ?>
                    <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="" class="review-thumb">
                <?php else: ?>
                    <div class="review-thumb-placeholder">
                        <i class="fas fa-image"></i>
                    </div>
                <?php endif; ?>

                <div class="review-body">
                    <div class="review-title"><?= htmlspecialchars($post['title'] ?? 'Untitled') ?></div>
                    <div class="review-content-preview"><?= htmlspecialchars($contentPreview) ?></div>

                    <div class="review-meta">
                        <span class="badge badge-scheduled" style="text-transform:capitalize"><?= htmlspecialchars(str_replace('_', ' ', $post['post_type'] ?? 'post')) ?></span>
                        <span class="text-small"><?= date('M j, Y \a\t g:ia', strtotime($post['created_at'])) ?></span>
                    </div>

                    <div class="approval-progress">
                        <div class="approval-bar">
                            <div class="approval-bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span><?= $approvalCount ?> of <?= $minRequired ?> approval<?= $minRequired !== 1 ? 's' : '' ?></span>
                    </div>

                    <?php if (!empty($reviews)): ?>
                    <div class="review-chips">
                        <?php foreach ($reviews as $review): ?>
                            <?php if ($review['status'] === 'approved'): ?>
                                <span class="review-chip review-chip-approved">
                                    <i class="fas fa-check-circle"></i>
                                    <?= htmlspecialchars($review['first_name'] ?? $review['username'] ?? 'Reviewer') ?> approved
                                </span>
                            <?php else: ?>
                                <span class="review-chip review-chip-changes" title="<?= htmlspecialchars($review['comment'] ?? '') ?>">
                                    <i class="fas fa-edit"></i>
                                    <?= htmlspecialchars($review['first_name'] ?? $review['username'] ?? 'Reviewer') ?> requested changes
                                    <?php if (!empty($review['comment'])): ?>
                                        <span class="review-chip-comment">&mdash; "<?= htmlspecialchars(mb_substr($review['comment'], 0, 60)) ?><?= mb_strlen($review['comment']) > 60 ? '...' : '' ?>"</span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="review-actions">
                        <button class="btn btn-sm btn-approve" onclick="approvePost(<?= (int)$post['id'] ?>)">
                            <i class="fas fa-check"></i> Approve
                        </button>
                        <button class="btn btn-sm btn-changes" onclick="showChangesForm(<?= (int)$post['id'] ?>)">
                            <i class="fas fa-pen"></i> Request Changes
                        </button>
                        <a href="<?= BASE_URL ?>/posts/edit/<?= (int)$post['id'] ?>" class="btn btn-ghost btn-sm">
                            <i class="fas fa-external-link-alt"></i> View Post
                        </a>
                    </div>

                    <div class="changes-form" id="changes-form-<?= (int)$post['id'] ?>">
                        <textarea id="changes-comment-<?= (int)$post['id'] ?>" placeholder="Describe the changes needed..."></textarea>
                        <div class="changes-form-actions">
                            <button class="btn btn-sm btn-primary" onclick="submitChanges(<?= (int)$post['id'] ?>)">
                                <i class="fas fa-paper-plane"></i> Submit Feedback
                            </button>
                            <button class="btn btn-sm btn-ghost" onclick="hideChangesForm(<?= (int)$post['id'] ?>)">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
const BASE = '<?= rtrim(BASE_URL, "/") ?>';
const csrfToken = () => document.getElementById('csrf-token').value;

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}

async function approvePost(postId) {
    confirmModal('Approve Post', 'Are you sure you want to approve this post?', async () => {
        try {
            const fd = new FormData();
            fd.append('csrf_token', csrfToken());

            const res = await fetch(BASE + '/reviews/approve/' + postId, {
                method: 'POST',
                body: fd
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.error || 'Approval failed');

            if (data.fully_approved) {
                showToast('Post fully approved and ready to publish!', 'success');
                removeCard(postId);
            } else {
                showToast('Approval recorded.', 'success');
                updateCardAfterApproval(postId, data);
            }
        } catch (err) {
            showToast(err.message, 'error');
        }
    });
}

function showChangesForm(postId) {
    document.querySelectorAll('.changes-form.active').forEach(f => f.classList.remove('active'));
    const form = document.getElementById('changes-form-' + postId);
    if (form) {
        form.classList.add('active');
        form.querySelector('textarea').focus();
    }
}

function hideChangesForm(postId) {
    const form = document.getElementById('changes-form-' + postId);
    if (form) {
        form.classList.remove('active');
        form.querySelector('textarea').value = '';
    }
}

async function submitChanges(postId) {
    const textarea = document.getElementById('changes-comment-' + postId);
    const comment = textarea ? textarea.value.trim() : '';

    if (!comment) {
        showToast('Please describe the changes needed.', 'error');
        if (textarea) textarea.focus();
        return;
    }

    try {
        const fd = new FormData();
        fd.append('csrf_token', csrfToken());
        fd.append('comment', comment);

        const res = await fetch(BASE + '/reviews/request-changes/' + postId, {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to submit feedback');

        showToast('Feedback submitted.', 'success');
        addChangeChip(postId, comment);
        hideChangesForm(postId);
    } catch (err) {
        showToast(err.message, 'error');
    }
}

function removeCard(postId) {
    const card = document.getElementById('review-card-' + postId);
    if (card) {
        card.style.transition = 'opacity 0.3s ease, max-height 0.3s ease';
        card.style.opacity = '0';
        setTimeout(() => {
            card.remove();
            updatePendingCount();
            if (!document.querySelector('[id^="review-card-"]')) {
                document.getElementById('review-list').innerHTML =
                    '<div class="card"><div class="empty-state" style="padding:48px 24px">' +
                    '<i class="fas fa-clipboard-check" style="font-size:48px;color:var(--success);animation:emptyFloat 3s ease-in-out infinite"></i>' +
                    '<p style="font-size:16px;font-weight:600;margin-top:16px;color:var(--text)">All caught up!</p>' +
                    '<p style="color:var(--text-muted);font-size:13px;max-width:320px;margin:4px auto 0">No posts are waiting for review right now. When editors submit posts for approval, they will appear here.</p>' +
                    '</div></div>';
            }
        }, 300);
    }
}

function updateCardAfterApproval(postId, data) {
    const card = document.getElementById('review-card-' + postId);
    if (!card) return;

    // Update approval progress
    const progressEl = card.querySelector('.approval-progress span');
    const barFill = card.querySelector('.approval-bar-fill');
    if (data.approval_count !== undefined && data.min_required !== undefined) {
        const pct = Math.min(100, Math.round((data.approval_count / data.min_required) * 100));
        if (progressEl) progressEl.textContent = data.approval_count + ' of ' + data.min_required + ' approval' + (data.min_required !== 1 ? 's' : '');
        if (barFill) barFill.style.width = pct + '%';
    }

    // Add approved chip
    const chips = card.querySelector('.review-chips');
    if (chips) {
        const chip = document.createElement('span');
        chip.className = 'review-chip review-chip-approved';
        chip.innerHTML = '<i class="fas fa-check-circle"></i> ' + escHtml(data.reviewer_name || 'You') + ' approved';
        chips.appendChild(chip);
    }
}

function addChangeChip(postId, comment) {
    const card = document.getElementById('review-card-' + postId);
    if (!card) return;

    let chips = card.querySelector('.review-chips');
    if (!chips) {
        chips = document.createElement('div');
        chips.className = 'review-chips';
        const actions = card.querySelector('.review-actions');
        if (actions) actions.parentNode.insertBefore(chips, actions);
    }

    const chip = document.createElement('span');
    chip.className = 'review-chip review-chip-changes';
    const preview = comment.length > 60 ? comment.substring(0, 60) + '...' : comment;
    chip.innerHTML = '<i class="fas fa-edit"></i> You requested changes <span class="review-chip-comment">&mdash; "' + escHtml(preview) + '"</span>';
    chips.appendChild(chip);
}

function updatePendingCount() {
    const countEl = document.getElementById('pending-count');
    if (countEl) {
        const remaining = document.querySelectorAll('[id^="review-card-"]').length;
        countEl.textContent = remaining;
    }
}
</script>
