<?php $pesos = fn($valor) => '$' . number_format((float) $valor, 0, ',', '.'); ?>

<section class="inicio-portada">
    <div>
        <p class="inicio-etiqueta">Motos nuevas · Taller · Crédito</p>
        <h1>Tu moto nueva, con respaldo de verdad.</h1>
        <p class="inicio-lema">En MOTOCONS compras tu moto, la financias con Brilla o ServiCrédito y la mantienes en nuestro taller con garantía de fábrica.</p>
        <div class="inicio-acciones">
            <a class="btn" href="index.php?r=login/index">Iniciar sesión</a>
            <a class="btn btn-secundario" href="index.php?r=login/registro">Crear cuenta</a>
        </div>
    </div>
    <img class="inicio-portada-logo" src="assets/img/logo.svg" alt="">
</section>

<section class="inicio-servicios">
    <div>
        <span class="inicio-numero">01</span>
        <h2>Venta en tienda</h2>
        <p>Motos de combustión y eléctricas de las mejores marcas, con factura al momento.</p>
    </div>
    <div>
        <span class="inicio-numero">02</span>
        <h2>Crédito fácil</h2>
        <p>Financia tu moto con Brilla o ServiCrédito. Te ayudamos con los papeles.</p>
    </div>
    <div>
        <span class="inicio-numero">03</span>
        <h2>Taller propio</h2>
        <p>Mantenimientos de garantía y revisiones hechos por nuestros mecánicos.</p>
    </div>
    <div>
        <span class="inicio-numero">04</span>
        <h2>Garantía clara</h2>
        <p>Consulta en línea si la garantía de tu moto sigue vigente y qué cubre.</p>
    </div>
</section>

<?php if ($motos): ?>
<section id="catalogo">
    <h2 class="inicio-titulo-seccion">Algunas de nuestras motos</h2>
    <div class="rejilla">
        <?php foreach ($motos as $m): ?>
            <article class="tarjeta inicio-moto">
                <p class="inicio-moto-marca"><?= htmlspecialchars($m['marca']) ?></p>
                <h3><?= htmlspecialchars($m['modelo']) ?></h3>
                <p class="inicio-moto-precio"><?= $pesos($m['precio']) ?></p>
                <p class="inicio-moto-datos">
                    <?= $m['propulsion'] === 'electrica' ? 'Eléctrica' : 'Combustión' ?>
                    · <?= (int) $m['meses_garantia'] ?> meses de garantía
                </p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="inicio-cliente">
    <div>
        <h2>¿Ya compraste con nosotros?</h2>
        <p>Crea tu cuenta con tu cédula y mira tus motos, su garantía y las promociones vigentes.</p>
    </div>
    <a class="btn" href="index.php?r=login/registro">Crear mi cuenta</a>
</section>
