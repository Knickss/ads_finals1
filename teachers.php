<?php session_start(); 
include("dbconnection.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        
        $stmt = $conn->prepare("INSERT INTO teachers (first_name, last_name, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $firstName, $lastName, $email);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        $teacherId = $_POST['teacher_id'];
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $email = $_POST['email'];
        
        $stmt = $conn->prepare("UPDATE teachers SET first_name=?, last_name=?, email=? WHERE teacher_id=?");
        $stmt->bind_param("sssi", $firstName, $lastName, $email, $teacherId);
        $stmt->execute();
        $stmt->close();
    }
}

if (isset($_GET['delete'])) {
    $teacherId = $_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacherId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Teacher deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting teacher: " . $conn->error;
    }

    $stmt->close();
    header("Location: teachers.php");
    exit;
} elseif (isset($_GET['view_courses'])) {
    $teacherId = $_GET['view_courses'];
    $viewCourses = true;
} else {
    $viewCourses = false;
}

// Get all teachers with course counts using LEFT JOIN
$teachers = $conn->query("
    SELECT t.*, COUNT(c.course_id) as course_count 
    FROM teachers t
    LEFT JOIN courses c ON t.teacher_id = c.teacher_id
    GROUP BY t.teacher_id
");

if ($viewCourses && isset($teacherId)) {
    $stmt = $conn->prepare("
        SELECT c.course_id, c.course_name, 
               (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id) as student_count
        FROM courses c
        LEFT JOIN teachers t ON c.teacher_id = t.teacher_id
        WHERE c.teacher_id = ?
        ORDER BY c.course_name
    ");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $teacherCourses = $stmt->get_result();
    $stmt->close();
    
    // Get teacher name for display
    $stmt = $conn->prepare("SELECT first_name, last_name FROM teachers WHERE teacher_id = ?");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $teacherInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teachers Management</title>
    <link rel='stylesheet' href="styles.css">
    <style>
        input[type="text"], input[type="email"] {
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
            position: sticky;
            top: 0;
            z-index: 10;
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

        /* Ensure smooth scrolling and better scroll wheel experience */
        .scrollable-table {
            scroll-behavior: smooth;
        }
        
        .back-link {
            margin-bottom: 15px;
            display: block;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .student-count {
            background-color: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Teachers Management</h1>

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

    <?php if ($viewCourses && isset($teacherId) && isset($teacherInfo)): ?>
        <a href="teachers.php" class="back-link">← Back to Teachers List</a>
        <h2>Courses taught by <?php echo htmlspecialchars($teacherInfo['first_name'] . ' ' . $teacherInfo['last_name']); ?></h2>
        
        <div class="scrollable-table">
            <table>
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Name</th>
                        <th>Students Enrolled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($teacherCourses->num_rows > 0): ?>
                        <?php while($course = $teacherCourses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                            <td><?php echo htmlspecialchars($course['student_count']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3">No courses assigned to this teacher.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <h2>Add New Teacher</h2>
        <form method="POST">
            <input type="hidden" name="teacher_id" id="teacher_id">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" id="first_name" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" id="last_name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="email" required>
            </div>
            <button type="submit" name="add">Add Teacher</button>
            <button type="submit" name="update" style="display: none;">Update</button>
            <button type="button" id="cancel_edit" style="display: none;">Cancel</button>
        </form>

        <h2>Search Teachers</h2>
        <input type="text" id="searchInput" placeholder="Search by name or email...">
        <button class="btn" onclick="searchTable()">Search</button>
        <button class="btn btn-clear" onclick="clearSearch()">Clear</button>

        <h2>Teacher List</h2>
        <div class="scrollable-table" id="tableContainer">
            <table id="teacherTable">
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
                    <?php while ($row = $teachers->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['teacher_id']; ?></td>
                            <td><?php echo $row['first_name']; ?></td>
                            <td><?php echo $row['last_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <?php 
                                echo $row['course_count']; 
                                if ($row['course_count'] > 0) {
                                    echo ' <a href="teachers.php?view_courses=' . $row['teacher_id'] . '">View</a>';
                                }
                                ?>
                            </td>
                            <td class="action-links">
                                <a href="#" onclick="editTeacher(<?php echo $row['teacher_id']; ?>, '<?php echo $row['first_name']; ?>', '<?php echo $row['last_name']; ?>', '<?php echo $row['email']; ?>')">Edit</a>
                                <a href="teachers.php?delete=<?php echo $row['teacher_id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function editTeacher(id, first, last, email) {
        document.getElementById('teacher_id').value = id;
        document.getElementById('first_name').value = first;
        document.getElementById('last_name').value = last;
        document.getElementById('email').value = email;
        document.querySelector('button[name="add"]').style.display = 'none';
        document.querySelector('button[name="update"]').style.display = 'inline-block';
        document.getElementById('cancel_edit').style.display = 'inline-block';
    }

    document.getElementById('cancel_edit').addEventListener('click', function () {
        document.getElementById('teacher_id').value = '';
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value = '';
        document.getElementById('email').value = '';
        document.querySelector('button[name="add"]').style.display = 'inline-block';
        document.querySelector('button[name="update"]').style.display = 'none';
        this.style.display = 'none';
    });

    function searchTable() {
        const value = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll("#teacherTable tbody tr");

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            row.style.display = rowText.includes(value) ? "" : "none";
        });
    }

    function clearSearch() {
        document.getElementById('searchInput').value = '';
        searchTable();
    }

    // Add scroll wheel support
    const tableContainer = document.getElementById('tableContainer');
    
    tableContainer.addEventListener('wheel', function(e) {
        // Prevent default scroll behavior
        e.preventDefault();
        
        // Calculate scroll amount based on wheel delta
        const scrollAmount = e.deltaY;
        
        // Scroll the container
        tableContainer.scrollTop += scrollAmount;
    }, { passive: false });
</script>
</body>
</html>

<?php $conn->close(); ?>
