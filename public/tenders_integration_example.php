<?php
/**
 * VMRF-DU Tenders Integration — Example for vmrfdu.edu.in/tenders.php
 *
 * HOW TO USE:
 *  1. Copy this file to your external website as  tenders.php
 *  2. Update VMS_BASE_URL below to point to the VMS server.
 *  3. Done — tenders auto-refresh from the VMS API.
 *
 * API Endpoint: http://192.168.32.249:8099/vms-system/public/api/public/tenders
 * (If VMS is accessible on the public internet, replace with its public domain)
 */

define('VMS_API_URL', 'http://192.168.32.249:8099/vms-system/public/api/public/tenders?limit=50');
define('CACHE_FILE',  __DIR__ . '/tenders_cache.json');
define('CACHE_TTL',   300); // 5 minutes

/* ─── Fetch from API with file-based cache ─────────────────────────── */
function fetchTenders(): array
{
    // Serve from cache if fresh
    if (file_exists(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
        $cached = json_decode(file_get_contents(CACHE_FILE), true);
        if (!empty($cached['tenders'])) {
            return $cached;
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'timeout' => 8,
            'header'  => "Accept: application/json\r\n",
        ],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);

    $response = @file_get_contents(VMS_API_URL, false, $ctx);
    if ($response === false) {
        // Return stale cache if available
        if (file_exists(CACHE_FILE)) {
            return json_decode(file_get_contents(CACHE_FILE), true) ?? ['tenders' => []];
        }
        return ['tenders' => [], 'count' => 0];
    }

    $data = json_decode($response, true);
    if (!empty($data['success'])) {
        file_put_contents(CACHE_FILE, $response, LOCK_EX);
    }
    return $data ?? ['tenders' => []];
}

$result  = fetchTenders();
$tenders = $result['tenders'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenders — VMRF-DU</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #222; }

        .page-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2563a8 100%);
            color: #fff;
            padding: 2.5rem 1.5rem 2rem;
            text-align: center;
        }
        .page-header h1 { font-size: 2rem; font-weight: 700; letter-spacing: .02em; }
        .page-header p  { font-size: .95rem; opacity: .78; margin-top: .4rem; }

        .container { max-width: 1060px; margin: 0 auto; padding: 2rem 1rem; }

        /* ── Search bar ── */
        .search-bar {
            display: flex; gap: .75rem; flex-wrap: wrap;
            margin-bottom: 1.75rem;
        }
        .search-bar input, .search-bar select {
            border: 1px solid #d1d5db; border-radius: 8px;
            padding: .55rem 1rem; font-size: .9rem; outline: none;
            transition: border-color .15s;
        }
        .search-bar input { flex: 1; min-width: 200px; }
        .search-bar input:focus, .search-bar select:focus { border-color: #2563a8; }

        /* ── Stats ── */
        .stats-bar {
            display: flex; gap: 1rem; flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }
        .stat-pill {
            background: #fff; border: 1px solid #e5e7eb;
            border-radius: 999px; padding: .35rem 1rem;
            font-size: .82rem; font-weight: 600; color: #374151;
        }
        .stat-pill span { color: #2563a8; }

        /* ── Cards ── */
        .tender-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.1rem;
            position: relative;
            transition: box-shadow .18s ease, transform .18s ease;
        }
        .tender-card:hover { box-shadow: 0 6px 24px rgba(37,99,168,.09); transform: translateY(-1px); }

        .card-top    { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap; }
        .card-number { font-size: .78rem; color: #6b7280; font-weight: 600; letter-spacing: .06em; margin-bottom: .35rem; font-family: monospace; }
        .card-title  { font-size: 1.08rem; font-weight: 700; color: #1e3a5f; line-height: 1.35; }
        .card-desc   { font-size: .88rem; color: #6b7280; margin-top: .5rem; line-height: 1.55; }

        /* Status badge */
        .badge {
            display: inline-flex; align-items: center; gap: .3rem;
            font-size: .75rem; font-weight: 700; padding: .3rem .85rem;
            border-radius: 999px; white-space: nowrap; flex-shrink: 0;
        }
        .badge-open   { background: #d1fae5; color: #065f46; }
        .badge-closed { background: #fee2e2; color: #991b1b; }
        .badge-dot    { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        /* Meta grid */
        .card-meta {
            display: flex; gap: 1.5rem; flex-wrap: wrap;
            margin-top: 1rem; padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
        }
        .meta-item { font-size: .82rem; color: #6b7280; }
        .meta-item strong { color: #374151; display: block; font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; margin-bottom: .15rem; }

        /* Urgency */
        .days-badge {
            display: inline-block; font-size: .74rem; font-weight: 700;
            padding: .15rem .6rem; border-radius: 6px; margin-left: .5rem;
        }
        .days-urgent { background: #fee2e2; color: #b91c1c; }
        .days-warn   { background: #fef3c7; color: #92400e; }
        .days-ok     { background: #d1fae5; color: #065f46; }

        /* Documents */
        .doc-list  { margin-top: .85rem; display: flex; gap: .5rem; flex-wrap: wrap; }
        .doc-link  {
            display: inline-flex; align-items: center; gap: .35rem;
            background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
            border-radius: 7px; padding: .3rem .75rem; font-size: .8rem;
            font-weight: 500; text-decoration: none; transition: background .15s;
        }
        .doc-link:hover { background: #dbeafe; }

        /* Empty state */
        .empty-state {
            text-align: center; padding: 4rem 1rem;
            color: #9ca3af; font-size: .95rem;
        }
        .empty-state svg { width: 56px; height: 56px; opacity: .3; margin: 0 auto 1rem; display: block; }

        /* Footer note */
        .api-note {
            font-size: .78rem; color: #9ca3af; text-align: center;
            margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 600px) {
            .card-meta { gap: 1rem; }
            .page-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>Active Tenders</h1>
    <p>Vinayaka Mission's Research Foundation – Deemed to be University</p>
</div>

<div class="container">

    <!-- Search / Filter -->
    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search by title or tender number…" oninput="filterCards()">
        <select id="statusFilter" onchange="filterCards()">
            <option value="">All Status</option>
            <option value="open">Open (accepting bids)</option>
            <option value="closed">Closed (under evaluation)</option>
        </select>
    </div>

    <!-- Stats -->
    <?php
    $openCount   = count(array_filter($tenders, fn($t) => $t['is_open']));
    $closedCount = count($tenders) - $openCount;
    ?>
    <div class="stats-bar">
        <div class="stat-pill">Total: <span><?= count($tenders) ?></span></div>
        <div class="stat-pill">Open: <span><?= $openCount ?></span></div>
        <div class="stat-pill">Under Evaluation: <span><?= $closedCount ?></span></div>
    </div>

    <!-- Tender Cards -->
    <div id="tenderList">
    <?php if (empty($tenders)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>No active tenders at this time.</p>
            <p style="margin-top:.4rem;font-size:.83rem;">Please check back later or contact the procurement office.</p>
        </div>
    <?php else: ?>
        <?php foreach ($tenders as $t):
            $isOpen  = $t['is_open'];
            $days    = (int) $t['days_remaining'];
            $daysCls = $days <= 3 ? 'days-urgent' : ($days <= 7 ? 'days-warn' : 'days-ok');
            $estVal  = $t['estimated_value'] ? '₹' . number_format($t['estimated_value'], 2) : '—';
            $emda    = $t['emda_amount']     ? '₹' . number_format($t['emda_amount'], 2)     : '—';
        ?>
        <div class="tender-card"
             data-title="<?= htmlspecialchars(strtolower($t['title'] . ' ' . $t['tender_number'])) ?>"
             data-status="<?= $isOpen ? 'open' : 'closed' ?>">

            <div class="card-top">
                <div style="flex:1;">
                    <div class="card-number"><?= htmlspecialchars($t['tender_number']) ?></div>
                    <div class="card-title"><?= htmlspecialchars($t['title']) ?></div>
                    <?php if ($t['description']): ?>
                        <div class="card-desc"><?= nl2br(htmlspecialchars(mb_strimwidth($t['description'], 0, 180, '…'))) ?></div>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <?php if ($isOpen): ?>
                        <span class="badge badge-open"><span class="badge-dot"></span>Open</span>
                    <?php else: ?>
                        <span class="badge badge-closed"><span class="badge-dot"></span>Under Evaluation</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-meta">
                <div class="meta-item">
                    <strong>Estimated Value</strong>
                    <?= $estVal ?>
                </div>
                <div class="meta-item">
                    <strong>EMD Amount</strong>
                    <?= $emda ?>
                </div>
                <div class="meta-item">
                    <strong>Submission Deadline</strong>
                    <?= $t['submission_deadline']
                        ? date('d M Y', strtotime($t['submission_deadline'])) . ($t['submission_time'] ? ', ' . $t['submission_time'] : '')
                        : '—' ?>
                    <?php if ($isOpen && $days >= 0): ?>
                        <span class="days-badge <?= $daysCls ?>">
                            <?= $days === 0 ? 'Today!' : $days . ' day' . ($days === 1 ? '' : 's') . ' left' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="meta-item">
                    <strong>Opening Date</strong>
                    <?= $t['opening_date']
                        ? date('d M Y', strtotime($t['opening_date'])) . ($t['opening_time'] ? ', ' . $t['opening_time'] : '')
                        : '—' ?>
                </div>
                <div class="meta-item">
                    <strong>Type</strong>
                    <?= ucfirst($t['tender_type'] ?? '—') ?>
                </div>
            </div>

            <?php if (!empty($t['documents'])): ?>
            <div class="doc-list">
                <?php foreach ($t['documents'] as $doc): ?>
                <a href="<?= htmlspecialchars($doc['url']) ?>" target="_blank" rel="noopener" class="doc-link">
                    📄 <?= htmlspecialchars($doc['title'] ?: $doc['file_name']) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div><!-- #tenderList -->

    <div class="api-note">
        Data sourced from VMRF-DU Vendor Management System &nbsp;·&nbsp;
        Last updated: <?= date('d M Y, H:i') ?>
        <?php if (!empty($result['generated'])): ?>
            &nbsp;·&nbsp; API generated: <?= date('d M Y, H:i', strtotime($result['generated'])) ?>
        <?php endif; ?>
    </div>

</div><!-- .container -->

<script>
function filterCards() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    document.querySelectorAll('.tender-card').forEach(card => {
        const titleMatch  = !q      || card.dataset.title.includes(q);
        const statusMatch = !status || card.dataset.status === status;
        card.style.display = (titleMatch && statusMatch) ? '' : 'none';
    });
}
</script>

</body>
</html>
