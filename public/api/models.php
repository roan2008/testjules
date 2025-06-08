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
        $projectId = $_GET['project_id'] ?? null;
        if ($projectId) {
            $stmt = $pdo->prepare('SELECT ModelID, ModelName FROM Models WHERE ProjectID=? ORDER BY ModelName');
            $stmt->execute([$projectId]);
        } else {
            $stmt = $pdo->query('SELECT ModelID, ProjectID, ModelName FROM Models ORDER BY ModelName');
        }
        sendJsonResponse(true, $stmt->fetchAll(PDO::FETCH_ASSOC), 'OK');
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['ModelName'] ?? '');
        $projectId = (int)($data['ProjectID'] ?? 0);
        if ($name === '' || !$projectId) {
            sendJsonResponse(false, null, 'Invalid data', 422);
        }
        $stmt = $pdo->prepare('INSERT INTO Models (ProjectID, ModelName) VALUES (?, ?)');
        $stmt->execute([$projectId, $name]);
        sendJsonResponse(true, ['ModelID' => $pdo->lastInsertId()], 'Model created', 201);
        break;

    case 'PUT':
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['ModelName'] ?? '');
        $projectId = (int)($data['ProjectID'] ?? 0);
        if (!$id || $name === '' || !$projectId) {
            sendJsonResponse(false, null, 'Invalid data', 400);
        }
        $stmt = $pdo->prepare('UPDATE Models SET ProjectID=?, ModelName=? WHERE ModelID=?');
        $stmt->execute([$projectId, $name, $id]);
        sendJsonResponse(true, null, 'Model updated');
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            sendJsonResponse(false, null, 'Invalid ID', 400);
        }
        $stmt = $pdo->prepare('DELETE FROM Models WHERE ModelID=?');
        $stmt->execute([$id]);
        sendJsonResponse(true, null, 'Model deleted');
        break;

    default:
        sendJsonResponse(false, null, 'Method not allowed', 405);
}
?>
