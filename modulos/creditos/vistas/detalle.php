<?php
$pesos = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
$fecha = fn(?string $f) => $f ? date('d/m/Y', strtotime($f)) : '—';
$etiquetasSolicitud = [
    'BORRADOR'           => 'Borrador',
    'EN_PROCESO_CREDITO' => 'En proceso',
    'APROBADA'           => 'Aprobada',
    'RECHAZADA'          => 'Rechazada',
    'ANULADA'            => 'Anulada',
];
$etiquetasChecklist = [
    'PENDIENTE' => 'Pendiente',
    'VALIDADO'  => 'Validado',
    'RECHAZADO' => 'Rechazado',
];
?>
<div class="creditos-cabecera">
    <h1><?= $solicitud ? 'Solicitud #' . (int) $solicitud['id'] : 'Solicitud' ?></h1>
    <a class="btn btn-secundario btn-chico" href="index.php?r=creditos/index">Volver</a>
</div>

<?php if ($mensaje): ?><div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (!$solicitud): ?>
    <p>No se encontró la solicitud.</p>
<?php else: ?>

    <section class="tarjeta creditos-resumen-solicitud">
        <span class="creditos-estado creditos-estado-<?= strtolower($solicitud['estado']) ?>">
            <?= $etiquetasSolicitud[$solicitud['estado']] ?? htmlspecialchars($solicitud['estado']) ?>
        </span>
        <div class="creditos-barra creditos-barra-grande" title="<?= $avance ?>% del checklist">
            <div class="creditos-barra-relleno" style="width: <?= $avance ?>%"></div>
        </div>
        <p class="creditos-barra-texto"><?= $avance ?>% del checklist completado</p>

        <dl class="creditos-datos">
            <dt>Cliente</dt><dd><?= htmlspecialchars($solicitud['cliente']) ?> · <?= htmlspecialchars($solicitud['documento']) ?></dd>
            <dt>Venta</dt><dd><?= htmlspecialchars($solicitud['numero_factura'] ?? ('Venta #' . $solicitud['venta_id'])) ?></dd>
            <dt>Unidad</dt><dd><?= $solicitud['marca'] ? htmlspecialchars($solicitud['marca'] . ' ' . $solicitud['modelo'] . ' · ' . ($solicitud['placa'] ?: $solicitud['chasis'])) : 'Sin unidad asignada' ?></dd>
            <dt>Medio de pago</dt><dd><?= htmlspecialchars($solicitud['medio_pago']) ?></dd>
            <dt>Monto financiado</dt><dd><?= $pesos($solicitud['monto_financiado']) ?></dd>
            <dt>Radicada</dt><dd><?= $fecha($solicitud['creado_en']) ?> <?= $solicitud['registrado_por'] ? '· ' . htmlspecialchars($solicitud['registrado_por']) : '' ?></dd>
            <?php if ($solicitud['aprobado_por']): ?>
                <dt>Dictaminada por</dt><dd><?= htmlspecialchars($solicitud['aprobado_por']) ?></dd>
            <?php endif; ?>
            <?php if ($solicitud['observacion']): ?>
                <dt>Observación</dt><dd><?= htmlspecialchars($solicitud['observacion']) ?></dd>
            <?php endif; ?>
        </dl>
    </section>

    <h2>Checklist de requisitos</h2>
    <div class="creditos-checklist">
        <?php foreach ($checklist as $r): ?>
            <div class="tarjeta creditos-requisito">
                <div class="creditos-requisito-info">
                    <strong><?= htmlspecialchars($r['descripcion']) ?></strong>
                    <?php if (!$r['obligatorio']): ?><span class="creditos-opcional">opcional</span><?php endif; ?>
                    <span class="creditos-estado creditos-estado-<?= strtolower($r['estado']) ?>">
                        <?= $etiquetasChecklist[$r['estado']] ?? htmlspecialchars($r['estado']) ?>
                    </span>
                    <?php if ($r['validador']): ?>
                        <span class="creditos-validador">por <?= htmlspecialchars($r['validador']) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($r['soporte_adjunto']): ?>
                    <a class="btn btn-chico btn-secundario" target="_blank"
                       href="index.php?r=creditos/soporte&solicitud=<?= (int) $solicitud['id'] ?>&requisito=<?= (int) $r['requisito_id'] ?>">Ver soporte</a>
                <?php endif; ?>

                <?php if ($editable && $puedeGestionar): ?>
                    <form class="creditos-adjuntar" method="post" action="index.php?r=creditos/adjuntar" enctype="multipart/form-data">
                        <input type="hidden" name="solicitud_id" value="<?= (int) $solicitud['id'] ?>">
                        <input type="hidden" name="requisito_id" value="<?= (int) $r['requisito_id'] ?>">
                        <input type="file" name="soporte" accept=".pdf,.png,.jpg,.jpeg" required>
                        <button type="submit" class="btn btn-chico">Adjuntar</button>
                    </form>
                <?php endif; ?>

                <?php if ($editable && $puedeDictaminar): ?>
                    <div class="creditos-evaluar">
                        <form method="post" action="index.php?r=creditos/evaluar">
                            <input type="hidden" name="solicitud_id" value="<?= (int) $solicitud['id'] ?>">
                            <input type="hidden" name="requisito_id" value="<?= (int) $r['requisito_id'] ?>">
                            <input type="hidden" name="estado" value="VALIDADO">
                            <button type="submit" class="btn btn-chico">Validar</button>
                        </form>
                        <form method="post" action="index.php?r=creditos/evaluar">
                            <input type="hidden" name="solicitud_id" value="<?= (int) $solicitud['id'] ?>">
                            <input type="hidden" name="requisito_id" value="<?= (int) $r['requisito_id'] ?>">
                            <input type="hidden" name="estado" value="RECHAZADO">
                            <button type="submit" class="btn btn-chico btn-secundario">Rechazar</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($editable && ($puedeDictaminar || $puedeGestionar)): ?>
        <div class="creditos-acciones-finales">
            <?php if ($puedeDictaminar): ?>
                <form method="post" action="index.php?r=creditos/aprobar">
                    <input type="hidden" name="solicitud_id" value="<?= (int) $solicitud['id'] ?>">
                    <button type="submit" class="btn">Aprobar crédito</button>
                </form>
                <form method="post" action="index.php?r=creditos/rechazar"
                      onsubmit="return confirm('¿Rechazar este crédito? El cliente quedará sin financiación por esta vía.');">
                    <input type="hidden" name="solicitud_id" value="<?= (int) $solicitud['id'] ?>">
                    <button type="submit" class="btn btn-secundario">Rechazar crédito</button>
                </form>
            <?php endif; ?>
            <?php if ($puedeGestionar): ?>
                <form method="post" action="index.php?r=creditos/anular"
                      onsubmit="return confirm('¿Anular esta solicitud? No se puede deshacer.');">
                    <input type="hidden" name="solicitud_id" value="<?= (int) $solicitud['id'] ?>">
                    <button type="submit" class="btn btn-peligro">Anular solicitud</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>
