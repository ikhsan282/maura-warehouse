<?php
$root = dirname(__DIR__);
$schema = file_get_contents($root . '/database/schema.sql');
$checks = [
    'purchase order table' => str_contains($schema, 'CREATE TABLE `purchase_orders`'),
    'PO stock-in uniqueness' => str_contains($schema, 'UNIQUE KEY `stock_in_unique`'),
    'PO approval columns' => str_contains($schema, '`approval_status`') && str_contains($schema, '`approved_by`') && str_contains($schema, '`approved_at`') && str_contains($schema, '`approval_notes`'),
    'PO approval permission' => str_contains($schema, "'po.approve'"),
    'PO approval action' => is_file($root . '/pages/purchase-orders/approve.php'),
    'PO receive requires approval' => str_contains(file_get_contents($root . '/includes/purchase_orders.php'), "approval_status'] !== 'approved'"),
    'PO transaction helper' => is_file($root . '/includes/purchase_orders.php'),
    'PO list' => is_file($root . '/pages/purchase-orders/index.php'),
    'PO form' => is_file($root . '/pages/purchase-orders/form.php'),
    'PO receive action' => is_file($root . '/pages/purchase-orders/receive.php'),
    'PO receive locks row' => str_contains(file_get_contents($root . '/includes/purchase_orders.php'), 'FOR UPDATE'),
    'PO receive is transactional' => str_contains(file_get_contents($root . '/includes/purchase_orders.php'), 'begin_transaction()'),
    'low-stock page' => is_file($root . '/pages/stock/alerts.php'),
    'barcode labels' => is_file($root . '/pages/items/labels.php'),
    'supplier returns table' => str_contains($schema, 'CREATE TABLE `supplier_returns`'),
    'supplier returns cancel locks row' => str_contains(file_get_contents($root . '/includes/supplier_returns.php'), 'FOR UPDATE'),
];
$failed = array_keys(array_filter($checks, fn($ok) => !$ok));
if ($failed) {
    fwrite(STDERR, 'FAILED: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}
echo "Feature self-check passed (" . count($checks) . " checks).\n";
