<?php
// Admin activity logs data.
$escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$adminName = $admin['full_name'] ?? 'Administrator';
$adminEmail = $admin['email'] ?? '';
$activeNav = 'activity-logs';

$hasFilters = $filters['action'] !== 'all'
    || $filters['date_from'] !== null
    || $filters['date_to'] !== null
    || $filters['q'] !== ''
    || $filters['user_id'] !== null;

$filtersJson = json_encode([
    'action'    => $filters['action'],
    'date_from' => $filters['date_from'] ?? '',
    'date_to'   => $filters['date_to'] ?? '',
    'q'         => $filters['q'],
    'user_id'   => $filters['user_id'] ?? '',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Squir Admin - Activity Logs</title>
  <link rel="icon" href="<?= url('assets/images/squir.png') ?>" type="image/x-icon" />
  <script>
    (function () {
      try {
        if (window.localStorage.getItem('squir-admin-theme') === 'dark') {
          document.documentElement.classList.add('dashboard-dark-preload');
        }
      } catch (error) {}
    })();
  </script>
  <link rel="stylesheet" href="<?= url('dist/assets/fonts/tabler-icons.min.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/dashboard.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/admin.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/users.css') ?>" />
  <link rel="stylesheet" href="<?= url('assets/css/admin/activity-logs.css') ?>" />
</head>
<body class="admin-page">
<div class="app-shell" id="adminShell">
<script>
  try {
    if (window.localStorage.getItem('squir-admin-sidebar-collapsed') === '1') {
      document.getElementById('adminShell').classList.add('sidebar-collapsed');
    }
  } catch (error) {}
</script>
  <?php require __DIR__ . '/../../../includes/admin/sidebar.php'; ?>

  <div class="main-area">
    <?php require __DIR__ . '/../../../includes/admin/header.php'; ?>

    <main class="content-area al-page" id="alRoot"
          data-endpoint="<?= $escape(url('admin/activity-logs')) ?>"
          data-filters="<?= $escape($filtersJson) ?>"
          data-has-more="<?= $hasMore ? '1' : '0' ?>"
          data-user-name="<?= $escape($filterUser ?? '') ?>"
          data-clear-ranges="<?= $escape(json_encode(ActivityLog::clearRanges())) ?>">

      <div class="page-heading al-heading">
        <div>
          <h1>Activity Logs</h1>
          <p>View all user and admin recent activities.</p>
        </div>

        <!-- Filters -->
        <div class="al-toolbar">
          <?php
          // Current action label.
          $menuLabel = 'All Actions';
          foreach ($actionGroups as $groupKey => $group) {
              if ($filters['action'] === 'cat:' . $groupKey) {
                  $menuLabel = $group['label'];
              } elseif (isset($group['actions'][$filters['action']])) {
                  $menuLabel = $group['actions'][$filters['action']];
              }
          }
          ?>
          <div class="al-menu" id="alMenu">
            <input type="hidden" id="alAction" value="<?= $escape($filters['action']) ?>">
            <button type="button" class="al-menu-btn<?= $filters['action'] !== 'all' ? ' is-filtered' : '' ?>" id="alMenuBtn"
                    aria-haspopup="menu" aria-expanded="false" aria-label="Filter by action">
              <span id="alMenuLabel"><?= $escape($menuLabel) ?></span>
              <i class="ti ti-chevron-down" aria-hidden="true"></i>
            </button>

            <div class="al-menu-pop" id="alMenuPop" role="menu" hidden>
              <button type="button" class="al-menu-item<?= $filters['action'] === 'all' ? ' is-selected' : '' ?>" role="menuitem"
                      data-value="all" data-short="All Actions">All Actions</button>
              <div class="al-menu-sep" role="separator"></div>

              <?php foreach ($actionGroups as $key => $group):
                  $groupSelected = $filters['action'] === 'cat:' . $key || isset($group['actions'][$filters['action']]);
              ?>
                <div class="al-menu-group<?= $groupSelected ? ' has-selected' : '' ?>">
                  <button type="button" class="al-menu-item has-sub" role="menuitem" aria-haspopup="menu" aria-expanded="false">
                    <span><?= $escape($group['label']) ?></span>
                    <i class="ti ti-chevron-right" aria-hidden="true"></i>
                  </button>
                  <div class="al-submenu" role="menu" aria-label="<?= $escape($group['label']) ?>">
                    <button type="button" class="al-menu-item<?= $filters['action'] === 'cat:' . $key ? ' is-selected' : '' ?>" role="menuitem"
                            data-value="cat:<?= $escape($key) ?>" data-short="<?= $escape($group['label']) ?>">All <?= $escape($group['label']) ?></button>
                    <div class="al-menu-sep" role="separator"></div>
                    <?php foreach ($group['actions'] as $code => $label): ?>
                      <button type="button" class="al-menu-item<?= $filters['action'] === $code ? ' is-selected' : '' ?>" role="menuitem"
                              data-value="<?= $escape($code) ?>" data-short="<?= $escape($label) ?>"><?= $escape($label) ?></button>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="al-daterange" id="alDate">
            <button type="button" class="al-date-btn" id="alDateBtn" aria-haspopup="dialog" aria-expanded="false">
              <i class="ti ti-calendar" aria-hidden="true"></i>
              <span id="alDateLabel">Select date range</span>
              <i class="ti ti-chevron-down al-date-chevron" aria-hidden="true"></i>
            </button>

            <div class="al-date-pop" id="alDatePop" role="dialog" aria-label="Select date range" hidden>
              <p class="al-date-title">Quick select</p>
              <div class="al-presets">
                <button type="button" data-preset="today">Today</button>
                <button type="button" data-preset="yesterday">Yesterday</button>
                <button type="button" data-preset="7">Last 7 days</button>
                <button type="button" data-preset="30">Last 30 days</button>
                <button type="button" data-preset="month">This month</button>
              </div>

              <p class="al-date-title">Custom range</p>
              <div class="al-date-fields">
                <label for="alDateFrom">
                  <span>From</span>
                  <input type="date" id="alDateFrom">
                </label>
                <label for="alDateTo">
                  <span>To</span>
                  <input type="date" id="alDateTo">
                </label>
              </div>
              <p class="al-date-error" id="alDateError" role="alert" hidden></p>

              <div class="al-date-actions">
                <button type="button" class="btn-admin btn-admin-outline btn-admin-sm" id="alDateClear">Clear</button>
                <button type="button" class="btn-admin btn-admin-primary btn-admin-sm" id="alDateApply">Apply</button>
              </div>
            </div>
          </div>

          <div class="al-search">
            <i class="ti ti-search" aria-hidden="true"></i>
            <input type="search" id="alSearch" placeholder="Search user..." aria-label="Search user"
                   value="<?= $escape($filters['q']) ?>" autocomplete="off" maxlength="100">
          </div>

          <!-- Mobile clear action -->
          <button type="button" class="al-clear al-clear-top" id="alClearTop" data-al-clear
                  aria-label="Clear activity logs" title="Clear activity logs">
            <i class="ti ti-trash" aria-hidden="true"></i><span>Clear Activity Logs</span>
          </button>

          <!-- Mobile export action -->
          <a class="al-export al-export-top" id="alExportTop" href="<?= $escape(url('admin/activity-logs?export=csv')) ?>"
             aria-label="Export CSV" title="Export CSV">
            <i class="ti ti-download" aria-hidden="true"></i><span>Export</span>
          </a>

          <button type="button" class="al-refresh" id="alRefresh" aria-label="Refresh">
            <i class="ti ti-refresh" aria-hidden="true"></i><span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- User activity filter -->
      <div class="al-user-chip" id="alUserChip"<?= $filterUser === null ? ' hidden' : '' ?>>
        <i class="ti ti-user" aria-hidden="true"></i>
        <span>Showing activity for <strong id="alUserChipName"><?= $escape($filterUser ?? '') ?></strong></span>
        <button type="button" id="alUserChipClear" aria-label="Show everyone's activity"><i class="ti ti-x"></i></button>
      </div>

      <section class="dash-card al-card" aria-busy="false" id="alCard">
        <div class="al-scroll" id="alScroll">
          <table class="admin-table al-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Date &amp; Time</th>
                <th>Activity</th>
                <th>Details</th>
                <th>Device</th>
                <th>IP Address</th>
              </tr>
            </thead>
            <tbody id="alBody">
              <?php require __DIR__ . '/partials/activity-log-rows.php'; ?>
            </tbody>
          </table>

          <div class="al-empty" id="alEmpty"<?= $logs !== [] ? ' hidden' : '' ?>>
            <i class="ti ti-clock-search" aria-hidden="true"></i>
            <p id="alEmptyText"><?= $hasFilters ? 'No activity matches your filters.' : 'No activity has been recorded yet.' ?></p>
            <button type="button" class="btn-admin btn-admin-outline btn-admin-sm" id="alEmptyClear"<?= $hasFilters ? '' : ' hidden' ?>>Clear filters</button>
          </div>

          <div class="al-error" id="alError" hidden>
            <i class="ti ti-alert-circle" aria-hidden="true"></i>
            <p id="alErrorText">Could not load the activity logs.</p>
            <button type="button" class="btn-admin btn-admin-outline btn-admin-sm" id="alRetry">Try again</button>
          </div>

          <div class="al-loading" id="alLoading" hidden><span class="al-spinner" aria-hidden="true"></span> Loading...</div>
        </div>

        <div class="al-footer">
          <span id="alCount" aria-live="polite">Showing <?= count($logs) ?> of <?= (int) $total ?> logs</span>
          <span id="alUpdated"></span>
        </div>
      </section>

      <div class="al-export-row">
        <button type="button" class="al-clear" id="alClear" data-al-clear>
          <i class="ti ti-trash" aria-hidden="true"></i> Clear Activity Logs
        </button>
        <a class="al-export" id="alExport" href="<?= $escape(url('admin/activity-logs?export=csv')) ?>">
          <i class="ti ti-download" aria-hidden="true"></i> Export
        </a>
      </div>
    </main>

  <?php require __DIR__ . '/../../../includes/admin/footer.php'; ?>

  <script>window.ADMIN_CSRF_TOKEN = <?= json_encode($csrfToken) ?>;</script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="<?= url('assets/js/admin/admin-alert.js') ?>"></script>
  <script src="<?= url('assets/js/admin/admin.js') ?>"></script>
  <script src="<?= url('assets/js/admin/activity-logs.js') ?>"></script>
</body>
</html>