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
$breadcrumbs = [
    ['title' => 'Create New Order']
];
include 'templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-plus-circle me-2"></i>Create Production Order</h1>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" id="create-order-form" novalidate>
            <!-- Basic Information Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Production Number</strong> <span class="text-danger">*</span></label>
                                <input type="text" name="ProductionNumber" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Empty Tube Number</label>
                                <input type="text" name="EmptyTubeNumber" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Project</strong> <span class="text-danger">*</span></label>
                                <select name="ProjectID" class="form-select" required onchange="loadModels(this.value)">
                                    <option value="">--Select Project--</option>
                                    <?php foreach ($projects as $p): ?>
                                        <option value="<?php echo $p['ProjectID']; ?>"><?php echo htmlspecialchars($p['ProjectName']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label"><strong>Model</strong> <span class="text-danger">*</span></label>
                                <select name="ModelID" id="model" class="form-select" required>
                                    <option value="">--Select Project First--</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liner Usage Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-layer-group me-2"></i>Liner Usage</h5>
                    <button type="button" class="btn btn-sm btn-success" onclick="addLinerRow()">
                        <i class="fas fa-plus me-1"></i>Add Liner
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="liner-table" class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Type</th>
                                    <th>Batch Number</th>
                                    <th>Remarks</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="liner[0][LinerType]" class="form-control"></td>
                                    <td><input type="text" name="liner[0][LinerBatchNumber]" class="form-control"></td>
                                    <td><input type="text" name="liner[0][Remarks]" class="form-control"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLinerRow(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Process Log Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="fas fa-clipboard-list me-2"></i>Process Log</h5>
                    <button type="button" onclick="addLogRow()" class="btn btn-primary btn-sm">Add Process Log Row</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="logTable">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">Seq</th>
                                    <th width="25%">Step Name</th>
                                    <th width="12%">Date</th>
                                    <th width="12%">Result</th>
                                    <th width="15%">Operator</th>
                                    <th width="10%">Control</th>
                                    <th width="10%">Actual</th>
                                    <th width="11%">Remarks</th>
                                    <th width="8%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="number" name="log[0][SequenceNo]" value="1" class="form-control form-control-sm" readonly></td>
                                    <td><input type="text" name="log[0][ProcessStepName]" required class="form-control form-control-sm"></td>
                                    <td><input type="date" name="log[0][DatePerformed]" class="form-control form-control-sm"></td>
                                    <td>
                                        <select name="log[0][Result]" class="form-select form-select-sm">
                                            <option value="">--</option>
                                            <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                                            <option value="✗ แก้ไข">✗ แก้ไข</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="log[0][Operator_UserID]" class="form-select form-select-sm">
                                            <option value="">--</option>
                                            <?php foreach ($users as $u): ?>
                                                <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.001" name="log[0][ControlValue]" class="form-control form-control-sm"></td>
                                    <td><input type="number" step="0.001" name="log[0][ActualMeasuredValue]" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="log[0][Remarks]" class="form-control form-control-sm"></td>
                                    <td><button type="button" onclick="removeLogRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Save Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save me-2"></i>Create Order
                            </button>
                            <a href="index.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                        <small class="text-muted">
                            <span class="text-danger">*</span> Required fields
                        </small>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function loadModels(projectId) {
    const modelSelect = document.getElementById('model');
    modelSelect.innerHTML = '<option>Loading...</option>';
    fetch('models.php?project_id=' + projectId)
        .then(r => r.json())
        .then(data => {
            modelSelect.innerHTML = '<option value="">--Select Model--</option>';
            data.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.ModelID;
                opt.textContent = m.ModelName;
                modelSelect.appendChild(opt);
            });
        })
        .catch(error => {
            console.error('Error loading models:', error);
            modelSelect.innerHTML = '<option value="">Error loading models</option>';
        });
}

function addLinerRow() {
    const table = document.getElementById('liner-table').getElementsByTagName('tbody')[0];
    const rowCount = table.rows.length;
    const row = table.insertRow();
    row.innerHTML = `
        <td><input type="text" name="liner[${rowCount}][LinerType]" class="form-control"></td>
        <td><input type="text" name="liner[${rowCount}][LinerBatchNumber]" class="form-control"></td>
        <td><input type="text" name="liner[${rowCount}][Remarks]" class="form-control"></td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLinerRow(this)">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
}

function removeLinerRow(button) {
    const row = button.closest('tr');
    const table = row.closest('tbody');
    
    // Don't allow removing the last row
    if (table.rows.length > 1) {
        row.remove();
        
        // Update the name attributes to maintain proper indexing
        Array.from(table.rows).forEach((row, index) => {
            const inputs = row.querySelectorAll('input');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('liner[')) {
                    const newName = name.replace(/liner\[\d+\]/, `liner[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    } else {
        alert('You must have at least one liner row.');
    }
}

// Process Log dynamic row functions
let logRowCount = 1;
function addLogRow() {
    const table = document.getElementById('logTable').getElementsByTagName('tbody')[0];
    const newRow = table.insertRow();
    newRow.innerHTML = `
        <td><input type="number" name="log[${logRowCount}][SequenceNo]" value="${logRowCount+1}" class="form-control form-control-sm" readonly></td>
        <td><input type="text" name="log[${logRowCount}][ProcessStepName]" required class="form-control form-control-sm"></td>
        <td><input type="date" name="log[${logRowCount}][DatePerformed]" class="form-control form-control-sm"></td>
        <td>
            <select name="log[${logRowCount}][Result]" class="form-select form-select-sm">
                <option value="">--</option>
                <option value="✓ เรียบร้อย">✓ เรียบร้อย</option>
                <option value="✗ แก้ไข">✗ แก้ไข</option>
            </select>
        </td>
        <td>
            <select name="log[${logRowCount}][Operator_UserID]" class="form-select form-select-sm">
                <option value="">--</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="number" step="0.001" name="log[${logRowCount}][ControlValue]" class="form-control form-control-sm"></td>
        <td><input type="number" step="0.001" name="log[${logRowCount}][ActualMeasuredValue]" class="form-control form-control-sm"></td>
        <td><input type="text" name="log[${logRowCount}][Remarks]" class="form-control form-control-sm"></td>
        <td><button type="button" onclick="removeLogRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
    `;
    logRowCount++;
}
function removeLogRow(button) {
    const row = button.closest('tr');
    const table = row.closest('tbody');
    if (table.rows.length > 1) {
        row.remove();
        // Update SequenceNo and name attributes
        Array.from(table.rows).forEach((row, idx) => {
            row.querySelector('input[name^="log"]').value = idx + 1;
            row.querySelectorAll('input, select').forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/log\[\d+\]/, `log[${idx}]`);
                }
            });
        });
        logRowCount = table.rows.length;
    } else {
        alert('You must have at least one process log row.');
    }
}

// --- Auto-save Draft ---
const form = document.getElementById('create-order-form');
const draftKey = 'createOrderDraft';

function saveDraft() {
    const data = {};
    new FormData(form).forEach((value, key) => { data[key] = value; });
    localStorage.setItem(draftKey, JSON.stringify(data));
}

function loadDraft() {
    const stored = localStorage.getItem(draftKey);
    if (!stored) return;
    try {
        const data = JSON.parse(stored);
        Object.entries(data).forEach(([key, value]) => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) field.value = value;
        });
    } catch(e) { console.error('Failed to load draft', e); }
}

form.addEventListener('input', saveDraft);
window.addEventListener('DOMContentLoaded', loadDraft);

// --- AJAX Form Submission ---
form.addEventListener('submit', function(e) {
    e.preventDefault();
    if (!form.checkValidity()) {
        e.stopPropagation();
        form.classList.add('was-validated');
        showToast('Please fill in all required fields.', 'error');
        return;
    }

    const formData = new FormData(form);
    showLoading();
    fetch('api/create_order.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            localStorage.removeItem(draftKey);
            showToast('Order created successfully!');
            setTimeout(() => {
                window.location.href = 'view_order.php?pn=' + encodeURIComponent(data.production_number);
            }, 1500);
        } else {
            showToast(data.error || 'Error creating order', 'error');
        }
    })
    .catch(() => {
        showToast('Error creating order', 'error');
    })
    .finally(() => hideLoading());
});
</script>
<?php include 'templates/footer.php'; ?>
