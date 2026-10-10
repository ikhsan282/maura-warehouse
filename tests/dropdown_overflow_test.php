<?php
// Regression test: every TomSelect instance inside a grid must render its dropdown
// in <body>. TomSelect appends the dropdown inside the field wrapper by default,
// so a parent with overflow (Bootstrap .table-responsive / .table-card) clips it —
// the user sees the dropdown cut off or invisible inside grid tables.
//
// Run: php tests/dropdown_overflow_test.php
error_reporting(E_ALL);

$root = dirname(__DIR__);
$passed = $failed = 0;
function test(string $name, bool $pass, string $reason = ''): void {
    global $passed, $failed;
    if ($pass) { $passed++; echo "✓ $name\n"; }
    else { $failed++; echo "✗ $name\n  $reason\n"; }
}

echo "=== Dropdown Overflow Tests ===\n\n";

// 1. Every `new TomSelect(...)` call site must pass dropdownParent: 'body'.
$sources = [];
foreach (['includes/footer.php', 'assets/js/app.js', 'assets/js/*.js'] as $glob) {
    foreach (glob("$root/$glob") ?: [] as $f) $sources[] = $f;
}
$sources = array_unique($sources);

$call_sites = 0;
foreach ($sources as $file) {
    $code = file_get_contents($file);
    $rel  = str_replace("$root/", '', $file);
    // Split on each `new TomSelect(` occurrence and inspect the options object
    $parts = preg_split('/new\s+TomSelect\s*\(/', $code);
    array_shift($parts);
    foreach ($parts as $i => $chunk) {
        $call_sites++;
        $options = substr($chunk, 0, 400); // enough to cover the options literal
        test(
            "$rel: TomSelect call #" . ($i + 1) . " uses dropdownParent",
            str_contains($options, 'dropdownParent'),
            "dropdown akan terpotong oleh container overflow: " . substr(preg_replace('/\s+/', ' ', $options), 0, 120)
        );
    }
}
test('Ada minimal 1 call site TomSelect yang diperiksa', $call_sites > 0, "call_sites=$call_sites");

// 2. Global CSS must lift the TomSelect dropdown above sticky headers/sidebar.
$css = file_get_contents("$root/assets/css/style.css");
test('CSS memberi z-index tinggi untuk .ts-dropdown',
    (bool)preg_match('/\.ts-dropdown[^{]*\{[^}]*z-index\s*:\s*(\d{4,})/s', $css),
    '.ts-dropdown tidak punya z-index >= 1000 di style.css');
test('CSS memakai dropdownParent body (position fixed-safe)',
    str_contains($css, '.ts-dropdown') && str_contains($css, 'z-index'),
    'Aturan .ts-dropdown belum ada');

// 3. The stock-opname create page must load its helper include.
$opname = file_get_contents("$root/pages/stock-opname/create.php");
test('stock-opname/create.php memuat includes/stock_opname.php',
    str_contains($opname, "includes/stock_opname.php"),
    'create_stock_opname() undefined tanpa include ini');

echo "\n=== Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
exit($failed > 0 ? 1 : 0);
