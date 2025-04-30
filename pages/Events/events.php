<?php
include '../../config/db_connect.php';
require_once '../../config/session_test.php';

// Check if user has admin privileges
$role = $_SESSION['role'] ?? 'member'; 
$message = ''; // Variable to store success/error messages


// Handle form submissions for adding, modifying, or deleting events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add event
        if ($_POST['action'] === 'add' && $role === 'admin') {
            if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['event_date'])) {
                $message = "Error: Title, description, and event date are required.";
            } else {
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $event_date = trim($_POST['event_date']);
                $created_by = $_SESSION['id'] ?? 0;

                if ($created_by === 0) {
                    $message = "Error: User not authenticated.";
                } else {
                    // Validate event_date format
                    try {
                        $date = new DateTime($event_date);
                        $formatted_date = $date->format('Y-m-d H:i:s');
                    } catch (Exception $e) {
                        $message = "Error: Invalid event date format.";
                    }

                    if (empty($message)) {
                        $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, created_by) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$title, $description, $formatted_date, $created_by])) {
                            $message = "Event added successfully!";
                        } else {
                            $message = "Error adding event.";
                        }
                    }
                }
            }
        }

        // Modify event
        if ($_POST['action'] === 'modify') {
            if (empty($_POST['id']) || empty($_POST['title']) || empty($_POST['description']) || empty($_POST['event_date'])) {
                $message = "Error: All fields are required.";
            } else {
                $id = (int) $_POST['id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $event_date = trim($_POST['event_date']);
                $created_by = $_SESSION['user_id'] ?? 0;

                if ($created_by === 0) {
                    $message = "Error: User not authenticated.";
                } else {
                    // Validate event_date format
                    try {
                        $date = new DateTime($event_date);
                        $formatted_date = $date->format('Y-m-d H:i:s');
                    } catch (Exception $e) {
                        $message = "Error: Invalid event date format.";
                    }

                    // Check if event exists
                    $checkStmt = $pdo->prepare("SELECT id FROM events WHERE id = ?");
                    $checkStmt->execute([$id]);
                    if ($checkStmt->rowCount() == 0) {
                        $message = "Error: Event not found.";
                    } elseif (empty($message)) {
                        $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, created_by = ? WHERE id = ?");
                        if ($stmt->execute([$title, $description, $formatted_date, $created_by, $id])) {
                            $message = "Event modified successfully!";
                        } else {
                            $message = "Error modifying event.";
                        }
                    }
                }
            }
        }

        // Delete event
        if ($_POST['action'] === 'delete') {
            if (empty($_POST['id'])) {
                $message = "Error: Event ID is required.";
            } else {
                $id = (int) $_POST['id'];

                // Check if event exists
                $checkStmt = $pdo->prepare("SELECT id FROM events WHERE id = ?");
                $checkStmt->execute([$id]);
                if ($checkStmt->rowCount() == 0) {
                    $message = "Error: Event not found.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = "Event deleted successfully!";
                    } else {
                        $message = "Error deleting event.";
                    }
                }
            }
        }
    }
}

// Set up pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Set up sorting
$sortField = isset($_GET['sort']) ? $_GET['sort'] : 'id';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
$allowedSortFields = ['id', 'title', 'description', 'event_date', 'created_by', 'created_at'];
if (!in_array($sortField, $allowedSortFields)) {
    $sortField = 'id';
}

// Set up searching
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchParams = [];
$searchCondition = '';

if (!empty($search)) {
    $searchCondition = "WHERE events.title LIKE ? OR events.description LIKE ? OR users.firstname LIKE ? OR users.lastname LIKE ?";
    $searchValue = "%$search%";
    $searchParams = [$searchValue, $searchValue, $searchValue, $searchValue];
}

// Get total count for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM events JOIN users ON events.created_by = users.id $searchCondition");
if (!empty($searchParams)) {
    $countStmt->execute($searchParams);
} else {
    $countStmt->execute();
}
$totalEvents = $countStmt->fetchColumn();
$totalPages = ceil($totalEvents / $perPage);

// Fetch events with sorting, searching, and pagination
$query = "SELECT events.*, users.firstname, users.lastname 
          FROM events 
          JOIN users ON events.created_by = users.id 
          $searchCondition 
          ORDER BY $sortField $sortOrder 
          LIMIT $offset, $perPage";
$stmt = $pdo->prepare($query);
if (!empty($searchParams)) {
    $stmt->execute($searchParams);
} else {
    $stmt->execute();
}

// Helper function to generate sort URL
function getSortUrl($field, $currentSort, $currentOrder)
{
    $newOrder = ($currentSort === $field && $currentOrder === 'ASC') ? 'desc' : 'asc';
    $params = $_GET;
    $params['sort'] = $field;
    $params['order'] = $newOrder;
    return '?' . http_build_query($params);
}

// Helper function to get sort icon
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
    <title>Events List | Admin Dashboard</title>
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

        /* Layout */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: calc(100vh - var(--header-height));
        }

        /* Card Styling */
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

        /* Table Styling */
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

        /* Search Bar and Filters */
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

        /* Pagination */
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

        /* Modal Styling */
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

        /* Responsive adjustments */
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

        /* Sort icons */
        .sort-icon {
            margin-left: 5px;
        }

        /* Button Styling */
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        /* Responsive Data View */
        @media screen and (max-width: 992px) {
            .mobile-hidden {
                display: none;
            }
        }

        /* Notification Styling */
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
                <h1 class="card-title">Events Directory</h1>
            </div>

            <div class="card-body">
                <div class="filters">
                    <form class="search-box" method="GET" action="">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search events..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <!-- Preserve existing sort parameters -->
                        <?php if (isset($_GET['sort'])): ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['order'])): ?>
                            <input type="hidden" name="order" value="<?php echo htmlspecialchars($_GET['order']); ?>">
                        <?php endif; ?>
                    </form>
                    <?php if ($role === 'admin'): ?>
                        <button class="btn btn-primary" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Add New Event
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
                                    onclick="window.location.href='<?php echo getSortUrl('title', $sortField, $sortOrder); ?>'">
                                    Title <?php echo getSortIcon('title', $sortField, $sortOrder); ?>
                                </th>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('description', $sortField, $sortOrder); ?>'">
                                    Description <?php echo getSortIcon('description', $sortField, $sortOrder); ?>
                                </th>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('event_date', $sortField, $sortOrder); ?>'">
                                    Event Date <?php echo getSortIcon('event_date', $sortField, $sortOrder); ?>
                                </th>
                                <th
                                    onclick="window.location.href='<?php echo getSortUrl('created_by', $sortField, $sortOrder); ?>'">
                                    Created By <?php echo getSortIcon('created_by', $sortField, $sortOrder); ?>
                                </th>
                                <th class="mobile-hidden"
                                    onclick="window.location.href='<?php echo getSortUrl('created_at', $sortField, $sortOrder); ?>'">
                                    Created At <?php echo getSortIcon('created_at', $sortField, $sortOrder); ?>
                                </th>
                                <?php if ($role === 'admin'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="eventsTableBody">
                            <?php if ($stmt->rowCount() === 0): ?>
                                <tr>
                                    <td colspan="<?php echo $role ? '7' : '6'; ?>"
                                        style="text-align: center; padding: 30px;">
                                        <i class="fas fa-calendar-alt"
                                            style="font-size: 24px; color: #d1d5db; margin-bottom: 10px;"></i>
                                        <p>No events found</p>
                                        <?php if (!empty($search)): ?>
                                            <p style="font-size: 12px; color: #6b7280;">Try adjusting your search criteria</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($event = $stmt->fetch()): ?>
                                    <tr id="event-<?php echo htmlspecialchars($event['id']); ?>">
                                        <td><?php echo htmlspecialchars($event['id']); ?></td>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . (strlen($event['description']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <?php
                                            $eventDate = new DateTime($event['event_date']);
                                            echo $eventDate->format('M d, Y H:i');
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($event['firstname'] . ' ' . $event['lastname']); ?></td>
                                        <td class="mobile-hidden">
                                            <?php
                                            $createdDate = new DateTime($event['created_at']);
                                            echo $createdDate->format('M d, Y H:i');
                                            ?>
                                        </td>
                                        <?php if ($role === 'admin'): ?>
                                            <td class="action-buttons">
                                                <button class="btn btn-warning"
                                                    onclick="openEditModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['title']); ?>', '<?php echo addslashes($event['description']); ?>', '<?php echo $event['event_date']; ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-success"
                                                    onclick="openViewModal(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                                <button class="btn btn-danger"
                                                    onclick="confirmDelete(<?php echo $event['id']; ?>, '<?php echo addslashes($event['title']); ?>')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <div class="pagination-info">
                            Showing <?php echo min($totalEvents, $offset + 1); ?> to
                            <?php echo min($totalEvents, $offset + $perPage); ?> of <?php echo $totalEvents; ?> events
                        </div>
                        <div class="pagination-controls">
                            <?php
                            $queryParams = $_GET;

                            // Previous button
                            $prevPage = max(1, $page - 1);
                            $queryParams['page'] = $prevPage;
                            $prevUrl = '?' . http_build_query($queryParams);
                            echo '<a href="' . $prevUrl . '" class="pagination-button' . ($page == 1 ? ' disabled' : '') . '" ' . ($page == 1 ? 'disabled' : '') . '>';
                            echo '<i class="fas fa-chevron-left"></i></a>';

                            // Show range of page numbers
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

                            // Next button
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

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Event</h3>
                <button class="modal-close" onclick="closeModal('createModal')">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="createTitle">Title</label>
                    <input type="text" id="createTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="createDescription">Description</label>
                    <textarea id="createDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="createEventDate">Event Date</label>
                    <input type="datetime-local" id="createEventDate" name="event_date" required>
                </div>
                <input type="hidden" name="action" value="add">
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('createModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Event</h3>
                <button class="modal-close" onclick="closeModal('editModal')">×</button>
            </div>
            <form method="POST">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editTitle">Title</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editEventDate">Event Date</label>
                    <input type="datetime-local" id="editEventDate" name="event_date" required>
                </div>
                <input type="hidden" name="action" value="modify">
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">View Event</h3>
                <button class="modal-close" onclick="closeModal('viewModal')">×</button>
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="viewTitle" readonly>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="viewDescription" readonly></textarea>
            </div>
            <div class="form-group">
                <label>Event Date</label>
                <input type="text" id="viewEventDate" readonly>
            </div>
            <div class="form-group">
                <label>Created By</label>
                <input type="text" id="viewCreatedBy" readonly>
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" onclick="closeModal('deleteModal')">×</button>
            </div>
            <p id="deleteMessage">Are you sure you want to delete this event? This action cannot be undone.</p>
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
        // Show Notification
        function showNotification(message, type) {
            const notification = document.getElementById('notification');
            notification.innerText = message;
            notification.className = 'notification notification-' + type;
            notification.style.display = 'block';
            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }

        // Display message if set
        <?php if (!empty($message)): ?>
            showNotification("<?php echo addslashes($message); ?>", "<?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>");
        <?php endif; ?>

        // Open Create Modal
        function openCreateModal() {
            document.getElementById('createModal').style.display = 'flex';
        }

        // Open Edit Modal with pre-filled data
        function openEditModal(id, title, description, event_date) {
            document.getElementById('editId').value = id;
            document.getElementById('editTitle').value = title;
            document.getElementById('editDescription').value = description;
            // Format event_date for datetime-local input (YYYY-MM-DDTHH:MM)
            const date = new Date(event_date);
            const formattedDate = date.toISOString().slice(0, 16);
            document.getElementById('editEventDate').value = formattedDate;
            document.getElementById('editModal').style.display = 'flex';
        }

        // Open View Modal
        function openViewModal(event) {
            document.getElementById('viewTitle').value = event.title;
            document.getElementById('viewDescription').value = event.description;
            document.getElementById('viewEventDate').value = new Date(event.event_date).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('viewCreatedBy').value = event.firstname + ' ' + event.lastname;
            document.getElementById('viewCreatedAt').value = new Date(event.created_at).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('viewModal').style.display = 'flex';
        }

        // Open Delete Confirmation Modal
        function confirmDelete(id, title) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteMessage').innerText = 'Are you sure you want to delete "' + title + '"?';
            document.getElementById('deleteModal').style.display = 'flex';
        }

        // Close the specified modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'createModal') {
                document.getElementById('createTitle').value = '';
                document.getElementById('createDescription').value = '';
                document.getElementById('createEventDate').value = '';
            } else if (modalId === 'editModal') {
                document.getElementById('editId').value = '';
                document.getElementById('editTitle').value = '';
                document.getElementById('editDescription').value = '';
                document.getElementById('editEventDate').value = '';
            }
        }

        // Auto-submit search form on input change
        const searchInput = document.querySelector('.search-box input');
        let searchTimeout;

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

        // Close modals when clicking outside
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