// Ventas en tienda: filas de motos, total en vivo y búsqueda del cliente por documento.
(function () {
    const pesos = (valor) => '$' + Math.round(valor).toLocaleString('es-CO');

    // Confirmación antes de anular y botón de imprimir del comprobante.
    document.querySelectorAll('form[data-confirmar]').forEach((form) => {
        form.addEventListener('submit', (evento) => {
            if (!confirm(form.dataset.confirmar)) {
                evento.preventDefault();
            }
        });
    });
    document.querySelectorAll('[data-imprimir]').forEach((boton) => {
        boton.addEventListener('click', () => window.print());
    });

    const form = document.getElementById('form-venta');
    if (!form) {
        return;
    }

    // ---------- Motos de la venta ----------
    const cuerpo = document.getElementById('venta-unidades');
    const plantilla = document.getElementById('plantilla-unidad');
    const agregar = document.getElementById('agregar-unidad');
    const maximo = Number(form.dataset.maxUnidades) || 10;

    function recalcular() {
        const filas = cuerpo.querySelectorAll('.vp-unidad');
        let total = 0;
        let motos = 0;
        filas.forEach((fila) => {
            const opcion = fila.querySelector('[data-modelo]').selectedOptions[0];
            const precio = opcion && opcion.dataset.precio ? Number(opcion.dataset.precio) : null;
            fila.querySelector('[data-precio-fila]').textContent = precio === null ? '—' : pesos(precio);
            if (precio !== null) {
                total += precio;
                motos++;
            }
            fila.querySelector('[data-quitar]').hidden = filas.length === 1;
        });
        document.getElementById('resumen-total').textContent = pesos(total);
        document.getElementById('resumen-unidades').textContent = motos;
        agregar.hidden = filas.length >= maximo;
    }

    agregar.addEventListener('click', () => {
        cuerpo.appendChild(plantilla.content.cloneNode(true));
        recalcular();
        cuerpo.lastElementChild.querySelector('[data-modelo]').focus();
    });
    cuerpo.addEventListener('change', (evento) => {
        if (evento.target.matches('[data-modelo]')) {
            recalcular();
        }
    });
    cuerpo.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-quitar]');
        if (boton) {
            boton.closest('.vp-unidad').remove();
            recalcular();
        }
    });
    recalcular();

    // ---------- Cliente por documento ----------
    const documento = document.getElementById('documento');
    const buscar = document.getElementById('buscar-cliente');
    const estado = document.getElementById('cliente-estado');
    const campos = form.querySelectorAll('[data-cliente]');
    const textoInicial = estado.textContent;
    let ultimo = '';

    function mostrar(texto, clase) {
        estado.textContent = texto;
        estado.className = 'vp-nota' + (clase ? ' ' + clase : '');
    }

    function soltarCampos() {
        campos.forEach((campo) => { campo.readOnly = false; });
    }

    async function buscarCliente() {
        const valor = documento.value.replace(/[\s.]/g, '');
        if (valor === '' || valor === ultimo) {
            return;
        }
        ultimo = valor;
        try {
            const respuesta = await fetch('index.php?r=ventas_presenciales/cliente&documento=' + encodeURIComponent(valor));
            const datos = await respuesta.json();
            if (datos.encontrado) {
                const c = datos.cliente;
                document.getElementById('nombre').value = c.nombre;
                document.getElementById('correo').value = c.correo || '';
                document.getElementById('telefono').value = c.telefono || '';
                campos.forEach((campo) => { campo.readOnly = true; });
                mostrar('Cliente registrado: ' + c.nombre + '. Se usan sus datos guardados.', 'es-encontrado');
            } else {
                soltarCampos();
                mostrar('Cliente nuevo: escribe su nombre y, si quieres, su correo y teléfono.', 'es-nuevo');
                document.getElementById('nombre').focus();
            }
        } catch (e) {
            ultimo = '';
            soltarCampos();
            mostrar(textoInicial);
        }
    }

    buscar.hidden = false;
    buscar.addEventListener('click', buscarCliente);
    documento.addEventListener('blur', buscarCliente);
    documento.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            buscarCliente();
        }
    });
    documento.addEventListener('input', () => {
        if (ultimo !== '') {
            // Cambió el documento: los datos del cliente anterior ya no aplican.
            ultimo = '';
            campos.forEach((campo) => { if (campo.readOnly) { campo.value = ''; } });
            soltarCampos();
            mostrar(textoInicial);
        }
    });
    // Si el formulario volvió con un error, el documento ya viene escrito.
    buscarCliente();
})();
