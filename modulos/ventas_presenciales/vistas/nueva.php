<?php
$pesos = static fn($valor): string => '$' . number_format((float) $valor, 0, ',', '.');
$precios = array_column($catalogo, 'precio', 'id');
$total = 0;
foreach ($anterior['unidades'] as $u) {
    $total += (float) ($precios[$u['moto_id']] ?? 0);
}

// Una fila de moto. Se usa para las filas del formulario y para la plantilla que agrega filas.
$fila = static function (array $u) use ($catalogo, $precios, $pesos): void { ?>
    <tr class="vp-unidad">
        <td>
            <select name="unidad_moto[]" class="vp-input" aria-label="Modelo de la moto" data-modelo>
                <option value="">Selecciona un modelo...</option>
                <?php foreach ($catalogo as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" data-precio="<?= (float) $m['precio'] ?>" <?= (int) $m['id'] === $u['moto_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['marca'] . ' ' . $m['modelo']) ?> · <?= $m['propulsion'] === 'electrica' ? 'Eléctrica' : 'Combustión' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input name="unidad_chasis[]" class="vp-input vp-mayusculas" maxlength="30" value="<?= htmlspecialchars($u['chasis']) ?>" placeholder="Ej.: 9FKRG2110N0001234" aria-label="Número de chasis"></td>
        <td><input name="unidad_placa[]" class="vp-input vp-mayusculas" maxlength="10" value="<?= htmlspecialchars($u['placa']) ?>" placeholder="Sin placa" aria-label="Placa (opcional)"></td>
        <td class="vp-numero-col" data-precio-fila><?= isset($precios[$u['moto_id']]) ? $pesos($precios[$u['moto_id']]) : '—' ?></td>
        <td class="vp-quitar-col"><button type="button" class="btn btn-secundario btn-chico" data-quitar>Quitar</button></td>
    </tr>
<?php };
?>
<header class="vp-cabecera">
    <div>
        <p class="vp-antetitulo">Punto de venta</p>
        <h1>Nueva venta</h1>
        <p class="vp-subtitulo">La venta queda sin factura hasta que se confirme el pago.</p>
    </div>
    <a class="btn btn-secundario" href="index.php?r=ventas_presenciales/index">Volver al listado</a>
</header>

<?php if ($error): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="vp-venta" method="post" action="index.php?r=ventas_presenciales/registrar" id="form-venta" data-max-unidades="<?= (int) $maxUnidades ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

    <div class="vp-pasos">
        <section class="vp-seccion">
            <div class="vp-seccion-titulo"><span class="vp-paso">01</span><h2>Cliente</h2></div>
            <div class="vp-documento">
                <div class="campo">
                    <label for="documento">Documento</label>
                    <input id="documento" name="documento" maxlength="20" value="<?= htmlspecialchars($anterior['documento']) ?>" placeholder="Ej.: 1067123456" required autofocus>
                </div>
                <button type="button" class="btn btn-secundario" id="buscar-cliente" hidden>Buscar cliente</button>
            </div>
            <p class="vp-nota" id="cliente-estado" aria-live="polite">Si el documento ya está registrado se usa ese cliente; no se crea otro.</p>
            <div class="vp-campos-cliente">
                <div class="campo vp-campo-ancho">
                    <label for="nombre">Nombre completo</label>
                    <input id="nombre" name="nombre" maxlength="120" value="<?= htmlspecialchars($anterior['nombre']) ?>" data-cliente>
                </div>
                <div class="campo">
                    <label for="correo">Correo (opcional)</label>
                    <input id="correo" name="correo" type="email" maxlength="150" value="<?= htmlspecialchars($anterior['correo']) ?>" data-cliente>
                </div>
                <div class="campo">
                    <label for="telefono">Teléfono (opcional)</label>
                    <input id="telefono" name="telefono" type="tel" maxlength="20" value="<?= htmlspecialchars($anterior['telefono']) ?>" data-cliente>
                </div>
            </div>
        </section>

        <section class="vp-seccion">
            <div class="vp-seccion-titulo"><span class="vp-paso">02</span><h2>Motos</h2></div>
            <div class="vp-tabla">
                <table class="tabla vp-unidades">
                    <thead>
                    <tr><th>Modelo</th><th>Chasis</th><th>Placa</th><th class="vp-numero-col">Precio</th><th></th></tr>
                    </thead>
                    <tbody id="venta-unidades">
                    <?php foreach ($anterior['unidades'] as $u) { $fila($u); } ?>
                    </tbody>
                </table>
            </div>
            <template id="plantilla-unidad"><?php $fila(['moto_id' => 0, 'chasis' => '', 'placa' => '']); ?></template>
            <div class="vp-pie-seccion">
                <button type="button" class="btn btn-secundario btn-chico" id="agregar-unidad" hidden>Agregar otra moto</button>
                <p class="vp-nota">La placa puede quedar vacía si la moto todavía no está matriculada.</p>
            </div>
        </section>

        <section class="vp-seccion">
            <div class="vp-seccion-titulo"><span class="vp-paso">03</span><h2>Forma de pago</h2></div>
            <div class="vp-opciones">
                <label class="vp-opcion">
                    <input type="radio" name="forma_pago" value="contado" <?= $anterior['forma_pago'] !== 'credito' ? 'checked' : '' ?>>
                    <span>
                        <strong>Contado</strong>
                        Efectivo, transferencia o tarjeta. Queda pendiente hasta que confirmes el pago.
                    </span>
                </label>
                <label class="vp-opcion">
                    <input type="radio" name="forma_pago" value="credito" <?= $anterior['forma_pago'] === 'credito' ? 'checked' : '' ?>>
                    <span>
                        <strong>Financiada</strong>
                        Crédito con entidad o Brilla. Queda en crédito hasta que financiación lo apruebe.
                    </span>
                </label>
            </div>
        </section>
    </div>

    <aside class="vp-resumen">
        <p class="vp-antetitulo">Resumen</p>
        <dl>
            <dt>Motos</dt>
            <dd id="resumen-unidades"><?= count(array_filter($anterior['unidades'], static fn($u) => isset($precios[$u['moto_id']]))) ?></dd>
            <dt>Total</dt>
            <dd class="vp-total" id="resumen-total"><?= $pesos($total) ?></dd>
        </dl>
        <p class="vp-nota">El precio sale del catálogo. La factura se asigna al confirmar el pago.</p>
        <button type="submit" class="btn btn-bloque">Registrar venta</button>
    </aside>
</form>
