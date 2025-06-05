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

$page_title = "Production Orders";
include 'templates/header.php';
?>
<h1>Production Orders</h1>
<p><a class="btn btn-primary" href="create_order.php">Create New Order</a></p>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Production Number</th>
            <th>Project</th>
            <th>Model</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>">
                <?php echo htmlspecialchars($order['ProductionNumber']); ?></a></td>
            <td><?php echo htmlspecialchars($order['ProjectName']); ?></td>
            <td><?php echo htmlspecialchars($order['ModelName']); ?></td>
            <td><?php echo htmlspecialchars($order['MC02_Status']); ?></td>
            <td>
                <a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="btn btn-success btn-sm me-1">View</a>
                <a href="edit_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="btn btn-primary btn-sm">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include 'templates/footer.php'; ?>
