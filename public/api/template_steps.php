<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../src/Database.php';

try {
    $pdo = Database::connect();
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);
      switch($method) {
        case 'POST':
            // Check if this is a replace_all action
            if(isset($input['action']) && $input['action'] === 'replace_all') {
                if(!isset($input['template_id']) || !isset($input['steps'])) {
                    throw new Exception('Missing template_id or steps');
                }
                
                $templateId = $input['template_id'];
                $steps = $input['steps'];
                
                // Start transaction
                $pdo->beginTransaction();
                
                try {
                    // Delete all existing steps for this template
                    $stmt = $pdo->prepare('DELETE FROM ProcessTemplateSteps WHERE TemplateID = ?');
                    $stmt->execute([$templateId]);
                    
                    // Insert new steps
                    $stmt = $pdo->prepare('INSERT INTO ProcessTemplateSteps (TemplateID, ProcessName, StepOrder, IsRequired, EstimatedDuration) VALUES (?, ?, ?, ?, ?)');
                    
                    foreach($steps as $step) {
                        $stmt->execute([
                            $templateId,
                            $step['ProcessName'],
                            $step['StepOrder'],
                            $step['IsRequired'] ? 1 : 0,
                            $step['EstimatedDuration'] ?: null
                        ]);
                    }
                    
                    $pdo->commit();
                    echo json_encode(['success' => true, 'message' => 'Process steps updated successfully']);
                } catch(Exception $e) {
                    $pdo->rollback();
                    throw $e;
                }
            } else {
                // Create new process step (original code)
                if(!isset($input['TemplateID']) || !isset($input['ProcessName']) || !isset($input['StepOrder'])) {
                    throw new Exception('Missing required fields');
                }
                
                $stmt = $pdo->prepare('INSERT INTO ProcessTemplateSteps (TemplateID, ProcessName, StepOrder, IsRequired, EstimatedDuration) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $input['TemplateID'],
                    $input['ProcessName'],
                    $input['StepOrder'],
                    isset($input['IsRequired']) ? ($input['IsRequired'] ? 1 : 0) : 1,
                    isset($input['EstimatedDuration']) ? $input['EstimatedDuration'] : null
                ]);
                
                echo json_encode(['success' => true, 'message' => 'Process step created successfully']);
            }
            break;
            
        case 'GET':
            // Get process steps for a template
            if(isset($_GET['template_id'])) {
                $stmt = $pdo->prepare('SELECT * FROM ProcessTemplateSteps WHERE TemplateID = ? ORDER BY StepOrder');
                $stmt->execute([$_GET['template_id']]);
                $steps = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'data' => $steps]);
            } else {
                throw new Exception('Template ID required');
            }
            break;
            
        case 'PUT':
            // Update process step
            if(!isset($_GET['id'])) {
                throw new Exception('Step ID required');
            }
            
            $updateFields = [];
            $params = [];
            
            if(isset($input['ProcessName'])) {
                $updateFields[] = 'ProcessName = ?';
                $params[] = $input['ProcessName'];
            }
            
            if(isset($input['StepOrder'])) {
                $updateFields[] = 'StepOrder = ?';
                $params[] = $input['StepOrder'];
            }
              if(isset($input['IsRequired'])) {
                $updateFields[] = 'IsRequired = ?';
                $params[] = $input['IsRequired'] ? 1 : 0;
            }
            
            if(isset($input['EstimatedDuration'])) {
                $updateFields[] = 'EstimatedDuration = ?';
                $params[] = $input['EstimatedDuration'] ?: null;
            }
            
            if(empty($updateFields)) {
                throw new Exception('No fields to update');
            }
            
            $params[] = $_GET['id'];
            $stmt = $pdo->prepare('UPDATE ProcessTemplateSteps SET ' . implode(', ', $updateFields) . ' WHERE TemplateStepID = ?');
            $stmt->execute($params);
            
            echo json_encode(['success' => true, 'message' => 'Process step updated successfully']);
            break;
            
        case 'DELETE':
            // Delete process step
            if(!isset($_GET['id'])) {
                throw new Exception('Step ID required');
            }
            
            $stmt = $pdo->prepare('DELETE FROM ProcessTemplateSteps WHERE TemplateStepID = ?');
            $stmt->execute([$_GET['id']]);
            
            echo json_encode(['success' => true, 'message' => 'Process step deleted successfully']);
            break;
            
        default:
            throw new Exception('Method not allowed');
    }
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
