<?php
session_start();
include 'config.php';

if (isset($_GET['status_code'])) {
    $status_code = $_GET['status_code'];
    $message = "Proposal submitted for review successfully!";
    // Bootstrap alert class based on status_code
    $alert_class = ($status_code == '1024') ? 'alert-success' : 'alert-danger';

    echo '<div class="custom-alert">'; // Custom wrapper for positioning
    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show small-alert" role="alert">';
    echo htmlspecialchars($message); // Prevent XSS attack
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    echo '</div>';
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['hf_proposal_id']) && isset($_POST['hf_proposal_id'])) {

    $proposal_id = (int) $_POST['hf_proposal_id'];
    $allocated_user_id = (int) $_POST['hf_allocated_user_id'];
    
    // Ensure only admins can perform this action
    if ($_SESSION['user_role'] === "admin") {
        $sql = "UPDATE proposals SET allocated_to_user_id = '$allocated_user_id' WHERE id = '$proposal_id'";
        
        if ($stmt = $conn->prepare($sql)) {
            
            if ($stmt->execute()) {
                echo '<div class="custom-alert alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill"></i> Allocation updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            } else {
                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill"></i> Error updating allocation: ' . $stmt->error . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                      </div>';
            }
    
            $stmt->close();
        } else {
            echo "Error preparing query: " . $conn->error;
        }
    
    } else {
        echo json_encode(["success" => false, "message" => "Unauthorized action!"]);
    }
}

// Include 'Allocated To' field only for admin users
$filterContext = ""; // Initialize filter condition

if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === "sales") {
        // Sales users see only their records
        $filterContext = " WHERE p.ar_user_id = " . (int) $_SESSION['user_id'];
    } elseif ($_SESSION['user_role'] === "user") {
        // Normal users see only records allocated to them
        $filterContext = " WHERE p.allocated_to_user_id = " . (int) $_SESSION['user_id'];
    }
}
// Include 'Allocated To' field only for admin users
$allocatedToField = "";
$joinAllocatedTo = "";

if ($_SESSION['user_role'] === "admin") {
    $allocatedToField = ", COALESCE(u2.full_name, 'NOT ALLOCATED') AS 'Allocated To'"; // Show "NOT ALLOCATED" if NULL
    $joinAllocatedTo = " LEFT JOIN users u2 ON p.allocated_to_user_id = u2.id"; // Join users table to get name
}

// Build the query dynamically
$query = "SELECT 
    p.id, 
    u.full_name AS 'User', 
    COALESCE(u3.full_name, 'No Agent') AS 'Agent Name',
    p.created_by AS 'Created By', 
    p.borrower_name AS 'Borrower Name', 
    p.city AS 'City', 
    CONCAT(p.vehicle_name, ' - ', p.model) AS 'Vehicle', 
    p.loan_amount AS 'Loan Amount', 
    ps.status_name AS 'Status'
    $allocatedToField
FROM proposals p 
INNER JOIN users u ON p.created_by = u.id
INNER JOIN proposal_status_master ps ON p.status = ps.status_id
LEFT JOIN users u3 ON p.ar_user_id = u3.id -- Join for Agent Name
$joinAllocatedTo
$filterContext"; // Add filter at the end

$result = mysqli_query($conn, $query);

$query = "select id, full_name from users where role='user'";
$allocation_users = mysqli_query($conn, $query);





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
                        <th>Agent Name</th>
                        <th>Borrower Name</th>
                        <th>City</th>
                        <th>Vehicle</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <?php if ($_SESSION['user_role'] === "admin") { ?>
                            <th>Allocated To</th>
                        <?php } ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['Agent Name']; ?></td>
                            <td><?php echo $row['Borrower Name']; ?></td>
                            <td><?php echo $row['City']; ?></td>
                            <td><?php echo $row['Vehicle']; ?></td>
                            <td><?php echo $row['Loan Amount']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    switch ($row['Status']) {
                                        case 'Draft': echo 'secondary'; break;
                                        case 'Submitted for Review': echo 'warning'; break;
                                        case 'Requested for Documents': echo 'info'; break;
                                        case 'Documents Uploaded': echo 'primary'; break;
                                        case 'Resubmitted for Review': echo 'dark'; break;
                                        case 'Sent for Approval': echo 'purple'; break;
                                        case 'Approved': echo 'success'; break;
                                        case 'Rejected': echo 'danger'; break;
                                        case 'On Hold': echo 'secondary'; break;
                                        case 'Closed': echo 'dark'; break;
                                        default: echo 'secondary';
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($row['Status']); ?>
                                </span>
                            </td>

                            <?php if ($_SESSION['user_role'] === "admin") { ?>
                                <td>
                                    <span class="allocation-cell text-<?php echo ($row['Allocated To'] == 'NOT ALLOCATED') ? 'danger' : 'primary'; ?>" 
                                        data-proposal-id="<?php echo $row['id']; ?>" 
                                        data-agent-name="<?php echo htmlspecialchars($row['Agent Name']); ?>"
                                        data-borrower="<?php echo htmlspecialchars($row['Borrower Name']); ?>"
                                        data-allocated="<?php echo htmlspecialchars($row['Allocated To']); ?>">
                                        <?php echo $row['Allocated To']; ?>
                                    </span>
                                </td>
                            <?php } ?>
                            <td>
                                <button class="btn btn-info btn-sm" onclick="openProposal(<?php echo $row['id']; ?>)">Open</button>
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

<!-- Modal for Allocation -->
<div class="modal fade" id="allocationModal" tabindex="-1" aria-labelledby="allocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="allocateForm" method="POST">
            <input type="hidden" name="hf_proposal_id" id="hf_proposal_id">
            <input type="hidden" name="hf_allocated_user_id" id="hf_allocated_user_id">
            <div class="modal-header">
                <h5 class="modal-title" id="allocationModalLabel">Allocate Proposal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Proposal #:</strong> <span id="modalProposalNo"></span></p>
                <p><strong>Agent :</strong> <span id="modalProposalAgent"></span></p>
                <p><strong>Borrower Name:</strong> <span id="modalBorrower"></span></p>
                <p><strong>Current Allocation:</strong> <span id="modalAllocated"></span></p>
                <label for="allocatedUser">Select User:</label>
                <select id="allocatedUser" class="form-select">
                    <option value="">-- Select User --</option>
                    <?php foreach ($allocation_users as $user) { ?>
                        <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="submit" name="saveAllocation" id="saveAllocation" class="btn btn-primary">Allocate</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
            </form>
        </div>
    </div>
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

    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".allocation-cell").forEach(function (cell) {
            cell.addEventListener("click", function () {
                let proposalId = this.getAttribute("data-proposal-id");
                let borrowerName = this.getAttribute("data-borrower");
                let allocatedTo = this.getAttribute("data-allocated");
                let proposalAgent = this.getAttribute("data-agent-name");
                document.getElementById("hf_proposal_id").value = proposalId;
                document.getElementById("modalProposalNo").textContent = proposalId;
                document.getElementById("modalProposalAgent").textContent = proposalAgent;
                document.getElementById("modalBorrower").textContent = borrowerName;
                document.getElementById("modalAllocated").textContent = allocatedTo;
                document.getElementById("hf_proposal_id").textContent = proposalId;
                

                new bootstrap.Modal(document.getElementById("allocationModal")).show();
            });
        });

        document.getElementById("saveAllocation").addEventListener("click", function () {
            let proposalId = document.getElementById("hf_proposal_id").value;
            let allocatedUserId = document.getElementById("allocatedUser").value;
            document.getElementById("hf_allocated_user_id").value = allocatedUserId;
            
            fetch("allocate_proposal.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `proposal_id=${proposalId}&allocated_user_id=${allocatedUserId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            });
        });
    });
    function openProposal(proposalId) {
        window.location.href = "proposal-form.php?id=" + proposalId;
    }
</script>

