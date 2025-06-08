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
        $projectId = $_GET['project'] ?? null;
        $modelId = $_GET['model'] ?? null;
        if ($projectId || $modelId) {
            $stmt = $pdo->prepare('SELECT * FROM ProcessTemplates WHERE (ProjectID=? OR ? IS NULL) AND (ModelID=? OR ? IS NULL) ORDER BY TemplateName');
            $stmt->execute([$projectId, $projectId, $modelId, $modelId]);
        } else {
            $stmt = $pdo->query('SELECT * FROM ProcessTemplates ORDER BY TemplateName');
        }
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($projectId && $modelId && !isset($_GET['list'])) {
            // return first template (default) with steps
            if ($templates) {
                $tpl = $templates[0];
                $s = $pdo->prepare('SELECT * FROM ProcessTemplateSteps WHERE TemplateID=? ORDER BY StepOrder');
                $s->execute([$tpl['TemplateID']]);
                $tpl['steps'] = $s->fetchAll(PDO::FETCH_ASSOC);
                sendJsonResponse(true, $tpl, 'OK');
            } else {
                sendJsonResponse(true, null, 'No template');
            }
        } else {
            sendJsonResponse(true, $templates, 'OK');
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['TemplateName'] ?? '');
        $projectId = $data['ProjectID'] ?? null;
        $modelId = $data['ModelID'] ?? null;
        if ($name === '') {
            sendJsonResponse(false, null, 'Template name required', 422);
        }
        $stmt = $pdo->prepare('INSERT INTO ProcessTemplates (TemplateName, ProjectID, ModelID, IsDefault, CreatedBy) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $projectId, $modelId, !empty($data['IsDefault']) ? 1 : 0, $_SESSION['UserID']]);
        $templateId = $pdo->lastInsertId();
        if (!empty($data['steps']) && is_array($data['steps'])) {
            $stepStmt = $pdo->prepare('INSERT INTO ProcessTemplateSteps (TemplateID, ProcessName, StepOrder, IsRequired, EstimatedDuration) VALUES (?, ?, ?, ?, ?)');
            foreach ($data['steps'] as $i => $step) {
                $stepStmt->execute([$templateId, $step['ProcessName'], $i+1, !empty($step['IsRequired']) ? 1 : 0, $step['EstimatedDuration'] ?? null]);
            }
        }
        sendJsonResponse(true, ['TemplateID' => $templateId], 'Template created', 201);
        break;

    case 'PUT':
        $id = $_GET['id'] ?? 0;
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$id) {
            sendJsonResponse(false, null, 'Invalid ID', 400);
        }
        $name = trim($data['TemplateName'] ?? '');
        $projectId = $data['ProjectID'] ?? null;
        $modelId = $data['ModelID'] ?? null;
        $isDefault = !empty($data['IsDefault']) ? 1 : 0;
        $pdo->prepare('UPDATE ProcessTemplates SET TemplateName=?, ProjectID=?, ModelID=?, IsDefault=? WHERE TemplateID=?')
            ->execute([$name, $projectId, $modelId, $isDefault, $id]);
        // Replace steps if provided
        if (isset($data['steps'])) {
            $pdo->prepare('DELETE FROM ProcessTemplateSteps WHERE TemplateID=?')->execute([$id]);
            $stepStmt = $pdo->prepare('INSERT INTO ProcessTemplateSteps (TemplateID, ProcessName, StepOrder, IsRequired, EstimatedDuration) VALUES (?, ?, ?, ?, ?)');
            foreach ($data['steps'] as $i => $step) {
                $stepStmt->execute([$id, $step['ProcessName'], $i+1, !empty($step['IsRequired']) ? 1 : 0, $step['EstimatedDuration'] ?? null]);
            }
        }
        sendJsonResponse(true, null, 'Template updated');
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? 0;
        if (!$id) {
            sendJsonResponse(false, null, 'Invalid ID', 400);
        }
        $pdo->prepare('DELETE FROM ProcessTemplateSteps WHERE TemplateID=?')->execute([$id]);
        $pdo->prepare('DELETE FROM ProcessTemplates WHERE TemplateID=?')->execute([$id]);
        sendJsonResponse(true, null, 'Template deleted');
        break;

    default:
        sendJsonResponse(false, null, 'Method not allowed', 405);
}
?>
