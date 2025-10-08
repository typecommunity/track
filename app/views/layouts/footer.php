</div> <!-- content-area -->
</div> <!-- main-wrapper -->

</div> <!-- app-container -->

<script>
// Helper para formatar moeda
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

// Helper para formatar número
function formatNumber(value) {
    return new Intl.NumberFormat('pt-BR').format(value);
}

// Auto-submit de filtros
document.querySelectorAll('select[name="period"], select[name="account"], select[name="platform"]').forEach(select => {
    select.addEventListener('change', function() {
        this.form?.submit();
    });
});
</script>

</body>
</html>