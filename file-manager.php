<?php
/**
 * Simple Web File Manager
 *
 * ⚠️ EXTREME SECURITY RISK - DELETE AFTER USE! ⚠️
 *
 * This file provides full filesystem access to your WordPress installation.
 * It can create, edit, delete, rename, and change permissions on files.
 *
 * INSTRUCTIONS:
 * 1. Change the password below (line 13)
 * 2. Upload to WordPress root directory
 * 3. Access: https://yoursite.com/file-manager.php
 * 4. DELETE IMMEDIATELY after use
 *
 * @author Claude
 * @version 1.0
 */

// ==================== CONFIGURATION ====================
define('FILE_MANAGER_PASSWORD', 'change_me_123'); // CHANGE THIS NOW!
define('FILE_MANAGER_SESSION', 'file_manager_auth');

// Security: Restrict to WordPress directory
define('ROOT_PATH', dirname(__FILE__));
define('ALLOW_DELETE', true);      // Set to false to disable delete
define('ALLOW_EDIT', true);        // Set to false to disable edit
define('ALLOW_UPLOAD', true);      // Set to false to disable upload
define('ALLOW_CHMOD', true);       // Set to false to disable permission changes
define('ALLOW_RECURSIVE_CHMOD', true); // Allow recursive chmod on folders

// Files to protect (cannot be deleted)
define('PROTECTED_FILES', serialize([

]));

// ==================== START SESSION ====================
session_start();

// ==================== AUTHENTICATION ====================
if (isset($_POST['login'])) {
    if ($_POST['password'] === FILE_MANAGER_PASSWORD) {
        $_SESSION[FILE_MANAGER_SESSION] = true;
    } else {
        $error = "Invalid password!";
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION[FILE_MANAGER_SESSION]);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION[FILE_MANAGER_SESSION])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Manager - Login</title>
        <style>
            * { box-sizing: border-box; }
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
            .login-box { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 380px; }
            h2 { margin: 0 0 10px 0; color: #333; text-align: center; font-size: 24px; }
            .subtitle { text-align: center; color: #666; margin-bottom: 25px; font-size: 14px; }
            .warning { background: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; text-align: center; }
            input[type="password"] { width: 100%; padding: 14px; margin: 10px 0; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
            input[type="password"]:focus { outline: none; border-color: #667eea; }
            button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 600; transition: transform 0.2s; }
            button:hover { transform: translateY(-2px); }
            .error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>📁 File Manager</h2>
            <p class="subtitle">Secure File Management</p>
            <div class="warning">⚠️ This tool provides full file system access. Use carefully and delete when done.</div>
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

// ==================== UTILITY FUNCTIONS ====================
function normalizePath($path) {
    // Convert all backslashes to forward slashes first
    $path = str_replace('\\', '/', $path);
    // Remove duplicate slashes
    $path = str_replace('//', '/', $path);
    $parts = explode('/', $path);
    $normalized = [];
    foreach ($parts as $part) {
        if ($part === '..') {
            array_pop($normalized);
        } elseif ($part !== '.' && $part !== '') {
            $normalized[] = $part;
        }
    }
    return implode('/', $normalized);
}

function getFullPath($path) {
    // First normalize the input path
    $normalizedPath = normalizePath($path);

    // Construct full path by joining ROOT_PATH with normalized path
    // Use DIRECTORY_SEPARATOR for the OS-specific separator
    $fullPath = ROOT_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedPath);

    error_log('[File Manager] getFullPath:');
    error_log('  - Input path: ' . $path);
    error_log('  - Normalized path: ' . $normalizedPath);
    error_log('  - ROOT_PATH: ' . ROOT_PATH);
    error_log('  - Constructed full path: ' . $fullPath);

    // Ensure we stay within ROOT_PATH
    $realPath = realpath($fullPath);
    $rootRealPath = realpath(ROOT_PATH);

    error_log('  - Realpath of full path: ' . ($realPath ?: 'FALSE'));
    error_log('  - Realpath of ROOT_PATH: ' . $rootRealPath);

    if ($realPath === false) {
        error_log('  - Realpath failed, returning constructed path');
        // If realpath fails, return the constructed path
        // This can happen if the file doesn't exist yet
        return $fullPath;
    }

    if (strpos($realPath, $rootRealPath) !== 0) {
        error_log('  - Path outside ROOT_PATH, returning ROOT_PATH');
        return ROOT_PATH;
    }

    error_log('  - Returning resolved path: ' . $realPath);
    return $realPath;
}

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function formatPerms($perms) {
    $info = '';
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';

    $info .= (($perms >> 6) & 0x7) ? 'r' : '-';
    $info .= (($perms >> 6) & 0x7) ? 'w' : '-';
    $info .= (($perms >> 6) & 0x7) ? (($perms & 0x800) ? 's' : 'x') : (($perms & 0x800) ? 'S' : '-');

    return sprintf('%o %s', $perms & 0777, $info);
}

function getRelativePath($fullPath) {
    $rootRealPath = realpath(ROOT_PATH);
    if (strpos($fullPath, $rootRealPath) === 0) {
        return ltrim(substr($fullPath, strlen($rootRealPath)), '/');
    }
    return basename($fullPath);
}

function isProtected($filename) {
    $protected = unserialize(PROTECTED_FILES);
    return in_array(basename($filename), $protected);
}

// Safe URL decoding function with debugging
function safeUrlDecode($path, $context = 'path') {
    if (empty($path)) {
        return $path;
    }

    $originalPath = $path;

    // Check if path contains URL-encoded characters
    if (strpos($path, '%') !== false) {
        $path = urldecode($path);
        error_log('[File Manager] URL decode applied (' . $context . '): ' . $originalPath . ' -> ' . $path);
    }

    return $path;
}

// ==================== HANDLE ACTIONS ====================
$message = '';
$messageType = '';

// ==================== AJAX API FOR DIRECTORY LISTING ====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'error' => null,
        'data' => null
    ];

    $requestedPath = isset($_POST['path']) ? $_POST['path'] : (isset($_GET['path']) ? $_GET['path'] : '');
    error_log('[File Manager AJAX] Raw requested path: ' . $requestedPath);

    // CRITICAL FIX: Decode URL-encoded paths
    if (strpos($requestedPath, '%') !== false) {
        error_log('[File Manager AJAX] Path contains URL-encoded characters, decoding...');
        $requestedPath = urldecode($requestedPath);
        error_log('[File Manager AJAX] Decoded path: ' . $requestedPath);
    }

    if (empty($requestedPath)) {
        $response['error'] = 'Path is required';
        error_log('[File Manager AJAX] Error: Empty path');
        echo json_encode($response);
        exit;
    }

    // Normalize and validate the path
    $normalizedPath = normalizePath($requestedPath);
    $targetPath = getFullPath($normalizedPath);

    error_log('[File Manager AJAX] Normalized path: ' . $normalizedPath);
    error_log('[File Manager AJAX] Resolved full path: ' . $targetPath);

    // Check if path exists
    if (!file_exists($targetPath)) {
        $response['error'] = 'Path not found: ' . htmlspecialchars($requestedPath);
        error_log('[File Manager AJAX] Error: Path not found');
        echo json_encode($response);
        exit;
    }

    // Check if path is a directory
    if (!is_dir($targetPath)) {
        $response['error'] = 'Path is not a directory: ' . htmlspecialchars($requestedPath);
        error_log('[File Manager AJAX] Error: Not a directory');
        echo json_encode($response);
        exit;
    }

    // Check if path is within ROOT_PATH
    $rootRealPath = realpath(ROOT_PATH);
    $targetRealPath = realpath($targetPath);

    if ($targetRealPath === false || strpos($targetRealPath, $rootRealPath) !== 0) {
        $response['error'] = 'Access denied: Path is outside allowed directory';
        error_log('[File Manager AJAX] Error: Path outside ROOT_PATH');
        echo json_encode($response);
        exit;
    }

    // Check if path is readable
    if (!is_readable($targetPath)) {
        $response['error'] = 'Permission denied: Directory is not readable';
        error_log('[File Manager AJAX] Error: Not readable');
        echo json_encode($response);
        exit;
    }

    // Scan directory
    $items = [];
    $files = @scandir($targetPath);

    if ($files === false) {
        $response['error'] = 'Failed to scan directory';
        error_log('[File Manager AJAX] Error: scandir failed');
        echo json_encode($response);
        exit;
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '') continue;

        $itemPath = $targetPath . '/' . $file;
        $items[] = [
            'name' => $file,
            'is_dir' => is_dir($itemPath),
            'size' => is_dir($itemPath) ? 0 : filesize($itemPath),
            'perms' => substr(sprintf('%o', fileperms($itemPath)), -4),
            'modified' => date('Y-m-d H:i:s', filemtime($itemPath)),
            'protected' => isProtected($itemPath)
        ];
    }

    // Sort: directories first, then alphabetically
    usort($items, function($a, $b) {
        if ($a['is_dir'] !== $b['is_dir']) {
            return $b['is_dir'] ? 1 : -1;
        }
        return strcasecmp($a['name'], $b['name']);
    });

    // Get relative path for display
    $relativePath = getRelativePath($targetPath);

    // Build breadcrumb
    $breadcrumb = [];
    $pathParts = !empty($relativePath) ? explode('/', trim($relativePath, '/')) : [];
    $buildPath = '';
    $breadcrumb[] = ['name' => '🏠 Root', 'path' => ''];
    foreach ($pathParts as $part) {
        if (!empty($part)) {
            $buildPath = ltrim($buildPath . '/' . $part, '/');
            $breadcrumb[] = ['name' => $part, 'path' => $buildPath];
        }
    }

    $response['success'] = true;
    $response['data'] = [
        'path' => $relativePath,
        'full_path' => $targetPath,
        'breadcrumb' => $breadcrumb,
        'items' => $items,
        'item_count' => count($items)
    ];

    error_log('[File Manager AJAX] Success: Returning ' . count($items) . ' items');
    echo json_encode($response);
    exit;
}

// ==================== AJAX API FOR FILE SAVE ====================
if (isset($_GET['ajax']) && $_GET['ajax'] === 'save' && ALLOW_EDIT) {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 0);

    header('Content-Type: application/json');

    $response = [
        'success' => false,
        'error' => null,
        'data' => null
    ];

    try {
        error_log('[File Manager AJAX Save] Request received');
        error_log('[File Manager AJAX Save] POST data: ' . print_r($_POST, true));
        error_log('[File Manager AJAX Save] GET data: ' . print_r($_GET, true));

    // Get and decode the file path
    $editPathParam = isset($_POST['edit_path']) ? $_POST['edit_path'] : '';
    $content = isset($_POST['file_content']) ? $_POST['file_content'] : '';

    if (empty($editPathParam)) {
        $response['error'] = 'File path is required';
        error_log('[File Manager AJAX Save] Error: Empty file path');
        echo json_encode($response);
        exit;
    }

    // Decode URL-encoded paths
    if (strpos($editPathParam, '%') !== false) {
        $editPathParam = urldecode($editPathParam);
    }

    error_log('[File Manager AJAX Save] File: ' . $editPathParam);
    error_log('[File Manager AJAX Save] Content length: ' . strlen($content) . ' bytes');

    $editPath = getFullPath($editPathParam);

    // Validate the path
    if (!file_exists($editPath)) {
        $response['error'] = 'File not found';
        error_log('[File Manager AJAX Save] Error: File does not exist');
        echo json_encode($response);
        exit;
    }

    if (!is_file($editPath)) {
        $response['error'] = 'Path is not a file';
        error_log('[File Manager AJAX Save] Error: Not a file');
        echo json_encode($response);
        exit;
    }

    // Check if protected
    if (isProtected($editPath)) {
        $response['error'] = 'Cannot save protected file';
        error_log('[File Manager AJAX Save] Error: Protected file');
        echo json_encode($response);
        exit;
    }

    // Check if writable
    if (!is_writable($editPath) && !is_writable(dirname($editPath))) {
        $response['error'] = 'File or directory is not writable';
        error_log('[File Manager AJAX Save] Error: Not writable');
        echo json_encode($response);
        exit;
    }

    // Create backup before saving
    $backupPath = $editPath . '.backup.' . date('YmdHis');
    if (file_exists($editPath)) {
        @copy($editPath, $backupPath);
        error_log('[File Manager AJAX Save] Backup created: ' . $backupPath);
    }

    // Save the file
    $result = @file_put_contents($editPath, $content, LOCK_EX);

    if ($result === false) {
        $response['error'] = 'Failed to save file. Check permissions and disk space.';
        error_log('[File Manager AJAX Save] Error: file_put_contents failed');
        echo json_encode($response);
        exit;
    }

    $bytesWritten = $result;
    error_log('[File Manager AJAX Save] Success: ' . $bytesWritten . ' bytes written');

    $response['success'] = true;
    $response['data'] = [
        'bytes_written' => $bytesWritten,
        'message' => 'File saved successfully! (' . formatSize($bytesWritten) . ' written)',
        'backup_path' => $backupPath
    ];

    error_log('[File Manager AJAX Save] Sending success response');
    echo json_encode($response);
    exit;
    } catch (Exception $e) {
        error_log('[File Manager AJAX Save] Exception: ' . $e->getMessage());
        error_log('[File Manager AJAX Save] Exception trace: ' . $e->getTraceAsString());
        $response['error'] = 'Server error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    } catch (Error $e) {
        error_log('[File Manager AJAX Save] Error: ' . $e->getMessage());
        error_log('[File Manager AJAX Save] Error trace: ' . $e->getTraceAsString());
        $response['error'] = 'Server error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

// ==================== PATH NAVIGATION DEBUGGING ====================
$debugMode = true; // Set to false to disable debugging
if ($debugMode) {
    error_log('[File Manager] REQUEST: ' . $_SERVER['REQUEST_URI']);
    error_log('[File Manager] GET params: ' . print_r($_GET, true));
    error_log('[File Manager] Current path from GET: ' . (isset($_GET['path']) ? $_GET['path'] : '(empty)'));
}

// CRITICAL FIX: Decode URL-encoded path parameter
$currentPath = isset($_GET['path']) ? $_GET['path'] : '';
if (strpos($currentPath, '%') !== false) {
    error_log('[File Manager] Path parameter contains URL-encoded characters, decoding...');
    $currentPath = urldecode($currentPath);
    error_log('[File Manager] Decoded path: ' . $currentPath);
}

$currentPath = normalizePath($currentPath);
$fullPath = getFullPath($currentPath);

if ($debugMode) {
    error_log('[File Manager] Normalized path: ' . $currentPath);
    error_log('[File Manager] Full path: ' . $fullPath);
    error_log('[File Manager] Directory exists: ' . (is_dir($fullPath) ? 'YES' : 'NO'));
}

// Create folder
if (isset($_POST['create_folder']) && ALLOW_EDIT) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $_POST['folder_name']);
    if (!empty($name)) {
        $newPath = $fullPath . '/' . $name;
        if (!file_exists($newPath)) {
            if (mkdir($newPath, 0755)) {
                $message = "Folder created successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to create folder!";
                $messageType = "error";
            }
        } else {
            $message = "Folder already exists!";
            $messageType = "error";
        }
    }
}

// Create file
if (isset($_POST['create_file']) && ALLOW_EDIT) {
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $_POST['file_name']);
    if (!empty($name)) {
        $newPath = $fullPath . '/' . $name;
        if (!file_exists($newPath)) {
            if (file_put_contents($newPath, '')) {
                $message = "File created successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to create file!";
                $messageType = "error";
            }
        } else {
            $message = "File already exists!";
            $messageType = "error";
        }
    }
}

// Upload file
if (isset($_FILES['upload_file']) && ALLOW_UPLOAD && $_FILES['upload_file']['error'] === 0) {
    $filename = basename($_FILES['upload_file']['name']);
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    $targetPath = $fullPath . '/' . $filename;

    if (!isProtected($filename)) {
        if (move_uploaded_file($_FILES['upload_file']['tmp_name'], $targetPath)) {
            $message = "File uploaded successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to upload file!";
            $messageType = "error";
        }
    } else {
        $message = "Cannot overwrite protected file!";
        $messageType = "error";
    }
}

// Delete
if (isset($_GET['delete']) && ALLOW_DELETE) {
    $deleteParam = $_GET['delete'];
    // Decode URL-encoded paths
    if (strpos($deleteParam, '%') !== false) {
        $deleteParam = urldecode($deleteParam);
    }
    $deletePath = getFullPath($deleteParam);
    $relPath = getRelativePath($deletePath);

    if (!isProtected($deletePath)) {
        if (is_dir($deletePath)) {
            // Only delete empty directories or recursively
            $files = array_diff(scandir($deletePath), ['.', '..']);
            if (empty($files)) {
                if (rmdir($deletePath)) {
                    $message = "Folder deleted successfully!";
                    $messageType = "success";
                }
            } else {
                $message = "Folder is not empty!";
                $messageType = "error";
            }
        } elseif (is_file($deletePath)) {
            if (unlink($deletePath)) {
                $message = "File deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to delete file!";
                $messageType = "error";
            }
        }
    } else {
        $message = "Cannot delete protected file!";
        $messageType = "error";
    }
}

// Rename
if (isset($_POST['rename']) && ALLOW_EDIT) {
    $oldPathParam = $_POST['old_path'];
    // Decode URL-encoded paths
    if (strpos($oldPathParam, '%') !== false) {
        $oldPathParam = urldecode($oldPathParam);
    }
    $oldPath = getFullPath($oldPathParam);
    $newName = preg_replace('/[^a-zA-Z0-9._-]/', '', $_POST['new_name']);
    $newPath = dirname($oldPath) . '/' . $newName;

    if (!isProtected($oldPath) && !isProtected($newName)) {
        if (rename($oldPath, $newPath)) {
            $message = "Renamed successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to rename!";
            $messageType = "error";
        }
    } else {
        $message = "Cannot rename protected file!";
        $messageType = "error";
    }
}

// Change permissions
if (isset($_POST['chmod']) && ALLOW_CHMOD) {
    $chmodPathParam = $_POST['chmod_path'];
    // Decode URL-encoded paths
    if (strpos($chmodPathParam, '%') !== false) {
        $chmodPathParam = urldecode($chmodPathParam);
    }
    $chmodPath = getFullPath($chmodPathParam);
    $perms = octdec($_POST['permissions']);
    $recursive = isset($_POST['recursive']) && ALLOW_RECURSIVE_CHMOD;

    if (!isProtected($chmodPath)) {
        if ($recursive && is_dir($chmodPath)) {
            // Recursive chmod
            $changedFiles = chmodRecursive($chmodPath, $perms);
            $message = "Permissions changed recursively! Affected: $changedFiles items.";
            $messageType = "success";
        } else {
            // Single file/folder chmod
            if (chmod($chmodPath, $perms)) {
                $message = "Permissions changed successfully!";
                $messageType = "success";
            } else {
                $message = "Failed to change permissions!";
                $messageType = "error";
            }
        }
    } else {
        $message = "Cannot change permissions on protected file!";
        $messageType = "error";
    }
}

// Recursive chmod function
function chmodRecursive($path, $perms) {
    $count = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if (chmod($item, $perms)) {
            $count++;
        }
    }
    // Also set permissions on the root folder
    chmod($path, $perms);
    return $count;
}

// Save file content
if (isset($_POST['save_file']) && ALLOW_EDIT) {
    $editPathParam = $_POST['edit_path'];
    error_log('[File Manager] Save requested for: ' . $editPathParam);

    // Decode URL-encoded paths
    if (strpos($editPathParam, '%') !== false) {
        error_log('[File Manager] Edit path contains URL-encoded characters, decoding...');
        $editPathParam = urldecode($editPathParam);
        error_log('[File Manager] Decoded edit path: ' . $editPathParam);
    }

    $editPath = getFullPath($editPathParam);
    $content = $_POST['file_content'];

    error_log('[File Manager] Save path resolved to: ' . $editPath);
    error_log('[File Manager] Content length: ' . strlen($content) . ' bytes');

    if (!isProtected($editPath)) {
        if (is_writable($editPath) || is_writable(dirname($editPath))) {
            // Create backup before saving
            $backupPath = $editPath . '.backup.' . date('YmdHis');
            if (file_exists($editPath)) {
                @copy($editPath, $backupPath);
                error_log('[File Manager] Backup created: ' . $backupPath);
            }

            // Save the file
            $result = @file_put_contents($editPath, $content, LOCK_EX);

            if ($result !== false) {
                $bytesWritten = $result;
                $message = "File saved successfully! (" . formatSize($bytesWritten) . " written)";
                $messageType = "success";
                error_log('[File Manager] File saved successfully: ' . $bytesWritten . ' bytes');

                // Clear edit mode to return to file list
                unset($_GET['edit']);
            } else {
                $message = "Failed to save file! Check file permissions and disk space.";
                $messageType = "error";
                error_log('[File Manager] Save failed: file_put_contents returned false');

                // Keep edit mode on error
                $editFile = getRelativePath($editPath);
                $editContent = $content;
            }
        } else {
            $message = "File or directory is not writable! Check permissions.";
            $messageType = "error";
            error_log('[File Manager] Save failed: not writable');

            // Keep edit mode on error
            $editFile = getRelativePath($editPath);
            $editContent = $content;
        }
    } else {
        $message = "Cannot save protected file!";
        $messageType = "error";
        error_log('[File Manager] Save denied: protected file');

        // Keep edit mode on error
        $editFile = getRelativePath($editPath);
        $editContent = $content;
    }
}

// Get directory contents
$items = [];
if (is_dir($fullPath)) {
    $files = scandir($fullPath);
    foreach ($files as $file) {
        if ($file === '.' || $file === '') continue;

        $itemPath = $fullPath . '/' . $file;
        $items[] = [
            'name' => $file,
            'is_dir' => is_dir($itemPath),
            'size' => is_dir($itemPath) ? 0 : filesize($itemPath),
            'perms' => substr(sprintf('%o', fileperms($itemPath)), -4),
            'modified' => date('Y-m-d H:i:s', filemtime($itemPath)),
            'protected' => isProtected($itemPath)
        ];
    }

    usort($items, function($a, $b) {
        if ($a['is_dir'] !== $b['is_dir']) {
            return $b['is_dir'] ? 1 : -1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
}

// Build breadcrumb - fixed path handling
$breadcrumb = [];
$pathParts = [];
if (!empty($currentPath)) {
    $pathParts = explode('/', trim($currentPath, '/'));
}
$buildPath = '';
$breadcrumb[] = ['name' => '🏠 Root', 'path' => ''];
foreach ($pathParts as $part) {
    if (!empty($part)) {
        $buildPath = ltrim($buildPath . '/' . $part, '/');
        $breadcrumb[] = ['name' => $part, 'path' => $buildPath];
    }
}

// Edit file content
$editFile = null;
$editContent = '';
$editError = '';
$editableFileTypes = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'htaccess', 'log', 'sql', 'conf', 'ini', 'env', 'gitignore', 'gitattributes', 'yml', 'yaml', 'twig', 'blade', 'svg'];
$maxEditableSize = 5 * 1024 * 1024; // 5MB limit for editing

if (isset($_GET['edit']) && ALLOW_EDIT) {
    error_log('[File Manager] ==========================================');
    error_log('[File Manager] EDIT REQUEST DEBUGGING:');
    error_log('[File Manager] Raw $_GET[edit]: ' . $_GET['edit']);
    error_log('[File Manager] Raw $_GET[path]: ' . (isset($_GET['path']) ? $_GET['path'] : '(not set)'));
    error_log('[File Manager] PHP $currentPath: ' . $currentPath);
    error_log('[File Manager] ROOT_PATH: ' . ROOT_PATH);

    // CRITICAL FIX: Decode URL-encoded path parameters
    // PHP doesn't always auto-decode $_GET parameters correctly
    $editInputPath = $_GET['edit'];

    // Check if path contains URL-encoded characters (e.g., %2F)
    if (strpos($editInputPath, '%') !== false) {
        error_log('[File Manager] Path contains URL-encoded characters, decoding...');
        $editInputPath = urldecode($editInputPath);
        error_log('[File Manager] Decoded path: ' . $editInputPath);
    }

    error_log('[File Manager] Input edit path: ' . $editInputPath);

    // Normalize the input path (convert backslashes to forward slashes, remove . and ..)
    $normalizedEditPath = normalizePath($editInputPath);
    error_log('[File Manager] Normalized edit path: ' . $normalizedEditPath);

    // Get the full path
    $editPath = getFullPath($normalizedEditPath);
    error_log('[File Manager] Resolved full path: ' . $editPath);

    // Check if file exists
    $fileExists = file_exists($editPath);
    error_log('[File Manager] File exists: ' . ($fileExists ? 'YES' : 'NO'));

    // Check if it's a file (not directory)
    $isFile = is_file($editPath);
    error_log('[File Manager] Is file: ' . ($isFile ? 'YES' : 'NO'));

    // Get realpath for debugging
    $realPath = realpath($editPath);
    error_log('[File Manager] Realpath: ' . ($realPath ?: 'FALSE'));

    // Check if it's within ROOT_PATH
    $rootRealPath = realpath(ROOT_PATH);
    error_log('[File Manager] Root realpath: ' . $rootRealPath);
    error_log('[File Manager] Is within root: ' . (($realPath && strpos($realPath, $rootRealPath) === 0) ? 'YES' : 'NO'));
    error_log('[File Manager] ==========================================');

    if ($fileExists && $isFile) {
        if (isProtected($editPath)) {
            $editError = "This file is protected and cannot be edited.";
            error_log('[File Manager] Edit denied: protected file');
        } elseif (!is_readable($editPath)) {
            $editError = "File is not readable. Check permissions.";
            error_log('[File Manager] Edit denied: not readable');
        } else {
            $fileSize = filesize($editPath);
            if ($fileSize > $maxEditableSize) {
                $editError = "File is too large to edit (" . formatSize($fileSize) . "). Maximum size is " . formatSize($maxEditableSize) . ".";
                error_log('[File Manager] Edit denied: file too large');
            } else {
                $fileExt = strtolower(pathinfo($editPath, PATHINFO_EXTENSION));
                $fileName = basename($editPath);

                // Check if file is editable (has extension or is a known dotfile)
                $isEditable = !empty($fileExt) && in_array($fileExt, $editableFileTypes);

                // Allow editing of files without extension that are likely text files
                if (empty($fileExt) && $fileSize < 1024 * 100) {
                    $isEditable = true;
                }

                if (!$isEditable) {
                    $editError = "File type '.$fileExt' is not supported for editing. Only text-based files can be edited.";
                    error_log('[File Manager] Edit denied: unsupported file type');
                } else {
                    $content = @file_get_contents($editPath);
                    if ($content === false) {
                        $editError = "Failed to read file content. The file may be locked or inaccessible.";
                        error_log('[File Manager] Edit failed: could not read file');
                    } else {
                        // Try to detect encoding and convert to UTF-8
                        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
                        if ($encoding && $encoding !== 'UTF-8') {
                            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                        }

                        $editFile = getRelativePath($editPath);
                        $editContent = $content;
                        error_log('[File Manager] Edit loaded successfully: ' . $editFile . ' (' . strlen($editContent) . ' bytes)');
                    }
                }
            }
        }
    } else {
        $rawEditPath = $_GET['edit'];
        $editError = "File not found: " . htmlspecialchars($rawEditPath);
        $editError .= "<br><small>Resolved path: " . htmlspecialchars($editPath) . "</small>";

        // Show if URL decoding was applied
        if ($rawEditPath !== $editInputPath) {
            $editError .= "<br><small>Decoded path: " . htmlspecialchars($editInputPath) . "</small>";
        }

        if (!$fileExists) {
            $editError .= "<br><small>The file does not exist at this location.</small>";
        } elseif (!$isFile) {
            $editError .= "<br><small>The path exists but is not a file (it might be a directory).</small>";
        }

        error_log('[File Manager] Edit failed: file not found');

        // Try to find similar files
        $dirPath = dirname($editPath);
        if (is_dir($dirPath)) {
            $files = scandir($dirPath);
            $similarFiles = array_filter($files, function($f) {
                return $f !== '.' && $f !== '..';
            });
            if (!empty($similarFiles)) {
                $editError .= "<br><small>Files in this directory: " . implode(', ', array_slice($similarFiles, 0, 5));
                if (count($similarFiles) > 5) {
                    $editError .= "...";
                }
                $editError .= "</small>";
            }
        }
    }
}

// ==================== HTML OUTPUT ====================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3); }
        .header h1 { font-size: 24px; font-weight: 600; }
        .logout { background: rgba(255,255,255,0.2); color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; font-size: 14px; transition: background 0.3s; }
        .logout:hover { background: rgba(255,255,255,0.3); }
        .warning { background: #fff3cd; color: #856404; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107; display: flex; align-items: center; gap: 10px; }
        .warning svg { flex-shrink: 0; }
        .message { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; }
        .message.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .message.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }

        .breadcrumb { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .breadcrumb a { color: #667eea; text-decoration: none; padding: 8px 12px; background: white; border-radius: 6px; font-size: 14px; }
        .breadcrumb a:hover { background: #f0f0f0; }
        .breadcrumb span { padding: 8px 12px; background: #e0e0e0; border-radius: 6px; font-size: 14px; }

        .toolbar { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-1px); }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }

        .file-list { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .file-list table { width: 100%; border-collapse: collapse; }
        .file-list th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #495057; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .file-list td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
        .file-list tr:last-child td { border-bottom: none; }
        .file-list tr:hover { background: #f8f9fa; }
        .file-name { display: flex; align-items: center; gap: 10px; font-weight: 500; }
        .icon { width: 20px; text-align: center; }
        .folder { color: #ffc107; }
        .file { color: #6c757d; }
        .protected { background: #fff3cd; color: #856404; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px; }
        .folder-link { transition: all 0.2s; border-radius: 4px; padding: 4px 8px; margin: -4px -8px; }
        .folder-link:hover { background: #f0f0f0; color: #667eea !important; }
        .folder-link:active { transform: scale(0.98); }
        .file-link { transition: all 0.2s; border-radius: 4px; padding: 4px 8px; margin: -4px -8px; color: #28a745 !important; font-weight: 500; }
        .file-link:hover { background: #f0f0f0; color: #218838 !important; text-decoration: underline; }
        .file-link:active { transform: scale(0.98); }

        .actions { display: flex; gap: 5px; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; }
        .btn-edit { background: #17a2b8; color: white; }
        .btn-chmod { background: #6c757d; color: white; }
        .btn-delete { background: #dc3545; color: white; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal.active { display: flex; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 30px; border-radius: 12px; width: 500px; max-width: 90%; }
        .modal-content h3 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #555; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
        .form-group textarea { font-family: 'Monaco', 'Menlo', monospace; min-height: 300px; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }

        .editor { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; font-family: 'Monaco', 'Menlo', monospace; font-size: 13px; }
        .editor-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #444; }
        .editor-nav h3 { color: #fff; }
        .editor-textarea { width: 100%; min-height: 500px; background: transparent; border: none; color: #d4d4d4; font-family: inherit; font-size: 13px; resize: vertical; }
        .editor-textarea:focus { outline: none; }

        .badge { display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-php { background: #8892bf; color: white; }
        .badge-js { background: #f7df1e; color: #333; }
        .badge-css { background: #563d7c; color: white; }
        .badge-html { background: #e34c26; color: white; }
        .badge-img { background: #a074c4; color: white; }
        .badge-txt { background: #6c757d; color: white; }

        /* Navigation debugging */
        #nav-debug { position: fixed; bottom: 10px; right: 10px; background: rgba(0,0,0,0.8); color: white; padding: 10px; border-radius: 8px; font-size: 12px; max-width: 300px; z-index: 9999; }
        #nav-debug h4 { margin: 0 0 5px 0; font-size: 13px; }
        #nav-debug div { margin: 2px 0; }
        #nav-loading { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.8); color: white; padding: 20px 40px; border-radius: 12px; font-size: 16px; z-index: 10000; }
        .spinner { display: inline-block; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: white; animation: spin 1s ease-in-out infinite; margin-left: 10px; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Path Navigation Status */
        #pathNavStatus { padding: 12px 20px; border-radius: 8px; font-size: 14px; }
        #pathNavStatus.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        #pathNavStatus.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        #pathNavStatus.loading { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 File Manager</h1>
            <a href="?logout=1" class="logout">Logout</a>
        </div>

        <div class="warning">
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
            <span><strong>Security Warning:</strong> This tool provides full file system access. Delete this file immediately after use. Never share the URL or password.</span>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($editFile || $editError): ?>
        <div class="editor">
            <?php if ($editError): ?>
                <div class="message error" style="margin: 0 0 20px 0; padding: 15px;">
                    <?php echo htmlspecialchars($editError); ?>
                    <div style="margin-top: 10px;">
                        <button type="button" onclick="location.href='?path=<?php echo urlencode($currentPath); ?>'" class="btn btn-danger">← Back to File List</button>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($editFile): ?>
            <form method="post" id="editForm">
                <div class="editor-nav">
                    <div>
                        <h3>📝 Editing: <?php echo htmlspecialchars($editFile); ?></h3>
                        <div style="font-size: 12px; color: #888; margin-top: 5px;">
                            File size: <?php echo formatSize(strlen($editContent)); ?> |
                            Lines: <span id="lineCount">-</span> |
                            Encoding: UTF-8
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-success" id="saveButton">💾 Save</button>
                        <button type="button" onclick="confirmCancelEdit()" class="btn btn-danger">✕ Cancel</button>
                    </div>
                </div>
                <input type="hidden" name="edit_path" value="<?php echo htmlspecialchars($editFile); ?>">
                <textarea name="file_content"
                          class="editor-textarea"
                          id="fileContentEditor"
                          spellcheck="false"
                          placeholder="File content will appear here..."><?php echo htmlspecialchars($editContent); ?></textarea>
            </form>

            <div style="margin-top: 15px; padding: 15px; background: #2d2d2d; border-radius: 8px; font-size: 12px; color: #888;">
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <span>💡 <strong>Tips:</strong></span>
                    <span>Ctrl+S to save</span>
                    <span>Esc to cancel</span>
                    <span>Tab inserts tab character</span>
                    <span id="saveStatus" style="color: #4CAF50;"></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="breadcrumb">
            <?php foreach ($breadcrumb as $i => $crumb): ?>
                <?php if ($i < count($breadcrumb) - 1): ?>
                    <a href="?path=<?php echo urlencode($crumb['path']); ?>"><?php echo htmlspecialchars($crumb['name']); ?></a>
                    <span style="color: #999;">/</span>
                <?php else: ?>
                    <span><?php echo htmlspecialchars($crumb['name']); ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="toolbar">
            <button class="btn btn-primary" onclick="document.getElementById('createFolderModal').classList.add('active')">📁 New Folder</button>
            <button class="btn btn-primary" onclick="document.getElementById('createFileModal').classList.add('active')">📄 New File</button>
            <button class="btn btn-success" onclick="document.getElementById('uploadModal').classList.add('active')">⬆️ Upload</button>

            <!-- Manual Path Navigation -->
            <div style="display: flex; gap: 10px; align-items: center; margin-left: auto;">
                <label for="manualPath" style="font-weight: 500; color: #555;">Path:</label>
                <input type="text"
                       id="manualPath"
                       placeholder="Enter path (e.g., wp-content/plugins)"
                       style="flex: 1; min-width: 300px; padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;"
                       onkeypress="if(event.key === 'Enter') loadManualPath()">
                <button class="btn btn-primary" onclick="loadManualPath()">📂 Load Folder</button>
                <button class="btn" onclick="refreshCurrentFolder()" style="background: #6c757d; color: white;">🔄 Refresh</button>
            </div>
        </div>

        <!-- Path Navigation Status -->
        <div id="pathNavStatus" style="display: none; margin-bottom: 20px;"></div>

        <div class="file-list">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pathParts)): ?>
                    <tr>
                        <td colspan="5">
                            <?php
                            $parentPath = implode('/', array_slice($pathParts, 0, -1));
                            ?>
                            <a href="?path=<?php echo urlencode($parentPath); ?>"
                               class="parent-link"
                               style="color: #667eea; text-decoration: none; font-weight: 500; cursor: pointer;">⬆️ Parent Directory</a>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="file-name">
                                <span class="icon"><?php echo $item['is_dir'] ? '📁' : '📄'; ?></span>
                                <?php if ($item['is_dir']): ?>
                                    <?php
                                    $folderPath = empty($currentPath) ? $item['name'] : $currentPath . '/' . $item['name'];
                                    $folderPathEncoded = urlencode($folderPath);
                                    ?>
                                    <a href="?path=<?php echo $folderPathEncoded; ?>"
                                       class="folder-link"
                                       data-folder-name="<?php echo htmlspecialchars($item['name']); ?>"
                                       data-folder-path="<?php echo $folderPathEncoded; ?>"
                                       style="color: #333; text-decoration: none; cursor: pointer;">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php
                                    $filePath = empty($currentPath) ? $item['name'] : $currentPath . '/' . $item['name'];
                                    $isEditable = ALLOW_EDIT && !isProtected($fullPath . '/' . $item['name']);
                                    $fileExt = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
                                    $editableFileTypes = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'htaccess', 'log', 'sql', 'conf', 'ini', 'env', 'gitignore', 'gitattributes', 'yml', 'yaml', 'twig', 'blade'];
                                    $canEditType = in_array($fileExt, $editableFileTypes);
                                    ?>
                                    <?php if ($isEditable && $canEditType): ?>
                                        <a href="?path=<?php echo urlencode($currentPath); ?>&edit=<?php echo urlencode($filePath); ?>"
                                           class="file-link"
                                           data-file-name="<?php echo htmlspecialchars($item['name']); ?>"
                                           data-file-path="<?php echo urlencode($filePath); ?>"
                                           style="color: #333; text-decoration: none; cursor: pointer;">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    <?php endif; ?>
                                    <?php
                                    $badge = '';
                                    switch($fileExt) {
                                        case 'php': $badge = 'badge-php'; break;
                                        case 'js': $badge = 'badge-js'; break;
                                        case 'css': $badge = 'badge-css'; break;
                                        case 'html': case 'htm': $badge = 'badge-html'; break;
                                        case 'jpg': case 'jpeg': case 'png': case 'gif': case 'svg': $badge = 'badge-img'; break;
                                        case 'txt': $badge = 'badge-txt'; break;
                                    }
                                    if ($badge) echo '<span class="badge ' . $badge . '">' . strtoupper($fileExt) . '</span>';
                                    ?>
                                <?php endif; ?>
                                <?php if ($item['protected']): ?>
                                    <span class="protected">Protected</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo $item['is_dir'] ? '-' : formatSize($item['size']); ?></td>
                        <td><code><?php echo htmlspecialchars($item['perms']); ?></code></td>
                        <td style="color: #666; font-size: 13px;"><?php echo $item['modified']; ?></td>
                        <td>
                            <div class="actions">
                                <?php if (!$item['is_dir'] && ALLOW_EDIT && !isProtected($fullPath . '/' . $item['name'])): ?>
                                    <?php
                                    $filePath = empty($currentPath) ? $item['name'] : $currentPath . '/' . $item['name'];
                                    $fileExt = strtolower(pathinfo($item['name'], PATHINFO_EXTENSION));
                                    $editableFileTypes = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'htaccess', 'log', 'sql', 'conf', 'ini', 'env', 'gitignore', 'gitattributes', 'yml', 'yaml', 'twig', 'blade'];
                                    $canEditType = in_array($fileExt, $editableFileTypes);
                                    ?>
                                    <?php if ($canEditType): ?>
                                        <button class="action-btn btn-edit"
                                                onclick="handleEditClick('<?php echo urlencode($filePath); ?>', '<?php echo htmlspecialchars($item['name']); ?>')"
                                                data-file-path="<?php echo urlencode($filePath); ?>"
                                                data-file-name="<?php echo htmlspecialchars($item['name']); ?>">
                                            ✏️ Edit
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (ALLOW_CHMOD && !isProtected($fullPath . '/' . $item['name'])): ?>
                                    <?php
                                    $itemPath = empty($currentPath) ? $item['name'] : $currentPath . '/' . $item['name'];
                                    ?>
                                    <button class="action-btn btn-chmod" onclick="openChmodModal('<?php echo htmlspecialchars($itemPath); ?>', '<?php echo $item['perms']; ?>', <?php echo $item['is_dir'] ? 'true' : 'false'; ?>)">Chmod</button>
                                <?php endif; ?>
                                <?php if (ALLOW_EDIT && !isProtected($fullPath . '/' . $item['name'])): ?>
                                    <?php
                                    $itemPath = empty($currentPath) ? $item['name'] : $currentPath . '/' . $item['name'];
                                    ?>
                                    <button class="action-btn btn-edit" onclick="openRenameModal('<?php echo htmlspecialchars($itemPath); ?>', '<?php echo htmlspecialchars($item['name']); ?>')">Rename</button>
                                <?php endif; ?>
                                <?php if (ALLOW_DELETE && !isProtected($fullPath . '/' . $item['name'])): ?>
                                    <?php
                                    $itemPath = empty($currentPath) ? $item['name'] : $currentPath . '/' . $item['name'];
                                    ?>
                                    <button class="action-btn btn-delete" onclick="confirmDelete('<?php echo htmlspecialchars($itemPath); ?>', '<?php echo $item['is_dir'] ? 'folder' : 'file'; ?>')">Delete</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Navigation Debugging Panel -->
    <div id="nav-debug">
        <h4>🔍 Navigation Debug</h4>
        <div>URL: <span id="debug-url">-</span></div>
        <div>Path param: <span id="debug-path">-</span></div>
        <div>Full path: <span id="debug-fullpath">-</span></div>
        <div>JS currentNavigationPath: <span id="debug-js-path">-</span></div>
        <div>Links found: <span id="debug-links">0</span></div>
        <div>Last click: <span id="debug-click">None</span></div>
        <div>Edit mode: <span id="debug-edit"><?php echo $editFile ? 'YES (' . htmlspecialchars($editFile) . ')' : 'NO'; ?></span></div>
        <div><small>Click folders to navigate, click file names to edit</small></div>
    </div>

    <!-- Loading Indicator -->
    <div id="nav-loading">
        Loading folder contents... <span class="spinner"></span>
    </div>

    <!-- Create Folder Modal -->
    <div id="createFolderModal" class="modal">
        <div class="modal-content">
            <h3>📁 Create New Folder</h3>
            <form method="post">
                <div class="form-group">
                    <label>Folder Name</label>
                    <input type="text" name="folder_name" placeholder="my-folder" required pattern="[a-zA-Z0-9._-]+">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('createFolderModal')" class="btn">Cancel</button>
                    <button type="submit" name="create_folder" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create File Modal -->
    <div id="createFileModal" class="modal">
        <div class="modal-content">
            <h3>📄 Create New File</h3>
            <form method="post">
                <div class="form-group">
                    <label>File Name</label>
                    <input type="text" name="file_name" placeholder="file.php" required pattern="[a-zA-Z0-9._-]+">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('createFileModal')" class="btn">Cancel</button>
                    <button type="submit" name="create_file" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <h3>⬆️ Upload File</h3>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Choose File</label>
                    <input type="file" name="upload_file" required>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('uploadModal')" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-success">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Rename Modal -->
    <div id="renameModal" class="modal">
        <div class="modal-content">
            <h3>✏️ Rename</h3>
            <form method="post" id="renameForm">
                <input type="hidden" name="old_path" id="renameOldPath">
                <div class="form-group">
                    <label>New Name</label>
                    <input type="text" name="new_name" id="renameNewName" required pattern="[a-zA-Z0-9._-]+">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('renameModal')" class="btn">Cancel</button>
                    <button type="submit" name="rename" class="btn btn-primary">Rename</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Chmod Modal -->
    <div id="chmodModal" class="modal">
        <div class="modal-content">
            <h3>🔒 Change Permissions</h3>
            <form method="post" id="chmodForm">
                <input type="hidden" name="chmod_path" id="chmodPath">
                <input type="hidden" id="isFolder" value="0">
                <div class="form-group">
                    <label>Permissions (octal)</label>
                    <select name="permissions" id="chmodPerms">
                        <option value="0644">644 (rw-r--r--) - Default File</option>
                        <option value="0755">755 (rwxr-xr-x) - Executable</option>
                        <option value="0777">777 (rwxrwxrwx) - All Access</option>
                        <option value="0600">600 (rw-------) - Private</option>
                    </select>
                </div>
                <div class="form-group" id="recursiveOption" style="display:none;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="recursive" value="1" style="width: 18px; height: 18px;">
                        <span>🔄 Apply recursively to all files and subfolders</span>
                    </label>
                    <small style="color: #666; display: block; margin-top: 5px;">This will change permissions on the entire folder tree.</small>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal('chmodModal')" class="btn">Cancel</button>
                    <button type="submit" name="chmod" class="btn btn-primary">Change</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function openRenameModal(path, currentName) {
            document.getElementById('renameOldPath').value = path;
            document.getElementById('renameNewName').value = currentName;
            document.getElementById('renameModal').classList.add('active');
        }

        function openChmodModal(path, currentPerms, isFolder) {
            document.getElementById('chmodPath').value = path;
            document.getElementById('chmodPerms').value = '0' + currentPerms;
            document.getElementById('isFolder').value = isFolder ? '1' : '0';

            // Show recursive option for folders
            if (isFolder) {
                document.getElementById('recursiveOption').style.display = 'block';
            } else {
                document.getElementById('recursiveOption').style.display = 'none';
            }

            document.getElementById('chmodModal').classList.add('active');
        }

        function confirmDelete(path, type) {
            if (confirm('Are you sure you want to delete this ' + type + ': ' + path + '?')) {
                location.href = '?path=<?php echo urlencode($currentPath); ?>&delete=' + encodeURIComponent(path);
            }
        }

        // Close modals when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // ==================== FOLDER NAVIGATION DEBUGGING ====================
        console.log('[File Manager] JavaScript loaded');
        console.log('[File Manager] Current path: <?php echo addslashes(json_encode($currentPath)); ?>');

        // Update debug panel
        function updateDebugPanel() {
            var debugUrl = document.getElementById('debug-url');
            var debugPath = document.getElementById('debug-path');
            var debugFullpath = document.getElementById('debug-fullpath');
            var debugJsPath = document.getElementById('debug-js-path');
            var debugLinks = document.getElementById('debug-links');

            if (debugUrl) debugUrl.textContent = window.location.href;
            if (debugPath) debugPath.textContent = '<?php echo htmlspecialchars($currentPath); ?>';
            if (debugFullpath) debugFullpath.textContent = '<?php echo htmlspecialchars($fullPath); ?>';
            if (debugJsPath) debugJsPath.textContent = currentNavigationPath;
            if (debugLinks) debugLinks.textContent = document.querySelectorAll('.folder-link, .parent-link, .breadcrumb a, .file-link').length;
        }

        // Enhanced folder navigation with debugging and fallback
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[File Manager] DOM ready');
            updateDebugPanel();

            // Handle folder link clicks with debugging
            var folderLinks = document.querySelectorAll('.folder-link');
            console.log('[File Manager] Found ' + folderLinks.length + ' folder links');

            folderLinks.forEach(function(link) {
                // Remove any existing listeners by cloning
                var newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);

                newLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var folderName = this.getAttribute('data-folder-name');
                    var folderPath = this.getAttribute('data-folder-path');
                    var targetUrl = this.getAttribute('href');

                    console.log('[File Manager] Folder clicked:');
                    console.log('  - Name: ' + folderName);
                    console.log('  - Path: ' + folderPath);
                    console.log('  - Target URL: ' + targetUrl);

                    // Update debug panel
                    var debugClick = document.getElementById('debug-click');
                    if (debugClick) debugClick.textContent = folderName + ' -> ' + targetUrl;

                    // Show loading indicator
                    var loading = document.getElementById('nav-loading');
                    if (loading) loading.style.display = 'block';

                    // Visual feedback
                    this.style.background = '#e0e0e0';

                    // Navigate with fallback
                    console.log('[File Manager] Navigating to: ' + targetUrl);

                    setTimeout(function() {
                        window.location.href = targetUrl;
                    }, 100);
                });
            });

            // Handle parent directory link
            var parentLink = document.querySelector('.parent-link');
            if (parentLink) {
                console.log('[File Manager] Parent directory link found');

                var newParentLink = parentLink.cloneNode(true);
                parentLink.parentNode.replaceChild(newParentLink, parentLink);

                newParentLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var targetUrl = this.getAttribute('href');
                    console.log('[File Manager] Parent directory clicked');
                    console.log('  - Target URL: ' + targetUrl);

                    var debugClick = document.getElementById('debug-click');
                    if (debugClick) debugClick.textContent = 'Parent Directory -> ' + targetUrl;

                    var loading = document.getElementById('nav-loading');
                    if (loading) loading.style.display = 'block';

                    setTimeout(function() {
                        window.location.href = targetUrl;
                    }, 100);
                });
            }

            // Handle breadcrumb links
            var breadcrumbLinks = document.querySelectorAll('.breadcrumb a');
            console.log('[File Manager] Found ' + breadcrumbLinks.length + ' breadcrumb links');

            breadcrumbLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    console.log('[File Manager] Breadcrumb clicked: ' + this.getAttribute('href'));

                    var loading = document.getElementById('nav-loading');
                    if (loading) loading.style.display = 'block';
                });
            });

            // Detect if navigation is working
            console.log('[File Manager] Navigation handlers initialized');
            console.log('[File Manager] Page URL: ' + window.location.href);
        });

        // Log any navigation errors
        window.addEventListener('error', function(e) {
            console.error('[File Manager] Error:', e.message);
        });

        // Log before unload to see if navigation started
        window.addEventListener('beforeunload', function(e) {
            console.log('[File Manager] Page unloading, navigation should be happening...');

            var loading = document.getElementById('nav-loading');
            if (loading) loading.textContent = 'Navigating...';
        });

        // ==================== FILE EDITING DEBUGGING ====================
        console.log('[File Manager] File editing module loaded');
        console.log('[File Manager] Initial currentNavigationPath: ' + currentNavigationPath);

        // Editor state management (declared outside conditional block for global access)
        var isDirty = false;
        var isSaving = false;
        var editorTextarea = null;
        var saveButton = null;

        // Handle edit button clicks with debugging
        function handleEditClick(filePath, fileName) {
            console.log('[File Manager] ========================================');
            console.log('[File Manager] Edit button clicked:');
            console.log('  - File name: ' + fileName);
            console.log('  - File path parameter: ' + filePath);
            console.log('  - Current navigation path: ' + currentNavigationPath);

            // Get the button element to access data attributes
            var button = event.target;
            console.log('  - Button element:', button);
            console.log('  - Button data-file-fullpath:', button.getAttribute('data-file-fullpath'));
            console.log('  - Button data-current-path:', button.getAttribute('data-current-path'));

            // Use the current navigation path (JavaScript variable, updated during AJAX)
            var currentPath = currentNavigationPath || '';
            console.log('  - Using current path for URL: ' + currentPath);

            // The filePath should already be the full path from root
            // So we use it directly in the edit parameter
            var editUrl = '?path=' + encodeURIComponent(currentPath) + '&edit=' + encodeURIComponent(filePath);
            console.log('  - Final target URL: ' + editUrl);

            // Parse the URL to verify
            var urlParams = new URLSearchParams(editUrl.split('?')[1]);
            console.log('  - URL path param: ' + urlParams.get('path'));
            console.log('  - URL edit param: ' + urlParams.get('edit'));
            console.log('[File Manager] ========================================');

            // Show loading
            var loading = document.getElementById('nav-loading');
            if (loading) {
                loading.textContent = 'Opening editor for ' + fileName + '...';
                loading.style.display = 'block';
            }

            // Navigate to editor
            window.location.href = editUrl;
        }

        // Handle file link clicks (clickable file names)
        var fileLinks = document.querySelectorAll('.file-link');
        console.log('[File Manager] Found ' + fileLinks.length + ' editable file links');

        fileLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                var fileName = this.getAttribute('data-file-name');
                var filePath = this.getAttribute('data-file-path');

                console.log('[File Manager] File link clicked:');
                console.log('  - File: ' + fileName);
                console.log('  - Path: ' + filePath);

                // Update debug panel
                var debugClick = document.getElementById('debug-click');
                if (debugClick) debugClick.textContent = 'Edit: ' + fileName;

                // Show loading
                var loading = document.getElementById('nav-loading');
                if (loading) {
                    loading.textContent = 'Opening editor for ' + fileName + '...';
                    loading.style.display = 'block';
                }
            });
        });

        // Editor functionality
        var editForm = document.getElementById('editForm');
        var lineCount = document.getElementById('lineCount');
        var saveStatus = document.getElementById('saveStatus');

        // Get editor elements
        editorTextarea = document.getElementById('fileContentEditor');
        saveButton = document.getElementById('saveButton');

        if (editorTextarea) {
            console.log('[File Manager] Editor textarea found, initializing...');
            console.log('[File Manager] Initial content length: ' + editorTextarea.value.length + ' bytes');
            console.log('[File Manager] Initial defaultValue length: ' + editorTextarea.defaultValue.length + ' bytes');

            // Update line count
            function updateLineCount() {
                if (lineCount) {
                    var lines = editorTextarea.value.split('\n').length;
                    lineCount.textContent = lines;
                }
            }

            updateLineCount();

            // Mark content as dirty (modified)
            function markAsDirty() {
                if (!isDirty) {
                    isDirty = true;
                    console.log('[File Manager] Content marked as DIRTY');
                    if (saveButton) {
                        saveButton.textContent = '💾 Save*';
                        saveButton.style.background = '#ff9800';
                    }
                }
            }

            // Mark content as clean (saved)
            function markAsClean() {
                isDirty = false;
                console.log('[File Manager] Content marked as CLEAN (saved)');
                if (saveButton) {
                    saveButton.textContent = '💾 Save';
                    saveButton.style.background = '#28a745';
                }
                // Update defaultValue to match current content
                // This is the key fix: after save, defaultValue becomes the new baseline
                editorTextarea.defaultValue = editorTextarea.value;
                console.log('[File Manager] defaultValue updated to current content');
            }

            // Update line count and dirty state on input
            editorTextarea.addEventListener('input', function() {
                updateLineCount();
                var wasDirty = isDirty;
                markAsDirty();
                if (!wasDirty) {
                    console.log('[File Manager] User made a change - content is now dirty');
                }
            });

            // Handle Tab key in editor
            editorTextarea.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    var start = this.selectionStart;
                    var end = this.selectionEnd;

                    // Insert tab character
                    this.value = this.value.substring(0, start) + '\t' + this.value.substring(end);

                    // Move cursor
                    this.selectionStart = this.selectionEnd = start + 1;
                }

                // Ctrl+S to save
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    console.log('[File Manager] Ctrl+S pressed, saving...');
                    saveFileViaAjax();
                }

                // Escape to cancel
                if (e.key === 'Escape') {
                    e.preventDefault();
                    console.log('[File Manager] Escape pressed, canceling edit...');
                    confirmCancelEdit();
                }
            });

            // Save file via AJAX (prevents page navigation)
            function saveFileViaAjax() {
                if (isSaving) {
                    console.log('[File Manager] Save already in progress, ignoring duplicate request');
                    return;
                }

                if (!editForm) {
                    console.error('[File Manager] Edit form not found');
                    return;
                }

                console.log('[File Manager] ========== Starting AJAX save ==========');
                console.log('[File Manager] Content length: ' + editorTextarea.value.length + ' bytes');
                console.log('[File Manager] isDirty: ' + isDirty);

                isSaving = true;

                // Update UI for saving state
                if (saveStatus) {
                    saveStatus.textContent = 'Saving...';
                    saveStatus.style.color = '#ffc107';
                }
                if (saveButton) {
                    saveButton.disabled = true;
                    saveButton.textContent = '⏳ Saving...';
                }

                // Get the file path from the hidden input
                var editPathInput = editForm.querySelector('input[name="edit_path"]');
                if (!editPathInput) {
                    console.error('[File Manager] edit_path input not found in form');
                    isSaving = false;
                    if (saveButton) saveButton.disabled = false;
                    alert('Error: File path not found');
                    return;
                }

                var editPath = editPathInput.value;
                console.log('[File Manager] Saving to path: ' + editPath);

                // Use URL-encoded form data instead of FormData to avoid WAF/mod_security blocks
                // We need to properly encode the content for URL transmission
                var postData = 'edit_path=' + encodeURIComponent(editPath) + '&file_content=' + encodeURIComponent(editorTextarea.value);

                console.log('[File Manager] Sending AJAX request to: ?ajax=save');
                console.log('[File Manager] Request data length: ' + postData.length + ' bytes');

                // Send AJAX request with URL-encoded data
                fetch('?ajax=save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: postData
                })
                .then(function(response) {
                    console.log('[File Manager] AJAX response received');
                    console.log('[File Manager] Response status: ' + response.status);
                    console.log('[File Manager] Response ok: ' + response.ok);

                    // Check if response is OK before parsing JSON
                    if (!response.ok) {
                        throw new Error('HTTP error! Status: ' + response.status);
                    }

                    return response.text().then(function(text) {
                        console.log('[File Manager] Raw response text:', text.substring(0, 200));
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('[File Manager] Failed to parse JSON:', text);
                            throw new Error('Invalid JSON response: ' + text.substring(0, 100));
                        }
                    });
                })
                .then(function(data) {
                    console.log('[File Manager] AJAX save response:', data);

                    if (data.success) {
                        // Success! Mark content as clean
                        markAsClean();
                        console.log('[File Manager] ========== Save SUCCESSFUL ==========');

                        if (saveStatus) {
                            saveStatus.textContent = 'Saved! ' + (data.data ? data.data.message : '');
                            saveStatus.style.color = '#4CAF50';
                        }

                        // Show success message briefly, then clear
                        setTimeout(function() {
                            if (saveStatus && saveStatus.textContent.includes('Saved')) {
                                saveStatus.textContent = '';
                            }
                        }, 3000);
                    } else {
                        // Error occurred
                        console.error('[File Manager] ========== Save FAILED ==========');
                        console.error('[File Manager] Error:', data.error);

                        if (saveStatus) {
                            saveStatus.textContent = 'Error: ' + (data.error || 'Unknown error');
                            saveStatus.style.color = '#f44336';
                        }

                        alert('Failed to save file: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(function(error) {
                    console.error('[File Manager] ========== Save ERROR ==========');
                    console.error('[File Manager] Error details:', error.message);
                    console.error('[File Manager] Error stack:', error.stack);

                    if (saveStatus) {
                        saveStatus.textContent = 'Error: ' + error.message;
                        saveStatus.style.color = '#f44336';
                    }

                    alert('Error: ' + error.message);
                })
                .finally(function() {
                    isSaving = false;

                    // Re-enable save button
                    if (saveButton) {
                        saveButton.disabled = false;
                        // Update button text based on dirty state
                        if (isDirty) {
                            saveButton.textContent = '💾 Save*';
                            saveButton.style.background = '#ff9800';
                        } else {
                            saveButton.textContent = '💾 Save';
                            saveButton.style.background = '#28a745';
                        }
                    }

                    console.log('[File Manager] Save process complete, isDirty: ' + isDirty);
                });
            }

            // Add click handler for save button
            if (saveButton) {
                saveButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('[File Manager] Save button clicked');
                    saveFileViaAjax();
                });
            }

            console.log('[File Manager] Editor initialized');
        } else {
            console.log('[File Manager] No editor textarea found (not in edit mode)');
        }

        // Confirm cancel edit
        function confirmCancelEdit() {
            var editorTextareaLocal = document.getElementById('fileContentEditor');
            // Check if there are unsaved changes using the isDirty flag
            // Also check value vs defaultValue as a fallback for edge cases
            var hasUnsavedChanges = typeof isDirty !== 'undefined' && isDirty;
            if (!hasUnsavedChanges && editorTextareaLocal) {
                hasUnsavedChanges = editorTextareaLocal.value !== editorTextareaLocal.defaultValue;
            }

            if (hasUnsavedChanges) {
                if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
                    window.location.href = '?path=<?php echo urlencode($currentPath); ?>';
                }
            } else {
                window.location.href = '?path=<?php echo urlencode($currentPath); ?>';
            }
        }

        // Warn before leaving with unsaved changes
        // Uses the isDirty flag instead of comparing value to defaultValue
        // This prevents the warning during/after save since we update isDirty on successful save
        window.addEventListener('beforeunload', function(e) {
            var editorTextareaLocal = document.getElementById('fileContentEditor');
            var hasUnsavedChanges = false;

            // Primary check: use the isDirty flag (set by editor input, cleared on successful save)
            if (typeof isDirty !== 'undefined' && isDirty) {
                hasUnsavedChanges = true;
                console.log('[File Manager] beforeunload: Showing warning (isDirty = true)');
            }
            // Fallback check: compare current value to defaultValue
            // This handles edge cases where isDirty might not be set correctly
            else if (editorTextareaLocal && editorTextareaLocal.value !== editorTextareaLocal.defaultValue) {
                hasUnsavedChanges = true;
                console.log('[File Manager] beforeunload: Showing warning (value !== defaultValue)');
            }

            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // ==================== MANUAL PATH NAVIGATION ====================
        console.log('[File Manager] Manual path navigation module loaded');

        var currentNavigationPath = '<?php echo addslashes(json_encode($currentPath)); ?>';

        // Load folder from manual path input
        function loadManualPath() {
            var pathInput = document.getElementById('manualPath');
            var requestedPath = pathInput.value.trim();

            console.log('[File Manager] Manual path load requested:');
            console.log('  - Input path: ' + requestedPath);

            if (!requestedPath) {
                showPathStatus('Please enter a path', 'error');
                return;
            }

            // Show loading status
            showPathStatus('Loading folder: ' + requestedPath + '...', 'loading');

            // Show loading indicator
            var loading = document.getElementById('nav-loading');
            if (loading) {
                loading.textContent = 'Loading folder contents...';
                loading.style.display = 'block';
            }

            // Make AJAX request to load folder
            fetch('?ajax=list', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'path=' + encodeURIComponent(requestedPath)
            })
            .then(function(response) {
                console.log('[File Manager] AJAX response received');
                return response.json();
            })
            .then(function(data) {
                console.log('[File Manager] AJAX data received:');
                console.log('  - Success: ' + data.success);
                console.log('  - Data: ', data);

                // Hide loading indicator
                if (loading) loading.style.display = 'none';

                if (data.success) {
                    // Update current path
                    currentNavigationPath = data.data.path;

                    // Update breadcrumb
                    updateBreadcrumbFromAjax(data.data.breadcrumb);

                    // Render file list
                    renderFileListFromAjax(data.data.items, data.data.path);

                    // Show success message
                    showPathStatus('Loaded ' + data.data.item_count + ' items from: ' + data.data.path, 'success');

                    // Update manual path input for next use
                    pathInput.value = data.data.path;

                    // Update debug panel
                    updateDebugPanelFromAjax(data.data);

                    // Re-attach event listeners to new elements
                    attachNavigationEventListeners();
                } else {
                    // Show error message
                    showPathStatus('Error: ' + (data.error || 'Unknown error'), 'error');
                    console.error('[File Manager] AJAX error: ' + data.error);
                }
            })
            .catch(function(error) {
                console.error('[File Manager] AJAX request failed:', error);

                // Hide loading indicator
                if (loading) loading.style.display = 'none';

                // Show error message
                showPathStatus('Network error: Failed to load folder', 'error');
            });
        }

        // Refresh current folder
        function refreshCurrentFolder() {
            console.log('[File Manager] Refreshing current folder: ' + currentNavigationPath);

            // Use current path
            var pathInput = document.getElementById('manualPath');
            pathInput.value = currentNavigationPath;

            loadManualPath();
        }

        // Show path navigation status
        function showPathStatus(message, type) {
            var statusDiv = document.getElementById('pathNavStatus');
            if (statusDiv) {
                statusDiv.textContent = message;
                statusDiv.className = type;
                statusDiv.style.display = 'block';

                // Auto-hide success and loading messages
                if (type === 'success' || type === 'loading') {
                    setTimeout(function() {
                        statusDiv.style.display = 'none';
                    }, 3000);
                }
            }
        }

        // Update breadcrumb from AJAX data
        function updateBreadcrumbFromAjax(breadcrumb) {
            var breadcrumbDiv = document.querySelector('.breadcrumb');
            if (!breadcrumbDiv) {
                console.error('[File Manager] Breadcrumb element not found');
                return;
            }

            console.log('[File Manager] Updating breadcrumb with ' + breadcrumb.length + ' items');

            var html = '';
            breadcrumb.forEach(function(crumb, index) {
                if (index < breadcrumb.length - 1) {
                    html += '<a href="?path=' + encodeURIComponent(crumb.path) + '" data-breadcrumb-path="' + crumb.path + '">' + htmlspecialchars(crumb.name) + '</a>';
                    html += '<span style="color: #999;">/</span>';
                } else {
                    html += '<span>' + htmlspecialchars(crumb.name) + '</span>';
                }
            });

            breadcrumbDiv.innerHTML = html;
            console.log('[File Manager] Breadcrumb updated');
        }

        // Render file list from AJAX data
        function renderFileListFromAjax(items, currentPath) {
            var tbody = document.querySelector('.file-list tbody');
            if (!tbody) {
                console.error('[File Manager] File list tbody not found');
                return;
            }

            console.log('[File Manager] Rendering ' + items.length + ' items');

            var editableFileTypes = ['txt', 'php', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'htaccess', 'log', 'sql', 'conf', 'ini', 'env', 'gitignore', 'gitattributes', 'yml', 'yaml', 'twig', 'blade'];

            var html = '';

            // Add parent directory link if not in root
            if (currentPath && currentPath !== '') {
                var pathParts = currentPath.split('/').filter(function(p) { return p; });
                if (pathParts.length > 0) {
                    var parentPath = pathParts.slice(0, -1).join('/');
                    html += '<tr>';
                    html += '<td colspan="5">';
                    html += '<a href="?path=' + encodeURIComponent(parentPath) + '" class="parent-link" style="color: #667eea; text-decoration: none; font-weight: 500; cursor: pointer;">⬆️ Parent Directory</a>';
                    html += '</td>';
                    html += '</tr>';
                }
            }

            // Add each item
            items.forEach(function(item) {
                html += '<tr>';
                html += '<td>';
                html += '<div class="file-name">';

                if (item.is_dir) {
                    // Folder
                    var folderPath = currentPath ? currentPath + '/' + item.name : item.name;
                    var folderPathEncoded = encodeURIComponent(folderPath);

                    html += '<span class="icon">📁</span>';
                    html += '<a href="?path=' + folderPathEncoded + '" ';
                    html += 'class="folder-link" ';
                    html += 'data-folder-name="' + htmlspecialchars(item.name) + '" ';
                    html += 'data-folder-path="' + folderPathEncoded + '" ';
                    html += 'style="color: #333; text-decoration: none; cursor: pointer;">';
                    html += htmlspecialchars(item.name);
                    html += '</a>';
                } else {
                    // File
                    var filePath = currentPath ? currentPath + '/' + item.name : item.name;
                    var filePathEncoded = encodeURIComponent(filePath);
                    var fileExt = item.name.split('.').pop().toLowerCase();
                    var canEditType = editableFileTypes.indexOf(fileExt) !== -1;

                    html += '<span class="icon">📄</span>';

                    if (canEditType) {
                        html += '<a href="?path=' + encodeURIComponent(currentPath) + '&edit=' + filePathEncoded + '" ';
                        html += 'class="file-link" ';
                        html += 'data-file-name="' + htmlspecialchars(item.name) + '" ';
                        html += 'data-file-path="' + filePathEncoded + '" ';
                        html += 'style="color: #333; text-decoration: none; cursor: pointer;">';
                        html += htmlspecialchars(item.name);
                        html += '</a>';
                    } else {
                        html += '<span>' + htmlspecialchars(item.name) + '</span>';
                    }

                    // Add file type badge
                    var badgeClass = '';
                    switch(fileExt) {
                        case 'php': badgeClass = 'badge-php'; break;
                        case 'js': badgeClass = 'badge-js'; break;
                        case 'css': badgeClass = 'badge-css'; break;
                        case 'html':
                        case 'htm': badgeClass = 'badge-html'; break;
                        case 'jpg':
                        case 'jpeg':
                        case 'png':
                        case 'gif':
                        case 'svg': badgeClass = 'badge-img'; break;
                        case 'txt': badgeClass = 'badge-txt'; break;
                    }
                    if (badgeClass) {
                        html += '<span class="badge ' + badgeClass + '">' + fileExt.toUpperCase() + '</span>';
                    }
                }

                if (item.protected) {
                    html += '<span class="protected">Protected</span>';
                }

                html += '</div>';
                html += '</td>';
                html += '<td>' + (item.is_dir ? '-' : formatSize(item.size)) + '</td>';
                html += '<td><code>' + htmlspecialchars(item.perms) + '</code></td>';
                html += '<td style="color: #666; font-size: 13px;">' + item.modified + '</td>';
                html += '<td><div class="actions">';

                // Add action buttons
                if (!item.is_dir) {
                    if (canEditType) {
                        // Debug logging for Edit button
                        console.log('[File Manager] Creating Edit button for:');
                        console.log('  - Item name: ' + item.name);
                        console.log('  - Current path: ' + currentPath);
                        console.log('  - File path: ' + filePath);
                        console.log('  - File path encoded: ' + filePathEncoded);

                        html += '<button class="action-btn btn-edit" ';
                        html += 'data-file-fullpath="' + filePathEncoded + '" ';
                        html += 'data-current-path="' + currentPath + '" ';
                        html += 'onclick="handleEditClick(\'' + filePathEncoded + '\', \'' + htmlspecialchars(item.name).replace(/'/g, "\\'") + '\')">✏️ Edit</button>';
                    }
                }

                html += '<button class="action-btn btn-chmod" onclick="openChmodModal(\'' + (currentPath ? currentPath + '/' + item.name : item.name) + '\', \'' + item.perms + '\', ' + (item.is_dir ? 'true' : 'false') + ')">Chmod</button>';

                if (!item.is_dir) {
                    html += '<button class="action-btn btn-edit" onclick="openRenameModal(\'' + (currentPath ? currentPath + '/' + item.name : item.name) + '\', \'' + htmlspecialchars(item.name).replace(/'/g, "\\'") + '\')">Rename</button>';
                }

                html += '<button class="action-btn btn-delete" onclick="confirmDelete(\'' + (currentPath ? currentPath + '/' + item.name : item.name) + '\', \'' + (item.is_dir ? 'folder' : 'file') + '\')">Delete</button>';

                html += '</div></td>';
                html += '</tr>';
            });

            tbody.innerHTML = html;
            console.log('[File Manager] File list rendered with ' + items.length + ' items');
        }

        // Update debug panel from AJAX data
        function updateDebugPanelFromAjax(data) {
            var debugUrl = document.getElementById('debug-url');
            var debugPath = document.getElementById('debug-path');
            var debugFullpath = document.getElementById('debug-fullpath');
            var debugJsPath = document.getElementById('debug-js-path');
            var debugLinks = document.getElementById('debug-links');
            var debugClick = document.getElementById('debug-click');

            if (debugUrl) debugUrl.textContent = window.location.href;
            if (debugPath) debugPath.textContent = data.path;
            if (debugFullpath) debugFullpath.textContent = data.full_path;
            if (debugJsPath) debugJsPath.textContent = currentNavigationPath;
            if (debugLinks) debugLinks.textContent = document.querySelectorAll('.folder-link, .parent-link, .breadcrumb a, .file-link').length;
            if (debugClick) debugClick.textContent = 'Manual load: ' + data.item_count + ' items';
        }

        // Attach navigation event listeners to dynamically added elements
        function attachNavigationEventListeners() {
            console.log('[File Manager] Attaching navigation event listeners...');

            // Folder links
            var folderLinks = document.querySelectorAll('.folder-link');
            folderLinks.forEach(function(link) {
                // Clone to remove existing listeners
                var newLink = link.cloneNode(true);
                link.parentNode.replaceChild(newLink, link);

                newLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var folderName = this.getAttribute('data-folder-name');
                    var folderPath = this.getAttribute('data-folder-path');
                    var targetUrl = this.getAttribute('href');

                    console.log('[File Manager] Folder clicked (AJAX rendered):');
                    console.log('  - Name: ' + folderName);
                    console.log('  - Path: ' + folderPath);

                    // Update debug panel
                    var debugClick = document.getElementById('debug-click');
                    if (debugClick) debugClick.textContent = folderName + ' -> ' + targetUrl;

                    // Update manual path input
                    var pathInput = document.getElementById('manualPath');
                    var pathParam = new URLSearchParams(targetUrl.split('?')[1]).get('path');
                    if (pathInput) pathInput.value = pathParam || '';

                    // Load folder via AJAX
                    var loadPath = pathParam || '';
                    currentNavigationPath = loadPath;

                    // Show loading and navigate
                    var loading = document.getElementById('nav-loading');
                    if (loading) {
                        loading.textContent = 'Opening folder: ' + folderName + '...';
                        loading.style.display = 'block';
                    }

                    // Navigate using AJAX
                    fetch('?ajax=list', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'path=' + encodeURIComponent(loadPath)
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (loading) loading.style.display = 'none';

                        if (data.success) {
                            currentNavigationPath = data.data.path;
                            updateBreadcrumbFromAjax(data.data.breadcrumb);
                            renderFileListFromAjax(data.data.items, data.data.path);
                            showPathStatus('Opened folder: ' + data.data.path, 'success');
                            attachNavigationEventListeners();
                        } else {
                            showPathStatus('Error: ' + (data.error || 'Unknown error'), 'error');
                        }
                    })
                    .catch(function(error) {
                        if (loading) loading.style.display = 'none';
                        showPathStatus('Network error: Failed to open folder', 'error');
                    });
                });
            });

            // Parent directory link
            var parentLink = document.querySelector('.parent-link');
            if (parentLink) {
                var newParentLink = parentLink.cloneNode(true);
                parentLink.parentNode.replaceChild(newParentLink, parentLink);

                newParentLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var targetUrl = this.getAttribute('href');
                    var pathParam = new URLSearchParams(targetUrl.split('?')[1]).get('path');

                    console.log('[File Manager] Parent directory clicked (AJAX rendered)');

                    // Update manual path input
                    var pathInput = document.getElementById('manualPath');
                    if (pathInput) pathInput.value = pathParam || '';

                    currentNavigationPath = pathParam || '';

                    // Load via AJAX
                    loadManualPath();
                });
            }

            // Breadcrumb links
            var breadcrumbLinks = document.querySelectorAll('.breadcrumb a');
            breadcrumbLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    console.log('[File Manager] Breadcrumb clicked (AJAX rendered): ' + this.getAttribute('data-breadcrumb-path'));

                    var pathParam = this.getAttribute('data-breadcrumb-path');

                    // Update manual path input
                    var pathInput = document.getElementById('manualPath');
                    if (pathInput) pathInput.value = pathParam;

                    currentNavigationPath = pathParam;

                    // Load via AJAX
                    var loading = document.getElementById('nav-loading');
                    if (loading) loading.style.display = 'block';

                    fetch('?ajax=list', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'path=' + encodeURIComponent(pathParam)
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (loading) loading.style.display = 'none';

                        if (data.success) {
                            currentNavigationPath = data.data.path;
                            updateBreadcrumbFromAjax(data.data.breadcrumb);
                            renderFileListFromAjax(data.data.items, data.data.path);
                            attachNavigationEventListeners();
                        } else {
                            showPathStatus('Error: ' + (data.error || 'Unknown error'), 'error');
                        }
                    })
                    .catch(function(error) {
                        if (loading) loading.style.display = 'none';
                        showPathStatus('Network error', 'error');
                    });
                });
            });

            // File links
            var fileLinks = document.querySelectorAll('.file-link');
            fileLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    console.log('[File Manager] File clicked (AJAX rendered): ' + this.getAttribute('data-file-name'));

                    var fileName = this.getAttribute('data-file-name');
                    var loading = document.getElementById('nav-loading');
                    if (loading) {
                        loading.textContent = 'Opening editor for ' + fileName + '...';
                        loading.style.display = 'block';
                    }

                    // Allow default navigation for edit links
                    // Don't prevent default - let the browser navigate to edit mode
                });
            });

            console.log('[File Manager] Navigation event listeners attached');
        }

        // HTML escape helper function
        function htmlspecialchars(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Format size helper function
        function formatSize(bytes) {
            var units = ['B', 'KB', 'MB', 'GB'];
            bytes = Math.max(bytes, 0);
            var pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024));
            pow = Math.min(pow, units.length - 1);
            bytes /= Math.pow(1024, pow);
            return Math.round(bytes * 100) / 100 + ' ' + units[pow];
        }

        // Initialize manual path input with current path
        document.addEventListener('DOMContentLoaded', function() {
            var pathInput = document.getElementById('manualPath');
            if (pathInput) {
                pathInput.value = currentNavigationPath;
                console.log('[File Manager] Manual path input initialized with: ' + currentNavigationPath);
            }

            // Update debug panel on initial load
            updateDebugPanel();
        });
    </script>
</body>
</html>
