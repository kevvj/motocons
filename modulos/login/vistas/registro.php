<div class="login">
    <div class="tarjeta login-tarjeta login-tarjeta-ancha">
        <h1>Crear cuenta</h1>
        <p class="login-subtitulo">Con tu cuenta ves tus motos, su garantía y las promociones. Si ya compraste con nosotros, usa el mismo documento.</p>

        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?r=login/registro">
            <div class="campo">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($datos['nombre']) ?>" required autofocus>
            </div>
            <div class="login-fila">
                <div class="campo">
                    <label for="documento">Cédula</label>
                    <input type="text" id="documento" name="documento" inputmode="numeric" value="<?= htmlspecialchars($datos['documento']) ?>" required>
                </div>
                <div class="campo">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars($datos['telefono']) ?>">
                </div>
            </div>
            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($datos['correo']) ?>" required>
            </div>
            <div class="login-fila">
                <div class="campo">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" minlength="6" required>
                </div>
                <div class="campo">
                    <label for="clave2">Repite la contraseña</label>
                    <input type="password" id="clave2" name="clave2" minlength="6" required>
                </div>
            </div>
            <button type="submit" class="btn btn-bloque">Crear mi cuenta</button>
        </form>
        <p class="login-pie">¿Ya tienes cuenta? <a href="index.php?r=login/index">Inicia sesión</a></p>
    </div>
</div>
