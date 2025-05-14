<?php 
include("dbconnection.php");
session_start();

$searchTerm = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("INSERT INTO students (first_name, last_name, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $firstName, $lastName, $email);
        if($stmt->execute()) {
            $_SESSION['message'] = "Student added successfully";
        } else {
            $_SESSION['error'] = "Error adding student: " . $conn->error;
        }
        $stmt->close();
        header("Location: students.php");
        exit;

    } elseif (isset($_POST['update'])) {
        $studentId = $_POST['student_id'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];

        $stmt = $conn->prepare("UPDATE students SET first_name=?, last_name=?, email=? WHERE student_id=?");
        $stmt->bind_param("sssi", $firstName, $lastName, $email, $studentId);
        if($stmt->execute()) {
            $_SESSION['message'] = "Student updated successfully";
        } else {
            $_SESSION['error'] = "Error updating student: " . $conn->error;
        }
        $stmt->close();
        header("Location: students.php");
        exit;
    } elseif (isset($_POST['search'])) {
        $searchTerm = trim($_POST['search_term']);
    }
} elseif (isset($_GET['clear'])) {
    header("Location: students.php");
    exit;
} elseif (isset($_GET['delete'])) {
    $studentId = $_GET['delete'];

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['message'] = "Student deleted successfully";
        } else {
            $_SESSION['error'] = "No Student found with that ID";
        }

        $stmt->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting student: " . $e->getMessage();
    }

    header("Location: students.php");
    exit;
} elseif (isset($_GET['view_courses'])) {
    $studentId = $_GET['view_courses'];
    $viewCourses = true;
} else {
    $viewCourses = false;
}

// Search query
if (!empty($searchTerm)) {
    $searchLike = "%" . $searchTerm . "%";
    $stmt = $conn->prepare("SELECT * FROM students WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?");
    $stmt->bind_param("sss", $searchLike, $searchLike, $searchLike);
    $stmt->execute();
    $students = $stmt->get_result();
    $stmt->close();
} else {
    $students = $conn->query("SELECT * FROM students");
}

// Get course enrollment counts using LEFT JOIN
$studentEnrollmentCounts = $conn->query("
    SELECT s.student_id, COUNT(e.enrollment_id) as course_count
    FROM students s
    LEFT JOIN enrollments e ON s.student_id = e.student_id
    GROUP BY s.student_id
");

// Convert to associative array for easy lookup
$enrollmentCountsByStudent = [];
while($row = $studentEnrollmentCounts->fetch_assoc()) {
    $enrollmentCountsByStudent[$row['student_id']] = $row['course_count'];
}

// Get detailed course info if viewing a specific student's courses
if ($viewCourses && isset($studentId)) {
    $stmt = $conn->prepare("
        SELECT c.course_id, c.course_name, e.enroll_date, t.first_name AS teacher_first_name, t.last_name AS teacher_last_name
        FROM enrollments e
        LEFT JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
        WHERE e.student_id = ?
        ORDER BY e.enroll_date DESC
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $studentCourses = $stmt->get_result();
    $stmt->close();
    
    // Get student name for display
    $stmt = $conn->prepare("SELECT first_name, last_name FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $studentInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management</title>
    <link rel='stylesheet' href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .scrollable-table {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
            margin-top: 10px;
        }
        .scrollable-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .scrollable-table th, .scrollable-table td {
            padding: 10px;
            border: 1px solid #ccc;
        }
        .scrollable-table th {
            background-color: #333;
            color: white;
            /* Optional: stick the header row */
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
        }
        .alert {
            padding: 10px;
            margin: 10px 0;
        }
        .alert-success {
            background-color: #e6ffed;
            border-left: 5px solid #3c763d;
        }
        .alert-danger {
            background-color: #ffe6e6;
            border-left: 5px solid #a94442;
        }
        .nav a {
            margin-right: 10px;
        }
        .action-links a {
            margin-right: 8px;
        }
        .back-link {
            margin-bottom: 15px;
            display: block;
        }
        .course-badge {
            background-color: #007bff;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Students Management</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="nav">
            <a href="index.php">Home</a>
            <a href="students.php">Students</a>
            <a href="teachers.php">Teachers</a>
            <a href="courses.php">Courses</a>
            <a href="enrollments.php">Enrollments</a>
        </div>

        <?php if ($viewCourses && isset($studentId) && isset($studentInfo)): ?>
            <a href="students.php" class="back-link">← Back to Students List</a>
            <h2>Courses for <?php echo htmlspecialchars($studentInfo['first_name'] . ' ' . $studentInfo['last_name']); ?></h2>
            
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>Course Name</th>
                            <th>Teacher</th>
                            <th>Enrollment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($studentCourses->num_rows > 0): ?>
                            <?php while($course = $studentCourses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['enroll_date']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No courses found for this student.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
        <?php else: ?>
            <h2>Add New Student</h2>
            <form method="POST">
                <input type="hidden" name="student_id" id="student_id">
                <div class="form-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" name="add">Add Student</button>
                <button type="submit" name="update" style="display: none;">Update Student</button>
                <button type="button" id="cancel_edit" style="display: none;">Cancel</button>
            </form>

            <h2>Search Students</h2>
            <form method="POST" style="display: inline; margin-bottom: 20px;">
                <div style="margin-bottom: 10px;">
                    <input type="text" name="search_term" placeholder="Search by name or email..." value="<?php echo htmlspecialchars($searchTerm); ?>" style="width: 100%; padding: 8px;">
                </div>
                <button type="submit" name="search" style="background-color: #333; color: white; padding: 8px 14px; margin-right: 5px;">Search</button>
            </form>
            <form method="GET" style="display: inline;">
                <button type="submit" name="clear" style="background-color: #333; color: white; padding: 8px 14px;">Clear</button>
            </form>

            <h2>Student List</h2>
            <div class="scrollable-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Courses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $student['student_id']; ?></td>
                            <td><?php echo $student['first_name']; ?></td>
                            <td><?php echo $student['last_name']; ?></td>
                            <td><?php echo $student['email']; ?></td>
                            <td>
                                <?php 
                                $count = isset($enrollmentCountsByStudent[$student['student_id']]) ? 
                                    $enrollmentCountsByStudent[$student['student_id']] : 0;
                                echo $count;
                                if ($count > 0) {
                                    echo ' <a href="students.php?view_courses=' . $student['student_id'] . '">View</a>';
                                }
                                ?>
                            </td>
                            <td class="action-links">
                                <a href="#" onclick="editStudent(<?php echo $student['student_id']; ?>, '<?php echo $student['first_name']; ?>', '<?php echo $student['last_name']; ?>', '<?php echo $student['email']; ?>')">Edit</a>
                                <a href="students.php?delete=<?php echo $student['student_id']; ?>" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function editStudent(id, firstName, lastName, email) {
            document.getElementById('student_id').value = id;
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('email').value = email;

            document.querySelector('button[name="add"]').style.display = 'none';
            document.querySelector('button[name="update"]').style.display = 'inline-block';
            document.getElementById('cancel_edit').style.display = 'inline-block';
        }

        document.getElementById('cancel_edit').addEventListener('click', function() {
            document.getElementById('student_id').value = '';
            document.getElementById('first_name').value = '';
            document.getElementById('last_name').value = '';
            document.getElementById('email').value = '';

            document.querySelector('button[name="add"]').style.display = 'inline-block';
            document.querySelector('button[name="update"]').style.display = 'none';
            this.style.display = 'none';
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>

