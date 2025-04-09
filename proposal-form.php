<?php
include 'config.php'; // Database connection
session_start(); // Start the session
$show_alert = 0;
$maxFileSize = 2 * 1024 * 1024;
$proposal_mode = "NEW"; // Default mode
$current_status ='';
$allow_edit = "EDIT";
$status_description="";
$proposal_documents_json = json_encode(null, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); 
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $proposal_id = intval($_GET['id']); // Get proposal ID from query string

    $query = "update proposals set allocated_to_user_id = '" . $_SESSION['user_id'] . "', status=3  WHERE id='$proposal_id' and status in(1,2)";
    $result = mysqli_query($conn, $query);

    $proposal_mode = "OPEN"; // Change mode to EDIT
    $query = "SELECT *, s.status_name, s.description FROM proposals p join proposal_status_master s on p.status = s.status_id  WHERE id='$proposal_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $selected_proposal_details = mysqli_fetch_assoc($result); // Fetch the row as an associative array
        $current_status = $selected_proposal_details["status"];
        $current_status_text = $selected_proposal_details["status_name"];
        $status_description = $selected_proposal_details["description"];

        if ($_SESSION["user_role"] == "sales") {
            // Add condition to the query to filter by 'show_to_client = 1'
            $query = "SELECT *, u.full_name AS user 
                      FROM proposal_comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.proposal_id = '$proposal_id' 
                      AND c.show_to_client = 1";
        } else {
            // If the user is not 'sales', run the query without the 'show_to_client' condition
            $query = "SELECT *, u.full_name AS user 
                      FROM proposal_comments c 
                      JOIN users u ON c.user_id = u.id 
                      WHERE c.proposal_id = '$proposal_id'";
        }
        $proposal_comments = mysqli_query($conn, $query);

        $query = "SELECT * FROM proposal_documents WHERE proposal_id='$proposal_id'";
        $result = mysqli_query($conn, $query);

        $proposal_documents = []; // Initialize an array

        while ($row = mysqli_fetch_assoc($result)) {
            $proposal_documents[] = $row; // Fetch each row as an associative array
        }
        $proposal_documents_json = json_encode($proposal_documents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); 

        

    } else {
        echo "Error: " . mysqli_error($conn); // Debugging in case of error
    }

} else {
    $proposal_id = ""; // No ID means a new proposal
}


$allowed_statuses  = ['Draft'];
// Allowed Status Names (Set dynamically based on your business logic)
if ( $_SESSION['user_role'] === "sales")
{
    if($proposal_mode === "NEW")
        $allowed_statuses = ['Draft', 'Submitted for Review']; 
    else if($proposal_mode === "OPEN")
    {
        if ($current_status == 1)
        {
            $allow_edit = "EDIT";
            $allowed_statuses = ['Draft', 'Submitted for Review']; 
        }
        else if ($current_status == 4 || $current_status == 5 )
        {
            $allow_edit = "EDIT";
            $allowed_statuses = ['Resubmitted']; 
        }
        else
        {
            $allow_edit = "VIEW ONLY";
        }
    }
}
else if ( $_SESSION['user_role'] === "user")
{
    if($proposal_mode === "OPEN")
    {
        if ($current_status != 1 && $current_status != 8 && $current_status != 9 && $current_status != 10  && $current_status != 12)
        {
            $allow_edit = "EDIT";
        }
        else
        {
            $allow_edit = "VIEW ONLY";
        }
    }
    
        $allowed_statuses = ['Under Review', 'Documents Requested', 'Sent for Approval', 'Cancelled']; 
}
else if ( $_SESSION['user_role'] === "approver")
{
    
        $allow_edit = "VIEW ONLY";
    
        $allowed_statuses = ['Approved', 'Documents Requested','Rejected', 'Ask for More Details']; 
}

// Prepare a parameterized query with placeholders
$placeholders = implode(',', array_fill(0, count($allowed_statuses), '?'));

// Construct query
$query = "SELECT status_id, status_name FROM proposal_status_master WHERE status_name IN ($placeholders)";


// Prepare the statement
$stmt = $conn->prepare($query);

// Bind parameters dynamically
$types = str_repeat('s', count($allowed_statuses)); // 's' for string parameters
$stmt->bind_param($types, ...$allowed_statuses);

// $final_query = vsprintf(str_replace("?", "'%s'", $query), $allowed_statuses);

// // Print the final query
// echo "Final query: " . $final_query . "\n";

// Execute query
$stmt->execute();
$result = $stmt->get_result();

// Fetch results
$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[] = $row;
}


// Close statement
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $proposal_mode = $_POST['hf_proposal_mode']; // NEW or OPEN
    $proposal_id = isset($_POST['hf_proposal_id']) ? (int)$_POST['hf_proposal_id'] : null;
    $deleted_files = isset($_POST['hf_deleted_documents']) ? explode(',', $_POST['hf_deleted_documents']) : [];

    $proposal_status = isset($_POST['proposal_status']) ? (int)$_POST['proposal_status'] : null;
    // Create a mapping for fields that have different names in the form and database
        $field_mapping = [
            'email_id' => 'email',
            'borrower_name' => 'borrower_name',
            'initials' => 'initials',
            'mobile_number' => 'mobile_number',
            'city' => 'city',
            'vehicle_name' => 'vehicle_name',
            'vehicle_model' => 'model',
            'loan_amount' => 'loan_amount',
            'coapplicant_name' => 'co_applicant_name',
            'coapplicant_mobile' => 'co_applicant_mobile',
            'coapplicant_relationship' => 'co_applicant_relationship',
            'proposal_status' => 'status'
        ];
    // Sanitize inputs
    $borrower_name = mysqli_real_escape_string($conn, $_POST['borrower_name']);
    $initials = mysqli_real_escape_string($conn, $_POST['initials']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $email_id = mysqli_real_escape_string($conn, $_POST['email_id']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $model = mysqli_real_escape_string($conn, $_POST['vehicle_model']);
    $loan_amount = mysqli_real_escape_string($conn, $_POST['loan_amount']);
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);
    $agent_request_number = $_POST['agent_request_number'];
    $co_name = mysqli_real_escape_string($conn, $_POST['coapplicant_name']);
    $co_mobile = mysqli_real_escape_string($conn, $_POST['coapplicant_mobile']);
    $co_relationship = mysqli_real_escape_string($conn, $_POST['coapplicant_relationship']);
    $created_by = $_SESSION['user_id']; // Assuming user is logged in
    // INSERT or UPDATE proposal
    if ($proposal_mode === "NEW") {
        $sql = "INSERT INTO proposals 
                (borrower_name, initials, mobile_number, email, city, vehicle_name, model, loan_amount,  co_applicant_name, co_applicant_mobile, co_applicant_relationship, created_by, status, ar_user_id)
                VALUES 
                ('$borrower_name', '$initials', '$mobile_number', '$email_id', '$city', '$vehicle_name', '$model', '$loan_amount','$co_name', '$co_mobile', '$co_relationship', '$created_by','$proposal_status','$agent_request_number')";
        
        if (mysqli_query($conn, $sql)) {
            $proposal_id = mysqli_insert_id($conn); // Get last inserted proposal ID
        }
    } elseif ($proposal_mode === "OPEN" && $proposal_id) {
         // Fetch the current proposal data to log old values
            $select_sql = "SELECT * FROM proposals WHERE id = '$proposal_id'";
            $result = mysqli_query($conn, $select_sql);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $existing_data = mysqli_fetch_assoc($result);

                // Step 1: Insert the header record first (to get the header ID)
                $header_sql = "INSERT INTO audit_logs_header (proposal_id, action_type, changed_by) 
                VALUES ('$proposal_id', 'UPDATE', '$created_by')";
                if (mysqli_query($conn, $header_sql)) {
                $audit_header_id = mysqli_insert_id($conn);  // Get the ID of the inserted header
                } else {
                // Handle error if header insertion fails
                die('Error inserting audit log header: ' . mysqli_error($conn));
                }

                // Step 2: Initialize an array to hold modified fields for the header
                $modified_fields = [];

                foreach ($field_mapping as $form_field => $db_field) {
                $new_value = isset($_POST[$form_field]) ? $_POST[$form_field] : ''; // Get the new value from POST

                // Compare existing value and new value
                if ($existing_data[$db_field] !== $new_value) {
                // Log the change in file (for debugging purposes)
                file_put_contents('log.txt', $existing_data[$db_field] . " - " . $new_value . "\n", FILE_APPEND);

                // Insert the change into the audit_logs_details table
                $log_sql = "INSERT INTO audit_logs_details (audit_header_id, field_name, old_value, new_value) 
                    VALUES ('$audit_header_id', '$db_field', '" . mysqli_real_escape_string($conn, $existing_data[$db_field]) . "', '" . mysqli_real_escape_string($conn, $new_value) . "')";
                mysqli_query($conn, $log_sql);

                // Add this field to the list of modified fields
                $modified_fields[] = $db_field;
                }
                }

                // Step 3: Update the header with the modified fields
                if (!empty($modified_fields)) {
                $modified_fields_str = implode(',', $modified_fields); // Convert array to comma-separated string
                $update_header_sql = "UPDATE audit_logs_header 
                        SET modified_fields = '" . mysqli_real_escape_string($conn, $modified_fields_str) . "'
                        WHERE id = '$audit_header_id'";
                mysqli_query($conn, $update_header_sql);
                }
            }
        $sql = "UPDATE proposals 
                SET borrower_name='$borrower_name', initials='$initials', mobile_number='$mobile_number',
                    email='$email_id', city='$city', vehicle_name='$vehicle_name', model='$model',
                    loan_amount='$loan_amount', co_applicant_name='$co_name', 
                    co_applicant_mobile='$co_mobile', co_applicant_relationship='$co_relationship', 
                    status='$proposal_status' 
                WHERE id = '$proposal_id'";

        mysqli_query($conn, $sql);
    }

    // Delete old files if any
    if (!empty($deleted_files)) {
        foreach ($deleted_files as $file_path) {
            $file_path = trim($file_path);
            if (!empty($file_path)) {
                $delete_sql = "DELETE FROM proposal_documents WHERE proposal_id = '$proposal_id' AND file_path = '$file_path'";
                mysqli_query($conn, $delete_sql);
                if (file_exists($file_path)) {
                    unlink($file_path); // Delete the file from storage
                }
            }
        }
    }
    
    // Handle file uploads
    if (isset($_FILES['files']) && $proposal_id) {
        
        $uploadDir = "uploads/";
        foreach ($_FILES['files']['name'] as $category => $files) { 
            foreach ($files as $key => $fileName) {
                if ($_FILES['files']['error'][$category][$key] === 0) {
                    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                    $uniqueName = $baseName . '_' . time() . '_' . uniqid() . '.' . $fileExt;
                    $uploadFile = $uploadDir . $uniqueName;

                    if (move_uploaded_file($_FILES['files']['tmp_name'][$category][$key], $uploadFile)) {
                        $document_type = ($fileExt === 'pdf') ? 'pdf' : 'image';
                        $doc_sql = "INSERT INTO proposal_documents 
                                    (proposal_id, document_type, file_path, uploaded_at, created_by, category_id) 
                                    VALUES ('$proposal_id', '$document_type', '$uploadFile', NOW(), '$created_by', '$category')";
                        mysqli_query($conn, $doc_sql);
                    }
                }
            }
        }
    }

    // Save comment if any
    
    if (!empty($comments) && $proposal_id) {
        $comment_sql = "INSERT INTO proposal_comments (proposal_id, comment, created_at, user_id, show_to_client) 
                        VALUES ('$proposal_id', '$comments', NOW(), '$created_by', 1)";
        mysqli_query($conn, $comment_sql);
    }

    $_SESSION['alert_message'] = "Proposal saved successfully!";
    $_SESSION['alert_type'] = "success";
    header("Location: proposal.php");
    exit;
}


$query = "select id, full_name from users where role='sales'";
$agent_users = mysqli_query($conn, $query);

$is_disabled_class = ($allow_edit === "VIEW ONLY") ? 'disabled-div' : '';

$query = "select id, category, isadditional from document_category_master";
$document_category = mysqli_query($conn, $query);


mysqli_close($conn);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Proposal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/proposal-form.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (including Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    
</head>
<body>
    <div id="fileSizeAlert" class="alert alert-danger d-none position-fixed top-0 start-50 translate-middle-x mt-3" role="alert">
    <strong>File Size Error:</strong> <span id="fileSizeMessage"></span>
</div>

    <div class="container mt-4">
    <button type="button" class="close-fixed" >
        <i class="bi bi-x-circle"></i>
    </button>        
    <h4 class="mb-3">
    <h4 class="mb-3">
        <?php if ($proposal_mode === "OPEN") { ?>
            <span class="text-primary">
            <h4 class="mb-3">
                <i class="bi bi-pencil-square text-primary" style="font-size: 1.2rem;"></i> 
                <span class="<?php echo ($allow_edit === 'EDIT') ? 'text-success' : 'text-danger'; ?>" style="font-size: 1.2rem;">
                    <?php echo ($allow_edit === 'EDIT') ? 'Editable' : 'View Only'; ?>
                </span>  
                <span class="text-dark">ID: <em class="fw-bold"><?php echo htmlspecialchars($proposal_id); ?></em></span>  
                <span class="badge 
                    <?php echo ($allow_edit === 'EDIT') ? 'bg-success' : 'bg-danger'; ?>" 
                    style="font-size: 1rem;">
                    <?php echo htmlspecialchars($status_description); ?>
                </span>
            </h4>


            </span>
        <?php } else { ?>
            <span class="text-success">
                <i class="bi bi-plus-circle"></i> Create New Proposal
            </span>
        <?php } ?>
    </h4>
    <div>
    <form action="" method="POST" id="uploadForm" enctype="multipart/form-data">
    <input type="hidden" id="hf_proposal_id" name="hf_proposal_id" value="<?php echo htmlspecialchars($proposal_id); ?>">
    <input type="hidden" id="hf_proposal_mode" name="hf_proposal_mode" value="<?php echo htmlspecialchars($proposal_mode); ?>">
    <input type="hidden" id="hf_deleted_documents" name="hf_deleted_documents">
    <div class="<?php echo $is_disabled_class; ?>">
    <div class="row">
        <!-- Left Side: Applicant Details -->
        <div class="col-md-6">
            <h5>Applicant Details</h5>
            <div class="form-group">
                <label>Agent</label>
                
                <select class="form-control" name="agent_request_number" id="agent_request_number"
                    <?php 
                        $is_disabled = ($_SESSION['user_role'] === "user" || $_SESSION['user_role'] === "sales");
                        echo $is_disabled ? 'disabled' : ''; 
                    ?>
                    onchange="syncHiddenField(this)">
                    <option value="">-- Select Agent --</option>
                    <?php foreach ($agent_users as $user) { ?>
                        <option value="<?php echo $user['id']; ?>" 
                            <?php 
                                if ($proposal_mode === "OPEN" && $selected_proposal_details['ar_user_id'] == $user['id']) {
                                    echo 'selected';
                                }
                                elseif ($proposal_mode === "NEW" && $_SESSION['user_id'] == $user['id']) {
                                    echo 'selected';
                                }
                            ?>>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </option>
                    <?php } ?>
                </select>

                <!-- Hidden field is only added if the dropdown is disabled -->
                <?php if ($is_disabled) { ?>
                    <input type="hidden" name="agent_request_number" id="hidden_agent_request_number" 
                        value="<?php echo $selected_proposal_details['ar_user_id'] ?? $_SESSION['user_id']; ?>">
                <?php } ?>
            </div>
            <div class="form-group">
                <label>Borrower Name</label>
                <input type="text" class="form-control" name="borrower_name" placeholder="Borrower name" value="<?php echo htmlspecialchars($selected_proposal_details['borrower_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Initials</label>
                <input type="text" class="form-control" name="initials" placeholder="Only alphabets, space, or periods" value="<?php echo htmlspecialchars($selected_proposal_details['initials'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" class="form-control" name="mobile_number" placeholder="10-digit number" required value="<?php echo htmlspecialchars($selected_proposal_details['mobile_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Email ID</label>
                <input type="email" class="form-control" name="email_id" placeholder="Borrower’s email id" required value="<?php echo htmlspecialchars($selected_proposal_details['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" class="form-control" name="city" placeholder="Borrower’s city" required value="<?php echo htmlspecialchars($selected_proposal_details['city'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Vehicle Name</label>
                <input type="text" class="form-control" name="vehicle_name" placeholder="Name of the vehicle" required value="<?php echo htmlspecialchars($selected_proposal_details['vehicle_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" class="form-control" name="vehicle_model" placeholder="Manufacturing year" required value="<?php echo htmlspecialchars($selected_proposal_details['model'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Loan Amount</label>
                <input type="text" class="form-control" name="loan_amount" placeholder="Requested loan amount" required value="<?php echo htmlspecialchars($selected_proposal_details['loan_amount'] ?? ''); ?>">
            </div>
            
            <h5>Co-Applicant Details</h5>
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" name="coapplicant_name" placeholder="Co-applicant Name" value="<?php echo htmlspecialchars($selected_proposal_details['co_applicant_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" class="form-control" name="coapplicant_mobile" placeholder="Co-applicant contact number" value="<?php echo htmlspecialchars($selected_proposal_details['co_applicant_mobile'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Relationship</label>
                <select class="form-control" name="coapplicant_relationship">
                    <option value="">-- Relationship with borrower --</option>
                    <option value="spouse" <?php echo (isset($selected_proposal_details['co_applicant_relationship']) && $selected_proposal_details['co_applicant_relationship'] == 'spouse') ? 'selected' : ''; ?>>Spouse</option>
                    <option value="parent" <?php echo (isset($selected_proposal_details['co_applicant_relationship']) && $selected_proposal_details['co_applicant_relationship'] == 'parent') ? 'selected' : ''; ?>>Parent</option>
                    <option value="sibling" <?php echo (isset($selected_proposal_details['co_applicant_relationship']) && $selected_proposal_details['co_applicant_relationship'] == 'sibling') ? 'selected' : ''; ?>>Sibling</option>
                </select>
            </div>
        </div>

        
        <!-- Right Side: Comments Details -->
        <div class="col-md-6">
            <!-- Comments Section -->
            <h5 class="mt-4">Comments</h5>
            <div class="comments-box">
                <!-- Scrollable Comments List -->
                <div class="comments-list">
                    <?php if (!empty($proposal_comments)) { ?>
                        <?php foreach ($proposal_comments as $comment) { ?>
                            <div class="comment-entry">
                                <span class="comment-icon comment-user"></span>
                                <div class="comment-content">
                                    <small>
                                        <span><strong><?php echo htmlspecialchars($comment['user']); ?></strong></span>
                                        <span class="comment-time"><?php echo date("Y-m-d h:i A", strtotime($comment['created_at'])); ?></span>
                                    </small>
                                    <p><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } else { ?>
                        <p>No comments yet.</p>
                    <?php } ?>
                </div>

                <!-- Fixed New Comment Input -->
                <div class="new-comment">
                    <label class="comment-label">New Comment</label>
                    <textarea class="form-control" name="comments" placeholder="Enter your comment"></textarea>
                </div>
            </div>
        </div>

    </div>
    </div>
    <!-- Upload Documents Section -->
    <div class="mt-4" style="padding-bottom:150px;" >
        <h5>Upload Documents</h5>
        <div class="row">
            <div id="documentCategories"></div>
        </div>
    </div>

   <!-- Submit Button -->
    <div class="mt-4 text-end" style="position: fixed; z-index:999; bottom:0px; right:0px; background-color:#fff; width:100%;">
        <!-- Status Dropdown -->
        <select id="statusDropdown" name="proposal_status" class="form-select d-inline-block w-auto me-2 <?php echo $is_disabled_class; ?>" onchange="toggleSubmitButton()">
            <option value="" disabled selected>-- Select Status --</option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars($status['status_id']) ?>"><?= htmlspecialchars($status['status_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Submit Button -->
        <button type="submit" id="submitBtn" style="top:10px; position:relative" onclick="setAction()" class="btn btn-success <?php echo $is_disabled_class; ?>" disabled>
            <i class="fas fa-paper-plane"></i> Submit Proposal
        </button>

        <input type="hidden" name="action" id="actionField">
        <input type="hidden" name="alert_flag" id="alert_flag">
        <input type="hidden" name="alert_message" id="alert_message">
    </div>


</form>
    </div>
    </div>
    <!-- Modal to display enlarged content -->
    <!-- Modal HTML -->
    <!-- Modal Structure -->
<div id="previewModal" class="modal" style="display: none; align-items: center; justify-content: center;">
  <div class="modal-content" style="position: relative; background: white; padding: 20px; border-radius: 10px; max-width: 90%; max-height: 90vh; overflow: hidden;">

    <!-- 🛠 Toolbar (now floating) -->
    <div id="toolbar" style="
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 1000;
      background: rgba(255, 255, 255, 0.9);
      padding: 6px 10px;
      border-radius: 6px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    ">
      <button onclick="zoomIn()">🔍+</button>
      <button onclick="zoomOut()">🔍−</button>
      <button onclick="rotate()">🔄</button>
      <button onclick="closeModal()">❌</button>
    </div>

    <!-- 📄 Content viewer -->
    <div id="modalPreviewContainer" style="
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
    "></div>
    
  </div>
</div>



<script>

let zoomLevel = 1;
let rotation = 0;
let currentViewer = null;
let currentType = null;

const user_role = "<?php echo $_SESSION['user_role']; ?>"; // Get user role from PHP

let documentCategories = [
        <?php
        foreach ($document_category as $doc) {
            if ($doc['isadditional'] == 0) { // Check if isadditional is 0
                echo "{ id: '{$doc['id']}', name: '{$doc['category']}', class: 'card-sales' },";
            }
        }
        ?>
    ];


    // If user_role is not 'sales', add additional documents
    if (user_role.toLowerCase() !== 'sales') {
        const additionalDocuments = [
            <?php
            foreach ($document_category as $doc) {
                if ($doc['isadditional'] == 1) { // Include only additional documents
                    echo "{ id: '{$doc['id']}', name: '{$doc['category']}', class: 'card-other' },";
                }
            }
            ?>
        ];

        documentCategories = documentCategories.concat(additionalDocuments);
    }
const filesToUpload = {}; // Store newly added files
const proposal_documents2 = { 
    "1": ["uploads/aadhar1.png", "uploads/aadhar2.pdf"], 
    "2": ["uploads/license1.jpg"], 
    "3": ["uploads/voter1.pdf"]
}; // Preloaded existing documents
 
const proposal_documents = <?php echo $proposal_documents_json ?: '[]'; ?>; // Ensure it's an array

const categorizedDocuments = {};

if (Array.isArray(proposal_documents)) { // Check if proposal_documents is a valid array
    proposal_documents.forEach(doc => {
        const categoryId = doc.category_id;
        
        if (!categorizedDocuments[categoryId]) {
            categorizedDocuments[categoryId] = [];
        }
        
        categorizedDocuments[categoryId].push(doc);
    });
}


const uploadForm = document.getElementById('uploadForm');
const uploadBtn = document.getElementById('uploadBtn');
const documentCategoriesContainer = document.getElementById('documentCategories');

// Function to create a category card with file input and preview section
function createCategoryCard(category) {
    const card = document.createElement('div');
    card.classList.add('category-card');
    
    card.id = category.id;

    const cardHeaderDiv = document.createElement('div');
    card.appendChild(cardHeaderDiv);

    const title = document.createElement('h3');
    title.classList.add(category.class);
    title.textContent = category.name;
    cardHeaderDiv.appendChild(title);

    const pasteButton = document.createElement('span');
    pasteButton.innerHTML = '<i class="fas fa-paste"></i> Paste Image'; // FontAwesome Icon
    pasteButton.classList.add('category-card-paste-button');
    var isDisabledClass = "<?php echo $is_disabled_class; ?>"; // Get PHP class
    if (isDisabledClass) {
        pasteButton.classList.add(isDisabledClass); // Add the PHP class dynamically
        }
    pasteButton.onclick = function () {
        triggerPaste(category.id);
    };
    cardHeaderDiv.appendChild(pasteButton);

    const cardContainer = document.createElement('div');
    card.appendChild(cardContainer);
    cardContainer.classList.add('category-card-container');

    isDisabledClass = "<?php echo $is_disabled_class; ?>"; // Get PHP class

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*,application/pdf';
        fileInput.multiple = true;

        if (isDisabledClass) {
            fileInput.classList.add(isDisabledClass); // Add PHP class dynamically
        }

        // Optional: Disable the input if the class represents a disabled state
        if (isDisabledClass === 'disabled') {
            fileInput.setAttribute('disabled', 'disabled');
        }
    
    fileInput.addEventListener('change', function () {
        handleFileSelect(category.id, fileInput);
    });
    cardContainer.appendChild(fileInput);

    const previewContainer = document.createElement('div');
    previewContainer.classList.add('preview-container');
    previewContainer.id = `${category.id}-preview`;
    card.appendChild(previewContainer);

    documentCategoriesContainer.appendChild(card);

    // Load existing documents if available
    if (categorizedDocuments[category.id]) {
        loadExistingDocuments(category.id, categorizedDocuments[category.id]);
    }
}

// Function to load existing documents
function loadExistingDocuments(categoryId, documents) {
    const previewContainer = document.getElementById(`${categoryId}-preview`);

    if (!filesToUpload[categoryId]) {
        filesToUpload[categoryId] = [];
    }

    documents.forEach(doc => {
        const fileDiv = document.createElement('div');
        fileDiv.classList.add('file-preview');

        let preview;
        if (doc.document_type === 'image') {
            preview = document.createElement('img');
            preview.src = doc.file_path; // Use file_path from docUrl
            preview.alt = 'Uploaded Image';
            preview.onclick = function () {
                openModal(preview.src, 'image');
            };
        } else if (doc.document_type === 'pdf') {
            preview = document.createElement('div');
            preview.classList.add('pdf-icon');
            preview.innerHTML = '<i class="fas fa-file-pdf"></i> View PDF';
            preview.onclick = function () {
                window.open(doc.file_path, '_blank');
            };
        }

        fileDiv.appendChild(preview);

        // Remove Button
        const removeBtn = document.createElement('div');
        removeBtn.classList.add('file-remove');
        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove';
        const isDisabledClass = "<?php echo $is_disabled_class; ?>"; // Get PHP class
        if (isDisabledClass) {
                removeBtn.classList.add(isDisabledClass); // Add the PHP class dynamically
            }
        removeBtn.onclick = function () {
            let hfDeletedDocuments = document.getElementById("hf_deleted_documents");
            hfDeletedDocuments.value = doc.file_path + ",";
            fileDiv.remove();
        };
        fileDiv.appendChild(removeBtn);

        previewContainer.appendChild(fileDiv);
        filesToUpload[categoryId].push(doc);
    });

    
}


// Function to handle file selection and display previews
const maxFileSize = <?php echo $maxFileSize; ?>; // Get from PHP

function handleFileSelect(categoryId, input) {
    const files = input.files;
    const previewContainer = document.getElementById(`${categoryId}-preview`);
    const alertContainer = document.getElementById("alert-container");

    if (!filesToUpload[categoryId]) {
        filesToUpload[categoryId] = [];
    }

    for (let i = 0; i < files.length; i++) {
        const file = files[i];

        // ✅ File Size Validation
        if (file.size > maxFileSize) {
            showAlert(`🚫 File "${file.name}" exceeds the maximum size of ${maxFileSize / (1024 * 1024)}MB.`, "danger");
            continue; // Skip this file
        }

        const fileDiv = document.createElement('div');
        fileDiv.classList.add('file-preview');

        let preview;
        if (file.type.startsWith('image/')) {
            preview = document.createElement('img');
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            preview.onclick = function () {
                openModal(preview.src, 'image');
            };
            reader.readAsDataURL(file);
        } else if (file.type === 'application/pdf') {
            preview = document.createElement('div');
            preview.classList.add('pdf-icon');
            preview.innerHTML = '<i class="fas fa-file-pdf"></i>';
            preview.onclick = function () {
                openModal(URL.createObjectURL(file), 'pdf');
            };
        }

        fileDiv.appendChild(preview);

        const removeBtn = document.createElement('div');
        removeBtn.classList.add('file-remove');
        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove';
        removeBtn.onclick = function () {
            let hfDeletedDocuments = document.getElementById("hf_deleted_documents");
            hfDeletedDocuments.value = file.file_path + ",";
            fileDiv.remove();
            const index = filesToUpload[categoryId].indexOf(file);
            if (index > -1) filesToUpload[categoryId].splice(index, 1);
        };
        fileDiv.appendChild(removeBtn);

        previewContainer.appendChild(fileDiv);
        filesToUpload[categoryId].push(file);
    }
}
// **Function to Show Bootstrap Alert and Auto-Hide After 5 Seconds**
function showAlert(message) {
    const alertBox = document.getElementById("fileSizeAlert");
    const alertMessage = document.getElementById("fileSizeMessage");

    alertMessage.innerText = message;
    alertBox.classList.remove("d-none");

    setTimeout(() => {
        alertBox.classList.add("d-none");
    }, 5000); // Auto-hide after 5 seconds
}

// Function to handle paste events for images
async function triggerPaste(categoryId) {
    try {
        const permission = await navigator.permissions.query({ name: "clipboard-read" });
        if (permission.state === "denied") {
            console.error("Clipboard access denied. Please allow permissions.");
            return;
        }

        const clipboardItems = await navigator.clipboard.read();
        const previewContainer = document.getElementById(`${categoryId}-preview`);

        if (!filesToUpload[categoryId]) {
            filesToUpload[categoryId] = [];
        }

        for (const item of clipboardItems) {
            for (const type of item.types) {
                if (type.startsWith("image/")) {
                    const blob = await item.getType(type);
                    const file = new File([blob], `pasted-image-${Date.now()}.png`, { type });

                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement("img");
                        img.src = e.target.result;
                        img.style.maxWidth = "200px";

                        const fileDiv = document.createElement("div");
                        fileDiv.classList.add("file-preview");
                        fileDiv.appendChild(img);

                        const removeBtn = document.createElement("div");
                        removeBtn.classList.add("file-remove");
                        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Remove';
                        removeBtn.onclick = function () {
                            let hfDeletedDocuments = document.getElementById("hf_deleted_documents");
                            hfDeletedDocuments.value = doc.file_path + ",";
                            fileDiv.remove();
                            const index = filesToUpload[categoryId].indexOf(file);
                            if (index > -1) filesToUpload[categoryId].splice(index, 1);
                        };
                        fileDiv.appendChild(removeBtn);

                        previewContainer.appendChild(fileDiv);
                        filesToUpload[categoryId].push(file);
                        
                    };
                    reader.readAsDataURL(file);
                }
            }
        }
    } catch (err) {
        console.error("Failed to read clipboard contents:", err);
    }
}

// Function to check if the upload button should be enabled




// Initialize category cards
documentCategories.forEach(category => {
    createCategoryCard(category);
});

        // Submit the form with all files
        uploadForm.addEventListener('submit', function(event) {
            
            event.preventDefault(); // Prevent the default form submission
            document.getElementById('loader').style.display = 'block'; // Show loader

            const formData = new FormData(uploadForm); // Capture all form fields
            
            // Get the clicked button value
            const submitButton = document.activeElement; // Get the button that triggered the event
            const buttonValue = submitButton ? encodeURIComponent(submitButton.value) : '';

            // Add all files from all categories to FormData
            for (const category in filesToUpload) {
                filesToUpload[category].forEach(file => {
                    //formData.append('files[]', file);
                    formData.append(`files[${category}][]`, file); // Include category in the key
                });
            }

            // Perform the AJAX request to submit the form data
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '', true);

            xhr.onload = function() {
                document.getElementById('loader').style.display = 'none'; // Hide loader
                if (xhr.status === 200) {
                    document.getElementById('alert_flag').value = '1';
                    document.getElementById('alert_message').value = 'Saved Successfully';

                    location.href="proposal.php?status_code=1024";
                } else {
                    alert('Error uploading files!');
                }
            };
            xhr.onerror = function() {
                document.getElementById('loader').style.display = 'none'; // Hide loader on error
                alert('Network error! Please try again.');
            };
            xhr.send(formData); // Send all files via AJAX
        });


document.querySelector('.close-fixed').addEventListener('click', function () {
        window.location.href = 'proposal.php';
    });
   

// Function to open the modal with the enlarged content
// function openModal(contentUrl, type) {
//     const modal = document.getElementById('previewModal');
//     const modalPreviewContainer = document.getElementById('modalPreviewContainer');
//     modal.style.display = 'flex';

//     // Clear any existing content in the modal
//     modalPreviewContainer.innerHTML = '';

//     if (type === 'image') {
//         const img = document.createElement('img');
//         img.src = contentUrl;
//         modalPreviewContainer.appendChild(img);
//     } else if (type === 'pdf') {
//         const pdfViewer = document.createElement('iframe');
//         pdfViewer.src = contentUrl;
//         pdfViewer.style.width = '100%';
//         pdfViewer.style.height = '500px'; // Adjust height as needed
//         modalPreviewContainer.appendChild(pdfViewer);
//     }
// }

// Close the modal when the close button is clicked
document.getElementById('closeModal').onclick = function() {
    const modal = document.getElementById('previewModal');
    modal.style.display = 'none';
};

function setAction(value) {
        document.getElementById('actionField').value = value; // Set hidden input value
}
function syncHiddenField(selectElement) {
        let hiddenField = document.getElementById('hidden_agent_request_number');
        if (hiddenField) {
            hiddenField.value = selectElement.value;
        }
    }
    
    function toggleSubmitButton() {
    var statusDropdown = document.getElementById('statusDropdown');
    var submitBtn = document.getElementById('submitBtn');
    
    // Check if a valid status is selected (not the default "-- Select Status --")
    if (statusDropdown.value) {
        // Enable the submit button if a status is selected
        submitBtn.disabled = false;
    } else {
        // Keep the submit button disabled if no status is selected
        submitBtn.disabled = true;
    }
}

// modal zoom/rotate 



function openModal(contentUrl, type) {
    const modal = document.getElementById('previewModal');
    const modalPreviewContainer = document.getElementById('modalPreviewContainer');
    modal.style.display = 'flex';

    // Reset states
    zoomLevel = 1;
    rotation = 0;
    currentType = type;

    // Clear existing content
    modalPreviewContainer.innerHTML = '';

    if (type === 'image') {
        const img = document.createElement('img');
        img.src = contentUrl;
        img.style.maxWidth = '90vw';
        img.style.maxHeight = '80vh';
        img.style.transition = 'transform 0.3s ease';
        img.style.transformOrigin = 'center';
        img.style.display = 'block';
        currentViewer = img;
        modalPreviewContainer.appendChild(img);
    } else if (type === 'pdf') {
        const iframe = document.createElement('iframe');
        iframe.src = contentUrl;
        iframe.style.transition = 'transform 0.3s ease';
        iframe.style.transformOrigin = 'center';
        iframe.style.border = 'none';
        currentViewer = iframe;
        modalPreviewContainer.appendChild(iframe);
    }

    updateTransform();
}

function updateTransform() {
    if (!currentViewer) return;

    // For images: no size changes, only scale + rotate
    if (currentType === 'image') {
        currentViewer.style.transform = `scale(${zoomLevel}) rotate(${rotation}deg)`;
    }
    // For PDFs (iframe): adjust size
    else if (currentType === 'pdf') {
        const baseWidth = 800;
        const baseHeight = 600;
        const isRotated = rotation % 180 !== 0;

        const width = isRotated ? baseHeight : baseWidth;
        const height = isRotated ? baseWidth : baseHeight;

        currentViewer.style.width = `${width * zoomLevel}px`;
        currentViewer.style.height = `${height * zoomLevel}px`;
        currentViewer.style.transform = `rotate(${rotation}deg)`;
    }
}


function zoomIn() {
    zoomLevel += 0.1;
    updateTransform();
}

function zoomOut() {
    zoomLevel = Math.max(0.1, zoomLevel - 0.1);
    updateTransform();
}

function rotate() {
    rotation = (rotation + 90) % 360;
    updateTransform();
}

function closeModal() {
    document.getElementById('previewModal').style.display = 'none';
    document.getElementById('modalPreviewContainer').innerHTML = '';
    currentViewer = null;
}

</script>
<style>
    .disabled-div {
        pointer-events: none;  /* Prevents clicks */
        opacity: 0.6;          /* Makes it look disabled */
        background: #f8f9fa;   /* Light grey background */
    }
</style>