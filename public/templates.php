<?php
session_start();
if(!isset($_SESSION['UserID'])){header('Location: login.php');exit;}
require_once __DIR__.'/../src/Database.php';
$pdo=Database::connect();
$projects=$pdo->query('SELECT ProjectID,ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);
$page_title='Template Management';
$breadcrumbs=[['title'=>'Templates']];
include 'templates/header.php';
?>

<style>
.sortable-ghost {
    opacity: 0.5;
    background: #f8f9fa;
}
.sortable-chosen {
    background: #e3f2fd;
}
.sortable-drag {
    background: #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.drag-handle:hover {
    background: #f8f9fa;
}
.step-row {
    transition: all 0.2s ease;
}
.step-row:hover {
    background: #f8f9fa;
}
</style>
<div class="row"><div class="col-12">
<h1 class="mb-3">Process Templates</h1>
<div class="mb-3">
<select id="templateProject" class="form-select w-auto d-inline-block me-2" onchange="loadTemplates()">
<option value="">All Projects</option>
<?php foreach($projects as $p): ?><option value="<?= $p['ProjectID'] ?>"><?= htmlspecialchars($p['ProjectName']) ?></option><?php endforeach; ?>
</select>
<select id="templateModel" class="form-select w-auto d-inline-block me-2" onchange="loadTemplates()">
<option value="">All Models</option>
</select>
<button class="btn btn-success" onclick="showCreateModal()">New Template</button>
</div>
<table class="table" id="templatesTable"><thead><tr><th>ID</th><th>Name</th><th>Project</th><th>Model</th><th>Actions</th></tr></thead><tbody></tbody></table>
</div></div>

<div class="modal fade" id="templateModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Create New Template</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" id="tplId">
<div class="mb-3">
<label for="tplName" class="form-label">Template Name</label>
<input type="text" id="tplName" class="form-control" placeholder="Enter template name" required>
</div>
<div class="mb-3">
<label for="tplProject" class="form-label">Project (Optional)</label>
<select id="tplProject" class="form-select">
<option value="">Select Project</option>
<?php foreach($projects as $p): ?><option value="<?= $p['ProjectID'] ?>"><?= htmlspecialchars($p['ProjectName']) ?></option><?php endforeach; ?>
</select>
</div>
<div class="mb-3">
<label for="tplModel" class="form-label">Model (Optional)</label>
<select id="tplModel" class="form-select">
<option value="">Select Model</option>
</select>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-primary" onclick="saveTemplate()">Create Template</button>
</div>
</div></div></div>

<!-- Process Steps Management Modal -->
<div class="modal fade" id="stepsModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Manage Process Steps - <span id="currentTemplateName"></span></h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" id="currentTemplateId">

<!-- Process Steps Table -->
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h6 class="mb-0">Process Steps</h6>
<button type="button" onclick="addStepRow()" class="btn btn-primary btn-sm">
<i class="fas fa-plus"></i> Add Process Step
</button>
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table table-striped table-hover" id="stepsTable">
<thead class="table-dark">
<tr>
<th style="width: 5%;">🔄</th>
<th style="width: 10%;">Order</th>
<th style="width: 40%;">Process Name</th>
<th style="width: 15%;">Required</th>
<th style="width: 20%;">Duration (min)</th>
<th style="width: 10%;">Actions</th>
</tr>
</thead>
<tbody id="stepsTableBody" class="sortable-steps">
<!-- Steps will be loaded here -->
</tbody>
</table>
</div>
</div>
</div>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-success" onclick="saveAllSteps()">Save All Changes</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>
</div></div></div>

<script>
const projects=<?php echo json_encode($projects); ?>;
const modelSelect=document.getElementById('templateModel');
function loadModels(){
 const pid=document.getElementById('templateProject').value;
 if(!pid){modelSelect.innerHTML='<option value="">All Models</option>';return;}
 fetch('api/models.php?project_id='+pid)
  .then(r=>r.json()).then(resp=>{
   modelSelect.innerHTML='<option value="">All Models</option>';
   resp.data.forEach(m=>{const opt=document.createElement('option');opt.value=m.ModelID;opt.textContent=m.ModelName;modelSelect.appendChild(opt);});
  });
}

function loadModalModels(projectSelect, modelSelect){
 const pid = projectSelect.value;
 if(!pid){
  modelSelect.innerHTML='<option value="">Select Model</option>';
  return;
 }
 fetch('api/models.php?project_id='+pid)
  .then(r=>r.json()).then(resp=>{
   modelSelect.innerHTML='<option value="">Select Model</option>';
   if(resp.success && resp.data) {
    resp.data.forEach(m=>{
     const opt=document.createElement('option');
     opt.value=m.ModelID;
     opt.textContent=m.ModelName;
     modelSelect.appendChild(opt);
    });
   }
  }).catch(err => {
   console.error('Error loading models:', err);
   modelSelect.innerHTML='<option value="">Error loading models</option>';
  });
}
function loadTemplates(){
 const p=document.getElementById('templateProject').value;
 const m=document.getElementById('templateModel').value;
 let url='api/templates.php';
 const params=[]; if(p) params.push('project='+p); if(m) params.push('model='+m); if(params.length) url+='?'+params.join('&'); fetch(url).then(r=>r.json()).then(resp=>{
  const tb=document.querySelector('#templatesTable tbody');tb.innerHTML='';
  resp.data.forEach(t=>{
   const tr=document.createElement('tr');
   tr.innerHTML=`<td>${t.TemplateID}</td><td>${t.TemplateName}</td><td>${t.ProjectID||''}</td><td>${t.ModelID||''}</td><td>
    <button class="btn btn-sm btn-primary me-1" onclick="manageSteps(${t.TemplateID}, '${t.TemplateName}')">Manage Steps</button>
    <button class="btn btn-sm btn-danger" onclick="deleteTemplate(${t.TemplateID})">Delete</button>
   </td>`;
   tb.appendChild(tr);
  });
 });
}
function showCreateModal(){ 
 document.getElementById('tplId').value=''; 
 document.getElementById('tplName').value=''; 
 document.getElementById('tplProject').value='';
 document.getElementById('tplModel').innerHTML='<option value="">Select Model</option>';
 
 // Setup project change listener for modal
 document.getElementById('tplProject').onchange = function() {
  loadModalModels(this, document.getElementById('tplModel'));
 };
 
 const modal = new bootstrap.Modal(document.getElementById('templateModal'));
 modal.show(); 
}

function saveTemplate(){ 
 const id=document.getElementById('tplId').value; 
 const name=document.getElementById('tplName').value.trim(); 
 if(!name) {
  alert('Please enter a template name');
  return;
 }
 
 const projectId = document.getElementById('tplProject').value || null;
 const modelId = document.getElementById('tplModel').value || null;
 
 const body={
  TemplateName:name,
  ProjectID: projectId,
  ModelID: modelId
 }; 
 
 let url='api/templates.php'; 
 let method='POST'; 
 if(id){
  url+='?id='+id;
  method='PUT';
 } 
 
 fetch(url,{
  method:method,
  headers: {
   'Content-Type': 'application/json'
  },
  body:JSON.stringify(body)
 })
 .then(r => r.json())
 .then(resp => {
  if(resp.success) {
   bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();
   loadTemplates();
  } else {
   alert('Error creating template: ' + resp.message);
  }
 })
 .catch(err => {
  console.error('Error:', err);
  alert('Network error occurred');
 }); 
}
function deleteTemplate(id){ 
 if(!confirm('Delete template?'))return; 
 fetch('api/templates.php?id='+id,{method:'DELETE'})
  .then(r => r.json())
  .then(resp => {
   if(resp.success) {
    loadTemplates();
   } else {
    alert('Error deleting template: ' + resp.message);
   }
  })
  .catch(err => {
   console.error('Error:', err);
   alert('Network error occurred');
  });
}

// Process Steps Management Functions
let stepRowCount = 0;
let originalSteps = []; // Store original steps for comparison

function manageSteps(templateId, templateName) {
 document.getElementById('currentTemplateId').value = templateId;
 document.getElementById('currentTemplateName').textContent = templateName;
 
 // Load current steps
 loadProcessSteps(templateId);
 
 // Show modal
 const modal = new bootstrap.Modal(document.getElementById('stepsModal'));
 modal.show();
}

function loadProcessSteps(templateId) {
 const tbody = document.getElementById('stepsTableBody');
 tbody.innerHTML = '<tr><td colspan="6" class="text-center">Loading steps...</td></tr>';
 
 fetch(`api/templates.php?id=${templateId}&include_steps=1`)
  .then(r => r.json())
  .then(resp => {
   if(resp.success && resp.data.steps) {
    originalSteps = [...resp.data.steps]; // Store original data
    displayProcessSteps(resp.data.steps);
   } else {
    tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">No process steps found. Click "Add Process Step" to create one.</td></tr>';
    stepRowCount = 0;
   }
  })
  .catch(err => {
   console.error('Error loading steps:', err);
   tbody.innerHTML = '<tr><td colspan="6" class="text-danger text-center">Error loading steps.</td></tr>';
  });
}

function displayProcessSteps(steps) {
 const tbody = document.getElementById('stepsTableBody');
 
 if(steps.length === 0) {
  tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">No process steps found. Click "Add Process Step" to create one.</td></tr>';
  stepRowCount = 0;
  return;
 }
 
 // Sort steps by order
 steps.sort((a, b) => a.StepOrder - b.StepOrder);
 
 tbody.innerHTML = '';
 stepRowCount = 0;
 
 steps.forEach((step, index) => {
  addStepRowWithData(step, index);
 });
 
 // Initialize sortable
 initializeSortable();
}

function addStepRow() {
 const tbody = document.getElementById('stepsTableBody');
 
 // If this is the first step and table shows "no steps" message, clear it
 if (tbody.children.length === 1 && tbody.children[0].children.length === 1) {
  tbody.innerHTML = '';
 }
 
 const newRow = document.createElement('tr');
 newRow.className = 'step-row';
 newRow.setAttribute('data-step-id', 'new');
 newRow.innerHTML = createStepRowHTML(stepRowCount, {
  TemplateStepID: 'new',
  ProcessName: '',
  StepOrder: stepRowCount + 1,
  IsRequired: 1,
  EstimatedDuration: ''
 });
 
 tbody.appendChild(newRow);
 stepRowCount++;
 
 // Focus on the process name input
 newRow.querySelector('input[name$="[ProcessName]"]').focus();
 
 // Update step orders
 updateStepOrders();
}

function addStepRowWithData(step, index) {
 const tbody = document.getElementById('stepsTableBody');
 const newRow = document.createElement('tr');
 newRow.className = 'step-row';
 newRow.setAttribute('data-step-id', step.TemplateStepID);
 newRow.innerHTML = createStepRowHTML(index, step);
 tbody.appendChild(newRow);
 stepRowCount = Math.max(stepRowCount, index + 1);
}

function createStepRowHTML(index, step) {
 return `
  <td class="drag-handle text-center" style="cursor: move;">
   <i class="fas fa-grip-vertical text-muted"></i>
  </td>
  <td>
   <input type="number" name="steps[${index}][StepOrder]" value="${step.StepOrder}" 
    class="form-control form-control-sm" readonly>
   <input type="hidden" name="steps[${index}][TemplateStepID]" value="${step.TemplateStepID}">
  </td>
  <td>
   <input type="text" name="steps[${index}][ProcessName]" value="${step.ProcessName || ''}" 
    class="form-control form-control-sm" placeholder="Enter process name" required>
  </td>
  <td>
   <select name="steps[${index}][IsRequired]" class="form-select form-select-sm">
    <option value="1" ${step.IsRequired == 1 ? 'selected' : ''}>Yes</option>
    <option value="0" ${step.IsRequired == 0 ? 'selected' : ''}>No</option>
   </select>
  </td>
  <td>
   <input type="number" name="steps[${index}][EstimatedDuration]" value="${step.EstimatedDuration || ''}" 
    class="form-control form-control-sm" placeholder="Minutes" min="0">
  </td>
  <td>
   <button type="button" onclick="removeStepRow(this)" class="btn btn-outline-danger btn-sm">
    <i class="fas fa-trash"></i>
   </button>
  </td>
 `;
}

function removeStepRow(button) {
 const row = button.closest('tr');
 const tbody = row.closest('tbody');
 
 if (tbody.querySelectorAll('.step-row').length > 1) {
  row.remove();
  updateStepOrders();
 } else {
  // Show "no steps" message
  tbody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">No process steps found. Click "Add Process Step" to create one.</td></tr>';
  stepRowCount = 0;
 }
}

function updateStepOrders() {
 const tbody = document.getElementById('stepsTableBody');
 const rows = tbody.querySelectorAll('.step-row');
 
 rows.forEach((row, index) => {
  const orderInput = row.querySelector('input[name$="[StepOrder]"]');
  const processInput = row.querySelector('input[name$="[ProcessName]"]');
  const requiredSelect = row.querySelector('select[name$="[IsRequired]"]');
  const durationInput = row.querySelector('input[name$="[EstimatedDuration]"]');
  const hiddenInput = row.querySelector('input[name$="[TemplateStepID]"]');
  
  // Update order value
  orderInput.value = index + 1;
  
  // Update name attributes
  orderInput.name = `steps[${index}][StepOrder]`;
  processInput.name = `steps[${index}][ProcessName]`;
  requiredSelect.name = `steps[${index}][IsRequired]`;
  durationInput.name = `steps[${index}][EstimatedDuration]`;
  hiddenInput.name = `steps[${index}][TemplateStepID]`;
 });
 
 stepRowCount = rows.length;
}

function initializeSortable() {
 const tbody = document.getElementById('stepsTableBody');
 
 // Remove existing sortable if any
 if (tbody.sortable) {
  tbody.sortable.destroy();
 }
 
 // Initialize new sortable
 tbody.sortable = new Sortable(tbody, {
  handle: '.drag-handle',
  animation: 150,
  ghostClass: 'sortable-ghost',
  chosenClass: 'sortable-chosen',
  dragClass: 'sortable-drag',
  onEnd: function(evt) {
   updateStepOrders();
  }
 });
}

function saveAllSteps() {
 const templateId = document.getElementById('currentTemplateId').value;
 const tbody = document.getElementById('stepsTableBody');
 const stepRows = tbody.querySelectorAll('.step-row');
 
 if (stepRows.length === 0) {
  alert('No process steps to save.');
  return;
 }
 
 // Validate all steps
 let isValid = true;
 stepRows.forEach(row => {
  const processName = row.querySelector('input[name$="[ProcessName]"]').value.trim();
  if (!processName) {
   isValid = false;
   row.querySelector('input[name$="[ProcessName]"]').focus();
   return false;
  }
 });
 
 if (!isValid) {
  alert('Please fill in all process names.');
  return;
 }
 
 // Collect step data
 const steps = [];
 stepRows.forEach(row => {
  const stepId = row.querySelector('input[name$="[TemplateStepID]"]').value;
  const processName = row.querySelector('input[name$="[ProcessName]"]').value.trim();
  const stepOrder = parseInt(row.querySelector('input[name$="[StepOrder]"]').value);
  const isRequired = parseInt(row.querySelector('select[name$="[IsRequired]"]').value);
  const duration = row.querySelector('input[name$="[EstimatedDuration]"]').value;
  
  steps.push({
   TemplateStepID: stepId === 'new' ? null : stepId,
   TemplateID: templateId,
   ProcessName: processName,
   StepOrder: stepOrder,
   IsRequired: isRequired,
   EstimatedDuration: duration ? parseInt(duration) : null
  });
 });
 
 // Save to server
 const saveButton = document.querySelector('#stepsModal .btn-success');
 saveButton.disabled = true;
 saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
 
 fetch('api/template_steps.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
   action: 'replace_all',
   template_id: templateId,
   steps: steps
  })
 })
 .then(r => r.json())
 .then(resp => {
  if(resp.success) {
   // Close modal and reload templates
   bootstrap.Modal.getInstance(document.getElementById('stepsModal')).hide();
   loadTemplates();
   showToast('Process steps saved successfully!', 'success');
  } else {
   alert('Error saving steps: ' + resp.message);
  }
 })
 .catch(err => {
  console.error('Error:', err);
  alert('Network error occurred');
 })
 .finally(() => {
  saveButton.disabled = false;
  saveButton.innerHTML = 'Save All Changes';
 });
}

function showToast(message, type = 'info') {
 // Simple toast notification
 const toast = document.createElement('div');
 toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed`;
 toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
 toast.innerHTML = `
  <div class="d-flex justify-content-between align-items-center">
   <span>${message}</span>
   <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
  </div>
 `;
 document.body.appendChild(toast);
 
 // Auto remove after 5 seconds
 setTimeout(() => {
  if (toast.parentElement) {
   toast.remove();
  }
 }, 5000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
 loadModels(); 
 loadTemplates();
 
 // Load SortableJS library
 if (!window.Sortable) {
  const script = document.createElement('script');
  script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
  document.head.appendChild(script);
 }
});
</script>
<?php include 'templates/footer.php'; ?>
