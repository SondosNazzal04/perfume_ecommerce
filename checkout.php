<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; }
        .checkout-container { margin-top: 50px; margin-bottom: 50px; }
        .form-label { font-weight: 500; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><i class="fas fa-spray-can"></i> Perfume Store</a>
            <div class="ms-auto">
                <a href="cart.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Back to Cart</a>
            </div>
        </div>
    </nav>

    <!-- Checkout Section -->
    <div class="container checkout-container">
        <h2 class="mb-4 fw-bold">Checkout</h2>

        <form id="checkout-form">
            <div class="row">
                <!-- Shipping Information Form -->
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-map-marker-alt text-primary me-2"></i> Shipping Address</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" required placeholder="e.g., Amman">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Area *</label>
                                    <input type="text" class="form-control" id="area" required placeholder="e.g., Abdoun">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Street *</label>
                                    <input type="text" class="form-control" id="street" required placeholder="e.g., Main Street">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Building No.</label>
                                    <input type="text" class="form-control" id="building" placeholder="e.g., Building 5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold"><i class="fas fa-credit-card text-primary me-2"></i> Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked>
                                <label class="form-check-label" for="cash">
                                    <i class="fas fa-money-bill-wave text-success me-1"></i> Cash on Delivery
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="visa" value="visa">
                                <label class="form-check-label" for="visa">
                                    <i class="fab fa-cc-visa text-primary me-1"></i> Credit / Debit Card (Visa)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-5 mt-4 mt-lg-0">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white py-3">
                            <h5 class="mb-0 fw-bold">Order Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal</span>
                                <span id="subtotal-text" class="fw-bold">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-3 text-muted">
                                <span>Delivery Fee</span>
                                <span>$5.00</span> <!-- Fixed fee as per our backend -->
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4 fw-bold fs-4">
                                <span>Total</span>
                                <span id="total-text" class="text-success">$0.00</span>
                            </div>
                            
                            <button type="submit" id="submit-btn" class="btn btn-primary w-100 py-3 fw-bold">
                                <i class="fas fa-lock me-2"></i> Place Order
                            </button>
                            <p class="text-center text-muted mt-3 small">
                                <i class="fas fa-shield-alt"></i> Secure Checkout
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- JavaScript Logic -->
    <script>
        let cartSubtotal = 0;
        const deliveryFee = 5.00; // Matches the PHP backend calculation

        // Load cart summary on page load
        document.addEventListener("DOMContentLoaded", () => {
            loadSummary();
        });

        // 1. Fetch Cart Summary (GET)
        async function loadSummary() {
            try {
                const response = await fetch('api/cart.php');
                const data = await response.json();

                if (data.success) {
                    if (data.data.length === 0) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Cart is Empty',
                            text: 'You cannot checkout with an empty cart.',
                            confirmButtonText: 'Back to Shop'
                        }).then(() => {
                            window.location.href = 'shop.php';
                        });
                        document.getElementById('submit-btn').disabled = true;
                        return;
                    }

                    // Calculate Subtotal
                    data.data.forEach(item => {
                        cartSubtotal += (item.price * item.quantity);
                    });

                    // Update UI
                    document.getElementById('subtotal-text').innerText = `$${cartSubtotal.toFixed(2)}`;
                    const grandTotal = cartSubtotal + deliveryFee;
                    document.getElementById('total-text').innerText = `$${grandTotal.toFixed(2)}`;

                } else {
                    console.error("Failed to load cart", data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // 2. Submit Order (POST)
        document.getElementById('checkout-form').addEventListener('submit', async (e) => {
            e.preventDefault(); // Prevent standard form submission

            // Show loading state
            const submitBtn = document.getElementById('submit-btn');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...`;
            submitBtn.disabled = true;

            // Get form data
            const formData = {
                city: document.getElementById('city').value.trim(),
                area: document.getElementById('area').value.trim(),
                street: document.getElementById('street').value.trim(),
                building: document.getElementById('building').value.trim()
                // Note: payment_method is ignored by current backend, but can be added later
            };

            try {
                const response = await fetch('api/orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();

                if (data.success) {
                    // Success Alert
                    Swal.fire({
                        icon: 'success',
                        title: 'Order Placed!',
                        text: `Your order #${data.order_id} has been created successfully. Total: $${data.calculated_total}`,
                        confirmButtonText: 'View My Orders',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to My Orders page (will be built later)
                            window.location.href = 'my-orders.php'; 
                        }
                    });
                } else {
                    // Error Alert
                    Swal.fire({
                        icon: 'error',
                        title: 'Checkout Failed',
                        text: data.message
                    });
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error',
                    text: 'Something went wrong. Please try again.'
                });
                submitBtn.innerHTML = originalBtnText;
                submitBtn.disabled = false;
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>