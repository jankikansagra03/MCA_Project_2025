<?php
include_once("db_config.php");
include_once("admin_authentication.php"); // Ensure admin is logged in

$admin_email = $_SESSION['admin_email'];

// =======================
// FETCH ADMIN DETAILS
// =======================
$admin_data = null;
$fullname = "Admin";
$role = "Administrator";
$status = "Active";

$stmt = $con->prepare("CALL Registration_Select(NULL, ?, NULL, NULL, NULL, NULL, NULL)");
if ($stmt === false) {
    die("Prepare failed: " . $con->error);
}

$stmt->bind_param("s", $admin_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $admin_data = $result->fetch_assoc();
    $fullname = $admin_data['fullname'];
    $role = $admin_data['role'];
    $status = $admin_data['status'];
}

$result->free();
$stmt->close();

// Flush stored results
while ($con->more_results()) {
    $con->next_result();
    if ($res = $con->store_result()) {
        $res->free();
    }
}

// =======================
// FETCH STATISTICS
// =======================

// 1. TOTAL USERS COUNT
$total_users = 0;
$prev_month_users = 0;

$users_query = "SELECT COUNT(*) as count FROM registration WHERE role = 'user'";
$users_result = $con->query($users_query);
if ($users_result) {
    $users_row = $users_result->fetch_assoc();
    $total_users = $users_row['count'];
    $users_result->free();
}

// Current month users
$current_month_users_query = "SELECT COUNT(*) as count FROM registration 
                               WHERE role = 'user' 
                               AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
$current_month_users_result = $con->query($current_month_users_query);
$current_month_users = 0;
if ($current_month_users_result) {
    $current_month_users_row = $current_month_users_result->fetch_assoc();
    $current_month_users = $current_month_users_row['count'];
    $current_month_users_result->free();
}

// Previous month users
$prev_users_query = "SELECT COUNT(*) as count FROM registration 
                     WHERE role = 'user' 
                     AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')";
$prev_users_result = $con->query($prev_users_query);
if ($prev_users_result) {
    $prev_users_row = $prev_users_result->fetch_assoc();
    $prev_month_users = $prev_users_row['count'];
    $prev_users_result->free();
}

$user_growth = $prev_month_users > 0 ? round((($current_month_users - $prev_month_users) / $prev_month_users) * 100, 1) : 0;

// 2. TOTAL PRODUCTS COUNT
$total_products = 0;
$products_query = "SELECT COUNT(*) as count FROM products";
$products_result = $con->query($products_query);
if ($products_result) {
    $products_row = $products_result->fetch_assoc();
    $total_products = $products_row['count'];
    $products_result->free();
}

// Products added this week
$week_products_query = "SELECT COUNT(*) as count FROM products 
                        WHERE YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)";
$week_products_result = $con->query($week_products_query);
$week_products = 0;
if ($week_products_result) {
    $week_products_row = $week_products_result->fetch_assoc();
    $week_products = $week_products_row['count'];
    $week_products_result->free();
}

// 3. NEW ORDERS COUNT (Pending/Processing)
$new_orders = 0;
$orders_query = "SELECT COUNT(*) as count FROM orders 
                 WHERE order_status IN ('Pending', 'Processing')";
$orders_result = $con->query($orders_query);
if ($orders_result) {
    $orders_row = $orders_result->fetch_assoc();
    $new_orders = $orders_row['count'];
    $orders_result->free();
}

// Orders today
$today_orders_query = "SELECT COUNT(*) as count FROM orders 
                       WHERE DATE(created_at) = CURDATE()";
$today_orders_result = $con->query($today_orders_query);
$today_orders = 0;
if ($today_orders_result) {
    $today_orders_row = $today_orders_result->fetch_assoc();
    $today_orders = $today_orders_row['count'];
    $today_orders_result->free();
}

// 4. TOTAL REVENUE
$total_revenue = 0;
$revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
                  WHERE order_status NOT IN ('Cancelled', 'Returned')";
$revenue_result = $con->query($revenue_query);
if ($revenue_result) {
    $revenue_row = $revenue_result->fetch_assoc();
    $total_revenue = $revenue_row['revenue'];
    $revenue_result->free();
}

// Current month revenue
$current_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
                          WHERE order_status NOT IN ('Cancelled', 'Returned')
                          AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')";
$current_revenue_result = $con->query($current_revenue_query);
$current_revenue = 0;
if ($current_revenue_result) {
    $current_revenue_row = $current_revenue_result->fetch_assoc();
    $current_revenue = $current_revenue_row['revenue'];
    $current_revenue_result->free();
}

// Previous month revenue
$prev_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
                       WHERE order_status NOT IN ('Cancelled', 'Returned')
                       AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')";
$prev_revenue_result = $con->query($prev_revenue_query);
$prev_revenue = 0;
if ($prev_revenue_result) {
    $prev_revenue_row = $prev_revenue_result->fetch_assoc();
    $prev_revenue = $prev_revenue_row['revenue'];
    $prev_revenue_result->free();
}

$revenue_growth = $prev_revenue > 0 ? round((($current_revenue - $prev_revenue) / $prev_revenue) * 100, 1) : 0;

// =======================
// MONTHLY SALES DATA FOR CHART - FINAL FIX
// =======================
$monthly_sales = [];
$months_labels = [];

$sales_query = "SELECT 
                    DATE_FORMAT(MIN(created_at), '%b') as month_name,
                    COALESCE(SUM(total_amount), 0) as total_sales
                FROM orders 
                WHERE order_status NOT IN ('Cancelled', 'Returned')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY YEAR(created_at), MONTH(created_at)
                ORDER BY YEAR(created_at) ASC, MONTH(created_at) ASC";

$sales_result = $con->query($sales_query);

if ($sales_result && $sales_result->num_rows > 0) {
    while ($row = $sales_result->fetch_assoc()) {
        $months_labels[] = $row['month_name'];
        $monthly_sales[] = (float)$row['total_sales'];
    }
    $sales_result->free();
} else {
    // Fallback: Generate last 6 months labels with zero sales
    for ($i = 5; $i >= 0; $i--) {
        $timestamp = strtotime("-$i months");
        $months_labels[] = date('M', $timestamp);
        $monthly_sales[] = 0;
    }
}

// =======================
// USER DISTRIBUTION DATA FOR PIE CHART
// =======================
$active_users = 0;
$inactive_users = 0;
$blocked_users = 0;

$active_query = "SELECT COUNT(*) as count FROM registration WHERE role = 'user' AND status = 'Active'";
$active_result = $con->query($active_query);
if ($active_result) {
    $active_row = $active_result->fetch_assoc();
    $active_users = $active_row['count'];
    $active_result->free();
}

$inactive_query = "SELECT COUNT(*) as count FROM registration WHERE role = 'user' AND status = 'Inactive'";
$inactive_result = $con->query($inactive_query);
if ($inactive_result) {
    $inactive_row = $inactive_result->fetch_assoc();
    $inactive_users = $inactive_row['count'];
    $inactive_result->free();
}

$blocked_query = "SELECT COUNT(*) as count FROM registration WHERE role = 'user' AND status = 'Blocked'";
$blocked_result = $con->query($blocked_query);
if ($blocked_result) {
    $blocked_row = $blocked_result->fetch_assoc();
    $blocked_users = $blocked_row['count'];
    $blocked_result->free();
}


ob_start();
?>

<h4 class="mb-4">
    <i class="fa-solid fa-gauge-high me-2" style="color:#0d9488;"></i>
    Welcome, <?= htmlspecialchars($fullname) ?>
    <span class="badge" style="background-color:#0d9488;font-size:small;"><?= htmlspecialchars($role) ?></span>
</h4>

<div class="container-fluid">

    <!-- Top Stats -->
    <div class="row g-4">

        <!-- Users -->
        <div class="col-md-3">
            <a href="admin_users.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-card" style="border-left: 5px solid #0d9488;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted mb-0">Total Users</h6>
                            <i class="fa-solid fa-users fa-2x" style="color:#0d9488; opacity:0.3;"></i>
                        </div>
                        <h4 class="fw-bold mb-2" style="color:#0d9488;"><?= number_format($total_users) ?></h4>
                        <p class="<?= $user_growth >= 0 ? 'text-success' : 'text-danger' ?> small mb-0">
                            <i class="fa fa-arrow-<?= $user_growth >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($user_growth) ?>% this month
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Products -->
        <div class="col-md-3">
            <a href="admin_products.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-card" style="border-left: 5px solid #d4af37;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted mb-0">Total Products</h6>
                            <i class="fa-solid fa-box fa-2x" style="color:#d4af37; opacity:0.3;"></i>
                        </div>
                        <h4 class="fw-bold mb-2" style="color:#d4af37;"><?= number_format($total_products) ?></h4>
                        <p class="text-info small mb-0">
                            <i class="fa fa-plus"></i>
                            <?= $week_products ?> added this week
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Orders -->
        <div class="col-md-3">
            <a href="manage_orders.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-card" style="border-left: 5px solid #0f766e;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted mb-0">Pending Orders</h6>
                            <i class="fa-solid fa-shopping-cart fa-2x" style="color:#0f766e; opacity:0.3;"></i>
                        </div>
                        <h4 class="fw-bold mb-2" style="color:#0f766e;"><?= number_format($new_orders) ?></h4>
                        <p class="<?= $today_orders > 0 ? 'text-success' : 'text-warning' ?> small mb-0">
                            <i class="fa fa-clock"></i>
                            <?= $today_orders ?> orders today
                        </p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Revenue -->
        <div class="col-md-3">
            <a href="manage_orders.php" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-card" style="border-left: 5px solid #0d9488;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted mb-0">Total Revenue</h6>
                            <i class="fa-solid fa-indian-rupee-sign fa-2x" style="color:#0d9488; opacity:0.3;"></i>
                        </div>
                        <h4 class="fw-bold mb-2" style="color:#0d9488;">₹<?= number_format($total_revenue, 0) ?></h4>
                        <p class="<?= $revenue_growth >= 0 ? 'text-success' : 'text-danger' ?> small mb-0">
                            <i class="fa fa-arrow-<?= $revenue_growth >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($revenue_growth) ?>% growth
                        </p>
                    </div>
                </div>
            </a>
        </div>

    </div>

    <!-- Quick Stats Row (Moved Up) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa-solid fa-chart-simple me-2" style="color:#0d9488;"></i>
                        Quick Statistics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-md-3">
                            <div class="p-3 rounded" style="background-color: #f0fdfa;">
                                <i class="fa-solid fa-box-open fa-3x mb-3" style="color:#0d9488;"></i>
                                <h4 class="fw-bold mb-1" style="color:#0d9488;"><?= $total_products ?></h4>
                                <small class="text-muted">Products in Stock</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded" style="background-color: #fef3c7;">
                                <i class="fa-solid fa-users fa-3x mb-3" style="color:#d4af37;"></i>
                                <h4 class="fw-bold mb-1" style="color:#d4af37;"><?= $active_users ?></h4>
                                <small class="text-muted">Active Customers</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded" style="background-color: #ccfbf1;">
                                <i class="fa-solid fa-truck-fast fa-3x mb-3" style="color:#0f766e;"></i>
                                <h4 class="fw-bold mb-1" style="color:#0f766e;"><?= $today_orders ?></h4>
                                <small class="text-muted">Orders Today</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 rounded" style="background-color: #f0fdfa;">
                                <i class="fa-solid fa-sack-dollar fa-3x mb-3" style="color:#0d9488;"></i>
                                <h4 class="fw-bold mb-1" style="color:#0d9488;">₹<?= number_format($current_revenue, 0) ?></h4>
                                <small class="text-muted">Revenue This Month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row (Moved Down with Equal Heights) -->
    <div class="row mt-4 g-4">

        <!-- Sales Chart -->
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa-solid fa-chart-line me-2" style="color:#0d9488;"></i>
                        Sales Overview (Last 6 Months)
                    </h6>
                </div>
                <div class="card-body d-flex align-items-center">
                    <canvas id="salesChart" style="width: 100%; height: 300px;"></canvas>
                </div>
            </div>
        </div>

        <!-- User Distribution -->
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa-solid fa-user-group me-2" style="color:#0d9488;"></i>
                        User Distribution
                    </h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="text-center" style="height: 200px;">
                        <canvas id="pieChart"></canvas>
                    </div>
                    <div class="mt-3 small">
                        <div class="d-flex justify-content-between mb-2 align-items-center py-2 px-3 rounded" style="background-color: #f0fdfa;">
                            <span>
                                <i class="fa-solid fa-circle me-2" style="color:#0d9488;"></i>
                                Active Users
                            </span>
                            <strong style="color:#0d9488;"><?= $active_users ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 align-items-center py-2 px-3 rounded" style="background-color: #fef3c7;">
                            <span>
                                <i class="fa-solid fa-circle me-2" style="color:#d4af37;"></i>
                                Inactive Users
                            </span>
                            <strong style="color:#d4af37;"><?= $inactive_users ?></strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center py-2 px-3 rounded" style="background-color: #fee2e2;">
                            <span>
                                <i class="fa-solid fa-circle me-2" style="color:#e11d48;"></i>
                                Blocked Users
                            </span>
                            <strong style="color:#e11d48;"><?= $blocked_users ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
<br>br

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Sales Line Chart
    const ctx1 = document.getElementById('salesChart');
    new Chart(ctx1, {
        type: 'line',
        data: {
            labels: <?= json_encode($months_labels) ?>,
            datasets: [{
                label: 'Sales (₹)',
                data: <?= json_encode($monthly_sales) ?>,
                borderWidth: 3,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13,148,136,0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#0d9488',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        color: '#374151'
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Sales: ₹' + context.parsed.y.toLocaleString('en-IN');
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#0d9488',
                    borderWidth: 2
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '₹' + value.toLocaleString('en-IN');
                        },
                        font: {
                            size: 11
                        },
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        },
                        color: '#6b7280'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // Pie Chart
    const ctx2 = document.getElementById('pieChart');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Active Users', 'Inactive Users', 'Blocked Users'],
            datasets: [{
                data: [<?= $active_users ?>, <?= $inactive_users ?>, <?= $blocked_users ?>],
                backgroundColor: ['#0d9488', '#d4af37', '#e11d48'],
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff'
                }
            }
        }
    });
</script>

<style>
    .hover-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: pointer;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .card-header {
        border-bottom: 2px solid #f3f4f6;
    }

    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
        font-weight: 600;
    }

    /* Equal height for chart cards */
    .card.h-100 {
        height: 100% !important;
    }

    .card-body.d-flex {
        min-height: 350px;
    }
</style>

<?php
$content_admin = ob_get_clean();
include "admin_layout.php";
?>