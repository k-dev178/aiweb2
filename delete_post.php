<?php
require_once 'db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('허용되지 않은 요청입니다.');
}

require_csrf();

$post_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$post_id) {
    http_response_code(404);
    die('게시글을 찾을 수 없습니다.');
}

$posts = all_posts();
$deleted = false;
$remaining = array();

foreach ($posts as $post) {
    $canDelete = (int) $post['id'] === (int) $post_id && (is_admin() || (int) $post['user_id'] === (int) $_SESSION['user_id']);

    if ($canDelete) {
        $deleted = true;
        continue;
    }

    $remaining[] = $post;
}

if (!$deleted) {
    http_response_code(403);
    die('삭제할 수 없는 게시글입니다.');
}

save_posts($remaining);
$_SESSION['post_flash'] = '게시글이 삭제되었습니다.';
redirect_to('index.php#posts');
