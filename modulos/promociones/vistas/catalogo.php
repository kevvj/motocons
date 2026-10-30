<?php
$pesos = fn($v) => '$' . number_format((float) $v, 0, ',', '.');
$etiquetasPropulsion = [
    'combustion' => 'Combustión',
    'electrica'  => 'Eléctrica',
];
$conDescuento = 0;
$ahorroTotal = 0.0;
foreach ($catalogo as $moto) {
    if ($moto['promocion'] !== null) {
        $conDescuento++;
        $ahorroTotal += $moto['descuento'];
    }
}
?>
<div class="promociones-cabecera">
    <div>
        <h1>Catálogo con descuento</h1>
        <p class="promociones-subtitulo">
            Precio original y precio final con la mejor promoción vigente.
        </p>
    </div>
    <div class="promociones-acciones-cabecera">
        <?php if (!empty($puedeAdmin)): ?>
            <a class="btn btn-secundario" href="index.php?r=promociones/index">Administrar</a>
        <?php endif; ?>
    </div>
</div>

<div class="rejilla promociones-stats">
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Promociones vigentes</span>
        <strong class="promociones-stat-valor"><?= count($promociones) ?></strong>
    </div>
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Motos con descuento</span>
        <strong class="promociones-stat-valor"><?= $conDescuento ?> / <?= count($catalogo) ?></strong>
    </div>
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Ahorro en catálogo</span>
        <strong class="promociones-stat-valor"><?= htmlspecialchars($pesos($ahorroTotal)) ?></strong>
    </div>
</div>

<div class="tarjeta">
    <form class="promociones-filtros-catalogo" method="get" action="index.php">
        <input type="hidden" name="r" value="promociones/catalogo">
        <div class="campo">
            <label for="marca">Marca</label>
            <select id="marca" name="marca">
                <option value="">Todas</option>
                <?php foreach ($marcas as $marca): ?>
                    <option value="<?= htmlspecialchars($marca) ?>"
                        <?= ($filtros['marca'] ?? '') === $marca ? 'selected' : '' ?>>
                        <?= htmlspecialchars($marca) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="gama">Gama</label>
            <select id="gama" name="gama">
                <option value="">Todas</option>
                <?php foreach ($gamas as $gama): ?>
                    <option value="<?= htmlspecialchars($gama) ?>"
                        <?= ($filtros['gama'] ?? '') === $gama ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($gama)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="propulsion">Propulsión</label>
            <select id="propulsion" name="propulsion">
                <option value="">Todas</option>
                <?php foreach ($propulsiones as $propulsion): ?>
                    <option value="<?= htmlspecialchars($propulsion) ?>"
                        <?= ($filtros['propulsion'] ?? '') === $propulsion ? 'selected' : '' ?>>
                        <?= htmlspecialchars($etiquetasPropulsion[$propulsion] ?? $propulsion) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="q">Buscar</label>
            <input type="search" id="q" name="q"
                   value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>"
                   placeholder="Marca o modelo">
        </div>
        <button type="submit" class="btn btn-secundario">Filtrar</button>
    </form>

    <?php if (!$catalogo): ?>
        <div class="alerta alerta-aviso">No hay motos que coincidan con el filtro.</div>
    <?php else: ?>
        <div class="rejilla promociones-catalogo">
            <?php foreach ($catalogo as $moto): ?>
                <article class="tarjeta promociones-moto <?= $moto['promocion'] ? 'tiene-descuento' : '' ?>">
                    <strong><?= htmlspecialchars($moto['marca'] . ' ' . $moto['modelo']) ?></strong>
                    <div class="promociones-secundario">
                        Gama <?= htmlspecialchars(ucfirst($moto['gama'])) ?>
                        · <?= htmlspecialchars($etiquetasPropulsion[$moto['propulsion']] ?? $moto['propulsion']) ?>
                    </div>
                    <?php if ($moto['promocion']): ?>
                        <div class="promociones-precios">
                            <span class="promociones-precio-antes"><?= htmlspecialchars($pesos($moto['precio_original'])) ?></span>
                            <span class="promociones-precio-final"><?= htmlspecialchars($pesos($moto['precio_final'])) ?></span>
                        </div>
                        <span class="promociones-insignia promociones-vigente">
                            <?= htmlspecialchars($moto['promocion']['nombre']) ?>
                            (−<?= htmlspecialchars($pesos($moto['descuento'])) ?>)
                        </span>
                    <?php else: ?>
                        <div class="promociones-precios">
                            <span class="promociones-precio-final"><?= htmlspecialchars($pesos($moto['precio_original'])) ?></span>
                        </div>
                        <span class="promociones-secundario">Sin promoción vigente</span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
