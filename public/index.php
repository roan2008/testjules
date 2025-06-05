<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();

// Handle search and filter parameters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query with filters
$query = 'SELECT po.ProductionNumber, p.ProjectName, m.ModelName, po.MC02_Status, po.CreatedDate
          FROM ProductionOrders po
          LEFT JOIN Projects p ON po.ProjectID = p.ProjectID
          LEFT JOIN Models m ON po.ModelID = m.ModelID
          WHERE 1=1';
$params = [];

if ($search) {
    $query .= ' AND (po.ProductionNumber LIKE ? OR p.ProjectName LIKE ? OR m.ModelName LIKE ?)';
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($statusFilter) {
    $query .= ' AND po.MC02_Status = ?';
    $params[] = $statusFilter;
}

$query .= ' ORDER BY po.CreatedDate DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get KPI data
$kpiData = [];
$kpiQueries = [
    'total' => 'SELECT COUNT(*) FROM ProductionOrders',
    'completed' => 'SELECT COUNT(*) FROM ProductionOrders WHERE MC02_Status = "Completed"',
    'in_progress' => 'SELECT COUNT(*) FROM ProductionOrders WHERE MC02_Status = "In Progress"',
    'pending' => 'SELECT COUNT(*) FROM ProductionOrders WHERE MC02_Status IS NULL OR MC02_Status = "" OR MC02_Status = "Pending"'
];

foreach ($kpiQueries as $key => $kpiQuery) {
    $kpiData[$key] = $pdo->query($kpiQuery)->fetchColumn();
}

// Get distinct statuses for filter dropdown
$statusQuery = 'SELECT DISTINCT MC02_Status FROM ProductionOrders WHERE MC02_Status IS NOT NULL AND MC02_Status != "" ORDER BY MC02_Status';
$statuses = $pdo->query($statusQuery)->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Production Orders';
include __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-industry me-2"></i>Production Orders Dashboard</h1>
            <a href="create_order.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create New Order
            </a>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Orders
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $kpiData['total']; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Completed
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $kpiData['completed']; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    In Progress
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $kpiData['in_progress']; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-spinner fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Pending
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?php echo $kpiData['pending']; ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-search me-2"></i>Search & Filter</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Search Orders</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by Production Number, Project, or Model..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status Filter</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?php echo htmlspecialchars($status); ?>" 
                                        <?php echo $status === $statusFilter ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </form>
                <?php if ($search || $statusFilter): ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                        <small class="text-muted ms-2">
                            Showing <?php echo count($orders); ?> result(s)
                            <?php if ($search): ?>
                                for "<?php echo htmlspecialchars($search); ?>"
                            <?php endif; ?>
                            <?php if ($statusFilter): ?>
                                with status "<?php echo htmlspecialchars($statusFilter); ?>"
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-table me-2"></i>Production Orders 
                    <span class="badge bg-secondary ms-2"><?php echo count($orders); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No orders found</h5>
                        <p class="text-muted">
                            <?php if ($search || $statusFilter): ?>
                                Try adjusting your search criteria or 
                                <a href="index.php" class="text-decoration-none">clear filters</a>.
                            <?php else: ?>
                                <a href="create_order.php" class="btn btn-primary">Create your first order</a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Production Number</th>
                                    <th>Project</th>
                                    <th>Model</th>
                                    <th>Status</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" 
                                           class="text-decoration-none fw-bold">
                                            <?php echo htmlspecialchars($order['ProductionNumber']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['ProjectName']); ?></td>
                                    <td><?php echo htmlspecialchars($order['ModelName']); ?></td>
                                    <td>
                                        <?php 
                                        $status = $order['MC02_Status'];
                                        $badgeClass = 'secondary';
                                        if ($status === 'Completed') $badgeClass = 'success';
                                        elseif ($status === 'In Progress') $badgeClass = 'warning';
                                        elseif ($status === 'Pending') $badgeClass = 'info';
                                        ?>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo htmlspecialchars($status ?: 'Not Set'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($order['CreatedDate']): ?>
                                            <small class="text-muted">
                                                <?php echo date('M j, Y', strtotime($order['CreatedDate'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" 
                                               class="btn btn-outline-success" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" 
                                               class="btn btn-outline-primary" title="Edit Order">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
