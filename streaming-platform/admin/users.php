<?php
session_start();
require_once '../config/functions.php';
requireAdmin();

$db = getDB();
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'toggle_admin':
            $user_id = (int)$_POST['user_id'];
            $is_admin = $_POST['is_admin'] === '1' ? 1 : 0;
            $db->query("UPDATE users SET is_admin = $is_admin WHERE id = $user_id");
            $message = 'User admin status updated';
            break;
            
        case 'toggle_ban':
            $user_id = (int)$_POST['user_id'];
            $is_banned = $_POST['is_banned'] === '1' ? 1 : 0;
            $db->query("UPDATE users SET is_banned = $is_banned WHERE id = $user_id");
            $message = 'User ban status updated';
            break;
            
        case 'delete_user':
            $user_id = (int)$_POST['user_id'];
            // Delete user videos first
            $db->query("DELETE FROM videos WHERE user_id = $user_id");
            // Delete user comments
            $db->query("DELETE FROM comments WHERE user_id = $user_id");
            // Delete user
            $db->query("DELETE FROM users WHERE id = $user_id");
            $message = 'User deleted successfully';
            break;
    }
}

// Get all users
$users = $db->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM videos WHERE user_id = u.id) as video_count,
           (SELECT SUM(views) FROM videos WHERE user_id = u.id) as total_views
    FROM users u 
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 250px; background: #1a1a2e; color: white; padding: 20px 0; }
        .admin-main { flex: 1; padding: 20px; background: #f8f9fa; }
        .admin-header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .user-filters { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .admin-table { width: 100%; background: white; border-radius: 8px; overflow: hidden; }
        .admin-table th { background: #f8f9fa; padding: 15px; text-align: left; }
        .admin-table td { padding: 15px; border-top: 1px solid #dee2e6; }
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8em; }
        .badge-admin { background: #ff4757; color: white; }
        .badge-banned { background: #2d3436; color: white; }
        .badge-active { background: #00b894; color: white; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-admin { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-admin-sm { padding: 4px 8px; font-size: 0.8em; }
        .btn-danger { background: #ff4757; color: white; }
        .btn-warning { background: #fdcb6e; color: #2d3436; }
        .btn-success { background: #00b894; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 20px; border-radius: 8px; width: 80%; max-width: 500px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin-sidebar.php'; ?>
        
        <div class="admin-main">
            <div class="admin-header">
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <p>Total: <?= count($users) ?> users</p>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <div class="user-filters">
                <h3>Filters</h3>
                <div class="filter-grid">
                    <input type="text" id="searchUsers" placeholder="Search users..." class="form-control">
                    <select id="filterStatus" class="form-control">
                        <option value="">All Status</option>
                        <option value="admin">Admins</option>
                        <option value="banned">Banned</option>
                        <option value="active">Active</option>
                    </select>
                </div>
            </div>
            
            <div class="admin-card">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Videos</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr class="user-row" 
                            data-username="<?= strtolower($user['username']) ?>"
                            data-status="<?= $user['is_admin'] ? 'admin' : ($user['is_banned'] ? 'banned' : 'active') ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="../<?= getProfilePicUrl($user['profile_pic']) ?>" 
                                         style="width: 40px; height: 40px; border-radius: 50%;">
                                    <?= htmlspecialchars($user['username']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= number_format($user['video_count']) ?></td>
                            <td><?= number_format($user['total_views']) ?></td>
                            <td>
                                <?php if($user['is_admin']): ?>
                                    <span class="badge badge-admin">Admin</span>
                                <?php elseif($user['is_banned']): ?>
                                    <span class="badge badge-banned">Banned</span>
                                <?php else: ?>
                                    <span class="badge badge-active">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="toggleAdmin(<?= $user['id'] ?>, <?= $user['is_admin'] ?>)" 
                                            class="btn-admin btn-admin-sm <?= $user['is_admin'] ? 'btn-danger' : 'btn-warning' ?>">
                                        <i class="fas fa-user-shield"></i>
                                    </button>
                                    
                                    <button onclick="toggleBan(<?= $user['id'] ?>, <?= $user['is_banned'] ?>)" 
                                            class="btn-admin btn-admin-sm <?= $user['is_banned'] ? 'btn-success' : 'btn-danger' ?>">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    
                                    <button onclick="showDeleteModal(<?= $user['id'] ?>, '<?= addslashes($user['username']) ?>')" 
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
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Delete User</h3>
            <p id="deleteMessage">Are you sure you want to delete this user?</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-admin btn-danger">Delete</button>
                    <button type="button" onclick="closeModal()" class="btn-admin">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Search and filter
    document.getElementById('searchUsers').addEventListener('input', function() {
        filterUsers();
    });
    
    document.getElementById('filterStatus').addEventListener('change', function() {
        filterUsers();
    });
    
    function filterUsers() {
        const search = document.getElementById('searchUsers').value.toLowerCase();
        const status = document.getElementById('filterStatus').value;
        
        document.querySelectorAll('.user-row').forEach(row => {
            const username = row.dataset.username;
            const userStatus = row.dataset.status;
            
            const matchesSearch = username.includes(search);
            const matchesStatus = !status || userStatus === status;
            
            row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
        });
    }
    
    // Toggle admin status
    function toggleAdmin(userId, isAdmin) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'toggle_admin';
        form.appendChild(actionInput);
        
        const userIdInput = document.createElement('input');
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        form.appendChild(userIdInput);
        
        const adminInput = document.createElement('input');
        adminInput.name = 'is_admin';
        adminInput.value = isAdmin ? '0' : '1';
        form.appendChild(adminInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    
    // Toggle ban status
    function toggleBan(userId, isBanned) {
        if (userId == <?= $_SESSION['user_id'] ?>) {
            alert('You cannot ban yourself!');
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.name = 'action';
        actionInput.value = 'toggle_ban';
        form.appendChild(actionInput);
        
        const userIdInput = document.createElement('input');
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        form.appendChild(userIdInput);
        
        const bannedInput = document.createElement('input');
        bannedInput.name = 'is_banned';
        bannedInput.value = isBanned ? '0' : '1';
        form.appendChild(bannedInput);
        
        document.body.appendChild(form);
        form.submit();
    }
    
    // Delete user modal
    function showDeleteModal(userId, username) {
        if (userId == <?= $_SESSION['user_id'] ?>) {
            alert('You cannot delete yourself!');
            return;
        }
        
        document.getElementById('deleteMessage').innerHTML = 
            `Are you sure you want to delete user <strong>${username}</strong>?<br>
             This will also delete all their videos and comments!`;
        document.getElementById('deleteUserId').value = userId;
        document.getElementById('deleteModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
</body>
</html>