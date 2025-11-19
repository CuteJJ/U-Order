<?php
// Include database connection
include 'configs/db.php';

// Fetch data from users table
$sql = "SELECT * FROM users ORDER BY CreatedAt DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A Big Users Database</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>

<body>
    <div class="container">
        <h1>Users Database</h1>
        <div class="stats">
            <?php
            $totalUsers = count($result);
            echo "Total Users: " . $totalUsers;
            ?>
        </div>
        <?php if (count($result) > 0): ?>
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
                    <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?= $row['UserId']; ?></td>
                            <td><strong><?= htmlspecialchars($row['Name']); ?></strong></td>
                            <td><?= htmlspecialchars($row['Email']); ?></td>
                            <td><?= $row['Role']; ?></td>
                            <td><?= date('M j, Y g:i A', strtotime($row['CreatedAt'])); ?></td>
                            <td>
                                <span class="status status-active">Active</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <h3>You got issue / No data found in the database.</h3>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>