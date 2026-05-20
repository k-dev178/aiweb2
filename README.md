# AIWeb2 게시판

CentOS 7 기본 Apache/PHP 환경에서 동작하는 파일 저장형 게시판입니다. MySQL/MariaDB 없이 `data/*.json` 파일에 계정과 게시글을 저장합니다.

## 환경

- CentOS 7
- Apache httpd
- PHP 5.4 이상
- PHP JSON 확장

CentOS 7 기본 PHP 5.4 문법에 맞춰 작성했습니다.

## 기본 계정

```text
관리자: gemma / wjsansrk
일반 계정: samuel / wjsansrk
```

## 로컬 실행

프로젝트 폴더에서 PHP 내장 서버를 실행합니다.

```bash
php -S 127.0.0.1:8000
```

브라우저에서 접속합니다.

```text
http://127.0.0.1:8000/index.php
```

처음 접속하면 `data/users.json`, `data/posts.json`가 자동 생성됩니다.

## CentOS 7 배포

패키지를 설치합니다.

```bash
sudo yum install -y httpd php
```

프로젝트를 Apache 문서 루트에 배치합니다.

```bash
sudo mkdir -p /var/www/html/aiweb2
sudo cp -R . /var/www/html/aiweb2/
sudo chown -R apache:apache /var/www/html/aiweb2
sudo chmod -R u+rwX,g+rwX /var/www/html/aiweb2/data
```

Apache를 시작합니다.

```bash
sudo systemctl enable httpd
sudo systemctl start httpd
```

접속 주소 예시:

```text
http://서버IP/aiweb2/index.php
```

방화벽을 쓰는 서버라면 HTTP 포트를 엽니다.

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --reload
```

## 저장 파일

```text
data/users.json
data/posts.json
```

백업은 `data` 폴더만 복사하면 됩니다.

## 기능

- 로그인한 사용자만 게시글 작성 가능
- 작성자 본인만 게시글 수정 가능
- 작성자 본인 또는 관리자만 게시글 삭제 가능
- 공개 회원가입 차단
- 관리자 페이지에서 계정 추가, 수정, 삭제 가능
- `gemma` 관리자 계정 보호
- 라이트/다크 모드 전환
