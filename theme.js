(function () {
    var root = document.documentElement;
    var toggle = document.getElementById('themeToggle');
    var savedTheme = localStorage.getItem('theme');
    var initialTheme = savedTheme === 'dark' ? 'dark' : 'light';

    function applyTheme(theme) {
        root.dataset.theme = theme;
        localStorage.setItem('theme', theme);

        if (toggle) {
            var isDark = theme === 'dark';
            toggle.setAttribute('aria-pressed', String(isDark));
            toggle.setAttribute('aria-label', isDark ? '라이트 모드로 전환' : '다크 모드로 전환');
        }
    }

    applyTheme(initialTheme);

    if (toggle) {
        toggle.onclick = function () {
            applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
        };
    }
})();
