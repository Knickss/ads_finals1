<?php
include("dbconnection.php");

// Search functionality
$search = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search = trim($_POST['search_value']);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $studentId = $_POST['student_id'];
        $courseId = $_POST['course_id'];
        $enrollDate = $_POST['enroll_date'];

        $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, enroll_date) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $studentId, $courseId, $enrollDate);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        $enrollmentId = $_POST['enrollment_id'];
        $studentId = $_POST['student_id'];
        $courseId = $_POST['course_id'];
        $enrollDate = $_POST['enroll_date'];

        $stmt = $conn->prepare("UPDATE enrollments SET student_id=?, course_id=?, enroll_date=? WHERE enrollment_id=?");
        $stmt->bind_param("iisi", $studentId, $courseId, $enrollDate, $enrollmentId);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['clear'])) {
        $search = '';
    }
}

if (isset($_GET['delete'])) {
    $enrollmentId = $_GET['delete'];

    try {
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE enrollment_id = ?");
        $stmt->bind_param("i", $enrollmentId);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Enrollment deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting enrollment: " . $conn->error;
        }

        $stmt->close();
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting enrollment: " . $e->getMessage();
    }

    header("Location: enrollments.php");
    exit;
}

// Get all enrollments with search filter
$query = "
    SELECT e.enrollment_id, e.enroll_date, 
           s.student_id, s.first_name as student_first, s.last_name as student_last,
           c.course_id, c.course_name,
           t.teacher_id, t.first_name as teacher_first, t.last_name as teacher_last
    FROM enrollments e
    JOIN students s ON e.student_id = s.student_id
    JOIN courses c ON e.course_id = c.course_id
    JOIN teachers t ON c.teacher_id = t.teacher_id
";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " WHERE s.first_name LIKE '%$search%' 
                OR s.last_name LIKE '%$search%' 
                OR c.course_name LIKE '%$search%' 
                OR t.first_name LIKE '%$search%' 
                OR t.last_name LIKE '%$search%'";
}

$query .= " ORDER BY e.enrollment_id DESC";
$enrollments = $conn->query($query);

// Get all students and courses for dropdowns
$students = $conn->query("SELECT * FROM students ORDER BY last_name, first_name");
$courses = $conn->query("SELECT * FROM courses");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollments Management</title>
    <link rel='stylesheet' href="styles.css">
    <style>
        .scrollable-table {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
        }
        .scrollable-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .scrollable-table th, .scrollable-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .scrollable-table thead th {
            position: sticky;
            top: 0;
            background-color: #333;
            z-index: 1;
        }
        .action-links a {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Enrollments Management</h1>

        <div class="nav">
            <a href="index.php">Home</a>
            <a href="students.php">Students</a>
            <a href="teachers.php">Teachers</a>
            <a href="courses.php">Courses</a>
            <a href="enrollments.php">Enrollments</a>
        </div>

        <h2>Add New Enrollment</h2>
        <form method="POST">
            <input type="hidden" name="enrollment_id" id="enrollment_id">
            <div class="form-group">
                <label for="student_id">Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Select Student</option>
                    <?php while($student = $students->fetch_assoc()): ?>
                    <option value="<?php echo $student['student_id']; ?>">
                        <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="course_id">Course</label>
                <select id="course_id" name="course_id" required>
                    <option value="">Select Course</option>
                    <?php while($course = $courses->fetch_assoc()): ?>
                    <option value="<?php echo $course['course_id']; ?>">
                        <?php echo $course['course_name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="enroll_date">Enrollment Date</label>
                <input type="date" id="enroll_date" name="enroll_date" required>
            </div>
            <button type="submit" name="add">Add Enrollment</button>
            <button type="submit" name="update" style="display: none;">Update Enrollment</button>
            <button type="button" id="cancel_edit" style="display: none;">Cancel</button>
        </form>

        <h2>Search Enrollments</h2>
        <form method="POST" style="margin-bottom: 20px;">
            <div style="margin-bottom: 10px;">
                <input type="text" name="search_value" placeholder="Search by student, course, or teacher..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 8px;">
            </div>
            <button type="submit" name="search" style="background-color: #333; color: white; padding: 7px 14px; margin-right: 5px;">Search</button>
            <button type="submit" name="clear" style="background-color: #333; color: white; padding: 7px 14px;">Clear</button>
        </form>

        <h2>Enrollment List</h2>
        <div class="scrollable-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Teacher</th>
                        <th>Enrollment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($enrollment = $enrollments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $enrollment['enrollment_id']; ?></td>
                        <td><?php echo $enrollment['student_first'] . ' ' . $enrollment['student_last']; ?></td>
                        <td><?php echo $enrollment['course_name']; ?></td>
                        <td><?php echo $enrollment['teacher_first'] . ' ' . $enrollment['teacher_last']; ?></td>
                        <td><?php echo $enrollment['enroll_date']; ?></td>
                        <td class="action-links">
                            <a href="#" onclick="editEnrollment(<?php echo $enrollment['enrollment_id']; ?>, <?php echo $enrollment['student_id']; ?>, <?php echo $enrollment['course_id']; ?>, '<?php echo $enrollment['enroll_date']; ?>')">Edit</a>
                            <a href="enrollments.php?delete=<?php echo $enrollment['enrollment_id']; ?>" onclick="return confirm('Are you sure you want to delete this enrollment?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editEnrollment(id, studentId, courseId, enrollDate) {
            document.getElementById('enrollment_id').value = id;
            document.getElementById('student_id').value = studentId;
            document.getElementById('course_id').value = courseId;
            document.getElementById('enroll_date').value = enrollDate;

            document.querySelector('button[name="add"]').style.display = 'none';
            document.querySelector('button[name="update"]').style.display = 'inline-block';
            document.getElementById('cancel_edit').style.display = 'inline-block';
        }

        document.getElementById('cancel_edit').addEventListener('click', function() {
            document.getElementById('enrollment_id').value = '';
            document.getElementById('student_id').value = '';
            document.getElementById('course_id').value = '';
            document.getElementById('enroll_date').value = '';

            document.querySelector('button[name="add"]').style.display = 'inline-block';
            document.querySelector('button[name="update"]').style.display = 'none';
            this.style.display = 'none';
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
