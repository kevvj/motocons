<?php
$pesos = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
?>
<div class="creditos-cabecera">
    <h1>Radicar solicitud de crédito</h1>
    <a class="btn btn-secundario btn-chico" href="index.php?r=creditos/index">Volver</a>
</div>

<?php if ($error): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="tarjeta creditos-buscador" method="get" action="index.php">
    <input type="hidden" name="r" value="creditos/radicar">

    <div class="campo">
        <label for="venta_id">Venta</label>
        <select id="venta_id" name="venta_id" onchange="this.form.submit()" required>
            <option value="">Elige la venta que se va a financiar...</option>
            <?php foreach ($ventas as $v): ?>
                <option value="<?= (int) $v['id'] ?>" <?= (int) $v['id'] === $ventaId ? 'selected' : '' ?>>
                    <?= htmlspecialchars(($v['numero_factura'] ?? 'sin factura aún') . ' · ' . $v['cliente'] . ' · ' . ($v['marca'] ? $v['marca'] . ' ' . $v['modelo'] : 'sin unidad asignada')) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!$ventas): ?>
        <p class="creditos-nota">No hay ventas sin solicitud de crédito todavía.</p>
    <?php endif; ?>

    <div class="campo">
        <label for="medio_pago">Medio de pago</label>
        <select id="medio_pago" name="medio_pago" onchange="this.form.submit()" required>
            <option value="">Elige el medio de pago...</option>
            <?php foreach ($mediosDePago as $m): ?>
                <option value="<?= htmlspecialchars($m['medio_pago']) ?>" <?= $m['medio_pago'] === $medioPago ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['medio_pago']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<?php if ($checklistPreview): ?>
    <section class="tarjeta creditos-preview-checklist">
        <h2>Esto es lo que va a pedir <?= htmlspecialchars($medioPago) ?></h2>
        <ul class="creditos-preview-lista">
            <?php foreach ($checklistPreview as $r): ?>
                <li>
                    <?= htmlspecialchars($r->getDescripcion()) ?>
                    <?php if (!$r->isObligatorio()): ?><span class="creditos-opcional">opcional</span><?php endif; ?>
                    <?php if ($r->esReciboGas()): ?><span class="creditos-opcional">obligatorio para aprobar</span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="creditos-nota">Así lo verá el analista al revisar el checklist, apenas se radique la solicitud.</p>
    </section>
<?php endif; ?>

<?php if ($venta && $medioPago !== ''): ?>
    <form class="tarjeta creditos-form" method="post" action="index.php?r=creditos/radicar">
        <input type="hidden" name="venta_id" value="<?= (int) $venta['id'] ?>">
        <input type="hidden" name="medio_pago" value="<?= htmlspecialchars($medioPago) ?>">

        <dl class="creditos-datos">
            <dt>Cliente</dt><dd><?= htmlspecialchars($venta['cliente']) ?> · <?= htmlspecialchars($venta['documento']) ?></dd>
            <dt>Venta</dt><dd><?= htmlspecialchars($venta['numero_factura'] ?? 'sin factura aún (se asigna al pagar)') ?> · <?= $pesos($venta['total']) ?></dd>
            <dt>Medio de pago</dt><dd><?= htmlspecialchars($medioPago) ?></dd>
        </dl>

        <?php if ($vehiculos): ?>
            <div class="campo">
                <label for="vehiculo_id">Unidad a financiar</label>
                <select id="vehiculo_id" name="vehiculo_id">
                    <?php foreach ($vehiculos as $veh): ?>
                        <option value="<?= (int) $veh['id'] ?>">
                            <?= htmlspecialchars($veh['marca'] . ' ' . $veh['modelo'] . ' · ' . ($veh['placa'] ?: $veh['chasis'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div class="campo">
            <label for="monto_financiado">Monto a financiar</label>
            <input id="monto_financiado" name="monto_financiado" type="number" min="0" step="1000" required>
        </div>

        <div class="campo">
            <label for="observacion">Observación (opcional)</label>
            <textarea id="observacion" name="observacion" rows="2"></textarea>
        </div>

        <button type="submit" class="btn btn-bloque">Radicar solicitud</button>
    </form>
<?php elseif ($ventaId > 0 && $medioPago === ''): ?>
    <p class="creditos-nota">Elige también el medio de pago para ver el checklist y completar la radicación.</p>
<?php endif; ?>
