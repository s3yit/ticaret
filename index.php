<?php
// Determine active page
$allowed = ['dashboard', 'defter', 'kisilerim', 'alacak-listem', 'verilecek-listem'];
$active_page = $_GET['page'] ?? 'dashboard';
if (!in_array($active_page, $allowed)) {
    $active_page = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticaret Yönetim Paneli</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #2563eb;
      --primary-dark: #1d4ed8;
      --secondary-color: #f8fafc;
      --accent-color: #06b6d4;
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --border-color: #e2e8f0;
      --success-color: #10b981;
      --warning-color: #f59e0b;
      --danger-color: #ef4444;
      --sidebar-width: 260px;
      --sidebar-collapsed: 70px;
    }

    .app-container {
      display: flex;
      min-height: 100vh;
    }

    .sidebar {
      width: var(--sidebar-width);
      background: white;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      transition: width 0.3s ease, transform 0.3s ease;
      position: fixed;
      height: 100vh;
      z-index: 1000;
      overflow: hidden;
    }

    .sidebar.collapsed {
      width: var(--sidebar-collapsed);
    }

    .sidebar-header {
      padding: 1.5rem;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      gap: 1rem;
      height: 80px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      flex: 1;
    }

    .brand-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .brand-text {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-primary);
      white-space: nowrap;
      opacity: 1;
      transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .brand-text {
      opacity: 0;
    }

    .toggle-btn {
      background: none;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      padding: 8px;
      border-radius: 6px;
      transition: all 0.2s ease;
      flex-shrink: 0;
    }

    .toggle-btn:hover {
      background: var(--secondary-color);
      color: var(--primary-color);
    }

    .nav-menu {
      padding: 1rem 0;
    }

    .nav-item {
      margin: 0 1rem 0.5rem 1rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.875rem 1rem;
      color: var(--text-secondary);
      text-decoration: none;
      font-weight: 500;
      border-radius: 8px;
      transition: all 0.2s ease;
      position: relative;
    }

    .nav-link:hover {
      background: var(--secondary-color);
      color: var(--primary-color);
      transform: translateX(2px);
    }

    .nav-link.active {
      background: var(--primary-color);
      color: white;
    }

    .nav-icon {
      width: 20px;
      text-align: center;
      flex-shrink: 0;
    }

    .nav-text {
      white-space: nowrap;
      opacity: 1;
      transition: opacity 0.3s ease;
    }

    .sidebar.collapsed .nav-text {
      opacity: 0;
    }

    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      transition: margin-left 0.3s ease;
      min-height: 100vh;
      background: #f1f5f9;
    }

    .sidebar.collapsed + .main-content {
      margin-left: var(--sidebar-collapsed);
    }

    .header {
      background: white;
      padding: 1.5rem 2rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
      border-bottom: 1px solid var(--border-color);
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .page-title {
      font-size: 1.875rem;
      font-weight: 700;
      color: var(--text-primary);
      margin: 0;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 1rem;
      color: var(--text-secondary);
      font-size: 0.875rem;
    }

    .content {
      padding: 2rem;
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: var(--sidebar-width);
        transition: transform 0.3s ease;
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .main-content {
        margin-left: 0;
      }

      .sidebar.collapsed + .main-content {
        margin-left: 0;
      }

      .mobile-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
      }

      .mobile-overlay.active {
        opacity: 1;
        visibility: visible;
      }

      .mobile-menu-btn {
        display: block;
        background: none;
        border: none;
        font-size: 1.25rem;
        color: var(--text-secondary);
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: all 0.2s ease;
      }

      .mobile-menu-btn:hover {
        background: var(--secondary-color);
        color: var(--primary-color);
      }

      .content {
        padding: 1rem;
      }

      .header {
        padding: 1rem;
      }

      .page-title {
        font-size: 1.5rem;
      }
    }

    @media (min-width: 769px) {
      .mobile-menu-btn {
        display: none;
      }
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="closeMobileSidebar()"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="brand">
          <div class="brand-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <span class="brand-text">TİCARET</span>
        </div>
        <button class="toggle-btn" aria-label="Menüyü küçült veya büyüt" onclick="toggleSidebar()">
          <i class="fas fa-chevron-left"></i>
        </button>
      </div>

      <nav class="nav-menu">
        <div class="nav-item">
          <a href="?page=dashboard" class="nav-link <?= $active_page === 'dashboard' ? 'active' : '' ?>" aria-label="Dashboard sayfasına git">
            <i class="nav-icon fas fa-home"></i>
            <span class="nav-text">Dashboard</span>
          </a>
        </div>
        <div class="nav-item">
          <a href="?page=defter" class="nav-link <?= $active_page === 'defter' ? 'active' : '' ?>" aria-label="Defter sayfasına git">
            <i class="nav-icon fas fa-book"></i>
            <span class="nav-text">DEFTER</span>
          </a>
        </div>
        <div class="nav-item">
          <a href="?page=kisilerim" class="nav-link <?= $active_page === 'kisilerim' ? 'active' : '' ?>" aria-label="Kişilerim sayfasına git">
            <i class="nav-icon fas fa-users"></i>
            <span class="nav-text">KİŞİLERİM</span>
          </a>
        </div>
        <div class="nav-item">
          <a href="?page=alacak-listem" class="nav-link <?= $active_page === 'alacak-listem' ? 'active' : '' ?>" aria-label="Alacak listem sayfasına git">
            <i class="nav-icon fas fa-hand-holding-usd"></i>
            <span class="nav-text">ALACAK LİSTEM</span>
          </a>
        </div>
        <div class="nav-item">
          <a href="?page=verilecek-listem" class="nav-link <?= $active_page === 'verilecek-listem' ? 'active' : '' ?>" aria-label="Verilecek listem sayfasına git">
            <i class="nav-icon fas fa-credit-card"></i>
            <span class="nav-text">VERİLECEK LİSTEM</span>
          </a>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <header class="header">
        <div class="header-content">
          <div class="flex items-center gap-4">
            <button class="mobile-menu-btn" aria-label="Mobil menüyü aç" onclick="openMobileSidebar()">
              <i class="fas fa-bars"></i>
            </button>
            <h1 class="page-title">
              <?php
                $page_titles = [
                  'dashboard' => 'Dashboard',
                  'defter' => 'Defter',
                  'kisilerim' => 'Kişilerim',
                  'alacak-listem' => 'Alacak Listem',
                  'verilecek-listem' => 'Verilecek Listem',
                  'odeme' => 'Ödeme Listesi'
                ];
                echo $page_titles[$active_page];
              ?>
            </h1>
          </div>
          <div class="user-info">
            <i class="fas fa-calendar"></i>
            <span id="current-date"></span>
          </div>
        </div>
      </header>

      <div class="content">
        <?php
          $page_file = "pages/{$active_page}.php";
          if (file_exists($page_file)) {
            include $page_file;
          } else {
            echo '<div class="text-center text-gray-500 py-4">Sayfa bulunamadı.</div>';
          }
        ?>
      </div>
    </main>
  </div>

  <script>
    let sidebarCollapsed = false;
    let mobileSidebarOpen = false;

    // Initialize app
    document.addEventListener('DOMContentLoaded', () => {
      updateCurrentDate();
      setActiveNavLink();
    });

    // Sidebar functions
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const toggleIcon = document.querySelector('.toggle-btn i');
      sidebarCollapsed = !sidebarCollapsed;
      sidebar.classList.toggle('collapsed', sidebarCollapsed);
      toggleIcon.className = sidebarCollapsed ? 'fas fa-chevron-right' : 'fas fa-chevron-left';
    }

    function openMobileSidebar() {
      document.getElementById('sidebar').classList.add('open');
      document.querySelector('.mobile-overlay').classList.add('active');
      mobileSidebarOpen = true;
    }

    function closeMobileSidebar() {
      document.getElementById('sidebar').classList.remove('open');
      document.querySelector('.mobile-overlay').classList.remove('active');
      mobileSidebarOpen = false;
    }

    // Set active nav link based on current page
    function setActiveNavLink() {
      const currentPage = '<?php echo $active_page; ?>';
      document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `?page=${currentPage}`) {
          link.classList.add('active');
        }
      });
    }

    // Date
    function updateCurrentDate() {
      const now = new Date();
      const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      document.getElementById('current-date').textContent = now.toLocaleDateString('tr-TR', options);
    }

    // Window resize handler
    window.addEventListener('resize', () => {
      if (window.innerWidth > 768) closeMobileSidebar();
    });

    // ESC key handler
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && mobileSidebarOpen) closeMobileSidebar();
    });
  </script>
</body>
</html>