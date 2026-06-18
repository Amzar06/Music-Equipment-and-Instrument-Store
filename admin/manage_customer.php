<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Customer Directory";
$active = "customers"; 

// Search & sort process

// Linear pattern matching search via SQL LIKE

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where_clause = "WHERE status != 'Deleted'"; // Hide deleted customers

if (!empty($search)) {
    $where_clause .= " AND (cust_name LIKE '%$search%' OR cust_email LIKE '%$search%' OR cust_phone_number LIKE '%$search%')";
}

// Sorting evaluation (Database-driven Quicksort/Merge Sort execution)
$sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'alpha_asc';
switch ($sort_by) {
    case 'alpha_desc':
        $order_clause = "ORDER BY cust_name DESC";
        break;
    case 'newest':
        $order_clause = "ORDER BY cust_id DESC"; // Assumes higher IDs are recently registered
        break;
    case 'oldest':
        $order_clause = "ORDER BY cust_id ASC";
        break;
    case 'alpha_asc':
    default:
        $order_clause = "ORDER BY cust_name ASC";
        break;
}

// Execute the final processed query string

$customer_query = "SELECT * FROM customers $where_clause $order_clause";
$customer_result = mysqli_query($conn, $customer_query);

require_once('admin_header.php');
?>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-weight: 700; color: #111827; font-size: 1.25rem;">Registered Customers</h3>
        </div>

        <form action="admin_customer_list.php" method="GET" style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Name, Email or Phone..." 
                   style="flex-grow: 1; min-width: 250px; padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-size: 0.95rem;">
            
            <select name="sort_by" style="padding: 10px 14px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; background: #fff; font-size: 0.95rem; cursor: pointer;">
                <option value="alpha_asc" <?php echo ($sort_by == 'alpha_asc') ? 'selected' : ''; ?>>Name: A to Z</option>
                <option value="alpha_desc" <?php echo ($sort_by == 'alpha_desc') ? 'selected' : ''; ?>>Name: Z to A</option>
                <option value="newest" <?php echo ($sort_by == 'newest') ? 'selected' : ''; ?>>Newest Registered</option>
                <option value="oldest" <?php echo ($sort_by == 'oldest') ? 'selected' : ''; ?>>Oldest Registered</option>
            </select>
            
            <button type="submit" style="padding: 10px 20px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;"
                    onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                Apply Filters
            </button>
        </form>

        <?php if(!empty($search)): ?>
            <div style="margin-bottom: 16px; font-size: 0.85rem; color: #4b5563;">
                Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                <a href="admin_customer_list.php" style="color: #ef4444; text-decoration: none; font-weight: 600; margin-left: 10px;">Clear Search</a>
            </div>
        <?php endif; ?>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Customer Name</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Contact Info</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customer_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($customer_result)): 
                            $status = isset($row['status']) ? $row['status'] : 'Active';
                            if ($status == 'Active') { $bg = '#d1fae5'; $txt = '#065f46'; }
                            elseif ($status == 'Suspended') { $bg = '#fef3c7'; $txt = '#92400e'; }
                            elseif ($status == 'Blacklisted') { $bg = '#111827'; $txt = '#f9fafb'; }
                            else { $bg = '#fee2e2'; $txt = '#991b1b'; }
                        ?>
                        <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" onmouseover="this.style.backgroundColor='#f9fafb'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 16px; font-weight: 600; color: #111827;">
                                <?php echo htmlspecialchars($row['cust_name']); ?>
                            </td>
                            <td style="padding: 16px; font-size: 0.85rem; color: #4b5563; line-height: 1.5;">
                                <strong>📧 Email:</strong> <?php echo htmlspecialchars($row['cust_email']); ?><br>
                                <strong>📞 Phone:</strong> <?php echo !empty($row['cust_phone_number']) ? htmlspecialchars($row['cust_phone_number']) : '-'; ?>
                            </td>
                            <td style="padding: 16px;">
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $txt; ?>; padding: 4px 12px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; display: inline-block;">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: right;">
                                <a href="admin_view_customer.php?id=<?php echo $row['cust_id']; ?>" 
                                   style="color: #4f46e5; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 8px 16px; border-radius: 6px; background: #e0e7ff; transition: 0.2s; display: inline-block;"
                                   onmouseover="this.style.backgroundColor='#c7d2fe'" onmouseout="this.style.backgroundColor='#e0e7ff'">
                                    View Full Profile
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af; font-style: italic;">No matching customers found in the system registry.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once('admin_footer.php'); ?>