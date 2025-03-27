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
$query = "SELECT * FROM proposals";
$result = mysqli_query($conn, $query);
?>

<!-- Include Bootstrap & DataTables CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css">

<div class="container-fluid mt-4"> <!-- Full width -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-primary" onclick="toggleForm()">Create New Proposal</button>
    </div>

    <!-- Proposal Form (Initially Hidden) -->
    <div id="proposalForm" class="card p-3 mb-4" style="display: none;">
        <h4>Create New Proposal</h4>
        <form method="POST">
            <div class="mb-2">
                <label>Title</label>
                <input type="text" class="form-control" name="title" required>
            </div>
            <div class="mb-2">
                <label>Client Name</label>
                <input type="text" class="form-control" name="client_name" required>
            </div>
            <div class="mb-2">
                <label>Amount</label>
                <input type="number" class="form-control" name="amount" required>
            </div>
            <button type="submit" name="create_proposal" class="btn btn-success">Save Proposal</button>
        </form>
    </div>

    <?php if (mysqli_num_rows($result) > 0) { ?>
        <div class="table-responsive w-100" style="max-width: 100%;">  <!-- Full width -->
            <table id="proposalTable" class="table table-hover table-striped table-bordered nowrap" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?php echo $row['title']; ?></td>
                            <td><?php echo $row['client_name']; ?></td>
                            <td><?php echo number_format($row['amount'], 2); ?></td>
                            <td><span class="badge bg-<?php echo ($row['status'] == 'Pending' ? 'warning' : ($row['status'] == 'Approved' ? 'success' : 'danger')); ?>">
                                <?php echo $row['status']; ?>
                            </span></td>
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

    function toggleForm() {
        let form = document.getElementById('proposalForm');
        form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
    }
</script>

<style>
    /* Reduce font size and padding for overall content */
    body {
        font-size: 12px; /* Slightly smaller text */
    }

    /* Ensure full-width table on larger screens */
    .container {
        max-width: 100%;
        padding: 8px;
    }

    /* Compact table rows */
    .table th, .table td {
        padding: 3px 6px; /* Further reduce padding */
        font-size: 11px;  /* Decrease font size */
        vertical-align: middle; /* Align text properly */
    }

    /* Reduce button height */
    .btn-sm {
        padding: 1px 6px; /* Adjust padding */
        font-size: 10px;  /* Reduce font size */
        line-height: 1;   /* Adjust line height */
    }
    /* Table border styling */
    .table {
        border: 1px solidrgb(77, 80, 84); /* Light gray border */
    }

    .table th, .table td {
        border: 1px solidrgb(106, 113, 120)!important; /* Slightly darker gray for cell borders */
    }

    /* Add subtle shadow for better appearance */
    .table-bordered {
        border-radius: 5px;
        overflow: hidden;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    }

    /* Alternate row styles */
    .table tbody tr:nth-child(odd) td {
        background-color: rgb(207, 230, 255) !important; /* Light gray */
    }

    .table tbody tr:nth-child(even) td {
        background-color: #ffffff !important; /* White */
    }

    /* Further reduce row height and spacing on desktops */
    @media (min-width: 1024px) {
        .table th, .table td {
            padding: 2px 5px;
            font-size: 10px;
        }
        
        .btn-sm {
            padding: 1px 5px;
            font-size: 9px;
        }
    }
</style>
