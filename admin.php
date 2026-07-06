<?php
require_once 'api/config.php';
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RoboAIAPaths | Order Admin</title>
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

        /* Chatbot Style Extensions */
        .cb-select { background: rgba(15, 23, 42, 0.8) !important; color: white !important; border: 1px solid rgba(255,255,255,0.15) !important; border-radius: 10px; padding: 6px 12px; font-size: 0.8rem; font-weight: 600; outline: none; }
        .cb-notes { background: rgba(255,255,255,0.03) !important; color: #e2e8f0 !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 12px; font-size: 0.8rem; width: 100%; min-width: 160px; height: 54px; padding: 8px; resize: vertical; outline: none; }
        .cb-notes:focus { border-color: var(--tech-blue) !important; background: rgba(255,255,255,0.08) !important; }
        .cb-date { background: rgba(255,255,255,0.03) !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 10px; font-size: 0.8rem; padding: 6px 10px; outline: none; }
        .cb-date:focus { border-color: var(--tech-blue) !important; }
        .cb-status-New { color: #3b82f6 !important; background: rgba(59, 130, 246, 0.1) !important; }
        .cb-status-Contacted { color: #f59e0b !important; background: rgba(245, 158, 11, 0.1) !important; }
        .cb-status-Demo { color: #8b5cf6 !important; background: rgba(139, 92, 246, 0.1) !important; }
        .cb-status-Joined { color: #10b981 !important; background: rgba(16, 185, 129, 0.1) !important; }
        .cb-status-Not { color: #ef4444 !important; background: rgba(239, 68, 68, 0.1) !important; }
        
        .btn-whatsapp { background: #25d366; color: white; border: none; border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 700; transition: 0.3s; }
        .btn-whatsapp:hover { background: #128c7e; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); }
        .btn-save { background: var(--tech-blue); color: white; border: none; border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 700; transition: 0.3s; }
        .btn-save:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
        .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; padding: 6px 12px; font-size: 0.75rem; font-weight: 700; transition: 0.3s; }
        .btn-delete:hover { background: #ef4444; color: white; transform: translateY(-1px); }
        .smaller-text { font-size: 0.8rem; color: #94a3b8; }
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
                <a href="api/admin_export_csv.php" id="exportOrdersBtn" class="btn btn-outline-light rounded-pill px-4"><i class="fas fa-file-excel me-2"></i> Export CSV</a>
                <button onclick="logoutAdmin()" class="btn btn-tech rounded-pill px-4"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
            </div>
        </header>

        <!-- Tabs Navigation -->
        <div class="d-flex gap-3 mb-4">
            <button class="btn btn-tech rounded-pill px-4" id="tabOrders" onclick="switchTab('orders')"><i class="fas fa-shopping-cart me-2"></i> Orders Dashboard</button>
            <button class="btn btn-outline-light rounded-pill px-4" id="tabLeads" onclick="switchTab('leads')"><i class="fas fa-user-friends me-2"></i> Chatbot Leads</button>
            <button class="btn btn-outline-light rounded-pill px-4" id="tabChats" onclick="switchTab('chats')"><i class="fas fa-comments me-2"></i> Bot Chat Logs</button>
        </div>

        <!-- SECTION 1: ORDERS DASHBOARD STATS -->
        <div class="row g-4 mb-5" id="ordersStatsRow">
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

        <!-- SECTION 2: CHATBOT LEADS STATS -->
        <div class="row g-4 mb-5" id="chatbotStatsRow" style="display: none;">
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val" id="cbTotalLeads">0</div>
                    <div class="small text-muted">Total Chatbot Leads</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card stat-card text-success">
                    <div class="stat-val text-success" id="cbTodayLeads">0</div>
                    <div class="small text-muted">Today's Leads</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val text-warning" id="cbTotalChats">0</div>
                    <div class="small text-muted">Total Chats Managed</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card stat-card">
                    <div class="stat-val text-info"><span id="cbConversionRate">0</span>%</div>
                    <div class="small text-muted">Joined Conversion Rate</div>
                </div>
            </div>
        </div>

        <!-- VIEW 1: ORDERS TABLE -->
        <div class="glass-card p-4 overflow-hidden" id="ordersView">
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

        <!-- VIEW 2: CHATBOT LEADS TABLE -->
        <div class="glass-card p-4 overflow-hidden" id="chatbotLeadsView" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex gap-3 align-items-center">
                    <h5 class="fw-bold m-0">Chatbot Leads</h5>
                    <select id="statusFilter" class="cb-select" onchange="loadChatbotLeads()">
                        <option value="All">All Statuses</option>
                        <option value="New">New</option>
                        <option value="Contacted">Contacted</option>
                        <option value="Demo Scheduled">Demo Scheduled</option>
                        <option value="Joined">Joined</option>
                        <option value="Not Interested">Not Interested</option>
                    </select>
                </div>
                <div class="d-flex gap-3 align-items-center w-50 justify-content-end">
                    <input type="text" id="leadSearch" class="form-control w-50 rounded-pill px-4" placeholder="Search name, phone, course..." onkeyup="loadChatbotLeads()">
                    <button onclick="exportChatbotCSV()" class="btn btn-outline-light rounded-pill px-4"><i class="fas fa-file-csv me-2"></i> Export Leads</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Parent Details</th>
                            <th>Child Class</th>
                            <th>Course Interest</th>
                            <th>Status</th>
                            <th>Follow-up Date</th>
                            <th>Demo Date</th>
                            <th>Notes</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="chatbotLeadsBody">
                        <!-- Loaded via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 3: CHATBOT LOGS TABLE -->
        <div class="glass-card p-4 overflow-hidden" id="chatbotLogsView" style="display: none;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0">Bot Conversation Logs</h5>
                <button onclick="loadChatbotLogs()" class="btn btn-sm btn-outline-light rounded-pill px-3"><i class="fas fa-sync-alt me-1"></i> Refresh Logs</button>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 25%;">User Message</th>
                            <th style="width: 45%;">Bot Reply</th>
                            <th style="width: 12%;">Lead Detected?</th>
                            <th style="width: 18%;">Time</th>
                        </tr>
                    </thead>
                    <tbody id="chatbotLogsBody">
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
    // General API Request Handler for PHP endpoints
    async function apiRequest(action, body = {}) {
        try {
            const res = await fetch('api/admin_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action, ...body })
            });
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return await res.json();
        } catch (error) {
            console.error("API Request Error:", error);
            showToast("Failed to communicate with server.", "error");
            return { status: 'error', message: error.message };
        }
    }

    // OTP Login Functions
    async function requestAdminOtp() {
        const mobile = document.getElementById('mobile').value;
        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;
        sendBtn.innerText = "Sending...";
        
        const data = await apiRequest('send_otp', { mobile });
        if (data.status === 'success') {
            showToast(data.message, 'success');
            document.getElementById('loginStep1').style.display = 'none';
            document.getElementById('loginStep2').style.display = 'block';
        } else {
            showToast(data.message, 'error');
            sendBtn.disabled = false;
            sendBtn.innerText = "Send OTP";
        }
    }

    async function verifyAdminOtp() {
        const otp = document.getElementById('otp').value;
        const verifyBtn = document.getElementById('verifyBtn');
        verifyBtn.disabled = true;
        verifyBtn.innerText = "Verifying...";
        
        const data = await apiRequest('verify_otp', { otp });
        if (data.status === 'success') {
            showToast(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast(data.message, 'error');
            verifyBtn.disabled = false;
            verifyBtn.innerText = "Verify & Enter";
        }
    }

    // DASHBOARD LOGIC
    let allOrders = [];
    let currentTab = 'orders';

    // 2. CHATBOT LEADS ACTIONS
    async function loadChatbotStats() {
        const res = await apiRequest('cb_stats');
        if (res && res.status === 'success') {
            const stats = res.data;
            document.getElementById('cbTotalLeads').innerText = stats.total_leads || 0;
            document.getElementById('cbTodayLeads').innerText = stats.today_leads || 0;
            document.getElementById('cbTotalChats').innerText = stats.total_chats || 0;
            document.getElementById('cbConversionRate').innerText = stats.conversion_rate || 0;
        }
    }

    async function loadChatbotLeads() {
        const status = document.getElementById('statusFilter').value;
        const search = document.getElementById('leadSearch').value;
        const res = await apiRequest('cb_leads', { search, status_filter: status });
        
        const body = document.getElementById('chatbotLeadsBody');
        if (!body) return;
        body.innerHTML = '';

        if (!res || res.status !== 'success' || !res.data || !res.data.leads || res.data.leads.length === 0) {
            body.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-4">No chatbot leads found</td></tr>`;
            return;
        }

        res.data.leads.forEach(lead => {
            const safeName = (lead.name || "-").replace(/'/g, "\\'");
            const safePhone = (lead.phone || "-").replace(/'/g, "\\'");
            const safeClass = (lead.child_class || "-").replace(/'/g, "\\'");
            const safeCourse = (lead.course_interest || "-").replace(/'/g, "\\'");

            body.innerHTML += `
                <tr class="order-row">
                    <td>
                        <div class="fw-bold text-white">${lead.name || '-'}</div>
                        <div class="smaller-text"><i class="fas fa-phone me-1"></i> ${lead.phone || '-'}</div>
                    </td>
                    <td><span class="badge bg-secondary px-3 py-2 rounded-pill">${lead.child_class || '-'}</span></td>
                    <td class="fw-bold text-info">${lead.course_interest || '-'}</td>
                    <td>
                        <select class="cb-select cb-status-${lead.status.split(' ')[0]}" onchange="updateLeadStatus('${lead.id}', this.value)">
                            <option value="New" ${lead.status === 'New' ? 'selected' : ''}>New</option>
                            <option value="Contacted" ${lead.status === 'Contacted' ? 'selected' : ''}>Contacted</option>
                            <option value="Demo Scheduled" ${lead.status === 'Demo Scheduled' ? 'selected' : ''}>Demo Scheduled</option>
                            <option value="Joined" ${lead.status === 'Joined' ? 'selected' : ''}>Joined</option>
                            <option value="Not Interested" ${lead.status === 'Not Interested' ? 'selected' : ''}>Not Interested</option>
                        </select>
                    </td>
                    <td>
                        <input type="date" class="cb-date" id="followup_${lead.id}" value="${lead.followup_date || ''}">
                    </td>
                    <td>
                        <input type="date" class="cb-date" id="demo_${lead.id}" value="${lead.demo_date || ''}">
                    </td>
                    <td>
                        <textarea class="cb-notes" id="notes_${lead.id}" placeholder="Lead details/enquiry">${lead.notes || lead.message || ''}</textarea>
                    </td>
                    <td class="smaller-text">${new Date(lead.created_at).toLocaleString()}</td>
                    <td>
                        <div class="d-flex flex-column gap-2">
                            <button class="btn-save" onclick="saveLeadCRM('${lead.id}')"><i class="fas fa-save me-1"></i> Save</button>
                            <button class="btn-whatsapp" onclick="sendWhatsApp('${safePhone}', '${safeName}', '${safeClass}', '${safeCourse}')"><i class="fab fa-whatsapp me-1"></i> WhatsApp</button>
                            <button class="btn-delete" onclick="deleteLead('${lead.id}')"><i class="fas fa-trash me-1"></i> Delete</button>
                        </div>
                    </td>
                </tr>
            `;
        });
    }

    async function updateLeadStatus(leadId, status) {
        const res = await apiRequest('cb_update_status', { lead_id: leadId, status });
        if (res && res.status === 'success') {
            showToast("Lead status synchronized.", "success");
            loadChatbotLeads();
            loadChatbotStats();
        } else {
            showToast(res.message || "Failed to update status", "error");
        }
    }

    async function saveLeadCRM(leadId) {
        const notes = document.getElementById(`notes_${leadId}`).value;
        const followup_date = document.getElementById(`followup_${leadId}`).value;
        const demo_date = document.getElementById(`demo_${leadId}`).value;

        const res = await apiRequest('cb_update_crm', {
            lead_id: leadId, notes, followup_date, demo_date
        });
        if (res && res.status === 'success') {
            showToast("CRM details updated.", "success");
            loadChatbotLeads();
        } else {
            showToast(res.message || "Failed to update CRM details", "error");
        }
    }

    async function deleteLead(leadId) {
        if (!confirm("Are you sure you want to delete this chatbot lead?")) return;
        const res = await apiRequest('cb_delete_lead', { lead_id: leadId });
        if (res && res.status === 'success') {
            showToast("Lead deleted successfully.", "success");
            loadChatbotLeads();
            loadChatbotStats();
        } else {
            showToast(res.message || "Failed to delete lead", "error");
        }
    }

    function exportChatbotCSV() {
        window.open(`api/admin_handler.php?action=cb_export_csv`, "_blank");
    }

    function cleanPhone(phone) {
        if (!phone) return "";
        let cleaned = phone.toString().replace(/\D/g, "");
        if (cleaned.startsWith("91") && cleaned.length === 12) {
            return cleaned;
        }
        if (cleaned.length === 10) {
            return "91" + cleaned;
        }
        return cleaned;
    }

    function sendWhatsApp(phone, name, childClass, course) {
        const finalPhone = cleanPhone(phone);
        if (!finalPhone) {
            showToast("Phone number not available", "error");
            return;
        }
        const parentName = name && name !== "-" ? name : "Parent";
        const studentClass = childClass && childClass !== "-" ? childClass : "";
        const courseName = course && course !== "-" ? course : "RoboAIAPaths course";

        const message = `Hello ${parentName} 👋\n\nThank you for contacting RoboAIAPaths.\n\nWe received your enquiry for ${courseName}${studentClass ? " for Class " + studentClass : ""}.\n\nOur team will guide you with course details and demo class timing.\n\nRegards,\nRoboAIAPaths Team`;

        const whatsappURL = `https://wa.me/${finalPhone}?text=${encodeURIComponent(message)}`;
        window.open(whatsappURL, "_blank");
    }

    // 3. CHATBOT LOGS ACTIONS
    async function loadChatbotLogs() {
        const res = await apiRequest('cb_chats');
        const body = document.getElementById('chatbotLogsBody');
        if (!body) return;
        body.innerHTML = '';

        if (!res || res.status !== 'success' || !res.data || !res.data.logs || res.data.logs.length === 0) {
            body.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No conversational logs found</td></tr>`;
            return;
        }

        res.data.logs.forEach(log => {
            body.innerHTML += `
                <tr class="order-row">
                    <td class="text-white small">${log.user_message || '-'}</td>
                    <td class="text-muted small" style="white-space: pre-wrap;">${log.bot_reply || '-'}</td>
                    <td>
                        <span class="badge ${log.lead_detected == 1 ? 'bg-success' : 'bg-secondary'} rounded-pill px-3">
                            ${log.lead_detected == 1 ? 'Yes' : 'No'}
                        </span>
                    </td>
                    <td class="smaller-text">${new Date(log.created_at).toLocaleString()}</td>
                </tr>
            `;
        });
    }


    async function logoutAdmin() {
        window.location.href = 'api/logout.php';
    }

    <?php if($is_logged_in): ?>
    document.addEventListener('DOMContentLoaded', () => {
        loadOrders();
        // pre-fetch chatbot stats
        loadChatbotStats();
    });
    
    // Auto-refresh loops based on tab view
    setInterval(() => {
        if (currentTab === 'orders') {
            loadOrders();
        } else if (currentTab === 'leads') {
            loadChatbotLeads();
            loadChatbotStats();
        } else if (currentTab === 'chats') {
            loadChatbotLogs();
        }
    }, 30000);
    <?php endif; ?>
</script>
</body>
</html>
