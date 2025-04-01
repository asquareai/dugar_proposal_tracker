<?php
// Include database configuration file
include 'config.php'; // Assuming this is your database connection file

// Query to fetch proposal details including status, user, and allocation
$query = "
    SELECT 
        p.id, 
        u.full_name AS 'User', 
        COALESCE(u3.full_name, 'No Agent') AS 'Agent Name',
        COALESCE(u4.full_name, 'Not Allocated') AS 'Allocated To',
        p.created_by AS 'Created By', 
        p.borrower_name AS 'Borrower Name', 
        p.city AS 'City', 
        CONCAT(p.vehicle_name, ' - ', p.model) AS 'Vehicle', 
        p.loan_amount AS 'Loan Amount', 
        ps.status_name AS 'Status',
        p.status AS 'StatusID'
    FROM proposals p 
    INNER JOIN users u ON p.created_by = u.id
    INNER JOIN proposal_status_master ps ON p.status = ps.status_id
    LEFT JOIN users u3 ON p.ar_user_id = u3.id
    LEFT JOIN users u4 ON p.allocated_to_user_id = u4.id
";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Status Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            font-size: 0.9rem; /* Reduced font size */
        }
        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }
        .table-container {
            margin-top: 20px;
        }
        .btn-back {
            margin-bottom: 20px;
        }
        .filter-input {
            margin-bottom: 15px;
        }
        .export-btns {
            margin-bottom: 20px;
        }
        .export-btns button {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="btn-back">
            <a href="report_landing_page.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Reports</a>
        </div>
        <h2 class="text-center mb-4">Proposal Status Report</h2>

        <!-- Filter Section -->
        <div class="filter-input">
            <input type="text" id="filterInput" class="form-control" placeholder="Search for proposals..." onkeyup="filterTable()">
        </div>

        <!-- Export Buttons -->
        <div class="export-btns">
            <button class="btn btn-success" onclick="exportToExcel()">Export to Excel</button>
            <button class="btn btn-info" onclick="exportToCSV()">Export to CSV</button>
        </div>

        <!-- Table Section -->
        <div class="table-container">
            <table class="table table-bordered table-striped w-100" id="proposalTable">
                <thead class="table-light">
                    <tr>
                        <th>Proposal ID</th>
                        <th>User</th>
                        <th>Agent Name</th>
                        <th>Allocated To</th>
                        <th>Borrower Name</th>
                        <th>City</th>
                        <th>Vehicle</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetching data and displaying the report
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['User']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Agent Name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Allocated To']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Borrower Name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['City']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Vehicle']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Loan Amount']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- JavaScript for table search and export functions -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to filter the table
        function filterTable() {
            const filter = document.getElementById("filterInput").value.toUpperCase();
            const table = document.getElementById("proposalTable");
            const rows = table.getElementsByTagName("tr");
            
            for (let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName("td");
                let match = false;
                for (let j = 0; j < cells.length; j++) {
                    if (cells[j]) {
                        const cellValue = cells[j].textContent || cells[j].innerText;
                        if (cellValue.toUpperCase().indexOf(filter) > -1) {
                            match = true;
                        }
                    }
                }
                rows[i].style.display = match ? "" : "none";
            }
        }

        // Function to export to Excel
        function exportToExcel() {
            const table = document.getElementById("proposalTable");
            let html = table.outerHTML;
            let blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'proposal_status_report.xlsx';
            link.click();
        }

        // Function to export to CSV
        function exportToCSV() {
            const table = document.getElementById("proposalTable");
            let rows = table.rows;
            let csv = [];
            for (let i = 0; i < rows.length; i++) {
                let row = rows[i];
                let cols = row.querySelectorAll('td, th');
                let csvRow = [];
                cols.forEach(col => csvRow.push(col.innerText));
                csv.push(csvRow.join(","));
            }
            let csvFile = new Blob([csv.join("\n")], { type: 'text/csv' });
            let link = document.createElement('a');
            link.href = URL.createObjectURL(csvFile);
            link.download = 'proposal_status_report.csv';
            link.click();
        }


    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.11/jspdf.plugin.autotable.min.js"></script>
</body>
</html>
