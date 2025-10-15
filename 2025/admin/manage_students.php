<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include('../config/db.php');

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // You should also delete related results to maintain data integrity
    $conn->query("DELETE FROM result_headers WHERE student_id = $id");
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_students.php");
    exit;
}

// --- Pagination & Search Logic ---
$records_per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

$search = $_GET['search'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $search_term = "%" . $search . "%";
    $where_clause = " WHERE name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?";
    $params = [$search_term, $search_term, $search_term];
    $types = 'sss';
}

// Get total number of records for pagination
$count_query = "SELECT COUNT(*) AS total FROM students" . $where_clause;
$count_stmt = $conn->prepare($count_query);
if (!empty($search)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch students for the current page
$query = "SELECT id, name, email, enrollment_no, branch, semester, verified FROM students" . $where_clause . " ORDER BY id DESC LIMIT ? OFFSET ?";
$types .= 'ii';
$params[] = $records_per_page;
$params[] = $offset;

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$students = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Students</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    :root { --primary-color: #367BF5; --success-color: #2ecc71; --warning-color: #f39c12; --danger-color: #e74c3c; --card-background: rgba(255, 255, 255, 0.7); --sidebar-background: rgba(255, 255, 255, 0.5); --text-color-dark: #121212; --text-color-light: #595959; --shadow-color: rgba(0, 0, 0, 0.1); --border-color: rgba(255, 255, 255, 0.8); --table-border-color: #e0e0e0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%); color: var(--text-color-dark); min-height: 100vh; }
    .dashboard-container { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
    .sidebar { background: var(--sidebar-background); backdrop-filter: blur(15px); border-right: 1px solid var(--border-color); padding: 2rem 1.5rem; }
    .sidebar-header { font-size: 1.5rem; font-weight: 600; margin-bottom: 3rem; text-align: center; }
    .sidebar-header i { margin-right: 10px; }
    .sidebar-nav a { display: flex; align-items: center; color: var(--text-color-light); text-decoration: none; font-size: 1rem; font-weight: 500; padding: 1rem; border-radius: 10px; margin-bottom: 0.5rem; transition: background 0.3s, color 0.3s; }
    .sidebar-nav a:hover, .sidebar-nav a.active { background-color: var(--primary-color); color: #fff; }
    .sidebar-nav a i { width: 20px; margin-right: 1rem; }
    .main-content { padding: 2rem 3rem; }
    .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
    .main-header h2 { font-size: 2rem; font-weight: 600; }
    .header-actions .btn { text-decoration: none; color: #fff; background-color: var(--primary-color); padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 500; }
    .content-card { background: var(--card-background); padding: 2rem; border-radius: 14px; box-shadow: 0 4px 20px var(--shadow-color); }
    .search-form { display: flex; gap: 1rem; margin-bottom: 1.5rem; }
    .search-form input { flex-grow: 1; padding: 0.7rem; border: 1px solid #ccc; border-radius: 8px; font-size: 1rem; }
    .search-form button { background-color: var(--primary-color); color: white; border: none; padding: 0 1.5rem; border-radius: 8px; cursor: pointer; }
    table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
    thead tr { border-bottom: 2px solid var(--table-border-color); }
    th, td { padding: 0.9rem; text-align: left; }
    tbody tr { border-bottom: 1px solid var(--table-border-color); }
    .status { padding: 0.25rem 0.6rem; border-radius: 12px; font-weight: 500; font-size: 0.75rem; color: #fff; }
    .status.verified { background-color: var(--success-color); }
    .status.pending { background-color: var(--warning-color); }
    .action-buttons { display: flex; gap: 0.5rem; }
    .action-btn { text-decoration: none; padding: 0.4rem 0.8rem; border-radius: 6px; color: #fff; font-size: 0.85rem; }
    .action-btn.edit { background-color: var(--primary-color); }
    .action-btn.delete { background-color: var(--danger-color); }
    .pagination { margin-top: 1.5rem; display: flex; justify-content: center; align-items: center; gap: 0.5rem; }
    .pagination a, .pagination span { text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; color: var(--text-color-dark); }
    .pagination a { background-color: rgba(255,255,255,0.5); }
    .pagination a:hover { background-color: rgba(255,255,255,0.9); }
    .pagination .current { background-color: var(--primary-color); color: white; font-weight: 600; }
    .pagination .disabled { color: #aaa; background-color: rgba(0,0,0,0.05); }
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
        <h2>Manage Students</h2>
        <div class="header-actions"><a href="add_student.php" class="btn"><i class="fa-solid fa-user-plus"></i> Add Student</a></div>
      </header>
      <div class="content-card">
        <form method="GET" class="search-form">
          <input type="search" name="search" placeholder="Search by name, email, or enrollment no..." value="<?php echo htmlspecialchars($search); ?>">
          <button type="submit"><i class="fa-solid fa-search"></i></button>
        </form>
        <table>
          <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Enrollment No</th><th>Branch</th><th>Sem</th><th>Verified</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php while ($s = $students->fetch_assoc()): ?>
              <tr>
                <td><?php echo $s['id']; ?></td>
                <td><?php echo htmlspecialchars($s['name']); ?></td>
                <td><?php echo htmlspecialchars($s['email']); ?></td>
                <td><?php echo htmlspecialchars($s['enrollment_no']); ?></td>
                <td><?php echo htmlspecialchars($s['branch']); ?></td>
                <td><?php echo intval($s['semester']); ?></td>
                <td><span class="status <?php echo $s['verified'] ? 'verified' : 'pending'; ?>"><?php echo $s['verified'] ? 'Yes' : 'No'; ?></span></td>
                <td>
                  <div class="action-buttons">
                    <a href="edit_student.php?id=<?php echo $s['id']; ?>" class="action-btn edit"><i class="fa-solid fa-pencil"></i></a>
                    <a href="manage_students.php?delete=<?php echo $s['id']; ?>" class="action-btn delete" onclick="return confirm('Are you sure?')"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
            <?php if ($students->num_rows === 0): ?>
              <tr><td colspan="8" style="text-align: center; padding: 2rem;">No students found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php if ($total_pages > 1): ?>
        <nav class="pagination">
            <?php if ($page > 1): ?><a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a><?php else: ?><span class="disabled">Previous</span><?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php if ($i == $page): ?><span class="current"><?php echo $i; ?></span>
                <?php else: ?><a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?><a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a><?php else: ?><span class="disabled">Next</span><?php endif; ?>
        </nav>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>