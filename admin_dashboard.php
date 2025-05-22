<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['id'])) {
    header("Location: signin.php");
    exit();
}

// Get user full name from session
$user = [
    'fullname' => $_SESSION['user_fullname'] 
        ?? $_SESSION['admin_name'] 
        ?? 'Kafea Kiosk'
];

// Include database connection
include('connection/config.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $is_available = (int) $_POST['is_available'];
    $image_path = '';
    $edit_id = isset($_POST['edit_id']) ? (int) $_POST['edit_id'] : 0;


    // Handle image upload
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $target_dir = "uploads/menu_items/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        $check = getimagesize($_FILES['image']['tmp_name']);
        if ($check !== false && in_array($file_extension, $allowed_extensions)) {
            $filename = uniqid('', true) . '.' . $file_extension;
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            }
        }
    }
    if ($edit_id > 0) {
        // Update mode
        if (!empty($image_path)) {
            $stmt = $conn->prepare("UPDATE menu_items SET item_name=?, description=?, price=?, image_path=?, is_available=? WHERE id=?");
            $stmt->bind_param("ssdsii", $item_name, $description, $price, $image_path, $is_available, $edit_id);
        } else {
            $stmt = $conn->prepare("UPDATE menu_items SET item_name=?, description=?, price=?, is_available=? WHERE id=?");
            $stmt->bind_param("ssdii", $item_name, $description, $price, $is_available, $edit_id);
        }
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Menu item updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding menu item: " . $conn->error;
        }
        
        $stmt->close();
    } else {
    // Insert into database
   $stmt = $conn->prepare("INSERT INTO menu_items (item_name, description, price, image_path, is_available, category) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssdsi", $item_name, $description, $price, $image_path, $is_available, $category);

        if ($stmt->execute()) {
            $success_message = "Menu item added successfully!";
        } else {
            $error_message = "Error adding menu item: " . $conn->error;
        }
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']); // Prevent form resubmission
    exit();
}



// Fetch menu items
$menu_items = [];
$result = $conn->query("SELECT * FROM menu_items ORDER BY id DESC");
if ($result) {
    $menu_items = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
$edit_mode = false;
$edit_item = [
    'id' => '',
    'item_name' => '',
    'description' => '',
    'price' => '',
    'is_available' => 1
];

if (isset($_GET['edit'])) {
    $edit_id = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $edit_item = $result->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}

if (isset($_GET['delete'])) {
    $delete_id = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success_message = "Menu item deleted successfully!";
    } else {
        $error_message = "Error deleting item: " . $conn->error;
    }
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kafèa-Kiosk | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        :root {
            --primary-color: #6F4E37;
            --secondary-color: #C4A484;
            --light-color: #F5F5DC;
            --dark-color: #3E2723;
            --accent-color: #D2B48C;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand img {
            height: 50px;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--accent-color);
        }
        
       .container {
    max-width: 1400px;
    width: 95%;
    margin: 2rem auto;
    padding: 0 2rem;
    box-sizing: border-box;
}

        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .profile-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
        }

        .username {
            font-weight: 500;
        }

        .user-icon {
            font-size: 1.2rem;
            color: white;
            margin-right: 8px;
        }

        .logout-btn i {
            margin-right: 5px;
        }

        .logout-btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
            cursor: pointer;
            border: none;
        }

        .logout-btn:hover {
            background-color: var(--accent-color);
        }
        
        /* Form styles */
        .form-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2.5rem;
        }
        
        .form-group {
    margin-bottom: 1.5rem; /* Increase if needed */
}

#searchInput {
    margin-bottom: 1rem; /* Adds space below the search bar */
}


        .form-group input[type="text"] {
    margin-bottom: 1rem;
}

        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
     
        select.form-control {
    appearance: none;
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 0.75rem;
    font-size: 1rem;
    border-radius: 4px;
    cursor: pointer;
}


        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary-color);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input {
            margin-right: 0.5rem;
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--dark-color);
        }
            
            /* Menu items table */
        .menu-items-table {
            width: 100%;
            min-width: 1100px;
            margin-top: 2.5rem;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .menu-items-table th, 
        .menu-items-table td {
            padding: 1.25rem 2rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .menu-items-table th {
            background-color: var(--primary-color);
            color: white;
        }
        
        .menu-items-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .status-available {
            color: green;
            font-weight: 500;
        }
        
        .status-unavailable {
            color: #ccc;
            font-weight: 500;
        }
        
        .action-btn {
            margin-right: 0; /* Remove any margin that may cause stacking */
            min-width: 80px; /* Ensures buttons are wide enough */
            justify-content: center;
            display: flex;
            align-items: center;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem; /* Space between buttons */
            align-items: center;
        }
        
        .edit-btn {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Space out the DataTables search bar */
        .dataTables_filter {
            margin-bottom: 1.5rem !important;
            float: right !important;
        }

        /* Space out the DataTables length selector */
        .dataTables_length {
            margin-bottom: 1.5rem !important;
        }

        /* Ensure the table is not too close to the top */
        #menuItemsTable_wrapper {
            margin-top: 2rem;
        }

        /* Set min-widths for columns for better spacing */
        .menu-items-table th:nth-child(1), 
        .menu-items-table td:nth-child(1) {
            min-width: 100px; /* Image */
        }
        .menu-items-table th:nth-child(2), 
        .menu-items-table td:nth-child(2) {
            min-width: 160px; /* Item Name */
        }
        .menu-items-table th:nth-child(3), 
        .menu-items-table td:nth-child(3) {
            min-width: 300px; /* Description */
        }
        .menu-items-table th:nth-child(4), 
        .menu-items-table td:nth-child(4) {
            min-width: 90px; /* Price */
        }
        .menu-items-table th:nth-child(5), 
        .menu-items-table td:nth-child(5) {
            min-width: 110px; /* Status */
        }
        .menu-items-table th:nth-child(6), 
        .menu-items-table td:nth-child(6) {
            min-width: 160px; /* Actions */
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="#" class="navbar-brand">
            <img src="assets/img/icon_t.png" alt="Kafèa-Kiosk Logo">
        </a>
        <div class="nav-links">
            <a href="admin_dashboard.php">Add Item</a>
            <a href="orders.php">Orders</a>
        </div>
        <div class="profile-section">
            <i class="fas fa-user-circle user-icon"></i>
            <span class="username"><?php echo htmlspecialchars($user['fullname']); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </div>
    </div>

    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-error"><?php echo $error_message; ?></div>
<?php endif; ?>

        
        <div class="form-container">
        <h2><?php echo $edit_mode ? "Edit Menu Item" : "Add New Menu Item"; ?></h2>
<form action="" method="POST" enctype="multipart/form-data">
    <?php if ($edit_mode): ?>
        <input type="hidden" name="edit_id" value="<?php echo $edit_item['id']; ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="item_name">Item Name</label>
        <input type="text" id="item_name" name="item_name" class="form-control" required
               value="<?php echo htmlspecialchars($edit_item['item_name']); ?>">
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control" required><?php echo htmlspecialchars($edit_item['description']); ?></textarea>
    </div>

    <div class="form-group">
        <label for="price">Price</label>
        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required
               value="<?php echo htmlspecialchars($edit_item['price']); ?>">
    </div>

    <div class="form-group">
    <label for="image">Image <?php if ($edit_mode): ?> (leave blank to keep current)<?php endif; ?></label>
    <input type="file" id="image" name="image" class="form-control" accept="image/*">
</div>

<div class="form-group">
    <label for="category">Category</label>
    <select id="category" name="category" class="form-control" required>
       <option value="" disabled selected>-- Select Category --</option>
        <option value="Pastry" <?php echo (isset($edit_item['category']) && $edit_item['category'] == 'Pastry') ? 'selected' : ''; ?>>Pastry</option>
        <option value="Coffee" <?php echo (isset($edit_item['category']) && $edit_item['category'] == 'Coffee') ? 'selected' : ''; ?>>Coffee</option>
    </select>
</div>



    <div class="form-group">
        <label for="is_available">Status</label>
        <select id="is_available" name="is_available" class="form-control" required>
            <option value="1" <?php echo ($edit_item['is_available'] == 1 ? 'selected' : ''); ?>>Available</option>
            <option value="0" <?php echo ($edit_item['is_available'] == 0 ? 'selected' : ''); ?>>Not Available</option>
        </select>
    </div>

    <button type="submit" class="btn">
        <i class="fas <?php echo $edit_mode ? 'fa-save' : 'fa-plus'; ?>"></i>
        <?php echo $edit_mode ? "Update Item" : "Add Item"; ?>
    </button>
</form>

        </div>
        
        <h2>Menu Items</h2>

<table id="menuItemsTable" class="menu-items-table display">

            <thead>
                <tr>
                    <th>Image</th>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menu_items)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No menu items found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($menu_items as $item): ?>
                        <tr>
                            <td>
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-image">
                                <?php else: ?>
                                    <div style="width: 80px; height: 80px; background-color: #eee; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
                                        <i class="fas fa-image" style="font-size: 2rem; color: #aaa;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <?php if ($item['is_available']): ?>
                                    <span class="status-available">Available</span>
                                <?php else: ?>
                                    <span class="status-unavailable">Unavailable</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $item['id']; ?>" class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this item?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
         // Confirm before updating an item
    document.querySelector('form').addEventListener('submit', function(e) {
        const isEditMode = document.querySelector('input[name="edit_id"]');
        if (isEditMode) {
            const confirmEdit = confirm("Are you sure you want to update this item?");
            if (!confirmEdit) {
                e.preventDefault(); // Cancel form submission
            }
        }
    });
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                // First send request to logout.php to clear session
                fetch('logout.php')
                    .then(response => {
                        // After logout completes, redirect to signin.php
                        window.location.href = 'signin.php';
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        window.location.href = 'signin.php';
                    });
            }
        }

        document.getElementById('searchInput').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('.menu-items-table tbody tr');

    rows.forEach(row => {
        const itemName = row.cells[1].textContent.toLowerCase();
        const description = row.cells[2].textContent.toLowerCase();

        if (itemName.includes(filter) || description.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
    </script>

    <!-- jQuery (required by DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<!-- Initialize DataTables -->
<script>
    $(document).ready(function() {
        $('#menuItemsTable').DataTable({
            responsive: true,
            pageLength: 10
        });
    });
</script>

</body>
</html>