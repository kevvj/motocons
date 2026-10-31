document.addEventListener('DOMContentLoaded', function () {
    var selectorAlcance = document.querySelector('[data-selector-alcance]');

    if (selectorAlcance) {
        var paneles = document.querySelectorAll('.promociones-paneles-alcance [data-panel]');

        var actualizarPaneles = function () {
            var criterio = selectorAlcance.value;

            paneles.forEach(function (panel) {
                var activo = panel.getAttribute('data-panel') === criterio;
                panel.hidden = !activo;

                panel.querySelectorAll('input, select, textarea').forEach(function (control) {
                    control.disabled = !activo;
                    if (!activo && control.type === 'checkbox') {
                        control.checked = false;
                    }
                });
            });
        };

        selectorAlcance.addEventListener('change', actualizarPaneles);
        actualizarPaneles();
    }

    var sufijo = document.getElementById('sufijo-valor');
    var radiosTipo = document.querySelectorAll('input[name="tipo_descuento"]');

    if (sufijo && radiosTipo.length) {
        var actualizarSufijo = function () {
            var seleccionado = document.querySelector('input[name="tipo_descuento"]:checked');
            var esPorcentaje = !seleccionado || seleccionado.value === 'porcentaje';
            sufijo.textContent = esPorcentaje ? '%' : '$';
        };

        radiosTipo.forEach(function (radio) {
            radio.addEventListener('change', actualizarSufijo);
        });
        actualizarSufijo();
    }

    document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
        formulario.addEventListener('submit', function (evento) {
            if (!window.confirm(formulario.getAttribute('data-confirmar'))) {
                evento.preventDefault();
            }
        });
    });

    var fechaInicio = document.getElementById('fecha_inicio');
    var fechaFin = document.getElementById('fecha_fin');

    if (fechaInicio && fechaFin) {
        var sincronizarFechas = function () {
            if (fechaInicio.value) {
                fechaFin.min = fechaInicio.value;
            }
        };

        fechaInicio.addEventListener('change', sincronizarFechas);
        sincronizarFechas();
    }
});
