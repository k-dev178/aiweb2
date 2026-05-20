<?php
require_once 'db.php';
require_logout();

$_SESSION['flash'] = '계정은 관리자 페이지에서만 추가할 수 있습니다.';
redirect_to('login.php');
