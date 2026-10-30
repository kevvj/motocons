<?php
$fecha = static fn(string $valor): string => date('d/m/Y', strtotime($valor));
$pesos = static fn($valor): string => '$' . number_format((float) $valor, 0, ',', '.');
$hoy = date('Y-m-d');
?>
<h1>Taller y mantenimientos</h1>
<p class="mantenimiento-subtitulo">
    Registra servicios, controla los tres mantenimientos de garantía y consulta las facturas de cada vehículo.
</p>

<?php if (!empty($mensaje)): ?>
    <div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<section class="tarjeta mantenimiento-registro">
    <div class="mantenimiento-cabecera">
        <div>
            <h2>Registrar mantenimiento</h2>
            <p class="mantenimiento-subtitulo">Los servicios 1 a 3 tienen mano de obra gratis; los repuestos e insumos siempre se cobran.</p>
        </div>
    </div>

    <form method="post" action="index.php?r=mantenimiento/registrar">
        <div class="mantenimiento-form-grid">
            <div class="campo">
                <label for="moto_id">Moto asignada</label>
                <select id="moto_id" name="moto_id" required>
                    <option value="">Selecciona una moto...</option>
                    <?php foreach ($motos as $moto): ?>
                        <option value="<?= (int) $moto['id_moto'] ?>"
                            <?= $seleccionada && (int) $seleccionada['id_moto'] === (int) $moto['id_moto'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($moto['placa'] ?? 'Sin placa') . ' · ' . $moto['marca'] . ' ' . $moto['modelo'] . ' · ' . $moto['cliente_nombre']) ?>
                            · <?= (int) $moto['garantias_usadas'] ?>/<?= (int) $tope_garantia ?> garantías
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="fecha">Fecha del servicio</label>
                <input id="fecha" name="fecha" type="date" value="<?= $hoy ?>" max="<?= $hoy ?>" required>
            </div>
        </div>

        <div class="mantenimiento-lineas">
            <div class="mantenimiento-seccion-titulo">
                <strong>Ítems y conceptos del servicio</strong>
                <span>Las filas vacías se ignoran.</span>
            </div>
            <div class="tabla-scroll">
                <table class="tabla mantenimiento-tabla-lineas">
                    <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th class="numero">Cantidad</th>
                        <th class="numero">Valor unitario</th>
                    </tr>
                    </thead>
                    <tbody id="lineas-mantenimiento">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <tr>
                            <td>
                                <select name="linea_tipo[]" class="mantenimiento-input">
                                    <option value="insumo">Insumo</option>
                                    <option value="repuesto">Repuesto</option>
                                </select>
                            </td>
                            <td><input name="linea_desc[]" class="mantenimiento-input" type="text" maxlength="200" placeholder="Descripción del repuesto o insumo"></td>
                            <td><input name="linea_cant[]" class="mantenimiento-input numero" type="number" min="1" value="1"></td>
                            <td><input name="linea_valor[]" class="mantenimiento-input numero" type="number" min="0" step="0.01" placeholder="0"></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-secundario btn-chico" id="agregar-linea">+ Agregar línea</button>
        </div>

        <div class="alerta alerta-aviso mantenimiento-regla">
            <strong>Condición de garantía:</strong> mano de obra gratis en los servicios 1 a 3; desde el cuarto servicio se cobran <?= $pesos(30000) ?>.
        </div>
        <button type="submit" class="btn">Registrar mantenimiento</button>
    </form>
</section>

<section class="tarjeta">
    <div class="mantenimiento-cabecera">
        <div>
            <h2>Motos y mantenimientos</h2>
            <p class="mantenimiento-subtitulo">Consulta servicios, garantías utilizadas y facturas.</p>
        </div>
    </div>

    <form class="mantenimiento-buscador" method="get" action="index.php">
        <input type="hidden" name="r" value="mantenimiento/index">
        <div class="campo mantenimiento-valor">
            <label for="q">Buscar por placa, chasis, factura o cliente</label>
            <input id="q" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Ej.: ABC12D">
        </div>
        <button type="submit" class="btn">Filtrar</button>
        <?php if ($busqueda !== ''): ?>
            <a class="btn btn-secundario" href="index.php?r=mantenimiento/index">Limpiar</a>
        <?php endif; ?>
    </form>

    <div class="tabla-scroll">
        <table class="tabla mantenimiento-tabla">
            <thead>
            <tr>
                <th>Placa</th>
                <th>Referencia</th>
                <th>Cliente</th>
                <th>Garantías usadas</th>
                <th>Restantes</th>
                <th>Estado</th>
                <th class="centrado">Facturas</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$motos): ?>
                <tr><td colspan="7" class="tabla-vacia">No se encontraron vehículos para la búsqueda.</td></tr>
            <?php endif; ?>
            <?php foreach ($motos as $moto): ?>
                <?php
                $idMoto = (int) $moto['id_moto'];
                $usadas = (int) $moto['garantias_usadas'];
                $restantes = (int) $moto['garantias_restantes'];
                $vigente = (bool) $moto['garantia_vigente'];
                ?>
                <tr>
                    <td><span class="mantenimiento-placa"><?= htmlspecialchars($moto['placa'] ?? 'Sin placa') ?></span></td>
                    <td>
                        <strong><?= htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']) ?></strong>
                        <small><?= htmlspecialchars($moto['propulsion']) ?> · <?= htmlspecialchars($moto['chasis']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($moto['cliente_nombre']) ?>
                        <small>CC <?= htmlspecialchars($moto['cliente_documento']) ?></small>
                    </td>
                    <td>
                        <div class="mantenimiento-segmentos" aria-label="<?= $usadas ?> de <?= (int) $tope_garantia ?> garantías usadas">
                            <?php for ($segmento = 1; $segmento <= $tope_garantia; $segmento++): ?>
                                <span class="<?= $segmento <= $usadas ? ($vigente ? 'usado' : 'agotado') : '' ?>"></span>
                            <?php endfor; ?>
                        </div>
                        <small><?= $usadas ?> / <?= (int) $tope_garantia ?></small>
                    </td>
                    <td><?= $restantes > 0 ? $restantes . ' servicio' . ($restantes === 1 ? '' : 's') : '0 servicios' ?></td>
                    <td><span class="mantenimiento-estado <?= $vigente ? 'vigente' : 'agotada' ?>"><?= $vigente ? 'Garantía vigente' : 'Garantía agotada' ?></span></td>
                    <td class="centrado">
                        <button type="button" class="btn btn-chico" onclick="document.getElementById('facturas-<?= $idMoto ?>').showModal()">Ver facturas (<?= count($moto['facturas']) ?>)</button>
                        <dialog id="facturas-<?= $idMoto ?>" class="mantenimiento-dialog" onclick="if (event.target === this) this.close()">
                            <div class="dialog-cabecera">
                                <div>
                                    <h2>Historial de facturas</h2>
                                    <p><?= htmlspecialchars(($moto['placa'] ?? 'Sin placa') . ' · ' . $moto['marca'] . ' ' . $moto['modelo']) ?></p>
                                </div>
                                <button type="button" class="dialog-cerrar" onclick="this.closest('dialog').close()" aria-label="Cerrar">×</button>
                            </div>
                            <div class="dialog-cuerpo">
                                <p><strong>Propietario:</strong> <?= htmlspecialchars($moto['cliente_nombre']) ?> · CC <?= htmlspecialchars($moto['cliente_documento']) ?></p>
                                <?php if (!$moto['facturas']): ?>
                                    <p class="tabla-vacia">Esta motocicleta aún no tiene mantenimientos registrados.</p>
                                <?php else: ?>
                                    <div class="facturas-grid">
                                        <?php foreach ($moto['facturas'] as $factura): ?>
                                            <?php $esGarantia = $factura['tipo'] === 'garantia'; ?>
                                            <article class="factura <?= $esGarantia ? '' : 'factura-pagada' ?>">
                                                <header>
                                                    <div>
                                                        <strong>Servicio #<?= (int) $factura['numero_servicio'] ?> · <?= $fecha($factura['fecha']) ?></strong>
                                                        <small><?= htmlspecialchars($factura['mecanico_nombre'] ?? 'Usuario no disponible') ?></small>
                                                    </div>
                                                    <span class="mantenimiento-estado <?= $esGarantia ? 'vigente' : 'pagada' ?>"><?= $esGarantia ? 'Garantía' : 'Pagado' ?></span>
                                                </header>
                                                <table class="tabla factura-tabla">
                                                    <thead><tr><th>Tipo</th><th>Descripción</th><th class="numero">Cant.</th><th class="numero">Subtotal</th></tr></thead>
                                                    <tbody>
                                                    <?php foreach ($factura['lineas'] as $linea): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars(ucfirst($linea['tipo'])) ?></td>
                                                            <td><?= htmlspecialchars($linea['descripcion']) ?></td>
                                                            <td class="numero"><?= (int) $linea['cantidad'] ?></td>
                                                            <td class="numero"><?= $pesos((int) $linea['cantidad'] * (float) $linea['valor_unitario']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <tr class="factura-mano-obra">
                                                        <td colspan="3"><strong>Mano de obra</strong></td>
                                                        <td class="numero"><?= $esGarantia ? 'Gratis' : $pesos($factura['costo_mano_obra']) ?></td>
                                                    </tr>
                                                    <tr class="factura-total"><td colspan="3">TOTAL</td><td class="numero"><?= $pesos($factura['total']) ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <footer><button type="button" class="btn btn-secundario" onclick="this.closest('dialog').close()">Cerrar</button></footer>
                        </dialog>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.getElementById('agregar-linea').addEventListener('click', function () {
    const fila = document.createElement('tr');
    fila.innerHTML = `
        <td><select name="linea_tipo[]" class="mantenimiento-input"><option value="insumo">Insumo</option><option value="repuesto">Repuesto</option></select></td>
        <td><input name="linea_desc[]" class="mantenimiento-input" type="text" maxlength="200" placeholder="Descripción del repuesto o insumo"></td>
        <td><input name="linea_cant[]" class="mantenimiento-input numero" type="number" min="1" value="1"></td>
        <td><input name="linea_valor[]" class="mantenimiento-input numero" type="number" min="0" step="0.01" placeholder="0"></td>
    `;
    document.getElementById('lineas-mantenimiento').appendChild(fila);
});
<?php if (!empty($_GET['ver_moto'])): ?>
const facturaDialogo = document.getElementById('facturas-<?= (int) $_GET['ver_moto'] ?>');
if (facturaDialogo) facturaDialogo.showModal();
<?php endif; ?>
</script>
