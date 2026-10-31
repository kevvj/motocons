<div class="login">
    <div class="tarjeta login-tarjeta">
        <h1>Iniciar sesión</h1>
        <p class="login-subtitulo">Entra con tu correo y contraseña de MOTOCONS.</p>

        <?php if ($error): ?>
            <div class="alerta alerta-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="index.php?r=login/entrar">
            <div class="campo">
                <label for="correo">Correo</label>
                <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required autofocus>
            </div>
            <div class="campo">
                <label for="clave">Contraseña</label>
                <input type="password" id="clave" name="clave" required>
            </div>
            <button type="submit" class="btn btn-bloque">Entrar</button>
        </form>
        <p class="login-pie">¿Eres cliente y no tienes cuenta? <a href="index.php?r=login/registro">Crea tu cuenta</a></p>
    </div>
</div>
