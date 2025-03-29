<?php
include 'config.php';

// Handle form submission for creating a new proposal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_proposal'])) {
    $title = $_POST['title'];
    $client_name = $_POST['client_name'];
    $amount = $_POST['amount'];
    $status = 'Pending';

    $sql = "INSERT INTO proposals (title, client_name, amount, status) VALUES ('$title', '$client_name', '$amount', '$status')";
    mysqli_query($conn, $sql);
}

// Fetch all proposals
$query = "SELECT 
    p.id, 
    u.full_name AS 'User', 
    p.created_by AS 'Created By', 
    p.borrower_name AS 'Borrower Name', 
    p.city AS 'City', 
    CONCAT(p.vehicle_name, ' - ', p.model) AS 'Vehicle', 
    p.loan_amount AS 'Loan Amount', 
    ps.status_name AS 'Status'
FROM proposals p
INNER JOIN users u ON p.created_by = u.id
INNER JOIN proposal_status_master ps ON p.status = ps.status_id;
";
$result = mysqli_query($conn, $query);
?>

<!-- Include Bootstrap & DataTables CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="assets/css/proposal.css">
<link rel="stylesheet" href="assets/css/global.css">
<link rel="stylesheet" href="assets/css/loader.css">


<div class="container-fluid mt-4"> <!-- Full width -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-primary" onclick="proposalForm()">Create New Proposal</button>
    </div>
    <?php if (mysqli_num_rows($result) > 0) { ?>
        <div class="table-responsive w-100" style="max-width: 100%;">  <!-- Full width -->
            <table id="proposalTable" class="table table-hover table-striped table-bordered nowrap" style="width:100%">
                <thead class="table-row-header">
                    <tr>
                        <th>Proposal #</th>
                        <th>Created By</th>
                        <th>Borrower Name</th>
                        <th>City</th>
                        <th>Vehicle</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['User']; ?></td>
                            <td><?php echo $row['Borrower Name']; ?></td>
                            <td><?php echo $row['City']; ?></td>
                            <td><?php echo $row['Vehicle']; ?></td>
                            <td><?php echo $row['Loan Amount']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    switch ($row['Status']) {
                                        case 'Draft':
                                            echo 'secondary'; // Gray for draft
                                            break;
                                        case 'Submitted for Review':
                                            echo 'warning'; // Yellow for pending review
                                            break;
                                        case 'Requested for Documents':
                                            echo 'info'; // Light blue for document request
                                            break;
                                        case 'Documents Uploaded':
                                            echo 'primary'; // Blue for document upload
                                            break;
                                        case 'Resubmitted for Review':
                                            echo 'dark'; // Dark for re-review stage
                                            break;
                                        case 'Sent for Approval':
                                            echo 'purple'; // Purple for sent to approval
                                            break;
                                        case 'Approved':
                                            echo 'success'; // Green for approved
                                            break;
                                        case 'Rejected':
                                            echo 'danger'; // Red for rejected
                                            break;
                                        case 'On Hold':
                                            echo 'secondary'; // Gray for on hold
                                            break;
                                        case 'Closed':
                                            echo 'dark'; // Dark for closed cases
                                            break;
                                        default:
                                            echo 'secondary'; // Default case for unknown status
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($row['Status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm">Open</button>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    <?php } else { ?>
        <div class="alert alert-info text-center">No proposals found.</div>
    <?php } ?>
</div>

<!-- Include jQuery, DataTables, Bootstrap & Responsive JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    $(document).ready(function () {
        $('#proposalTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "lengthMenu": [5, 10, 25, 50],
            "pageLength": 5,
            "responsive": true // Enables responsiveness
        });
    });

    function proposalForm() {
        document.location.href="proposal-form.php";
    }
</script>

