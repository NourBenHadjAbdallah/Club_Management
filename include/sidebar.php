<?php
require_once __DIR__ . '/../config/session_test.php';
require_once __DIR__ . '/../config/base_url.php';

if (!isset($_SESSION['user'])) {
  header('Location: /FINALPHP/login.php');
  exit();
}
?>

<div class="sidebar">
  <div class="sidebar-header">
    <h2 class="sidebar-title">Club Audio Visuel Hammam Sousse</h2>
    <p class="user-info"><?php echo htmlspecialchars($_SESSION['user']); ?></p>
  </div>

  <nav class="sidebar-nav">
    <ul class="nav-list">
      <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
        <a href="<?php echo $base_url; ?>index.php" class="nav-link">
          <i class="icon">🏠</i>
          <span>Dashboard</span>
        </a>
      </li>

      <li
        class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/Equipments/equipments.php') !== false ? 'active' : ''; ?>">
        <a href="<?php echo $base_url; ?>pages/Equipments/equipments.php" class="nav-link">
          <i class="icon">🧰</i>
          <span>Equipment</span>
        </a>
      </li>

      <?php if ($_SESSION['role'] === 'admin'): ?>
        <li
          class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/Equipments/request.php') !== false ? 'active' : ''; ?>">
          <a href="<?php echo $base_url; ?>pages/Equipments/request.php" class="nav-link">
            <i class="icon">📋</i>
            <span>Requests</span>
          </a>
        </li>

        <li
          class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/Members/members.php') !== false ? 'active' : ''; ?>">
          <a href="<?php echo $base_url; ?>pages/Members/members.php" class="nav-link">
            <i class="icon">👥</i>
            <span>Members</span>
          </a>
        </li>
      <?php endif; ?>

      <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/Events/events.php') !== false ? 'active' : ''; ?>">
        <a href="<?php echo $base_url; ?>pages/Events/events.php" class="nav-link">
          <i class="icon">📅</i>
          <span>Events</span>
        </a>
      </li>

      <li
        class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/Annancements/Annancements.php') !== false ? 'active' : ''; ?>">
        <a href="<?php echo $base_url; ?>pages/Annancements/Annancements.php" class="nav-link">
          <i class="icon">📣</i>
          <span>Announcements</span>
        </a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="<?php echo $base_url; ?>logout.php" class="logout-btn">
      <i class="icon">🚪</i>
      <span>Logout</span>
    </a>
  </div>
</div>

<style>
  .sidebar {
    width: 250px;
    height: 100vh;
    background: #2c3e50;
    color: #ecf0f1;
    display: flex;
    flex-direction: column;
    box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    position: fixed;
    left: 0;
    top: 0;
  }

  .sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #34495e;
  }

  .sidebar-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #ecf0f1;
  }

  .user-info {
    font-size: 0.9rem;
    margin: 5px 0 0;
    color: #bdc3c7;
  }

  .sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
  }

  .nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .nav-item {
    margin-bottom: 5px;
  }

  .nav-item.active .nav-link {
    background: #34495e;
    border-left: 4px solid #3498db;
  }

  .nav-link {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #ecf0f1;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
  }

  .nav-link:hover {
    background: #34495e;
  }

  .icon {
    margin-right: 10px;
    width: 20px;
    text-align: center;
  }

  .sidebar-footer {
    padding: 15px 20px;
    border-top: 1px solid #34495e;
  }

  .logout-btn {
    display: flex;
    align-items: center;
    color: #e74c3c;
    text-decoration: none;
    padding: 10px;
    border-radius: 4px;
    transition: all 0.3s ease;
  }

  .logout-btn:hover {
    background: rgba(231, 76, 60, 0.1);
  }
</style>