<?php
$fecha = static fn(string $valor): string => date('d/m/Y', strtotime($valor));
$pesos = static fn($valor): string => '$' . number_format((float) $valor, 0, ',', '.');
$claseEstado = static fn(string $e): string => 'estado-' . strtolower(str_replace('_', '-', $e));
// Enlace al listado con un filtro de estado y una búsqueda.
$enlace = static fn(string $e, string $q): string => 'index.php?' . http_build_query(array_filter([
    'r' => 'ventas_presenciales/index',
    'estado' => $e,
    'q' => $q,
]));
?>
<header class="vp-cabecera">
    <div>
        <p class="vp-antetitulo">Punto de venta</p>
        <h1>Ventas en tienda</h1>
        <p class="vp-subtitulo">Registra la venta, confirma el pago y la moto queda lista para garantía y taller.</p>
    </div>
    <a class="btn" href="index.php?r=ventas_presenciales/nueva">Nueva venta</a>
</header>

<?php if ($mensaje): ?>
    <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<nav class="vp-filtros" aria-label="Filtrar por estado">
    <a class="vp-filtro <?= $estado === '' ? 'es-activo' : '' ?>" href="<?= htmlspecialchars($enlace('', $busqueda)) ?>">
        Todas <span><?= array_sum($conteo) ?></span>
    </a>
    <?php foreach ($estados as $clave => $texto): ?>
        <a class="vp-filtro <?= $estado === $clave ? 'es-activo' : '' ?>" href="<?= htmlspecialchars($enlace($clave, $busqueda)) ?>">
            <?= htmlspecialchars($texto) ?> <span><?= (int) $conteo[$clave] ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<form class="vp-buscador" method="get" action="index.php">
    <input type="hidden" name="r" value="ventas_presenciales/index">
    <?php if ($estado !== ''): ?>
        <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
    <?php endif; ?>
    <div class="campo">
        <label for="q">Buscar por factura, documento o nombre del cliente</label>
        <input id="q" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: FV-0001 o 1067123456">
    </div>
    <button type="submit" class="btn">Buscar</button>
    <?php if ($busqueda !== ''): ?>
        <a class="btn btn-secundario" href="<?= htmlspecialchars($enlace($estado, '')) ?>">Limpiar</a>
    <?php endif; ?>
</form>

<div class="vp-tabla">
    <table class="tabla">
        <thead>
        <tr>
            <th>Factura</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th class="vp-numero-col">Motos</th>
            <th class="vp-numero-col">Total</th>
            <th>Estado</th>
            <th>Vendedor</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$ventas): ?>
            <tr><td colspan="8" class="vp-vacio">No hay ventas con ese filtro.</td></tr>
        <?php endif; ?>
        <?php foreach ($ventas as $v): ?>
            <tr>
                <td>
                    <?php if ($v['numero_factura'] !== null): ?>
                        <strong class="vp-factura"><?= htmlspecialchars($v['numero_factura']) ?></strong>
                    <?php else: ?>
                        <span class="vp-suave">Sin factura</span>
                    <?php endif; ?>
                </td>
                <td><?= $fecha($v['fecha']) ?></td>
                <td>
                    <?= htmlspecialchars($v['cliente']) ?>
                    <small>CC <?= htmlspecialchars($v['documento']) ?></small>
                </td>
                <td class="vp-numero-col"><?= (int) $v['unidades'] ?></td>
                <td class="vp-numero-col"><?= $pesos($v['total']) ?></td>
                <td><span class="vp-estado <?= $claseEstado($v['estado']) ?>"><?= htmlspecialchars($estados[$v['estado']]) ?></span></td>
                <td><?= htmlspecialchars($v['vendedor'] ?? 'Sin registro') ?></td>
                <td><a class="btn btn-secundario btn-chico" href="index.php?r=ventas_presenciales/ver&amp;id=<?= (int) $v['id'] ?>">Ver</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
