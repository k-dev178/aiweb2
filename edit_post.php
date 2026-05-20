<?php
require_once 'db.php';
require_once 'layout.php';
require_login();

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id) {
    $post_id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$post_id) {
    http_response_code(404);
    die('게시글을 찾을 수 없습니다.');
}

$post = find_post($post_id);

if (!$post) {
    http_response_code(404);
    die('게시글을 찾을 수 없습니다.');
}

if ((int) $post['user_id'] !== (int) $_SESSION['user_id']) {
    http_response_code(403);
    die('본인이 작성한 게시글만 수정할 수 있습니다.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $content = trim(isset($_POST['content']) ? $_POST['content'] : '');

    if ($title === '' || $content === '') {
        $error = '제목과 내용을 모두 입력해 주세요.';
    } elseif (text_length($title) > 140) {
        $error = '제목은 140자 이하로 입력해 주세요.';
    } elseif (text_length($content) > 1200) {
        $error = '내용은 1200자 이하로 입력해 주세요.';
    } else {
        $posts = all_posts();
        foreach ($posts as $index => $storedPost) {
            if ((int) $storedPost['id'] === (int) $post_id && (int) $storedPost['user_id'] === (int) $_SESSION['user_id']) {
                $posts[$index]['title'] = $title;
                $posts[$index]['content'] = $content;
                $posts[$index]['updated_at'] = now_string();
                save_posts($posts);
                redirect_to('post.php?id=' . urlencode((string) $post_id));
            }
        }

        http_response_code(403);
        die('본인이 작성한 게시글만 수정할 수 있습니다.');
    }

    $post['title'] = $title;
    $post['content'] = $content;
}

render_header('게시글 수정 - AIWeb2', 'home');
?>
    <main class="shell">
        <section class="tool-panel composer-panel">
            <div class="panel-title">
                <h2>게시글 수정</h2>
                <a href="post.php?id=<?= urlencode((string) $post_id) ?>">상세로</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="edit_post.php" class="post-form">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                <div class="form-row">
                    <label for="title">제목</label>
                    <input type="text" id="title" name="title" maxlength="140" value="<?= e($post['title']) ?>" required>
                </div>
                <div class="form-row">
                    <label for="content">내용</label>
                    <textarea id="content" name="content" rows="7" maxlength="1200" required><?= e($post['content']) ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">저장</button>
                </div>
            </form>
        </section>
    </main>
<?php render_footer(); ?>
