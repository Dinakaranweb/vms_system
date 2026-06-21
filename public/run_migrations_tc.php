<?php
/**
 * TEMPORARY — run once to create the terms_conditions table on the remote server.
 * DELETE THIS FILE immediately after use.
 * Access: http://192.168.32.249:8099/vms-system/public/run_migrations_tc.php?key=vms2026migrate
 */
if (($_GET['key'] ?? '') !== 'vms2026migrate') { http_response_code(403); die('Forbidden'); }

$host = '127.0.0.1'; $db = 'vms_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { die('DB connect failed: ' . $e->getMessage()); }

$log = [];

// 1. Create terms_conditions table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `terms_conditions` (
      `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `content` text NOT NULL,
      `applicable_to` enum('work_order','purchase_order','both') NOT NULL DEFAULT 'both',
      `sort_order` int(11) NOT NULL DEFAULT 0,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NULL DEFAULT NULL,
      `updated_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $log[] = '✅ terms_conditions table created (or already exists).';
} catch (Exception $e) { $log[] = '❌ Create table: ' . $e->getMessage(); }

// 2. Seed default terms (skip if already seeded)
$existing = $pdo->query("SELECT COUNT(*) FROM terms_conditions")->fetchColumn();
if ($existing == 0) {
    $now = date('Y-m-d H:i:s');
    $rows = [
        ['Price', 'Rates are firm and inclusive of packing, forwarding charges.', 'purchase_order', 1],
        ['Taxes', 'GST @ 18% is included in the quoted rates. No additional tax shall be payable.', 'purchase_order', 2],
        ['Delivery Mode', 'Immediate delivery upon receipt of the Purchase Order.', 'purchase_order', 3],
        ['Transportation', 'Included in VMRF-DU Scope.', 'purchase_order', 4],
        ['Payment Mode', '100% advance payment against the Purchase Order.', 'purchase_order', 5],
        ['Quality', 'The Supplied product should be as per given specification. If any quality complaint arises, it should be replaced immediately by the supplier.', 'purchase_order', 6],
        ['Cancellation', 'The VMRF-DU reserves the right to cancel the purchase order in case of non-compliance with the agreed specifications, delivery schedule, or other terms and conditions.', 'purchase_order', 7],
        ['Documents Required at Despatch', 'Original Invoice with GST details, E-way Bill and warranty details.', 'purchase_order', 8],
        ['Duration', 'The work shall be completed within 30 days from the date of issuance of the work order as per discussion. If the work is not completed by the committed date, 0.5% of the total amount will be deducted.', 'work_order', 1],
        ['Pricing', 'Inclusive of any additional work that may be performed related with the work order.', 'work_order', 2],
        ['Payment Mode', '50% advance payment against Work order. 40% Advance payment against payment request. Remaining 10% balance payment after work completion.', 'work_order', 3],
        ['Quality & Service', 'Should be superior. If any quality complaint arises, it should be rectified immediately.', 'work_order', 4],
        ['Safety', 'Workers must adhere to safety measures (helmet/safety jacket/safety rope) at the work place. The contractor is responsible for the workers safety at the workplace.', 'work_order', 5],
        ['Work Completion', 'Work completion report should be approved by Property Management department.', 'work_order', 6],
    ];
    $stmt = $pdo->prepare("INSERT INTO terms_conditions (title, content, applicable_to, sort_order, is_active, created_at, updated_at) VALUES (?,?,?,?,1,?,?)");
    foreach ($rows as $r) { $stmt->execute([$r[0], $r[1], $r[2], $r[3], $now, $now]); }
    $log[] = '✅ Seeded ' . count($rows) . ' default terms.';
} else {
    $log[] = "ℹ️ terms_conditions already has {$existing} rows — skipped seed.";
}

echo '<pre style="font-family:monospace;font-size:14px;padding:20px;">';
echo "<strong>Migration Runner — Terms &amp; Conditions</strong>\n\n";
foreach ($log as $l) echo $l . "\n";
echo "\n<strong style='color:red'>⚠️ Delete this file now: public/run_migrations_tc.php</strong>";
echo '</pre>';
