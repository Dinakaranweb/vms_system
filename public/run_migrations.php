<?php
// TEMPORARY - DELETE THIS FILE AFTER USE
if (!isset($_GET['key']) || $_GET['key'] !== 'vms2026migrate') {
    http_response_code(403); die('Unauthorized');
}

$host = '127.0.0.1'; $db = 'vms_db'; $user = 'root'; $pass = '';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$results = [];

// 1. Add order fields to contracts table
$cols = $pdo->query("SHOW COLUMNS FROM contracts")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('order_number', $cols)) {
    $pdo->exec("ALTER TABLE contracts
        ADD COLUMN order_type VARCHAR(50) NULL AFTER status,
        ADD COLUMN order_number VARCHAR(100) NULL AFTER order_type,
        ADD COLUMN order_date DATE NULL AFTER order_number,
        ADD COLUMN vmrfdu_ref VARCHAR(100) NULL,
        ADD COLUMN supplier_ref VARCHAR(100) NULL,
        ADD COLUMN order_items JSON NULL,
        ADD COLUMN gst_rate DECIMAL(5,2) DEFAULT 18.00,
        ADD COLUMN order_discount DECIMAL(15,2) DEFAULT 0.00,
        ADD COLUMN order_terms JSON NULL,
        ADD COLUMN order_notes TEXT NULL,
        ADD COLUMN order_generated_at TIMESTAMP NULL");
    $results[] = '✅ Added order columns to contracts table';
} else {
    $results[] = '⚠️  order_number already exists in contracts table';
}

// 2. Create eligibility_failures table
$tables = $pdo->query("SHOW TABLES LIKE 'eligibility_failures'")->fetchAll();
if (!$tables) {
    $pdo->exec("CREATE TABLE eligibility_failures (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tender_id BIGINT UNSIGNED NOT NULL,
        vendor_id BIGINT UNSIGNED NOT NULL,
        failed_questions JSON NULL,
        submitted_answers JSON NULL,
        ip_address VARCHAR(45) NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE,
        FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $results[] = '✅ Created eligibility_failures table';
} else {
    $results[] = '⚠️  eligibility_failures table already exists';
}

// 3. Add application requirement flags to tenders table
$tCols = $pdo->query("SHOW COLUMNS FROM tenders")->fetchAll(PDO::FETCH_COLUMN);
$toAdd = [];
if (!in_array('require_bid_amount', $tCols))    $toAdd[] = "ADD COLUMN require_bid_amount TINYINT(1) NOT NULL DEFAULT 1";
if (!in_array('require_technical_bid', $tCols)) $toAdd[] = "ADD COLUMN require_technical_bid TINYINT(1) NOT NULL DEFAULT 1";
if (!in_array('require_financial_bid', $tCols)) $toAdd[] = "ADD COLUMN require_financial_bid TINYINT(1) NOT NULL DEFAULT 1";
if ($toAdd) {
    $pdo->exec("ALTER TABLE tenders " . implode(', ', $toAdd));
    $results[] = '✅ Added require_bid_amount, require_technical_bid, require_financial_bid to tenders table';
} else {
    $results[] = '⚠️  Application requirement columns already exist in tenders table';
}

echo '<pre style="font-family:monospace;font-size:14px;padding:20px">';
echo "=== VMS Migration Runner ===\n\n";
foreach ($results as $r) echo $r . "\n";
echo "\n✅ Done. PLEASE DELETE this file: public/run_migrations.php\n";
echo '</pre>';
