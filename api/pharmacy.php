<?php
require_once 'config.php';
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;
$type   = $_GET['type']   ?? null;
$filter = $_GET['filter'] ?? null;

switch ($method) {

    case 'GET':
        // Return categories list
        if ($type === 'categories') {
            $rows = $pdo->query("SELECT * FROM medicine_categories ORDER BY name")->fetchAll();
            echo json_encode($rows);
            break;
        }
        // Low stock
        if ($filter === 'lowstock') {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $stmt  = $pdo->prepare("
                SELECT m.*, i.quantity_in_stock, i.reorder_level, i.expiry_date,
                       c.name AS category
                FROM medicines m
                JOIN inventory i ON i.medicine_id = m.medicine_id
                LEFT JOIN medicine_categories c ON c.category_id = m.category_id
                WHERE i.quantity_in_stock <= i.reorder_level
                ORDER BY i.quantity_in_stock ASC
                LIMIT $limit
            ");
            $stmt->execute();
            echo json_encode($stmt->fetchAll());
            break;
        }
        // All medicines with inventory
        $stmt = $pdo->query("
            SELECT m.*, i.quantity_in_stock, i.reorder_level, i.expiry_date, i.last_restocked,
                   c.name AS category
            FROM medicines m
            LEFT JOIN inventory i ON i.medicine_id = m.medicine_id
            LEFT JOIN medicine_categories c ON c.category_id = m.category_id
            ORDER BY m.name
        ");
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        // Restock action
        if ($action === 'restock') {
            $mid = $_POST['medicine_id'] ?? null;
            $qty = (int)($_POST['quantity'] ?? 0);
            if (!$mid || $qty < 1) {
                echo json_encode(['success'=>false,'error'=>'Invalid data']); exit;
            }
            $pdo->prepare("
                UPDATE inventory
                SET quantity_in_stock = quantity_in_stock + ?,
                    last_restocked = NOW()
                WHERE medicine_id = ?
            ")->execute([$qty, $mid]);
            echo json_encode(['success' => true]);
            break;
        }
        // Add new medicine
        $f = $_POST;
        if (empty($f['name']) || !isset($f['unit_price'])) {
            echo json_encode(['success'=>false,'error'=>'Name and unit_price required']); exit;
        }
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO medicines
                  (name, generic_name, manufacturer, dosage_form, strength,
                   unit_price, requires_prescription, category_id)
                VALUES (:name,:gname,:mfr,:form,:str,:price,:rx,:cat)
            ");
            $stmt->execute([
                ':name'  => trim($f['name']),
                ':gname' => $f['generic_name']  ?? null,
                ':mfr'   => $f['manufacturer']  ?? null,
                ':form'  => $f['dosage_form']   ?? null,
                ':str'   => $f['strength']      ?? null,
                ':price' => $f['unit_price'],
                ':rx'    => $f['requires_prescription'] ?? 1,
                ':cat'   => !empty($f['category_id']) ? $f['category_id'] : null,
            ]);
            $mid = $pdo->lastInsertId();
            $pdo->prepare("
                INSERT INTO inventory
                  (medicine_id, quantity_in_stock, reorder_level, expiry_date, last_restocked)
                VALUES (?, ?, ?, ?, NOW())
            ")->execute([
                $mid,
                (int)($f['quantity']     ?? 0),
                (int)($f['reorder_level']?? 50),
                !empty($f['expiry_date']) ? $f['expiry_date'] : null,
            ]);
            $pdo->commit();
            echo json_encode(['success' => true, 'medicine_id' => $mid]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
        $pdo->prepare("DELETE FROM medicines WHERE medicine_id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
