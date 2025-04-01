<?php
session_start();

// Check if the user is an approver
if ($_SESSION['user_role'] !== 'approver') {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

// Get proposal ID from URL
$proposal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($proposal_id === 0) {
    echo "Invalid Proposal ID";
    exit;
}
// Ensure you have a database connection established ($conn)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture form values
    $proposal_id = $_POST['proposal_id'];
    $status = $_POST['status'];
    $reject_reason = isset($_POST['reject_reason']) ? $_POST['reject_reason'] : null;
    $category = $_POST['category'];
    $comments = isset($_POST['comments']) ? $_POST['comments'] : null;
    $show_to_client = isset($_POST['show_to_client']) ? 1 : 0; // Checkbox value (0 or 1)
    $user_id = $_SESSION['user_id']; // Assuming user_id is stored in session (adjust as needed)
    
    // Step 1: Update the `proposals` table with status, reason, and category
    $sql_update_proposal = "UPDATE proposals SET 
        status = '$status', 
        reject_reason = '$reject_reason', 
        category = '$category'
        WHERE proposal_id = '$proposal_id'";

    if (mysqli_query($conn, $sql_update_proposal)) {
        // Step 2: Insert a new comment in the `proposal_comments` table if there is a comment
        if ($comments) {
            $created_at = date('Y-m-d H:i:s');
            $sql_insert_comment = "INSERT INTO proposal_comments 
                (proposal_id, user_id, comment, created_at, show_to_client) 
                VALUES 
                ('$proposal_id', '$user_id', '$comments', '$created_at', '$show_to_client')";

            if (mysqli_query($conn, $sql_insert_comment)) {
                // Comment inserted successfully
                echo "Proposal and comment updated successfully!";
            } else {
                // Handle comment insert error
                echo "Error inserting comment: " . mysqli_error($conn);
            }
        } else {
            // Redirect to proposal.php after success
            header("Location: proposal.php");
            exit(); // Ensure no further code is executed after the redirect
        }
    } else {
        // Handle proposal update error
        echo "Error updating proposal: " . mysqli_error($conn);
    }
}
// Fetch proposal details
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
    COALESCE(u2.full_name, 'NOT ALLOCATED') AS 'Allocated To',
    ps.description
FROM proposals p 
INNER JOIN users u ON p.created_by = u.id
INNER JOIN proposal_status_master ps ON p.status = ps.status_id
LEFT JOIN users u3 ON p.ar_user_id = u3.id 
LEFT JOIN users u2 ON p.allocated_to_user_id = u2.id

where p.id = '$proposal_id'";
$result = mysqli_query($conn, $query);
$proposal = mysqli_fetch_assoc($result);

if (!$proposal) {
    echo "Proposal not found!";
    exit;
}

$status_description = htmlspecialchars($proposal["description"]);

$comments_query = "select *, u.full_name user from proposal_comments c join users u on c.user_id = u.id where c.proposal_id='$proposal_id'";
$comments_result = mysqli_query($conn, $comments_query);
$proposal_comments = mysqli_fetch_all($comments_result, MYSQLI_ASSOC);

$query = "select id, reason From reject_reason_master ;";
$reject_reasons = mysqli_query($conn, $query);

$query = "select status_id, status_name  from proposal_status_master where status_id in(9, 10, 11, 12);";
$approve_status = mysqli_query($conn, $query);

$query = "select status_id, status_name  from proposal_status_master where status_id in(9, 10, 11, 12);";
$approve_status = mysqli_query($conn, $query);

$query = "SELECT p.id, document_type, file_path, c.category FROM proposal_documents p join document_category_master c on p.category_id = c.id where proposal_id = '$proposal_id' order by category_id, id;";
$result  = mysqli_query($conn, $query);
$proposal_documents = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proposal_documents[] = $row; // Convert result to an array
}

// Encode properly as JSON
echo "<script>const proposalDocuments = " . json_encode($proposal_documents) . ";</script>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/proposal-form.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-size: 14px; }
        .card { padding: 10px; }
        .border { padding: 5px; }
        .chat-container { max-height: 250px; overflow-y: auto; background: #f9f9f9; border-radius: 5px; padding: 8px; }
        .chat-message { padding: 6px; border-radius: 5px; margin-bottom: 5px; max-width: 80%; }
        .left-message {
            background: #e0e0e0;
            text-align: left;
            float: left;
            clear: both;
        }
        .right-message {
            background: #007bff;
            color: white;
            text-align: right;
            float: right;
            clear: both;
        }
        .chat-timestamp {
            font-size: 0.8rem;
            color: #666;
            display: block;
            margin-top: 4px;
        }
     
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Proposal Approval -  ID <?php echo htmlspecialchars($proposal_id); ?></h5>
            <span class="badge bg-success" style="font-size: 1rem;"><?php echo $status_description; ?></span>
        </div>
        <div class="card-body" style="padding:10px!important;">
           <!-- Proposal Details -->
            <div class="container" >
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border p-2 rounded text-center">
                            <span class="fw-bold text-primary"><?php echo htmlspecialchars($proposal['Agent Name']); ?></span>
                            <div class="text-muted small">Proposal ID</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-2 rounded text-center">
                            <span class="fw-bold"><?php echo htmlspecialchars($proposal['Borrower Name']); ?></span>
                            <div class="text-muted small">Borrower Name</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-2 rounded text-center">
                            <span class="fw-bold"><?php echo htmlspecialchars($proposal['City']); ?></span>
                            <div class="text-muted small">City</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-2 rounded text-center">
                            <span class="fw-bold"><?php echo htmlspecialchars($proposal['Vehicle']); ?></span>
                            <div class="text-muted small">Vehicle</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-2 rounded text-center">
                            <span class="fw-bold text-success">₹<?php echo number_format($proposal['Loan Amount'], 2); ?></span>
                            <div class="text-muted small">Loan Amount</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border p-2 rounded text-center">
                            <span class="fw-bold"><?php echo htmlspecialchars($proposal['Allocated To']); ?></span>
                            <div class="text-muted small">Allocated To</div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="process_approval.php">
                <input type="hidden" name="proposal_id" value="<?php echo $proposal_id; ?>">

                <!-- Approval Action -->
                <div class="container mt-4" style="padding:10px!important;margin:0px!important">
                    <div class="row g-3">
                   <!-- Approval Status -->
                    <div class="col-md-4">
                        <label for="approval_status" class="form-label fw-bold">Approval Action</label>
                        <select class="form-select" id="approval_status" name="status" required onchange="toggleReasonField()">
                            <option value="">Select Action</option>
                            <?php
                            // Loop through $approval_statuses array and populate the dropdown
                            foreach ($approve_status as $status) {
                                echo "<option value='{$status['status_id']}'>{$status['status_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>


                        <!-- Reason for Rejection (Hidden Initially) -->
                        <div class="col-md-4 d-none" id="reason_section">
                            <label for="reject_reason" class="form-label fw-bold">Reason for Rejection</label>
                            <select class="form-select" id="reject_reason" name="reject_reason">
                                <option value="">Select Reason</option>
                                <?php
                                // Loop through $reject_reasons array and populate the dropdown
                                foreach ($reject_reasons as $reason) {
                                    echo "<option value='{$reason['id']}'>{$reason['reason']}</option>";
                                }
                                ?>
                            </select>
                        </div>


                        <!-- Category Selection -->
                        <div class="col-md-4">
                            <label for="category" class="form-label fw-bold">Select Category</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="A">Category A</option>
                                <option value="B">Category B</option>
                                <option value="C">Category C</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="container mt-4">
                    <div class="row ">
                        <div class="col-auto">
                            <a href="#" class="btn btn-secondary  btn-sm d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#documentModal" style="width: 200px;">
                                <i class="fas fa-file-alt"></i> View Documents
                            </a>
                        </div>
                    </div>
                </div>
                <div class="container mt-4">
                    <h5 class="mb-3">Comments</h5>

                    <!-- Comments Box -->
                    <div class="comments-box border rounded p-3 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <?php if (!empty($proposal_comments)) { ?>
                            <?php foreach ($proposal_comments as $comment) { ?>
                                <div class="d-flex align-items-start mb-3">
                                    <!-- User Avatar/Icon -->
                                    <div class="me-2">
                                        <span class="badge bg-primary rounded-circle p-2">
                                            <?php echo strtoupper(substr($comment['user'], 0, 1)); ?>
                                        </span>
                                    </div>

                                    <!-- Comment Content -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($comment['user']); ?></strong>
                                            <small class="text-muted">
                                                <?php echo date("Y-m-d h:i A", strtotime($comment['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="m-0 p-2 bg-white rounded border">
                                            <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <p class="text-muted">No comments yet.</p>
                        <?php } ?>
                    </div>

                    <!-- New Comment Input -->
                    <div class="mt-3">
                        <label class="form-label fw-bold">New Comment</label>
                        <textarea class="form-control" name="comments" rows="3" placeholder="Enter your comment"></textarea>

                        <!-- Show to Client Checkbox -->
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="show_to_client" name="show_to_client">
                            <label class="form-check-label" for="show_to_client">Show to Client</label>
                        </div>
                    </div>
                </div>
                <!-- Submit Button -->
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
                </div>
            </form>

        </div>
    </div>
</div>

<div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="documentModalLabel">Available Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Category</th>
                            <th>Document Type</th>
                            <th>View</th>
                        </tr>
                    </thead>
                    <tbody id="documentTableBody">
                        <!-- Dynamic rows will be added here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>

document.addEventListener("DOMContentLoaded", function () {
    const documentTableBody = document.getElementById("documentTableBody");

    // Data from PHP
    const proposalDocuments = <?php echo json_encode($proposal_documents); ?>;

    documentTableBody.innerHTML = ""; // Clear previous content

    proposalDocuments.forEach((doc, index) => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${doc.category}</td>
            <td>${doc.document_type}</td>
            <td>
                <a href="${doc.file_path}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye"></i> View
                </a>
            </td>
        `;
        documentTableBody.appendChild(row);
    });
});

function toggleReasonField() {
    let approvalStatus = document.getElementById("approval_status").value;
    let reasonSection = document.getElementById("reason_section");

    if (approvalStatus === "10") {
        reasonSection.classList.remove("d-none");
    } else {
        reasonSection.classList.add("d-none");
    }
}
</script>
</body>
</html>
