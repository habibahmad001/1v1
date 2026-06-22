<?php
/**
 * Simple WordPress Database Manager
 *
 * INSTRUCTIONS:
 * 1. Upload this file to your WordPress root directory
 * 2. Access via: https://yoursite.com/db-manager.php
 * 3. DEFAULT PASSWORD: change_me_123 (change it below!)
 * 4. DELETE this file when done!
 *
 * SECURITY: This file should be deleted after use.
 */

// ==================== CONFIGURATION ====================
define('DB_MANAGER_PASSWORD', 'change_me_123'); // CHANGE THIS!
define('DB_MANAGER_SESSION', 'db_manager_auth');

// ==================== DATABASE CREDENTIALS ====================
define('DB_NAME', 'u244462117_6b1Qo');
define('DB_USER', 'u244462117_PUa4W');
define('DB_PASSWORD', '#k%1#$zYB8YuJ9mpwD&y');
define('DB_HOST', 'localhost');

// ==================== START SESSION ====================
session_start();

// ==================== AUTHENTICATION ====================
if (isset($_POST['login'])) {
    if ($_POST['password'] === DB_MANAGER_PASSWORD) {
        $_SESSION[DB_MANAGER_SESSION] = true;
    } else {
        $error = "Invalid password!";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION[DB_MANAGER_SESSION]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION[DB_MANAGER_SESSION])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>DB Manager - Login</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: Arial, sans-serif; background: #f0f0f0; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 350px; }
            h2 { margin: 0 0 20px 0; color: #333; text-align: center; }
            input[type="password"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
            button { width: 100%; padding: 12px; background: #2271b1; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
            button:hover { background: #135e96; }
            .error { color: #d63638; text-align: center; margin-bottom: 15px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>🔐 DB Manager Login</h2>
            <?php if (isset($error)) echo '<p class="error">' . htmlspecialchars($error) . '</p>'; ?>
            <form method="post">
                <input type="password" name="password" placeholder="Enter password" required autofocus>
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== DATABASE CONNECTION ====================
$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($mysqli->connect_error) {
    die('<div style="color:red;padding:20px;">Connection failed: ' . htmlspecialchars($mysqli->connect_error) . '</div>');
}

$mysqli->set_charset("utf8mb4");

// ==================== HANDLE ACTIONS ====================
$message = '';
$messageType = '';

// Execute SQL Query
if (isset($_POST['execute_query'])) {
    $query = trim($_POST['sql_query']);

    if (!empty($query)) {
        $result = $mysqli->query($query);

        if ($result) {
            if ($result === true) {
                $affected = $mysqli->affected_rows;
                $message = "Query executed successfully! Affected rows: $affected";
                $messageType = "success";
            } else {
                $message = "Query executed successfully! Found {$result->num_rows} row(s).";
                $messageType = "success";
                $query_result = $result;
            }
        } else {
            $message = "Error: " . htmlspecialchars($mysqli->error);
            $messageType = "error";
        }
    }
}

// ==================== GET DATABASE INFO ====================
$tables = $mysqli->query("SHOW TABLES");
$database_size = 0;
$table_info = [];

while ($table = $tables->fetch_array()) {
    $tableName = $table[0];
    $countResult = $mysqli->query("SELECT COUNT(*) FROM `$tableName`");
    $count = $countResult ? $countResult->fetch_row()[0] : 0;

    $sizeResult = $mysqli->query("SELECT table_name AS 'Table',
        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
        FROM information_schema.TABLES
        WHERE table_schema = '" . DB_NAME . "'
        AND table_name = '$tableName'");

    $size = $sizeResult ? $sizeResult->fetch_assoc()['Size (MB)'] : 0;
    $database_size += $size;

    $table_info[] = [
        'name' => $tableName,
        'rows' => $count,
        'size' => $size
    ];
}

// Browse table
if (isset($_GET['table'])) {
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['table']);
    $columns = $mysqli->query("DESCRIBE `$tableName`");
    $column_info = [];

    while ($col = $columns->fetch_assoc()) {
        $column_info[] = $col;
    }
}

// ==================== HTML OUTPUT ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WordPress DB Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f0f0f1; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #2271b1; color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; font-weight: 600; }
        .logout { background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; font-size: 14px; }
        .logout:hover { background: rgba(255,255,255,0.3); }
        .tabs { display: flex; border-bottom: 1px solid #ddd; background: #f9f9f9; }
        .tab { padding: 15px 25px; cursor: pointer; border-bottom: 3px solid transparent; color: #666; }
        .tab:hover { background: #f0f0f0; }
        .tab.active { border-bottom-color: #2271b1; color: #2271b1; font-weight: 600; }
        .tab-content { padding: 20px; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .info-card { background: #f9f9f9; padding: 20px; border-radius: 6px; border: 1px solid #ddd; }
        .info-card h3 { font-size: 14px; color: #666; margin-bottom: 5px; }
        .info-card .value { font-size: 24px; font-weight: 600; color: #2271b1; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9f9f9; font-weight: 600; color: #333; position: sticky; top: 0; }
        tr:hover { background: #f9f9f9; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        textarea { width: 100%; font-family: 'Monaco', 'Menlo', monospace; font-size: 13px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; resize: vertical; min-height: 150px; background: #f9f9f9; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 500; }
        .btn-primary { background: #2271b1; color: white; }
        .btn-primary:hover { background: #135e96; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .table-link { color: #2271b1; text-decoration: none; font-weight: 500; }
        .table-link:hover { text-decoration: underline; }
        .code { font-family: 'Monaco', 'Menlo', monospace; background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ffeaa7; }
        .query-history { background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 15px; }
        .query-history h3 { font-size: 14px; margin-bottom: 10px; color: #666; }
        .quick-queries { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 15px; }
        .quick-btn { background: #f0f0f0; border: 1px solid #ddd; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .quick-btn:hover { background: #e0e0e0; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; }
        .badge-users { background: #e3f2fd; color: #1976d2; }
        .badge-posts { background: #f3e5f5; color: #7b1fa2; }
        .badge-options { background: #fff3e0; color: #f57c00; }
        .badge-system { background: #ffebee; color: #c62828; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗄️ WordPress Database Manager</h1>
            <a href="?logout=1" class="logout">Logout</a>
        </div>

        <div class="warning">
            <strong>⚠️ Security Warning:</strong> Delete this file after use! Leaving it on your server creates a security risk.
        </div>

        <div class="tabs">
            <div class="tab active" onclick="showTab('dashboard')">Dashboard</div>
            <div class="tab" onclick="showTab('sql')">SQL Query</div>
            <div class="tab" onclick="showTab('users')">Users</div>
            <div class="tab" onclick="showTab('options')">Options</div>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-panel active">
            <div class="info-grid">
                <div class="info-card">
                    <h3>Database</h3>
                    <div class="value"><?php echo htmlspecialchars(DB_NAME); ?></div>
                </div>
                <div class="info-card">
                    <h3>Total Tables</h3>
                    <div class="value"><?php echo count($table_info); ?></div>
                </div>
                <div class="info-card">
                    <h3>Total Size</h3>
                    <div class="value"><?php echo number_format($database_size, 2); ?> MB</div>
                </div>
                <div class="info-card">
                    <h3>MySQL Version</h3>
                    <div class="value"><?php echo $mysqli->server_info; ?></div>
                </div>
            </div>

            <h3 style="margin-bottom: 15px;">📊 Database Tables</h3>
            <table>
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Rows</th>
                        <th>Size (MB)</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_info as $table): ?>
                    <tr>
                        <td>
                            <span class="code"><?php echo htmlspecialchars($table['name']); ?></span>
                            <?php
                            $badge = '';
                            if (strpos($table['name'], 'wp_users') !== false || strpos($table['name'], 'wp_usermeta') !== false) {
                                $badge = ' badge-users';
                            } elseif (strpos($table['name'], 'wp_posts') !== false || strpos($table['name'], 'wp_postmeta') !== false) {
                                $badge = ' badge-posts';
                            } elseif (strpos($table['name'], 'wp_options') !== false) {
                                $badge = ' badge-options';
                            }
                            if ($badge) echo '<span class="badge' . $badge . '">' . strtoupper(str_replace('wp_', '', $table['name'])) . '</span>';
                            ?>
                        </td>
                        <td><?php echo number_format($table['rows']); ?></td>
                        <td><?php echo number_format($table['size'], 2); ?></td>
                        <td>
                            <a href="?table=<?php echo urlencode($table['name']); ?>" class="table-link">Browse</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (isset($_GET['table'])): ?>
            <h3 style="margin: 30px 0 15px;">🔍 Browsing: <?php echo htmlspecialchars($tableName); ?></h3>
            <?php
            $perPage = 50;
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $offset = ($page - 1) * $perPage;

            $totalResult = $mysqli->query("SELECT COUNT(*) FROM `$tableName`");
            $totalRows = $totalResult->fetch_row()[0];
            $totalPages = ceil($totalRows / $perPage);

            $dataQuery = "SELECT * FROM `$tableName` LIMIT $perPage OFFSET $offset";
            $dataResult = $mysqli->query($dataQuery);

            if ($dataResult && $dataResult->num_rows > 0):
            ?>
            <table style="max-width: 100%; overflow-x: auto;">
                <thead>
                    <tr>
                        <?php foreach ($dataResult->fetch_fields() as $field): ?>
                        <th><?php echo htmlspecialchars($field->name); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $dataResult->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                        <td><?php echo htmlspecialchars(substr($value, 0, 200)); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <div style="margin-top: 15px; text-align: center;">
                <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                    <a href="?table=<?php echo urlencode($tableName); ?>&page=<?php echo $i; ?>"
                       style="padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; <?php echo $i == $page ? 'background: #2271b1; color: white;' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <?php if ($totalPages > 10) echo '<span>... ' . $totalPages . ' pages</span>'; ?>
            </div>
            <?php else: ?>
            <p style="padding: 20px; color: #666;">No data in this table.</p>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- SQL Query Tab -->
        <div id="sql" class="tab-panel">
            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <form method="post">
                <div class="quick-queries">
                    <button type="button" class="quick-btn" onclick="setQuery('SELECT * FROM wp_users LIMIT 10')">Show Users (10)</button>
                    <button type="button" class="quick-btn" onclick="setQuery('SELECT * FROM wp_options WHERE option_name LIKE \'site%\'')">Site Options</button>
                    <button type="button" class="quick-btn" onclick="setQuery('SHOW TABLES')">List Tables</button>
                    <button type="button" class="quick-btn" onclick="setQuery('SELECT user_login, user_email, user_registered FROM wp_users ORDER BY user_registered DESC LIMIT 20')">Recent Users</button>
                </div>
                <textarea name="sql_query" id="sqlQuery" placeholder="Enter your SQL query here..."><?php echo isset($_POST['sql_query']) ? htmlspecialchars($_POST['sql_query']) : ''; ?></textarea>
                <div style="margin-top: 15px;">
                    <button type="submit" name="execute_query" class="btn btn-primary">▶ Execute Query</button>
                </div>
            </form>

            <?php if (isset($query_result) && $query_result instanceof mysqli_result): ?>
            <div style="margin-top: 20px; overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($query_result->fetch_fields() as $field): ?>
                            <th><?php echo htmlspecialchars($field->name); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $query_result->fetch_assoc()): ?>
                        <tr>
                            <?php foreach ($row as $value): ?>
                            <td><?php echo htmlspecialchars($value); ?></td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-panel">
            <h3 style="margin-bottom: 15px;">👥 WordPress Users</h3>
            <?php
            $usersQuery = "SELECT ID, user_login, user_email, user_registered,
                         (SELECT meta_value FROM wp_usermeta WHERE user_id = wp_users.ID AND meta_key = 'wp_capabilities') as capabilities
                         FROM wp_users ORDER BY user_registered DESC LIMIT 50";
            $usersResult = $mysqli->query($usersQuery);
            ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $usersResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['ID']); ?></td>
                        <td><?php echo htmlspecialchars($user['user_login']); ?></td>
                        <td><?php echo htmlspecialchars($user['user_email']); ?></td>
                        <td>
                            <?php
                            if ($user['capabilities']) {
                                $caps = unserialize($user['capabilities']);
                                foreach ($caps as $role => $has) {
                                    if ($has) echo '<span class="badge badge-users">' . htmlspecialchars($role) . '</span> ';
                                }
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['user_registered']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Options Tab -->
        <div id="options" class="tab-panel">
            <h3 style="margin-bottom: 15px;">⚙️ WordPress Options (wp_options)</h3>
            <?php
            $optionsQuery = "SELECT option_name, option_value, autoload FROM wp_options
                           WHERE option_name NOT LIKE '_transient%'
                           AND option_name NOT LIKE '_site_transient%'
                           ORDER BY option_name LIMIT 100";
            $optionsResult = $mysqli->query($optionsQuery);
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Option Name</th>
                        <th>Option Value</th>
                        <th>Autoload</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($opt = $optionsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($opt['option_name']); ?></td>
                        <td><?php echo htmlspecialchars(substr($opt['option_value'], 0, 200)); ?></td>
                        <td><?php echo htmlspecialchars($opt['autoload']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function showTab(tabId) {
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            event.target.classList.add('active');
        }

        function setQuery(query) {
            document.getElementById('sqlQuery').value = query;
        }
    </script>
</body>
</html>
<?php
$mysqli->close();
?>
