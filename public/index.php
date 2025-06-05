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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Production Orders</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; }
    </style>
</head>
<body>
    <p>Logged in as <?php echo htmlspecialchars($_SESSION['Role']); ?> | <a href="logout.php">Logout</a></p>
    <h1>Production Orders</h1>
    <p><a href="create_order.php">Create New Order</a></p>
    <table>
        <tr>
            <th>Production Number</th>
            <th>Project</th>
            <th>Model</th>
            <th>Status</th>
        </tr>
        <?php foreach ($orders as $order): ?>
        <tr>
            <td><a href="view_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>">
                <?php echo htmlspecialchars($order['ProductionNumber']); ?></a></td>
            <td><?php echo htmlspecialchars($order['ProjectName']); ?></td>
            <td><?php echo htmlspecialchars($order['ModelName']); ?></td>
            <td><?php echo htmlspecialchars($order['MC02_Status']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
