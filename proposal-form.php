<?php
// Assuming this page is opened in an iframe and needs a back link
echo '<a href="proposal_list.php" class="btn btn-secondary">Go Back to Proposal List</a>';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Proposal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
       .container {
            width: 100vw; /* Full viewport width */
            max-width: 100%; /* Override Bootstrap's max-width */
            height: 100vh; /* Full viewport height */
            padding: 20px;
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


    </style>
</head>
<body>
    <div class="container mt-4">
        <h4 class="mb-3">New Request – <em>Basic Information</em></h4>
        <form action="submit_proposal.php" method="POST">
            <div class="row">
                <!-- Left Side: Applicant Details -->
                <div class="col-md-6">
                    <h5>Applicant Details</h5>
                    <div class="form-group">
                        <label>Agent Request Number</label>
                        <input type="text" class="form-control" value="DF-MSK-001" readonly>
                    </div>
                    <div class="form-group">
                        <label>Borrower Name</label>
                        <input type="text" class="form-control" placeholder="Borrower name" required>
                    </div>
                    <div class="form-group">
                        <label>Initials</label>
                        <input type="text" class="form-control" placeholder="Only alphabets, space, or periods" required>
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="text" class="form-control" placeholder="10-digit number" required>
                    </div>
                    <div class="form-group">
                        <label>Email ID</label>
                        <input type="email" class="form-control" placeholder="Borrower’s email id" required>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <select class="form-control">
                            <option value="">-- Select City --</option>
                            <option value="city1">City 1</option>
                            <option value="city2">City 2</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <input type="text" class="form-control" placeholder="Name of the vehicle" required>
                    </div>
                    <div class="form-group">
                        <label>Model</label>
                        <input type="text" class="form-control" placeholder="Manufacturing year" required>
                    </div>
                    <div class="form-group">
                        <label>Loan Amount</label>
                        <input type="text" class="form-control" placeholder="Requested loan amount" required>
                    </div>
                    <div class="form-group">
                        <label>Comments</label>
                        <textarea class="form-control" placeholder="Requested loan amount"></textarea>
                    </div>
                </div>
                
                <!-- Right Side: Co-Applicant Details -->
                <div class="col-md-6">
                    <h5>Co-Applicant Details</h5>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" placeholder="Co-applicant Name">
                    </div>
                    <div class="form-group">
                        <label>Mobile Number</label>
                        <input type="text" class="form-control" placeholder="Co-applicant contact number">
                    </div>
                    <div class="form-group">
                        <label>Relationship</label>
                        <select class="form-control">
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
                            <!-- More comments dynamically added here -->
                        </div>

                        <!-- Fixed New Comment Input -->
                        <div class="new-comment">
                            <label>New Comment</label>
                            <textarea class="form-control" placeholder="Enter your comment"></textarea>
                            <button class="btn btn-primary mt-2">Add Comment</button>
                        </div>
                    </div>

                </div>

            </div>
            <div class="container mt-4">
            <h5>Upload Documents</h5>
            <div class="row">
                    <!-- Document Upload Card Example -->
                <div class="col-md-4">
                    <div class="card" style="height: 250px;">
                        <div class="card-header">Aadhar Card</div>
                        <div class="card-body">
                            <input type="file" class="form-control" multiple accept="image/*,application/pdf" onchange="previewFiles(event, 'aadhar-preview', 'aadhar-no-files')">
                            <div class="preview-container mt-2 d-flex flex-wrap" id="aadhar-preview" style="gap: 8px;">
                                <p class="text-muted" id="aadhar-no-files">No images uploaded</p>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Repeat similar cards for other document types -->
            </div>
             <!-- Modal for Enlarged Image/PDF Preview -->
                <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="modalImage" class="img-fluid d-none">
                                <iframe id="modalPdf" class="d-none" style="width: 100%; height: 500px;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Save & Proceed Next</button>
            </div>
        </form>
    </div>
</body>
</html>

<script>
function previewFiles(event, previewId, noFilesId) {
    let files = event.target.files;
    let previewContainer = document.getElementById(previewId);
    let noFilesMessage = document.getElementById(noFilesId);

    if (files.length === 0 && previewContainer.children.length === 0) {
        noFilesMessage.style.display = "block";
        return;
    }

    noFilesMessage.style.display = "none"; 

    Array.from(files).forEach((file, index) => {
        let div = document.createElement('div');
        div.className = "position-relative d-inline-block";

        let delBtn = document.createElement('button');
        delBtn.innerHTML = "&times;";
        delBtn.className = "btn btn-danger btn-sm position-absolute top-0 end-0";
        delBtn.onclick = function() {
            div.remove();
            if (previewContainer.children.length === 0) {
                noFilesMessage.style.display = "block";
            }
        };

        if (file.type.startsWith('image/')) {
            let reader = new FileReader();
            reader.onload = function(e) {
                let img = document.createElement('img');
                img.src = e.target.result;
                img.className = "img-thumbnail";
                img.style.width = "100px";
                img.style.height = "100px";
                img.style.cursor = "pointer";
                img.onclick = function() {
                    document.getElementById('modalImage').src = e.target.result;
                    document.getElementById('modalImage').classList.remove('d-none');
                    document.getElementById('modalPdf').classList.add('d-none');
                    new bootstrap.Modal(document.getElementById('previewModal')).show();
                };
                div.appendChild(img);
            };
            reader.readAsDataURL(file);
        } else if (file.type === "application/pdf") {
            let blobUrl = URL.createObjectURL(file);
            let link = document.createElement('a');
            link.href = blobUrl;
            link.className = "btn btn-outline-primary d-block text-center";
            link.style.width = "100px";
            link.style.height = "100px";
            link.innerHTML = "📄 PDF";
            link.onclick = function(event) {
                event.preventDefault();
                document.getElementById('modalPdf').src = blobUrl;
                document.getElementById('modalPdf').classList.remove('d-none');
                document.getElementById('modalImage').classList.add('d-none');
                new bootstrap.Modal(document.getElementById('previewModal')).show();
            };
            div.appendChild(link);
        }

        div.appendChild(delBtn);
        previewContainer.appendChild(div);
    });

    event.target.value = ''; // Clear the input so the same file can be re-uploaded if needed
}
</script>