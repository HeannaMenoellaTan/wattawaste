<?php
session_start();
include('db.php');

// ✅ Restrict page to Admins only
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.html");
    exit();
}

// Fetch all users
$query = "SELECT * FROM users";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin | Manage Users</title>
<?php include 'notif_bell.php';?>
   <!-- Bootstrap -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
   <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
   <style>
    /* Sidebar */


/* Main content */
.main-content {
  margin-left: 260px;
  padding: 40px 60px;
}


.main-content h2 {
  font-weight: 700;
  font-size: 26px;
  margin-bottom: 25px;
}
/* Table Styling - Match Uploaded Image */
/* Table Styling - Match Uploaded Image, Larger Size */
.table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 10px;
  font-family: 'Poppins', sans-serif;
  font-size: 16px; /* larger text */
}

/* Header Styling */
.table thead th {
  background-color: #8e8e8e; /* gray headers */
  color: white;
  font-weight: 500;
  text-align: center;
  padding: 14px 12px; /* taller headers */
  border: none;
  border-radius: 8px 8px 0 0;
  font-size: 15px;
}

/* Row Styling */
.table tbody tr {
  background-color: #f9f9f9;
  border-radius: 10px;
  height: 60px; /* makes rows taller */
}

.table tbody tr td {
  text-align: center;
  padding: 14px 12px;
  vertical-align: middle;
  border-top: none;
  border-bottom: none;
  font-size: 15px;
}

/* Alternating Row Colors */
.table tbody tr:nth-child(even) {
  background-color: #ededed;
}

/* Rounded table container */
.table-responsive {
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  padding: 15px;
  background: #fff;
}


/* Buttons */
.btn-add {
  background-color: #52734D;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
}

.btn-add:hover {
  background-color: #3e5e3c;
}
/* Action Buttons */
.btn-edit {
  background-color: transparent;
  color: #333;
  border: none;
  font-weight: 500;
  padding: 6px 14px;
  transition: 0.3s;
  font-size: 15px;
}

.btn-edit:hover {
  color: #52734D;
  text-decoration: underline;
}

.btn-delete {
  background-color: #e74c3c;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 6px 14px;
  font-weight: 500;
  transition: 0.3s;
  font-size: 15px;
}

.btn-delete:hover {
  background-color: #c0392b;
}

/* Pagination (bottom) */
.pagination {
  display: flex;
  justify-content: flex-start;
  align-items: center;
  gap: 12px;
  margin-top: 25px;
}

.pagination a,
.pagination span {
  color: #777;
  text-decoration: none;
  font-size: 14px;
}

.pagination a:hover {
  text-decoration: underline;
}

   </style>
</head>
<body>

<?php include 'sidebar.php'; ?>


  <div class="main-content">
    <?php include 'topnav.php';?>
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-dark">USER MANAGEMENT</h2>
      <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#userModal">+ Add New User</button>
    </div>

    <div class="input-group mb-3 search-box"> <span class="input-group-text bg-white"><i class="bi bi-search"></i></span> <input type="text" id="searchInput" class="form-control" placeholder="Search user..."> </div>
    <!-- Table -->
    <div class="table-responsive shadow-sm rounded bg-white p-3">
      <table class="table align-middle" id="userTable">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Role</th>
            <th>Activity</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
          <tr>
            <td><?= htmlspecialchars($row['Username']) ?></td>
            <td><?= htmlspecialchars($row['Email']) ?></td>
            <td><?= htmlspecialchars($row['Status']) ?></td>
            <td><?= htmlspecialchars($row['Role']) ?></td>
            <td><?= htmlspecialchars($row['Activity']) ?></td>
            <td>
              <button class="btn btn-sm btn-edit"
                      data-bs-toggle="modal"
                      data-bs-target="#userModal"
                      data-id="<?= $row['User_Id'] ?>"
                      data-name="<?= $row['Username'] ?>"
                      data-email="<?= $row['Email'] ?>"
                      data-role="<?= $row['Role'] ?>"
                      data-status="<?= $row['Status'] ?>"
                      data-activity="<?= $row['Activity'] ?>">
                      Edit
              </button>
              <a href="delete_user.php?id=<?= $row['User_Id'] ?>" class="btn btn-sm btn-delete">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form action="save_user.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title" id="userModalLabel">Add / Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="User_Id" id="user_id">

            <div class="mb-3">
              <label>Username</label>
              <input type="text" class="form-control" name="Username" id="username" required>
            </div>

            <div class="mb-3">
              <label>Email</label>
              <input type="email" class="form-control" name="Email" id="email" required>
            </div>

            <div class="mb-3">
              <label>Password</label>
              <input type="password" class="form-control" name="Password" id="password">
              <small class="text-muted">Leave blank to keep current password</small>
            </div>

            <div class="mb-3">
              <label>Role</label>
              <select name="Role" id="role" class="form-select">
                <option value="Admin">Admin</option>
                <option value="User">User</option>
              </select>
            </div>

            <div class="mb-3">
              <label>Status</label>
              <select name="Status" id="status" class="form-select">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>

            <div class="mb-3">
              <label>Activity</label>
              <input type="text" class="form-control" name="Activity" id="activity">
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-add w-100">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Populate modal when editing
    const userModal = document.getElementById('userModal');
    userModal.addEventListener('show.bs.modal', event => {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      const name = button.getAttribute('data-name');
      const email = button.getAttribute('data-email');
      const role = button.getAttribute('data-role');
      const status = button.getAttribute('data-status');
      const activity = button.getAttribute('data-activity');

      document.getElementById('user_id').value = id || '';
      document.getElementById('username').value = name || '';
      document.getElementById('email').value = email || '';
      document.getElementById('role').value = role || 'User';
      document.getElementById('status').value = status || 'Active';
      document.getElementById('activity').value = activity || '';
    });

    // Search Filter
    document.getElementById('searchInput').addEventListener('keyup', function() {
      const filter = this.value.toLowerCase();
      const rows = document.querySelectorAll('#userTable tbody tr');

      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
