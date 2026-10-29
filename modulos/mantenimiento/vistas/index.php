<?php
$fecha      = static fn(string $f): string => date('d/m/Y', strtotime($f));
$pesos      = static fn($v): string => '$' . number_format((float) $v, 0, ',', '.');
$estado     = static fn(array $m): string => $m['aplica_garantia'] ? 'es-garantia' : 'es-pagado';
$hoy        = date('Y-m-d');
?>
<h1>Taller y mantenimientos</h1>
<p class="mantenimiento-subtitulo">
    Cada moto nueva tiene <strong><?= (int) $tope_garantia ?> servicios de garantía</strong> con mano de obra gratis.
    Al terminarse la garantía o los <?= (int) $tope_garantia ?> servicios, la mano de obra se cobra.
</p>

<?php if (!empty($mensaje)): ?>
    <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="tarjeta mantenimiento-buscador" method="get" action="index.php">
    <input type="hidden" name="r" value="mantenimiento/index">
    <div class="campo mantenimiento-valor">
        <label for="q">Buscar moto</label>
        <input id="q" name="q" value="<?= htmlspecialchars($busqueda) ?>"
               placeholder="Placa, factura, documento o nombre del cliente">
    </div>
    <button type="submit" class="btn">Buscar</button>
    <?php if ($busqueda !== ''): ?>
        <a class="btn btn-secundario" href="index.php?r=mantenimiento/index">Limpiar</a>
    <?php endif; ?>
</form>

<?php if (!$seleccionada): ?>
    <?php if (!$motos): ?>
        <div class="alerta alerta-aviso">No hay motos vendidas que coincidan con la búsqueda.</div>
    <?php else: ?>
        <div class="rejilla mantenimiento-motos">
            <?php foreach ($motos as $moto): ?>
                <a class="tarjeta mantenimiento-moto <?= $estado($moto) ?>"
                   href="index.php?r=mantenimiento/index&amp;ver_moto=<?= (int) $moto['id_moto'] ?>&amp;q=<?= urlencode($busqueda) ?>">
                    <span class="mantenimiento-sello">
                        <?= $moto['aplica_garantia'] ? 'Garantía disponible' : 'Fuera de garantía' ?>
                    </span>
                    <strong><?= htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']) ?></strong>
                    <span>Placa <?= htmlspecialchars($moto['placa'] ?? 'sin placa') ?></span>
                    <span>Cliente: <?= htmlspecialchars($moto['cliente_nombre']) ?></span>
                    <span class="mantenimiento-moto-fin">
                        <?= (int) $moto['garantias_usadas'] ?> de <?= (int) $tope_garantia ?> garantías usadas ·
                        <?php if ($moto['vencida_por_tiempo']): ?>
                            venció el <?= $fecha($moto['fecha_fin_garantia']) ?>
                        <?php else: ?>
                            <?= (int) $moto['garantias_restantes'] ?> disponibles
                        <?php endif; ?>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if ($seleccionada): ?>
    <section class="mantenimiento-panel">
        <div class="tarjeta">
            <div class="mantenimiento-cabecera">
                <div>
                    <h2>Registrar servicio</h2>
                    <p class="mantenimiento-subtitulo">
                        <?= htmlspecialchars($seleccionada['marca'] . ' ' . $seleccionada['modelo']) ?>
                        · Placa <?= htmlspecialchars($seleccionada['placa'] ?? 'sin placa') ?>
                    </p>
                </div>
                <a class="btn btn-secundario btn-chico"
                   href="index.php?r=mantenimiento/index&amp;q=<?= urlencode($busqueda) ?>">Cambiar moto</a>
            </div>

            <form method="post" action="index.php?r=mantenimiento/registrar">
                <input type="hidden" name="moto_id" value="<?= (int) $seleccionada['id_moto'] ?>">

                <div class="campo">
                    <label for="fecha">Fecha del servicio</label>
                    <input id="fecha" name="fecha" type="date" value="<?= $hoy ?>" max="<?= $hoy ?>" required>
                </div>

                <div class="campo">
                    <label for="descripcion">Descripción del servicio</label>
                    <input id="descripcion" name="descripcion" type="text" maxlength="255"
                           placeholder="Ej.: cambio de aceite y ajuste de cadena" required>
                </div>

                <div class="campo">
                    <label for="costo_repuestos">Costo de repuestos e insumos</label>
                    <input id="costo_repuestos" name="costo_repuestos" type="number"
                           min="0" step="0.01" value="0" required>
                    <p class="mantenimiento-ayuda">La mano de obra se agrega automáticamente según la garantía.</p>
                </div>

                <?php if ($seleccionada['aplica_garantia']): ?>
                    <div class="alerta alerta-exito mantenimiento-regla">
                        Este servicio se registrará como <strong>garantía</strong>: la mano de obra no se cobra.
                    </div>
                <?php else: ?>
                    <div class="alerta alerta-aviso mantenimiento-regla">
                        Este servicio se registrará como <strong>pagado</strong>: incluye
                        <?= $pesos($mano_obra) ?> de mano de obra.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn">Registrar servicio</button>
            </form>
        </div>

        <aside class="tarjeta mantenimiento-resumen <?= $estado($seleccionada) ?>">
            <span class="mantenimiento-sello">
                <?= $seleccionada['aplica_garantia'] ? 'Garantía disponible' : 'Fuera de garantía' ?>
            </span>
            <h2>Estado de la garantía</h2>

            <dl class="mantenimiento-datos">
                <dt>Cliente</dt>
                <dd><?= htmlspecialchars($seleccionada['cliente_nombre']) ?></dd>
                <dt>Documento</dt>
                <dd><?= htmlspecialchars($seleccionada['cliente_documento']) ?></dd>
                <dt>Factura</dt>
                <dd><?= htmlspecialchars($seleccionada['numero_factura']) ?></dd>
                <dt>Compra</dt>
                <dd><?= $fecha($seleccionada['fecha_compra']) ?></dd>
                <dt>Garantía hasta</dt>
                <dd><?= $fecha($seleccionada['fecha_fin_garantia']) ?></dd>
            </dl>

            <p class="mantenimiento-contador">
                <strong><?= (int) $seleccionada['garantias_usadas'] ?></strong> de <?= (int) $tope_garantia ?> servicios de garantía usados
            </p>

            <p class="mantenimiento-nota">
                <?php if ($seleccionada['vencida_por_tiempo']): ?>
                    La garantía venció el <?= $fecha($seleccionada['fecha_fin_garantia']) ?>.
                <?php elseif ($seleccionada['tope_alcanzado']): ?>
                    Ya se usaron los <?= (int) $tope_garantia ?> servicios de garantía.
                <?php elseif ((int) $seleccionada['garantias_restantes'] === 1): ?>
                    Queda 1 servicio de garantía disponible.
                <?php else: ?>
                    Quedan <?= (int) $seleccionada['garantias_restantes'] ?> servicios de garantía disponibles.
                <?php endif; ?>
            </p>
        </aside>
    </section>

    <?php
    // El historial completo vive en el módulo garantia; aquí solo se enlaza.
    $criterioHistorial = !empty($seleccionada['placa']) ? 'placa' : 'factura';
    $valorHistorial    = $criterioHistorial === 'placa'
        ? $seleccionada['placa']
        : $seleccionada['numero_factura'];
    ?>
    <section class="tarjeta mantenimiento-historial">
        <div class="mantenimiento-cabecera">
            <div>
                <h2>Historial de mantenimientos</h2>
                <p class="mantenimiento-subtitulo">
                    El historial completo y el estado de la garantía se consultan en el módulo de Garantía.
                </p>
            </div>
            <a class="btn btn-secundario btn-chico"
               href="index.php?r=garantia/index&amp;criterio=<?= $criterioHistorial ?>&amp;valor=<?= urlencode($valorHistorial) ?>">
                Ver en Garantía
            </a>
        </div>
    </section>
<?php endif; ?>
