<?php
require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();
$projectId = $_GET['project_id'] ?? 0;
$stmt = $pdo->prepare('SELECT ModelID, ModelName FROM Models WHERE ProjectID = ? ORDER BY ModelName');
$stmt->execute([$projectId]);
header('Content-Type: application/json');
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

