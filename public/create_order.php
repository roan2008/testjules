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
                        <!-- Project/Model selection moved below -->
                    </div>
                </div>
            </div>

            <!-- Process Template Selection -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-copy me-2"></i>Process Template</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Project</label>
                            <select id="projectSelect" name="ProjectID" class="form-select" onchange="loadModels(this.value)">
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $p): ?>
                                    <option value="<?php echo $p['ProjectID']; ?>"><?php echo htmlspecialchars($p['ProjectName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>                        <div class="col-md-6">
                            <label class="form-label">Model</label>
                            <select id="modelSelect" name="ModelID" class="form-select" onchange="onModelChange()">
                                <option value="">Select Model</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Process Template</label>
                        <select id="templateSelect" class="form-select">
                            <option value="">Auto (Default)</option>
                        </select>                        <div class="mt-2">
                            <button type="button" id="loadTemplate" class="btn btn-outline-primary btn-sm" onclick="loadTemplateSteps()">🔄 Load Template</button>
                            <button type="button" id="clearSteps" class="btn btn-outline-secondary btn-sm" onclick="clearSteps()">🗑️ Clear All Steps</button>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="autoLoadTemplate()">⚡ Auto-Load</button>
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
    const modelSelect = document.getElementById('modelSelect');
    const templateSelect = document.getElementById('templateSelect');
    
    if (!projectId) {
        modelSelect.innerHTML = '<option value="">--Select Model--</option>';
        templateSelect.innerHTML = '<option value="">Auto (Default)</option>';
        return;
    }
    
    modelSelect.innerHTML = '<option>Loading...</option>';
    fetch('api/models.php?project_id=' + projectId)
        .then(r => r.json())
        .then(resp => {
            modelSelect.innerHTML = '<option value="">--Select Model--</option>';
            if (resp.success && resp.data) {
                resp.data.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.ModelID;
                    opt.textContent = m.ModelName;
                    modelSelect.appendChild(opt);
                });
            }
            loadTemplates();
        })
        .catch(error => {
            console.error('Error loading models:', error);
            modelSelect.innerHTML = '<option value="">Error loading models</option>';
        });
}

// New function for Model change event
function onModelChange() {
    loadTemplates();
    // Auto-load template after templates are loaded
    setTimeout(() => autoLoadTemplate(), 800);
}

function loadTemplates() {
    const projectId = document.getElementById('projectSelect').value;
    const modelId = document.getElementById('modelSelect').value;
    const tplSelect = document.getElementById('templateSelect');
    if (!projectId || !modelId) {
        tplSelect.innerHTML = '<option value="">Auto (Default)</option>';
        return;
    }
    tplSelect.innerHTML = '<option>Loading...</option>';
    fetch(`api/templates.php?project=${projectId}&model=${modelId}&list=1`)
        .then(r => r.json())
        .then(resp => {
            tplSelect.innerHTML = '<option value="">Auto (Default)</option>';
            resp.data.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.TemplateID;
                opt.textContent = t.TemplateName;
                tplSelect.appendChild(opt);
            });
        })
        .catch(() => {
            tplSelect.innerHTML = '<option value="">Error loading templates</option>';
        });
}

function loadTemplateSteps() {
    const projectId = document.getElementById('projectSelect').value;
    const modelId = document.getElementById('modelSelect').value;
    const tplId = document.getElementById('templateSelect').value;
    if (!projectId || !modelId) return;
    showLoading();
    let url = `api/templates.php?project=${projectId}&model=${modelId}`;
    if (tplId) url += `&id=${tplId}`;
    fetch(url)
        .then(r => r.json())
        .then(resp => {
            if (resp.data && resp.data.steps) {
                populateProcessLog(resp.data.steps);
            }
        })
        .finally(() => hideLoading());
}

function clearSteps() {
    const table = document.getElementById('logTable').getElementsByTagName('tbody')[0];
    table.innerHTML = '';
    addLogRow();
}

function populateProcessLog(steps) {
    const table = document.getElementById('logTable').getElementsByTagName('tbody')[0];
    table.innerHTML = '';
    steps.forEach((step, idx) => {
        const row = table.insertRow();
        row.innerHTML = `
            <td><input type="number" name="log[${idx}][SequenceNo]" value="${idx+1}" class="form-control form-control-sm" readonly></td>
            <td><input type="text" name="log[${idx}][ProcessStepName]" value="${step.ProcessName}" class="form-control form-control-sm" required></td>
            <td><input type="date" name="log[${idx}][DatePerformed]" class="form-control form-control-sm"></td>
            <td><select name="log[${idx}][Result]" class="form-select form-select-sm"><option value="">--</option><option value="✓ เรียบร้อย">✓ เรียบร้อย</option><option value="✗ แก้ไข">✗ แก้ไข</option></select></td>
            <td><select name="log[${idx}][Operator_UserID]" class="form-select form-select-sm">
                <option value="">--</option>
                <?php foreach ($users as $u): ?>
                <option value="<?php echo $u['UserID']; ?>"><?php echo htmlspecialchars($u['FullName']); ?></option>
                <?php endforeach; ?>
            </select></td>
            <td><input type="number" step="0.001" name="log[${idx}][ControlValue]" class="form-control form-control-sm"></td>
            <td><input type="number" step="0.001" name="log[${idx}][ActualMeasuredValue]" class="form-control form-control-sm"></td>
            <td><input type="text" name="log[${idx}][Remarks]" class="form-control form-control-sm"></td>
            <td><button type="button" onclick="removeLogRow(this)" class="btn btn-outline-danger btn-sm">Remove</button></td>
        `;
    });
    logRowCount = steps.length;
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

// Auto-load template function
function autoLoadTemplate() {
    const projectId = document.getElementById('projectSelect').value;
    const modelId = document.getElementById('modelSelect').value;
    
    if (!projectId || !modelId) {
        showToast('Please select both Project and Model first', 'warning');
        return;
    }
    
    showLoading();
    showToast('🔄 Auto-loading template...', 'info');
    
    // Fetch default template for this project/model combination
    fetch(`api/templates.php?project=${projectId}&model=${modelId}`)
        .then(r => r.json())
        .then(resp => {
            if (resp.success && resp.data && resp.data.steps && resp.data.steps.length > 0) {
                populateProcessLog(resp.data.steps);
                showToast(`✅ Loaded "${resp.data.TemplateName}" template with ${resp.data.steps.length} steps`, 'success');
            } else {
                showToast('ℹ️ No template found for this Project/Model combination', 'info');
            }
        })
        .catch(error => {
            console.error('Error auto-loading template:', error);
            showToast('❌ Error loading template', 'error');
        })
        .finally(() => hideLoading());
}
</script>
<?php include 'templates/footer.php'; ?>
