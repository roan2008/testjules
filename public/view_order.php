<?php
session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../src/Database.php';
$pn = $_GET['pn'] ?? '';
$pdo = Database::connect();
$stmt = $pdo->prepare('SELECT po.*, p.ProjectName, m.ModelName, u.FullName AS SignedBy
                       FROM ProductionOrders po
                       LEFT JOIN Projects p ON po.ProjectID = p.ProjectID
                       LEFT JOIN Models m ON po.ModelID = m.ModelID
                       LEFT JOIN Users u ON po.MC02_SignedBy_UserID = u.UserID
                       WHERE po.ProductionNumber = ?');
$stmt->execute([$pn]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo 'Order not found';
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM MC02_LinerUsage WHERE ProductionNumber = ?');
$stmt->execute([$pn]);
$liners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
$stmt->execute([$pn]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'View Order';
include 'templates/header.php';
?>
<p><a href="index.php" class="btn btn-secondary btn-sm">Back to list</a>
   <a href="edit_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="btn btn-primary btn-sm">Edit Order</a></p>
<h1>Production Number: <?php echo htmlspecialchars($order['ProductionNumber']); ?></h1>
<p>Project: <?php echo htmlspecialchars($order['ProjectName']); ?></p>
<p>Model: <?php echo htmlspecialchars($order['ModelName']); ?></p>
<p>Status: <?php echo htmlspecialchars($order['MC02_Status']); ?></p>
<?php if ($order['MC02_SignedBy_UserID']): ?>
    <p>Signed By: <?php echo htmlspecialchars($order['SignedBy']); ?> on <?php echo htmlspecialchars($order['MC02_SignedDate']); ?></p>
<?php endif; ?>
<h2>Liner Usage</h2>
<table class="table table-bordered">
    <tr><th>Liner Type</th><th>Batch Number</th><th>Remarks</th></tr>
    <?php foreach ($liners as $lu): ?>
    <tr>
        <td><?php echo htmlspecialchars($lu['LinerType']); ?></td>
        <td><?php echo htmlspecialchars($lu['LinerBatchNumber']); ?></td>
        <td><?php echo htmlspecialchars($lu['Remarks']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<h2>Process Log</h2>
<table class="table table-bordered">
    <tr>
        <th>Seq</th><th>Step</th><th>Date</th><th>Result</th><th>Operator</th><th>Control</th><th>Actual</th><th>Remarks</th>
    </tr>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?php echo htmlspecialchars($log['SequenceNo']); ?></td>
        <td><?php echo htmlspecialchars($log['ProcessStepName']); ?></td>
        <td><?php echo htmlspecialchars($log['DatePerformed']); ?></td>
        <td><?php echo htmlspecialchars($log['Result']); ?></td>
        <td><?php echo htmlspecialchars($log['Operator_UserID']); ?></td>
        <td><?php echo htmlspecialchars($log['ControlValue']); ?></td>
        <td><?php echo htmlspecialchars($log['ActualMeasuredValue']); ?></td>
        <td><?php echo htmlspecialchars($log['Remarks']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php include 'templates/footer.php'; ?>
