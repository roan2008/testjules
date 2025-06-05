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

    if (!$productionNumber) {
        $error = 'Production Number is required';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM ProductionOrders WHERE ProductionNumber = ?');
        $stmt->execute([$productionNumber]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Production Number already exists';
        }
    }

    if (!$error) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO ProductionOrders (ProductionNumber, EmptyTubeNumber, ProjectID, ModelID) VALUES (?, ?, ?, ?)');
        $stmt->execute([$productionNumber, $emptyTube, $projectID, $modelID]);

        if (!empty($_POST['liner'])) {
            $luStmt = $pdo->prepare('INSERT INTO MC02_LinerUsage (ProductionNumber, LinerType, LinerBatchNumber, Remarks) VALUES (?, ?, ?, ?)');
            foreach ($_POST['liner'] as $liner) {
                if (!$liner['LinerType']) continue;
                $luStmt->execute([$productionNumber, $liner['LinerType'], $liner['LinerBatchNumber'], $liner['Remarks']]);
            }
        }

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

$projects = $pdo->query('SELECT ProjectID, ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query('SELECT UserID, FullName FROM Users ORDER BY FullName')->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Create Order';
include 'templates/header.php';
?>
<h1>Create Production Order</h1>
<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<form method="post">
    <div class="mb-3">
        <label class="form-label">Production Number</label>
        <input type="text" name="ProductionNumber" class="form-control" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Empty Tube Number</label>
        <input type="text" name="EmptyTubeNumber" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Project</label>
        <select name="ProjectID" class="form-select" required onchange="loadModels(this.value)">
            <option value="">--Select--</option>
            <?php foreach ($projects as $p): ?>
                <option value="<?php echo $p['ProjectID']; ?>"><?php echo htmlspecialchars($p['ProjectName']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Model</label>
        <select name="ModelID" id="model" class="form-select" required>
            <option value="">--Select Project First--</option>
        </select>
    </div>
    <h2>Liner Usage</h2>
    <table id="liner-table" class="table">
        <tr><th>Type</th><th>Batch Number</th><th>Remarks</th></tr>
        <tr>
            <td><input type="text" name="liner[0][LinerType]" class="form-control"></td>
            <td><input type="text" name="liner[0][LinerBatchNumber]" class="form-control"></td>
            <td><input type="text" name="liner[0][Remarks]" class="form-control"></td>
        </tr>
    </table>
    <button type="button" class="btn btn-secondary mb-3" onclick="addLinerRow()">Add Liner</button>

    <h2>Process Log</h2>
    <table id="log-table" class="table">
        <tr><th>Seq</th><th>Step Name</th><th>Date</th><th>Result</th><th>Operator</th><th>Control</th><th>Actual</th><th>Remarks</th></tr>
        <?php for ($i = 1; $i <= 16; $i++): ?>
        <tr>
            <td><input type="number" name="log[<?php echo $i; ?>][SequenceNo]" value="<?php echo $i; ?>" readonly class="form-control"></td>
            <td><input type="text" name="log[<?php echo $i; ?>][ProcessStepName]" required class="form-control"></td>
            <td><input type="date" name="log[<?php echo $i; ?>][DatePerformed]" class="form-control"></td>
            <td>
                <select name="log[<?php echo $i; ?>][Result]" class="form-select">
                    <option value="">--</option>
                    <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                    <option value="✗ แก้ไข">✗ แก้ไข</option>
                </select>
            </td>
            <td>
                <select name="log[<?php echo $i; ?>][Operator_UserID]" class="form-select">
                    <option value="">--</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" step="0.001" name="log[<?php echo $i; ?>][ControlValue]" class="form-control"></td>
            <td><input type="number" step="0.001" name="log[<?php echo $i; ?>][ActualMeasuredValue]" class="form-control"></td>
            <td><input type="text" name="log[<?php echo $i; ?>][Remarks]" class="form-control"></td>
        </tr>
        <?php endfor; ?>
    </table>
    <button type="submit" class="btn btn-success">Save</button>
</form>

<script>
function loadModels(projectId) {
    const modelSelect = document.getElementById('model');
    modelSelect.innerHTML = '<option>Loading...</option>';
    fetch('models.php?project_id=' + projectId)
        .then(r => r.json())
        .then(data => {
            modelSelect.innerHTML = '';
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.ModelID;
                opt.textContent = m.ModelName;
                modelSelect.appendChild(opt);
            });
        });
}
function addLinerRow() {
    const table = document.getElementById('liner-table');
    const rowCount = table.rows.length;
    const row = table.insertRow();
    row.innerHTML = `<td><input type="text" name="liner[${rowCount-1}][LinerType]" class="form-control"></td>
                     <td><input type="text" name="liner[${rowCount-1}][LinerBatchNumber]" class="form-control"></td>
                     <td><input type="text" name="liner[${rowCount-1}][Remarks]" class="form-control"></td>`;
}
</script>
<?php include 'templates/footer.php'; ?>
