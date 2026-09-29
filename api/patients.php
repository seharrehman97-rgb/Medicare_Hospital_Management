<?php
require_once 'config.php';
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET: list all patients ──────────────────────────────
    case 'GET':
        $sql  = "SELECT * FROM patients ORDER BY created_at DESC";
        $stmt = $pdo->query($sql);
        echo json_encode($stmt->fetchAll());
        break;

    // ── POST: create patient ────────────────────────────────
    case 'POST':
        $f = $_POST;
        $required = ['first_name','last_name','date_of_birth','gender'];
        foreach ($required as $key) {
            if (empty($f[$key])) {
                echo json_encode(['success'=>false,'error'=>"$key is required"]); exit;
            }
        }
        $stmt = $pdo->prepare("
            INSERT INTO patients
              (first_name, last_name, date_of_birth, gender, blood_group,
               phone, email, address, city,
               emergency_contact_name, emergency_contact_phone)
            VALUES
              (:first_name, :last_name, :date_of_birth, :gender, :blood_group,
               :phone, :email, :address, :city,
               :emergency_contact_name, :emergency_contact_phone)
        ");
        $stmt->execute([
            ':first_name'              => trim($f['first_name']),
            ':last_name'               => trim($f['last_name']),
            ':date_of_birth'           => $f['date_of_birth'],
            ':gender'                  => $f['gender'],
            ':blood_group'             => $f['blood_group']  ?? null,
            ':phone'                   => $f['phone']        ?? null,
            ':email'                   => $f['email']        ?? null,
            ':address'                 => $f['address']      ?? null,
            ':city'                    => $f['city']         ?? null,
            ':emergency_contact_name'  => $f['emergency_contact_name']  ?? null,
            ':emergency_contact_phone' => $f['emergency_contact_phone'] ?? null,
        ]);
        echo json_encode(['success' => true, 'patient_id' => $pdo->lastInsertId()]);
        break;

    // ── PUT: update patient ─────────────────────────────────
    case 'PUT':
        parse_str(file_get_contents('php://input'), $f);
        $id = $_GET['id'] ?? $f['patient_id'] ?? null;
        if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
        $stmt = $pdo->prepare("
            UPDATE patients SET
              first_name=:first_name, last_name=:last_name,
              date_of_birth=:dob, gender=:gender,
              blood_group=:blood_group, phone=:phone, email=:email,
              address=:address, city=:city
            WHERE patient_id=:id
        ");
        $stmt->execute([
            ':first_name'  => $f['first_name'],
            ':last_name'   => $f['last_name'],
            ':dob'         => $f['date_of_birth'],
            ':gender'      => $f['gender'],
            ':blood_group' => $f['blood_group'] ?? null,
            ':phone'       => $f['phone']       ?? null,
            ':email'       => $f['email']       ?? null,
            ':address'     => $f['address']     ?? null,
            ':city'        => $f['city']        ?? null,
            ':id'          => $id,
        ]);
        echo json_encode(['success' => true]);
        break;

    // ── DELETE: remove patient ──────────────────────────────
    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
        $pdo->prepare("DELETE FROM patients WHERE patient_id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
