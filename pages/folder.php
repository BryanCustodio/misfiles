<?php
session_start();
require_once '../db/file_system.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$folder_id = $_GET['id'];

// Fetch folder name
$stmt = $conn->prepare("SELECT folder_name FROM folders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $folder_id, $user_id);
$stmt->execute();
$stmt->bind_result($folder_name);
$stmt->fetch();
$stmt->close();

if (!$folder_name) {
    echo "Folder not found.";
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $file = $_FILES['upload_file'];
    $filename = basename($file['name']);
    $target_dir = "../uploads/";
    $target_file = $target_dir . uniqid() . "_" . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $stmt = $conn->prepare("INSERT INTO files (folder_id, file_name, file_path) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $folder_id, $filename, $target_file);
        $stmt->execute();
        $stmt->close();
        header("Location: folder.php?id=$folder_id");
        exit();
    } else {
        $error = "File upload failed.";
    }
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $file_id = $_POST['file_id'];
    $stmt = $conn->prepare("SELECT file_path FROM files WHERE id = ? AND folder_id = ?");
    $stmt->bind_param("ii", $file_id, $folder_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    if ($file_path && file_exists($file_path)) {
        unlink($file_path);
    }

    $stmt = $conn->prepare("DELETE FROM files WHERE id = ? AND folder_id = ?");
    $stmt->bind_param("ii", $file_id, $folder_id);
    $stmt->execute();
    $stmt->close();

    header("Location: folder.php?id=$folder_id");
    exit();
}

// Fetch files
$stmt = $conn->prepare("SELECT * FROM files WHERE folder_id = ?");
$stmt->bind_param("i", $folder_id);
$stmt->execute();
$result = $stmt->get_result();
$files = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($folder_name) ?> - Folder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">File System</a>
        <div class="d-flex">
            <span class="navbar-text me-3">Welcome, <?= htmlspecialchars($username) ?></span>
            <a href="logout.php" class="btn btn-outline-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2><?= htmlspecialchars($folder_name) ?> - Files</h2>

    <!-- Upload Form -->
    <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
        <div class="input-group">
            <input type="file" name="upload_file" class="form-control" required>
            <button type="submit" class="btn btn-success">Upload</button>
        </div>
        <?php if (isset($error)): ?>
            <div class="text-danger mt-2"><?= $error ?></div>
        <?php endif; ?>
    </form>

    <!-- File List -->
    <?php if (count($files) > 0): ?>
        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Filename</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?= htmlspecialchars($file['file_name']) ?></td>
                        <td>
                            <a href="<?= $file['file_path'] ?>" class="btn btn-success btn-sm" target="_blank">View</a>
                            <a href="<?= $file['file_path'] ?>" class="btn btn-primary btn-sm" download>Download</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                                <button type="submit" name="delete_file" class="btn btn-danger btn-sm" onclick="return confirm('Delete this file?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No files found in this folder.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>