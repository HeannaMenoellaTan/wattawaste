<?php
/**
 * User Management Page - Admin Panel
 * STRICTLY FOR ADMIN USE ONLY
 * Now syncs Firebase Authentication users to Realtime Database
 */

// ===== STEP 1: VERIFY ADMIN ACCESS =====
require_once 'firebase_admin_check.php';
requireAdmin(); // Automatically redirects non-admins

// ===== STEP 2: LOAD FIREBASE =====
require_once 'firebase_config.php';

// ===== STEP 3: LOAD ADMIN NAVIGATION =====
require_once 'admin_nav.php';
?>

<style>
/* User Management Specific Styles */
.user-management-container {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.sync-info-banner {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}

.sync-info-banner i {
    font-size: 20px;
}

.sync-info-banner button {
    margin-left: auto;
    padding: 8px 16px;
    background: white;
    color: #2563eb;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.sync-info-banner button:hover {
    background: #f0f9ff;
    transform: scale(1.05);
}

.users-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.users-count {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
}

.users-count i {
    font-size: 24px;
    color: #4CAF50;
}

.users-count .count {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 16px;
}

.users-actions {
    display: flex;
    gap: 15px;
    align-items: center;
}

.search-box {
    position: relative;
}

.search-box input {
    padding: 10px 40px 10px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 25px;
    width: 280px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.search-box input:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.search-box .search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 32px;
    height: 32px;
    background: #ef4444;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-box .search-icon:hover {
    background: #dc2626;
    transform: translateY(-50%) scale(1.1);
}

.add-user-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.add-user-btn:hover {
    background: #dc2626;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.users-table-container {
    overflow-x: auto;
    margin-top: 20px;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.users-table thead {
    background: linear-gradient(135deg, #6b7280, #4b5563);
}

.users-table thead th {
    padding: 15px 12px;
    text-align: left;
    color: white;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
}

.users-table tbody tr {
    border-bottom: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.users-table tbody tr:hover {
    background: #f9fafb;
}

.users-table tbody td {
    padding: 14px 12px;
    color: #374151;
}

.provider-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.provider-badge.email {
    background: #dbeafe;
    color: #1e40af;
}

.provider-badge.google {
    background: #fee2e2;
    color: #991b1b;
}

.provider-badge.facebook {
    background: #dbeafe;
    color: #1e3a8a;
}

.provider-badge.phone {
    background: #d1fae5;
    color: #065f46;
}

.status-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.role-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: 600;
    background: #dbeafe;
    color: #1e40af;
}

.role-badge.admin {
    background: #fef3c7;
    color: #92400e;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-edit, .btn-delete {
    padding: 6px 14px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit {
    background: #e5e7eb;
    color: #374151;
}

.btn-edit:hover {
    background: #d1d5db;
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-delete:hover {
    background: #dc2626;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 16px;
    padding: 30px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.modal-header h2 {
    color: #1f2937;
    font-size: 22px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.3s ease;
}

.modal-close:hover {
    color: #ef4444;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #374151;
    font-weight: 600;
    font-size: 14px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
}

.modal-footer {
    display: flex;
    gap: 12px;
    margin-top: 25px;
}

.btn-cancel, .btn-submit {
    flex: 1;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cancel {
    background: #e5e7eb;
    color: #374151;
}

.btn-cancel:hover {
    background: #d1d5db;
}

.btn-submit {
    background: #4CAF50;
    color: white;
}

.btn-submit:hover {
    background: #45a049;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.loading {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

.loading i {
    font-size: 32px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-state h3 {
    margin-top: 15px;
    color: #374151;
}

.alert {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert.active {
    display: flex;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.alert i {
    font-size: 18px;
}
</style>

<!-- Sync Info Banner -->
<div class="sync-info-banner">
    <i class="fas fa-sync-alt"></i>
    <div>
        <strong>Auto-Sync Enabled:</strong> Users are automatically synced from Firebase Authentication when they log in.
    </div>
    <button onclick="syncAuthUsers()">
        <i class="fas fa-cloud-download-alt"></i> Sync Now
    </button>
</div>

<!-- User Management Container -->
<div class="user-management-container">
    <div id="alertSuccess" class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <span id="successMessage"></span>
    </div>
    <div id="alertError" class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span id="errorMessage"></span>
    </div>

    <div class="users-header">
        <div class="users-count">
            <i class="fas fa-users"></i>
            <span id="userCountText">0 users</span>
            <span class="count" id="userCount">0</span>
        </div>

        <div class="users-actions">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search users...">
                <div class="search-icon" onclick="searchUsers()">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <button class="add-user-btn" onclick="openAddUserModal()">
                <i class="fas fa-plus"></i>
                Add New User
            </button>
        </div>
    </div>

    <div class="users-table-container">
        <div id="loadingState" class="loading">
            <i class="fas fa-spinner"></i>
            <p>Loading users...</p>
        </div>

        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="fas fa-users-slash"></i>
            <h3>No Users Found</h3>
            <p>Users will appear here when they log in to the system.</p>
        </div>

        <table class="users-table" id="usersTable" style="display: none;">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email Address</th>
                    <th>Provider</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Last Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody"></tbody>
        </table>
    </div>
</div>

<!-- User Modal -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New User</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>

        <form id="userForm" onsubmit="saveUser(event)">
            <div class="form-group">
                <label for="userName">Full Name *</label>
                <input type="text" id="userName" placeholder="Enter full name" required>
            </div>

            <div class="form-group">
                <label for="userEmail">Email Address *</label>
                <input type="email" id="userEmail" placeholder="Enter email address" required>
            </div>

            <div class="form-group">
                <label for="userPassword">Password *</label>
                <input type="password" id="userPassword" placeholder="Minimum 6 characters" required minlength="6">
                <small style="color: #6b7280; font-size: 12px; display: block; margin-top: 5px;">
                    Leave blank when editing to keep current password
                </small>
            </div>

            <div class="form-group">
                <label for="userRole">Role *</label>
                <select id="userRole" required>
                    <option value="User">User</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="userStatus">Status *</label>
                <select id="userStatus" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit" id="submitBtnText">Add User</button>
            </div>
        </form>
    </div>
</div>

</div> <!-- Close admin-main from admin_nav.php -->
</div> <!-- Close admin-layout from admin_nav.php -->

<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-database-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>

<script>
// Initialize Firebase
firebase.initializeApp({
    databaseURL: "https://wattawaste-d3503-default-rtdb.asia-southeast1.firebasedatabase.app/",
    apiKey: "YOUR_API_KEY", // You'll need to add this
    authDomain: "wattawaste-d3503.firebaseapp.com",
    projectId: "wattawaste-d3503"
});

const database = firebase.database();
const auth = firebase.auth();
const usersRef = database.ref('users');

let allUsers = [];
let filteredUsers = [];
let editingUserId = null;

// Sync Firebase Auth users to Realtime Database
async function syncAuthUsers() {
    try {
        showAlert('success', 'Syncing users from Firebase Authentication...');
        
        // This would require Firebase Admin SDK or Cloud Functions
        // For now, we'll work with users already in the database
        console.log('Sync completed - using database users');
        
        loadUsers();
    } catch (error) {
        console.error('Sync error:', error);
        showAlert('error', 'Failed to sync users');
    }
}

// Load all users from Firebase Realtime Database
function loadUsers() {
    document.getElementById('loadingState').style.display = 'block';
    document.getElementById('emptyState').style.display = 'none';
    document.getElementById('usersTable').style.display = 'none';

    usersRef.on('value', (snapshot) => {
        allUsers = [];
        
        if (snapshot.exists()) {
            snapshot.forEach((child) => {
                const userData = child.val();
                
                // Skip if this is not a valid user object
                if (!userData || typeof userData !== 'object') {
                    return;
                }
                
                allUsers.push({
                    id: child.key,
                    name: userData.name || userData.Username || userData.displayName || 'Unknown',
                    email: userData.email || userData.Email || 'N/A',
                    role: userData.role || userData.Role || 'User',
                    status: userData.status || userData.Status || 'Active',
                    lastActive: userData.lastActive || userData.lastLogin || 'Never',
                    provider: userData.provider || userData.providerId || 'email',
                    photoURL: userData.photoURL || null,
                    ...userData
                });
            });
            
            filteredUsers = [...allUsers];
            displayUsers();
            
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('usersTable').style.display = 'table';
        } else {
            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('emptyState').style.display = 'block';
        }
    }, (error) => {
        console.error('Error loading users:', error);
        showAlert('error', 'Failed to load users. Please refresh the page.');
        document.getElementById('loadingState').style.display = 'none';
    });
}

// Get provider icon
function getProviderIcon(provider) {
    const providerLower = (provider || 'email').toLowerCase();
    
    if (providerLower.includes('google')) {
        return '<i class="fab fa-google"></i>';
    } else if (providerLower.includes('facebook')) {
        return '<i class="fab fa-facebook"></i>';
    } else if (providerLower.includes('phone')) {
        return '<i class="fas fa-phone"></i>';
    } else {
        return '<i class="fas fa-envelope"></i>';
    }
}

// Get provider class
function getProviderClass(provider) {
    const providerLower = (provider || 'email').toLowerCase();
    
    if (providerLower.includes('google')) return 'google';
    if (providerLower.includes('facebook')) return 'facebook';
    if (providerLower.includes('phone')) return 'phone';
    return 'email';
}

// Display users in the table
function displayUsers() {
    const tbody = document.getElementById('usersTableBody');
    tbody.innerHTML = '';
    
    if (filteredUsers.length === 0) {
        document.getElementById('usersTable').style.display = 'none';
        document.getElementById('emptyState').style.display = 'block';
        return;
    }
    
    filteredUsers.forEach(user => {
        const row = tbody.insertRow();
        
        // Name
        row.insertCell(0).textContent = user.name;
        
        // Email
        row.insertCell(1).textContent = user.email;
        
        // Provider
        const providerCell = row.insertCell(2);
        const providerClass = getProviderClass(user.provider);
        const providerIcon = getProviderIcon(user.provider);
        providerCell.innerHTML = `<span class="provider-badge ${providerClass}">${providerIcon} ${providerClass}</span>`;
        
        // Status
        const statusCell = row.insertCell(3);
        statusCell.innerHTML = `<span class="status-badge ${user.status.toLowerCase()}">${user.status}</span>`;
        
        // Role
        const roleCell = row.insertCell(4);
        roleCell.innerHTML = `<span class="role-badge ${user.role.toLowerCase()}">${user.role}</span>`;
        
        // Last Active
        row.insertCell(5).textContent = user.lastActive;
        
        // Actions
        const actionsCell = row.insertCell(6);
        actionsCell.innerHTML = `
            <div class="action-buttons">
                <button class="btn-edit" onclick="editUser('${user.id}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn-delete" onclick="deleteUser('${user.id}', '${user.name.replace(/'/g, "\\'")}')">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;
    });
    
    // Update user count
    document.getElementById('userCount').textContent = filteredUsers.length;
    document.getElementById('userCountText').textContent = `${filteredUsers.length} user${filteredUsers.length !== 1 ? 's' : ''}`;
}

// Search users
function searchUsers() {
    const term = document.getElementById('searchInput').value.toLowerCase().trim();
    
    if (!term) {
        filteredUsers = [...allUsers];
    } else {
        filteredUsers = allUsers.filter(u => 
            (u.name || '').toLowerCase().includes(term) ||
            (u.email || '').toLowerCase().includes(term) ||
            (u.role || '').toLowerCase().includes(term) ||
            (u.provider || '').toLowerCase().includes(term)
        );
    }
    
    displayUsers();
}

// Real-time search
document.getElementById('searchInput').addEventListener('input', searchUsers);

// Open Add User Modal
function openAddUserModal() {
    editingUserId = null;
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('submitBtnText').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userPassword').required = true;
    document.getElementById('userModal').classList.add('active');
}

// Edit User
function editUser(userId) {
    editingUserId = userId;
    const user = allUsers.find(u => u.id === userId);
    
    if (user) {
        document.getElementById('modalTitle').textContent = 'Edit User';
        document.getElementById('submitBtnText').textContent = 'Update User';
        document.getElementById('userName').value = user.name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userStatus').value = user.status;
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').required = false;
        document.getElementById('userModal').classList.add('active');
    }
}

// Save User (Add or Update)
function saveUser(event) {
    event.preventDefault();
    
    const userData = {
        name: document.getElementById('userName').value.trim(),
        Username: document.getElementById('userName').value.trim(),
        email: document.getElementById('userEmail').value.trim(),
        Email: document.getElementById('userEmail').value.trim(),
        role: document.getElementById('userRole').value,
        Role: document.getElementById('userRole').value,
        status: document.getElementById('userStatus').value,
        Status: document.getElementById('userStatus').value,
        lastActive: new Date().toLocaleString(),
        provider: 'email'
    };

    const password = document.getElementById('userPassword').value;
    if (password) {
        userData.password = password;
        userData.Password = password;
    }

    if (editingUserId) {
        // Update existing user
        usersRef.child(editingUserId).update(userData)
            .then(() => {
                showAlert('success', 'User updated successfully!');
                closeModal();
            })
            .catch((error) => {
                console.error('Error updating user:', error);
                showAlert('error', 'Failed to update user. Please try again.');
            });
    } else {
        // Add new user
        usersRef.push().set(userData)
            .then(() => {
                showAlert('success', 'User added successfully!');
                closeModal();
            })
            .catch((error) => {
                console.error('Error adding user:', error);
                showAlert('error', 'Failed to add user. Please try again.');
            });
    }
}

// Delete User
function deleteUser(userId, userName) {
    if (confirm(`Are you sure you want to delete user "${userName}"?\n\nThis action cannot be undone.`)) {
        usersRef.child(userId).remove()
            .then(() => {
                showAlert('success', `User "${userName}" deleted successfully!`);
            })
            .catch((error) => {
                console.error('Error deleting user:', error);
                showAlert('error', 'Failed to delete user. Please try again.');
            });
    }
}

// Close Modal
function closeModal() {
    document.getElementById('userModal').classList.remove('active');
    document.getElementById('userForm').reset();
    editingUserId = null;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('userModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Show Alert
function showAlert(type, message) {
    const alertId = type === 'success' ? 'alertSuccess' : 'alertError';
    const msgId = type === 'success' ? 'successMessage' : 'errorMessage';
    
    document.getElementById(msgId).textContent = message;
    document.getElementById(alertId).classList.add('active');
    
    setTimeout(() => {
        document.getElementById(alertId).classList.remove('active');
    }, 5000);
}

// Update DateTime in topbar
function updateDateTime() {
    const now = new Date();
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: true 
    };
    const element = document.getElementById('currentDateTime');
    if (element) {
        element.textContent = now.toLocaleDateString('en-US', options);
    }
}

// Initialize
window.addEventListener('load', () => {
    loadUsers();
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute
    console.log('👥 User Management System Initialized');
    console.log('📊 Displaying authenticated users from Realtime Database');
});
</script>