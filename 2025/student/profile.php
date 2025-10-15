<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit;
}
include('../config/db.php');

$student_id = $_SESSION['student_id'];
$msg_success = "";
$msg_error = "";

// --- Handle Password Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $msg_error = "Please fill all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $msg_error = "New password and confirm password do not match.";
    } else {
        // Fetch current password hash from DB
        $stmt = $conn->prepare("SELECT password FROM students WHERE id = ?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->bind_result($hashed_password);
        $stmt->fetch();
        $stmt->close();

        // Verify the current password
        if (password_verify($current_password, $hashed_password)) {
            // Hash the new password
            $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update the password in the database
            $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_hashed_password, $student_id);
            if ($update_stmt->execute()) {
                $msg_success = "Password updated successfully!";
            } else {
                $msg_error = "An error occurred. Please try again.";
            }
            $update_stmt->close();
        } else {
            $msg_error = "Incorrect current password.";
        }
    }
}

// --- Fetch Student Profile Data ---
$stmt = $conn->prepare("SELECT name, email, enrollment_no, seat_no, program, branch, semester FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root { --primary-color: #367BF5; --success-color: #2ecc71; --danger-color: #e74c3c; --card-background: rgba(255, 255, 255, 0.7); --text-color-dark: #121212; --text-color-light: #595959; --shadow-color: rgba(0, 0, 0, 0.1); --border-color: rgba(255, 255, 255, 0.8); --input-bg-color: rgba(255, 255, 255, 0.5); }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Poppins', sans-serif; background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%); color: var(--text-color-dark); min-height: 100vh; padding: 2rem; }
    .container { max-width: 800px; margin: 0 auto; }
    .page-header { background: var(--card-background); backdrop-filter: blur(15px); border: 1px solid var(--border-color); padding: 1.5rem 2rem; border-radius: 14px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; box-shadow: 0 4px 20px var(--shadow-color); }
    .page-header h2 { font-size: 1.5rem; font-weight: 600; }
    .page-header .actions { display: flex; gap: 1rem; }
    .page-header .actions a { text-decoration: none; color: var(--primary-color); background-color: rgba(255,255,255,0.5); padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 500; transition: background-color 0.3s; }
    .page-header .actions a:hover { background-color: white; }
    .page-header .actions a.logout { background-color: var(--primary-color); color: white; }
    .page-header .actions a.logout:hover { background-color: #2a62c5; }
    .content-card { background: var(--card-background); padding: 2.5rem; border-radius: 14px; box-shadow: 0 4px 20px var(--shadow-color); margin-bottom: 2rem; }
    .content-card h3 { font-size: 1.3rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
    .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem 2rem; }
    .profile-item { font-size: 1rem; }
    .profile-item span { display: block; font-weight: 500; color: var(--text-color-light); font-size: 0.9rem; margin-bottom: 0.25rem; }
    .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 10px; color: #fff; font-weight: 500; }
    .alert.success { background-color: var(--success-color); }
    .alert.error { background-color: var(--danger-color); }
    .input-group { display: flex; flex-direction: column; margin-bottom: 1.5rem; }
    label { font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light); }
    input[type="password"] { width: 100%; padding: 0.8rem; border: 1px solid #ddd; background: var(--input-bg-color); border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; }
    .btn-primary { text-decoration: none; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; font-family: 'Poppins', sans-serif; cursor: pointer; background-color: var(--primary-color); color: #fff; }
    #form-actions { margin-top: 1rem; text-align: right; }
    @media (max-width: 768px) { .profile-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="container">
    <header class="page-header">
      <h2>My Profile</h2>
      <div class="actions">
        <a href="result.php">View Results</a>
        <a href="logout.php" class="logout">Logout</a>
      </div>
    </header>
    
    <div class="content-card">
        <h3>Your Information</h3>
        <?php if ($student): ?>
        <div class="profile-grid">
            <div class="profile-item"><span>Full Name</span> <?php echo htmlspecialchars($student['name']); ?></div>
            <div class="profile-item"><span>Email Address</span> <?php echo htmlspecialchars($student['email']); ?></div>
            <div class="profile-item"><span>Enrollment No.</span> <?php echo htmlspecialchars($student['enrollment_no']); ?></div>
            <div class="profile-item"><span>Seat No.</span> <?php echo htmlspecialchars($student['seat_no'] ?? '-'); ?></div>
            <div class="profile-item"><span>Program</span> <?php echo htmlspecialchars($student['program']); ?></div>
            <div class="profile-item"><span>Branch</span> <?php echo htmlspecialchars($student['branch']); ?></div>
            <div class="profile-item"><span>Current Semester</span> <?php echo htmlspecialchars($student['semester']); ?></div>
        </div>
        <?php else: ?>
            <p>Could not retrieve student information.</p>
        <?php endif; ?>
    </div>

    <div class="content-card">
        <h3>Change Password</h3>
        <?php if ($msg_success): ?><div class="alert success"><?php echo $msg_success; ?></div><?php endif; ?>
        <?php if ($msg_error): ?><div class="alert error"><?php echo $msg_error; ?></div><?php endif; ?>
        <form method="POST">
            <div class="profile-grid">
                <div class="input-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="input-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <div id="form-actions">
                <button type="submit" class="btn-primary">Update Password</button>
            </div>
        </form>
    </div>
  </div>
</body>
</html>