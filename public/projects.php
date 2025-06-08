<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}
$page_title = 'Project Management';
$breadcrumbs = [['title' => 'Projects']];
include 'templates/header.php';
?>
<div class="row">
    <div class="col-12">
        <h1 class="mb-3">Projects</h1>
        <div class="mb-3">
            <label class="form-label">New Project</label>
            <div class="input-group">
                <input type="text" id="newProject" class="form-control" placeholder="Project name">
                <button class="btn btn-primary" onclick="createProject()">Add</button>
            </div>
        </div>
        <table class="table" id="projectsTable">
            <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script>
function loadProjects() {
    fetch('api/projects.php')
        .then(r => r.json())
        .then(resp => {
            const tbody = document.querySelector('#projectsTable tbody');
            tbody.innerHTML = '';
            resp.data.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${p.ProjectID}</td>
                                <td><input type="text" value="${p.ProjectName}" class="form-control form-control-sm" onchange="updateProject(${p.ProjectID}, this.value)"></td>
                                <td><button class="btn btn-sm btn-danger" onclick="deleteProject(${p.ProjectID})">Delete</button></td>`;
                tbody.appendChild(tr);
            });
        });
}
function createProject() {
    const name = document.getElementById('newProject').value.trim();
    if (!name) return;
    fetch('api/projects.php', {method:'POST', body: JSON.stringify({ProjectName:name})})
        .then(r => r.json())
        .then(loadProjects);
}
function updateProject(id, name) {
    fetch('api/projects.php?id='+id, {method:'PUT', body: JSON.stringify({ProjectName:name})})
        .then(loadProjects);
}
function deleteProject(id) {
    if(!confirm('Delete this project?')) return;
    fetch('api/projects.php?id='+id, {method:'DELETE'})
        .then(loadProjects);
}
loadProjects();
</script>
<?php include 'templates/footer.php'; ?>
