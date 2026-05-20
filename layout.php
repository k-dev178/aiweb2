<?php
function nav_class($active, $name) {
    return $active === $name ? ' class="is-active"' : '';
}

function render_header($title, $active) {
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <script>
        document.documentElement.dataset.theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';
    </script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a href="index.php" class="brand" aria-label="AIWeb2 홈">
            <span class="brand-mark">A2</span>
            <span>AIWeb2</span>
        </a>
        <nav class="nav-links" aria-label="주요 메뉴">
            <a href="index.php"<?= nav_class($active, 'home') ?>>게시판</a>
            <?php if (is_logged_in()): ?>
                <?php if (is_admin()): ?>
                    <a href="admin.php"<?= nav_class($active, 'admin') ?>>관리자</a>
                <?php endif; ?>
                <a href="dashboard.php"<?= nav_class($active, 'dashboard') ?>><?= e(isset($_SESSION['username']) ? $_SESSION['username'] : '계정') ?></a>
                <form method="POST" action="logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="link-button">로그아웃</button>
                </form>
            <?php else: ?>
                <a href="login.php"<?= nav_class($active, 'login') ?>>로그인</a>
            <?php endif; ?>
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="다크 모드로 전환" aria-pressed="false">
                <span class="theme-toggle-track">
                    <span class="theme-toggle-thumb"></span>
                </span>
            </button>
        </nav>
    </header>
<?php
}

function render_footer() {
?>
    <script src="theme.js"></script>
</body>
</html>
<?php
}
