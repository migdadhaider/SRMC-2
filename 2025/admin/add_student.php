<?php
session_start();
if(empty($_SESSION['csrf_token'])){
  $_SESSION['csrf_token']=bin2hex(random_bytes(32));
}
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include('../config/db.php');

$msg = "";
// Handle form submission to add a new student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    if( !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])){
$msg="security validation is failed please refresh and try aagain";
    }
    else{

    
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $enrollment = trim($_POST['enrollment_no']);
    $seat = trim($_POST['seat_no']);
    $program = trim($_POST['program']);
    $branch = trim($_POST['branch']);
    $semester = intval($_POST['semester']);
    $password = $_POST['password'];
    $verified = isset($_POST['verified']) ? 1 : 0;

    // Basic validation
    if (empty($name) || empty($email) || empty($enrollment) || empty($branch) || empty($semester) || empty($password)) {
        $msg = "Error: Please fill all required fields.";
    } else {
        // Check for duplicate email or enrollment number
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE email = ? OR enrollment_no = ?");
        $check_stmt->bind_param("ss", $email, $enrollment);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $msg = "Error: A student with this email or enrollment number already exists.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert new student into the database
            $stmt = $conn->prepare("INSERT INTO students (name, email, password, enrollment_no, seat_no, program, branch, semester, verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssii", $name, $email, $hashed_password, $enrollment, $seat, $program, $branch, $semester, $verified);
            
            if ($stmt->execute()) {
                $msg = "Student account created successfully!";
            } else {
                $msg = "Error: Could not create student account. " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Student</title>
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
    .content-card { background: var(--card-background); padding: 2.5rem; border-radius: 14px; box-shadow: 0 4px 20px var(--shadow-color); }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem 2rem; }
    .input-group { display: flex; flex-direction: column; }
    label { font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light); }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"] { width: 100%; padding: 0.8rem; border: 1px solid #ddd; background: var(--input-bg-color); border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; transition: border-color 0.3s ease, box-shadow 0.3s ease; }
    input:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(54, 123, 245, 0.3); }
    .toggle-switch { position: relative; display: inline-block; width: 60px; height: 34px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 34px; transition: .4s; }
    .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; border-radius: 50%; transition: .4s; }
    input:checked + .slider { background-color: var(--success-color); }
    input:checked + .slider:before { transform: translateX(26px); }
    .btn { text-decoration: none; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; font-family: 'Poppins', sans-serif; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .btn i { margin-right: 8px; }
    .btn-primary { background-color: var(--primary-color); color: #fff; }
    .btn-primary:hover { background-color: #2a62c5; }
    .btn-secondary { background-color: rgba(0,0,0,0.05); color: var(--text-color-light); border: 1px solid #ccc; }
    .btn-secondary:hover { background-color: rgba(0,0,0,0.1); }
    #form-actions { margin-top: 2rem; text-align: right; }
    @media (max-width: 992px) { .dashboard-container { grid-template-columns: 1fr; } }
    @media (max-width: 768px) { .main-content { padding: 2rem 1.5rem; } .form-grid { grid-template-columns: 1fr; } }
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
        <h2>Add New Student</h2>
        <a href="manage_students.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to List</a>
      </header>
      
      <?php if ($msg): ?>
        <div class="alert <?php echo str_contains($msg, 'Error') ? 'error' : ''; ?>"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>

      <div class="content-card">
        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']);?>">
          <div class="form-grid">
            <div class="input-group"><label for="name">Full Name</label><input type="text" id="name" name="name" required></div>
            <div class="input-group"><label for="email">Email Address</label><input type="email" id="email" name="email" required></div>
            <div class="input-group"><label for="enrollment_no">Enrollment No</label><input type="text" id="enrollment_no" name="enrollment_no" required></div>
            <div class="input-group"><label for="seat_no">Seat No (Optional)</label><input type="text" id="seat_no" name="seat_no"></div>
            <div class="input-group"><label for="program">Program</label><input type="text" id="program" name="program" placeholder="e.g., B.Tech" required></div>
            <div class="input-group"><label for="branch">Branch</label><input type="text" id="branch" name="branch" placeholder="e.g., Computer Engineering" required></div>
            <div class="input-group"><label for="semester">Current Semester</label><input type="number" id="semester" name="semester" min="1" max="12" required></div>
            <div class="input-group"><label for="password">Set Password</label><input type="password" id="password" name="password" required></div>
            <div class="input-group">
                <label>Account Status</label>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label class="toggle-switch">
                        <input type="checkbox" name="verified" value="1" checked>
                        <span class="slider"></span>
                    </label>
                    <span>Verified</span>
                </div>
            </div>
          </div>
          <div id="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-user-plus"></i> Add Student</button>
          </div>
        </form>
      </div>
    </main>
  </div>
</body>
</html>