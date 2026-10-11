<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_perm('reports.view');
$db = getDB();

$date_from  = req_str('date_from', date('Y-m-01'));
$date_to    = req_str('date_to',   date('Y-m-d'));
$item_filter= req_int('item_id');
$type_filter= req_str('type');

$where_parts = ['m.created_at >= ?', 'm.created_at <= ?'];
$params = []; $types = '';
$df = $date_from.' 00:00:00'; $dt = $date_to.' 23:59:59';
$params[]=&$df; $params[]=&$dt; $types.='ss';
if ($item_filter) { $where_parts[]='m.item_id=?'; $params[]=&$item_filter; $types.='i'; }
if ($type_filter) { $where_parts[]='m.type=?';    $params[]=&$type_filter; $types.='s'; }
$where='WHERE '.implode(' AND ',$where_parts);

// PDF export
if (req_str('format')==='pdf') {
    require_once __DIR__.'/../../includes/pdf.php';
    $pdf = new SimplePDF();
    $pdf->addText('Laporan Mutasi Barang', 14);
    $pdf->addText('Periode: '.tgl($date_from).' - '.tgl($date_to), 10);
    $pdf->addText('', 8);
    $pdf->addTableRow(['Waktu','Kode','Barang','Tipe','Lokasi','Qty','Ref'],[40,50,80,50,50,30,50],true);
    
    $st=$db->prepare("SELECT m.created_at, i.code AS item_code, i.name AS item_name, m.type,
        l.name AS loc_name, m.quantity, m.reference, u.abbreviation
        FROM mutations m JOIN items i ON i.id=m.item_id JOIN units u ON u.id=i.unit_id
        JOIN locations l ON l.id=m.location_id JOIN users us ON us.id=m.user_id
        $where ORDER BY m.id DESC");
    $st->bind_param($types,...$params);
    $st->execute(); $rs=$st->get_result();
    while($r=$rs->fetch_assoc()) {
        $type_map=['in'=>'Masuk','out'=>'Keluar','transfer_in'=>'Transfer In','transfer_out'=>'Transfer Out','adjustment'=>'Penyesuaian'];
        $pdf->addTableRow([
            date('d/m H:i',strtotime($r['created_at'])),
            $r['item_code'],
            $r['item_name'],
            $type_map[$r['type']]??$r['type'],
            $r['loc_name'],
            $r['quantity'].' '.$r['abbreviation'],
            $r['reference']
        ],[40,50,80,50,50,30,50]);
    }
    $st->close();
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="mutasi_'.date('Ymd').'.pdf"');
    echo $pdf->output();
    exit;
}

// CSV export
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="mutasi_'.date('Ymd').'.csv"');
echo "\xEF\xBB\xBF";
$out=fopen('php://output','w');
fputcsv($out,['Waktu','Kode Barang','Nama Barang','Tipe','Lokasi','Qty','Satuan','Referensi','Oleh']);

$st=$db->prepare("SELECT m.created_at, i.code AS item_code, i.name AS item_name, m.type,
    l.name AS loc_name, m.quantity, u.abbreviation, m.reference, us.name AS user_name
    FROM mutations m JOIN items i ON i.id=m.item_id JOIN units u ON u.id=i.unit_id
    JOIN locations l ON l.id=m.location_id JOIN users us ON us.id=m.user_id
    $where ORDER BY m.id DESC");
$st->bind_param($types,...$params);
$st->execute(); $rs=$st->get_result();
while($r=$rs->fetch_assoc()) {
    $type_map=['in'=>'Masuk','out'=>'Keluar','transfer_in'=>'Transfer In','transfer_out'=>'Transfer Out','adjustment'=>'Penyesuaian'];
    fputcsv($out,[
        $r['created_at'],
        $r['item_code'],
        $r['item_name'],
        $type_map[$r['type']]??$r['type'],
        $r['loc_name'],
        $r['quantity'],
        $r['abbreviation'],
        $r['reference'],
        $r['user_name']
    ]);
}
$st->close();
fclose($out);
exit;
