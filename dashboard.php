<?php
require_once 'db.php';
require_once 'layout.php';
require_login();

$user = current_user();

$myPosts = array();
$posts = posts_with_authors();
foreach ($posts as $post) {
    if ((int) $post['user_id'] === (int) $_SESSION['user_id']) {
        $myPosts[] = $post;
    }
}

render_header('계정 정보 - AIWeb2', 'dashboard');
?>
    <main class="shell">
        <section class="workspace-head compact-head">
            <div>
                <p class="eyebrow">계정 정보</p>
                <h1><?= e($user['username']) ?></h1>
            </div>
            <div class="metric-strip" aria-label="내 활동">
                <div>
                    <strong><?= count($myPosts) ?></strong>
                    <span>내 글</span>
                </div>
                <div>
                    <strong><?= ((int) $user['is_admin'] === 1) ? '관리' : '일반' ?></strong>
                    <span>권한</span>
                </div>
            </div>
        </section>

        <section class="two-column">
            <div class="tool-panel">
                <div class="panel-title">
                    <h2>프로필</h2>
                </div>
                <dl class="info-list">
                    <div>
                        <dt>아이디</dt>
                        <dd><?= e($user['username']) ?></dd>
                    </div>
                    <div>
                        <dt>이메일</dt>
                        <dd><?= e($user['email']) ?></dd>
                    </div>
                    <div>
                        <dt>아이피</dt>
                        <dd><?= e($user['ip_address'] ? $user['ip_address'] : '-') ?></dd>
                    </div>
                    <div>
                        <dt>룹명</dt>
                        <dd><?= e($user['room_name'] ? $user['room_name'] : '-') ?></dd>
                    </div>
                    <div>
                        <dt>룹번</dt>
                        <dd><?= e($user['room_number'] ? $user['room_number'] : '-') ?></dd>
                    </div>
                </dl>
            </div>

            <div class="tool-panel">
                <div class="panel-title">
                    <h2>내 게시글</h2>
                    <span><?= count($myPosts) ?>개</span>
                </div>
                <div class="simple-list">
                    <?php if (count($myPosts) === 0): ?>
                        <p class="empty-state">작성한 게시글이 없습니다.</p>
                    <?php else: ?>
                        <?php foreach ($myPosts as $post): ?>
                            <a href="post.php?id=<?= urlencode((string) $post['id']) ?>">
                                <strong><?= e($post['title']) ?></strong>
                                <time><?= e($post['created_at']) ?></time>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
<?php render_footer(); ?>
