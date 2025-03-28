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

    function proposalForm() {
        document.location.href="proposal-form.php";
    }
</script>

