<?php
// Pruebas del dominio de créditos, sin PHPUnit. Uso: php pruebas_dominio.php
// Imprime "OK <nombre>" por cada prueba y un RESUMEN; sale con 0 si todo pasa.
require_once __DIR__ . '/CreditosDominio.php';

$fallos  = [];
$pruebas = 0;

// Corre una prueba aislada: si lanza, se cuenta como fallo y se sigue con la siguiente.
function prueba(string $nombre, callable $cuerpo): void
{
    global $fallos, $pruebas;

    $pruebas++;
    try {
        $cuerpo();
        echo "OK {$nombre}\n";
    } catch (Throwable $e) {
        $fallos[] = "{$nombre}: " . $e->getMessage();
        echo "FALLO {$nombre}: " . $e->getMessage() . "\n";
    }
}

function esperar(bool $condicion, string $mensaje): void
{
    if (! $condicion) {
        throw new RuntimeException($mensaje);
    }
}

function esperarIgual($esperado, $real, string $mensaje): void
{
    if ($esperado !== $real) {
        throw new RuntimeException($mensaje . ' (esperado: ' . var_export($esperado, true) . ', real: ' . var_export($real, true) . ')');
    }
}

// Datos del catálogo tal como están en RequisitoCreditoSeeder del backend Laravel.
function requisitoGas(): CreditosRequisito
{
    return new CreditosRequisito(1, 'BRILLA', 'Recibo de gas del último mes cancelado y con cupo aprobado disponible', true);
}

function requisitoCedulaBrilla(): CreditosRequisito
{
    return new CreditosRequisito(2, 'BRILLA', 'Cédula de ciudadanía original y fotocopia ampliada al 150% del titular', true);
}

function requisitoReferenciasBrilla(): CreditosRequisito
{
    return new CreditosRequisito(3, 'BRILLA', 'Dos referencias familiares o personales verificables', true);
}

function requisitoCedulaServicredito(): CreditosRequisito
{
    return new CreditosRequisito(4, 'ServiCrédito', 'Cédula de ciudadanía original del solicitante', true);
}

function requisitoCertificadoOpcional(): CreditosRequisito
{
    return new CreditosRequisito(5, 'SERVICREDITO', 'Certificado laboral vigente con antigüedad mínima de 6 meses', false);
}

// Crea el soporte copiando los datos del catálogo, como lo hará el modelo.
function soporte(string $venta, CreditosRequisito $requisito): CreditosSoporteRequisito
{
    return CreditosSoporteRequisito::desdeRequisito($venta, $requisito);
}

// Arma la solicitud Brilla completa (3 obligatorios) y devuelve [solicitud, soportes].
function solicitudBrilla(): array
{
    $venta = 'V-1001';
    $soportes = [
        'gas'         => soporte($venta, requisitoGas()),
        'cedula'      => soporte($venta, requisitoCedulaBrilla()),
        'referencias' => soporte($venta, requisitoReferenciasBrilla()),
    ];
    $solicitud = new CreditosSolicitudCredito($venta, 'BRILLA', array_values($soportes));

    return [$solicitud, $soportes];
}

// ---------------------------------------------------------------------------
// 1. Brilla sin recibo de gas validado -> excepción con código 'REQUISITO_GAS'
// ---------------------------------------------------------------------------
prueba('1. Brilla sin recibo de gas validado lanza REQUISITO_GAS', function (): void {
    [$solicitud, $soportes] = solicitudBrilla();

    // Cédula y referencias validadas; el gas queda PENDIENTE.
    $soportes['cedula']->validar('analista1');
    $soportes['referencias']->validar('analista1');

    esperarIgual(false, $solicitud->puedeAprobarse(), 'puedeAprobarse() debía ser false sin recibo de gas');
    esperar($solicitud->faltaReciboGas(), 'faltaReciboGas() debía ser true');

    $codigo = null;
    $mensaje = '';
    try {
        $solicitud->validarParaAprobar();
        throw new RuntimeException('validarParaAprobar() debía lanzar excepción');
    } catch (CreditosRequisitoCreditoInvalidoExcepcion $e) {
        $codigo  = $e->getCodigo();
        $mensaje = $e->getMessage();
    }

    esperarIgual('REQUISITO_GAS', $codigo, 'El código de la excepción');
    esperar(str_contains($mensaje, 'V-1001'), 'El mensaje debía nombrar la venta');
    esperar(str_contains($mensaje, 'recibo de gas'), 'El mensaje debía explicar el recibo de gas');
});

// ---------------------------------------------------------------------------
// 2. Brilla con el recibo de gas y los demás obligatorios validados -> se aprueba
// ---------------------------------------------------------------------------
prueba('2. Brilla con todos los obligatorios validados puede aprobarse', function (): void {
    [$solicitud, $soportes] = solicitudBrilla();

    esperarIgual(false, $solicitud->puedeAprobarse(), 'Antes de validar no debía poder aprobarse');

    foreach ($soportes as $uno) {
        $uno->adjuntarSoporte('soportes/' . $uno->getRequisitoId() . '.pdf');
        $uno->validar('analista1');
    }

    esperarIgual([], $solicitud->requisitosFaltantes(), 'No debía quedar ningún obligatorio pendiente');
    esperarIgual(true, $solicitud->puedeAprobarse(), 'Con todo validado debía poder aprobarse');
    esperarIgual(false, $solicitud->faltaReciboGas(), 'El recibo de gas ya está validado');

    // No debe lanzar.
    $solicitud->validarParaAprobar();
});

// ---------------------------------------------------------------------------
// 3. ServiCrédito: sin obligatorios se aprueba; con uno pendiente no
// ---------------------------------------------------------------------------
prueba('3. ServiCrédito sin obligatorios aprueba y con uno pendiente no', function (): void {
    // Solo el certificado laboral, que es opcional.
    $opcional = soporte('V-2001', requisitoCertificadoOpcional());
    $sinObligatorios = new CreditosSolicitudCredito('V-2001', 'SERVICREDITO', [$opcional]);

    esperarIgual([], $sinObligatorios->requisitosObligatorios(), 'No debía haber obligatorios');
    esperarIgual(true, $sinObligatorios->puedeAprobarse(), 'Sin obligatorios debía poder aprobarse');
    $sinObligatorios->validarParaAprobar();

    // Ahora con la cédula obligatoria en PENDIENTE.
    $pendiente = soporte('V-2002', requisitoCedulaServicredito());
    $conObligatorio = new CreditosSolicitudCredito('V-2002', 'SERVICREDITO', [$pendiente]);

    esperarIgual(false, $conObligatorio->puedeAprobarse(), 'Con un obligatorio pendiente no debía aprobarse');

    $mensaje = '';
    try {
        $conObligatorio->validarParaAprobar();
        throw new RuntimeException('validarParaAprobar() debía lanzar excepción');
    } catch (CreditosRequisitoCreditoInvalidoExcepcion $e) {
        throw new RuntimeException('No debía lanzar la excepción de recibo de gas en ServiCrédito');
    } catch (CreditosExcepcion $e) {
        $mensaje = $e->getMessage();
    }

    esperar(str_contains($mensaje, 'Cédula de ciudadanía original del solicitante'), 'El mensaje debía nombrar el requisito');
    esperar(str_contains($mensaje, 'PENDIENTE'), 'El mensaje debía indicar el estado actual');

    // Validada la cédula, ya se aprueba.
    $pendiente->validar('analista2');
    esperarIgual(true, $conObligatorio->puedeAprobarse(), 'Con la cédula validada debía poder aprobarse');
});

// ---------------------------------------------------------------------------
// 4. Adjuntar un soporte nuevo estando VALIDADO devuelve el estado a PENDIENTE
// ---------------------------------------------------------------------------
prueba('4. Adjuntar soporte nuevo reinicia el estado a PENDIENTE', function (): void {
    $gas = soporte('V-3001', requisitoGas());

    $gas->adjuntarSoporte('soportes/gas-enero.pdf');
    $gas->validar('analista1');
    esperarIgual(CreditosSoporteRequisito::ESTADO_VALIDADO, $gas->getEstado(), 'Debía quedar VALIDADO');
    esperarIgual('analista1', $gas->getValidadoPor(), 'Debía guardar quién validó');

    $gas->adjuntarSoporte('soportes/gas-febrero.pdf');
    esperarIgual(CreditosSoporteRequisito::ESTADO_PENDIENTE, $gas->getEstado(), 'Debía volver a PENDIENTE');
    esperarIgual('soportes/gas-febrero.pdf', $gas->getSoporteAdjunto(), 'Debía guardar la ruta nueva');
    esperarIgual(null, $gas->getValidadoPor(), 'La validación anterior ya no debía aplicar');
    esperar($gas->estaPendiente(), 'estaPendiente() debía ser true');

    // Y la solicitud vuelve a no poder aprobarse.
    [$solicitud, $soportes] = solicitudBrilla();
    foreach ($soportes as $uno) {
        $uno->validar('analista1');
    }
    esperarIgual(true, $solicitud->puedeAprobarse(), 'Todo validado debía aprobarse');
    $soportes['gas']->adjuntarSoporte('soportes/gas-marzo.pdf');
    esperarIgual(false, $solicitud->puedeAprobarse(), 'Un gas nuevo sin validar debía bloquear la aprobación');
});

// ---------------------------------------------------------------------------
// 5. rechazar() marca RECHAZADO y guarda validadoPor
// ---------------------------------------------------------------------------
prueba('5. rechazar() marca RECHAZADO y guarda validadoPor', function (): void {
    $referencias = soporte('V-4001', requisitoReferenciasBrilla());
    $referencias->adjuntarSoporte('soportes/referencias.pdf');
    $referencias->rechazar('analista3');

    esperarIgual(CreditosSoporteRequisito::ESTADO_RECHAZADO, $referencias->getEstado(), 'Debía quedar RECHAZADO');
    esperarIgual('analista3', $referencias->getValidadoPor(), 'Debía guardar quién rechazó');
    esperar($referencias->estaRechazado(), 'estaRechazado() debía ser true');
    esperarIgual(false, $referencias->estaValidado(), 'estaValidado() debía ser false');
    esperarIgual(false, $referencias->estaPendiente(), 'estaPendiente() debía ser false');

    // Un rechazado cuenta como faltante para aprobar.
    $solicitud = new CreditosSolicitudCredito('V-4001', 'BRILLA', [$referencias]);
    esperarIgual(1, count($solicitud->requisitosFaltantes()), 'Un rechazado debía quedar como faltante');
    esperarIgual(false, $solicitud->puedeAprobarse(), 'Con un obligatorio rechazado no debía aprobarse');
});

// ---------------------------------------------------------------------------
// 6. porcentajeAvance: 0%, 50% y 100%
// ---------------------------------------------------------------------------
prueba('6. porcentajeAvance calcula 0%, 50% y 100%', function (): void {
    $venta = 'V-5001';
    $uno = soporte($venta, requisitoCedulaServicredito());
    $dos = new CreditosSoporteRequisito(
        $venta,
        6,
        CreditosSoporteRequisito::ESTADO_PENDIENTE,
        null,
        null,
        'Desprendibles de nómina de los dos últimos meses',
        true
    );
    $solicitud = new CreditosSolicitudCredito($venta, 'SERVICREDITO', [$uno, $dos]);

    esperarIgual(0.0, $solicitud->porcentajeAvance(), 'Sin validar debía ser 0%');

    $uno->validar('analista1');
    esperarIgual(50.0, $solicitud->porcentajeAvance(), 'Uno de dos debía ser 50%');

    $dos->validar('analista1');
    esperarIgual(100.0, $solicitud->porcentajeAvance(), 'Con todo validado debía ser 100%');

    // Sin requisitos no se divide por cero.
    $vacia = new CreditosSolicitudCredito('V-5002', 'BRILLA');
    esperarIgual(0.0, $vacia->porcentajeAvance(), 'Sin requisitos debía ser 0%');
});

// ---------------------------------------------------------------------------
// 7. requisitosObligatorios() no incluye los opcionales
// ---------------------------------------------------------------------------
prueba('7. requisitosObligatorios no incluye los opcionales', function (): void {
    $venta = 'V-6001';
    $obligatorio = soporte($venta, requisitoCedulaServicredito());
    $opcional    = soporte($venta, requisitoCertificadoOpcional());
    $solicitud   = new CreditosSolicitudCredito($venta, 'SERVICREDITO', [$obligatorio, $opcional]);

    esperarIgual(2, count($solicitud->getRequisitos()), 'La solicitud debía tener los 2 requisitos');

    $ids = array_map(
        static fn (CreditosSoporteRequisito $r): int => $r->getRequisitoId(),
        $solicitud->requisitosObligatorios()
    );
    esperarIgual([4], $ids, 'Solo la cédula (id 4) es obligatoria');
    esperarIgual(false, $opcional->isObligatorio(), 'El certificado laboral debía ser opcional');

    // El opcional pendiente no bloquea; el obligatorio sí.
    $faltantes = array_map(
        static fn (CreditosSoporteRequisito $r): int => $r->getRequisitoId(),
        $solicitud->requisitosFaltantes()
    );
    esperarIgual([4], $faltantes, 'Solo debía faltar la cédula; el opcional no cuenta');

    $obligatorio->validar('analista4');
    esperarIgual([], $solicitud->requisitosFaltantes(), 'Con la cédula validada no debía faltar ninguno');
    esperarIgual(true, $solicitud->puedeAprobarse(), 'El opcional pendiente no debía impedir la aprobación');

    // Y el texto con tildes se conserva tal cual para mostrarlo en la vista.
    esperarIgual('Certificado laboral vigente con antigüedad mínima de 6 meses', $opcional->getDescripcion(), 'La descripción se copia sin cambios');
});

// ---------------------------------------------------------------------------
// 8. Las excepciones son del tipo base correcto
// ---------------------------------------------------------------------------
prueba('8. Las excepciones heredan de CreditosExcepcion y de Exception', function (): void {
    $gas  = CreditosRequisitoCreditoInvalidoExcepcion::reciboGasObligatorio('V-9001');
    $edit = CreditosVentaNoEditableExcepcion::estadoNoPermiteCambios('V-9001', 'PAGADA');

    esperar($gas instanceof CreditosExcepcion, 'La excepción de gas debía ser CreditosExcepcion');
    esperar($edit instanceof CreditosExcepcion, 'La excepción de venta debía ser CreditosExcepcion');
    esperar($gas instanceof Exception, 'La excepción de gas debía ser Exception');
    esperarIgual('REQUISITO_GAS', $gas->getCodigo(), 'Código de la excepción de gas');
    esperarIgual('VENTA_NO_EDITABLE', $edit->getCodigo(), 'Código de la excepción de venta no editable');
    esperar(str_contains($edit->getMessage(), 'V-9001') && str_contains($edit->getMessage(), 'PAGADA'), 'El mensaje debía nombrar venta y estado');

    // La base sin código queda con el genérico.
    $base = new CreditosExcepcion('Algo salió mal');
    esperarIgual(CreditosExcepcion::CODIGO_GENERICO, $base->getCodigo(), 'Código genérico por defecto');
    esperar($base instanceof Exception, 'La base debía ser Exception');

    // Una instancia de la subclase ES la base, pero no al revés.
    esperarIgual(false, $base instanceof CreditosRequisitoCreditoInvalidoExcepcion, 'La base no debe ser la subclase');
});

// ---------------------------------------------------------------------------
// Resumen
// ---------------------------------------------------------------------------
$pasadas = $pruebas - count($fallos);

echo "\nRESUMEN: {$pasadas}/{$pruebas} pruebas pasaron";
if ($fallos !== []) {
    echo ', ' . count($fallos) . " fallaron:\n";
    foreach ($fallos as $fallo) {
        echo "  - {$fallo}\n";
    }
    exit(1);
}

echo ".\n";
exit(0);
