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

// Fetch liner usage
$stmt = $pdo->prepare('SELECT * FROM MC02_LinerUsage WHERE ProductionNumber = ?');
$stmt->execute([$pn]);
$liners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch process log
$stmt = $pdo->prepare('SELECT * FROM MC02_ProcessLog WHERE ProductionNumber = ? ORDER BY SequenceNo');
$stmt->execute([$pn]);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'View Order - ' . htmlspecialchars($order['ProductionNumber']);
$breadcrumbs = [
    ['title' => 'Orders', 'url' => 'index.php'],
    ['title' => $order['ProductionNumber']]
];
include __DIR__ . '/templates/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Production Number: <?php echo htmlspecialchars($order['ProductionNumber']); ?></h1>
            <div>
                <a href="index.php" class="btn btn-secondary me-2">Back to List</a>
                <a href="edit_order.php?pn=<?php echo urlencode($order['ProductionNumber']); ?>" class="btn btn-primary">Edit Order</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Order Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Project:</strong> <?php echo htmlspecialchars($order['ProjectName']); ?></p>
                        <p><strong>Model:</strong> <?php echo htmlspecialchars($order['ModelName']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $order['MC02_Status'] === 'Completed' ? 'success' : ($order['MC02_Status'] === 'In Progress' ? 'warning' : 'secondary'); ?>">
                                <?php echo htmlspecialchars($order['MC02_Status']); ?>
                            </span>
                        </p>
                        <?php if ($order['MC02_SignedBy_UserID']): ?>
                            <p><strong>Signed By:</strong> <?php echo htmlspecialchars($order['SignedBy']); ?> on <?php echo htmlspecialchars($order['MC02_SignedDate']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Liner Usage</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead class="table-dark">
                            <tr><th>Liner Type</th><th>Batch Number</th><th>Remarks</th></tr>
                        </thead>                        <tbody>
                            <?php foreach ($liners as $lu): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($lu['LinerType']); ?></td>
                                <td><?php echo htmlspecialchars($lu['LinerBatchNumber']); ?></td>
                                <td><?php echo htmlspecialchars($lu['Remarks']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Process Log</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-sm">
                        <thead class="table-dark">
                            <tr>
                                <th>Seq</th><th>Step</th><th>Date</th><th>Result</th><th>Operator</th><th>Control</th><th>Actual</th><th>Remarks</th>
                            </tr>
                        </thead>                        <tbody>
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
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
