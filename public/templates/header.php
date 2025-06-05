<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $page_title ?? 'Rocket Production System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/navigation.php'; ?>

<?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mt-3 mb-0">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php" class="text-decoration-none">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <?php foreach ($breadcrumbs as $crumb): ?>
                <?php if (isset($crumb['url'])): ?>
                    <li class="breadcrumb-item">
                        <a href="<?php echo htmlspecialchars($crumb['url']); ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($crumb['title']); ?>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo htmlspecialchars($crumb['title']); ?>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>
<?php endif; ?>

<div class="container-fluid">
