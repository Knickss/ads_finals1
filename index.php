<?php include("dbconnection.php") ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Records System</title>
    <link rel='stylesheet' href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .container {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
        }
        .nav {
            background-color: #333;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 12px;
            display: inline-block;
        }
        .nav a:hover {
            background-color: #444;
            border-radius: 4px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        h2 {
            margin-bottom: 10px;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
            text-align: center;
            margin-top: 20px;
        }
        .stat-card {
            flex: 1 1 20%;
            padding: 20px;
            border-radius: 8px;
            background-color: #f8f8f8;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        }
        .stat-card h4 {
            margin-bottom: 10px;
        }
        .stat-card p {
            font-size: 1.5em;
            font-weight: bold;
            margin: 0;
        }
    </style>
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
        
        <h3>Dashboard</h3>
        <div class="stats-container">
            <div class="stat-card">
                <h4>Students</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM students");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
            <div class="stat-card">
                <h4>Teachers</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM teachers");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
            <div class="stat-card">
                <h4>Courses</h4>
                <?php
                $result = $conn->query("SELECT COUNT(*) as total FROM courses");
                $row = $result->fetch_assoc();
                echo "<p>" . $row['total'] . "</p>";
                ?>
            </div>
            <div class="stat-card">
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
