<?php
require_once 'api/config.php';
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoboAIA Paths | Order Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="css/toast.css">
    <style>
        :root { --tech-blue: #3b82f6; --dark-bg: #0f172a; --glass: rgba(30, 41, 59, 0.7); }
        body { background: var(--dark-bg); font-family: 'Plus Jakarta Sans', sans-serif; color: white; min-height: 100vh; }
        
        /* Glassmorphism Logic */
        .glass-card { background: var(--glass); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 24px; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); }
        
        /* Login Form */
        .login-wrap { max-width: 400px; margin: 100px auto; }
        .tech-gradient { background: linear-gradient(135deg, #3b82f6 0%, #2dd4bf 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Dashboard */
        .admin-header { padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 40px; }
        .stat-card { padding: 20px; text-align: center; }
        .stat-val { font-size: 2rem; font-weight: 800; color: var(--tech-blue); }
        .btn-tech { background: var(--tech-blue); border: none; border-radius: 12px; padding: 12px 24px; font-weight: 700; color: white; transition: all 0.3s; }
        .btn-tech:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(59, 130, 246, 0.4); }
        
        /* Table Styles */
        .table { color: #cbd5e1; --bs-table-bg: transparent; }
        .table thead th { border-bottom: 1px solid rgba(255,255,255,0.1); padding: 15px; text-transform: uppercase; font-size: 0.75rem; color: #94a3b8; }
        .table tbody td { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); vertical-align: middle; }
        
        .status-badge { padding: 6px 12px; border-radius: 100px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .status-success { background: rgba(34, 197, 94, 0.1); color: #22c55e; }
        .status-pending { background: rgba(234, 179, 8, 0.1); color: #eab308; }
        .status-failed { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        
        .order-row:hover { background: rgba(255,255,255,0.02); }
        
        .modal-content { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; }
        
        input { background: rgba(255,255,255,0.05) !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; }
        input:focus { box-shadow: 0 0 0 2px var(--tech-blue) !important; }
    </style>
</head>
<body>

<?php if (!$is_logged_in): ?>
    <!-- LOGIN VIEW -->
    <div class="container">
        <div class="login-wrap glass-card p-5 text-center">
            <img src="img/logo.jpeg" height="60" class="mb-4 rounded" alt="Logo">
            <h2 class="fw-bold mb-4">Admin <span class="tech-gradient">Portal</span></h2>
            
            <div id="loginStep1">
                <p class="text-muted small mb-4">Access restricted to authorized secondary number.</p>
                <input type="tel" id="mobile" class="form-control text-center mb-4 py-3" placeholder="9990911093" value="9990911093" readonly>
                <button class="btn btn-tech w-100" id="sendBtn" onclick="requestAdminOtp()">Send OTP</button>
            </div>

            <div id="loginStep2" style="display:none;">
                <p class="text-muted small mb-4">Enter the code sent to your mobile.</p>
                <input type="text" id="otp" class="form-control text-center mb-4 py-3 fs-3" placeholder="••••" maxlength="4">
                <button class="btn btn-tech w-100" id="verifyBtn" onclick="verifyAdminOtp()">Verify & Enter</button>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- DASHBOARD VIEW -->
    <div class="container-fluid px-5">
        <header class="admin-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <img src="img/logo.jpeg" height="40" class="rounded" alt="Logo">
                <h4 class="fw-bold m-0"><span class="tech-gradient">Control</span> Center</h4>
            </div>
            <div class="d-flex gap-3">
                <a href="api/admin_export_csv.php" class="btn btn-outline-light rounded-pill px-4"><i class="fas fa-file-excel me-2"></i> Export CSV</a>
                <button onclick="logoutAdmin()" class="btn btn-tech rounded-pill px-4"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
            </div>
        </header>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val" id="totalCount">0</div>
                    <div class="small text-muted">Total Orders</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val text-success" id="successCount">0</div>
                    <div class="small text-muted">Successful</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val text-warning" id="pendingCount">0</div>
                    <div class="small text-muted">Pending/Processing</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val text-info" id="totalRev">₹ 0</div>
                    <div class="small text-muted">Total Revenue</div>
                </div>
            </div>
        </div>

        <div class="glass-card p-4 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0">Recent Activity</h5>
                <input type="text" id="orderSearch" class="form-control w-25 rounded-pill px-4" placeholder="Search mobile or TXN..." onkeyup="filterOrders()">
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Transaction & Date</th>
                            <th>Customer Mobile</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersBody">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Items Modal -->
    <div class="modal fade" id="itemsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0 pb-0">
                    <h5 class="fw-bold">Order Breakdown</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="itemsList"></div>
                    <hr class="opacity-10 my-4">
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span>Total Paid</span>
                        <span id="modalTotal" class="text-primary"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/toast.js"></script>
<script>
    async function apiRequest(action, data = {}) {
        const res = await fetch('api/admin_handler.php', {
            method: 'POST',
            body: JSON.stringify({ action, ...data }),
            headers: { 'Content-Type': 'application/json' }
        });
        return await res.json();
    }

    // LOGIN LOGIC
    async function requestAdminOtp() {
        const mobile = document.getElementById('mobile').value;
        const btn = document.getElementById('sendBtn');
        btn.disabled = true; btn.innerText = 'Sending...';
        
        const data = await apiRequest('send_otp', { mobile });
        if(data.status === 'success') {
            document.getElementById('loginStep1').style.display = 'none';
            document.getElementById('loginStep2').style.display = 'block';
            showToast('Secure OTP sent to authorized device.', 'success');
        } else {
            showToast(data.message, 'error');
        }
        btn.disabled = false; btn.innerText = 'Send OTP';
    }

    async function verifyAdminOtp() {
        const otp = document.getElementById('otp').value;
        const btn = document.getElementById('verifyBtn');
        btn.disabled = true; btn.innerText = 'Authorizing...';
        
        const data = await apiRequest('verify_otp', { otp });
        if(data.status === 'success') {
            window.location.reload();
        } else {
            showToast(data.message, 'error');
        }
        btn.disabled = false; btn.innerText = 'Verify & Enter';
    }

    // DASHBOARD LOGIC
    let allOrders = [];
    async function loadOrders() {
        const data = await apiRequest('get_orders');
        if(data.status === 'success') {
            allOrders = data.orders;
            renderOrders(allOrders);
            updateStats(allOrders);
        }
    }

    function renderOrders(orders) {
        const body = document.getElementById('ordersBody');
        if(!body) return;
        body.innerHTML = orders.map(o => `
            <tr class="order-row">
                <td>
                    <div class="fw-bold text-white small">${o.txnid}</div>
                    <div class="text-muted smaller">${new Date(o.created_at).toLocaleString()}</div>
                </td>
                <td>${o.user_mobile}</td>
                <td><span class="badge bg-secondary rounded-pill px-3">${o.item_count} Items</span></td>
                <td class="fw-bold">₹ ${parseFloat(o.total_amount).toLocaleString('en-IN')}</td>
                <td>
                    <select class="form-select status-badge border-0 status-${o.status}" onchange="updateStatus(${o.id}, this.value)">
                        <option value="pending" ${o.status === 'pending' ? 'selected' : ''}>Pending</option>
                        <option value="success" ${o.status === 'success' ? 'selected' : ''}>Success</option>
                        <option value="failed" ${o.status === 'failed' ? 'selected' : ''}>Failed</option>
                        <option value="tampered" ${o.status === 'tampered' ? 'selected' : ''}>Tampered</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="viewItems(${o.id}, ${o.total_amount})">Details</button>
                </td>
            </tr>
        `).join('');
    }

    function updateStats(orders) {
        document.getElementById('totalCount').innerText = orders.length;
        document.getElementById('successCount').innerText = orders.filter(o => o.status === 'success').length;
        document.getElementById('pendingCount').innerText = orders.filter(o => o.status === 'pending').length;
        
        const rev = orders.filter(o => o.status === 'success').reduce((sum, o) => sum + parseFloat(o.total_amount), 0);
        document.getElementById('totalRev').innerText = '₹ ' + rev.toLocaleString('en-IN');
    }

    async function updateStatus(id, status) {
        const data = await apiRequest('update_status', { order_id: id, status });
        if(data.status === 'success') {
            showToast('Order status synchronized.', 'success');
            loadOrders();
        } else {
            showToast(data.message, 'error');
        }
    }

    async function viewItems(id, total) {
        const data = await apiRequest('get_items', { order_id: id });
        if(data.status === 'success') {
            const list = document.getElementById('itemsList');
            list.innerHTML = data.items.map(i => `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-bold text-white">${i.name}</div>
                        <div class="text-muted smaller">Qty: ${i.quantity} x ₹${i.price_at_time}</div>
                    </div>
                </div>
            `).join('');
            document.getElementById('modalTotal').innerText = '₹ ' + total.toLocaleString('en-IN');
            new bootstrap.Modal('#itemsModal').show();
        }
    }

    function filterOrders() {
        const val = document.getElementById('orderSearch').value.toLowerCase();
        const filtered = allOrders.filter(o => o.user_mobile.includes(val) || o.txnid.toLowerCase().includes(val));
        renderOrders(filtered);
    }

    async function logoutAdmin() {
        // Simple session destroy via dummy link or API call
        window.location.href = 'api/logout.php'; // Existing logout script
    }

    <?php if($is_logged_in): ?>
    document.addEventListener('DOMContentLoaded', loadOrders);
    setInterval(loadOrders, 30000); // Auto refresh every 30s
    <?php endif; ?>
</script>
</body>
</html>
