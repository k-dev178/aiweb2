<?php
$sessionSecure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_id() === '') {
    session_set_cookie_params(0, '/', '', $sessionSecure, true);
    session_start();
}

if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
}

if (file_exists(dirname(__FILE__) . '/config.php')) {
    require dirname(__FILE__) . '/config.php';
}

if (!isset($AIWEB_STORAGE)) {
    $AIWEB_STORAGE = 'file';
}
if (!isset($AIWEB_DB_HOST)) {
    $AIWEB_DB_HOST = 'localhost';
}
if (!isset($AIWEB_DB_NAME)) {
    $AIWEB_DB_NAME = 'aiweb2';
}
if (!isset($AIWEB_DB_USER)) {
    $AIWEB_DB_USER = 'aiweb2_user';
}
if (!isset($AIWEB_DB_PASS)) {
    $AIWEB_DB_PASS = 'wjsansrk';
}

if (!defined('PASSWORD_BCRYPT')) {
    define('PASSWORD_BCRYPT', 1);
}

if (!defined('PASSWORD_DEFAULT')) {
    define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);
}

if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string) {
        if (!is_string($known_string) || !is_string($user_string)) {
            return false;
        }

        if (strlen($known_string) !== strlen($user_string)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($known_string); $i++) {
            $result |= ord($known_string[$i]) ^ ord($user_string[$i]);
        }

        return $result === 0;
    }
}

if (!function_exists('password_hash')) {
    function password_hash($password, $algo, array $options = array()) {
        $cost = isset($options['cost']) ? (int) $options['cost'] : 10;
        $cost = max(4, min(31, $cost));

        if (function_exists('openssl_random_pseudo_bytes')) {
            $rawSalt = openssl_random_pseudo_bytes(16);
        } else {
            $rawSalt = uniqid(mt_rand(), true);
        }

        if ($rawSalt === false || $rawSalt === '') {
            $rawSalt = uniqid(mt_rand(), true);
        }

        $salt = substr(str_replace('=', '', strtr(base64_encode($rawSalt), '+', '.')), 0, 22);
        $hash = crypt($password, sprintf('$2y$%02d$', $cost) . $salt);

        return strlen($hash) >= 60 ? $hash : false;
    }
}

if (!function_exists('password_verify')) {
    function password_verify($password, $hash) {
        if (!is_string($hash) || $hash === '') {
            return false;
        }

        return hash_equals($hash, crypt($password, $hash));
    }
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function text_length($value) {
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }

    return strlen($value);
}

function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function random_hex($bytes) {
    if (function_exists('openssl_random_pseudo_bytes')) {
        $raw = openssl_random_pseudo_bytes($bytes);
        if ($raw !== false && $raw !== '') {
            return bin2hex($raw);
        }
    }

    return sha1(uniqid(mt_rand(), true) . microtime(true));
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = random_hex(32);
    }

    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function require_csrf() {
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        die('잘못된 요청입니다.');
    }
}

function flash_take($key) {
    $value = isset($_SESSION[$key]) ? $_SESSION[$key] : '';
    unset($_SESSION[$key]);

    return $value;
}

function is_valid_username($username) {
    return (bool) preg_match('/\A[a-zA-Z0-9_]{3,50}\z/', $username);
}

function is_allowed_email_domain($email) {
    $email = strtolower($email);
    $suffix = '@gemma.sm.jj.ac.kr';

    return substr($email, -strlen($suffix)) === $suffix;
}

function now_string() {
    return date('Y-m-d H:i:s');
}

function data_dir() {
    return dirname(__FILE__) . '/data';
}

function data_path($name) {
    return data_dir() . '/' . $name . '.json';
}

function ensure_data_dir() {
    $dir = data_dir();
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        http_response_code(500);
        die('data 폴더를 만들 수 없습니다.');
    }

    if (!is_writable($dir)) {
        http_response_code(500);
        die('data 폴더에 쓰기 권한이 필요합니다.');
    }
}

function json_read($name, $fallback) {
    ensure_data_dir();
    $path = data_path($name);

    if (!file_exists($path)) {
        json_write($name, $fallback);
        return $fallback;
    }

    $json = file_get_contents($path);
    $data = json_decode($json, true);

    return is_array($data) ? $data : $fallback;
}

function json_write($name, $data) {
    ensure_data_dir();
    $path = data_path($name);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false || file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        http_response_code(500);
        die('data 파일을 저장할 수 없습니다.');
    }
}

function storage_is_mysql() {
    global $AIWEB_STORAGE;

    return strtolower((string) $AIWEB_STORAGE) === 'mysql';
}

function db_conn() {
    static $db = null;

    if ($db !== null) {
        return $db;
    }

    global $AIWEB_DB_HOST, $AIWEB_DB_NAME, $AIWEB_DB_USER, $AIWEB_DB_PASS;

    if (!class_exists('PDO')) {
        http_response_code(500);
        die('PDO 확장이 필요합니다.');
    }

    try {
        $db = new PDO(
            'mysql:host=' . $AIWEB_DB_HOST . ';dbname=' . $AIWEB_DB_NAME . ';charset=utf8mb4',
            $AIWEB_DB_USER,
            $AIWEB_DB_PASS,
            array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            )
        );
    } catch (PDOException $e) {
        http_response_code(500);
        die('DB 연결 실패. setup.sql 적용과 config.php 설정을 확인하세요.');
    }

    return $db;
}

function seed_users() {
    $hash = password_hash('wjsansrk', PASSWORD_BCRYPT);
    $rows = array(
        array('samuel', null, 'kt, skt, lgt', '1000, 1001, 1002', 0),
        array('yelena', '.155', 'kt', '1000', 0),
        array('scarlett', '.160', 'skt', '1001', 0),
        array('daisy', '.140', 'lgt', '1002', 0),
        array('sienna', '.143', 'lgt', '1002', 0),
        array('yummer', '.138', 'skt', '1001', 0),
        array('gemma', '.149', 'kt', '1000', 1),
        array('ruby', '.158', 'kt', '1000', 0),
        array('giselle', '.170', 'lgt', '1002', 0),
        array('thea', '.150', 'skt', '1001', 0),
        array('kiera', '.145', 'lgt', '1002', 0),
        array('molly', '.154', 'lgt', '1002', 0),
        array('duber', '.151', 'kt', '1000', 0),
        array('amelia', '.153', 'skt', '1001', 0),
        array('gavin', '.167', 'kt', '1000', 0),
        array('glenn', '.146', 'lgt', '1002', 0),
        array('silas', '.147', 'kt', '1000', 0),
        array('nigel', '.148', 'lgt', '1002', 0),
    );
    $users = array();
    $id = 1;

    foreach ($rows as $row) {
        $users[] = array(
            'id' => $id,
            'username' => $row[0],
            'email' => $row[0] . '@gemma.sm.jj.ac.kr',
            'password' => $hash,
            'ip_address' => $row[1],
            'room_name' => $row[2],
            'room_number' => $row[3],
            'is_admin' => $row[4],
            'created_at' => now_string(),
        );
        $id++;
    }

    return $users;
}

function all_users() {
    if (storage_is_mysql()) {
        db_seed_users_if_empty();
        return db_conn()->query('
            SELECT id, username, email, password, ip_address, room_name, room_number, is_admin, created_at
            FROM users
            ORDER BY username ASC
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    return json_read('users', seed_users());
}

function save_users($users) {
    if (storage_is_mysql()) {
        $db = db_conn();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('
                INSERT INTO users (id, username, email, password, ip_address, room_name, room_number, is_admin, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    username = VALUES(username),
                    email = VALUES(email),
                    password = VALUES(password),
                    ip_address = VALUES(ip_address),
                    room_name = VALUES(room_name),
                    room_number = VALUES(room_number),
                    is_admin = VALUES(is_admin),
                    created_at = VALUES(created_at)
            ');
            $ids = array();
            foreach ($users as $user) {
                $ids[] = (int) $user['id'];
                $stmt->execute(array(
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $user['password'],
                    isset($user['ip_address']) ? $user['ip_address'] : null,
                    isset($user['room_name']) ? $user['room_name'] : null,
                    isset($user['room_number']) ? $user['room_number'] : null,
                    isset($user['is_admin']) ? (int) $user['is_admin'] : 0,
                    isset($user['created_at']) ? $user['created_at'] : now_string(),
                ));
            }

            if (count($ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $delete = $db->prepare('DELETE FROM users WHERE id NOT IN (' . $placeholders . ')');
                $delete->execute($ids);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        return;
    }

    usort($users, function ($a, $b) {
        return strcmp($a['username'], $b['username']);
    });
    json_write('users', array_values($users));
}

function all_posts() {
    if (storage_is_mysql()) {
        return db_conn()->query('
            SELECT id, user_id, title, content, created_at, updated_at
            FROM posts
            ORDER BY created_at DESC, id DESC
        ')->fetchAll(PDO::FETCH_ASSOC);
    }

    return json_read('posts', array());
}

function save_posts($posts) {
    if (storage_is_mysql()) {
        $db = db_conn();
        $db->beginTransaction();
        try {
            $db->exec('DELETE FROM posts');
            $stmt = $db->prepare('
                INSERT INTO posts (id, user_id, title, content, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            foreach ($posts as $post) {
                $stmt->execute(array(
                    $post['id'],
                    isset($post['user_id']) ? $post['user_id'] : null,
                    $post['title'],
                    $post['content'],
                    isset($post['created_at']) ? $post['created_at'] : now_string(),
                    isset($post['updated_at']) ? $post['updated_at'] : null,
                ));
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
        return;
    }

    json_write('posts', array_values($posts));
}

function db_seed_users_if_empty() {
    $count = (int) db_conn()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = db_conn()->prepare('
        INSERT INTO users (id, username, email, password, ip_address, room_name, room_number, is_admin, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    foreach (seed_users() as $user) {
        $stmt->execute(array(
            $user['id'],
            $user['username'],
            $user['email'],
            $user['password'],
            $user['ip_address'],
            $user['room_name'],
            $user['room_number'],
            $user['is_admin'],
            $user['created_at'],
        ));
    }
}

function next_id($items) {
    $max = 0;
    foreach ($items as $item) {
        if (isset($item['id']) && (int) $item['id'] > $max) {
            $max = (int) $item['id'];
        }
    }

    return $max + 1;
}

function find_user_by_id($id) {
    $users = all_users();
    foreach ($users as $user) {
        if ((int) $user['id'] === (int) $id) {
            return $user;
        }
    }

    return false;
}

function find_user_by_login($login) {
    $login = strtolower($login);
    $users = all_users();
    foreach ($users as $user) {
        if (strtolower($user['username']) === $login || strtolower($user['email']) === $login) {
            return $user;
        }
    }

    return false;
}

function user_login_exists($id, $username, $email) {
    $users = all_users();
    foreach ($users as $user) {
        if ((int) $user['id'] !== (int) $id && ($user['username'] === $username || $user['email'] === $email)) {
            return true;
        }
    }

    return false;
}

function posts_with_authors() {
    $posts = all_posts();
    $users = all_users();
    $map = array();

    foreach ($users as $user) {
        $map[(int) $user['id']] = $user['username'];
    }

    foreach ($posts as $index => $post) {
        $userId = isset($post['user_id']) ? (int) $post['user_id'] : 0;
        $posts[$index]['username'] = isset($map[$userId]) ? $map[$userId] : '';
    }

    usort($posts, function ($a, $b) {
        if ($a['created_at'] === $b['created_at']) {
            return (int) $b['id'] - (int) $a['id'];
        }

        return strcmp($b['created_at'], $a['created_at']);
    });

    return $posts;
}

function find_post($id) {
    $posts = posts_with_authors();
    foreach ($posts as $post) {
        if ((int) $post['id'] === (int) $id) {
            return $post;
        }
    }

    return false;
}

function current_user() {
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    $user = find_user_by_id((int) $_SESSION['user_id']);
    if (!$user) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['email'], $_SESSION['is_admin']);
        return false;
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['is_admin'] = (bool) $user['is_admin'];

    return $user;
}

function is_logged_in() {
    return current_user() !== false;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash'] = '로그인 후 이용할 수 있습니다.';
        redirect_to('login.php');
    }
}

function require_logout() {
    if (is_logged_in()) {
        redirect_to('dashboard.php');
    }
}

function is_admin() {
    $user = current_user();

    return $user && (int) $user['is_admin'] === 1;
}

function require_admin() {
    require_login();

    if (!is_admin()) {
        http_response_code(403);
        die('관리자만 접근할 수 있습니다.');
    }
}
