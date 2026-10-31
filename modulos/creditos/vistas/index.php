<?php
$pesos = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
$fecha = fn(string $f) => date('d/m/Y', strtotime($f));
$etiquetas = [
    'BORRADOR'            => 'Borrador',
    'EN_PROCESO_CREDITO'  => 'En proceso',
    'APROBADA'            => 'Aprobada',
    'RECHAZADA'           => 'Rechazada',
    'ANULADA'             => 'Anulada',
];
?>
<div class="creditos-cabecera">
    <h1>Créditos y financiación</h1>
    <a class="btn" href="index.php?r=creditos/radicar">Radicar solicitud</a>
</div>

<?php if ($mensaje): ?>
    <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<div class="rejilla creditos-kpis">
    <div class="tarjeta creditos-kpi">
        <span class="creditos-kpi-numero"><?= $resumen['total'] ?></span>
        <span class="creditos-kpi-etiqueta">Solicitudes</span>
    </div>
    <div class="tarjeta creditos-kpi">
        <span class="creditos-kpi-numero"><?= $resumen['en_proceso'] ?></span>
        <span class="creditos-kpi-etiqueta">En proceso</span>
    </div>
    <div class="tarjeta creditos-kpi">
        <span class="creditos-kpi-numero"><?= $resumen['aprobadas'] ?></span>
        <span class="creditos-kpi-etiqueta">Aprobadas</span>
    </div>
    <div class="tarjeta creditos-kpi">
        <span class="creditos-kpi-numero"><?= $resumen['rechazadas'] ?></span>
        <span class="creditos-kpi-etiqueta">Rechazadas</span>
    </div>
    <div class="tarjeta creditos-kpi">
        <span class="creditos-kpi-numero"><?= $pesos($resumen['monto']) ?></span>
        <span class="creditos-kpi-etiqueta">Financiado (aprobado)</span>
    </div>
</div>

<form class="creditos-filtro" method="get" action="index.php">
    <input type="hidden" name="r" value="creditos/index">
    <div class="campo creditos-filtro-campo">
        <label for="estado">Filtrar por estado</label>
        <select id="estado" name="estado" onchange="this.form.submit()">
            <option value="">Todas</option>
            <?php foreach ($etiquetas as $clave => $texto): ?>
                <option value="<?= $clave ?>" <?= $clave === $estadoFiltro ? 'selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo creditos-filtro-campo">
        <label for="q">Buscar cliente, factura o placa</label>
        <input id="q" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: Pérez, FV-0001, ABC12D">
    </div>
    <button type="submit" class="btn btn-chico">Buscar</button>
    <?php if ($busqueda !== '' || $estadoFiltro !== ''): ?>
        <a class="btn btn-chico btn-secundario" href="index.php?r=creditos/index">Limpiar</a>
    <?php endif; ?>
</form>

<?php if (!$solicitudes): ?>
    <div class="alerta alerta-aviso">
        No hay solicitudes<?= $estadoFiltro !== '' ? ' en ese estado' : '' ?><?= $busqueda !== '' ? ' que coincidan con "' . htmlspecialchars($busqueda) . '"' : '' ?>.
    </div>
<?php else: ?>
    <div class="creditos-tabla">
        <table class="tabla">
            <thead>
            <tr>
                <th>Venta</th>
                <th>Cliente</th>
                <th>Medio de pago</th>
                <th>Monto</th>
                <th>Avance</th>
                <th>Estado</th>
                <th>Radicada</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($solicitudes as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['numero_factura'] ?? ('Venta #' . $s['venta_id'])) ?></td>
                    <td><?= htmlspecialchars($s['cliente']) ?></td>
                    <td><?= htmlspecialchars($s['medio_pago']) ?></td>
                    <td><?= $pesos($s['monto_financiado']) ?></td>
                    <td>
                        <div class="creditos-barra creditos-barra-chica" title="<?= $s['avance'] ?>% del checklist">
                            <div class="creditos-barra-relleno" style="width: <?= $s['avance'] ?>%"></div>
                        </div>
                    </td>
                    <td><span class="creditos-estado creditos-estado-<?= strtolower($s['estado']) ?>"><?= $etiquetas[$s['estado']] ?? htmlspecialchars($s['estado']) ?></span></td>
                    <td><?= $fecha($s['creado_en']) ?></td>
                    <td><a class="btn btn-chico btn-secundario" href="index.php?r=creditos/detalle&id=<?= (int) $s['id'] ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
