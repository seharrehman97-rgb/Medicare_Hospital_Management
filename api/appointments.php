<?php
require_once 'config.php';
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

switch ($method) {

    case 'GET':
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
        $stmt  = $pdo->prepare("
            SELECT a.*,
                   CONCAT(p.first_name,' ',p.last_name) AS patient_name,
                   CONCAT(s.first_name,' ',s.last_name) AS doctor_name
            FROM appointments a
            JOIN patients p ON p.patient_id = a.patient_id
            JOIN staff    s ON s.staff_id   = a.doctor_id
            ORDER BY a.scheduled_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        if ($action === 'status') {
            $id     = $_POST['appointment_id'] ?? null;
            $status = $_POST['status']         ?? null;
            $allowed = ['Scheduled','Confirmed','Completed','Cancelled','No-Show'];
            if (!$id || !in_array($status, $allowed)) {
                echo json_encode(['success'=>false,'error'=>'Invalid data']); exit;
            }
            $pdo->prepare("UPDATE appointments SET status=? WHERE appointment_id=?")
                ->execute([$status, $id]);
            echo json_encode(['success' => true]);
            break;
        }
        $f = $_POST;
        $required = ['patient_id','doctor_id','scheduled_at'];
        foreach ($required as $k) {
            if (empty($f[$k])) {
                echo json_encode(['success'=>false,'error'=>"$k is required"]); exit;
            }
        }
        $stmt = $pdo->prepare("
            INSERT INTO appointments
              (patient_id, doctor_id, scheduled_at, duration_minutes, type, status, symptoms, notes)
            VALUES
              (:pid, :did, :sched, :dur, :type, :status, :symptoms, :notes)
        ");
        $stmt->execute([
            ':pid'      => $f['patient_id'],
            ':did'      => $f['doctor_id'],
            ':sched'    => $f['scheduled_at'],
            ':dur'      => $f['duration_minutes'] ?? 30,
            ':type'     => $f['type']     ?? 'Consultation',
            ':status'   => $f['status']   ?? 'Scheduled',
            ':symptoms' => $f['symptoms'] ?? null,
            ':notes'    => $f['notes']    ?? null,
        ]);
        echo json_encode(['success' => true, 'appointment_id' => $pdo->lastInsertId()]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
        $pdo->prepare("UPDATE appointments SET status='Cancelled' WHERE appointment_id=?")
            ->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
