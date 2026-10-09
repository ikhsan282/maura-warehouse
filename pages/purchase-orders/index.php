<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/purchase_orders.php';
require_perm('purchase_orders.view');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = req_int('id'); $action = req_str('action');
    if ($action === 'order' && can('purchase_orders.edit')) {
        $st=$db->prepare("UPDATE purchase_orders SET status='ordered' WHERE id=? AND status='draft'");
        $st->bind_param('i',$id); $st->execute(); $ok=$st->affected_rows; $st->close();
        set_flash($ok?'success':'error',$ok?'Purchase order dikirim ke supplier.':'Status purchase order tidak dapat diubah.');
    } elseif ($action === 'cancel' && can('purchase_orders.cancel')) {
        $st=$db->prepare("UPDATE purchase_orders SET status='cancelled' WHERE id=? AND status IN ('draft','ordered')");
        $st->bind_param('i',$id); $st->execute(); $ok=$st->affected_rows; $st->close();
        set_flash($ok?'success':'error',$ok?'Purchase order dibatalkan.':'Purchase order tidak dapat dibatalkan.');
    } elseif ($action === 'delete' && can('purchase_orders.edit')) {
        $st=$db->prepare("DELETE FROM purchase_orders WHERE id=? AND status='draft'");
        $st->bind_param('i',$id); $st->execute(); $ok=$st->affected_rows; $st->close();
        set_flash($ok?'success':'error',$ok?'Draft purchase order dihapus.':'Hanya draft yang dapat dihapus.');
    } else set_flash('error','Aksi tidak diizinkan.');
    redirect(APP_URL.'/pages/purchase-orders/index.php');
}

$status=req_str('status'); $where=''; $params=[]; $types='';
if (in_array($status,['draft','ordered','received','cancelled'],true)) { $where='WHERE po.status=?'; $params[]=&$status; $types='s'; }
$st=$db->prepare("SELECT po.*,s.name supplier_name,l.name location_name,u.name user_name,
 (SELECT COUNT(*) FROM purchase_order_details d WHERE d.po_id=po.id) item_count,
 (SELECT COALESCE(SUM(quantity*buy_price),0) FROM purchase_order_details d WHERE d.po_id=po.id) total
 FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN locations l ON l.id=po.location_id JOIN users u ON u.id=po.user_id $where ORDER BY po.id DESC");
if($types)$st->bind_param($types,...$params); $st->execute(); $rows=$st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
$labels=['draft'=>['Draft','secondary'],'ordered'=>['Dipesan','primary'],'received'=>['Diterima','success'],'cancelled'=>['Dibatalkan','danger']];
$page_title='Purchase Order'; include __DIR__.'/../../includes/header.php';
?>
<div class="page-header d-flex justify-content-between align-items-center"><h4><i class="bi bi-cart-check me-2 text-primary"></i>Purchase Order</h4>
<?php if(can('purchase_orders.create')):?><a class="btn btn-primary btn-sm" href="form.php"><i class="bi bi-plus-lg me-1"></i>Buat PO</a><?php endif;?></div>
<div class="card table-card"><div class="card-header bg-white"><form method="get" class="d-flex gap-2"><select name="status" class="form-select form-select-sm" style="max-width:180px"><option value="">Semua status</option><?php foreach($labels as $key=>$label):?><option value="<?=$key?>" <?=$status===$key?'selected':''?>><?=$label[0]?></option><?php endforeach;?></select><button class="btn btn-sm btn-outline-secondary">Filter</button></form></div>
<div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Supplier</th><th>Tujuan</th><th>Item</th><th class="text-end">Nilai</th><th>Status</th><th class="text-center">Aksi</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada purchase order</td></tr><?php endif;?>
<?php foreach($rows as $r): [$label,$color]=$labels[$r['status']];?><tr><td><a href="view.php?id=<?=$r['id']?>"><code><?=e($r['reference_no'])?></code></a></td><td><?=tgl($r['order_date'])?></td><td><?=e($r['supplier_name'])?></td><td><?=e($r['location_name'])?></td><td><?=$r['item_count']?></td><td class="text-end"><?=idr((float)$r['total'])?></td><td><span class="badge bg-<?=$color?>"><?=$label?></span></td><td class="text-center text-nowrap">
<a class="btn btn-action btn-outline-info" href="view.php?id=<?=$r['id']?>"><i class="bi bi-eye"></i></a>
<?php if($r['status']==='draft'&&can('purchase_orders.edit')):?><a class="btn btn-action btn-outline-primary" href="form.php?id=<?=$r['id']?>"><i class="bi bi-pencil"></i></a><form method="post" class="d-inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><button name="action" value="order" class="btn btn-action btn-outline-success" title="Kirim pesanan"><i class="bi bi-send"></i></button><button name="action" value="delete" class="btn btn-action btn-outline-danger" data-confirm="Hapus draft PO?"><i class="bi bi-trash"></i></button></form><?php endif;?>
<?php if($r['status']==='ordered'&&can('purchase_orders.receive')):?><form method="post" action="receive.php" class="d-inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-action btn-success" data-confirm="Terima seluruh barang PO dan tambah stok?"><i class="bi bi-box-arrow-in-down"></i></button></form><?php endif;?>
<?php if(in_array($r['status'],['draft','ordered'],true)&&can('purchase_orders.cancel')):?><form method="post" class="d-inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><button name="action" value="cancel" class="btn btn-action btn-outline-danger" data-confirm="Batalkan PO?"><i class="bi bi-x-lg"></i></button></form><?php endif;?>
</td></tr><?php endforeach;?></tbody></table></div></div>
<?php include __DIR__.'/../../includes/footer.php';?>
