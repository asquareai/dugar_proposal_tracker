<?php
// Include the configuration file
include('config.php');

// Query to get proposal allocation data
$query = "
SELECT 
    p.id AS 'Proposal ID',
    p.borrower_name AS 'Borrower Name',
    COALESCE(u4.full_name, 'Not Allocated') AS 'Allocated To',
    ps.status_name AS 'Status',
    DATE_FORMAT(p.allocated_on, '%d %b, %Y') AS 'Allocated Date'
FROM proposals p
INNER JOIN proposal_status_master ps ON p.status = ps.status_id
LEFT JOIN users u4 ON p.allocated_to_user_id = u4.id
WHERE p.allocated_on IS NOT NULL
ORDER BY p.allocated_on DESC";

// Execute the query
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Allocation Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-size: 14px;
        }
        .table th, .table td {
            text-align: center;
        }
        .back-btn {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <a href="report_landing_page.php" class="btn btn-secondary back-btn">Back to Reports</a>
        <h2 class="text-center mb-4">Proposal Allocation Report</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Proposal ID</th>
                        <th>Borrower Name</th>
                        <th>Allocated To</th>
                        <th>Status</th>
                        <th>Allocated Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td><?= $row['Proposal ID'] ?></td>
                            <td><?= $row['Borrower Name'] ?></td>
                            <td><?= $row['Allocated To'] ?></td>
                            <td><?= $row['Status'] ?></td>
                            <td><?= $row['Allocated Date'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        // Export button functionality (you can implement export logic based on your preferences)
    </script>
</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
