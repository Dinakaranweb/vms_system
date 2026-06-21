<?php
/**
 * TEMPORARY — run once to create category pivot tables on the remote server.
 * DELETE THIS FILE immediately after use.
 * Access: http://192.168.32.249:8099/vms-system/public/run_migrations_categories.php?key=vms2026migrate
 */
if (($_GET['key'] ?? '') !== 'vms2026migrate') { http_response_code(403); die('Forbidden'); }

$host = '127.0.0.1'; $db = 'vms_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) { die('DB connect failed: ' . $e->getMessage()); }

$log = [];

// 1. category_tender pivot table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `category_tender` (
      `category_id` bigint(20) UNSIGNED NOT NULL,
      `tender_id`   bigint(20) UNSIGNED NOT NULL,
      `created_at`  timestamp NULL DEFAULT NULL,
      `updated_at`  timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`category_id`, `tender_id`),
      KEY `category_tender_tender_id_foreign` (`tender_id`),
      CONSTRAINT `category_tender_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
      CONSTRAINT `category_tender_tender_id_foreign`   FOREIGN KEY (`tender_id`)   REFERENCES `tenders`    (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $log[] = '✅ category_tender table created (or already exists).';
} catch (Exception $e) {
    // Try without foreign keys (in case tables aren't created yet)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `category_tender` (
          `category_id` bigint(20) UNSIGNED NOT NULL,
          `tender_id`   bigint(20) UNSIGNED NOT NULL,
          `created_at`  timestamp NULL DEFAULT NULL,
          `updated_at`  timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`category_id`, `tender_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $log[] = '✅ category_tender created (without FK constraints).';
    } catch (Exception $e2) {
        $log[] = '❌ category_tender: ' . $e2->getMessage();
    }
}

// 2. category_vendor pivot table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `category_vendor` (
      `category_id` bigint(20) UNSIGNED NOT NULL,
      `vendor_id`   bigint(20) UNSIGNED NOT NULL,
      `created_at`  timestamp NULL DEFAULT NULL,
      `updated_at`  timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`category_id`, `vendor_id`),
      KEY `category_vendor_vendor_id_foreign` (`vendor_id`),
      CONSTRAINT `category_vendor_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
      CONSTRAINT `category_vendor_vendor_id_foreign`   FOREIGN KEY (`vendor_id`)   REFERENCES `vendors`    (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $log[] = '✅ category_vendor table created (or already exists).';
} catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `category_vendor` (
          `category_id` bigint(20) UNSIGNED NOT NULL,
          `vendor_id`   bigint(20) UNSIGNED NOT NULL,
          `created_at`  timestamp NULL DEFAULT NULL,
          `updated_at`  timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`category_id`, `vendor_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $log[] = '✅ category_vendor created (without FK constraints).';
    } catch (Exception $e2) {
        $log[] = '❌ category_vendor: ' . $e2->getMessage();
    }
}

// 3. Verify
$tables = ['category_tender', 'category_vendor'];
foreach ($tables as $tbl) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn();
        $log[] = "ℹ️ $tbl has $count row(s).";
    } catch (Exception $e) {
        $log[] = "⚠️ Could not query $tbl: " . $e->getMessage();
    }
}

echo '<pre style="font-family:monospace;font-size:14px;padding:20px;">';
echo "<strong>Migration Runner — Category Pivot Tables</strong>\n\n";
foreach ($log as $l) echo $l . "\n";
echo "\n<strong style='color:red'>⚠️ Delete this file now: public/run_migrations_categories.php</strong>";
echo '</pre>';
