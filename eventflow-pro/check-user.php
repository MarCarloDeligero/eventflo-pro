<?php
require_once 'includes/config.php';

$auth = new Auth();
echo "<h3>User Status Check</h3>";

if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    echo "<p>✅ Logged in as: " . htmlspecialchars($user->name) . "</p>";
    echo "<p>📧 Email: " . htmlspecialchars($user->email) . "</p>";
    echo "<p>🎭 Role: " . htmlspecialchars($user->role) . "</p>";
    echo "<p>🔑 Has super_admin role: " . ($auth->hasRole('super_admin') ? 'YES' : 'NO') . "</p>";
    
    if (!$auth->hasRole('super_admin')) {
        echo "<p style='color: red;'>❌ You don't have super_admin privileges!</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Not logged in</p>";
}
?>