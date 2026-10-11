<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_perm('reports.view');
$db = getDB();

$date_from  = req_str('date_from', date('Y-m-01'));
$date_to    = req_str('date_to',   date('Y-m-d'));
$loc_filter = req_int('location_id');

$where_parts=['so.transaction_date >= ?','so.transaction_date <= ?'];
$params=[]; $types='';
$params[]=&$date_from; $params[]=&$date_to; $types.='ss';
if($loc_filter){$where_parts[]='so.location_id=?';$params[]=&$loc_filter;$types.='i';}
$where='WHERE '.implode(' AND ',$where_parts);

// PDF export
if (req_str('format')==='pdf') {
    require_once __DIR__.'/../../includes/pdf.php';
    $pdf = new SimplePDF();
    $pdf->addText('Laporan Barang Keluar', 14);
    $pdf->addText('Periode: '.tgl($date_from).' - '.tgl($date_to), 10);
    
    $tot=$db->prepare("SELECT COALESCE(SUM(d.quantity),0)
        FROM stock_out so JOIN stock_out_details d ON d.stock_out_id=so.id $where");
    $tot->bind_param($types,...$params);$tot->execute();$tot->bind_result($period_qty);$tot->fetch();$tot->close();
    
    $pdf->addText('Total Qty: '.number_format($period_qty).' unit', 10);
    $pdf->addText('', 8);
    $pdf->addTableRow(['Ref','Tgl','Lokasi','Penerima','Qty'],[60,50,80,100,50],true);
    
    $st=$db->prepare("SELECT so.reference_no, so.transaction_date, so.recipient,
        l.name AS location_name,
        (SELECT SUM(d.quantity) FROM stock_out_details d WHERE d.stock_out_id=so.id) AS total_qty
        FROM stock_out so JOIN locations l ON l.id=so.location_id
        $where ORDER BY so.transaction_date DESC, so.id DESC");
    $st->bind_param($types,...$params); $st->execute(); $rs=$st->get_result();
    while($r=$rs->fetch_assoc()) {
        $pdf->addTableRow([$r['reference_no'],substr($r['transaction_date'],0,10),
            $r['location_name'],$r['recipient']??'-',number_format($r['total_qty'])],
            [60,50,80,100,50]);
    }
    $st->close();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="barang_keluar_'.date('Ymd').'.pdf"');
    echo $pdf->output();
    exit;
}

// CSV export
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="barang_keluar_'.date('Ymd').'.csv"');
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
fputcsv($out,['Referensi','Tanggal','Lokasi','Penerima','Keperluan','Item','Total Qty','Oleh']);

$st=$db->prepare("SELECT so.reference_no, so.transaction_date, so.recipient, so.purpose,
    l.name AS location_name, u.name AS user_name,
    (SELECT COUNT(*) FROM stock_out_details d WHERE d.stock_out_id=so.id) AS item_count,
    (SELECT SUM(d.quantity) FROM stock_out_details d WHERE d.stock_out_id=so.id) AS total_qty
    FROM stock_out so JOIN locations l ON l.id=so.location_id JOIN users u ON u.id=so.user_id
    $where ORDER BY so.transaction_date DESC, so.id DESC");
$st->bind_param($types,...$params); $st->execute(); $rs=$st->get_result();
while($r=$rs->fetch_assoc()) {
    fputcsv($out,[$r['reference_no'],$r['transaction_date'],$r['location_name'],
        $r['recipient']??'-',$r['purpose']??'-',$r['item_count'],$r['total_qty'],$r['user_name']]);
}
$st->close();
fclose($out);
exit;
