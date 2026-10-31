<?php
$etiquetasEstado = [
    'VIGENTE'    => 'Vigente',
    'PROGRAMADA' => 'Programada',
    'VENCIDA'    => 'Vencida',
    'INACTIVA'   => 'Inactiva',
];
$pestanas = [
    ''           => 'Todas',
    'VIGENTE'    => 'Vigentes',
    'PROGRAMADA' => 'Programadas',
    'VENCIDA'    => 'Vencidas',
    'INACTIVA'   => 'Inactivas',
];
$fecha = fn(string $f) => date('d/m/Y', strtotime($f));
$pesos = fn($v) => '$' . number_format((float) $v, 0, ',', '.');

$textoAlcance = function (array $promocion): string {
    if ($promocion['aplica'] === []) {
        return 'Todas';
    }
    $partes = [];
    foreach ($promocion['aplica'] as $a) {
        $partes[] = ucfirst($a['criterio']) . ': ' . $a['valor'];
    }
    return implode(', ', $partes);
};
?>
<div class="promociones-cabecera">
    <div>
        <h1>Promociones</h1>
        <p class="promociones-subtitulo">Administra descuentos, vigencias y el alcance de cada promoción.</p>
    </div>
    <div class="promociones-acciones-cabecera">
        <a class="btn btn-secundario" href="index.php?r=promociones/catalogo">Ver catálogo</a>
        <a class="btn" href="index.php?r=promociones/crear">Nueva promoción</a>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alerta <?= $mensaje['tipo'] === 'exito' ? 'alerta-exito' : 'alerta-error' ?>">
        <?= htmlspecialchars($mensaje['texto']) ?>
    </div>
<?php endif; ?>

<div class="rejilla promociones-stats">
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Vigentes</span>
        <strong class="promociones-stat-valor"><?= (int) $estadisticas['VIGENTE'] ?></strong>
    </div>
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Programadas</span>
        <strong class="promociones-stat-valor"><?= (int) $estadisticas['PROGRAMADA'] ?></strong>
    </div>
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Vencidas</span>
        <strong class="promociones-stat-valor"><?= (int) $estadisticas['VENCIDA'] ?></strong>
    </div>
    <div class="tarjeta">
        <span class="promociones-stat-etiqueta">Inactivas</span>
        <strong class="promociones-stat-valor"><?= (int) $estadisticas['INACTIVA'] ?></strong>
    </div>
</div>

<div class="tarjeta">
    <div class="promociones-filtros">
        <div class="promociones-pestanas">
            <?php foreach ($pestanas as $clave => $texto): ?>
                <?php
                $params = ['r' => 'promociones/index'];
                if ($clave !== '') {
                    $params['estado'] = $clave;
                }
                if ($busqueda !== '') {
                    $params['q'] = $busqueda;
                }
                ?>
                <a class="promociones-pestana <?= $estado === $clave ? 'es-activa' : '' ?>"
                   href="index.php?<?= htmlspecialchars(http_build_query($params)) ?>">
                    <?= htmlspecialchars($texto) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <form class="promociones-buscador" method="get" action="index.php">
            <input type="hidden" name="r" value="promociones/index">
            <?php if ($estado !== ''): ?>
                <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
            <?php endif; ?>
            <div class="campo">
                <label for="q">Buscar</label>
                <input type="search" id="q" name="q" value="<?= htmlspecialchars($busqueda) ?>"
                       placeholder="Nombre o descripción">
            </div>
            <button type="submit" class="btn btn-secundario">Buscar</button>
        </form>
    </div>

    <?php if (!$promociones): ?>
        <div class="alerta alerta-aviso">No hay promociones para mostrar.</div>
    <?php else: ?>
        <div class="tabla-scroll">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Promoción</th>
                        <th>Descuento</th>
                        <th>Alcance</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($promociones as $promocion): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($promocion['nombre']) ?></strong>
                            <div class="promociones-secundario">
                                <?= $promocion['descripcion'] !== ''
                                    ? htmlspecialchars($promocion['descripcion'])
                                    : 'Sin descripción' ?>
                            </div>
                        </td>
                        <td>
                            <?= $promocion['tipo_descuento'] === 'porcentaje'
                                ? htmlspecialchars(rtrim(rtrim(number_format($promocion['valor'], 2, ',', '.'), '0'), ',')) . '%'
                                : htmlspecialchars($pesos($promocion['valor'])) ?>
                            <div class="promociones-secundario">
                                <?= $promocion['tipo_descuento'] === 'porcentaje' ? 'Porcentaje' : 'Monto fijo' ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($textoAlcance($promocion)) ?></td>
                        <td>
                            <?= htmlspecialchars($fecha($promocion['fecha_inicio'])) ?>
                            —
                            <?= htmlspecialchars($fecha($promocion['fecha_fin'])) ?>
                        </td>
                        <td>
                            <span class="promociones-insignia promociones-<?= strtolower($promocion['estado_vigencia']) ?>">
                                <?= htmlspecialchars($etiquetasEstado[$promocion['estado_vigencia']] ?? $promocion['estado_vigencia']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="promociones-acciones-fila">
                                <a class="btn btn-chico btn-secundario"
                                   href="index.php?r=promociones/editar&amp;id=<?= (int) $promocion['id'] ?>">Editar</a>
                                <form method="post" action="index.php?r=promociones/estado">
                                    <input type="hidden" name="id" value="<?= (int) $promocion['id'] ?>">
                                    <input type="hidden" name="estado"
                                           value="<?= $promocion['estado'] === 'activa' ? 'inactiva' : 'activa' ?>">
                                    <button type="submit" class="btn btn-chico btn-secundario">
                                        <?= $promocion['estado'] === 'activa' ? 'Desactivar' : 'Activar' ?>
                                    </button>
                                </form>
                                <form method="post" action="index.php?r=promociones/eliminar"
                                      data-confirmar="¿Eliminar la promoción «<?= htmlspecialchars($promocion['nombre']) ?>»?">
                                    <input type="hidden" name="id" value="<?= (int) $promocion['id'] ?>">
                                    <button type="submit" class="btn btn-chico btn-peligro">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
