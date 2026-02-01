<?php
session_start();
require_once '../config/functions.php';
requireAdmin();

$db = getDB();

// Handle report actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $report_id = (int)$_GET['id'];
    
    switch($action) {
        case 'resolve':
            $db->query("UPDATE reports SET status = 'resolved' WHERE id = $report_id");
            break;
        case 'dismiss':
            $db->query("UPDATE reports SET status = 'dismissed' WHERE id = $report_id");
            break;
        case 'delete':
            $db->query("DELETE FROM reports WHERE id = $report_id");
            break;
    }
}

// Get all reports
$reports = $db->query("
    SELECT r.*, v.title as video_title, v.filename as video_file,
           u1.username as reporter, u2.username as video_owner
    FROM reports r
    JOIN videos v ON r.video_id = v.id
    JOIN users u1 ON r.user_id = u1.id
    JOIN users u2 ON v.user_id = u2.id
    ORDER BY r.created_at DESC
")->fetchAll();

// Count by status
$pending_count = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")->fetch()['count'];
$resolved_count = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'resolved'")->fetch()['count'];
$dismissed_count = $db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'dismissed'")->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reports - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-flag"></i> Content Reports</h1>
                <div style="display: flex; gap: 10px;">
                    <span class="badge badge-warning">Pending: <?= $pending_count ?></span>
                    <span class="badge badge-success">Resolved: <?= $resolved_count ?></span>
                    <span class="badge badge-danger">Dismissed: <?= $dismissed_count ?></span>
                </div>
            </div>
            
            <!-- Report Stats -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
                <div class="stat-card" style="background: #fff3cd;">
                    <h3>Pending</h3>
                    <p class="stat-number"><?= $pending_count ?></p>
                </div>
                <div class="stat-card" style="background: #d4edda;">
                    <h3>Resolved</h3>
                    <p class="stat-number"><?= $resolved_count ?></p>
                </div>
                <div class="stat-card" style="background: #f8d7da;">
                    <h3>Dismissed</h3>
                    <p class="stat-number"><?= $dismissed_count ?></p>
                </div>
                <div class="stat-card" style="background: #d1ecf1;">
                    <h3>Total</h3>
                    <p class="stat-number"><?= count($reports) ?></p>
                </div>
            </div>
            
            <!-- Report Tabs -->
            <div class="tab-nav">
                <a href="#pending" class="active">Pending</a>
                <a href="#resolved">Resolved</a>
                <a href="#dismissed">Dismissed</a>
                <a href="#all">All Reports</a>
            </div>
            
            <!-- Reports Table -->
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Video</th>
                            <th>Reporter</th>
                            <th>Video Owner</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reports as $report): ?>
                        <tr class="report-row" data-status="<?= $report['status'] ?>">
                            <td>
                                <a href="../watch.php?id=<?= $report['video_id'] ?>" target="_blank">
                                    <?= htmlspecialchars(substr($report['video_title'], 0, 30)) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($report['reporter']) ?></td>
                            <td><?= htmlspecialchars($report['video_owner']) ?></td>
                            <td><?= htmlspecialchars(substr($report['reason'], 0, 50)) ?></td>
                            <td>
                                <?php if($report['status'] == 'pending'): ?>
                                    <span class="badge badge-warning">Pending</span>
                                <?php elseif($report['status'] == 'resolved'): ?>
                                    <span class="badge badge-success">Resolved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Dismissed</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($report['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <?php if($report['status'] == 'pending'): ?>
                                        <button onclick="resolveReport(<?= $report['id'] ?>)" 
                                                class="btn-admin btn-admin-sm" style="background: #28a745;">
                                            <i class="fas fa-check"></i> Resolve
                                        </button>
                                        <button onclick="dismissReport(<?= $report['id'] ?>)" 
                                                class="btn-admin btn-admin-sm" style="background: #6c757d;">
                                            <i class="fas fa-times"></i> Dismiss
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteReport(<?= $report['id'] ?>)" 
                                            class="btn-admin btn-admin-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    // Tab navigation
    document.querySelectorAll('.tab-nav a').forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active tab
            document.querySelectorAll('.tab-nav a').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Filter reports
            const status = this.getAttribute('href').substring(1);
            document.querySelectorAll('.report-row').forEach(row => {
                if (status === 'all' || row.dataset.status === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });
    
    function resolveReport(reportId) {
        if (confirm('Mark this report as resolved?')) {
            window.location.href = `reports.php?action=resolve&id=${reportId}`;
        }
    }
    
    function dismissReport(reportId) {
        if (confirm('Dismiss this report?')) {
            window.location.href = `reports.php?action=dismiss&id=${reportId}`;
        }
    }
    
    function deleteReport(reportId) {
        if (confirm('Delete this report?')) {
            window.location.href = `reports.php?action=delete&id=${reportId}`;
        }
    }
    </script>
</body>
</html>