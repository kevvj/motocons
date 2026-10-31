<?php
$fecha = static fn(string $valor): string => date('d/m/Y', strtotime($valor));
$pesos = static fn($valor): string => '$' . number_format((float) $valor, 0, ',', '.');
$claseEstado = static fn(string $e): string => 'estado-' . strtolower(str_replace('_', '-', $e));
?>
<?php if (!$venta): ?>
    <header class="vp-cabecera">
        <div>
            <p class="vp-antetitulo">Punto de venta</p>
            <h1>Venta no encontrada</h1>
        </div>
        <a class="btn btn-secundario" href="index.php?r=ventas_presenciales/index">Volver al listado</a>
    </header>
    <div class="alerta alerta-error">No encontramos esa venta en tienda. Revisa el enlace o búscala en el listado.</div>
<?php else: ?>
    <?php
    $estado = $venta['estado'];
    // Paso del recorrido en que va la venta: registrada, pagada, entregada, cerrada.
    $paso = ['PENDIENTE' => 0, 'EN_CREDITO' => 0, 'PAGADA' => 1, 'ENTREGADA' => 2, 'CERRADA' => 3][$estado] ?? -1;
    $recorrido = [$estado === 'EN_CREDITO' ? 'En crédito' : 'Registrada', 'Pagada', 'Entregada', 'Cerrada'];
    $notas = [
        'PENDIENTE' => 'Al confirmar el pago se asigna la factura y las motos pasan a garantía y taller.',
        'EN_CREDITO' => 'Esperando el dictamen de financiación. Si aprueban con cuota inicial, cóbrala y confirma el pago.',
        'PAGADA' => 'Registra la entrega cuando el cliente se lleve la moto.',
    ];
    ?>
    <header class="vp-cabecera">
        <div>
            <p class="vp-antetitulo">Venta en tienda · N.º <?= (int) $venta['id'] ?></p>
            <h1><?= $venta['numero_factura'] !== null ? 'Factura ' . htmlspecialchars($venta['numero_factura']) : 'Venta sin factura' ?></h1>
            <p class="vp-subtitulo"><?= $fecha($venta['fecha']) ?> · Vendedor: <?= htmlspecialchars($venta['vendedor'] ?? 'sin registro') ?></p>
        </div>
        <div class="vp-botones">
            <button type="button" class="btn btn-secundario" data-imprimir>Imprimir</button>
            <a class="btn btn-secundario" href="index.php?r=ventas_presenciales/index">Volver al listado</a>
        </div>
    </header>

    <?php if ($mensaje): ?>
        <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($estado === 'ANULADA'): ?>
        <div class="alerta alerta-aviso">Esta venta fue anulada. Sus motos ya no aparecen en garantía ni en el taller.</div>
    <?php else: ?>
        <ol class="vp-recorrido" aria-label="Avance de la venta">
            <?php foreach ($recorrido as $i => $texto): ?>
                <li class="<?= $i < $paso ? 'es-hecho' : ($i === $paso ? 'es-actual' : '') ?>"><?= htmlspecialchars($texto) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <div class="vp-detalle">
        <section class="tarjeta">
            <h2>Cliente</h2>
            <dl class="vp-datos">
                <dt>Nombre</dt><dd><?= htmlspecialchars($venta['cliente']) ?></dd>
                <dt>Documento</dt><dd><?= htmlspecialchars($venta['documento']) ?></dd>
                <dt>Correo</dt><dd><?= htmlspecialchars($venta['correo'] ?? '') ?: '<span class="vp-suave">Sin correo</span>' ?></dd>
                <dt>Teléfono</dt><dd><?= htmlspecialchars($venta['telefono'] ?? '') ?: '<span class="vp-suave">Sin teléfono</span>' ?></dd>
            </dl>

            <h2>Motos</h2>
            <div class="vp-tabla">
                <table class="tabla">
                    <thead>
                    <tr><th>Modelo</th><th>Chasis</th><th>Placa</th><th>Garantía</th><th class="vp-numero-col">Precio</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($venta['detalle'] as $d): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($d['marca'] . ' ' . $d['modelo']) ?></strong>
                                <small><?= $d['propulsion'] === 'electrica' ? 'Eléctrica' : 'Combustión' ?></small>
                            </td>
                            <td class="vp-codigo"><?= htmlspecialchars($d['chasis']) ?></td>
                            <td><?= $d['placa'] !== null ? htmlspecialchars($d['placa']) : '<span class="vp-suave">Sin placa</span>' ?></td>
                            <td>
                                <?= (int) $d['meses_garantia'] ?> meses
                                <?php if ($d['vehiculo_id'] !== null): ?>
                                    <a class="vp-enlace" href="index.php?r=garantia/index&amp;criterio=chasis&amp;valor=<?= urlencode($d['chasis']) ?>">Ver garantía</a>
                                <?php endif; ?>
                            </td>
                            <td class="vp-numero-col"><?= $pesos($d['precio']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                    <tr class="vp-fila-total"><td colspan="4">Total</td><td class="vp-numero-col"><?= $pesos($venta['total']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <aside class="tarjeta vp-estado-tarjeta <?= $claseEstado($estado) ?>">
            <p class="vp-antetitulo">Estado</p>
            <span class="vp-estado vp-estado-grande <?= $claseEstado($estado) ?>"><?= htmlspecialchars($estados[$estado]) ?></span>
            <?php if (isset($notas[$estado])): ?>
                <p class="vp-nota"><?= htmlspecialchars($notas[$estado]) ?></p>
            <?php endif; ?>

            <?php if ($acciones): ?>
                <div class="vp-acciones">
                    <?php foreach ($acciones as $destino => $texto): ?>
                        <form method="post" action="index.php?r=ventas_presenciales/cambiarEstado"
                            <?= $destino === 'ANULADA' ? 'data-confirmar="¿Seguro que quieres anular esta venta? No se puede deshacer."' : '' ?>>
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <input type="hidden" name="id" value="<?= (int) $venta['id'] ?>">
                            <input type="hidden" name="estado" value="<?= htmlspecialchars($destino) ?>">
                            <button type="submit" class="btn btn-bloque <?= $destino === 'ANULADA' ? 'btn-peligro' : ($destino === array_key_first($acciones) ? '' : 'btn-secundario') ?>">
                                <?= htmlspecialchars($texto) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="vp-nota">Esta venta ya no tiene más cambios de estado.</p>
            <?php endif; ?>
        </aside>
    </div>
<?php endif; ?>
