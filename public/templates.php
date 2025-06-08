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
<div class="modal-header"><h5 class="modal-title">Template</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" id="tplId">
<div class="mb-2"><input type="text" id="tplName" class="form-control" placeholder="Template name"></div>
</div>
<div class="modal-footer"><button class="btn btn-primary" onclick="saveTemplate()">Save</button></div>
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
function loadTemplates(){
 const p=document.getElementById('templateProject').value;
 const m=document.getElementById('templateModel').value;
 let url='api/templates.php';
 const params=[]; if(p) params.push('project='+p); if(m) params.push('model='+m); if(params.length) url+='?'+params.join('&');
 fetch(url).then(r=>r.json()).then(resp=>{
  const tb=document.querySelector('#templatesTable tbody');tb.innerHTML='';
  resp.data.forEach(t=>{const tr=document.createElement('tr');tr.innerHTML=`<td>${t.TemplateID}</td><td>${t.TemplateName}</td><td>${t.ProjectID||''}</td><td>${t.ModelID||''}</td><td><button class="btn btn-sm btn-danger" onclick="deleteTemplate(${t.TemplateID})">Delete</button></td>`;tb.appendChild(tr);});
 });
}
function showCreateModal(){ document.getElementById('tplId').value=''; document.getElementById('tplName').value=''; new bootstrap.Modal(document.getElementById('templateModal')).show(); }
function saveTemplate(){ const id=document.getElementById('tplId').value; const name=document.getElementById('tplName').value.trim(); if(!name) return; const body={TemplateName:name,ProjectID:document.getElementById('templateProject').value||null,ModelID:document.getElementById('templateModel').value||null}; let url='api/templates.php'; let method='POST'; if(id){url+='?id='+id;method='PUT';} fetch(url,{method:method,body:JSON.stringify(body)}).then(()=>{bootstrap.Modal.getInstance(document.getElementById('templateModal')).hide();loadTemplates();}); }
function deleteTemplate(id){ if(!confirm('Delete template?'))return; fetch('api/templates.php?id='+id,{method:'DELETE'}).then(loadTemplates); }
loadModels(); loadTemplates();
</script>
<?php include 'templates/footer.php'; ?>
