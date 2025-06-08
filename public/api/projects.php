<?php
session_start();
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/ApiUtils.php';
$pdo = Database::connect();

if (!isset($_SESSION['UserID'])) {
    sendJsonResponse(false, null, 'Authentication required', 401);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $stmt = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName');
        sendJsonResponse(true, $stmt->fetchAll(PDO::FETCH_ASSOC), 'OK');
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['ProjectName'] ?? '');
        if ($name === '') {
            sendJsonResponse(false, null, 'Project name is required', 422);
        }
        $stmt = $pdo->prepare('INSERT INTO Projects (ProjectName) VALUES (?)');
        $stmt->execute([$name]);
        sendJsonResponse(true, ['ProjectID' => $pdo->lastInsertId()], 'Project created', 201);
        break;

    case 'PUT':
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['ProjectName'] ?? '');
        if (!$id || $name === '') {
            sendJsonResponse(false, null, 'Invalid data', 400);
        }
        $stmt = $pdo->prepare('UPDATE Projects SET ProjectName=? WHERE ProjectID=?');
        $stmt->execute([$name, $id]);
        sendJsonResponse(true, null, 'Project updated');
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            sendJsonResponse(false, null, 'Invalid ID', 400);
        }
        $stmt = $pdo->prepare('DELETE FROM Projects WHERE ProjectID=?');
        $stmt->execute([$id]);
        sendJsonResponse(true, null, 'Project deleted');
        break;

    default:
        sendJsonResponse(false, null, 'Method not allowed', 405);
}
?>
