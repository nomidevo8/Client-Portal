
(function() {
    const API_ROOT = ClientPortal.api_root;
    const NONCE    = ClientPortal.nonce;
    // API Helper
    async function apiCall(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            credentials: 'include',
            headers: {
                'X-WP-Nonce': NONCE,
            }
        };
        
        if (data) {
            if (data instanceof FormData) {
                options.body = data;
            } else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data);
            }
        }
        
        const response = await fetch(API_ROOT + endpoint, options);
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'API request failed');
        }
        return response.json();
    }
    
    // Tab Navigation
    document.querySelectorAll('.cp-tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.cp-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.cp-view').forEach(v => v.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(btn.dataset.tab).classList.add('active');
            
            // Load data when switching tabs
            if (btn.dataset.tab === 'documents') loadDocuments();
            if (btn.dataset.tab === 'messages') loadTickets();
            if (btn.dataset.tab === 'invoices') loadInvoices();

            // If on mobile and drawer is open, close it after selecting a tab
            closeMobileDrawer();
        });
    });
    
    // Profile Form
    const profileForm = document.getElementById('profileForm');
    
    async function loadProfile() {
        try {
            const profile = await apiCall('profile');
            profileForm.fullName.value = profile.fullName || '';
            profileForm.email.value = profile.email || '';
            profileForm.phone.value = profile.phone || '';
            profileForm.company.value = profile.company || '';
            profileForm.address.value = profile.address || '';
        } catch (error) {
            console.error('Error loading profile:', error);
        }
    }
    
    profileForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = profileForm.querySelector('button');
        btn.disabled = true;
        btn.textContent = 'Saving...';
        
        try {
            const formData = new FormData(profileForm);
            const data = Object.fromEntries(formData);
            await apiCall('profile', 'POST', data);
            alert('Profile updated successfully!');
        } catch (error) {
            alert('Error updating profile: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save Profile';
        }
    });
    
    // Documents
    const docList = document.getElementById('docList');
    const uploadBtn = document.getElementById('uploadBtn');
    const fileInput = document.getElementById('fileInput');
    const docTitle = document.getElementById('docTitle');
    
    async function loadDocuments() {
        docList.innerHTML = '<div class="cp-loading">Loading documents...</div>';
        try {
            const docs = await apiCall('documents');
            renderDocuments(docs);
        } catch (error) {
            docList.innerHTML = '<div class="cp-small">Error loading documents</div>';
        }
    }
    
    function renderDocuments(docs) {
        if (docs.length === 0) {
            docList.innerHTML = '<div class="cp-small">No documents yet</div>';
            return;
        }
        
        docList.innerHTML = docs.map(d => `
            <div class="cp-card cp-row">
                <div>
                    <div style="font-weight:600">${escapeHtml(d.name)}</div>
                    <div class="cp-small">${escapeHtml(d.filename)} • ${new Date(d.uploadedAt).toLocaleString()}</div>
                </div>
                <div style="display:flex;gap:8px">
                    <button class="cp-btn cp-btn-ghost" onclick="downloadDoc(${d.id})">Download</button>
                    <button class="cp-btn cp-btn-ghost" onclick="deleteDoc(${d.id})" style="color:#ef4444;border-color:#fee">Delete</button>
                </div>
            </div>
        `).join('');
    }
    
    uploadBtn.addEventListener('click', async () => {
        if (!fileInput.files[0]) {
            alert('Please select a file');
            return;
        }
        
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
        
        try {
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            if (docTitle.value) formData.append('title', docTitle.value);
            
            await apiCall('documents', 'POST', formData);
            fileInput.value = '';
            docTitle.value = '';
            loadDocuments();
        } catch (error) {
            alert('Error uploading file: ' + error.message);
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload';
        }
    });
    
    window.downloadDoc = async function(id) {
        try {
            const data = await apiCall('documents/' + id + '/download');
            window.open(data.url, '_blank');
        } catch (error) {
            alert('Error downloading file: ' + error.message);
        }
    };
    
    window.deleteDoc = async function(id) {
        if (!confirm('Delete this document?')) return;
        try {
            await apiCall('documents/' + id, 'DELETE');
            loadDocuments();
        } catch (error) {
            alert('Error deleting document: ' + error.message);
        }
    };
    
    // Tickets
    const ticketList = document.getElementById('ticketList');
    const ticketForm = document.getElementById('ticketForm');
    
    async function loadTickets() {
        ticketList.innerHTML = '<div class="cp-loading">Loading tickets...</div>';
        try {
            const tickets = await apiCall('tickets');
            renderTickets(tickets);
        } catch (error) {
            ticketList.innerHTML = '<div class="cp-small">Error loading tickets</div>';
        }
    }
    
    function renderTickets(tickets) {
        if (tickets.length === 0) {
            ticketList.innerHTML = '<div class="cp-small">No tickets yet</div>';
            return;
        }
        
        ticketList.innerHTML = tickets.map(t => `
            <div class="cp-card">
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
                    <div style="font-weight:700">${escapeHtml(t.subject)}</div>
                    <span class="cp-chip" style="margin-left:auto">${t.priority.toUpperCase()}</span>
                    <span class="cp-chip">${t.status}</span>
                </div>
                <div class="cp-small" style="margin-bottom:10px">Created: ${new Date(t.createdAt).toLocaleString()}</div>
                <div style="margin-bottom:10px">
                    ${t.messages.map(m => `
                        <div style="margin-bottom:8px;padding:8px;background:#f9fafb;border-radius:6px">
                            <strong>${escapeHtml(m.author)}</strong> 
                            <span class="cp-small">• ${new Date(m.date).toLocaleString()}</span>
                            <div style="margin-top:4px">${escapeHtml(m.text)}</div>
                        </div>
                    `).join('')}
                </div>
                <div style="display:flex;gap:8px">
                    <input type="text" class="cp-input" placeholder="Reply..." id="reply-${t.id}" style="flex:1">
                    <button class="cp-btn" onclick="replyTicket(${t.id})">Reply</button>
                    <button class="cp-btn cp-btn-ghost" onclick="toggleTicket(${t.id}, '${t.status}')">
                        ${t.status === 'open' ? 'Close' : 'Reopen'}
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    ticketForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = ticketForm.querySelector('button');
        btn.disabled = true;
        btn.textContent = 'Creating...';
        
        try {
            const formData = new FormData(ticketForm);
            const data = Object.fromEntries(formData);
            await apiCall('tickets', 'POST', data);
            ticketForm.reset();
            loadTickets();
        } catch (error) {
            alert('Error creating ticket: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Create Ticket';
        }
    });
    
    window.replyTicket = async function(id) {
        const input = document.getElementById('reply-' + id);
        const message = input.value.trim();
        if (!message) {
            alert('Please enter a reply');
            return;
        }
        
        try {
            await apiCall('tickets/' + id + '/reply', 'POST', { message });
            loadTickets();
        } catch (error) {
            alert('Error sending reply: ' + error.message);
        }
    };
    
    window.toggleTicket = async function(id, currentStatus) {
        const newStatus = currentStatus === 'open' ? 'closed' : 'open';
        try {
            await apiCall('tickets/' + id, 'PUT', { status: newStatus });
            loadTickets();
        } catch (error) {
            alert('Error updating ticket: ' + error.message);
        }
    };
    
    // Invoices
    const invoiceList = document.getElementById('invoiceList');
    
    async function loadInvoices() {
        invoiceList.innerHTML = '<div class="cp-loading">Loading invoices...</div>';
        try {
            const invoices = await apiCall('invoices');
            renderInvoices(invoices);
        } catch (error) {
            invoiceList.innerHTML = '<div class="cp-small">Error loading invoices</div>';
        }
    }
    
    function renderInvoices(invoices) {
        if (invoices.length === 0) {
            invoiceList.innerHTML = '<div class="cp-small">No invoices yet</div>';
            return;
        }
        
        // Filter invoices based on search and status
        const statusFilter = document.getElementById('invoiceStatusFilter').value;
        const searchFilter = document.getElementById('invoiceSearch').value.toLowerCase();
        
        let filteredInvoices = invoices;
        
        if (statusFilter) {
            filteredInvoices = filteredInvoices.filter(inv => inv.status === statusFilter);
        }
        
        if (searchFilter) {
            filteredInvoices = filteredInvoices.filter(inv => 
                inv.memo.toLowerCase().includes(searchFilter) ||
                inv.id.toString().includes(searchFilter)
            );
        }
        
        if (filteredInvoices.length === 0) {
            invoiceList.innerHTML = '<div class="cp-small">No invoices match your filters</div>';
            return;
        }
        
        invoiceList.innerHTML = filteredInvoices.map(inv => {
            const remainingAmount = Number(inv.amount) - Number(inv.totalPaid || 0);
            const isOverdue = inv.dueAt && new Date(inv.dueAt) < new Date() && inv.status !== 'paid';
            const statusClass = isOverdue ? 'overdue' : inv.status;
            
            return `
                <div class="cp-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <h4 style="margin: 0; font-weight: 700;">Invoice #${inv.id}</h4>
                                ${inv.hasFile ? '<span title="Has attached file">📎</span>' : ''}
                            </div>
                            <div class="cp-small" style="margin-bottom: 8px;">
                                ${escapeHtml(inv.memo)} • Created ${new Date(inv.createdAt).toLocaleDateString()}
                                ${inv.dueAt ? ` • Due ${new Date(inv.dueAt).toLocaleDateString()}` : ''}
                            </div>
                            <div style="display: flex; gap: 12px; margin-bottom: 8px;">
                                <span class="cp-chip cp-status-${statusClass}">${inv.status.toUpperCase()}</span>
                                ${isOverdue ? '<span class="cp-chip" style="background: #fee2e2; color: #991b1b;">OVERDUE</span>' : ''}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 18px; font-weight: 700; color: var(--accent);">
                                $${Number(inv.amount).toFixed(2)}
                            </div>
                            ${inv.totalPaid > 0 ? `
                                <div class="cp-small" style="color: #059669;">
                                    Paid: $${Number(inv.totalPaid).toFixed(2)}
                                </div>
                                ${remainingAmount > 0 ? `
                                    <div class="cp-small" style="color: #dc2626;">
                                        Remaining: $${remainingAmount.toFixed(2)}
                                    </div>
                                ` : ''}
                            ` : ''}
                        </div>
                    </div>
                    
                    ${inv.description ? `<div class="cp-small" style="margin-bottom: 12px; color: var(--muted);">${escapeHtml(inv.description)}</div>` : ''}
                    
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <button class="cp-btn cp-btn-ghost" onclick="viewInvoiceDetails(${inv.id})">
                            View Details
                        </button>
                        ${inv.hasFile ? `
                            <button class="cp-btn cp-btn-ghost" onclick="downloadInvoiceFile(${inv.id})">
                                📎 Download
                            </button>
                        ` : ''}
                        <button class="cp-btn cp-btn-ghost" onclick="uploadInvoiceFile(${inv.id})">
                            📤 Upload File
                        </button>
                        ${remainingAmount > 0 ? `
                            <button class="cp-btn" onclick="recordPayment(${inv.id}, ${remainingAmount})">
                                Record Payment
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Dashboard
    async function loadDashboard() {
        try {
            const data = await apiCall('dashboard');
            const dashboard = document.getElementById('dashboard');
            dashboard.innerHTML = `
                <h2>Dashboard</h2>
                <hr>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px">
                    <div class="cp-card">
                        <div class="cp-small" style="margin-bottom:8px">Documents</div>
                        <div style="font-size:32px;font-weight:700;color:var(--accent)">${data.docCount}</div>
                    </div>
                    <div class="cp-card">
                        <div class="cp-small" style="margin-bottom:8px">Open Tickets</div>
                        <div style="font-size:32px;font-weight:700;color:var(--accent)">${data.openTickets}</div>
                    </div>
                    <div class="cp-card">
                        <div class="cp-small" style="margin-bottom:8px">Recent Documents</div>
                        <div class="cp-small">${data.recentDocs.join(', ') || '—'}</div>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }
    
    // Utility
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Invoice Modal Functions
    window.showCreateInvoiceModal = function() {
        document.getElementById('createInvoiceModal').style.display = 'flex';
    };
    
    window.closeCreateInvoiceModal = function() {
        document.getElementById('createInvoiceModal').style.display = 'none';
        document.getElementById('createInvoiceForm').reset();
    };
    
    window.closeInvoiceDetailsModal = function() {
        document.getElementById('invoiceDetailsModal').style.display = 'none';
    };
    
    window.closePaymentModal = function() {
        document.getElementById('paymentModal').style.display = 'none';
        document.getElementById('paymentForm').reset();
    };
    
    // Create Invoice Form
    document.getElementById('createInvoiceForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.textContent = 'Creating...';
        
        try {
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            await apiCall('invoices', 'POST', data);
            closeCreateInvoiceModal();
            loadInvoices();
        } catch (error) {
            alert('Error creating invoice: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Create Invoice';
        }
    });
    
    // Payment Form
    document.getElementById('paymentForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const invoiceId = document.getElementById('paymentInvoiceId').value;
        btn.disabled = true;
        btn.textContent = 'Recording...';
        
        try {
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            await apiCall('invoices/' + invoiceId + '/payment', 'POST', data);
            closePaymentModal();
            loadInvoices();
        } catch (error) {
            alert('Error recording payment: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Record Payment';
        }
    });
    
    // Invoice Actions
    window.viewInvoiceDetails = async function(id) {
        try {
            const invoice = await apiCall('invoices/' + id);
            const modal = document.getElementById('invoiceDetailsModal');
            const title = document.getElementById('invoiceModalTitle');
            const content = document.getElementById('invoiceDetailsContent');
            
            title.textContent = `Invoice #${invoice.id} - ${invoice.memo}`;
            
            const remainingAmount = Number(invoice.amount) - Number(invoice.totalPaid || 0);
            const isOverdue = invoice.dueAt && new Date(invoice.dueAt) < new Date() && invoice.status !== 'paid';
            const statusClass = isOverdue ? 'overdue' : invoice.status;
            
            content.innerHTML = `
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <h4>Invoice Information</h4>
                            <p><strong>Amount:</strong> $${Number(invoice.amount).toFixed(2)}</p>
                            <p><strong>Status:</strong> <span class="cp-chip cp-status-${statusClass}">${invoice.status.toUpperCase()}</span></p>
                            <p><strong>Created:</strong> ${new Date(invoice.createdAt).toLocaleDateString()}</p>
                            ${invoice.dueAt ? `<p><strong>Due Date:</strong> ${new Date(invoice.dueAt).toLocaleDateString()}</p>` : ''}
                            ${isOverdue ? '<p style="color: #dc2626;"><strong>⚠️ OVERDUE</strong></p>' : ''}
                        </div>
                        <div>
                            <h4>Payment Summary</h4>
                            <p><strong>Total Paid:</strong> $${Number(invoice.totalPaid || 0).toFixed(2)}</p>
                            <p><strong>Remaining:</strong> $${remainingAmount.toFixed(2)}</p>
                            ${invoice.hasFile ? '<p><strong>📎 File Attached:</strong> <a href="#" onclick="downloadInvoiceFile(' + invoice.id + ')">Download</a></p>' : ''}
                        </div>
                    </div>
                    
                    ${invoice.description ? `<div style="margin-bottom: 20px;"><h4>Description</h4><p>${escapeHtml(invoice.description)}</p></div>` : ''}
                    
                    <div style="margin-bottom: 20px;">
                        <h4>Payment History</h4>
                        ${invoice.paymentHistory && invoice.paymentHistory.length > 0 ? `
                            <div>
                                ${invoice.paymentHistory.map(payment => `
                                    <div class="cp-payment-item">
                                        <div>
                                            <div class="cp-payment-method">${payment.method}</div>
                                            <div class="cp-small">${new Date(payment.date).toLocaleString()}</div>
                                            ${payment.notes ? `<div class="cp-small">${escapeHtml(payment.notes)}</div>` : ''}
                                        </div>
                                        <div class="cp-payment-amount">$${Number(payment.amount).toFixed(2)}</div>
                                    </div>
                                `).join('')}
                            </div>
                        ` : '<p class="cp-small">No payments recorded yet.</p>'}
                    </div>
                    
                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button class="cp-btn cp-btn-ghost" onclick="uploadInvoiceFile(${invoice.id})">
                            📤 Upload File
                        </button>
                        ${remainingAmount > 0 ? `
                            <button class="cp-btn" onclick="recordPayment(${invoice.id}, ${remainingAmount})">
                                Record Payment
                            </button>
                        ` : ''}
                        <button class="cp-btn cp-btn-ghost" onclick="closeInvoiceDetailsModal()">
                            Close
                        </button>
                    </div>
                </div>
            `;
            
            modal.style.display = 'flex';
        } catch (error) {
            alert('Error loading invoice details: ' + error.message);
        }
    };
    
    window.downloadInvoiceFile = async function(id) {
        try {
            const response = await apiCall('invoices/' + id + '/download');
            window.open(response.url, '_blank');
        } catch (error) {
            alert('Error downloading file: ' + error.message);
        }
    };
    
    window.uploadInvoiceFile = function(id) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.pdf,.jpg,.jpeg,.png,.doc,.docx';
        input.onchange = async function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (file.size > 10 * 1024 * 1024) {
                alert('File size must be less than 10MB');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('file', file);
                await apiCall('invoices/' + id + '/upload', 'POST', formData);
                alert('File uploaded successfully!');
                loadInvoices();
            } catch (error) {
                alert('Error uploading file: ' + error.message);
            }
        };
        input.click();
    };
    
    window.recordPayment = function(id, maxAmount) {
        document.getElementById('paymentInvoiceId').value = id;
        const amountInput = document.getElementById('paymentForm').querySelector('input[name="amount"]');
        amountInput.max = maxAmount;
        amountInput.placeholder = `Payment amount (max: $${maxAmount.toFixed(2)})`;
        document.getElementById('paymentModal').style.display = 'flex';
    };
    
    // Filter Events
    document.getElementById('invoiceStatusFilter').addEventListener('change', () => {
        loadInvoices();
    });
    
    document.getElementById('invoiceSearch').addEventListener('input', () => {
        loadInvoices();
    });
    
    // Initialize
    loadProfile();
    loadDashboard();
    setupMobileDrawer();
})();

// Mobile Drawer Controls
function setupMobileDrawer() {
    const btn = document.getElementById('cpMenuBtn');
    const sidebar = document.getElementById('cpSidebar');
    const overlay = document.getElementById('cpOverlay');
    if (!btn || !sidebar || !overlay) return;

    btn.addEventListener('click', () => {
        const isOpen = sidebar.classList.contains('open');
        if (isOpen) {
            sidebar.classList.remove('open');
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        } else {
            sidebar.classList.add('open');
            overlay.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    });

    overlay.addEventListener('click', () => {
        closeMobileDrawer();
    });
}

function closeMobileDrawer() {
    const sidebar = document.getElementById('cpSidebar');
    const overlay = document.getElementById('cpOverlay');
    if (!sidebar || !overlay) return;
    if (sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        overlay.style.display = 'none';
        document.body.style.overflow = '';
    }
}