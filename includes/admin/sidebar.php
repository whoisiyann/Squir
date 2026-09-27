<?php
// Expects $adminName (string) and $activeNav (string: 'dashboard' | 'users' | 'activity-logs')
// to already be set by the including view.
$adminName = $adminName ?? 'Administrator';
$activeNav = $activeNav ?? '';
$navClass = static fn (string $key): string => $activeNav === $key ? 'pc-item active' : 'pc-item';
?>
<!-- [ Admin Sidebar Menu ] start -->
<nav class="pc-sidebar">
  <div class="navbar-wrapper">
    <div class="mb-4 m-header flex items-center gap-2 py-4 px-6 h-header-height">
      <img src="./assets/images/squir.png" alt="Squir" class="w-8 h-8 rounded-lg" />
      <div class="leading-tight">
        <span class="block font-semibold text-[16px] text-white">Squir</span>
        <span class="block text-[11px] text-white/60">Admin Panel</span>
      </div>
    </div>
    <div class="navbar-content h-[calc(100vh_-_74px)] py-2.5">
      <ul class="pc-navbar">
        <li class="pc-item pc-caption">
          <label>Navigation</label>
        </li>

        <li class="<?= $navClass('dashboard') ?>">
          <a href="./admin/dashboard" class="pc-link">
            <span class="pc-micon"><i data-feather="home"></i></span>
            <span class="pc-mtext">Dashboard</span>
          </a>
        </li>

        <li class="<?= $navClass('users') ?>">
          <a href="./admin/users" class="pc-link">
            <span class="pc-micon"><i data-feather="users"></i></span>
            <span class="pc-mtext">Users</span>
          </a>
        </li>

        <li class="<?= $navClass('activity-logs') ?>">
          <a href="./admin/activity-logs" class="pc-link">
            <span class="pc-micon"><i data-feather="activity"></i></span>
            <span class="pc-mtext">Activity Logs</span>
          </a>
        </li>

        <li class="pc-item pc-caption">
          <label>Account</label>
        </li>

        <li class="pc-item">
          <a href="./admin/logout" class="pc-link" onclick="return confirm('Log out of the admin panel?')">
            <span class="pc-micon"><i data-feather="log-out"></i></span>
            <span class="pc-mtext">Log Out</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<!-- [ Admin Sidebar Menu ] end -->
