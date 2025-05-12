<?php 
session_start();
include("dbconnection.php");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $courseName = $_POST['course_name'];
        $teacherId = $_POST['teacher_id'];

        // Check if course already exists (regardless of teacher)
        $check = $conn->prepare("SELECT course_id FROM courses WHERE course_name = ?");
        $check->bind_param("s", $courseName);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $check->bind_result($existingCourseId);
            $check->fetch();

            $stmt = $conn->prepare("UPDATE courses SET teacher_id = ? WHERE course_id = ?");
            $stmt->bind_param("ii", $teacherId, $existingCourseId);
            $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (course_name, teacher_id) VALUES (?, ?)");
            $stmt->bind_param("si", $courseName, $teacherId);
            $stmt->execute();
            $stmt->close();
        }

        $check->close();
    } elseif (isset($_POST['update'])) {
        $courseId = $_POST['course_id'];
        $courseName = $_POST['course_name'];
        $teacherId = $_POST['teacher_id'];
        
        $stmt = $conn->prepare("UPDATE courses SET course_name=?, teacher_id=? WHERE course_id=?");
        $stmt->bind_param("sii", $courseName, $teacherId, $courseId);
        $stmt->execute();
        $stmt->close();
    } 
}

if (isset($_GET['delete'])) {
    $courseId = $_GET['delete'];
    
    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Course and associated enrollments deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting course: " . $conn->error;
        }
        
        $stmt->close();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error deleting course: " . $e->getMessage();
    }
    
    header("Location: courses.php");
    exit;
}

$courses = $conn->query("
    SELECT c.course_id, c.course_name, c.teacher_id, t.first_name, t.last_name 
    FROM courses c
    JOIN teachers t ON c.teacher_id = t.teacher_id
");

$coursesDropdown = $conn->query("
    SELECT DISTINCT course_name FROM courses ORDER BY course_name
");

$teachers = $conn->query("SELECT * FROM teachers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Courses Management</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        input[type="text"], select {
            padding: 8px;
            width: 100%;
            margin: 5px 0 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button, .btn {
            padding: 8px 12px;
            border: none;
            background-color: #333;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-clear {
            background-color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
        }

        th {
            background-color: #333;
            color: white;
        }

        .action-links a {
            margin-right: 8px;
            color: #007bff;
            text-decoration: none;
        }

        .action-links a:hover {
            text-decoration: underline;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .scrollable-table {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Courses Management</h1>

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

        <h2>Add New Course</h2>
        <form method="POST">
            <input type="hidden" name="course_id" id="course_id">
            <div class="form-group">
                <label>Course Name</label>
                <input type="text" id="course_name" name="course_name" required>
            </div>
            <div class="form-group">
                <label>Existing Courses</label>
                <select id="existing_courses" onchange="selectExistingCourse(this.value)">
                    <option value="">Select Existing Course</option>
                    <?php while($course = $coursesDropdown->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_name']; ?>"><?php echo $course['course_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Teacher</label>
                <select id="teacher_id" name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php 
                    $teachers->data_seek(0);
                    while($teacher = $teachers->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $teacher['teacher_id']; ?>">
                            <?php echo $teacher['first_name'] . ' ' . $teacher['last_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add">Add Course</button>
            <button type="submit" name="update" style="display: none;">Update Course</button>
            <button type="button" id="cancel_edit" style="display: none;">Cancel</button>
        </form>

        <h2>Search Courses</h2>
        <input type="text" id="searchInput" placeholder="Search by course name or teacher...">
        <button class="btn" onclick="searchTable()">Search</button>
        <button class="btn btn-clear" onclick="clearSearch()">Clear</button>

        <h2>Course List</h2>
        <div class="scrollable-table">
            <table id="courseTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course Name</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $courses->data_seek(0);
                    while($course = $courses->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $course['course_id']; ?></td>
                        <td><?php echo $course['course_name']; ?></td>
                        <td><?php echo $course['first_name'] . ' ' . $course['last_name']; ?></td>
                        <td class="action-links">
                            <a href="#" onclick="editCourse(<?php echo $course['course_id']; ?>, '<?php echo $course['course_name']; ?>', <?php echo $course['teacher_id']; ?>)">Edit</a>
                            <a href="courses.php?delete=<?php echo $course['course_id']; ?>" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function editCourse(id, courseName, teacherId) {
            document.getElementById('course_id').value = id;
            document.getElementById('course_name').value = courseName;
            document.getElementById('teacher_id').value = teacherId;
            document.querySelector('button[name="add"]').style.display = 'none';
            document.querySelector('button[name="update"]').style.display = 'inline-block';
            document.getElementById('cancel_edit').style.display = 'inline-block';
        }

        function selectExistingCourse(courseName) {
            if (!courseName) return;
            document.getElementById('course_name').value = courseName;
        }

        document.getElementById('cancel_edit').addEventListener('click', function () {
            document.getElementById('course_id').value = '';
            document.getElementById('course_name').value = '';
            document.getElementById('teacher_id').value = '';
            document.getElementById('existing_courses').value = '';
            document.querySelector('button[name="add"]').style.display = 'inline-block';
            document.querySelector('button[name="update"]').style.display = 'none';
            this.style.display = 'none';
        });

        function searchTable() {
            const value = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll("#courseTable tbody tr");

            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                row.style.display = rowText.includes(value) ? "" : "none";
            });
        }

        function clearSearch() {
            document.getElementById('searchInput').value = '';
            searchTable();
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
