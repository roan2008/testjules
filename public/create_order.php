<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productionNumber = $_POST['ProductionNumber'] ?? '';
    $emptyTube = $_POST['EmptyTubeNumber'] ?? '';
    $projectID = $_POST['ProjectID'] ?? null;
    $modelID = $_POST['ModelID'] ?? null;

    // basic validation
    if (!$productionNumber) {
        $error = 'Production Number is required';
    } else {
        // check unique
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ProductionOrders WHERE ProductionNumber = ?');
        $stmt->execute([$productionNumber]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Production Number already exists';
        }
    }

    if (!$error) {
        // begin transaction
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO ProductionOrders (ProductionNumber, EmptyTubeNumber, ProjectID, ModelID) VALUES (?, ?, ?, ?)');
        $stmt->execute([$productionNumber, $emptyTube, $projectID, $modelID]);

        // insert liner usage
        if (!empty($_POST['liner'])) {
            $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
            foreach ($_POST['liner'] as $liner) {
                if (!$liner['LinerType']) continue;
                $luStmt->execute([$productionNumber, $liner['LinerType'], $liner['LinerBatchNumber'], $liner['Remarks']]);
            }
        }

        // insert process log
        if (!empty($_POST['log'])) {
            $logStmt = $pdo->prepare('INSERT INTO MC02_ProcessLog (ProductionNumber, SequenceNo, ProcessStepName, DatePerformed, Result, Operator_UserID, Remarks, ControlValue, ActualMeasuredValue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            foreach ($_POST['log'] as $log) {
                $logStmt->execute([
                    $productionNumber,
                    $log['SequenceNo'],
                    $log['ProcessStepName'],
                    $log['DatePerformed'] ?: null,
                    $log['Result'] ?: null,
                    $log['Operator_UserID'] ?: null,
                    $log['Remarks'] ?: null,
                    $log['ControlValue'] ?: null,
                    $log['ActualMeasuredValue'] ?: null,
                ]);
            }
        }

        $pdo->commit();
        header('Location: view_order.php?pn=' . urlencode($productionNumber));
        exit;
    }
}

// fetch projects
$projects = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);
// fetch users for operator dropdown
$users = $pdo->query('SELECT UserID, FullName FROM Users ORDER BY FullName')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Create Order</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 1em; }
        .error { color: red; }
        table { border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px; }
    </style>
    <script>
    function loadModels(projectId) {
        const modelSelect = document.getElementById('model');
        modelSelect.innerHTML = '';
        fetch('models.php?project_id=' + projectId)
            .then(r => r.json())
            .then(data => {
                data.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.ModelID;
                    opt.textContent = m.ModelName;
                    modelSelect.appendChild(opt);
                });
            });
    }
    </script>
</head>
<body>
    <h1>Create Production Order</h1>
    <?php if ($error): ?><p class="error"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
    <form method="post">
        <label>Production Number: <input type="text" name="ProductionNumber" required></label><br><br>
        <label>Empty Tube Number: <input type="text" name="EmptyTubeNumber"></label><br><br>
        <label>Project:
            <select name="ProjectID" required onchange="loadModels(this.value)">
                <option value="">--Select--</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?php echo $p['ProjectID']; ?>"><?php echo htmlspecialchars($p['ProjectName']); ?></option>
                <?php endforeach; ?>
            </select>
        </label><br><br>
        <label>Model:
            <select name="ModelID" id="model" required>
                <option value="">--Select Project First--</option>
            </select>
        </label>
        <h2>Liner Usage</h2>
        <table id="liner-table">
            <tr><th>Type</th><th>Batch Number</th><th>Remarks</th></tr>
            <tr>
                <td><input type="text" name="liner[0][LinerType]"></td>
                <td><input type="text" name="liner[0][LinerBatchNumber]"></td>
                <td><input type="text" name="liner[0][Remarks]"></td>
            </tr>
        </table>
        <button type="button" onclick="addLinerRow()">Add Liner</button>

        <h2>Process Log</h2>
        <table id="log-table">
            <tr><th>Seq</th><th>Step Name</th><th>Date</th><th>Result</th><th>Operator</th><th>Control</th><th>Actual</th><th>Remarks</th></tr>
            <?php for ($i = 1; $i <= 16; $i++): ?>
            <tr>
                <td><input type="number" name="log[<?php echo $i; ?>][SequenceNo]" value="<?php echo $i; ?>" readonly></td>
                <td><input type="text" name="log[<?php echo $i; ?>][ProcessStepName]" required></td>
                <td><input type="date" name="log[<?php echo $i; ?>][DatePerformed]"></td>
                <td>
                    <select name="log[<?php echo $i; ?>][Result]">
                        <option value="">--</option>
                        <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                        <option value="✗ แก้ไข">✗ แก้ไข</option>
                    </select>
                </td>
                <td>
                    <select name="log[<?php echo $i; ?>][Operator_UserID]">
                        <option value="">--</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" step="0.001" name="log[<?php echo $i; ?>][ControlValue]"></td>
                <td><input type="number" step="0.001" name="log[<?php echo $i; ?>][ActualMeasuredValue]"></td>
                <td><input type="text" name="log[<?php echo $i; ?>][Remarks]"></td>
            </tr>
            <?php endfor; ?>
        </table>
        <button type="submit">Save</button>
    </form>

<script>
function addLinerRow() {
    const table = document.getElementById('liner-table');
    const rowCount = table.rows.length;
    const row = table.insertRow();
    row.innerHTML = `<td><input type="text" name="liner[${rowCount-1}][LinerType]"></td>
                     <td><input type="text" name="liner[${rowCount-1}][LinerBatchNumber]"></td>
                     <td><input type="text" name="liner[${rowCount-1}][Remarks]"></td>`;
}
</script>
</body>
</html>
