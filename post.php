<?php
require_once 'db.php';
require_once 'layout.php';

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$post_id) {
    http_response_code(404);
    die('게시글을 찾을 수 없습니다.');
}

$post = find_post($post_id);

if (!$post) {
    http_response_code(404);
    die('게시글을 찾을 수 없습니다.');
}

$can_edit = is_logged_in() && (int) $post['user_id'] === (int) $_SESSION['user_id'];
$can_delete = $can_edit || is_admin();

render_header($post['title'] . ' - AIWeb2', 'home');
?>
    <main class="shell">
        <article class="tool-panel detail-panel">
            <a href="index.php#posts" class="back-link">목록으로</a>
            <header class="detail-head">
                <p class="eyebrow">게시글 #<?= e($post['id']) ?></p>
                <h1><?= e($post['title']) ?></h1>
                <div class="detail-meta">
                    <span><?= e($post['username'] ? $post['username'] : '삭제된 계정') ?></span>
                    <time>작성 <?= e($post['created_at']) ?></time>
                    <?php if ($post['updated_at']): ?>
                        <time>수정 <?= e($post['updated_at']) ?></time>
                    <?php endif; ?>
                </div>
                <?php if ($can_edit || $can_delete): ?>
                    <div class="detail-actions">
                        <?php if ($can_edit): ?>
                            <a href="edit_post.php?id=<?= urlencode((string) $post['id']) ?>" class="btn">수정</a>
                        <?php endif; ?>
                        <?php if ($can_delete): ?>
                            <form method="POST" action="delete_post.php" onsubmit="return confirm('게시글을 삭제할까요?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= e($post['id']) ?>">
                                <button type="submit" class="btn btn-danger">삭제</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </header>
            <div class="detail-body">
                <?= nl2br(e($post['content'])) ?>
            </div>
        </article>
    </main>
<?php render_footer(); ?>
