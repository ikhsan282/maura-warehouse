<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_perm('reports.view');
$db = getDB();

$supplier_filter = req_int('supplier_id');
$start_date = req_str('start_date', date('Y-m-01'));
$end_date = req_str('end_date', date('Y-m-d'));

$where_parts = ['po.order_date BETWEEN ? AND ?'];
$params = [$start_date, $end_date];
$types = 'ss';

if ($supplier_filter) {
    $where_parts[] = 's.id = ?';
    $params[] = $supplier_filter;
    $types .= 'i';
}

$where = 'WHERE ' . implode(' AND ', $where_parts);

// PDF export
if (req_str('format')==='pdf') {
    require_once __DIR__.'/../../includes/pdf.php';
    $pdf = new SimplePDF();
    $pdf->addText('Laporan Performa Supplier', 14);
    $pdf->addText('Periode: '.tgl($start_date).' - '.tgl($end_date), 10);
    $pdf->addText('', 8);
    $pdf->addTableRow(['Kode','Supplier','PO','Selesai','Lead Time','On-Time %','Nilai'],[40,100,30,30,40,40,60],true);
    
    $query = "SELECT s.code, s.name,
        COUNT(DISTINCT po.id) as total_po,
        SUM(CASE WHEN po.status='completed' THEN 1 ELSE 0 END) as completed_po,
        AVG(CASE WHEN po.received_at IS NOT NULL AND po.order_date IS NOT NULL 
            THEN DATEDIFF(po.received_at, po.order_date) ELSE NULL END) as avg_lead_time,
        ROUND((SUM(CASE WHEN po.received_at IS NOT NULL AND po.expected_date IS NOT NULL 
            AND po.received_at <= po.expected_date THEN 1 ELSE 0 END) / 
         NULLIF(SUM(CASE WHEN po.received_at IS NOT NULL AND po.expected_date IS NOT NULL THEN 1 ELSE 0 END), 0)) * 100, 2) as on_time_rate,
        COALESCE(SUM(pod.quantity * pod.buy_price), 0) as total_value
    FROM suppliers s
    LEFT JOIN purchase_orders po ON s.id = po.supplier_id AND po.order_date BETWEEN ? AND ?
    LEFT JOIN purchase_order_details pod ON po.id = pod.po_id
    " . ($supplier_filter ? "WHERE s.id = ?" : "") . "
    GROUP BY s.id ORDER BY total_value DESC";
    
    $st = $db->prepare($query);
    $qp = [$start_date, $end_date];
    $qt = 'ss';
    if ($supplier_filter) { $qp[] = $supplier_filter; $qt .= 'i'; }
    $st->bind_param($qt, ...$qp);
    $st->execute(); $rs=$st->get_result();
    while($r=$rs->fetch_assoc()) {
        $lead_time = $r['avg_lead_time'] !== null ? round($r['avg_lead_time'], 1).' hari' : '-';
        $on_time = $r['on_time_rate'] !== null ? $r['on_time_rate'].'%' : '-';
        $pdf->addTableRow([
            $r['code'],
            $r['name'],
            $r['total_po'],
            $r['completed_po'],
            $lead_time,
            $on_time,
            idr((float)$r['total_value'])
        ],[40,100,30,30,40,40,60]);
    }
    $st->close();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="supplier_performance_'.date('Ymd').'.pdf"');
    echo $pdf->output();
    exit;
}

// CSV export
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="supplier_performance_'.date('Ymd').'.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Kode', 'Nama Supplier', 'Kontak', 'Telepon', 'Total PO', 'PO Selesai', 
               'Rata-rata Lead Time (hari)', 'On-Time Rate (%)', 'Total Nilai'], ',', '"', '\\');

$query = "SELECT s.code, s.name, s.contact_person, s.phone,
    COUNT(DISTINCT po.id) as total_po,
    SUM(CASE WHEN po.status='completed' THEN 1 ELSE 0 END) as completed_po,
    AVG(CASE WHEN po.received_at IS NOT NULL AND po.order_date IS NOT NULL 
        THEN DATEDIFF(po.received_at, po.order_date) ELSE NULL END) as avg_lead_time,
    ROUND((SUM(CASE WHEN po.received_at IS NOT NULL AND po.expected_date IS NOT NULL 
        AND po.received_at <= po.expected_date THEN 1 ELSE 0 END) / 
     NULLIF(SUM(CASE WHEN po.received_at IS NOT NULL AND po.expected_date IS NOT NULL THEN 1 ELSE 0 END), 0)) * 100, 2) as on_time_rate,
    COALESCE(SUM(pod.quantity * pod.buy_price), 0) as total_value
FROM suppliers s
LEFT JOIN purchase_orders po ON s.id = po.supplier_id AND po.order_date BETWEEN ? AND ?
LEFT JOIN purchase_order_details pod ON po.id = pod.po_id
" . ($supplier_filter ? "WHERE s.id = ?" : "") . "
GROUP BY s.id ORDER BY total_value DESC";

$st = $db->prepare($query);
$qp = [$start_date, $end_date];
$qt = 'ss';
if ($supplier_filter) { $qp[] = $supplier_filter; $qt .= 'i'; }
$st->bind_param($qt, ...$qp);
$st->execute(); $rs=$st->get_result();
while($r=$rs->fetch_assoc()) {
    $completion_rate = $r['total_po'] > 0 ? round(($r['completed_po'] / $r['total_po']) * 100, 1) : 0;
    $lead_time = $r['avg_lead_time'] !== null ? round($r['avg_lead_time'], 1) : '';
    $on_time = $r['on_time_rate'] !== null ? $r['on_time_rate'] : '';
    
    fputcsv($out, [
        $r['code'],
        $r['name'],
        $r['contact_person'] ?: '',
        $r['phone'] ?: '',
        $r['total_po'],
        $r['completed_po'] . ' (' . $completion_rate . '%)',
        $lead_time,
        $on_time,
        $r['total_value']
    ], ',', '"', '\\');
}
$st->close();
fclose($out);
exit;
