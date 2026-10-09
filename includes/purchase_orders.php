<?php

function po_valid_items(array $item_ids, array $quantities, array $prices): array {
    $items = [];
    foreach ($item_ids as $k => $raw_id) {
        $id = (int)$raw_id;
        $qty = (int)($quantities[$k] ?? 0);
        $price = (float)($prices[$k] ?? 0);
        if ($id > 0 && $qty > 0 && $price >= 0) $items[$id] = [$id, $qty, $price];
    }
    return array_values($items);
}

function receive_purchase_order(mysqli $db, int $po_id, int $user_id): string {
    $db->begin_transaction();
    try {
        $st = $db->prepare("SELECT reference_no,supplier_id,location_id,notes,status FROM purchase_orders WHERE id=? FOR UPDATE");
        $st->bind_param('i', $po_id); $st->execute();
        $po = $st->get_result()->fetch_assoc(); $st->close();
        if (!$po) throw new RuntimeException('Purchase order tidak ditemukan.');
        if ($po['status'] !== 'ordered') throw new RuntimeException('Purchase order sudah diproses atau belum dipesan.');

        $detail = $db->prepare('SELECT item_id,quantity,buy_price FROM purchase_order_details WHERE po_id=?');
        $detail->bind_param('i', $po_id); $detail->execute();
        $items = $detail->get_result()->fetch_all(MYSQLI_ASSOC); $detail->close();
        if (!$items) throw new RuntimeException('Purchase order tidak memiliki barang.');

        $ref = 'SI-' . $po['reference_no'];
        $today = date('Y-m-d');
        $notes = trim('Penerimaan ' . $po['reference_no'] . '. ' . ($po['notes'] ?? ''));
        $insert = $db->prepare('INSERT INTO stock_in (reference_no,supplier_id,location_id,user_id,notes,transaction_date) VALUES (?,?,?,?,?,?)');
        $insert->bind_param('siiiss', $ref, $po['supplier_id'], $po['location_id'], $user_id, $notes, $today);
        $insert->execute(); $stock_in_id = $db->insert_id; $insert->close();

        $line = $db->prepare('INSERT INTO stock_in_details (stock_in_id,item_id,quantity,buy_price) VALUES (?,?,?,?)');
        $stock = $db->prepare('INSERT INTO stock (item_id,location_id,quantity) VALUES (?,?,?) ON DUPLICATE KEY UPDATE quantity=quantity+VALUES(quantity)');
        $mutation = $db->prepare("INSERT INTO mutations (item_id,location_id,type,quantity,reference_no,reference_type,reference_id,user_id,notes) VALUES (?,?,'in',? ,?,'stock_in',?,?,?)");
        foreach ($items as $item) {
            $item_id = (int)$item['item_id']; $qty = (int)$item['quantity']; $price = (float)$item['buy_price'];
            $line->bind_param('iiid', $stock_in_id, $item_id, $qty, $price); $line->execute();
            $stock->bind_param('iii', $item_id, $po['location_id'], $qty); $stock->execute();
            $mutation->bind_param('iiisiis', $item_id, $po['location_id'], $qty, $ref, $stock_in_id, $user_id, $notes); $mutation->execute();
        }
        $line->close(); $stock->close(); $mutation->close();

        $update = $db->prepare("UPDATE purchase_orders SET status='received',received_by=?,received_at=NOW(),stock_in_id=? WHERE id=? AND status='ordered'");
        $update->bind_param('iii', $user_id, $stock_in_id, $po_id); $update->execute();
        if ($update->affected_rows !== 1) throw new RuntimeException('Purchase order sudah diterima.');
        $update->close();
        $db->commit();
        return $ref;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
