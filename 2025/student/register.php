<?php
session_start(); // Start session to pass data across pages

// Include your database configuration file
// Make sure the path is correct
// include('../config/db.php');

// --- Mock Database Connection for Demonstration ---
// This part is for demonstration purposes.
// In your real application, you would remove this and use your actual `db.php`.
class MockDB {
    public $error;
    function prepare($query) {
        return new MockStatement($this);
    }
    function close() { return true; }
}
class MockStatement {
    private $db;
    public function __construct($db) { $this->db = $db; }
    function bind_param($types, ...$vars) {}
    function execute() {
        // Simulate a pre-existing user for testing the error message
        if (isset($_POST['email']) && $_POST['email'] === 'existing@example.com') {
             // Let execute succeed to allow num_rows check
            return true;
        }
        return true;
    }
    function store_result() {}
    function num_rows() {
        // Simulate user already exists
        if (isset($_POST['email']) && $_POST['email'] == 'existing@example.com') {
            return 1;
        }
        return 0;
    }
    function close() { return true; }
    function error() { return $this->db->error; }
}
$conn = new MockDB();
// --- End of Mock Database Connection ---


$error = ""; // The $success variable is no longer needed on this page.

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $enrollment = trim($_POST['enrollment']);
    $seat = trim($_POST['seat']);
    $program = trim($_POST['program']);
    $branch = trim($_POST['branch']);
    $semester = intval($_POST['semester']);
    $password_plain = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // --- Main Validation Logic ---

    // 1. Check if passwords match
    if ($password_plain !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    } 
    // 2. Check for empty required fields
    else if (!$name || !$email || !$enrollment || !$program || !$branch || !$semester || !$password_plain) {
        $error = "Please fill all required fields.";
    } 
    // 3. If all checks pass, proceed to database interaction
    else {
        // Hash the password only after validation is complete
        $password_hashed = password_hash($password_plain, PASSWORD_BCRYPT);

        // Check if user already exists
        $check = $conn->prepare("SELECT id FROM students WHERE email = ? OR enrollment_no = ?");
        $check->bind_param("ss", $email, $enrollment);
        $check->execute();
        $check->store_result();

        if ($check->num_rows() > 0) {
            $error = "An account with this Email or Enrollment Number already exists!";
        } else {
            // Insert new student record
            $stmt = $conn->prepare("INSERT INTO students 
                (name, email, password, enrollment_no, seat_no, program, branch, semester, verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
            
            // Bind the HASHED password, not the plain text one
            $stmt->bind_param("sssssssi", $name, $email, $password_hashed, $enrollment, $seat, $program, $branch, $semester);

           if ($stmt->execute()) {
    $success = "Registration successful! Please wait for admin verification.";
    $_SESSION['message'] = "Registration successful! Please wait for admin verification.";
    $_SESSION['registered_email'] = $email;
    header("Location: /2025/admin/verify.php");
    exit();
}

         else {
                $error = "An error occurred during registration: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Registration</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #367BF5;
      --card-background: rgba(255, 255, 255, 0.7);
      --text-color-dark: #121212;
      --text-color-light: #595959;
      --shadow-color: rgba(0, 0, 0, 0.1);
      --border-color: rgba(255, 255, 255, 0.8);
      --input-bg-color: rgba(255, 255, 255, 0.5);
      --error-color: #e74c3c;
      --success-color: #2ecc71;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 2rem;
    }

    .register-container {
      width: 100%;
      max-width: 700px;
      background: var(--card-background);
      backdrop-filter: blur(15px);
      -webkit-backdrop-filter: blur(15px);
      border-radius: 20px;
      border: 1px solid var(--border-color);
      box-shadow: 0 8px 32px 0 var(--shadow-color);
      padding: 3rem;
    }

    h2 {
      font-size: 2.2rem;
      font-weight: 600;
      margin-bottom: 1rem;
      color: var(--text-color-dark);
      text-align: center;
    }
    
    .message {
        color: #fff;
        padding: 0.9rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-weight: 500;
        text-align: center;
    }
    .success { background-color: var(--success-color); }
    .error { background-color: var(--error-color); }

    form { text-align: left; }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    .input-group { margin-bottom: 1rem; }
    .full-width { grid-column: 1 / -1; }

    label {
      display: block;
      font-weight: 500;
      margin-bottom: 0.5rem;
      color: var(--text-color-light);
    }

    input {
      width: 100%;
      padding: 0.9rem;
      border: 1px solid #ddd;
      background: var(--input-bg-color);
      border-radius: 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 1rem;
      transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(54, 123, 245, 0.3);
    }

    button {
      width: 100%;
      padding: 0.9rem;
      border: none;
      border-radius: 8px;
      background-color: var(--primary-color);
      color: #fff;
      font-size: 1.1rem;
      font-weight: 600;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      margin-top: 1.5rem;
      transition: all 0.3s ease;
    }

    button:hover {
      background-color: #2a62c5;
      transform: translateY(-3px);
      box-shadow: 0 4px 15px rgba(54, 123, 245, 0.4);
    }

    .links {
      margin-top: 1.5rem;
      font-size: 0.9rem;
      text-align: center;
    }

    .links a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
      margin: 0 0.5rem;
      transition: text-decoration 0.2s;
    }

    .links a:hover { text-decoration: underline; }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
        .register-container { padding: 2rem; }
    }

  </style>
</head>
<body>
  <div class="register-container">
    <h2>Create Student Account</h2>
    
    <?php if ($error): ?>
      <div class='message error'><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="input-group full-width">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
      </div>

      <div class="input-group full-width">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required>
      </div>

      <div class="form-grid">
        <div class="input-group">
          <label for="enrollment">Enrollment No</label>
          <input type="text" id="enrollment" name="enrollment" placeholder="e.g., 2101234567" required>
        </div>
        <div class="input-group">
          <label for="seat">Seat No</label>
          <input type="text" id="seat" name="seat" placeholder="e.g., S21CE001 (Optional)">
        </div>
        <div class="input-group">
          <label for="program">Program</label>
          <input type="text" id="program" name="program" placeholder="e.g., B.Tech" required>
        </div>
        <div class="input-group">
          <label for="branch">Branch</label>
          <input type="text" id="branch" name="branch" placeholder="e.g., Computer Engineering" required>
        </div>
      </div>
      
      <div class="input-group full-width">
        <label for="semester">Semester</label>
        <input type="number" id="semester" name="semester" min="1" max="12" placeholder="e.g., 5" required>
      </div>
      
      <div class="form-grid">
          <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Choose a strong password" required>
          </div>
          <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
          </div>
      </div>

      <button type="submit" class="full-width">Register</button>
    </form>

    <div class="links">
      <a href="login.php">Already have an account? Login</a>
    </div>
  </div>
</body>
</html>

