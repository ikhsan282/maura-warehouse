<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_perm('reports.view');
$db = getDB();

$date_from   = req_str('date_from', date('Y-m-01'));
$date_to     = req_str('date_to',   date('Y-m-d'));
$sup_filter  = req_int('supplier_id');

$where_parts=['si.transaction_date >= ?','si.transaction_date <= ?'];
$params=[]; $types='';
$params[]=&$date_from; $params[]=&$date_to; $types.='ss';
if($sup_filter){$where_parts[]='si.supplier_id=?';$params[]=&$sup_filter;$types.='i';}
$where='WHERE '.implode(' AND ',$where_parts);

// PDF export
if (req_str('format')==='pdf') {
    require_once __DIR__.'/../../includes/pdf.php';
    $pdf = new SimplePDF();
    $pdf->addText('Laporan Barang Masuk', 14);
    $pdf->addText('Periode: '.tgl($date_from).' - '.tgl($date_to), 10);
    $pdf->addText('', 8);
    $pdf->addTableRow(['Referensi','Tanggal','Supplier','Lokasi','Items','Qty','Nilai'],[60,40,80,60,30,40,50],true);
    
    $st=$db->prepare("SELECT si.reference_no, si.transaction_date, s.name supplier_name, l.name location_name,
        (SELECT COUNT(*) FROM stock_in_details d WHERE d.stock_in_id=si.id) item_count,
        (SELECT COALESCE(SUM(d.quantity),0) FROM stock_in_details d WHERE d.stock_in_id=si.id) total_qty,
        (SELECT COALESCE(SUM(d.quantity*d.buy_price),0) FROM stock_in_details d WHERE d.stock_in_id=si.id) total_value
        FROM stock_in si JOIN suppliers s ON s.id=si.supplier_id JOIN locations l ON l.id=si.location_id
        $where ORDER BY si.transaction_date DESC, si.id DESC");
    $st->bind_param($types,...$params);
    $st->execute(); $rs=$st->get_result();
    while($r=$rs->fetch_assoc()) {
        $pdf->addTableRow([
            $r['reference_no'],
            tgl($r['transaction_date']),
            $r['supplier_name'],
            $r['location_name'],
            $r['item_count'],
            number_format($r['total_qty']),
            idr((float)$r['total_value'])
        ],[60,40,80,60,30,40,50]);
    }
    $st->close();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="barang_masuk_'.date('Ymd').'.pdf"');
    echo $pdf->output();
    exit;
}

// CSV export
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="barang_masuk_'.date('Ymd').'.csv"');
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
fputcsv($out,['Referensi','Tanggal','Supplier','Lokasi','Item','Total Qty','Nilai','Oleh']);

$st=$db->prepare("SELECT si.reference_no, si.transaction_date, s.name supplier_name, l.name location_name,
    (SELECT COUNT(*) FROM stock_in_details d WHERE d.stock_in_id=si.id) item_count,
    (SELECT COALESCE(SUM(d.quantity),0) FROM stock_in_details d WHERE d.stock_in_id=si.id) total_qty,
    (SELECT COALESCE(SUM(d.quantity*d.buy_price),0) FROM stock_in_details d WHERE d.stock_in_id=si.id) total_value,
    u.name user_name
    FROM stock_in si JOIN suppliers s ON s.id=si.supplier_id JOIN locations l ON l.id=si.location_id JOIN users u ON u.id=si.user_id
    $where ORDER BY si.transaction_date DESC, si.id DESC");
$st->bind_param($types,...$params);
$st->execute(); $rs=$st->get_result();
while($r=$rs->fetch_assoc()) {
    fputcsv($out,[$r['reference_no'],$r['transaction_date'],$r['supplier_name'],$r['location_name'],
        $r['item_count'],$r['total_qty'],$r['total_value'],$r['user_name']]);
}
$st->close();
fclose($out);
exit;
