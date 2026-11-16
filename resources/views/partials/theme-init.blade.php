{{-- apply the saved / system theme before the first paint to avoid a flash --}}
<script>
    (function () {
        var theme = null;
        try { theme = localStorage.getItem('theme'); } catch (e) {}
        if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    })();
</script>
