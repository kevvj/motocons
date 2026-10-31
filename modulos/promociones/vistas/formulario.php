<?php
$esEdicion = !empty($esEdicion);
$errores = $errores ?? [];
$aplicaciones = $aplicaciones ?? [];
$criterio = $criterio ?? 'todas';
?>
<div class="promociones-cabecera">
    <div>
        <h1><?= $esEdicion ? 'Editar promoción' : 'Nueva promoción' ?></h1>
        <p class="promociones-subtitulo">
            <?= $esEdicion
                ? 'Modifica los datos, la vigencia o el alcance.'
                : 'Define el descuento, las fechas y a qué motos aplica.' ?>
        </p>
    </div>
    <a class="btn btn-secundario" href="index.php?r=promociones/index">Volver</a>
</div>

<?php if (!empty($errores)): ?>
    <div class="alerta alerta-error">
        <?= htmlspecialchars($errores['general'] ?? 'Revisa los campos marcados.') ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($accion) ?>" class="tarjeta promociones-formulario" novalidate>
    <?php if ($esEdicion): ?>
        <input type="hidden" name="id" value="<?= (int) ($datos['id'] ?? 0) ?>">
    <?php endif; ?>

    <h2>Datos generales</h2>

    <div class="campo">
        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre" maxlength="120" required
               value="<?= htmlspecialchars($datos['nombre'] ?? '') ?>">
        <?php if (isset($errores['nombre'])): ?>
            <span class="promociones-error-campo"><?= htmlspecialchars($errores['nombre']) ?></span>
        <?php endif; ?>
    </div>

    <div class="campo">
        <label for="descripcion">Descripción</label>
        <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"><?= htmlspecialchars($datos['descripcion'] ?? '') ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
            <span class="promociones-error-campo"><?= htmlspecialchars($errores['descripcion']) ?></span>
        <?php endif; ?>
    </div>

    <div class="promociones-form-grid">
        <div class="campo">
            <label>Tipo de descuento</label>
            <div class="promociones-radios">
                <label>
                    <input type="radio" name="tipo_descuento" value="porcentaje"
                        <?= ($datos['tipo_descuento'] ?? 'porcentaje') === 'porcentaje' ? 'checked' : '' ?>>
                    Porcentaje
                </label>
                <label>
                    <input type="radio" name="tipo_descuento" value="monto_fijo"
                        <?= ($datos['tipo_descuento'] ?? '') === 'monto_fijo' ? 'checked' : '' ?>>
                    Monto fijo
                </label>
            </div>
            <?php if (isset($errores['tipo_descuento'])): ?>
                <span class="promociones-error-campo"><?= htmlspecialchars($errores['tipo_descuento']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="valor">Valor</label>
            <div class="promociones-valor">
                <input type="number" id="valor" name="valor" step="0.01" min="0" required
                       value="<?= htmlspecialchars((string) ($datos['valor'] ?? '')) ?>">
                <span id="sufijo-valor">
                    <?= ($datos['tipo_descuento'] ?? 'porcentaje') === 'porcentaje' ? '%' : '$' ?>
                </span>
            </div>
            <?php if (isset($errores['valor'])): ?>
                <span class="promociones-error-campo"><?= htmlspecialchars($errores['valor']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="activa" <?= ($datos['estado'] ?? 'activa') === 'activa' ? 'selected' : '' ?>>Activa</option>
                <option value="inactiva" <?= ($datos['estado'] ?? '') === 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
            </select>
            <?php if (isset($errores['estado'])): ?>
                <span class="promociones-error-campo"><?= htmlspecialchars($errores['estado']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="fecha_inicio">Fecha de inicio</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" required
                   value="<?= htmlspecialchars($datos['fecha_inicio'] ?? '') ?>">
            <?php if (isset($errores['fecha_inicio'])): ?>
                <span class="promociones-error-campo"><?= htmlspecialchars($errores['fecha_inicio']) ?></span>
            <?php endif; ?>
        </div>

        <div class="campo">
            <label for="fecha_fin">Fecha de finalización</label>
            <input type="date" id="fecha_fin" name="fecha_fin" required
                   value="<?= htmlspecialchars($datos['fecha_fin'] ?? '') ?>">
            <?php if (isset($errores['fecha_fin'])): ?>
                <span class="promociones-error-campo"><?= htmlspecialchars($errores['fecha_fin']) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <h2>Alcance</h2>

    <div class="campo">
        <label for="criterio">Aplica a</label>
        <select id="criterio" name="criterio" data-selector-alcance>
            <option value="todas" <?= $criterio === 'todas' ? 'selected' : '' ?>>Todas las motos</option>
            <option value="marca" <?= $criterio === 'marca' ? 'selected' : '' ?>>Por marca</option>
            <option value="gama" <?= $criterio === 'gama' ? 'selected' : '' ?>>Por gama</option>
            <option value="motocicleta" <?= $criterio === 'motocicleta' ? 'selected' : '' ?>>Por modelo específico</option>
        </select>
    </div>

    <div class="promociones-paneles-alcance">
        <p class="promociones-ayuda" data-panel="todas">La promoción se aplicará a todo el catálogo.</p>

        <div data-panel="marca" hidden>
            <span class="promociones-etiqueta-grupo">Marcas</span>
            <div class="promociones-casillas">
                <?php foreach ($opciones['marcas'] as $marca): ?>
                    <label class="promociones-casilla">
                        <input type="checkbox" name="aplicaciones[]"
                               value="<?= htmlspecialchars($marca) ?>"
                               <?= in_array((string) $marca, $aplicaciones, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($marca) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div data-panel="gama" hidden>
            <span class="promociones-etiqueta-grupo">Gamas</span>
            <div class="promociones-casillas">
                <?php foreach ($opciones['gamas'] as $gama): ?>
                    <label class="promociones-casilla">
                        <input type="checkbox" name="aplicaciones[]"
                               value="<?= htmlspecialchars($gama) ?>"
                               <?= in_array((string) $gama, $aplicaciones, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars(ucfirst($gama)) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div data-panel="motocicleta" hidden>
            <span class="promociones-etiqueta-grupo">Modelos</span>
            <div class="promociones-casillas promociones-casillas-vertical">
                <?php foreach ($opciones['motocicletas'] as $moto): ?>
                    <label class="promociones-casilla">
                        <input type="checkbox" name="aplicaciones[]"
                               value="<?= (int) $moto['id'] ?>"
                               <?= in_array((string) $moto['id'], $aplicaciones, true) ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($moto['nombre']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (isset($errores['aplicaciones'])): ?>
        <span class="promociones-error-campo"><?= htmlspecialchars($errores['aplicaciones']) ?></span>
    <?php endif; ?>

    <div class="promociones-barra-acciones">
        <a class="btn btn-secundario" href="index.php?r=promociones/index">Cancelar</a>
        <button type="submit" class="btn">
            <?= $esEdicion ? 'Guardar cambios' : 'Crear promoción' ?>
        </button>
    </div>
</form>
