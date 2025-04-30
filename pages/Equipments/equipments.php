<?php
include '../../config/db_connect.php';
require_once '../../config/session_test.php';


$role = $_SESSION['role'] ?? 'member'; 
$message = ''; 


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {

        if ($_POST['action'] === 'add' && $role === 'admin') {
            if (empty($_POST['name']) || empty($_POST['description'])) {
                $message = "Error: Name and description are required.";
            } else {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $available = isset($_POST['available']) ? 1 : 0;

                $checkStmt = $pdo->prepare("SELECT id FROM equipment WHERE name = ?");
                $checkStmt->execute([$name]);
                if ($checkStmt->rowCount() > 0) {
                    $message = "Error: Equipment name already exists.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO equipment (name, description, available) VALUES (?, ?, ?)");
                    if ($stmt->execute([$name, $description, $available])) {
                        $message = "Equipment added successfully!";
                    } else {
                        $message = "Error adding equipment.";
                    }
                }
            }
        }


        if ($_POST['action'] === 'modify' && $role === 'admin') {
            if (empty($_POST['id']) || empty($_POST['name']) || empty($_POST['description'])) {
                $message = "Error: ID, name, and description are required.";
            } else {
                $id = (int) $_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $available = isset($_POST['available']) ? 1 : 0;

                $checkStmt = $pdo->prepare("SELECT id FROM equipment WHERE id = ?");
                $checkStmt->execute([$id]);
                if ($checkStmt->rowCount() == 0) {
                    $message = "Error: Equipment not found.";
                } else {

                    $checkStmt = $pdo->prepare("SELECT id FROM equipment WHERE name = ? AND id != ?");
                    $checkStmt->execute([$name, $id]);
                    if ($checkStmt->rowCount() > 0) {
                        $message = "Error: Equipment name already exists.";
                    } else {
                        $stmt = $pdo->prepare("UPDATE equipment SET name = ?, description = ?, available = ? WHERE id = ?");
                        if ($stmt->execute([$name, $description, $available, $id])) {
                            $message = "Equipment modified successfully!";
                        } else {
                            $message = "Error modifying equipment.";
                        }
                    }
                }
            }
        }


        if ($_POST['action'] === 'delete' && $role === 'admin') {
            if (empty($_POST['id'])) {
                $message = "Error: Equipment ID is required.";
            } else {
                $id = (int) $_POST['id'];

                $checkStmt = $pdo->prepare("SELECT id FROM equipment WHERE id = ?");
                $checkStmt->execute([$id]);
                if ($checkStmt->rowCount() == 0) {
                    $message = "Error: Equipment not found.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = "Equipment deleted successfully!";
                    } else {
                        $message = "Error deleting equipment.";
                    }
                }
            }
        }


        if ($_POST['action'] === 'request') {
            if (empty($_POST['id'])) {
                $message = "Error: Equipment ID is required.";
            } else {
                $equipmentId = (int) $_POST['id'];
                $userId = $_SESSION['id'] ?? 0;

                if ($userId === 0) {
                    $message = "Error: User not authenticated.";
                } else {
                    $checkStmt = $pdo->prepare("SELECT id, available FROM equipment WHERE id = ?");
                    $checkStmt->execute([$equipmentId]);
                    $equipment = $checkStmt->fetch();

                    if ($equipment) {
                        if ($equipment['available']) {
                            $stmt = $pdo->prepare("INSERT INTO equipment_requests (equipment_id, user_id) VALUES (?, ?)");
                            if ($stmt->execute([$equipmentId, $userId])) {
                                $message = "Equipment request submitted successfully!";
                            } else {
                                $message = "Error submitting equipment request.";
                            }
                        } else {
                            $message = "Error: Equipment is not available.";
                        }
                    } else {
                        $message = "Error: Equipment not found.";
                    }
                }
            }
        }
    }
}

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
$allowedSortFields = ['id', 'name', 'description', 'available', 'created_at'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'id';
}


$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParams = [];
$searchCondition = '';

if (!empty($search)) {
    $searchCondition = "WHERE name LIKE ? OR description LIKE ?";
    $searchValue = "%$search%";
    $searchParams = [$searchValue, $searchValue];
}


$countStmt = $pdo->prepare("SELECT COUNT(*) FROM equipment $searchCondition");
if (!empty($searchParams)) {
    $countStmt->execute($searchParams);
} else {
    $countStmt->execute();
}
$totalEquipment = $countStmt->fetchColumn();
$totalPages = ceil($totalEquipment / $perPage);

$query = "SELECT * FROM equipment $searchCondition ORDER BY $sortField $sortOrder LIMIT $offset, $perPage";
$stmt = $pdo->prepare($query);
if (!empty($searchParams)) {
    $stmt->execute($searchParams);
} else {
    $stmt->execute();
}


function getSortUrl($field, $currentSort, $currentOrder)
{
    $newOrder = ($currentSort === $field && $currentOrder === 'ASC') ? 'desc' : 'asc';
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = $newOrder;
    return '?' . http_build_query($params);
}


function getSortIcon($field, $currentSort, $currentOrder)
{
    if ($field !== $currentSort) {
        return '<i class="fas fa-sort text-gray-400"></i>';
    }
    return $currentOrder === 'ASC' ?
        '<i class="fas fa-sort-up text-blue-600"></i>' :
        '<i class="fas fa-sort-down text-blue-600"></i>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Equipment Management | Admin Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6cf7;
            --secondary-color: #6b7280;
            --success-color: #22c55e;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --light-color: #f3f4f6;
            --dark-color: #111827;
            --border-color: #e5e7eb;
            --header-height: 70px;
            --sidebar-width: 240px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9fafb;
            color: #374151;
            line-height: 1.5;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: calc(100vh - var(--header-height));
        }

        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table th {
            padding: 12px 16px;
            text-align: left;
            background-color: #f9fafb;
            color: var(--secondary-color);
            font-weight: 600;
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            cursor: pointer;
        }

        .table th:hover {
            background-color: #f3f4f6;
        }

        .table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            color: #4b5563;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background-color: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }

        .badge-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .filters {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 40px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-box input:focus {
            border-color: var(--primary-color);
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .pagination-info {
            color: var(--secondary-color);
        }

        .pagination-controls {
            display: flex;
            gap: 5px;
        }

        .pagination-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--secondary-color);
            cursor: pointer;
            transition: all 0.2s;
        }

        .pagination-button:hover,
        .pagination-button.active {
            background-color: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .pagination-button:disabled {
            background-color: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
            border-color: var(--border-color);
        }

        .pagination-button i {
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 999;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            width: 500px;
            max-width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6b7280;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-group input[readonly] {
            background-color: #f3f4f6;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        @media screen and (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .filters {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        .sort-icon {
            margin-left: 5px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #3a5be0;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: #fff;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-warning {
            background-color: var(--warning-color);
            color: #fff;
        }

        .btn-warning:hover {
            background-color: #d97706;
        }

        .btn-success {
            background-color: var(--success-color);
            color: #fff;
        }

        .btn-success:hover {
            background-color: #1ea34e;
        }

        .btn:disabled {
            background-color: #d1d5db;
            color: #6b7280;
            cursor: not-allowed;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        @media screen and (max-width: 992px) {
            .mobile-hidden {
                display: none;
            }
        }

        .notification {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 4px;
            color: white;
            z-index: 1000;
        }

        .notification-success {
            background-color: var(--success-color);
        }

        .notification-error {
            background-color: var(--danger-color);
        }
    </style>
</head>

<body>
    <?php include '../../include/sidebar.php'; ?>

    <div class="main-content">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Equipment Management</h1>
            </div>

            <div class="card-body">
                <div class="filters">
                    <form class="search-box" method="GET" action="">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search equipment..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <?php if (isset($_GET['sort'])): ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['order'])): ?>
                            <input type="hidden" name="order" value="<?php echo htmlspecialchars($_GET['order']); ?>">
                        <?php endif; ?>
                    </form>
                    <?php if ($role === 'admin'): ?>
                        <button class="btn btn-primary" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Add New Equipment
                        </button>
                    <?php endif; ?>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('id', $sortField, $sortOrder); ?>'">
                                    ID <?php echo getSortIcon('id', $sortField, $sortOrder); ?>
                                </th>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('name', $sortField, $sortOrder); ?>'">
                                    Name <?php echo getSortIcon('name', $sortField, $sortOrder); ?>
                                </th>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('description', $sortField, $sortOrder); ?>'">
                                    Description <?php echo getSortIcon('description', $sortField, $sortOrder); ?>
                                </th>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('available', $sortField, $sortOrder); ?>'">
                                    Available <?php echo getSortIcon('available', $sortField, $sortOrder); ?>
                                </th>
                                <th class="mobile-hidden"
                                    onclick="window.location.href='<?php echo getSortUrl('created_at', $sortField, $sortOrder); ?>'">
                                    Created At <?php echo getSortIcon('created_at', $sortField, $sortOrder); ?>
                                </th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipmentTableBody">
                            <?php if ($stmt->rowCount() === 0): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-tools"
                                            style="font-size: 24px; color: #d1d5db; margin-bottom: 10px;"></i>
                                        <p>No equipment found</p>
                                        <?php if (!empty($search)): ?>
                                            <p style="font-size: 12px; color: #6b7280;">Try adjusting your search criteria</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($item = $stmt->fetch()): ?>
                                    <tr id="equipment-<?php echo htmlspecialchars($item['id']); ?>">
                                        <td><?php echo htmlspecialchars($item['id']); ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($item['description'], 0, 100)) . (strlen($item['description']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <span
                                                class="badge <?php echo $item['available'] ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo $item['available'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td class="mobile-hidden">
                                            <?php
                                            $createdDate = new DateTime($item['created_at']);
                                            echo $createdDate->format('M d, Y');
                                            ?>
                                        </td>
                                        <td class="action-buttons">
                                            <button class="btn btn-success"
                                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <?php if ($role === 'admin'): ?>
                                                <button class="btn btn-warning"
                                                    onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', '<?php echo addslashes($item['description']); ?>', <?php echo $item['available'] ? '1' : '0'; ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-danger"
                                                    onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            <?php elseif ($role === 'member'): ?>
                                                <?php if ($item['available']): ?>
                                                    <button class="btn btn-success"
                                                        onclick="requestEquipment(<?php echo $item['id']; ?>)">
                                                        <i class="fas fa-hand-holding"></i> Request
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn" disabled>Unavailable</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <div class="pagination-info">
                            Showing <?php echo min($totalEquipment, $offset + 1); ?> to
                            <?php echo min($totalEquipment, $offset + $perPage); ?> of <?php echo $totalEquipment; ?> equipment
                        </div>
                        <div class="pagination-controls">
                            <?php
                            $queryParams = $_GET;

                            $prevPage = max(1, $page - 1);
                            $queryParams['page'] = $prevPage;
                            $prevUrl = '?' . http_build_query($queryParams);
                            echo '<a href="' . $prevUrl . '" class="pagination-button' . ($page == 1 ? ' disabled' : '') . '" ' . ($page == 1 ? 'disabled' : '') . '>';
                            echo '<i class="fas fa-chevron-left"></i></a>';

                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);

                            if ($startPage > 1) {
                                $queryParams['page'] = 1;
                                echo '<a href="?' . http_build_query($queryParams) . '" class="pagination-button">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="pagination-button disabled">...</span>';
                                }
                            }

                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $queryParams['page'] = $i;
                                echo '<a href="?' . http_build_query($queryParams) . '" class="pagination-button' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
                            }

                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="pagination-button disabled">...</span>';
                                }
                                $queryParams['page'] = $totalPages;
                                echo '<a href="?' . http_build_query($queryParams) . '" class="pagination-button">' . $totalPages . '</a>';
                            }

                            $nextPage = min($totalPages, $page + 1);
                            $queryParams['page'] = $nextPage;
                            $nextUrl = '?' . http_build_query($queryParams);
                            echo '<a href="' . $nextUrl . '" class="pagination-button' . ($page == $totalPages ? ' disabled' : '') . '" ' . ($page == $totalPages ? 'disabled' : '') . '>';
                            echo '<i class="fas fa-chevron-right"></i></a>';
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="notification" class="notification"></div>

    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Equipment</h3>
                <button class="modal-close" onclick="closeModal('createModal')">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="createName">Equipment Name</label>
                    <input type="text" id="createName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="createDescription">Description</label>
                    <textarea id="createDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="createAvailable">Available</label>
                    <select id="createAvailable" name="available" required>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <input type="hidden" name="action" value="add">
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Equipment</h3>
                <button class="modal-close" onclick="closeModal('editModal')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editName">Equipment Name</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editAvailable">Available</label>
                    <select id="editAvailable" name="available" required>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <input type="hidden" name="action" value="modify">
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">View Equipment</h3>
                <button class="modal-close" onclick="closeModal('viewModal')">×</button>
            </div>
            <div class="form-group">
                <label>Name</label>
                <input type="text" id="viewName" readonly>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="viewDescription" readonly></textarea>
            </div>
            <div class="form-group">
                <label>Available</label>
                <input type="text" id="viewAvailable" readonly>
            </div>
            <div class="form-group">
                <label>Created At</label>
                <input type="text" id="viewCreatedAt" readonly>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">×</button>
            </div>
            <p id="deleteMessage">Are you sure you want to delete this equipment? This action cannot be undone.</p>
            <form method="POST">
                <input type="hidden" id="deleteId" name="id">
                <input type="hidden" name="action" value="delete">
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.innerText = message;
            notification.className = 'notification notification-' + type;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        <?php if (!empty($message)): ?>
            showNotification("<?php echo addslashes($message); ?>", "<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>");
        <?php endif; ?>

        function openCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
        }

        function openEditModal(id, name, description, available) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editDescription').value = description;
            document.getElementById('editAvailable').value = available;
            document.getElementById('editModal').style.display = 'flex';
        }

        function openViewModal(equipment) {
            document.getElementById('viewName').value = equipment.name;
            document.getElementById('viewDescription').value = equipment.description;
            document.getElementById('viewAvailable').value = equipment.available ? 'Yes' : 'No';
            document.getElementById('viewCreatedAt').value = new Date(equipment.created_at).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            document.getElementById('viewModal').style.display = 'flex';
        }

        function confirmDelete(id, name) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteMessage').innerText = 'Are you sure you want to delete "' + name + '"?';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'createModal') {
                document.getElementById('createName').value = '';
                document.getElementById('createDescription').value = '';
                document.getElementById('createAvailable').value = '1';
            } else if (modalId === 'editModal') {
                document.getElementById('editId').value = '';
                document.getElementById('editName').value = '';
                document.getElementById('editDescription').value = '';
                document.getElementById('editAvailable').value = '1';
            }
        }

        function requestEquipment(equipmentId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'request';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = equipmentId;

            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }

        const searchInput = document.querySelector('.search-box input');
        let searchTimeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

        window.addEventListener('click', function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal('createModal');
                closeModal('editModal');
                closeModal('viewModal');
                closeModal('deleteModal');
            }
        });
    </script>
</body>

</html>