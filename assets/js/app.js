// Maura Warehouse – app.js

document.addEventListener('DOMContentLoaded', () => {

  // ── Sidebar toggle (desktop) ─────────────────────────────
  const sidebar       = document.getElementById('sidebar');
  const toggleBtn     = document.getElementById('sidebarToggle');
  const toggleMobile  = document.getElementById('sidebarToggleMobile');

  // Restore collapsed state
  if (localStorage.getItem('sidebarCollapsed') === '1') {
    sidebar?.classList.add('collapsed');
  }

  toggleBtn?.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
  });

  // ── Sidebar toggle (mobile) ──────────────────────────────
  let overlay = document.querySelector('.sidebar-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
  }

  toggleMobile?.addEventListener('click', () => {
    sidebar?.classList.toggle('mobile-open');
    overlay.classList.toggle('show');
  });
  overlay.addEventListener('click', () => {
    sidebar?.classList.remove('mobile-open');
    overlay.classList.remove('show');
  });

  // ── Auto-dismiss alerts after 5s ────────────────────────
  document.querySelectorAll('.alert-dismissible').forEach(el => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      bsAlert?.close();
    }, 5000);
  });

  // ── Confirm delete ───────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      const msg = el.dataset.confirm || 'Yakin ingin menghapus data ini?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // ── Dynamic item rows (stock-in / stock-out) ─────────────
  const addRowBtn = document.getElementById('addItemRow');
  const itemsBody = document.getElementById('itemsBody');

  addRowBtn?.addEventListener('click', () => {
    const idx   = itemsBody.querySelectorAll('tr').length;
    const first = itemsBody.querySelector('tr');
    if (!first) return;
    const clone = first.cloneNode(true);
    // Update name attributes with new index
    clone.querySelectorAll('[name]').forEach(el => {
      el.name  = el.name.replace(/\[\d+\]/, `[${idx}]`);
      el.value = '';
    });
    clone.querySelector('.remove-row')?.addEventListener('click', removeRow);
    itemsBody.appendChild(clone);
  });

  function removeRow(e) {
    const rows = itemsBody.querySelectorAll('tr');
    if (rows.length <= 1) return;
    e.target.closest('tr').remove();
  }

  itemsBody?.querySelectorAll('.remove-row').forEach(btn => {
    btn.addEventListener('click', removeRow);
  });

  // ── Live stock total calculation ─────────────────────────
  document.addEventListener('input', e => {
    if (e.target.matches('.item-qty, .item-price')) calcTotal();
  });

  function calcTotal() {
    let total = 0;
    itemsBody?.querySelectorAll('tr').forEach(row => {
      const qty   = parseFloat(row.querySelector('.item-qty')?.value) || 0;
      const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
      total += qty * price;
    });
    const el = document.getElementById('grandTotal');
    if (el) el.textContent = 'Rp ' + total.toLocaleString('id-ID');
  }

  // ── Datepicker default today ──────────────────────────────
  document.querySelectorAll('input[type=date]:not([value])').forEach(el => {
    if (!el.value) el.value = new Date().toISOString().split('T')[0];
  });

  // ── Search filter (client-side table) ────────────────────
  const searchInput = document.getElementById('tableSearch');
  searchInput?.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase();
    document.querySelectorAll('table tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

});
