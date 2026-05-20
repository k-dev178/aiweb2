<?php
require_once 'db.php';
require_once 'layout.php';

$post_error = '';
$post_flash = flash_take('post_flash');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in()) {
        $_SESSION['flash'] = '로그인 후 게시글을 작성할 수 있습니다.';
        redirect_to('login.php');
    }

    require_csrf();

    $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $content = trim(isset($_POST['content']) ? $_POST['content'] : '');

    if ($title === '' || $content === '') {
        $post_error = '제목과 내용을 모두 입력해 주세요.';
    } elseif (text_length($title) > 140) {
        $post_error = '제목은 140자 이하로 입력해 주세요.';
    } elseif (text_length($content) > 1200) {
        $post_error = '내용은 1200자 이하로 입력해 주세요.';
    } else {
        $posts = all_posts();
        $posts[] = array(
            'id' => next_id($posts),
            'user_id' => (int) $_SESSION['user_id'],
            'title' => $title,
            'content' => $content,
            'created_at' => now_string(),
            'updated_at' => null,
        );
        save_posts($posts);
        $_SESSION['post_flash'] = '게시글이 등록되었습니다.';
        redirect_to('index.php#posts');
    }
}

$posts = posts_with_authors();

render_header('AIWeb2 게시판', 'home');
?>
    <main class="shell">
        <section class="workspace-head">
            <div>
                <p class="eyebrow">CentOS 7 APM 호환 게시판</p>
                <h1>팀 게시판</h1>
            </div>
            <div class="metric-strip" aria-label="게시판 현황">
                <div>
                    <strong><?= count($posts) ?></strong>
                    <span>게시글</span>
                </div>
            </div>
        </section>

        <section class="tool-panel composer-panel">
            <div class="panel-title">
                <h2>새 글 작성</h2>
                <span>1200자 제한</span>
            </div>

            <?php if ($post_flash): ?>
                <div class="alert alert-success"><?= e($post_flash) ?></div>
            <?php endif; ?>
            <?php if ($post_error): ?>
                <div class="alert alert-error"><?= e($post_error) ?></div>
            <?php endif; ?>

            <?php if (is_logged_in()): ?>
                <form method="POST" action="index.php#posts" class="post-form">
                    <?= csrf_field() ?>
                    <div class="form-row">
                        <label for="title">제목</label>
                        <input type="text" id="title" name="title" maxlength="140" value="<?= e(isset($_POST['title']) ? $_POST['title'] : '') ?>" required>
                    </div>
                    <div class="form-row">
                        <label for="content">내용</label>
                        <textarea id="content" name="content" rows="7" maxlength="1200" required><?= e(isset($_POST['content']) ? $_POST['content'] : '') ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">등록</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="notice-line">
                    <span>게시글 작성은 로그인한 사용자만 가능합니다.</span>
                    <a href="login.php" class="btn btn-primary">로그인</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="tool-panel" id="posts">
            <div class="panel-title">
                <h2>게시글 목록</h2>
                <span><?= count($posts) ?>개</span>
            </div>

            <div class="board-table">
                <div class="board-head">
                    <span>번호</span>
                    <span>제목</span>
                    <span>작성자</span>
                    <span>작성일</span>
                </div>
                <?php if (count($posts) === 0): ?>
                    <p class="empty-state">아직 등록된 게시글이 없습니다.</p>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <a class="board-row" href="post.php?id=<?= urlencode((string) $post['id']) ?>">
                            <span class="post-id">#<?= e($post['id']) ?></span>
                            <span class="post-summary">
                                <strong><?= e($post['title']) ?></strong>
                                <small><?= e($post['content']) ?></small>
                            </span>
                            <span><?= e($post['username'] ? $post['username'] : '삭제된 계정') ?></span>
                            <time><?= e($post['created_at']) ?></time>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
<?php render_footer(); ?>
