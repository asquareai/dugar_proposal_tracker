<?php
include 'config.php'; // Database connection
session_start(); // Start the session

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $proposal_mode = $_POST['hf_proposal_mode']; // NEW or OPEN
    $proposal_id = isset($_POST['hf_proposal_id']) ? (int)$_POST['hf_proposal_id'] : null;
    $deleted_files = isset($_POST['hf_deleted_documents']) ? explode(',', $_POST['hf_deleted_documents']) : [];

    $proposal_status = isset($_POST['proposal_status']) ? (int)$_POST['proposal_status'] : null;
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
        $comment_sql = "INSERT INTO proposal_comments (proposal_id, comment, created_at, user_id) 
                        VALUES ('$proposal_id', '$comments', NOW(), '$created_by')";
        mysqli_query($conn, $comment_sql);
    }

    $_SESSION['alert_message'] = "Proposal saved successfully!";
    $_SESSION['alert_type'] = "success";
    header("Location: proposals.php");
    exit;
}

?>