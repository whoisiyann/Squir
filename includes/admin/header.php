<?php
// Expects $adminName and $adminEmail to already be set by the including view.
$adminName = $adminName ?? 'Administrator';
$adminEmail = $adminEmail ?? '';
$escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$initials = adminInitials($adminName);
?>
<!-- [ Admin Header Topbar ] start -->
<header class="pc-header">
  <div class="header-wrapper flex max-sm:px-[15px] px-[25px] grow">
    <div class="me-auto pc-mob-drp">
      <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
        <li class="pc-h-item pc-sidebar-collapse max-lg:hidden lg:inline-flex">
          <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="sidebar-hide">
            <i data-feather="menu"></i>
          </a>
        </li>
        <li class="pc-h-item pc-sidebar-popup lg:hidden">
          <a href="#" class="pc-head-link ltr:!ml-0 rtl:!mr-0" id="mobile-collapse">
            <i data-feather="menu"></i>
          </a>
        </li>
        <li class="pc-h-item">
          <div class="relative">
            <i data-feather="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted"></i>
            <input type="search" class="form-control !pl-9 max-sm:!hidden" style="min-width:260px" placeholder="Search users, activities..." disabled />
          </div>
        </li>
      </ul>
    </div>
    <div class="ms-auto">
      <ul class="inline-flex *:min-h-header-height *:inline-flex *:items-center">
        <li class="pc-h-item">
          <a class="pc-head-link me-0" href="#" role="button">
            <i data-feather="bell"></i>
          </a>
        </li>

        <li class="dropdown pc-h-item header-user-profile">
          <a class="pc-head-link dropdown-toggle arrow-none me-0" data-pc-toggle="dropdown" href="#" role="button"
            aria-haspopup="false" data-pc-auto-close="outside" aria-expanded="false">
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary-500 text-white font-medium text-sm"><?= $escape($initials) ?></span>
          </a>
          <div class="dropdown-menu dropdown-user-profile dropdown-menu-end pc-h-dropdown p-2 overflow-hidden">
            <div class="dropdown-header flex items-center justify-between py-4 px-5 bg-primary-500">
              <div class="flex mb-1 items-center">
                <div class="shrink-0">
                  <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/20 text-white font-medium"><?= $escape($initials) ?></span>
                </div>
                <div class="grow ms-4">
                  <h4 class="mb-1 text-white"><?= $escape($adminName) ?></h4>
                  <span class="text-white"><?= $escape($adminEmail) ?></span>
                </div>
              </div>
            </div>
            <div class="dropdown-body py-4 px-5">
              <div class="grid my-1">
                <a href="./admin/logout" class="btn btn-primary flex items-center justify-center" onclick="return confirm('Log out of the admin panel?')">
                  <i data-feather="log-out" class="me-2 w-[18px] h-[18px]"></i>
                  Log Out
                </a>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </div>
</header>
<!-- [ Admin Header ] end -->
