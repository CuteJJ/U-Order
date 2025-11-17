<?php
// Include database connection
include 'configs/db.php';

// Fetch data from users table
$sql = "SELECT * FROM users ORDER BY CreatedAt DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Database</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="container">
        <h1>Users Database</h1>
        <div class="stats">
            <?php
            $totalUsers = $result->num_rows;
            echo "Total Users: " . $totalUsers;
            ?>
        </div> 
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Age</th>
                        <th>Created At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['UserId']; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['Name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['Email']); ?></td>
                        <td><?php echo $row['Role']; ?></td>
                        <td><?php echo date('M j, Y g:i A', strtotime($row['CreatedAt'])); ?></td>
                        <td>
                            <span class="status status-active">Active</span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <h3>You got issue / No data found in the database.</h3>
            </div>
        <?php endif; ?>
        <?php
        // Close connection
        $conn->close();
        ?>
    </div>
</body>
</html>