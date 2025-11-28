<?php
include_once("db_config.php");
include_once("admin_authentication.php"); // Ensure admin is logged in

$admin_email = $_SESSION['admin_email'];
$fullname = NULL;
$role = NULL;
$password = NULL;
$token = NULL;
$status = NULL;
$mobile = NULL;

$stmt = $con->prepare("CALL Registration_Select(?, ?, ?, ?, ?, ?,?)");
if ($stmt === false) {
    die("Prepare failed: " . $con->error);
}
$stmt->bind_param("ssssssi", $fullname, $admin_email, $role, $status, $password, $token, $mobile);
$result =    $stmt->execute();
if ($result) {
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $fullname = $row['fullname'];
        $role = $row['role'];
        $status = $row['status'];
    }
    $res->free();
    flush_stored_results($con);
} else {
    die("Execution failed: " . $stmt->error);
}
ob_start();
?>
<h3 class="mb-4">Welcome, <?= $fullname ?> </h3>

<div class="container-fluid">

    <!-- Top Stats -->
    <div class="row g-4">

        <!-- Users -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 5px solid #0d9488;">
                <div class="card-body">
                    <h6 class="text-muted">Total Users</h6>
                    <h3 class="fw-bold">1,284</h3>
                    <p class="text-success small"><i class="fa fa-arrow-up"></i> +12% this month</p>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 5px solid #d4af37;">
                <div class="card-body">
                    <h6 class="text-muted">Total Products</h6>
                    <h3 class="fw-bold">542</h3>
                    <p class="text-danger small"><i class="fa fa-arrow-down"></i> -3% this week</p>
                </div>
            </div>
        </div>

        <!-- Orders -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 5px solid #0f766e;">
                <div class="card-body">
                    <h6 class="text-muted">New Orders</h6>
                    <h3 class="fw-bold">73</h3>
                    <p class="text-success small"><i class="fa fa-arrow-up"></i> +8% today</p>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0" style="border-left: 5px solid #0d9488;">
                <div class="card-body">
                    <h6 class="text-muted">Revenue</h6>
                    <h3 class="fw-bold">₹ 1,52,000</h3>
                    <p class="text-success small"><i class="fa fa-arrow-up"></i> +18% growth</p>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Row -->
    <div class="row mt-4 g-4">

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-chart-line me-2"></i>Sales Overview</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-user-group me-2"></i>User Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="pieChart" height="200"></canvas>
                </div>
            </div>
        </div>

    </div>

</div>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sales Line Chart
    const ctx1 = document.getElementById('salesChart');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Sales (₹)',
                data: [12000, 15000, 18000, 22000, 19000, 25000],
                borderWidth: 3,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13,148,136,0.2)',
                tension: 0.3
            }]
        }
    });

    // Pie Chart
    const ctx2 = document.getElementById('pieChart');
    new Chart(ctx2, {
        type: 'pie',
        data: {
            labels: ['Active Users', 'New Users', 'Blocked Users'],
            datasets: [{
                data: [70, 20, 10],
                backgroundColor: ['#0d9488', '#d4af37', '#e11d48']
            }]
        }
    });
</script>
<?php
$content_admin = ob_get_clean();
include "admin_layout.php";
