<?php include("dbconnection.php") ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Records System</title>
    <link rel='stylesheet' href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Academic Records System</h1>
        
        <div class="nav">
            <a href="students.php">Students</a>
            <a href="teachers.php">Teachers</a>
            <a href="courses.php">Courses</a>
            <a href="enrollments.php">Enrollments</a>
        </div>
        
        <h2>Welcome to Academic Records System</h2>
        <p>Select a section from the navigation above to manage academic records.</p>
        
        <h3>Quick Stats</h3>
        <div style="display: flex; justify-content: space-around; text-align: center;">
            <div>
                <h4>Students</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM students");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
            <div>
                <h4>Teachers</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM teachers");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
            <div>
                <h4>Courses</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM courses");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
            <div>
                <h4>Enrollments</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM enrollments");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>