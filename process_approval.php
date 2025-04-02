<?php
session_start();

// Check if the user is an approver
if ($_SESSION['user_role'] !== 'approver') {
    header("Location: dashboard.php");
    exit;
}

// Database connection
require_once 'config.php';

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
        approved_category = '$category'
        WHERE id = '$proposal_id'";

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
                echo "Proposal and comment updated successfully! <a href='proposal.php'>Go Back</a>";

                header("Location: proposal.php");
                exit(); // Ensure no further code is executed after the redirect
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
?>