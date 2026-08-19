  <main class="main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="hamburger" id="hamburger" aria-label="Open menu">☰</button>
        <div>
          <div class="page-title"><?= e($page_title) ?></div>
          <?php if ($page_sub): ?><div class="page-sub"><?= e($page_sub) ?></div><?php endif; ?>
        </div>
      </div>
      <div class="topbar-right">
        <div class="profile-menu">
          <button class="avatar avatar-btn" id="profileMenuBtn" style="width:34px;height:34px;font-size:13px;" aria-haspopup="true" aria-expanded="false">
            <?= e(strtoupper(substr($user['full_name'], 0, 1))) ?>
          </button>
          <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-dropdown-head">
              <div class="avatar" style="width:36px;height:36px;"><?= e(strtoupper(substr($user['full_name'], 0, 1))) ?></div>
              <div style="min-width:0;">
                <div class="pd-name"><?= e($user['full_name']) ?></div>
                <div class="pd-email"><?= e($user['email']) ?></div>
              </div>
            </div>
            <a href="profile.php">👤 View Profile</a>
            <a href="settings.php">⚙️ Settings</a>
            <div class="dropdown-divider"></div>
            <div class="dropdown-theme-row">
              <span>🌙 Dark mode</span>
              <label class="theme-switch">
                <input type="checkbox" id="themeSwitchTopbar" class="theme-switch-input">
                <span class="slider"></span>
              </label>
            </div>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="pd-logout">↪ Log out</a>
          </div>
        </div>
      </div>
    </header>
    <div class="page-content">
      <?php $err = flash('error'); $ok = flash('success'); ?>
      <?php if ($err): ?><div class="alert alert-error" data-autohide>⚠ <?= e($err) ?></div><?php endif; ?>
      <?php if ($ok): ?><div class="alert alert-success" data-autohide>✓ <?= e($ok) ?></div><?php endif; ?>
