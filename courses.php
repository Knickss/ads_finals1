<?php include("dbconnection.php");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $courseName = $_POST['course_name'];
        $teacherId = $_POST['teacher_id'];
        
        $stmt = $conn->prepare("INSERT INTO courses (course_name, teacher_id) VALUES (?, ?)");
        $stmt->bind_param("si", $courseName, $teacherId);
        $stmt->execute();
        $stmt->close();
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
        // First delete all enrollments for this course
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE course_id = ?");
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $stmt->close();
        
        // Then delete the course
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

// Get all courses with teacher names
$courses = $conn->query("
    SELECT c.course_id, c.course_name, c.teacher_id, t.first_name, t.last_name 
    FROM courses c
    JOIN teachers t ON c.teacher_id = t.teacher_id
");

// Create a copy of the courses result to use in the dropdown
// Get unique course names to avoid duplicates
$coursesDropdown = $conn->query("
    SELECT DISTINCT course_name 
    FROM courses
    ORDER BY course_name
");

// Get all teachers for dropdown
$teachers = $conn->query("SELECT * FROM teachers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courses Management</title>
    <link rel='stylesheet' href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Courses Management</h1>
        
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
                <label for="course_name">Course Name</label>
                <input type="text" id="course_name" name="course_name" required>
            </div>
            <div class="form-group">
                <label for="existing_courses">Existing Courses</label>
                <select id="existing_courses" onchange="selectExistingCourse(this.value)">
                    <option value="">-- Select Existing Course --</option>
                    <?php while($course = $coursesDropdown->fetch_assoc()): ?>
                    <option value="<?php echo $course['course_name']; ?>">
                        <?php echo $course['course_name']; ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="teacher_id">Teacher</label>
                <select id="teacher_id" name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php 
                    // Reset the teachers result pointer to the beginning
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
        
        <h2>Course List</h2>
        <table>
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
                // Reset the courses result pointer to the beginning
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
            
            // Just fill the course name field, leaving teacher selection to the user
            document.getElementById('course_name').value = courseName;
        }
        
        document.getElementById('cancel_edit').addEventListener('click', function() {
            document.getElementById('course_id').value = '';
            document.getElementById('course_name').value = '';
            document.getElementById('teacher_id').value = '';
            document.getElementById('existing_courses').value = '';
            
            document.querySelector('button[name="add"]').style.display = 'inline-block';
            document.querySelector('button[name="update"]').style.display = 'none';
            this.style.display = 'none';
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
