<?php
// Include the configuration file
include('config.php');

// Query to get rejected and hold proposals
$query = "
SELECT 
    p.id AS 'Proposal ID',
    u.full_name AS 'Created By',
    p.borrower_name AS 'Borrower Name',
    p.city AS 'City',
    CONCAT(p.vehicle_name, ' - ', p.model) AS 'Vehicle',
    p.loan_amount AS 'Loan Amount',
    ps.status_name AS 'Status',
    p.reject_reaSON AS 'Reason'

FROM proposals p
INNER JOIN users u ON p.created_by = u.id
INNER JOIN proposal_status_master ps ON p.status = ps.status_id
WHERE p.status IN (10,12)";

// Execute the query
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejected & Hold Proposal Report</title>
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
        <h2 class="text-center mb-4">Rejected & Hold Proposal Report</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Proposal ID</th>
                        <th>Created By</th>
                        <th>Borrower Name</th>
                        <th>City</th>
                        <th>Vehicle</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                        <th>Reason for Rejection/Hold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                        <tr>
                            <td><?= $row['Proposal ID'] ?></td>
                            <td><?= $row['Created By'] ?></td>
                            <td><?= $row['Borrower Name'] ?></td>
                            <td><?= $row['City'] ?></td>
                            <td><?= $row['Vehicle'] ?></td>
                            <td><?= $row['Loan Amount'] ?></td>
                            <td><?= $row['Status'] ?></td>
                            <td><?= $row['Reason for Rejection/Hold'] ?></td>
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
