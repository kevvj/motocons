</main>
<footer class="pie">MOTOCONS · Diplomado en Desarrollo Web en la Nube</footer>
<?php // El JS del módulo se carga solo si existe public/assets/js/<modulo>.js ?>
<?php if (file_exists(RAIZ . '/public/assets/js/' . $this->modulo . '.js')): ?>
    <script src="assets/js/<?= $this->modulo ?>.js"></script>
<?php endif; ?>
</body>
</html>
