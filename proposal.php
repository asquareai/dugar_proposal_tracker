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
        $sql = "UPDATE proposals SET allocated_to_user_id = '$allocated_user_id', allocated_on = now() WHERE id = '$proposal_id'";
        
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
        $filterContext = " and p.ar_user_id = " . (int) $_SESSION['user_id'];
    } //elseif ($_SESSION['user_role'] === "user") {
    //     // Normal users see only records allocated to them
    //     $filterContext = " and p.allocated_to_user_id = " . (int) $_SESSION['user_id'];
    // }
    
}
// Get selected status from query string (default to 'In Progress' if not set)
$selectedStatus = isset($_GET['status']) ? $_GET['status'] : 'In Progress';

// Mapping of status for filtering
$statusMapping = [
    'New' => [1,2],
    'In Progress' => [3, 4, 5, 6, 7, 8, 11,14],
    'Hold' => [12],
    'Approved' => [9],
    'Rejected' => [10],
    'Cancelled' => [13]
];

// Get the status ID array for the selected status
$statusIds = isset($statusMapping[$selectedStatus]) ? $statusMapping[$selectedStatus] : [1,2, 3, 4, 5, 6, 7, 8, 11,12,13];

// Include 'Allocated To' field only for admin users
$allocatedToField = "";
$joinAllocatedTo = "";

if ($_SESSION['user_role'] === "admin") {
    $allocatedToField = ", COALESCE(u2.full_name, 'NOT ALLOCATED') AS 'Allocated To'"; // Show "NOT ALLOCATED" if NULL
    $joinAllocatedTo = " LEFT JOIN users u2 ON p.allocated_to_user_id = u2.id"; // Join users table to get name
}
if ($_SESSION['user_role'] === "user" and $selectedStatus != "New") {
    $allocatedToField = ", COALESCE(u2.full_name, 'NOT ALLOCATED') AS 'Allocated To'"; // Show "NOT ALLOCATED" if NULL
    $joinAllocatedTo = " JOIN users u2 ON p.allocated_to_user_id = u2.id and u2.id = " . $_SESSION['user_id']; // Join users table to get name
}

// Define the main status mapping
$mainStatusField = "
    CASE 
        WHEN p.status IN (" . implode(",", $statusIds) . ") THEN '$selectedStatus'
        ELSE 'Other'
    END AS 'Main Status'
";

// Build the query dynamically with selected status filter
$query = "SELECT 
    p.id, 
    u.full_name AS 'User', 
    COALESCE(u3.full_name, 'No Agent') AS 'Agent Name',
    p.created_by AS 'Created By', 
    p.borrower_name AS 'Borrower Name', 
    p.city AS 'City', 
    CONCAT(p.vehicle_name, ' - ', p.model) AS 'Vehicle', 
    p.loan_amount AS 'Loan Amount', 
    ps.status_name AS 'Status',
    p.status AS 'StatusID',
    $mainStatusField
    $allocatedToField
FROM proposals p 
INNER JOIN users u ON p.created_by = u.id
INNER JOIN proposal_status_master ps ON p.status = ps.status_id
LEFT JOIN users u3 ON p.ar_user_id = u3.id 
$joinAllocatedTo
where p.status IN (" . implode(",", $statusIds) . ")" .
$filterContext; // Filter based on selected status


$result = mysqli_query($conn, $query);

$query = "select id, full_name from users where role='user'";
$allocation_users = mysqli_query($conn, $query);


$isSales = ($_SESSION['user_role'] === 'sales') ? 1 : 0;

// SQL query to count records based on status mapping
if ($_SESSION['user_role'] === 'sales')
{
$sql = "
    SELECT 
        CASE
            WHEN status IN (1, 2) THEN 'New' 
            WHEN status IN (3, 4, 5, 6, 7, 8, 11) THEN 'In Progress'
            WHEN status = 9 THEN 'Approved'
            WHEN status = 10 THEN 'Rejected'
            WHEN status = 12 THEN 'Hold'
            WHEN status = 13 THEN 'Cancelled'
            ELSE 'Other' 
        END AS category,
        COUNT(*) as count 
    FROM proposals where ar_user_id = " . $_SESSION['user_id'] . " GROUP BY category ";
}
else if ($_SESSION['user_role'] === 'user') {
    $sql = "
        SELECT 
            CASE
                WHEN status IN (1, 2) THEN 'New' 
                WHEN status IN (3, 4, 5, 6, 7, 8, 11) THEN 'In Progress'
                WHEN status = 9 THEN 'Approved'
                WHEN status = 10 THEN 'Rejected'
                WHEN status = 12 THEN 'Hold'
                WHEN status = 13 THEN 'Cancelled'
                ELSE 'Other' 
            END AS category,
            COUNT(*) as count 
        FROM proposals 
        WHERE 
            (status IN (1, 2)) 
            OR 
            (status NOT IN (1, 2) AND allocated_to_user_id = " . $_SESSION['user_id'] . ")
        GROUP BY category
    ";
}

else
{
    $sql = "
    SELECT 
        CASE
            WHEN status IN (1, 2) THEN 'New' 
            WHEN status IN (3, 4, 5, 6, 7, 8, 11) THEN 'In Progress'
            WHEN status = 9 THEN 'Approved'
            WHEN status = 10 THEN 'Rejected'
            WHEN status = 12 THEN 'Hold'
            WHEN status = 13 THEN 'Cancelled'
            ELSE 'Other' 
        END AS category,
        COUNT(*) as count 
    FROM proposals GROUP BY category ";
}

$statuscoountresult = $conn->query($sql);

// Initialize counts
$counts = [
    "New" => 0,
    "In Progress" => 0,
    "Hold" => 0,
    "Approved" => 0,
    "Rejected" => 0,
    "Cancelled" => 0
];

// Assign counts dynamically
while ($row = $statuscoountresult->fetch_assoc()) {
    $category = $row['category'];
    if (isset($counts[$category])) {
        $counts[$category] = $row['count'];
    }
}


?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Include Bootstrap & DataTables CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link rel="stylesheet" href="assets/css/proposal.css">
<link rel="stylesheet" href="assets/css/global.css">
<link rel="stylesheet" href="assets/css/loader.css">
<div style="width:100%; text-align:center">
<img src="assets/images/logo.png" style="width:250px;">
<h3>Loan Proposal Management</h3>
<div><?php echo $_SESSION['user_fullname'] . " (" . $_SESSION['user_role'] . ")";?>
</div>
<div class="container-fluid mt-4" style="padding-bottom:100px"> <!-- Full width -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if ($_SESSION['user_role'] !== "approver"): ?>
            <button class="btn btn-primary" onclick="proposalForm()">Create New Proposal</button>
        <?php endif; ?>
    </div>
   <!-- This section is shown only on mobile -->
    <div id="statusTabs" class="d-flex justify-content-around mb-3">
        <button class="status-tab btn btn-outline-primary btn-sm" onclick="selectTab(this, 'New')" data-status="New">
            <i class="fas fa-plus-circle"></i> <span class="tab-label d-none">New</span>
        </button>
        <button class="status-tab btn btn-outline-warning btn-sm" onclick="selectTab(this, 'In Progress')" data-status="In Progress">
            <i class="fas fa-spinner"></i> <span class="tab-label d-none">In Progress</span>
        </button>
        <button class="status-tab btn btn-outline-success btn-sm" onclick="selectTab(this, 'Approved')" data-status="Approved">
            <i class="fas fa-check-circle"></i> <span class="tab-label d-none">Approved</span>
        </button>
        <button class="status-tab btn btn-outline-secondary btn-sm" onclick="selectTab(this, 'Hold')" data-status="Hold">
            <i class="fas fa-pause-circle"></i> <span class="tab-label d-none">Hold</span>
        </button>
        <button class="status-tab btn btn-outline-danger btn-sm" onclick="selectTab(this, 'Rejected')" data-status="Rejected">
            <i class="fas fa-times-circle"></i> <span class="tab-label d-none">Rejected</span>
        </button>
        <button class="status-tab btn btn-outline-dark btn-sm" onclick="selectTab(this, 'Cancelled')" data-status="Cancelled">
            <i class="fas fa-ban"></i> <span class="tab-label d-none">Cancelled</span>
        </button>
    </div>




    <div class="row d-none d-md-flex">
        <div class="col-md-2 status-col">
            <div class="card border-primary shadow-lg <?php if ($selectedStatus == "New"):?>active-category<?php endif; ?> position-relative">
                <div class="card-header bg-primary text-white fw-bold d-flex justify-content-between align-items-center">
                    New <?php if ($selectedStatus == "New"): ?><span class="selected-icon">✔</span>  <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-text">Total: <span class="fw-bold"><?php echo $counts['New']; ?></span></p>
                    <button class="btn btn-outline-primary btn-sm" onclick="filterByStatus('New')">View Details</button>
                </div>
            </div>
        </div>

        <div class="col-md-2 status-col">
            <div class="card border-warning shadow-lg <?php if ($selectedStatus == "In Progress"):?>active-category<?php endif; ?> position-relative">
                <div class="card-header bg-warning text-white fw-bold d-flex justify-content-between align-items-center">
                    In Progress <?php if ($selectedStatus == "In Progress"): ?><span class="selected-icon">✔</span>  <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-text">Total: <span class="fw-bold"><?php echo $counts['In Progress']; ?></span></p>
                    <button class="btn btn-outline-warning btn-sm" onclick="filterByStatus('In Progress')">View Details</button>
                </div>
            </div>
        </div>
        <div class="col-md-2 status-col">
            <div class="card border-success shadow-lg <?php if ($selectedStatus == "Approved"):?>active-category<?php endif; ?> position-relative">
                <div class="card-header bg-success text-white fw-bold d-flex justify-content-between align-items-center">
                    Approved <?php if ($selectedStatus == "Approved"): ?><span class="selected-icon">✔</span>  <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-text">Total: <span class="fw-bold"><?php echo $counts['Approved']; ?></span></p>
                    <button class="btn btn-outline-success btn-sm" onclick="filterByStatus('Approved')">View Details</button>
                </div>
            </div>
        </div>
        <div class="col-md-2 status-col">
            <div class="card border-secondary shadow-lg <?php if ($selectedStatus == "Hold"):?>active-category<?php endif; ?> position-relative">
                <div class="card-header bg-secondary text-white fw-bold d-flex justify-content-between align-items-center">
                    Hold <?php if ($selectedStatus == "Hold"): ?><span class="selected-icon">✔</span>  <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-text">Total: <span class="fw-bold"><?php echo $counts['Hold']; ?></span></p>
                    <button class="btn btn-outline-secondary btn-sm" onclick="filterByStatus('Hold')">View Details</button>
                </div>
            </div>
        </div>

        <div class="col-md-2 status-col">
            <div class="card border-danger shadow-lg <?php if ($selectedStatus == "Rejected"):?>active-category<?php endif; ?> position-relative">
                <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center">
                    Rejected <?php if ($selectedStatus == "Rejected"): ?><span class="selected-icon">✔</span>  <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-text">Total: <span class="fw-bold"><?php echo $counts['Rejected']; ?></span></p>
                    <button class="btn btn-outline-danger btn-sm" onclick="filterByStatus('Rejected')">View Details</button>
                </div>
            </div>
        </div>

        <div class="col-md-2 status-col">
            <div class="card border-dark shadow-lg <?php if ($selectedStatus == "Cancelled"):?>active-category<?php endif; ?> position-relative">
                <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
                    Cancelled <?php if ($selectedStatus == "Cancelled"): ?><span class="selected-icon">✔</span>  <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="card-text">Total: <span class="fw-bold"><?php echo $counts['Cancelled']; ?></span></p>
                    <button class="btn btn-outline-dark btn-sm" onclick="filterByStatus('Cancelled')">View Details</button>
                </div>
            </div>
        </div>
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
                            <span class="badge <?php 
                                switch ($row['Status']) {
                                    case 'Draft': echo 'bg-secondary'; break; // Gray
                                    case 'Submitted for Review': echo 'bg-warning text-dark'; break; // Yellow
                                    case 'Under Review': echo 'bg-orange text-white'; break; // Orange (custom)
                                    case 'Documents Requested': echo 'bg-info text-dark'; break; // Light Blue
                                    case 'More Details Required': echo 'bg-light text-dark border'; break; // Soft Gray/White
                                    case 'Documents Uploaded': echo 'bg-primary'; break; // Blue
                                    case 'Re-Submitted for Review': echo 'bg-dark'; break; // Dark Gray
                                    case 'Sent for Approval': echo 'bg-purple text-white'; break; // Purple (custom)
                                    case 'Approved': echo 'bg-success'; break; // Green
                                    case 'Rejected': echo 'bg-danger'; break; // Red
                                    case 'Ask for More Details': echo 'bg-teal text-white'; break; // Teal (custom)
                                    case 'Closed': echo 'bg-dark'; break; // Dark Gray
                                    default: echo 'bg-secondary'; // Default Gray
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
                                <button class="btn btn-info btn-sm" 
                                    onclick="openProposal(<?php echo $row['id']; ?>)" 
                                    <?php echo ($_SESSION['user_role'] === 'approver' && $row['StatusID'] != 8) ? 'disabled' : ''; ?>>
                                    Open
                                </button>
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
   let isMobile = window.innerWidth <= 768;

    $('#proposalTable').DataTable({
        paging: true,
        searching: true,
        ordering: true,
        info: true,
        lengthMenu: [5, 10, 25, 50],
        pageLength: 50,
        responsive: {
            details: isMobile ? {
                type: 'inline',
                display: $.fn.dataTable.Responsive.display.childRowImmediate,
                target: ''
            } : true
        }
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
            
            // fetch("allocate_proposal.php", {
            //     method: "POST",
            //     headers: { "Content-Type": "application/x-www-form-urlencoded" },
            //     body: `proposal_id=${proposalId}&allocated_user_id=${allocatedUserId}`
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         location.reload();
            //     } else {
            //         alert("Error: " + data.message);
            //     }
            // });
        });
    });
    function openProposal(proposalId) {

        var user_role = "<?php echo $_SESSION['user_role']; ?>"; // Assuming stored in session

        if (user_role === 'approver') {
            window.location.href = "proposal-approval.php?id=" + proposalId;
        } else {
            window.location.href = "proposal-form.php?id=" + proposalId;
        }
    }
    function filterByStatus(status) {
        // Redirect to the same page with the selected status in the URL
        localStorage.setItem('selectedStatus', status); // Save to local storage

        window.location.href = window.location.pathname + '?status=' + encodeURIComponent(status);
    }
    
</script>
<style>
/* 👇 Hide mobile tabs on desktop */
@media (min-width: 768px) {
  #statusTabs {
    display: none !important;
  }
}

/* 👇 Mobile-specific styles */
@media (max-width: 767.98px) {
  #statusTabs {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 4px;
    padding: 5px 0;
  }

  #statusTabs button {
    flex: 1 0 0;
    white-space: nowrap;
    font-size: 12px;
    padding: 6px;
  }
}
</style>


<script>
document.addEventListener("DOMContentLoaded", function () {
    let savedStatus = localStorage.getItem('selectedStatus');

    if (!savedStatus) {
        savedStatus = "In Progress";
        localStorage.setItem('selectedStatus', savedStatus);
    }

    const urlParams = new URLSearchParams(window.location.search);
    const statusFromUrl = urlParams.get('status');
    const activeStatus = statusFromUrl || savedStatus;

    if (!statusFromUrl) {
        window.location.href = window.location.pathname + '?status=' + encodeURIComponent(savedStatus);
        return;
    }

    // Highlight selected tab
    const matchingBtn = document.querySelector(`.status-tab[data-status="${activeStatus}"]`);
    if (matchingBtn) {
        const label = matchingBtn.querySelector('.tab-label');
        if (label) label.classList.remove('d-none');

        applyActiveStyle(matchingBtn, activeStatus);
    }
});

function selectTab(button, status) {
    // Hide all labels
    document.querySelectorAll('.status-tab .tab-label').forEach(label => label.classList.add('d-none'));

    // Remove previous active styles
    document.querySelectorAll('.status-tab').forEach(tab => {
        tab.classList.remove('active-category', 'bg-primary', 'bg-success', 'bg-warning', 'bg-secondary', 'bg-danger', 'bg-dark', 'text-white');
    });

    // Show selected label + apply active style
    const label = button.querySelector('.tab-label');
    if (label) label.classList.remove('d-none');

    applyActiveStyle(button, status);
    localStorage.setItem('selectedStatus', status);

    setTimeout(() => {
        filterByStatus(status);
    }, 200);
}

function applyActiveStyle(button, status) {
    const statusColors = {
        "New": "bg-primary",
        "In Progress": "bg-warning",
        "Approved": "bg-success",
        "Hold": "bg-secondary",
        "Rejected": "bg-danger",
        "Cancelled": "bg-dark"
    };

    const bgClass = statusColors[status] || "bg-primary";

    button.classList.add('active-category', bgClass, 'text-white');
}

function filterByStatus(status) {
    window.location.href = window.location.pathname + '?status=' + encodeURIComponent(status);
}
</script>


