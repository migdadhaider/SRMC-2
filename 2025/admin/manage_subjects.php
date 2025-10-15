<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include('../config/db.php');

$msg = "";
$edit_subject = null;

// Handle Add/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['subject_code']);
    $name = trim($_POST['subject_name']);
    $credits = intval($_POST['default_credits']);
    $id = intval($_POST['subject_id'] ?? 0);

    if ($id > 0) { // Update logic
        $stmt = $conn->prepare("UPDATE subjectss SET subject_code=?, subject_name=?, default_credits=? WHERE id=?");
        $stmt->bind_param("ssii", $code, $name, $credits, $id);
        if ($stmt->execute()) { $msg = "Subject updated successfully!"; }
        else { $msg = "Error updating subject."; }
    } else { // Add logic
        $stmt = $conn->prepare("INSERT INTO subjectss (subject_code, subject_name, default_credits) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $code, $name, $credits);
        if ($stmt->execute()) { $msg = "Subject added successfully!"; } 
        else { $msg = "Error: Subject code might already exist."; }
    }
    $stmt->close();
}

// Handle "Delete" (which is now a soft delete)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("UPDATE subjectss SET is_active = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_subjects.php"); exit;
}

// Handle Edit (fetch data to pre-fill the form)
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM subjectss WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_subject = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch all active subjects to display in the list
$subjects = $conn->query("SELECT * FROM subjectss WHERE is_active = 1 ORDER BY subject_code ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Subjects</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root { --primary-color: #367BF5; --success-color: #2ecc71; --danger-color: #e74c3c; --card-background: rgba(255, 255, 255, 0.7); --sidebar-background: rgba(255, 255, 255, 0.5); --text-color-dark: #121212; --text-color-light: #595959; --shadow-color: rgba(0, 0, 0, 0.1); --border-color: rgba(255, 255, 255, 0.8); --input-bg-color: rgba(255, 255, 255, 0.5); --table-border-color: #e0e0e0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%); min-height: 100vh; }
    .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
    .sidebar { background: var(--sidebar-background); backdrop-filter: blur(15px); border-right: 1px solid var(--border-color); padding: 2rem 1.5rem; }
    .sidebar-header { font-size: 1.5rem; font-weight: 600; margin-bottom: 3rem; text-align: center; }
    .sidebar-nav a { display: flex; align-items: center; color: var(--text-color-light); text-decoration: none; font-size: 1rem; font-weight: 500; padding: 1rem; border-radius: 10px; margin-bottom: 0.5rem; transition: background 0.3s, color 0.3s; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background-color: var(--primary-color); color: #fff; }
    .sidebar-nav a i { width: 20px; margin-right: 1rem; }
    .main-content { padding: 2rem 3rem; }
    .main-header h2 { font-size: 2rem; font-weight: 600; margin-bottom: 2rem;}
    .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 10px; color: #fff; background-color: var(--success-color); font-weight: 500; }
    .alert.error { background-color: var(--danger-color); }
    .content-card { background: var(--card-background); padding: 2.5rem; border-radius: 14px; box-shadow: 0 4px 20px var(--shadow-color); margin-bottom: 2rem; }
    .content-card h3 { font-size: 1.3rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
    .form-grid { display: grid; grid-template-columns: 1fr 2fr 1fr auto; gap: 1rem; align-items: flex-end; }
    .input-group { display: flex; flex-direction: column; }
    label { font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light); }
    input { width: 100%; padding: 0.7rem; border: 1px solid #ccc; background: var(--input-bg-color); border-radius: 8px; font-size: 1rem; }
    .btn { text-decoration: none; padding: 0.7rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; }
    .btn-primary { background-color: var(--primary-color); color: #fff; }
    .btn-secondary { background-color: #ddd; color: var(--text-color-dark); }
    table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
    thead tr { border-bottom: 2px solid var(--table-border-color); }
    th, td { padding: 0.9rem; text-align: left; }
    .action-buttons { display: flex; gap: 0.5rem; }
    .action-btn { text-decoration: none; padding: 0.4rem 0.8rem; border-radius: 6px; color: #fff; }
    .action-btn.edit { background-color: var(--primary-color); }
    .action-btn.delete { background-color: var(--danger-color); }
  </style>
</head>
<body>
<div class="dashboard-container">
    <nav class="sidebar">
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
    </nav>
    <main class="main-content">
        <header class="main-header"><h2>Manage Subjects</h2></header>
        <?php if ($msg): ?><div class="alert <?php echo str_contains($msg, 'Error') ? 'error' : ''; ?>"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <div class="content-card">
            <h3><?php echo $edit_subject ? 'Edit Subject' : 'Add New Subject'; ?></h3>
            <form method="POST" action="manage_subjects.php">
                <input type="hidden" name="subject_id" value="<?php echo $edit_subject['id'] ?? 0; ?>">
                <div class="form-grid">
                    <div class="input-group"><label>Subject Code</label><input type="text" name="subject_code" value="<?php echo htmlspecialchars($edit_subject['subject_code'] ?? ''); ?>" required></div>
                    <div class="input-group"><label>Subject Name</label><input type="text" name="subject_name" value="<?php echo htmlspecialchars($edit_subject['subject_name'] ?? ''); ?>" required></div>
                    <div class="input-group"><label>Default Credits</label><input type="number" name="default_credits" value="<?php echo htmlspecialchars($edit_subject['default_credits'] ?? ''); ?>" required></div>
                    <div>
                        <button type="submit" class="btn btn-primary"><?php echo $edit_subject ? 'Update Subject' : 'Add Subject'; ?></button>
                        <?php if ($edit_subject): ?><a href="manage_subjects.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
        <div class="content-card">
            <h3>Subject List</h3>
            <table>
                <thead><tr><th>Code</th><th>Name</th><th>Credits</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while($s = $subjects->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['subject_code']); ?></td>
                        <td><?php echo htmlspecialchars($s['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($s['default_credits']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="?edit=<?php echo $s['id']; ?>" class="action-btn edit"><i class="fa-solid fa-pencil"></i></a>
                                <a href="?delete=<?php echo $s['id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to deactivate this subject?')"><i class="fa-solid fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>