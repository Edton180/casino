{{-- Celular e tablet: o menu lateral do painel abre fechado, sem cobrir a página (no computador nada muda). --}}
<script>
    try {
        if (window.innerWidth < 1024) {
            localStorage.setItem('isOpen', 'false');
        }
    } catch (e) {}
</script>
