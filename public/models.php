<?php
session_start();
if (!isset($_SESSION['UserID'])) { header('Location: login.php'); exit; }
require_once __DIR__.'/../src/Database.php';
$pdo=Database::connect();
$projects=$pdo->query('SELECT ProjectID,ProjectName FROM Projects ORDER BY ProjectName')->fetchAll(PDO::FETCH_ASSOC);
$page_title='Model Management';
$breadcrumbs=[['title'=>'Models']];
include 'templates/header.php';
?>
<div class="row"><div class="col-12">
<h1 class="mb-3">Models</h1>
<div class="mb-3">
<select id="newModelProject" class="form-select w-auto d-inline-block me-2">
<?php foreach($projects as $p): ?>
<option value="<?= $p['ProjectID'] ?>"><?= htmlspecialchars($p['ProjectName']) ?></option>
<?php endforeach; ?>
</select>
<input type="text" id="newModelName" class="form-control d-inline-block w-auto" placeholder="Model name">
<button class="btn btn-primary" onclick="createModel()">Add</button>
</div>
<table class="table" id="modelsTable"><thead><tr><th>ID</th><th>Project</th><th>Name</th><th>Actions</th></tr></thead><tbody></tbody></table>
</div></div>
<script>
const projects=<?php echo json_encode($projects); ?>;
function loadModels(){
 fetch('api/models.php')
  .then(r=>r.json())
  .then(resp=>{
    const tb=document.querySelector('#modelsTable tbody');
    tb.innerHTML='';
    resp.data.forEach(m=>{
      const tr=document.createElement('tr');
      const projSelect=projects.map(p=>`<option value="${p.ProjectID}" ${p.ProjectID==m.ProjectID?'selected':''}>${p.ProjectName}</option>`).join('');
      tr.innerHTML=`<td>${m.ModelID}</td>
<td><select onchange="updateModel(${m.ModelID},this.value,'project')" class="form-select form-select-sm">${projSelect}</select></td>
<td><input type="text" value="${m.ModelName}" class="form-control form-control-sm" onchange="updateModel(${m.ModelID},this.value,'name')"></td>
<td><button class="btn btn-sm btn-danger" onclick="deleteModel(${m.ModelID})">Delete</button></td>`;
      tb.appendChild(tr);
    });
  });
}
function createModel(){
 const name=document.getElementById('newModelName').value.trim();
 const pid=document.getElementById('newModelProject').value;
 if(!name) return;
 fetch('api/models.php',{method:'POST',body:JSON.stringify({ModelName:name,ProjectID:pid})})
  .then(loadModels);
}
function updateModel(id,val,type){
 const row = event.target.closest('tr');
 const project = row.querySelector('select').value;
 const name = row.querySelector('input').value;
 fetch('api/models.php?id='+id,{method:'PUT',body:JSON.stringify({ModelName:name,ProjectID:project})})
  .then(loadModels);
}
function deleteModel(id){ if(!confirm('Delete this model?')) return; fetch('api/models.php?id='+id,{method:'DELETE'}).then(loadModels); }
loadModels();
</script>
<?php include 'templates/footer.php'; ?>
