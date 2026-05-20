<?php
require_once 'db.php';
require_once 'layout.php';
require_logout();

$error = '';
$flash = flash_take('flash');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $error = '아이디와 비밀번호를 입력해 주세요.';
    } else {
        $user = find_user_by_login($username);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = (bool) $user['is_admin'];
            redirect_to('dashboard.php');
        }

        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
    }
}

render_header('로그인 - AIWeb2', 'login');
?>
    <main class="auth-shell">
        <section class="auth-panel">
            <h1>로그인</h1>
            <p>관리자가 등록한 계정으로 접속합니다.</p>

            <?php if ($flash): ?>
                <div class="alert alert-success"><?= e($flash) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="stack-form">
                <?= csrf_field() ?>
                <label for="username">아이디 또는 이메일</label>
                <input type="text" id="username" name="username" value="<?= e(isset($_POST['username']) ? $_POST['username'] : '') ?>" required autofocus>
                <label for="password">비밀번호</label>
                <input type="password" id="password" name="password" required>
                <button type="submit" class="btn btn-primary">로그인</button>
            </form>
        </section>
    </main>
<?php render_footer(); ?>
