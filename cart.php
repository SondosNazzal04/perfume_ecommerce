<?php
session_start();
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CSS & JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; }
        .cart-container { margin-top: 50px; margin-bottom: 50px; }
        .table img { width: 60px; border-radius: 5px; }
        .quantity-input { width: 70px; text-align: center; }
    </style>
</head>
<body>

    <!-- Simple Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-spray-can"></i> Perfume Store</a>
            <div class="ms-auto">
                <a href="shop.php" class="btn btn-outline-light btn-sm">Continue Shopping</a>
            </div>
        </div>
    </nav>

    <!-- Main Cart Section -->
    <div class="container cart-container">
        <h2 class="mb-4 fw-bold">Shopping Cart</h2>

        <div class="row">
            <!-- Cart Items Table -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Size</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="cart-items-body">
                                <!-- JS will inject data here -->
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2 mb-0">Loading cart...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Clear Cart Button -->
                <div class="mt-3">
                    <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                        <i class="fas fa-trash-alt"></i> Clear Entire Cart
                    </button>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-3">Order Summary</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span id="subtotal-text">$0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted">
                            <span>Delivery Fee</span>
                            <span>Calculated at checkout</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                            <span>Total</span>
                            <span id="total-text" class="text-success">$0.00</span>
                        </div>
                        <button class="btn btn-dark w-100 py-2" onclick="window.location.href='checkout.php'">
                            Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Logic (Async/Await) -->
    <script>
        // Load cart when page loads
        document.addEventListener("DOMContentLoaded", () => {
            loadCart();
        });

        // Helper function for elegant toast notifications
        function showToast(icon, title, text = '') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            Toast.fire({
                icon: icon,
                title: title,
                text: text
            });
        }

        // 1. Fetch Cart Items (GET)
        async function loadCart() {
            try {
                const response = await fetch('api/cart.php');
                const data = await response.json();

                if (data.success) {
                    renderCart(data.data);
                } else {
                    showToast('error', 'Error', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('cart-items-body').innerHTML = 
                    `<tr><td colspan="6" class="text-center text-danger">Failed to load cart. Please try again.</td></tr>`;
            }
        }

        // 2. Display Cart Items in HTML
        function renderCart(items) {
            const tbody = document.getElementById('cart-items-body');
            tbody.innerHTML = '';
            let grandTotal = 0;

            if (items.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                            <p>Your cart is empty.</p>
                            <a href="shop.php" class="btn btn-primary btn-sm">Shop Now</a>
                        </td>
                    </tr>`;
                document.getElementById('subtotal-text').innerText = '$0.00';
                document.getElementById('total-text').innerText = '$0.00';
                return;
            }

            items.forEach(item => {
                const itemTotal = item.price * item.quantity;
                grandTotal += itemTotal;

                tbody.innerHTML += `
                    <tr>
                        <td class="fw-bold">${item.product_name}</td>
                        <td>${item.size_ml}ml</td>
                        <td>$${parseFloat(item.price).toFixed(2)}</td>
                        <td>
                            <input type="number" min="1" value="${item.quantity}" 
                                   class="form-control form-control-sm quantity-input" 
                                   onchange="updateQuantity(${item.cart_id}, this.value)">
                        </td>
                        <td class="fw-bold">$${itemTotal.toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeItem(${item.cart_id})">
                                <i class="fas fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            // Update Totals
            document.getElementById('subtotal-text').innerText = `$${grandTotal.toFixed(2)}`;
            document.getElementById('total-text').innerText = `$${grandTotal.toFixed(2)}`;
        }

        // 3. Update Quantity (PUT)
        async function updateQuantity(cartId, newQuantity) {
            if (newQuantity < 1) return;

            try {
                const response = await fetch(`api/cart.php?id=${cartId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ quantity: parseInt(newQuantity) })
                });
                const data = await response.json();

                if (data.success) {
                    loadCart(); // Reload to update totals
                } else {
                    showToast('error', 'Update Failed', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // 4. Remove Single Item (DELETE with ID)
        async function removeItem(cartId) {
            // SweetAlert2 Confirmation Popup
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "You want to remove this item from your cart?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, remove it!'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch(`api/cart.php?id=${cartId}`, {
                    method: 'DELETE'
                });
                const data = await response.json();

                if (data.success) {
                    showToast('success', 'Removed!', 'Item removed from cart.');
                    loadCart();
                } else {
                    showToast('error', 'Failed', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // 5. Clear Entire Cart (DELETE without ID)
        async function clearCart() {
            // SweetAlert2 Confirmation Popup
            const result = await Swal.fire({
                title: 'Clear Cart?',
                text: "This will remove ALL items from your cart!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, clear it!'
            });

            if (!result.isConfirmed) return;

            try {
                const response = await fetch('api/cart.php', {
                    method: 'DELETE'
                });
                const data = await response.json();

                if (data.success) {
                    showToast('success', 'Cleared!', 'Your cart is now empty.');
                    loadCart();
                } else {
                    showToast('error', 'Failed', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    </script>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>