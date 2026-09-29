<?php
require_once 'config.php';
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $status = $_GET['status'] ?? null;
        $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        if ($status) {
            $stmt = $pdo->prepare("
                SELECT a.*,
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       w.name AS ward_name
                FROM admissions a
                JOIN patients p ON p.patient_id = a.patient_id
                LEFT JOIN wards w ON w.ward_id = a.ward_id
                WHERE a.status = ?
                ORDER BY a.admission_date DESC
                LIMIT $limit
            ");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->prepare("
                SELECT a.*,
                       CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                       w.name AS ward_name
                FROM admissions a
                JOIN patients p ON p.patient_id = a.patient_id
                LEFT JOIN wards w ON w.ward_id = a.ward_id
                ORDER BY a.admission_date DESC
                LIMIT $limit
            ");
            $stmt->execute();
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $f = $_POST;
        if (empty($f['patient_id'])) {
            echo json_encode(['success'=>false,'error'=>'patient_id required']); exit;
        }
        $stmt = $pdo->prepare("
            INSERT INTO admissions
              (patient_id, ward_id, attending_doctor_id, reason, diagnosis, status)
            VALUES (:pid, :wid, :did, :reason, :diag, :status)
        ");
        $stmt->execute([
            ':pid'    => $f['patient_id'],
            ':wid'    => $f['ward_id']              ?? null,
            ':did'    => $f['attending_doctor_id']  ?? null,
            ':reason' => $f['reason']               ?? null,
            ':diag'   => $f['diagnosis']            ?? null,
            ':status' => $f['status']               ?? 'Admitted',
        ]);
        echo json_encode(['success' => true, 'admission_id' => $pdo->lastInsertId()]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
        $pdo->prepare("UPDATE admissions SET status='Discharged', discharge_date=NOW() WHERE admission_id=?")
            ->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
