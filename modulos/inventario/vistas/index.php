<?php
/**
 * VISTA: index.php (Módulo Inventario Completo con Filtros Nativo PHP)
 */
$busqueda  = $_GET['q'] ?? '';
$marca_sel = $_GET['marca_id'] ?? '';
$color_sel = $_GET['color'] ?? '';
$dispo_sel = $_GET['disponibilidad'] ?? '';
$tab_activa = $_GET['tab'] ?? 'stock'; 
?>

<h1>Gestión de Inventario</h1>
<p class="mantenimiento-subtitulo">Listado general de stock físico de motocicletas y proveedores en el almacén.</p>

<section class="seccion-tabla" aria-label="Consultas de inventario y proveedores">
    <!-- PESTAÑAS DE CONSULTA -->
    <div class="pestanas-consulta" role="tablist">
        <a href="index.php?r=inventario/index&tab=stock" class="pestana-consulta <?= $tab_activa === 'stock' ? 'activa' : '' ?>" style="text-decoration: none; display: inline-block;">
            Stock Moto
        </a>
        <a href="index.php?r=inventario/index&tab=proveedores" class="pestana-consulta <?= $tab_activa === 'proveedores' ? 'activa' : '' ?>" style="text-decoration: none; display: inline-block;">
            Proveedor
        </a>
        <a href="index.php?r=inventario/index&tab=registrar_modelo" class="pestana-consulta <?= $tab_activa === 'registrar_modelo' ? 'activa' : '' ?>" style="text-decoration: none; display: inline-block;">
            Registrar Marca y Modelo
        </a>
        <a href="index.php?r=inventario/index&tab=registrar_ingreso" class="pestana-consulta <?= $tab_activa === 'registrar_ingreso' ? 'activa' : '' ?>" style="text-decoration: none; display: inline-block;">
            Registrar Ingreso
        </a>
    </div>

    <div class="contenido-consulta">
        
        <!-- PANEL: STOCK MOTO -->
        <?php if ($tab_activa === 'stock'): ?>
            <div id="panel-stock" role="tabpanel">
                <h2 class="titulo-bloque">1. Stock Físico Real en Almacén</h2>

                <!-- PANEL DE BUSQUEDA Y FILTROS UNIFICADO -->
                <form method="GET" action="index.php" class="formulario-columna" style="background: none; border: none; padding: 0; margin-bottom: 25px;">
                    <input type="hidden" name="r" value="inventario/index">
                    <input type="hidden" name="tab" value="stock">

                    <!-- Buscador que hereda la clase campo-formulario -->
                    <div class="campo-formulario">
                        <label for="filtro-busqueda-stock">Buscar por nombre, chasis o motor:</label>
                        <input type="search" id="filtro-busqueda-stock" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Nombre del modelo, número de chasis o motor...">
                    </div>

                    <div class="grupo-selects-filtros" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; width: 100%;">
                        
                        <!-- MARCA: Agregamos la clase campo-formulario para asegurar su estructura -->
                        <div class="campo-formulario" style="flex: 1; min-width: 160px;">
                            <label for="filtro-marca-stock">Marca:</label>
                            <select id="filtro-marca-stock" name="marca_id">
                                <option value="">Todas las marcas</option>
                                <?php if (!empty($marcas) && is_array($marcas)): ?>
                                    <?php foreach ($marcas as $m): ?>
                                        <option value="<?= $m['marca_id'] ?>" <?= $marca_sel == $m['marca_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(strtoupper($m['nombre'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <!-- COLOR -->
                        <div class="campo-formulario" style="flex: 1; min-width: 160px;">
                            <label for="filtro-color-stock">Color:</label>
                            <select id="filtro-color-stock" name="color">
                                <option value="">Todos los colores</option>
                                <option value="AZUL" <?= $color_sel === 'AZUL' ? 'selected' : '' ?>>AZUL</option>
                                <option value="NEGRO" <?= $color_sel === 'NEGRO' ? 'selected' : '' ?>>NEGRO</option>
                                <option value="ROJO" <?= $color_sel === 'ROJO' ? 'selected' : '' ?>>ROJO</option>
                                <option value="GRIS" <?= $color_sel === 'GRIS' ? 'selected' : '' ?>>GRIS</option>
                                <option value="BLANCO" <?= $color_sel === 'BLANCO' ? 'selected' : '' ?>>BLANCO</option>
                            </select>
                        </div>

                        <!-- DISPONIBILIDAD -->
                        <div class="campo-formulario" style="flex: 1; min-width: 160px;">
                            <label for="filtro-disponibilidad-stock">Disponibilidad:</label>
                            <select id="filtro-disponibilidad-stock" name="disponibilidad">
                                <option value="">Todas</option>
                                <option value="disponible" <?= $dispo_sel === 'disponible' ? 'selected' : '' ?>>disponible</option>
                                <option value="reservada" <?= $dispo_sel === 'reservada' ? 'selected' : '' ?>>reservada</option>
                                <option value="vendida" <?= $dispo_sel === 'vendida' ? 'selected' : '' ?>>vendida</option>
                            </select>
                        </div>

                        <!-- BOTONES -->
                        <div style="display: flex; gap: 8px; align-items: center; height: 41px;">
                            <button type="submit" class="btn-naranja">Filtrar</button>
                            <?php if ($busqueda !== '' || $marca_sel !== '' || $color_sel !== '' || $dispo_sel !== ''): ?>
                                <a href="index.php?r=inventario/index&tab=stock" class="btn-oscuro">Limpiar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <div class="tabla-responsiva">
                    <table>
                        <thead>
                            <tr>
                                <th>Marca</th>
                                <th>Referencia Comercial (Modelo)</th>
                                <th>Cilindraje</th>
                                <th>Numero Chasis</th>
                                <th>Numero Motor</th>
                                <th>Color</th>
                                <th>Disponibilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($productos) && is_array($productos)): ?>
                                <?php foreach ($productos as $moto): ?>
                                    <tr>
                                        <td class="inventario-col-marca"><?= htmlspecialchars($moto['nombre_marca'] ?? 'N/A') ?></td>
                                        <td class="inventario-col-modelo"><?= htmlspecialchars($moto['modelo'] ?? '') ?></td>
                                        
                                        <td><?php 
                                            $es_electrica = (isset($moto['propulsion']) && $moto['propulsion'] === 'electrica') || (intval($moto['cilindraje'] ?? 0) === 0);
                                            echo $es_electrica ? 'Eléctrica' : htmlspecialchars($moto['cilindraje']) . ' cc';
                                        ?></td>
                                        
                                        <td><span class="inventario-serial"><?= htmlspecialchars($moto['chasis'] ?? 'N/A') ?></span></td>
                                        <td><span class="inventario-serial"><?= htmlspecialchars($moto['motor'] ?? 'N/A') ?></span></td>
                                        <td style="text-transform: uppercase; font-size: 0.85rem;"><?= htmlspecialchars($moto['color'] ?? 'N/A') ?></td>
                                        <td>
                                            <?php 
                                            $dispo = strtolower($moto['disponibilidad'] ?? 'disponible');
                                            if ($dispo === 'vendida'): ?>
                                                <span class="inventario-badge vendida">vendida</span>
                                            <?php elseif ($dispo === 'reservada'): ?>
                                                <span class="inventario-badge reservada">reservada</span>
                                            <?php else: ?>
                                                <span class="inventario-badge disponible">disponible</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="texto-carga">No se encontraron motocicletas registradas en el almacén con esos filtros.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- PANEL 2: PROVEEDORES -->
        <?php if ($tab_activa === 'proveedores'): ?>
            <div id="panel-proveedores" role="tabpanel">
                
                <!-- BLOQUE A: REGISTRAR NUEVO PROVEEDOR -->
                <div class="tarjeta-registro">
                    <h2 class="titulo-bloque">1. Registrar Nuevo Proveedor / Distribuidor</h2>
                    
                    <form id="form-crear-proveedor" action="index.php?r=inventario/guardarproveedor" method="POST" class="formulario-flex">
                        <div class="campo-formulario" style="flex: 2; min-width: 200px;">
                            <label>Nombre de proveedor:</label>
                            <input type="text" name="nombre" placeholder="Nombre de proveedor" required>
                        </div>
                        <div class="campo-formulario" style="flex: 1; min-width: 140px;">
                            <label>NIT (Con guion):</label>
                            <input type="text" name="nit" placeholder="Ej: 900123456-1" required>
                        </div>
                        <div class="campo-formulario" style="flex: 1; min-width: 140px;">
                            <label>Teléfono:</label>
                            <input type="tel" name="telefono" placeholder="Teléfono">
                        </div>
                        <div class="campo-formulario" style="flex: 1.5; min-width: 180px;">
                            <label>Correo electrónico:</label>
                            <input type="email" name="correo" placeholder="Correo electrónico">
                        </div>
                        <button type="submit" class="btn-naranja">Agregar</button>
                    </form>
                </div>

                <!-- BLOQUE B: CONSULTA Y LISTADO DE PROVEEDORES -->
                <div class="tarjeta-registro">
                    <h2 class="titulo-bloque">2. Directorio y Búsqueda de Proveedores</h2>

                    <!-- BUSCADOR INTERNO INTEGRADO -->
                    <form method="GET" action="index.php" style="background: none; border: none; padding: 0; margin-bottom: 20px; width: 100%;">
                        <input type="hidden" name="r" value="inventario/index">
                        <input type="hidden" name="tab" value="proveedores">
                        <div class="campo-formulario">
                            <label for="buscar-proveedor-nombre">Buscar proveedor por nombre:</label>
                            <div style="display: flex; gap: 10px; width: 100%;">
                                <input type="search" id="buscar-proveedor-nombre" name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Escribe el nombre del proveedor...">
                                <button type="submit" class="btn-naranja">Buscar</button>
                                <?php if ($busqueda !== ''): ?>
                                    <a href="index.php?r=inventario/index&tab=proveedores" class="btn-secundario-filtro" style="padding: 10px 20px; text-decoration: none;">Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>

                    <div class="tabla-responsiva">
                        <table>
                            <thead>
                                <tr><th>Proveedor</th><th>NIT</th><th>Teléfono</th><th>Correo</th><th>Estado</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($proveedores) && is_array($proveedores)): ?>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <tr>
                                            <td style="font-weight: 700; color: #1a202c;"><?= htmlspecialchars($prov['nombre'] ?? '') ?></td>
                                            <td><span class="inventario-serial"><?= htmlspecialchars($prov['nit'] ?? 'N/A') ?></span></td>
                                            <td><?= htmlspecialchars($prov['telefono'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($prov['correo'] ?? 'N/A') ?></td>
                                            <td><span class="inventario-badge <?= isset($prov['activo']) && $prov['activo'] == 1 ? 'disponible' : 'vendida' ?>"><?= isset($prov['activo']) && $prov['activo'] == 1 ? 'Activo' : 'Inactivo' ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- PANEL 3: REGISTRAR MODELO (CATÁLOGO) -->
        <?php if ($tab_activa === 'registrar_modelo'): ?>
            <div id="panel-registrar-modelo" role="tabpanel">
                
                <div class="tarjeta-registro">
                    <h2 class="titulo-bloque">1. Registrar Nueva Marca</h2>
                    <form action="index.php?r=inventario/guardarmarca" method="POST" class="formulario-columna">
                        <div class="campo-formulario">
                            <label>Nombre de nueva marca (Ej: Suzuki):</label>
                            <input type="text" name="nombre_marca_nuevo" placeholder="Escribe la marca a agregar..." required>
                        </div>
                        <div style="display: flex;">
                            <button type="submit" class="btn-naranja">Agregar Marca</button>
                        </div>
                    </form>
                </div>

                <div class="tarjeta-registro">
                    <h2 class="titulo-bloque">2. Registrar Especificaciones del Modelo (Catálogo)</h2>
                    <form action="index.php?r=inventario/guardarmodelo" method="POST" class="formulario-columna">
                        <div class="campo-formulario">
                            <label>Seleccionar Marca:</label>
                            <select name="marca_id" required>
                                <option value="">-- Seleccione Marca --</option>
                                <?php if (!empty($marcas) && is_array($marcas)): ?>
                                    <?php foreach ($marcas as $m): ?>
                                        <option value="<?= $m['marca_id'] ?>"><?= htmlspecialchars(strtoupper($m['nombre'])) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="campo-formulario">
                            <label>Seleccionar Distribuidor:</label>
                            <select name="distribuidor_id" required>
                                <option value="">-- Seleccione distribuidor --</option>
                                <?php if (!empty($distribuidores) && is_array($distribuidores)): ?>
                                    <?php foreach ($distribuidores as $dist): 
                                        $id_dist = $dist['id'] ?? $dist['proveedor_id'] ?? $dist['nit'] ?? '';
                                    ?>
                                        <option value="<?= $id_dist ?>"><?= htmlspecialchars(strtoupper($dist['nombre'])) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="campo-formulario">
                            <label>Nombre del Modelo:</label>
                            <input type="text" name="modelo" placeholder="Ej: FZ 25, XTZ 150" required>
                        </div>
                        <div class="campo-formulario">
                            <label>Precio de Venta ($):</label>
                            <input type="number" name="precio_venta" placeholder="Ej: 14500000">
                        </div>
                        <div class="campo-formulario">
                            <label>Año Comercial:</label>
                            <input type="number" name="anio" placeholder="Ej: 2026" required>
                        </div>
                        <div class="campo-checkbox">
                            <input type="checkbox" id="electrica" name="electrica" value="1">
                            <label for="electrica">Electrica</label>
                        </div>
                        <div class="campo-formulario">
                            <label>Cilindraje (cc):</label>
                            <input type="number" name="cilindraje" placeholder="Ej: 149" required>
                        </div>
                        <button type="submit" class="btn-naranja" style="width: 100%;">Registrar en Catálogo</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- PANEL 4: REGISTRAR INGRESO (STOCK FÍSICO) -->
        <?php if ($tab_activa === 'registrar_ingreso'): ?>
            <div id="panel-registrar-ingreso" role="tabpanel">
                
                <div class="tarjeta-registro">
                    <h2 class="titulo-bloque">2. Registrar Ingreso (Compra a Proveedor)</h2>
                    
                    <form action="index.php?r=inventario/guardaringreso" method="POST" class="formulario-columna">
                        
                        <div class="campo-formulario">
                            <label>Seleccionar Proveedor:</label>
                            <select name="proveedor_id" required>
                                <option value="">-- Seleccione proveedor --</option>
                                <?php if (!empty($proveedores) && is_array($proveedores)): ?>
                                    <?php foreach ($proveedores as $prov): ?>
                                        <option value="<?= $prov['proveedor_id'] ?? $prov['nit'] ?>"><?= htmlspecialchars($prov['nombre']) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="campo-formulario">
    <label>Seleccionar Vehículo (Catálogo):</label>
    <select name="moto_id" required>
        <option value="">-- Seleccione una referencia --</option>
        <?php if (!empty($catalogo_base) && is_array($catalogo_base)): ?>
            <?php foreach ($catalogo_base as $cat): ?>
                <?php 
                // Buscamos cuál es el nombre real de la columna de ID que viene del catálogo
                // Evaluamos las 3 variantes más comunes en tu diplomado:
                if (isset($cat['id'])) {
                    $id_moto = $cat['id'];
                } elseif (isset($cat['producto_moto_id'])) {
                    $id_moto = $cat['producto_moto_id'];
                } elseif (isset($cat['moto_id'])) {
                    $id_moto = $cat['moto_id'];
                } else {
                    $id_moto = 0; // Si no encuentra ninguna, temporalmente será 0
                }
                
                // Evaluamos si es eléctrica por cilindraje cero o vacío
                $cilindraje = intval($cat['cilindraje'] ?? 0);
                $info_tecnica = ($cilindraje === 0) ? 'ELÉCTRICA' : $cilindraje . 'cc';
                
                // Formateamos los textos de manera segura
                $marca = htmlspecialchars($cat['nombre_marca'] ?? 'MOTO');
                $modelo = htmlspecialchars($cat['modelo'] ?? '');
                ?>
                <option value="<?= $id_moto ?>">
                    <?= $marca . " - " . $modelo . " (" . $info_tecnica . ")" ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    </select>
</div>


                        <div class="campo-formulario">
                            <label>Número de Chasis:</label>
                            <input type="text" name="chasis" placeholder="Ej: 9SB123X..." required>
                        </div>

                        <div class="campo-formulario">
                            <label>Número de Motor:</label>
                            <input type="text" name="motor" placeholder="Ej: 152FMH..." required>
                        </div>

                        <div class="campo-formulario">
                            <label>Color:</label>
                            <input type="text" name="color" placeholder="Ej: Negro, Rojo" required>
                        </div>

                        <button type="submit" class="btn-naranja" style="width: 100%;">Ingresar Vehículo al Stock</button>
                    </form>
                </div>

            </div>
        <?php endif; ?>
    </div>
</section>
