<?php
$fecha = fn(string $f) => date('d/m/Y', strtotime($f));
$pesos = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
$criterios = ['placa' => 'Placa', 'chasis' => 'Número de chasis', 'factura' => 'Número de factura'];
?>
<h1>Consulta de garantía</h1>

<?php if ($esCliente): ?>
    <p class="garantia-subtitulo">Estas son las motos a tu nombre. Elige una para ver el detalle y su historial.</p>
    <?php if (!$misMotos): ?>
        <div class="alerta alerta-aviso">No tienes motos registradas a tu nombre.</div>
    <?php else: ?>
        <div class="rejilla garantia-mis-motos">
            <?php foreach ($misMotos as $mm): ?>
                <?php $mv = $mm['vehiculo']; $mg = $mm['garantia']; ?>
                <a class="tarjeta garantia-moto <?= $mg['vigente'] ? 'es-vigente' : 'es-vencida' ?> <?= $mv['placa'] === strtoupper($valor) ? 'es-elegida' : '' ?>"
                   href="index.php?r=garantia/index&amp;criterio=placa&amp;valor=<?= urlencode($mv['placa']) ?>">
                    <span class="garantia-sello"><?= $mg['vigente'] ? 'Vigente' : 'Vencida' ?></span>
                    <strong><?= htmlspecialchars($mv['marca'] . ' ' . $mv['modelo']) ?></strong>
                    <span>Placa <?= htmlspecialchars($mv['placa']) ?></span>
                    <span class="garantia-moto-fin">Vence el <?= $fecha($mg['fecha_fin']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
<p class="garantia-subtitulo">Busca la moto con uno de sus datos y mira si la garantía sigue vigente.</p>

<form class="tarjeta garantia-buscador" method="get" action="index.php">
    <input type="hidden" name="r" value="garantia/index">
    <div class="campo">
        <label for="criterio">Buscar por</label>
        <select id="criterio" name="criterio">
            <?php foreach ($criterios as $clave => $texto): ?>
                <option value="<?= $clave ?>" <?= $clave === $criterio ? 'selected' : '' ?>><?= $texto ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="campo garantia-valor">
        <label for="valor">Dato</label>
        <input id="valor" name="valor" value="<?= htmlspecialchars($valor) ?>" placeholder="Ej.: ABC12D" required autofocus>
    </div>
    <button type="submit" class="btn">Buscar</button>
</form>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($resultado): ?>
    <?php $v = $resultado['vehiculo']; $g = $resultado['garantia']; ?>
    <div class="garantia-resultado">
        <section class="tarjeta garantia-estado <?= $g['vigente'] ? 'es-vigente' : 'es-vencida' ?>">
            <span class="garantia-sello"><?= $g['vigente'] ? 'Vigente' : 'Vencida' ?></span>
            <dl>
                <dt>Vence el</dt>
                <dd class="garantia-fecha"><?= $fecha($g['fecha_fin']) ?></dd>
                <?php if ($g['vigente']): ?>
                    <dt>Quedan</dt>
                    <dd><?= $g['dias_restantes'] ?> días</dd>
                <?php endif; ?>
                <dt>Mantenimientos de garantía</dt>
                <dd>
                    <?= $g['mantenimientos_usados'] ?> <?= $g['mantenimientos_usados'] === 1 ? 'usado' : 'usados' ?> ·
                    <?= $g['vigente'] ? $g['mantenimientos_restantes'] . ' gratis restantes' : 'no aplica' ?>
                </dd>
            </dl>
            <?php if ($g['vigente'] && $g['mantenimientos_restantes'] === 0): ?>
                <p class="garantia-nota">Ya usó los 3 mantenimientos gratis: desde ahora se cobra la mano de obra.</p>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <h2><?= htmlspecialchars($v['marca'] . ' ' . $v['modelo']) ?></h2>
            <dl class="garantia-datos">
                <dt>Placa</dt><dd><?= htmlspecialchars($v['placa'] ?? 'Sin placa') ?></dd>
                <dt>Chasis</dt><dd><?= htmlspecialchars($v['chasis']) ?></dd>
                <dt>Propulsión</dt><dd><?= $v['propulsion'] === 'electrica' ? 'Eléctrica' : 'Combustión' ?></dd>
                <dt>Cliente</dt><dd><?= htmlspecialchars($v['cliente']) ?> · <?= htmlspecialchars($v['documento']) ?></dd>
                <dt>Factura</dt><dd><?= htmlspecialchars($v['numero_factura']) ?> · <?= $v['canal'] === 'web' ? 'Compra en línea' : 'Compra en tienda' ?></dd>
                <dt>Fecha de compra</dt><dd><?= $fecha($v['fecha_compra']) ?> · <?= (int) $v['meses_garantia'] ?> meses de garantía</dd>
            </dl>
            <h3>Qué cubre</h3>
            <p class="garantia-cobertura"><?= htmlspecialchars($v['cobertura']) ?></p>
        </section>
    </div>

    <section class="garantia-historial">
        <h2>Historial de mantenimientos</h2>
        <?php if (!$resultado['mantenimientos']): ?>
            <p class="garantia-subtitulo">Esta moto todavía no tiene mantenimientos.</p>
        <?php else: ?>
            <div class="garantia-tabla">
                <table class="tabla">
                    <thead>
                    <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Repuestos</th><th>Mano de obra</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($resultado['mantenimientos'] as $m): ?>
                        <tr>
                            <td><?= $fecha($m['fecha']) ?></td>
                            <td><span class="garantia-tipo tipo-<?= $m['tipo'] === 'garantia' ? 'garantia' : 'pagado' ?>"><?= $m['tipo'] === 'garantia' ? 'Garantía' : 'Pagado' ?></span></td>
                            <td><?= htmlspecialchars($m['descripcion']) ?></td>
                            <td><?= $pesos($m['costo_repuestos']) ?></td>
                            <td><?= (float) $m['costo_mano_obra'] > 0 ? $pesos($m['costo_mano_obra']) : 'Gratis' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
