<?php
require_once 'config.php';

$type = $_GET['type'] ?? '';
$pdo  = getDB();

switch ($type) {
    case 'patients':
        $count = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
        echo json_encode(['count' => $count]);
        break;

    case 'staff':
        $count = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'Active'")->fetchColumn();
        echo json_encode(['count' => $count]);
        break;

    case 'appointments':
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE DATE(scheduled_at) = CURDATE()");
        $stmt->execute();
        echo json_encode(['count' => $stmt->fetchColumn()]);
        break;

    case 'lowstock':
        $count = $pdo->query(
            "SELECT COUNT(*) FROM inventory WHERE quantity_in_stock <= reorder_level"
        )->fetchColumn();
        echo json_encode(['count' => $count]);
        break;

    default:
        echo json_encode(['error' => 'Unknown stat type']);
}
