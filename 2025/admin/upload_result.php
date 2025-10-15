<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header("Location: login.php"); exit; }
include('../config/db.php');

function getGradePoint($marks) {
    if ($marks >= 90) return 10;
    if ($marks >= 80) return 9;
    if ($marks >= 70) return 8;
    if ($marks >= 60) return 7;
    if ($marks >= 50) return 6;
    if ($marks >= 40) return 5;
    return 0; // Fail
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = intval($_POST['student_id']);
    $semester = intval($_POST['semester']);    
    $result_class = $_POST['result_class'] ?? 'Internal';

    $stmt = $conn->prepare("INSERT INTO result_headers (student_id, semester, result_class) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $student_id, $semester, $result_class);
    if ($stmt->execute()) {
        $header_id = $stmt->insert_id;
        $stmt->close();

        $codes = $_POST['course_code'] ?? [];
        $names = $_POST['subject_name'] ?? [];
        $credits = $_POST['course_credits'] ?? [];
        $theory = $_POST['theory_marks'] ?? [];
        $practical = $_POST['practical_marks'] ?? [];

        $ins = $conn->prepare("INSERT INTO result_items (header_id, course_code, subject_name, course_credits, theory_marks, practical_marks) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($codes as $i => $c) {
            $c = trim($c);
            $n = trim($names[$i] ?? '');
            $cr = intval($credits[$i] ?? 0);
            $t = intval($theory[$i] ?? 0);
            $p = intval($practical[$i] ?? 0);
            if ($c && $n && $cr > 0) {
                $ins->bind_param("issiii", $header_id, $c, $n, $cr, $t, $p);
                $ins->execute();
            }
        }
        $ins->close();

        // Automatic calculation logic
        $total_credits_spi = 0;
        $total_weighted_grade_points_spi = 0;
        foreach ($credits as $i => $credit) {
            $credit_val = intval($credit);
            $total_marks = intval($theory[$i] ?? 0) + intval($practical[$i] ?? 0);
            $grade_point = getGradePoint($total_marks);
            if ($credit_val > 0) {
                $total_credits_spi += $credit_val;
                $total_weighted_grade_points_spi += ($credit_val * $grade_point);
            }
        }
        $spi = ($total_credits_spi > 0) ? round($total_weighted_grade_points_spi / $total_credits_spi, 2) : 0;
        
        $prev_sem_stmt = $conn->prepare("SELECT ri.course_credits, ri.theory_marks, ri.practical_marks FROM result_headers rh JOIN result_items ri ON rh.id = ri.header_id WHERE rh.student_id = ? AND rh.semester < ?");
        $prev_sem_stmt->bind_param("ii", $student_id, $semester);
        $prev_sem_stmt->execute();
        $prev_results = $prev_sem_stmt->get_result();
        $total_prev_credits = 0;
        $total_prev_weighted_points = 0;
        while ($row = $prev_results->fetch_assoc()) {
            $prev_credit = intval($row['course_credits']);
            $prev_marks = intval($row['theory_marks']) + intval($row['practical_marks']);
            $prev_grade_point = getGradePoint($prev_marks);
            if ($prev_credit > 0) {
                $total_prev_credits += $prev_credit;
                $total_prev_weighted_points += ($prev_credit * $prev_grade_point);
            }
        }
        $prev_sem_stmt->close();
        
        $ppi = ($total_prev_credits > 0) ? round($total_prev_weighted_points / $total_prev_credits, 2) : 0;
        
        $total_credits_cgpa = $total_credits_spi + $total_prev_credits;
        $total_weighted_grade_points_cgpa = $total_weighted_grade_points_spi + $total_prev_weighted_points;
        $cgpa = ($total_credits_cgpa > 0) ? round($total_weighted_grade_points_cgpa / $total_credits_cgpa, 2) : 0;

        $update_scores_stmt = $conn->prepare("UPDATE result_headers SET spi = ?, ppi = ?, cgpa = ? WHERE id = ?");
        $update_scores_stmt->bind_param("dddi", $spi, $ppi, $cgpa, $header_id);
        $update_scores_stmt->execute();
        $update_scores_stmt->close();
        $msg = "Result processed successfully! SPI: $spi, CGPA: $cgpa";

    } else {
        $msg = "Error creating result header: " . $stmt->error;
    }
}

// Fetch students for dropdown
$students = $conn->query("SELECT id, name, enrollment_no FROM students WHERE verified = 1 ORDER BY name ASC");
// Fetch subjects from the 'subjectss' table
$subjects_for_dropdown = $conn->query("SELECT * FROM subjectss WHERE is_active = 1 ORDER BY subject_code ASC");
$subjects_json = json_encode($subjects_for_dropdown->fetch_all(MYSQLI_ASSOC));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Result</title>
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
    .main-header { margin-bottom: 2rem; }
    .main-header h2 { font-size: 2rem; font-weight: 600; }
    .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 10px; color: #fff; background-color: var(--success-color); font-weight: 500; }
    .alert.error { background-color: var(--danger-color); }
    .content-card { background: var(--card-background); padding: 2.5rem; border-radius: 14px; box-shadow: 0 4px 20px var(--shadow-color); }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
    .input-group { display: flex; flex-direction: column; }
    label { font-weight: 500; margin-bottom: 0.5rem; color: var(--text-color-light); }
    input, select { width: 100%; padding: 0.8rem; border: 1px solid #ddd; background: var(--input-bg-color); border-radius: 8px; font-family: 'Poppins', sans-serif; font-size: 0.95rem; transition: border-color 0.3s ease, box-shadow 0.3s ease; }
    input:focus, select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(54, 123, 245, 0.3); }
    hr { border: 0; height: 1px; background-color: #ddd; margin: 2rem 0; }
    #subjects-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    #subjects-header h3 { font-size: 1.5rem; font-weight: 600; }
    .subject-entry { background: rgba(255,255,255,0.4); border: 1px solid #eee; border-radius: 10px; padding: 1.5rem; margin-bottom: 1rem; }
    .subject-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: flex-end; }
    .btn { text-decoration: none; padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: 500; font-family: 'Poppins', sans-serif; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
    .btn i { margin-right: 8px; }
    .btn-primary { background-color: var(--primary-color); color: #fff; }
    .btn-secondary { background-color: transparent; color: var(--primary-color); border: 1px solid var(--primary-color); }
    .btn-danger { background-color: var(--danger-color); color: #fff; padding: 0.6rem; }
    #form-actions { margin-top: 2rem; text-align: right; }
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
          <a href="upload_result.php" class="active"><i class="fa-solid fa-file-arrow-up"></i> <span>Upload Result</span></a>
          <a href="manage_results.php"><i class="fa-solid fa-list-check"></i> <span>Manage Results</span></a>
        </div>
    </nav>
    <main class="main-content">
      <header class="main-header">
        <h2>Upload New Result</h2>
      </header>
      <?php if ($msg): ?>
        <div class="alert <?php echo str_contains($msg, 'Error') ? 'error' : ''; ?>"><?php echo htmlspecialchars($msg); ?></div>
      <?php endif; ?>
      <div class="content-card">
        <form method="POST">
          <div class="form-grid">
            <div class="input-group">
              <label for="student_id">Student</label>
              <select name="student_id" id="student_id" required>
                <option value="">-- Select Student --</option>
                <?php while ($s = $students->fetch_assoc()): ?>
                  <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name'] . " (" . $s['enrollment_no'] . ")"); ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="input-group">
              <label for="semester">Semester</label>
              <input type="number" name="semester" id="semester" min="1" max="12" placeholder="e.g., 5" required>
            </div>
            <div class="input-group">
              <label for="result_class">Result Class</label>
              <select name="result_class" id="result_class">
                <option value="Internal">Internal</option>
                <option value="Remedial">Remedial</option>
                <option value="External">External</option>
              </select>
            </div>
          </div>
          <hr>
          <div id="subjects-header">
              <h3>Subjects & Marks</h3>
              <button type="button" class="btn btn-secondary" onclick="addSubject()"><i class="fa-solid fa-plus"></i> Add Subject</button>
          </div>
          <div id="subjects-container">
            <div class="subject-entry">
              <div class="subject-grid">
                <div class="input-group" style="grid-column: 1 / 2;">
                    <label>Select Subject</label>
                    <select class="subject-select" onchange="fillSubjectDetails(this)" required>
                        <option value="">-- Select a Subject --</option>
                        <?php mysqli_data_seek($subjects_for_dropdown, 0); ?>
                        <?php while($s = $subjects_for_dropdown->fetch_assoc()): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_code'] . ' - ' . $s['subject_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <input type="hidden" name="course_code[]" class="course-code-hidden">
                <input type="hidden" name="subject_name[]" class="subject-name-hidden">
                <input type="hidden" name="course_credits[]" class="course-credits-hidden">
                <div class="input-group marks"><label>Theory</label><input type="number" name="theory_marks[]" min="0" max="200" placeholder="Marks"></div>
                <div class="input-group marks"><label>Practical</label><input type="number" name="practical_marks[]" min="0" max="200" placeholder="Marks"></div>
                <div class="remove-btn-wrapper"><button type="button" class="btn btn-danger" onclick="removeSubject(this)"><i class="fa-solid fa-trash"></i></button></div>
              </div>
            </div>
          </div>
          <div id="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Result</button>
          </div>
        </form>
      </div>
    </main>
  </div>
  <script>
    const subjectsData = <?php echo $subjects_json; ?>;
    function fillSubjectDetails(selectElement) {
        const selectedId = selectElement.value;
        const entry = selectElement.closest('.subject-entry');
        const subject = subjectsData.find(s => s.id == selectedId);
        if (subject) {
            entry.querySelector('.course-code-hidden').value = subject.subject_code;
            entry.querySelector('.subject-name-hidden').value = subject.subject_name;
            entry.querySelector('.course-credits-hidden').value = subject.default_credits;
        } else {
            entry.querySelector('.course-code-hidden').value = '';
            entry.querySelector('.subject-name-hidden').value = '';
            entry.querySelector('.course-credits-hidden').value = '';
        }
    }
    function addSubject() {
      const container = document.getElementById("subjects-container");
      const firstBlock = container.querySelector(".subject-entry");
      const clone = firstBlock.cloneNode(true);
      clone.querySelectorAll("input").forEach(input => input.value = "");
      clone.querySelector("select").selectedIndex = 0;
      container.appendChild(clone);
    }
    function removeSubject(btn) {
      const container = document.getElementById("subjects-container");
      if (container.querySelectorAll(".subject-entry").length > 1) {
        btn.closest(".subject-entry").remove();
      } else {
        alert("At least one subject is required.");
      }
    }
  </script>
</body>
</html>