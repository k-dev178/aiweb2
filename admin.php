<?php
require_once 'db.php';
require_once 'layout.php';
require_admin();

$flash = flash_take('admin_flash');
$error = flash_take('admin_error');

function admin_redirect() {
    redirect_to('admin.php');
}

function admin_normalize_account($username, $email) {
    if (!is_valid_username($username)) {
        throw new RuntimeException('아이디는 영문, 숫자, 밑줄 3~50자로 입력해 주세요.');
    }

    if ($email === '') {
        $email = $username . '@gemma.sm.jj.ac.kr';
    }

    $email = strtolower($email);

    if (text_length($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('이메일 형식이 올바르지 않습니다.');
    }

    if (!is_allowed_email_domain($email)) {
        throw new RuntimeException('이메일은 gemma.sm.jj.ac.kr 도메인만 사용할 수 있습니다.');
    }

    return array($username, $email);
}

function admin_optional_field($value, $maxLength, $label) {
    $value = trim((string) $value);

    if (text_length($value) > $maxLength) {
        throw new RuntimeException($label . ' 값이 너무 깁니다.');
    }

    return $value === '' ? null : $value;
}

function admin_validate_password($password, $allowEmpty) {
    if ($password === '' && $allowEmpty) {
        return;
    }

    if (strlen($password) < 6 || strlen($password) > 128) {
        throw new RuntimeException('비밀번호는 6~128자로 입력해 주세요.');
    }
}

function admin_find_user($id) {
    $user = find_user_by_id($id);

    if (!$user) {
        throw new RuntimeException('계정을 찾을 수 없습니다.');
    }

    return $user;
}

function admin_ensure_unique_account($id, $username, $email) {
    if (user_login_exists($id, $username, $email)) {
        throw new RuntimeException('이미 사용 중인 아이디 또는 이메일입니다.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = isset($_POST['action']) ? $_POST['action'] : '';

    try {
        if ($action === 'create') {
            $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $ip_address = admin_optional_field(isset($_POST['ip_address']) ? $_POST['ip_address'] : '', 20, '아이피');
            $room_name = admin_optional_field(isset($_POST['room_name']) ? $_POST['room_name'] : '', 30, '룸명');
            $room_number = admin_optional_field(isset($_POST['room_number']) ? $_POST['room_number'] : '', 30, '룸번');
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;

            list($username, $email) = admin_normalize_account($username, $email);
            admin_ensure_unique_account(0, $username, $email);

            if ($password === '') {
                $password = 'wjsansrk';
            }
            admin_validate_password($password, false);

            if ($username === 'gemma') {
                $is_admin = 1;
            }

            $users = all_users();
            $users[] = array(
                'id' => next_id($users),
                'username' => $username,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'ip_address' => $ip_address,
                'room_name' => $room_name,
                'room_number' => $room_number,
                'is_admin' => $is_admin,
                'created_at' => now_string(),
            );
            save_users($users);

            $_SESSION['admin_flash'] = '계정이 추가되었습니다.';
            admin_redirect();
        }

        if ($action === 'update') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
            $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $ip_address = admin_optional_field(isset($_POST['ip_address']) ? $_POST['ip_address'] : '', 20, '아이피');
            $room_name = admin_optional_field(isset($_POST['room_name']) ? $_POST['room_name'] : '', 30, '룸명');
            $room_number = admin_optional_field(isset($_POST['room_number']) ? $_POST['room_number'] : '', 30, '룸번');
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;

            if (!$id) {
                throw new RuntimeException('수정할 계정을 찾을 수 없습니다.');
            }

            $existingUser = admin_find_user($id);
            list($username, $email) = admin_normalize_account($username, $email);
            admin_ensure_unique_account($id, $username, $email);
            admin_validate_password($password, true);

            if ($existingUser['username'] === 'gemma' && $username !== 'gemma') {
                throw new RuntimeException('gemma 계정명은 변경할 수 없습니다.');
            }

            if ($existingUser['username'] === 'gemma' || $username === 'gemma' || $id === (int) $_SESSION['user_id']) {
                $is_admin = 1;
            }

            $users = all_users();
            foreach ($users as $index => $user) {
                if ((int) $user['id'] === (int) $id) {
                    $users[$index]['username'] = $username;
                    $users[$index]['email'] = $email;
                    $users[$index]['ip_address'] = $ip_address;
                    $users[$index]['room_name'] = $room_name;
                    $users[$index]['room_number'] = $room_number;
                    $users[$index]['is_admin'] = $is_admin;
                    if ($password !== '') {
                        $users[$index]['password'] = password_hash($password, PASSWORD_BCRYPT);
                    }
                    break;
                }
            }
            save_users($users);

            if ($id === (int) $_SESSION['user_id']) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['is_admin'] = true;
            }

            $_SESSION['admin_flash'] = '계정 정보가 수정되었습니다.';
            admin_redirect();
        }

        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (!$id) {
                throw new RuntimeException('삭제할 계정을 찾을 수 없습니다.');
            }

            $existingUser = admin_find_user($id);

            if ($id === (int) $_SESSION['user_id']) {
                throw new RuntimeException('현재 로그인한 관리자 계정은 삭제할 수 없습니다.');
            }

            if ($existingUser['username'] === 'gemma') {
                throw new RuntimeException('gemma 관리자 계정은 삭제할 수 없습니다.');
            }

            $users = all_users();
            $remaining = array();
            foreach ($users as $user) {
                if ((int) $user['id'] !== (int) $id) {
                    $remaining[] = $user;
                }
            }
            save_users($remaining);

            $posts = all_posts();
            foreach ($posts as $index => $post) {
                if ((int) $post['user_id'] === (int) $id) {
                    $posts[$index]['user_id'] = null;
                }
            }
            save_posts($posts);

            $_SESSION['admin_flash'] = '계정이 삭제되었습니다.';
            admin_redirect();
        }

        throw new RuntimeException('처리할 작업을 찾을 수 없습니다.');
    } catch (RuntimeException $e) {
        $_SESSION['admin_error'] = $e->getMessage();
        admin_redirect();
    }
}

$users = all_users();
usort($users, function ($a, $b) {
    return strcmp($a['username'], $b['username']);
});

render_header('관리자 - AIWeb2', 'admin');
?>
    <main class="shell wide-shell">
        <section class="workspace-head compact-head">
            <div>
                <p class="eyebrow">관리자</p>
                <h1>계정 관리</h1>
            </div>
            <div class="metric-strip" aria-label="계정 현황">
                <div>
                    <strong><?= count($users) ?></strong>
                    <span>계정</span>
                </div>
            </div>
        </section>

        <?php if ($flash): ?>
            <div class="alert alert-success page-alert"><?= e($flash) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error page-alert"><?= e($error) ?></div>
        <?php endif; ?>

        <section class="tool-panel">
            <div class="panel-title">
                <h2>계정 추가</h2>
                <span>기본 비밀번호 wjsansrk</span>
            </div>
            <form method="POST" action="admin.php" class="admin-create-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <input type="text" name="username" placeholder="아이디" required>
                <input type="email" name="email" placeholder="이메일 생략 가능">
                <input type="password" name="password" placeholder="비밀번호">
                <input type="text" name="ip_address" placeholder="아이피">
                <input type="text" name="room_name" placeholder="룹명">
                <input type="text" name="room_number" placeholder="룹번">
                <label class="check-label">
                    <input type="checkbox" name="is_admin" value="1">
                    관리자
                </label>
                <button type="submit" class="btn btn-primary">추가</button>
            </form>
        </section>

        <section class="tool-panel">
            <div class="panel-title">
                <h2>계정 목록</h2>
                <span><?= count($users) ?>개</span>
            </div>
            <div class="admin-list">
                <?php foreach ($users as $user): ?>
                    <div class="admin-row">
                        <div class="admin-summary">
                            <strong><?= e($user['username']) ?></strong>
                            <span>아이피 <?= e($user['ip_address'] ? $user['ip_address'] : '-') ?></span>
                            <span>룹명 <?= e($user['room_name'] ? $user['room_name'] : '-') ?></span>
                            <span>룹번 <?= e($user['room_number'] ? $user['room_number'] : '-') ?></span>
                        </div>
                        <form method="POST" action="admin.php" class="admin-edit-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                            <input type="text" name="username" value="<?= e($user['username']) ?>" required>
                            <input type="email" name="email" value="<?= e($user['email']) ?>" required>
                            <input type="password" name="password" placeholder="새 비밀번호">
                            <input type="text" name="ip_address" value="<?= e($user['ip_address'] ? $user['ip_address'] : '') ?>" placeholder="아이피">
                            <input type="text" name="room_name" value="<?= e($user['room_name'] ? $user['room_name'] : '') ?>" placeholder="룹명">
                            <input type="text" name="room_number" value="<?= e($user['room_number'] ? $user['room_number'] : '') ?>" placeholder="룹번">
                            <label class="check-label">
                                <input type="checkbox" name="is_admin" value="1" <?= $user['is_admin'] ? 'checked' : '' ?> <?= ((int) $user['id'] === (int) $_SESSION['user_id'] || $user['username'] === 'gemma') ? 'disabled' : '' ?>>
                                관리자
                            </label>
                            <button type="submit" class="btn btn-primary">저장</button>
                        </form>
                        <form method="POST" action="admin.php" onsubmit="return confirm('이 계정을 삭제할까요?');" class="delete-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
                            <button type="submit" class="btn btn-danger" <?= ((int) $user['id'] === (int) $_SESSION['user_id'] || $user['username'] === 'gemma') ? 'disabled' : '' ?>>삭제</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
<?php render_footer(); ?>
