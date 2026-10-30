<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page (note the ../ because we are inside admin folder)
    header("Location: ../login.php");
    exit();
}

// Check if the logged-in user has the 'admin' role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect to store home page if they are just a normal customer
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f4f6f9; overflow-x: hidden; }
        .sidebar { min-height: 100vh; background-color: #212529; color: white; padding-top: 20px; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 15px 20px; display: block; }
        .sidebar a:hover, .sidebar a.active { background-color: #0d6efd; color: white; }
        .main-content { padding: 30px; }
        .table th { background-color: #343a40; color: white; }
        .status-badge { font-size: 0.9em; padding: 5px 10px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="sidebar" style="width: 250px; flex-shrink: 0;">
        <h4 class="text-center mb-4"><i class="fas fa-spray-can"></i> Admin Panel</h4>
        <a href="index.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a>
        <a href="products.php"><i class="fas fa-box me-2"></i> Products</a>
        <a href="orders.php" class="active"><i class="fas fa-shopping-cart me-2"></i> Orders</a>
        <a href="users.php"><i class="fas fa-users me-2"></i> Users</a>
        <hr class="text-secondary">
        <a href="../index.php"><i class="fas fa-store me-2"></i> View Store</a>
    </div>

    <!-- Main Content -->
    <div class="main-content w-100">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Order Management</h2>
            <button class="btn btn-outline-primary btn-sm" onclick="loadOrders()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Change Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="orders-body">
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2 mb-0">Loading orders...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. MODAL FOR ORDER DETAILS (الجزء الناقص) -->
<!-- ========================================== -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice me-2"></i> Order Details <span id="modal-order-id"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Customer & Shipping Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted">Customer Info</h6>
                        <p class="mb-1"><strong>Name:</strong> <span id="modal-customer-name"></span></p>
                        <p class="mb-1"><strong>Email:</strong> <span id="modal-customer-email"></span></p>
                        <p class="mb-1"><strong>Date:</strong> <span id="modal-date"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted">Shipping Address</h6>
                        <p class="mb-1"><strong>City:</strong> <span id="modal-city"></span>, <span id="modal-area"></span></p>
                        <p class="mb-1"><strong>Street:</strong> <span id="modal-street"></span></p>
                        <p class="mb-1"><strong>Building:</strong> <span id="modal-building"></span></p>
                    </div>
                </div>

                <!-- Order Items Table -->
                <h6 class="fw-bold text-muted mb-2">Items Ordered</h6>
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Size</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="modal-items-body">
                        <!-- Items will be injected here -->
                    </tbody>
                </table>

                <!-- Financial Totals -->
                <div class="d-flex flex-column align-items-end mt-3">
                    <p class="mb-1"><strong>Subtotal:</strong> $<span id="modal-subtotal"></span></p>
                    <p class="mb-1"><strong>Delivery Fee:</strong> $<span id="modal-delivery"></span></p>
                    <h5 class="mt-2"><strong>Total:</strong> <span class="text-success">$<span id="modal-total"></span></span></h5>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Logic -->
<script>
    document.addEventListener("DOMContentLoaded", () => {
        loadOrders();
    });

    function showToast(icon, title, text = '') {
        Swal.fire({
            icon: icon, title: title, text: text, toast: true,
            position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true
        });
    }

    async function loadOrders() {
        try {
            const response = await fetch('../api/admin-orders.php');
            const data = await response.json();

            if (data.success) {
                renderOrders(data.data);
            } else {
                showToast('error', 'Error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            document.getElementById('orders-body').innerHTML = 
                `<tr><td colspan="7" class="text-center text-danger">Failed to load orders.</td></tr>`;
        }
    }

    function renderOrders(orders) {
        const tbody = document.getElementById('orders-body');
        tbody.innerHTML = '';

        if (orders.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4">No orders found.</td></tr>`;
            return;
        }

        orders.forEach(order => {
            let badgeClass = 'bg-secondary';
            if (order.status === 'pending') badgeClass = 'bg-warning text-dark';
            if (order.status === 'processing') badgeClass = 'bg-info text-dark';
            if (order.status === 'confirmed') badgeClass = 'bg-primary';
            if (order.status === 'delivered') badgeClass = 'bg-success';
            if (order.status === 'cancelled') badgeClass = 'bg-danger';

            const date = new Date(order.created_at).toLocaleString();

            const statusOptions = ['delivered', 'cancelled'];
            let selectHtml = `<select class="form-select form-select-sm" onchange="updateStatus(${order.id}, this.value)">`;
            statusOptions.forEach(opt => {
                const selected = (order.status === opt) ? 'selected' : '';
                selectHtml += `<option value="${opt}" ${selected}>${opt.charAt(0).toUpperCase() + opt.slice(1)}</option>`;
            });
            selectHtml += `</select>`;

            tbody.innerHTML += `
                <tr>
                    <td class="fw-bold">#${order.id}</td>
                    <td>${order.customer_name}</td>
                    <td>${date}</td>
                    <td class="fw-bold">$${parseFloat(order.total_amount).toFixed(2)}</td>
                    <td><span class="badge ${badgeClass} status-badge">${order.status.toUpperCase()}</span></td>
                    <td style="max-width: 150px;">${selectHtml}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(${order.id})">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </td>
                </tr>
            `;
        });
    }

    async function updateStatus(orderId, newStatus) {
        try {
            const response = await fetch(`../api/admin-orders.php?id=${orderId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            const data = await response.json();

            if (data.success) {
                showToast('success', 'Updated!', `Order #${orderId} is now ${newStatus}.`);
                loadOrders();
            } else {
                showToast('error', 'Failed', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Server Error', 'Could not update status.');
        }
    }


    async function viewOrderDetails(orderId) {
        try {
            const response = await fetch(`../api/admin-orders.php?id=${orderId}`);
            const data = await response.json();

            if (data.success) {
                const order = data.data;
                
                document.getElementById('modal-order-id').innerText = `#${order.id}`;
                document.getElementById('modal-customer-name').innerText = order.customer_name;
                document.getElementById('modal-customer-email').innerText = order.email;
                document.getElementById('modal-date').innerText = new Date(order.created_at).toLocaleString();
                
                document.getElementById('modal-city').innerText = order.city;
                document.getElementById('modal-area').innerText = order.area;
                document.getElementById('modal-street').innerText = order.street;
                document.getElementById('modal-building').innerText = order.building || 'N/A';

                document.getElementById('modal-subtotal').innerText = parseFloat(order.subtotal).toFixed(2);
                document.getElementById('modal-delivery').innerText = parseFloat(order.delivery_fee).toFixed(2);
                document.getElementById('modal-total').innerText = parseFloat(order.total_amount).toFixed(2);

                const itemsBody = document.getElementById('modal-items-body');
                itemsBody.innerHTML = '';
                order.items.forEach(item => {
                    const itemTotal = item.quantity * item.unit_price;
                    itemsBody.innerHTML += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.size_ml}ml</td>
                            <td>${item.quantity}</td>
                            <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                            <td>$${itemTotal.toFixed(2)}</td>
                        </tr>
                    `;
                });

                const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                modal.show();
            } else {
                showToast('error', 'Error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('error', 'Server Error', 'Could not fetch order details.');
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>