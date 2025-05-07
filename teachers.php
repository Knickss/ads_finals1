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
        $_SESSION['message'] = "Teacher deleted successfully. Associated courses were unlinked.";
    } else {
        $_SESSION['error'] = "Error deleting teacher: " . $conn->error;
    }

    $stmt->close();
    header("Location: teachers.php");
    exit;
}

$teachers = $conn->query("SELECT * FROM teachers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management</title>
    <link rel='stylesheet' href="styles.css">
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
        
        <h2>Add New Teacher</h2>
        <form method="POST">
            <input type="hidden" name="teacher_id" id="teacher_id">
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
            <button type="submit" name="add">Add Teacher</button>
            <button type="submit" name="update" style="display: none;">Update Teacher</button>
            <button type="button" id="cancel_edit" style="display: none;">Cancel</button>
        </form>
        
        <h2>Teacher List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($teacher = $teachers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $teacher['teacher_id']; ?></td>
                    <td><?php echo $teacher['first_name']; ?></td>
                    <td><?php echo $teacher['last_name']; ?></td>
                    <td><?php echo $teacher['email']; ?></td>
                    <td class="action-links">
                        <a href="#" onclick="editTeacher(<?php echo $teacher['teacher_id']; ?>, '<?php echo $teacher['first_name']; ?>', '<?php echo $teacher['last_name']; ?>', '<?php echo $teacher['email']; ?>')">Edit</a>
                        <a href="teachers.php?delete=<?php echo $teacher['teacher_id']; ?>" onclick="return confirm('Are you sure you want to delete this teacher?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        function editTeacher(id, firstName, lastName, email) {
            document.getElementById('teacher_id').value = id;
            document.getElementById('first_name').value = firstName;
            document.getElementById('last_name').value = lastName;
            document.getElementById('email').value = email;
            
            document.querySelector('button[name="add"]').style.display = 'none';
            document.querySelector('button[name="update"]').style.display = 'inline-block';
            document.getElementById('cancel_edit').style.display = 'inline-block';
        }
        
        document.getElementById('cancel_edit').addEventListener('click', function() {
            document.getElementById('teacher_id').value = '';
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
