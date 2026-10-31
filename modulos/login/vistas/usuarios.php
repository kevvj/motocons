<h1>Usuarios</h1>
<p class="login-subtitulo">Crea las cuentas del personal y desactiva las que ya no se usan. Los clientes se registran solos.</p>

<?php if ($mensaje): ?><div class="alerta alerta-exito"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="login-usuarios">
    <form class="tarjeta" method="post" action="index.php?r=login/usuarios">
        <h2>Nuevo usuario</h2>
        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nuevo['nombre']) ?>" required>
        </div>
        <div class="campo">
            <label for="correo">Correo</label>
            <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($nuevo['correo']) ?>" required>
        </div>
        <div class="campo">
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <?php foreach ($rolesPersonal as $clave => $texto): ?>
                    <option value="<?= $clave ?>" <?= $clave === $nuevo['rol'] ? 'selected' : '' ?>><?= $texto ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="campo">
            <label for="clave">Contraseña inicial</label>
            <input type="password" id="clave" name="clave" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-bloque">Crear usuario</button>
    </form>

    <div class="login-tabla">
        <table class="tabla">
            <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['nombre']) ?></td>
                    <td><?= htmlspecialchars($u['correo']) ?></td>
                    <td><?= htmlspecialchars($roles[$u['rol']] ?? $u['rol']) ?></td>
                    <td><?= $u['activo'] ? 'Activo' : '<span class="login-inactivo">Inactivo</span>' ?></td>
                    <td>
                        <?php if ((int) $u['id'] !== $yo): ?>
                            <form method="post" action="index.php?r=login/activar">
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <input type="hidden" name="activo" value="<?= $u['activo'] ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-secundario btn-chico"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
