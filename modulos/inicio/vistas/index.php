<?php
$nombreCorto = explode(' ', trim($usuario['nombre']))[0];
$roles = [
    'admin' => 'Administrador',
    'vendedor' => 'Vendedor',
    'mecanico' => 'Mecánico',
    'analista' => 'Analista de crédito',
    'cliente' => 'Cliente',
];
?>

<section class="inicio-saludo">
    <p class="inicio-etiqueta"><?= $esCliente ? 'Mi cuenta' : 'Panel de trabajo · ' . htmlspecialchars($roles[$usuario['rol']] ?? ucfirst($usuario['rol'])) ?></p>
    <h1>Hola, <?= htmlspecialchars($nombreCorto) ?>.</h1>
    <p class="inicio-lema">
        <?= $esCliente
            ? 'Aquí puedes ver tus motos, revisar su garantía y mirar las promociones vigentes.'
            : '¿Qué vas a hacer hoy?' ?>
    </p>
</section>

<?php if (!$modulos): ?>
    <div class="alerta alerta-aviso">Tu usuario todavía no tiene opciones asignadas. Habla con el administrador.</div>
<?php else: ?>
    <div class="rejilla">
        <?php foreach ($modulos as $m): ?>
            <a class="tarjeta inicio-modulo" href="index.php?r=<?= htmlspecialchars($m['ruta']) ?>">
                <h2><?= htmlspecialchars($m['nombre']) ?></h2>
                <p><?= htmlspecialchars($m['descripcion']) ?></p>
                <span class="inicio-ir">Entrar →</span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($esCliente): ?>
    <section class="inicio-cliente">
        <div>
            <h2>¿Tu moto necesita algo?</h2>
            <p>Acércate a la tienda o al taller con tu placa o número de factura y te atendemos.</p>
        </div>
    </section>
<?php endif; ?>
