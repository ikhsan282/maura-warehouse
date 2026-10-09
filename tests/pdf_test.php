<?php
// PDF export self-check: header/footer markers, xref offset integrity, escaping.
// Run: php -c <php-ext.ini> tests/pdf_test.php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once __DIR__ . '/../includes/pdf.php';

$passed = $failed = 0;
function test(string $name, bool $pass, string $reason = ''): void {
    global $passed, $failed;
    if ($pass) { $passed++; echo "✓ $name\n"; }
    else { $failed++; echo "✗ $name\n  $reason\n"; }
}

// --- Build a representative PDF ---
$pdf = new SimplePDF();
$pdf->addText('Laporan Stok Barang (Test) — 100% (ok)', 14);
$pdf->addText('Nama (dengan) tanda \\ backslash', 10);
$pdf->addTableRow(['Kode', 'Nama', 'Qty'], [60, 120, 50], true);
for ($i = 1; $i <= 120; $i++) { // force multiple pages
    $pdf->addTableRow(["ITM-$i", "Barang (uji) \\ $i", (string)$i], [60, 120, 50]);
}
$bytes = $pdf->output();

test('starts with %PDF-1.4 header', str_starts_with($bytes, "%PDF-1.4\n"),
    'header=' . substr($bytes, 0, 16));

test('contains %%EOF marker', str_contains($bytes, '%%EOF'),
    '%%EOF not found');

test('ends with %%EOF', str_ends_with($bytes, '%%EOF'),
    'tail=' . substr($bytes, -12));

// --- xref offset integrity ---
// Parse startxref, read the xref table, confirm each in-use offset points
// at the byte where "<objnum> 0 obj" literally begins.
preg_match('/startxref\n(\d+)\n%%EOF$/', $bytes, $sx);
$xref_offset = (int)($sx[1] ?? -1);
test('startxref points at "xref" keyword',
    substr($bytes, $xref_offset, 4) === 'xref',
    "offset=$xref_offset reads=" . substr($bytes, $xref_offset, 8));

$lines = explode("\n", substr($bytes, $xref_offset));
$count_parts = preg_split('/\s+/', trim($lines[1])); // "0 N"
$first = (int)$count_parts[0];
$count = (int)$count_parts[1];
test('xref subsection starts at object 0', $first === 0, "first=$first");

$offsets_ok = true;
$offset_detail = '';
// entry line i (after "xref\n0 N\n") = lines[2 + i]
for ($obj = 1; $obj < $count; $obj++) {
    $entry = $lines[2 + $obj]; // skip free entry at index 2+0
    if (!preg_match('/^(\d{10}) (\d{5}) ([nf])/', $entry, $m)) {
        $offsets_ok = false; $offset_detail = "bad entry for obj $obj: '$entry'"; break;
    }
    if ($m[3] !== 'n') continue;
    $off = (int)$m[1];
    $expected = "$obj 0 obj";
    $actual = substr($bytes, $off, strlen($expected));
    if ($actual !== $expected) {
        $offsets_ok = false;
        $offset_detail = "obj $obj: xref=$off expected '$expected' got '" . $actual . "'";
        break;
    }
}
test('all xref "n" offsets resolve to their object headers', $offsets_ok, $offset_detail);

// --- object count sanity: catalog/pages/font + N pages + N contents ---
test('trailer /Size matches xref total', (function () use ($bytes, $count) {
    if (!preg_match('/\/Size (\d+)/', $bytes, $m)) return false;
    return (int)$m[1] === $count;
})(), '/Size does not match xref count');

// --- escaping: ( ) \ must be backslash-escaped inside literal strings ---
$B = chr(92); // single backslash, explicit to avoid quoting confusion
$esc_pdf = new SimplePDF();
$esc_pdf->addText("paren (x) and backslash $B end", 10);
$esc_bytes = $esc_pdf->output();
$want = "(paren {$B}(x{$B}) and backslash {$B}{$B} end) Tj";
test('( ) \ escaped in literal string', str_contains($esc_bytes, $want),
    "want: $want");
test('non-ASCII bytes replaced with ?', (function () {
    $p = new SimplePDF(); $p->addText("caf\xC3\xA9", 10);
    return str_contains($p->output(), '(caf??) Tj'); // one ? per UTF-8 byte
})(), 'UTF-8 byte not sanitized');

// --- page-break behavior: 120 rows must yield more than one page object ---
preg_match_all('/\/Type \/Page[^s]/', $bytes, $pg);
test('page break produced multiple pages', count($pg[0]) > 1,
    'pages=' . count($pg[0]));

echo "\n$passed passed, $failed failed\n";
exit($failed > 0 ? 1 : 0);
