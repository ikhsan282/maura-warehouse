<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_perm('dashboard.view');

$db = getDB();

// Stats
$total_items = $db->query('SELECT COUNT(*) FROM items WHERE is_active=1')->fetch_row()[0];
$total_suppliers = $db->query('SELECT COUNT(*) FROM suppliers WHERE is_active=1')->fetch_row()[0];
$total_locations = $db->query('SELECT COUNT(*) FROM locations WHERE is_active=1')->fetch_row()[0];

// Inventory value
$inv_value = $db->query('SELECT COALESCE(SUM(s.quantity * i.buy_price),0) FROM stock s JOIN items i ON i.id=s.item_id')->fetch_row()[0];

// Low stock items
$low_stock = $db->query('SELECT i.code, i.name, i.min_stock, COALESCE(SUM(s.quantity),0) AS total_stock,
    u.abbreviation FROM items i
    LEFT JOIN stock s ON s.item_id=i.id
    LEFT JOIN units u ON u.id=i.unit_id
    WHERE i.is_active=1
    GROUP BY i.id HAVING total_stock <= i.min_stock
    ORDER BY total_stock ASC LIMIT 10')->fetch_all(MYSQLI_ASSOC);

// Today transactions
$today = date('Y-m-d');
$today_in  = $db->query("SELECT COUNT(*) FROM stock_in WHERE transaction_date='{$today}'")->fetch_row()[0];
$today_out = $db->query("SELECT COUNT(*) FROM stock_out WHERE transaction_date='{$today}'")->fetch_row()[0];

// Recent mutations
$recent = $db->query('SELECT m.created_at, m.type, m.quantity, i.name AS item_name,
    l.name AS location_name, m.reference_no
    FROM mutations m
    JOIN items i ON i.id=m.item_id
    JOIN locations l ON l.id=m.location_id
    ORDER BY m.id DESC LIMIT 8')->fetch_all(MYSQLI_ASSOC);

$page_title = 'Dashboard';
include __DIR__ . '/../../includes/header.php';
?>
<div class="page-header d-flex align-items-center justify-content-between">
  <div><h4><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h4>
    <p class="text-muted small mb-0">Selamat datang, <?= htmlspecialchars(current_user()['name']) ?>! Hari ini <?= tgl($today) ?></p>
  </div>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-box bg-primary-subtle text-primary"><i class="bi bi-box-seam-fill"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= $total_items ?></div>
          <div class="text-muted small">Total Barang</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-box bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
          <div class="fs-4 fw-bold text-warning"><?= count($low_stock) ?></div>
          <div class="text-muted small">Stok Menipis</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-box bg-success-subtle text-success"><i class="bi bi-currency-dollar"></i></div>
        <div>
          <div class="fs-5 fw-bold"><?= idr((float)$inv_value) ?></div>
          <div class="text-muted small">Nilai Inventori</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-box bg-info-subtle text-info"><i class="bi bi-activity"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= $today_in + $today_out ?></div>
          <div class="text-muted small">Transaksi Hari Ini</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Low stock alert -->
  <div class="col-lg-6">
    <div class="card table-card">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Stok Menipis</span>
        <a href="<?= APP_URL ?>/pages/stock/index.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <?php if (empty($low_stock)): ?>
        <div class="card-body text-center text-muted py-4"><i class="bi bi-check-circle text-success fs-3"></i><br>Semua stok aman</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Kode</th><th>Nama Barang</th><th class="text-center">Stok</th><th class="text-center">Min</th></tr></thead>
          <tbody>
          <?php foreach ($low_stock as $r): ?>
            <tr>
              <td><code class="small"><?= htmlspecialchars($r['code']) ?></code></td>
              <td class="small"><?= htmlspecialchars($r['name']) ?></td>
              <td class="text-center">
                <span class="badge <?= $r['total_stock'] == 0 ? 'badge-zero' : 'badge-low' ?>">
                  <?= $r['total_stock'] ?> <?= htmlspecialchars($r['abbreviation']) ?>
                </span>
              </td>
              <td class="text-center small"><?= $r['min_stock'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent mutations -->
  <div class="col-lg-6">
    <div class="card table-card">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-2">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Mutasi Terbaru</span>
        <a href="<?= APP_URL ?>/pages/reports/mutation.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
      </div>
      <?php if (empty($recent)): ?>
        <div class="card-body text-center text-muted py-4">Belum ada mutasi</div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Waktu</th><th>Barang</th><th>Tipe</th><th class="text-end">Qty</th></tr></thead>
          <tbody>
          <?php foreach ($recent as $r):
            $type_map = ['in'=>['Masuk','success'],'out'=>['Keluar','danger'],'transfer_in'=>['T.Masuk','info'],'transfer_out'=>['T.Keluar','warning']];
            [$label,$color] = $type_map[$r['type']] ?? ['?','secondary'];
          ?>
            <tr>
              <td class="small text-muted"><?= date('d/m H:i', strtotime($r['created_at'])) ?></td>
              <td class="small"><?= htmlspecialchars($r['item_name']) ?></td>
              <td><span class="badge bg-<?= $color ?>-subtle text-<?= $color ?> border"><?= $label ?></span></td>
              <td class="text-end small fw-semibold"><?= $r['quantity'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Today summary -->
<div class="row g-3 mt-1">
  <div class="col-md-6">
    <div class="card p-3 border-start border-4 border-success">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-box-arrow-in-down text-success fs-4"></i>
        <div><div class="fw-bold fs-5"><?= $today_in ?></div><div class="small text-muted">Transaksi Masuk Hari Ini</div></div>
        <a href="<?= APP_URL ?>/pages/stock-in/index.php" class="btn btn-sm btn-outline-success ms-auto">Detail</a>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card p-3 border-start border-4 border-danger">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-box-arrow-up text-danger fs-4"></i>
        <div><div class="fw-bold fs-5"><?= $today_out ?></div><div class="small text-muted">Transaksi Keluar Hari Ini</div></div>
        <a href="<?= APP_URL ?>/pages/stock-out/index.php" class="btn btn-sm btn-outline-danger ms-auto">Detail</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
