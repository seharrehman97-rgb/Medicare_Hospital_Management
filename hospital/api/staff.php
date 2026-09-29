<?php
require_once 'config.php';
$pdo    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        $role = $_GET['role'] ?? null;
        if ($role) {
            $stmt = $pdo->prepare("
                SELECT s.*, d.name AS department_name
                FROM staff s
                LEFT JOIN departments d ON d.department_id = s.department_id
                WHERE s.role = ?
                ORDER BY s.first_name
            ");
            $stmt->execute([$role]);
        } else {
            $stmt = $pdo->query("
                SELECT s.*, d.name AS department_name
                FROM staff s
                LEFT JOIN departments d ON d.department_id = s.department_id
                ORDER BY s.first_name
            ");
        }
        echo json_encode($stmt->fetchAll());
        break;

    case 'POST':
        $f = $_POST;
        $required = ['first_name','last_name','role','hire_date'];
        foreach ($required as $k) {
            if (empty($f[$k])) {
                echo json_encode(['success'=>false,'error'=>"$k is required"]); exit;
            }
        }
        $stmt = $pdo->prepare("
            INSERT INTO staff
              (first_name, last_name, role, specialization, qualification,
               email, phone, gender, date_of_birth, hire_date, salary, status, department_id)
            VALUES
              (:fn,:ln,:role,:spec,:qual,:email,:phone,:gender,:dob,:hire,:salary,:status,:dept)
        ");
        $stmt->execute([
            ':fn'     => trim($f['first_name']),
            ':ln'     => trim($f['last_name']),
            ':role'   => $f['role'],
            ':spec'   => $f['specialization']  ?? null,
            ':qual'   => $f['qualification']   ?? null,
            ':email'  => $f['email']           ?? null,
            ':phone'  => $f['phone']           ?? null,
            ':gender' => $f['gender']          ?? null,
            ':dob'    => $f['date_of_birth']   ?? null,
            ':hire'   => $f['hire_date'],
            ':salary' => $f['salary']          ?? null,
            ':status' => $f['status']          ?? 'Active',
            ':dept'   => $f['department_id']   ?? null,
        ]);
        echo json_encode(['success' => true, 'staff_id' => $pdo->lastInsertId()]);
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? null;
        if (!$id) { echo json_encode(['success'=>false,'error'=>'ID required']); exit; }
        $pdo->prepare("DELETE FROM staff WHERE staff_id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
