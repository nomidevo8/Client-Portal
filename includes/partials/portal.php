<div class="cp-wrapper" id="cpApp">
    <div class="cp-header">
        <div>
            <button id="cpMenuBtn" class="cp-mobile-menu" aria-label="Open menu" title="Open menu"
                style="display:none">☰</button>
            <h1>Client Portal</h1>
            <div class="cp-small">Welcome, <?php echo esc_html($user->display_name); ?></div>
        </div>
        <div>
            <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="cp-btn cp-btn-ghost">Logout</a>
        </div>
    </div>

    <div class="cp-grid">
        <nav id="cpSidebar" class="cp-nav">
            <div class="cp-card cp-menu">
                <button class="cp-tab-btn active" data-tab="dashboard">Dashboard</button>
                <button class="cp-tab-btn" data-tab="profile">Profile</button>
                <button class="cp-tab-btn" data-tab="documents">Documents</button>
                <button class="cp-tab-btn" data-tab="messages">Messages</button>
                <button class="cp-tab-btn" data-tab="invoices">Invoices</button>
                <a href="<?php echo wp_logout_url(get_permalink()); ?>" class="cp-tab-btn cp-logout-mobile-only" style="display:none">Logout</a>
            </div>
        </nav>

        <main>
            <!-- Dashboard -->
            <section id="dashboard" class="cp-card cp-view active">
                <h2>Dashboard</h2>
                <div class="cp-loading">Loading...</div>
            </section>

            <!-- Profile -->
            <section id="profile" class="cp-card cp-view">
                <h2>Profile</h2>
                <hr>
                <form id="profileForm">
                    <div class="cp-form-row">
                        <input class="cp-input" name="fullName" placeholder="Full name" />
                        <input class="cp-input" name="email" placeholder="Email" type="email" />
                    </div>
                    <div class="cp-form-row">
                        <input class="cp-input" name="phone" placeholder="Phone" />
                        <input class="cp-input" name="company" placeholder="Company" />
                    </div>
                    <div style="margin-bottom:10px">
                        <textarea class="cp-textarea" name="address" placeholder="Address"></textarea>
                    </div>
                    <button type="submit" class="cp-btn">Save Profile</button>
                </form>
            </section>

            <!-- Documents -->
            <section id="documents" class="cp-card cp-view">
                <h2>Documents</h2>
                <hr>
                <div style="display:flex;gap:12px;margin-bottom:12px;flex-wrap:wrap;">
                    <input id="fileInput" type="file" />
                    <input id="docTitle" placeholder="Optional title" class="cp-input" style="flex:1;" />
                    <button id="uploadBtn" class="cp-btn">Upload</button>
                </div>
                <div class="cp-list" id="docList">
                    <div class="cp-loading">Loading documents...</div>
                </div>
            </section>

            <!-- Messages/Tickets -->
            <section id="messages" class="cp-card cp-view">
                <h2>Support Tickets</h2>
                <hr>
                <form id="ticketForm" style="margin-bottom:12px">
                    <div class="cp-form-row">
                        <input name="subject" class="cp-input" placeholder="Ticket subject" required />
                        <select name="priority" class="cp-select" style="max-width:150px">
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <textarea name="message" class="cp-textarea" placeholder="Describe the issue..."
                        required></textarea>
                    <button type="submit" class="cp-btn" style="margin-top:8px">Create Ticket</button>
                </form>
                <div class="cp-list" id="ticketList">
                    <div class="cp-loading">Loading tickets...</div>
                </div>
            </section>

            <!-- Invoices -->
            <section id="invoices" class="cp-card cp-view">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                    <h2>Invoices</h2>
                    <button class="cp-btn" onclick="showCreateInvoiceModal()">
                        <span style="margin-right: 8px;">+</span>Create Invoice
                    </button>
                </div>
                <hr>

                <!-- Invoice Filters -->
                <div style="display: flex; gap: 12px; margin-bottom: 18px; flex-wrap: wrap;">
                    <select id="invoiceStatusFilter" class="cp-select" style="max-width: 300px;">
                        <option value="">All Status</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <input type="text" id="invoiceSearch" class="cp-input" placeholder="Search invoices..."
                        style="max-width: 200px;">
                    <button class="cp-btn cp-btn-ghost" onclick="loadInvoices()">Refresh</button>
                </div>

                <div class="cp-list" id="invoiceList">
                    <div class="cp-loading">Loading invoices...</div>
                </div>
            </section>
        </main>
    </div>
    <div id="cpOverlay" class="cp-overlay" style="display:none"></div>
</div>

<!-- Create Invoice Modal -->
<div id="createInvoiceModal" class="cp-modal" style="display: none;">
    <div class="cp-modal-content">
        <div class="cp-modal-header">
            <h3>Create New Invoice</h3>
            <button class="cp-modal-close" onclick="closeCreateInvoiceModal()">&times;</button>
        </div>
        <form id="createInvoiceForm">
            <div class="cp-form-row">
                <input name="memo" class="cp-input" placeholder="Invoice title/memo" required />
                <input name="amount" type="number" step="0.01" class="cp-input" placeholder="Amount ($)" required />
            </div>
            <div class="cp-form-row">
                <input name="dueDate" type="date" class="cp-input" />
                <select name="status" class="cp-select">
                    <option value="unpaid">Unpaid</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <textarea name="description" class="cp-textarea" placeholder="Invoice description (optional)"></textarea>
            <div class="cp-modal-footer">
                <button type="button" class="cp-btn cp-btn-ghost" onclick="closeCreateInvoiceModal()">Cancel</button>
                <button type="submit" class="cp-btn">Create Invoice</button>
            </div>
        </form>
    </div>
</div>

<!-- Invoice Details Modal -->
<div id="invoiceDetailsModal" class="cp-modal" style="display: none;">
    <div class="cp-modal-content cp-modal-large">
        <div class="cp-modal-header">
            <h3 id="invoiceModalTitle">Invoice Details</h3>
            <button class="cp-modal-close" onclick="closeInvoiceDetailsModal()">&times;</button>
        </div>
        <div id="invoiceDetailsContent">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="cp-modal" style="display: none;">
    <div class="cp-modal-content">
        <div class="cp-modal-header">
            <h3>Record Payment</h3>
            <button class="cp-modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <form id="paymentForm">
            <input type="hidden" id="paymentInvoiceId" />
            <div class="cp-form-row">
                <input name="amount" type="number" step="0.01" class="cp-input" placeholder="Payment amount ($)"
                    required />
                <select name="method" class="cp-select">
                    <option value="Cash">Cash</option>
                    <option value="Check">Check</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                    <option value="Credit Card">Credit Card</option>
                    <option value="PayPal">PayPal</option>
                    <option value="Stripe">Stripe</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <textarea name="notes" class="cp-textarea" placeholder="Payment notes (optional)"></textarea>
            <div class="cp-modal-footer">
                <button type="button" class="cp-btn cp-btn-ghost" onclick="closePaymentModal()">Cancel</button>
                <button type="submit" class="cp-btn">Record Payment</button>
            </div>
        </form>
    </div>
</div>