<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();
$pn = $_GET['pn'] ?? '';

// Fetch existing order data
$stmt = $pdo->prepare('SELECT po.*, p.ProjectName, m.ModelName
                       FROM ProductionOrders po
                       LEFT JOIN Projects p ON po.ProjectID = p.ProjectID
                       LEFT JOIN Models m ON po.ModelID = m.ModelID
                       WHERE po.ProductionNumber = ?');
$stmt->execute([$pn]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo 'Order not found';
    exit;
}

// Fetch projects and models for dropdowns
$projects = $pdo->query('SELECT * FROM Projects')->fetchAll(PDO::FETCH_ASSOC);
$models = $pdo->query('SELECT * FROM Models')->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing liner usage
$stmt = $pdo->prepare('SELECT * FROM MC02_LinerUsage WHERE ProductionNumber = ?');
$stmt->execute([$pn]);
$liners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing process logs
$stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
$stmt->execute([$pn]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emptyTube = $_POST['EmptyTubeNumber'] ?? '';
    $projectID = $_POST['ProjectID'] ?? null;
    $modelID = $_POST['ModelID'] ?? null;
    $status = $_POST['MC02_Status'] ?? '';

    if (!$error) {
        $pdo->beginTransaction();
        try {
            // Update main order
            $stmt = $pdo->prepare('UPDATE ProductionOrders SET EmptyTubeNumber = ?, ProjectID = ?, ModelID = ?, MC02_Status = ? WHERE ProductionNumber = ?');
            $stmt->execute([$emptyTube, $projectID, $modelID, $status, $pn]);

            // Delete existing liner usage and insert new ones
            $pdo->prepare('DELETE FROM MC02_LinerUsage WHERE ProductionNumber = ?')->execute([$pn]);
            if (!empty($_POST['liner'])) {
                $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
                foreach ($_POST['liner'] as $liner) {
                    if (!empty($liner['LinerType'])) {
                        $luStmt->execute([$pn, $liner['LinerType'], $liner['LinerBatchNumber'], $liner['Remarks']]);
                    }
                }
            }

            // Delete existing process logs and insert new ones
            $pdo->prepare('DELETE FROM MC02_ProcessLog WHERE ProductionNumber = ?')->execute([$pn]);
            if (!empty($_POST['log'])) {
                $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                foreach ($_POST['log'] as $log) {
                    if (!empty($log['ProcessStepName'])) {
                        $logStmt->execute([
                            $pn,
                            $log['SequenceNo'],
                            $log['ProcessStepName'],
                            $log['DatePerformed'] ?: null,
                            $log['Result'],
                            $log['Operator_UserID'] ?: null,
                            $log['Remarks'],
                            $log['ControlValue'] ?: null,
                            $log['ActualMeasuredValue'] ?: null
                        ]);
                    }
                }
            }

            $pdo->commit();
            $success = 'Order updated successfully!';
            
            // Refresh data
            $stmt = $pdo->prepare('SELECT * FROM ProductionOrders WHERE ProductionNumber = ?');
            $stmt->execute([$pn]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare('SELECT * FROM MC02_LinerUsage WHERE ProductionNumber = ?');
            $stmt->execute([$pn]);
            $liners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
            $stmt->execute([$pn]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error updating order: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Order</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        table { border-collapse: collapse; width: 100%; margin: 1em 0; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        input, select, textarea { width: 100%; padding: 4px; }
        .error { color: red; }
        .success { color: green; }
        .btn { padding: 8px 16px; margin: 4px; cursor: pointer; }
        .btn-primary { background-color: #007bff; color: white; border: none; }
        .btn-secondary { background-color: #6c757d; color: white; border: none; }
        .btn-success { background-color: #28a745; color: white; border: none; }
    </style>
</head>
<body>
    <p><a href="view_order.php?pn=<?php echo urlencode($pn); ?>" class="btn btn-secondary">← Back to View</a> | <a href="index.php" class="btn btn-secondary">Back to List</a></p>
    
    <h1>Edit Order: <?php echo htmlspecialchars($order['ProductionNumber']); ?></h1>
    
    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <form method="post">
        <h2>Basic Information</h2>
        <table>
            <tr>
                <td><strong>Production Number:</strong></td>
                <td><?php echo htmlspecialchars($order['ProductionNumber']); ?> (Cannot be changed)</td>
            </tr>
            <tr>
                <td><strong>Empty Tube Number:</strong></td>
                <td><input type="text" name="EmptyTubeNumber" value="<?php echo htmlspecialchars($order['EmptyTubeNumber']); ?>"></td>
            </tr>
            <tr>
                <td><strong>Project:</strong></td>
                <td>
                    <select name="ProjectID">
                        <option value="">Select Project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['ProjectID']; ?>" <?php echo ($project['ProjectID'] == $order['ProjectID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($project['ProjectName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><strong>Model:</strong></td>
                <td>
                    <select name="ModelID">
                        <option value="">Select Model</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?php echo $model['ModelID']; ?>" <?php echo ($model['ModelID'] == $order['ModelID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($model['ModelName']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <select name="MC02_Status">
                        <option value="Pending" <?php echo ($order['MC02_Status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo ($order['MC02_Status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo ($order['MC02_Status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="On Hold" <?php echo ($order['MC02_Status'] == 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
                    </select>
                </td>
            </tr>
        </table>

        <h2>Liner Usage</h2>
        <table id="linerTable">
            <thead>
                <tr>
                    <th>Liner Type</th>
                    <th>Batch Number</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($liners)): ?>
                    <?php foreach ($liners as $index => $liner): ?>
                        <tr>
                            <td><input type="text" name="liner[<?php echo $index; ?>][LinerType]" value="<?php echo htmlspecialchars($liner['LinerType']); ?>"></td>
                            <td><input type="text" name="liner[<?php echo $index; ?>][LinerBatchNumber]" value="<?php echo htmlspecialchars($liner['LinerBatchNumber']); ?>"></td>
                            <td><input type="text" name="liner[<?php echo $index; ?>][Remarks]" value="<?php echo htmlspecialchars($liner['Remarks']); ?>"></td>
                            <td><button type="button" onclick="removeRow(this)" class="btn btn-secondary">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><input type="text" name="liner[0][LinerType]"></td>
                        <td><input type="text" name="liner[0][LinerBatchNumber]"></td>
                        <td><input type="text" name="liner[0][Remarks]"></td>
                        <td><button type="button" onclick="removeRow(this)" class="btn btn-secondary">Remove</button></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button type="button" onclick="addLinerRow()" class="btn btn-primary">Add Liner Row</button>

        <h2>Process Log</h2>
        <table id="logTable">
            <thead>
                <tr>
                    <th>Seq</th>
                    <th>Process Step</th>
                    <th>Date</th>
                    <th>Result</th>
                    <th>Operator ID</th>
                    <th>Control Value</th>
                    <th>Actual Value</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $index => $log): ?>
                        <tr>
                            <td><input type="number" name="log[<?php echo $index; ?>][SequenceNo]" value="<?php echo $log['SequenceNo']; ?>" style="width: 60px;"></td>
                            <td><input type="text" name="log[<?php echo $index; ?>][ProcessStepName]" value="<?php echo htmlspecialchars($log['ProcessStepName']); ?>"></td>
                            <td><input type="date" name="log[<?php echo $index; ?>][DatePerformed]" value="<?php echo $log['DatePerformed']; ?>"></td>
                            <td><input type="text" name="log[<?php echo $index; ?>][Result]" value="<?php echo htmlspecialchars($log['Result']); ?>"></td>
                            <td><input type="number" name="log[<?php echo $index; ?>][Operator_UserID]" value="<?php echo $log['Operator_UserID']; ?>" style="width: 80px;"></td>
                            <td><input type="number" step="0.001" name="log[<?php echo $index; ?>][ControlValue]" value="<?php echo $log['ControlValue']; ?>" style="width: 90px;"></td>
                            <td><input type="number" step="0.001" name="log[<?php echo $index; ?>][ActualMeasuredValue]" value="<?php echo $log['ActualMeasuredValue']; ?>" style="width: 90px;"></td>
                            <td><textarea name="log[<?php echo $index; ?>][Remarks]" rows="2"><?php echo htmlspecialchars($log['Remarks']); ?></textarea></td>
                            <td><button type="button" onclick="removeRow(this)" class="btn btn-secondary">Remove</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><input type="number" name="log[0][SequenceNo]" value="1" style="width: 60px;"></td>
                        <td><input type="text" name="log[0][ProcessStepName]"></td>
                        <td><input type="date" name="log[0][DatePerformed]"></td>
                        <td><input type="text" name="log[0][Result]"></td>
                        <td><input type="number" name="log[0][Operator_UserID]" style="width: 80px;"></td>
                        <td><input type="number" step="0.001" name="log[0][ControlValue]" style="width: 90px;"></td>
                        <td><input type="number" step="0.001" name="log[0][ActualMeasuredValue]" style="width: 90px;"></td>
                        <td><textarea name="log[0][Remarks]" rows="2"></textarea></td>
                        <td><button type="button" onclick="removeRow(this)" class="btn btn-secondary">Remove</button></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <button type="button" onclick="addLogRow()" class="btn btn-primary">Add Process Log Row</button>

        <br><br>
        <button type="submit" class="btn btn-success">Update Order</button>
        <a href="view_order.php?pn=<?php echo urlencode($pn); ?>" class="btn btn-secondary">Cancel</a>
    </form>

    <script>
        let linerRowCount = <?php echo max(1, count($liners)); ?>;
        let logRowCount = <?php echo max(1, count($logs)); ?>;

        function addLinerRow() {
            const table = document.getElementById('linerTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="text" name="liner[${linerRowCount}][LinerType]"></td>
                <td><input type="text" name="liner[${linerRowCount}][LinerBatchNumber]"></td>
                <td><input type="text" name="liner[${linerRowCount}][Remarks]"></td>
                <td><button type="button" onclick="removeRow(this)" class="btn btn-secondary">Remove</button></td>
            `;
            linerRowCount++;
        }

        function addLogRow() {
            const table = document.getElementById('logTable').getElementsByTagName('tbody')[0];
            const newRow = table.insertRow();
            newRow.innerHTML = `
                <td><input type="number" name="log[${logRowCount}][SequenceNo]" value="${logRowCount + 1}" style="width: 60px;"></td>
                <td><input type="text" name="log[${logRowCount}][ProcessStepName]"></td>
                <td><input type="date" name="log[${logRowCount}][DatePerformed]"></td>
                <td><input type="text" name="log[${logRowCount}][Result]"></td>
                <td><input type="number" name="log[${logRowCount}][Operator_UserID]" style="width: 80px;"></td>
                <td><input type="number" step="0.001" name="log[${logRowCount}][ControlValue]" style="width: 90px;"></td>
                <td><input type="number" step="0.001" name="log[${logRowCount}][ActualMeasuredValue]" style="width: 90px;"></td>
                <td><textarea name="log[${logRowCount}][Remarks]" rows="2"></textarea></td>
                <td><button type="button" onclick="removeRow(this)" class="btn btn-secondary">Remove</button></td>
            `;
            logRowCount++;
        }

        function removeRow(button) {
            const row = button.closest('tr');
            row.remove();
        }
    </script>
</body>
</html>
