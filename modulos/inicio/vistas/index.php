<h1>MOTOCONS</h1>
<p class="inicio-subtitulo">Elige un módulo para entrar.</p>

<?php if (!$modulos): ?>
    <div class="alerta alerta-aviso">Todavía no hay módulos. Cada grupo agrega el suyo con un <code>modulo.php</code> en su carpeta.</div>
<?php else: ?>
    <div class="rejilla">
        <?php foreach ($modulos as $m): ?>
            <div class="tarjeta inicio-modulo">
                <h2><?= htmlspecialchars($m['nombre']) ?></h2>
                <p><?= htmlspecialchars($m['descripcion']) ?></p>
                <a class="btn" href="index.php?r=<?= htmlspecialchars($m['ruta']) ?>">Entrar</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
