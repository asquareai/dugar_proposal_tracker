<?php
include 'config.php'; // Database connection
session_start(); // Start the session
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input values
    $borrower_name = mysqli_real_escape_string($conn, $_POST['borrower_name']);
    $initials = mysqli_real_escape_string($conn, $_POST['initials']);
    $mobile_number = mysqli_real_escape_string($conn, $_POST['mobile_number']);
    $email_id = mysqli_real_escape_string($conn, $_POST['email_id']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $vehicle_name = mysqli_real_escape_string($conn, $_POST['vehicle_name']);
    $model = mysqli_real_escape_string($conn, $_POST['vehicle_model']);
    $loan_amount = mysqli_real_escape_string($conn, $_POST['loan_amount']);
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);
    
    // Co-applicant details
    $co_name = mysqli_real_escape_string($conn, $_POST['coapplicant_name']);
    $co_mobile = mysqli_real_escape_string($conn, $_POST['coapplicant_mobile']);
    $co_relationship = mysqli_real_escape_string($conn, $_POST['coapplicant_relationship']);

    // Capture the agent (user who created it)
    $created_by = $_SESSION['user_id']; // Assuming user is logged in

    // Insert into proposal table
    $sql = "INSERT INTO proposals 
            (borrower_name, initials, mobile_number, email, city, vehicle_name, model, loan_amount,  co_applicant_name, co_applicant_mobile, co_applicant_relationship, created_by, status)
            VALUES 
            ('$borrower_name', '$initials', '$mobile_number', '$email_id', '$city', '$vehicle_name', '$model', '$loan_amount','$co_name', '$co_mobile', '$co_relationship', '$created_by', 1)";

    if (mysqli_query($conn, $sql)) {
        $proposal_id = mysqli_insert_id($conn); // Get last inserted proposal ID
        // Handle file uploads
        if (isset($_FILES['files'])) {
            $uploadDir = "uploads/";  
            $uploadedFiles = [];
    
            // Loop through each uploaded file
            foreach ($_FILES['files']['name'] as $category => $files) { 
                foreach ($files as $key => $fileName) {
                    if ($_FILES['files']['error'][$category][$key] === 0) {
                        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION); // Get file extension
                        $baseName = pathinfo($fileName, PATHINFO_FILENAME); // Get original name (without extension)
                        $uniqueName = $baseName . '_' . time() . '_' . uniqid() . '.' . $fileExt; // Append timestamp & unique ID
                        $uploadFile = $uploadDir . $uniqueName;

                        if (move_uploaded_file($_FILES['files']['tmp_name'][$category][$key], $uploadFile)) {
                            $uploadedFiles[] = "<a href='$uploadFile' target='_blank'>$fileName</a>";

                            $document_type = ($fileExt === 'pdf') ? 'pdf' : 'image'; // Determine type based on extension
                            $doc_sql = "INSERT INTO proposal_documents (proposal_id, document_type, file_path, uploaded_at, created_by, category_id) 
                                        VALUES ('$proposal_id', '$document_type', '$uploadFile', NOW(), '$created_by', '$category')";

                            // Log query to a file
                            file_put_contents("log.txt", date("Y-m-d H:i:s") . " - document insert: $doc_sql\n", FILE_APPEND);

                            // Execute SQL
                            if (!mysqli_query($conn, $doc_sql)) {
                                file_put_contents("log.txt", date("Y-m-d H:i:s") . " - SQL Error: " . mysqli_error($conn) . "\n", FILE_APPEND);
                                echo "<p style='color:red;'>❌ Error inserting document into database: $fileName!</p>";
                            }
                        } else {
                            echo "<p style='color:red;'>❌ Error moving file: $fileName!</p>";
                        }
                    } else {
                        echo "<p style='color:red;'>❌ Error uploading file: $fileName!</p>";
                    }
                }
            }

    
            if (!empty($uploadedFiles)) {
                echo "<p style='color:green;'>✅ Files uploaded successfully: </p>";
                foreach ($uploadedFiles as $fileLink) {
                    echo "<p>$fileLink</p>";
                }
            }
        }
        
        
        

        // Save new comment (if any)
        if (!empty($_POST['comments'])) {
            $new_comment = mysqli_real_escape_string($conn, $_POST['comments']);
            $comment_sql = "INSERT INTO proposal_comments (proposal_id, comment,created_at, user_id) VALUES ('$proposal_id', '$new_comment',NOW(), '$created_by')";
            mysqli_query($conn, $comment_sql);
        }

        echo "Proposal saved successfully!";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Proposal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap JS Bundle (including Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <style>
       .container {
            width: 100vw; /* Full viewport width */
            max-width: 100%; /* Override Bootstrap's max-width */
            height: 100vh; /* Full viewport height */
            padding: 20px;
            padding-top: 0px;
            margin-top: 0px;
            border-radius: 0; /* Remove rounded corners */
        }


        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .form-group label {
            width: 40%; /* Adjust width as needed */
            text-align: right; /* Align text to the right */
            font-weight: normal; /* Make text normal (not bold) */
            padding-right: 10px; /* Add some space between label and input */
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 60%;
        }
        .comments-box {
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            background: #fff;
            max-height: 300px; /* Adjust height as needed */
        }

        .comments-list {
            flex-grow: 1;
            overflow-y: auto; /* Only previous comments are scrollable */
            max-height: 200px;
            padding-right: 5px;
        }

        .comment-entry {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }

        /* New comment section remains at the bottom */
        .new-comment {
            margin-top: 10px;
        }
        .close-fixed {
            position: fixed;
            top: 10px;
            right: 15px;
            background: red;
            color: white;
            border: none;
            font-size: 20px;
            padding: 5px 10px;
            cursor: pointer;
            z-index: 9999;
        }
       /* Container to display cards in a row */
.category-container {
    display: flex;
    overflow-x: auto;
    width: 100%;
    padding: 10px 0; /* Padding for top and bottom */
}

/* Style for individual category cards */
.category-card {
    flex: 0 0 auto;  /* Ensures the card does not grow or shrink */
    border: 1px solid #e0e0e0; /* Light border for a modern look */
    margin: 10px;
    width: 100%;
    box-sizing: border-box; /* Include padding and borders in the width */
    border-radius: 8px; /* Rounded corners for a modern touch */
    background-color: #ffffff; /* White background */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); /* Soft shadow for elevation effect */
    transition: all 0.3s ease; /* Smooth transition for hover effects */
}

/* Hover effect for card */
.category-card:hover {
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15); /* Increase shadow on hover */
    transform: translateY(-5px); /* Slight lift effect on hover */
}

/* Header style for category cards */
.category-card h3 {
    font-size: 16px; /* Smaller font for header */
    font-weight: 600; /* Medium bold text */
    margin-top: 0;
    padding: 10px;
    background-color: #f2f2f2; /* Light background for header */
    border-radius: 6px;
    color: #333; /* Dark text color */
    text-align: center; /* Center align the title */
}

/* File preview style */
.file-preview {
    display: inline-block;
    margin: 10px;
}

/* Preview container styles */
.preview-container {
    margin-top: 15px;
}

/* Image preview style */
.preview-container img {
    width:200px;
    height: auto;
    margin: 5px;
    border-radius: 4px; /* Rounded corners for images */
}

/* Remove button style */
.preview-container .file-remove {
    cursor: pointer;
    font-size: 12px;
    color: #ff4d4d; /* Red color for remove button */
    margin-top: 5px;
    display: block;
    text-align: center;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

/* Change remove button background on hover */
.preview-container .file-remove:hover {
    background-color: #ffcccc; /* Light red background on hover */
}

/* PDF icon style */
.preview-container .pdf-icon {
    width: 80px;
    height: 120px;
    background: url('assets/images/pdf-icon.png') no-repeat center center;
    background-size: cover;
    margin: 5px;
    border-radius: 4px; /* Rounded corners for PDF icon */
}
 /* Modal Styles */
 #previewModal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        display: none;
        justify-content: center;
        align-items: center;
    }
    #modalContent {
        position: relative;
        background-color: white;
        padding: 20px;
        max-width: 90%;
        max-height: 90%;
        overflow: auto;
    }
    #modalPreviewContainer img {
        max-width: 100%;
        max-height: 100%;
    }
    #modalPreviewContainer .pdf-icon {
        width: 100%;
        height: auto;
    }

    </style>
</head>
<body>
    
    <div class="container mt-4">
    <button type="button" class="close-fixed" onclick="window.location.href='proposal.php';">
        <i class="bi bi-x-circle"></i>
    </button>        <h4 class="mb-3">Create New Proposal </em></h4>
    <form action="" method="POST" id="uploadForm" enctype="multipart/form-data">
    <div class="row">
        <!-- Left Side: Applicant Details -->
        <div class="col-md-6">
            <h5>Applicant Details</h5>
            <div class="form-group">
                <label>Agent Request Number</label>
                <input type="text" class="form-control" name="agent_request_number" value="DF-MSK-001" readonly>
            </div>
            <div class="form-group">
                <label>Borrower Name</label>
                <input type="text" class="form-control" name="borrower_name" placeholder="Borrower name" value="Venkat" required>
            </div>
            <div class="form-group">
                <label>Initials</label>
                <input type="text" class="form-control" name="initials" placeholder="Only alphabets, space, or periods" value="M" required>
            </div>
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" class="form-control" name="mobile_number" placeholder="10-digit number" required value="9894295995">
            </div>
            <div class="form-group">
                <label>Email ID</label>
                <input type="email" class="form-control" name="email_id" placeholder="Borrower’s email id" required value="mvk.venkatesan@gmail.com">
            </div>
            <div class="form-group">
                <label>City</label>
                <select class="form-control" name="city">
                    <option value="">-- Select City --</option>
                    <option value="city1">City 1</option>
                    <option value="city2">City 2</option>
                </select>
            </div>
            <div class="form-group">
                <label>Vehicle Name</label>
                <input type="text" class="form-control" name="vehicle_name" placeholder="Name of the vehicle" required value="Honda">
            </div>
            <div class="form-group">
                <label>Model</label>
                <input type="text" class="form-control" name="vehicle_model" placeholder="Manufacturing year" required value="City">
            </div>
            <div class="form-group">
                <label>Loan Amount</label>
                <input type="text" class="form-control" name="loan_amount" placeholder="Requested loan amount" required value="150000">
            </div>
        </div>
        
        <!-- Right Side: Co-Applicant Details -->
        <div class="col-md-6">
            <h5>Co-Applicant Details</h5>
            <div class="form-group">
                <label>Name</label>
                <input type="text" class="form-control" name="coapplicant_name" placeholder="Co-applicant Name" value="co - app ">
            </div>
            <div class="form-group">
                <label>Mobile Number</label>
                <input type="text" class="form-control" name="coapplicant_mobile" placeholder="Co-applicant contact number" value="9003171903">
            </div>
            <div class="form-group">
                <label>Relationship</label>
                <select class="form-control" name="coapplicant_relationship">
                    <option value="">-- Relationship with borrower --</option>
                    <option value="spouse">Spouse</option>
                    <option value="parent">Parent</option>
                    <option value="sibling">Sibling</option>
                </select>
            </div>

            <!-- Comments Section -->
            <h5 class="mt-4">Comments</h5>
            <div class="comments-box">
                <!-- Scrollable Comments List -->
                <div class="comments-list">
                    <div class="comment-entry">
                        <small><strong>User1</strong> - 2024-03-28 10:15 AM</small>
                        <p>First comment text goes here...</p>
                    </div>
                    <div class="comment-entry">
                        <small><strong>User2</strong> - 2024-03-28 10:20 AM</small>
                        <p>Second comment text goes here...</p>
                    </div>
                </div>

                <!-- Fixed New Comment Input -->
                <div class="new-comment">
                    <label>New Comment</label>
                    <textarea class="form-control" name="comments" placeholder="Enter your comment" value="tested">tested</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Documents Section -->
    <div class="container mt-4">
        <h5>Upload Documents</h5>
        <div class="row">
            
            <!-- Aadhar Card Upload 
            <div class="card" style="min-height: 250px;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Aadhar Card</span>
                    <span class="btn btn-sm btn-secondary" onclick="pasteFromClipboard('aadhar-preview', 'aadhar-no-files')">📋 Paste Image</span>
                </div>
                <div class="card-body">
                    
                     <input type="file" name="files[]" id="fileInput" accept="image/*,application/pdf" required>
                    <div class="preview-container mt-2 d-flex flex-wrap" id="aadhar-preview" style="gap: 8px;">
                        <p class="text-muted" id="aadhar-no-files">No images uploaded</p>
                    </div>
                </div>
            </div> -->

            <div id="documentCategories"></div>
        </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Save & Proceed Next</button>
    </div>
</form>
    </div>
    <!-- Modal to display enlarged content -->
    <div id="previewModal" style="display:none;">
        <div id="modalContent">
            <span id="closeModal" style="cursor: pointer; font-size: 20px; color: red;">&times;</span>
            <div id="modalPreviewContainer"></div>
        </div>
    </div>
</body>
</html>

<script>
        const documentCategories = [
            { id: '1', name: 'Aadhar Card' },
            { id: '2', name: 'Driving License' },
            { id: '3', name: 'Voter ID' }
        ];

        const filesToUpload = {}; // Object to store files for each category

        const uploadForm = document.getElementById('uploadForm');
        const uploadBtn = document.getElementById('uploadBtn');
        const documentCategoriesContainer = document.getElementById('documentCategories');

        // Function to create a category card with file input and preview section
        function createCategoryCard(category) {
            const card = document.createElement('div');
            card.classList.add('category-card');
            card.id = category.id;

            const title = document.createElement('h3');
            title.textContent = category.name;
            card.appendChild(title);

            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*,application/pdf';
            fileInput.multiple = true;
            fileInput.addEventListener('change', function() {
                handleFileSelect(category.id, fileInput);
            });
            card.appendChild(fileInput);

            const pasteButton = document.createElement('span');
            pasteButton.textContent = 'Paste Image';
            pasteButton.onclick = function() {
                // This is a fix: trigger paste event on the document
                triggerPaste(category.id);
            };
            card.appendChild(pasteButton);

            const previewContainer = document.createElement('div');
            previewContainer.classList.add('preview-container');
            previewContainer.id = `${category.id}-preview`;
            card.appendChild(previewContainer);

            documentCategoriesContainer.appendChild(card);
        }

        // Function to handle file selection and display previews
        function handleFileSelect(categoryId, input) {
            const files = input.files;
            const previewContainer = document.getElementById(`${categoryId}-preview`);

            // Add files to filesToUpload object
            if (!filesToUpload[categoryId]) {
                filesToUpload[categoryId] = [];
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const fileDiv = document.createElement('div');
                fileDiv.classList.add('file-preview');
                
                let preview;
                if (file.type.startsWith('image/')) {
                    preview = document.createElement('img');
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else if (file.type === 'application/pdf') {
                    preview = document.createElement('div');
                    preview.classList.add('pdf-icon');
                }

                fileDiv.appendChild(preview);

                // Add remove button for the file
                const removeBtn = document.createElement('div');
                removeBtn.classList.add('file-remove');
                removeBtn.textContent = 'Remove';
                removeBtn.onclick = function() {
                    fileDiv.remove();
                    // Remove from filesToUpload object
                    const index = filesToUpload[categoryId].indexOf(file);
                    if (index > -1) filesToUpload[categoryId].splice(index, 1);
                };
                fileDiv.appendChild(removeBtn);

                previewContainer.appendChild(fileDiv);

                // Add to filesToUpload object
                filesToUpload[categoryId].push(file);
            }

            // Enable the upload button if there are any files in any category
            checkEnableUploadBtn();
        }

        // Function to handle paste events for images
        // Function to handle paste events for images
async function triggerPaste(categoryId) {
   
try{
      // Request clipboard permissions
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
                removeBtn.textContent = "Remove";
                removeBtn.onclick = function () {
                    fileDiv.remove();
                    const index = filesToUpload[categoryId].indexOf(file);
                    if (index > -1) filesToUpload[categoryId].splice(index, 1);
                };
                fileDiv.appendChild(removeBtn);

                previewContainer.appendChild(fileDiv);
                filesToUpload[categoryId].push(file);
                checkEnableUploadBtn();
            };
            reader.readAsDataURL(file);
        }
    }
}
} catch (err) {
console.error("Failed to read clipboard contents:", err);
}
     
   
}


        // Check if the upload button should be enabled
        function checkEnableUploadBtn() {
            let hasFiles = false;
            for (const category in filesToUpload) {
                if (filesToUpload[category].length > 0) {
                    hasFiles = true;
                    break;
                }
            }
            uploadBtn.disabled = !hasFiles;
        }

        // Initialize category cards
        documentCategories.forEach(category => {
            createCategoryCard(category);
        });

        // Submit the form with all files
        uploadForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData();

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
                if (xhr.status === 200) {
                    // Successfully uploaded
                    alert('Files uploaded successfully!');
                    location.reload(); // Reload page to clear the form
                } else {
                    alert('Error uploading files!');
                }
            };

            xhr.send(formData); // Send all files via AJAX
        });


document.querySelector('.close-fixed').addEventListener('click', function () {
        window.location.href = 'proposal.php';
    });
   
</script>