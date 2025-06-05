<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pdo = Database::connect();
$stmt = $pdo->query('SELECT po.ProductionNumber, p.ProjectName, m.ModelName, po.MC02_Status
                     FROM ProductionOrders po
                     LEFT JOIN Projects p ON po.ProjectID = p.ProjectID
                     LEFT JOIN Models m ON po.ModelID = m.ModelID');
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Production Orders';
include __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Production Orders</h1>
            <a href="create_order.php" class="btn btn-primary">Create New Order</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Production Number</th>
                        <th>Project</th>
                        <th>Model</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($order['ProductionNumber']); ?></a></td>
                        <td><?php echo htmlspecialchars($order['ProjectName']); ?></td>
                        <td><?php echo htmlspecialchars($order['ModelName']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $order['MC02_Status'] === 'Completed' ? 'success' : ($order['MC02_Status'] === 'In Progress' ? 'warning' : 'secondary'); ?>">
                                <?php echo htmlspecialchars($order['MC02_Status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="btn btn-sm btn-success me-1">View</a>
                            <a href="edit_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
