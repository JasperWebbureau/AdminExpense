<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/bootstrap.php';
$projectRoot = dirname(__DIR__, 4); require $projectRoot . '/.env.php';
$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['name'], $db['username'], $db['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); unset($db);
foreach (['admin_expense', 'admin_expense_category', 'admin_expense_attachment'] as $table) {
    $statement = $pdo->prepare('SHOW TABLES LIKE :table'); $statement->execute([':table' => $table]);
    adminExpenseAssert($statement->fetchColumn() !== false, 'Schema mist ' . $table . '; voer force_aw uit.');
}
foreach (['admin_expense' => ['tenant_id', 'public_id', 'category_public_id', 'supplier_snapshot', 'net_amount_minor', 'tax_amount_minor', 'gross_amount_minor'], 'admin_expense_category' => ['tenant_id', 'public_id', 'code', 'is_active', 'reporting_type', 'vat_deductible_percentage'], 'admin_expense_attachment' => ['tenant_id', 'public_id', 'expense_public_id', 'file_reference', 'sha256']] as $table => $columns) {
    $actual = []; foreach ($pdo->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) { $actual[] = $row['Field']; }
    foreach ($columns as $column) { adminExpenseAssert(in_array($column, $actual, true), $table . ' mist kolom ' . $column . '.'); }
}
echo "AdminExpense schema tests passed.\n";
