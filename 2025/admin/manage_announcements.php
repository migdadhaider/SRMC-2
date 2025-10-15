<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include('../config/db.php');

$msg = "";
// Handle form submission to add a new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    if (!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $title, $content);
        if ($stmt->execute()) {
            $msg = "Announcement posted successfully!";
        } else {
            $msg = "Error: Could not post announcement.";
        }
        $stmt->close();
    } else {
        $msg = "Error: Title and content cannot be empty.";
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_announcements.php");
    exit;
}

// Fetch all existing announcements
$announcements = $conn->query("SELECT id, title, content, created_at FROM announcements ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Announcements</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root { --primary-color: #367BF5; --success-color: #2ecc71; --danger-color: #e74c3c; --card-background: rgba(255, 255, 255, 0.7); --sidebar-background: rgba(255, 255, 255, 0.5); --text-color-dark: #121212; --text-color-light: #595959; --shadow-color: rgba(0, 0, 0, 0.1); --border-color: rgba(255, 255, 255, 0.8); --input-bg-color: rgba(255, 255, 255, 0.5); }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%); color: var(--text-color-dark); min-height: 100vh; }
    .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
    .sidebar { background: var(--sidebar-background); backdrop-filter: blur(15px); border-right: 1px solid var(--border-color); padding: 2rem 1.5rem; }
    .sidebar-header { font-size: 1.5rem; font-weight: 600; margin-bottom: 3rem; text-align: center; }
    .sidebar-header i { margin-right: 10px; }
    .sidebar-nav a { display: flex; align-items: center; color: var(--text-color-light); text-decoration: none; font-size: 1rem; font-weight: 500; padding: 1rem; border-radius: 10px; margin-bottom: 0.5rem; transition: background 0.3s, color 0.3s; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background-color: var(--primary-color); color: #fff; }
    .sidebar-nav a i { width: 20px; margin-right: 1rem; }
    .main-content { padding: 2rem 3rem; overflow-y: auto; }
    .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .main-header h2 { font-size: 2rem; font-weight: 600; }
    .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 10px; color: #fff; background-color: var(--success-color); font-weight: 500; }
    .alert.error { background-color: var(--danger-color); }
    .content-card { background: var(--card-background); padding: 2.5rem; border-radius: 14px; box-shadow: 0 4px 20px var(--shadow-color); margin-bottom: 2rem; }
    .input-group { display: flex; flex-direction: column; margin-bottom: 1.5rem; }
    label { font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light); }
    input, textarea { width: 100%; padding: 0.8rem; border: 1px solid #ddd; background: var(--input-bg-color); border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; }
    textarea { resize: vertical; min-height: 120px; }
    .btn { text-decoration: none; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; font-family: 'Poppins', sans-serif; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .btn i { margin-right: 8px; }
    .btn-primary { background-color: var(--primary-color); color: #fff; }
    #form-actions { margin-top: 1rem; text-align: right; }
    .announcement-list .item { background: rgba(255,255,255,0.4); padding: 1.5rem; border-radius: 10px; border: 1px solid #eee; margin-bottom: 1rem; }
    .item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; }
    .item-header h3 { font-size: 1.2rem; }
    .item-header .meta { font-size: 0.85rem; color: var(--text-color-light); }
    .item-content { color: var(--text-color-dark); }
    .action-btn.delete { text-decoration: none; color: var(--danger-color); font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <nav class="sidebar">
      <div>
        <div class="sidebar-header"><i class="fa-solid fa-user-shield"></i> SRMS Admin</div>
        <div class="sidebar-nav">
  <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span></a>
  <a href="manage_announcements.php"><i class="fa-solid fa-bullhorn"></i> <span>Announcements</span></a>
  <a href="manage_subjects.php"><i class="fa-solid fa-flask"></i> <span>Subjects</span></a>
  <a href="manage_students.php"><i class="fa-solid fa-users"></i> <span>Manage Students</span></a>
  <a href="verify.php"><i class="fa-solid fa-user-check"></i> <span>Verify Students</span></a>
  <a href="upload_result.php"><i class="fa-solid fa-file-arrow-up"></i> <span>Upload Result</span></a>
  <a href="manage_results.php"><i class="fa-solid fa-list-check"></i> <span>Manage Results</span></a>
</div>
      </div>
    </nav>
    <main class="main-content">
      <header class="main-header">
        <h2>Manage Announcements</h2>
      </header>
      <?php if ($msg): ?>
        <div class="alert <?php echo str_contains($msg, 'Error') ? 'error' : ''; ?>"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>
      <div class="content-card">
        <h3>Post a New Announcement</h3>
        <form method="POST">
          <div class="input-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required>
          </div>
          <div class="input-group">
            <label for="content">Content</label>
            <textarea id="content" name="content" required></textarea>
          </div>
          <div id="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Post Announcement</button>
          </div>
        </form>
      </div>
      <div class="content-card">
        <h3>Existing Announcements</h3>
        <div class="announcement-list">
        <?php if ($announcements->num_rows > 0): ?>
          <?php while($row = $announcements->fetch_assoc()): ?>
            <div class="item">
              <div class="item-header">
                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                <div class="meta">
                  <span><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></span> | 
                  <a href="manage_announcements.php?delete=<?php echo $row['id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure?')">Delete</a>
                </div>
              </div>
              <p class="item-content"><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
            <p>No announcements posted yet.</p>
        <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>