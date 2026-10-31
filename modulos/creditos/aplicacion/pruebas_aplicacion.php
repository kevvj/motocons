<?php
// Pruebas de integración de los casos de uso (fase 3) contra MySQL/MariaDB real.
// Sin PHPUnit, mismo formato que dominio/pruebas_dominio.php.
//
// Requiere: config/config.php apuntando a una base con el esquema de database/motocons.sql
// ya importado (clientes y motos de garantía deben existir: cliente 1-3, moto 1-3).
//
// Las ventas que usa cada prueba se crean aquí mismo (PENDIENTE/EN_CREDITO), en vez de
// depender de IDs fijos de otros módulos: desde que ventas_presenciales agregó venta.estado,
// las ventas de garantía (1-4) quedaron ENTREGADA y ya no sirven para radicar un crédito
// (ver ADR-014). Cada corrida inserta ventas nuevas; no hace falta limpiarlas entre corridas,
// pero si se quiere una base liviana, reimportar database/motocons.sql.
//
// Uso: php -d extension=pdo_mysql.so modulos/creditos/aplicacion/pruebas_aplicacion.php

define('RAIZ', dirname(__DIR__, 3));

require RAIZ . '/core/Database.php';
require RAIZ . '/core/Modelo.php';

$config = RAIZ . '/config/config.php';
if (!file_exists($config)) {
    exit("Falta config/config.php. Ver [[Correr el proyecto en local]].\n");
}
require $config;

require_once __DIR__ . '/../dominio/CreditosDominio.php';
require_once __DIR__ . '/../modelos/CreditosRequisitoModelo.php';
require_once __DIR__ . '/../modelos/CreditosSolicitudModelo.php';
require_once __DIR__ . '/../modelos/CreditosSolicitudRequisitoModelo.php';
require_once __DIR__ . '/../modelos/CreditosRecursoModelo.php';
require_once __DIR__ . '/CreditosAplicacion.php';

$fallos  = [];
$pruebas = 0;

function prueba(string $nombre, callable $cuerpo): void
{
    global $fallos, $pruebas;

    $pruebas++;
    try {
        $cuerpo();
        echo "OK {$nombre}\n";
    } catch (Throwable $e) {
        $fallos[] = "{$nombre}: " . get_class($e) . ' - ' . $e->getMessage();
        echo "FALLO {$nombre}: " . get_class($e) . ' - ' . $e->getMessage() . "\n";
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

// Deja las tablas propias en blanco antes de correr. Son nuestras (solicitud_credito,
// solicitud_requisito): no se tocan tablas de otros módulos.
function limpiarTablasPropias(): void
{
    $db = Database::conexion();
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    $db->exec('TRUNCATE TABLE solicitud_requisito');
    $db->exec('TRUNCATE TABLE solicitud_credito');
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// Espera que $cuerpo lance una excepción con ese código exacto (CreditosExcepcion::getCodigo()).
function esperarCodigo(string $codigoEsperado, callable $cuerpo, string $mensaje): void
{
    try {
        $cuerpo();
    } catch (CreditosExcepcion $e) {
        esperarIgual($codigoEsperado, $e->getCodigo(), $mensaje . ' — código de la excepción');
        return;
    }

    throw new RuntimeException($mensaje . ' — debía lanzar CreditosExcepcion y no lanzó nada');
}

// Venta de prueba propia del módulo créditos: no depende de los datos de otros grupos,
// que pueden cambiar (ver ADR-014). Nace sin factura ni vehículo, como una venta real
// recién creada por ventas_presenciales.
function crearVenta(int $clienteId, float $total, string $estado = 'PENDIENTE'): int
{
    $db = Database::conexion();
    $db->prepare(
        "INSERT INTO venta (numero_factura, fecha, cliente_id, canal, total, estado)
         VALUES (NULL, CURDATE(), ?, 'tienda', ?, ?)"
    )->execute([$clienteId, $total, $estado]);

    return (int) $db->lastInsertId();
}

// Solo para el caso (hoy poco común, pero posible) de una venta que ya tiene su unidad
// en `vehiculo` antes de radicar el crédito.
function crearVehiculo(int $ventaId, int $clienteId, int $motoId, string $chasis): int
{
    $db = Database::conexion();
    $db->prepare(
        'INSERT INTO vehiculo (chasis, moto_id, cliente_id, venta_id) VALUES (?, ?, ?, ?)'
    )->execute([$chasis, $motoId, $clienteId, $ventaId]);

    return (int) $db->lastInsertId();
}

limpiarTablasPropias();

$solicitudes = new CreditosSolicitudModelo();
$checklist   = new CreditosSolicitudRequisitoModelo();
$recursos    = new CreditosRecursoModelo();
$requisitos  = new CreditosRequisitoModelo();

$radicar  = new CreditosRadicarSolicitud($solicitudes, $checklist, $recursos);
$adjuntar = new CreditosAdjuntarSoporte($solicitudes, $checklist);
$evaluar  = new CreditosEvaluarRequisito($solicitudes, $checklist);
$aprobar  = new CreditosAprobarCredito($solicitudes, $checklist);
$listar   = new CreditosListarRequisitos($requisitos);

// IDs de requisito_credito según el INSERT de database/motocons.sql (en orden):
// 1 gas (BRILLA, obligatorio), 2 cédula (BRILLA, obligatorio), 3 referencias (BRILLA, obligatorio),
// 4 cédula (SERVICREDITO, obligatorio), 5 nómina (SERVICREDITO, obligatorio), 6 certificado (SERVICREDITO, opcional).
const REQ_GAS                  = 1;
const REQ_CEDULA_BRILLA        = 2;
const REQ_REFERENCIAS_BRILLA   = 3;
const REQ_CEDULA_SERVICREDITO  = 4;
const REQ_NOMINA_SERVICREDITO  = 5;
const REQ_CERTIFICADO_OPCIONAL = 6;
const ADMIN_ID = 1; // usuario de prueba admin@motocons.com

// ===========================================================================
// CreditosListarRequisitos
// ===========================================================================

prueba('Listar: BRILLA devuelve 3 requisitos, todos CreditosRequisito', function () use ($listar): void {
    $filas = $listar->ejecutar('BRILLA');
    esperarIgual(3, count($filas), 'BRILLA debía tener 3 requisitos');
    foreach ($filas as $r) {
        esperar($r instanceof CreditosRequisito, 'Cada fila debía ser CreditosRequisito');
        esperar($r->esParaBrilla(), 'Cada fila debía ser de BRILLA');
    }
});

prueba('Listar: SERVICREDITO devuelve 3 requisitos', function () use ($listar): void {
    esperarIgual(3, count($listar->ejecutar('SERVICREDITO')), 'SERVICREDITO debía tener 3 requisitos');
});

prueba('Listar: sin filtro devuelve el catálogo completo (6)', function () use ($listar): void {
    esperarIgual(6, count($listar->ejecutar()), 'Sin filtro debía traer las 6 filas del catálogo');
});

prueba('Listar: el medio de pago en minúsculas también funciona', function () use ($listar): void {
    esperarIgual(3, count($listar->ejecutar('brilla')), 'brilla en minúsculas debía normalizarse');
});

// ===========================================================================
// CreditosRadicarSolicitud
// ===========================================================================

$vBrilla = crearVenta(1, 15000000);

prueba('Radicar: crea la solicitud y copia el checklist BRILLA completo', function () use ($radicar, $solicitudes, $checklist, $vBrilla): void {
    $id = $radicar->ejecutar($vBrilla, null, 'BRILLA', 15000000, ADMIN_ID);
    esperar($id > 0, 'Debía devolver un id positivo');

    $fila = $solicitudes->buscarPorId($id);
    esperarIgual(CreditosSolicitudModelo::ESTADO_EN_PROCESO_CREDITO, $fila['estado'], 'Nace EN_PROCESO_CREDITO');
    esperarIgual($vBrilla, (int) $fila['venta_id'], 'Debía apuntar a la venta creada para esta prueba');

    $filasChecklist = $checklist->listarPorSolicitud($id);
    esperarIgual(3, count($filasChecklist), 'El checklist de BRILLA debía copiar 3 requisitos');
    foreach ($filasChecklist as $f) {
        esperarIgual('PENDIENTE', $f['estado'], 'Todo el checklist nace PENDIENTE');
    }
});

prueba('Radicar: una segunda solicitud sobre la misma venta se rechaza', function () use ($radicar, $vBrilla): void {
    esperarCodigo('SOLICITUD_DUPLICADA', function () use ($radicar, $vBrilla): void {
        $radicar->ejecutar($vBrilla, null, 'BRILLA', 1000000, ADMIN_ID);
    }, 'Radicar dos veces sobre la misma venta');
});

prueba('Radicar: venta inexistente se rechaza', function () use ($radicar): void {
    esperarCodigo('VENTA_NO_EXISTE', function () use ($radicar): void {
        $radicar->ejecutar(999999, null, 'BRILLA', 1000000, ADMIN_ID);
    }, 'Radicar sobre una venta que no existe');
});

prueba('Radicar: medio de pago inválido se rechaza', function () use ($radicar): void {
    $venta = crearVenta(1, 1000000);
    esperarCodigo('MEDIO_PAGO_INVALIDO', function () use ($radicar, $venta): void {
        $radicar->ejecutar($venta, null, 'OTRO', 1000000, ADMIN_ID);
    }, 'Radicar con medioPago=OTRO');
});

prueba('Radicar: el vehículo debe pertenecer a la venta indicada', function () use ($radicar): void {
    // vehiculo 1 es de la venta 1 de garantía (otra venta, no esta).
    $venta = crearVenta(2, 1000000);
    esperarCodigo('VEHICULO_NO_PERTENECE', function () use ($radicar, $venta): void {
        $radicar->ejecutar($venta, 1, 'SERVICREDITO', 1000000, ADMIN_ID);
    }, 'Radicar con el vehículo de otra venta');
});

prueba('Radicar: una venta ya ENTREGADA (resuelta) no se puede financiar', function () use ($radicar): void {
    // Venta 1 real: es de garantía, ya tiene factura, el merge de ventas_presenciales
    // la dejó en ENTREGADA. No tiene solicitud_credito (las pruebas usan ventas propias).
    esperarCodigo('VENTA_NO_FINANCIABLE', function () use ($radicar): void {
        $radicar->ejecutar(1, 1, 'BRILLA', 1000000, ADMIN_ID);
    }, 'Radicar sobre una venta ENTREGADA');
});

prueba('Radicar: una venta EN_CREDITO también se puede financiar', function () use ($radicar, $checklist): void {
    $venta = crearVenta(3, 14900000, 'EN_CREDITO');
    $id = $radicar->ejecutar($venta, null, 'SERVICREDITO', 14900000, ADMIN_ID);
    esperarIgual(3, count($checklist->listarPorSolicitud($id)), 'Debía copiar el checklist igual que cualquier otra');
});

prueba('Radicar: con un vehículo que ya existe para esa venta, lo asocia', function () use ($radicar, $solicitudes): void {
    $venta    = crearVenta(2, 8000000);
    $vehiculo = crearVehiculo($venta, 2, 2, 'CHASIS-PRUEBA-' . uniqid());

    $id  = $radicar->ejecutar($venta, $vehiculo, 'SERVICREDITO', 8000000, ADMIN_ID);
    $fila = $solicitudes->buscarPorId($id);

    esperarIgual($vehiculo, (int) $fila['vehiculo_id'], 'Debía guardar el vehículo indicado');
});

// ===========================================================================
// CreditosAdjuntarSoporte / CreditosEvaluarRequisito
// (reusan la solicitud BRILLA de $vBrilla, radicada arriba)
// ===========================================================================

prueba('Evaluar: marca VALIDADO y guarda quién validó', function () use ($solicitudes, $evaluar, $checklist, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    $evaluar->ejecutar($solicitudId, REQ_CEDULA_BRILLA, 'VALIDADO', ADMIN_ID);

    $fila = $checklist->buscar($solicitudId, REQ_CEDULA_BRILLA);
    esperarIgual('VALIDADO', $fila['estado'], 'Debía quedar VALIDADO');
    esperarIgual(ADMIN_ID, (int) $fila['validado_por'], 'Debía guardar quién validó');
});

prueba('Evaluar: marca RECHAZADO', function () use ($solicitudes, $evaluar, $checklist, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    $evaluar->ejecutar($solicitudId, REQ_REFERENCIAS_BRILLA, 'RECHAZADO', ADMIN_ID);

    $fila = $checklist->buscar($solicitudId, REQ_REFERENCIAS_BRILLA);
    esperarIgual('RECHAZADO', $fila['estado'], 'Debía quedar RECHAZADO');
});

prueba('Evaluar: un estado que no es VALIDADO ni RECHAZADO se rechaza', function () use ($solicitudes, $evaluar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    esperarCodigo('ESTADO_INVALIDO', function () use ($evaluar, $solicitudId): void {
        $evaluar->ejecutar($solicitudId, REQ_GAS, 'APROBADO', ADMIN_ID);
    }, 'Evaluar con estado=APROBADO');
});

prueba('Evaluar: un requisito que no es del checklist de esa solicitud se rechaza', function () use ($solicitudes, $evaluar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id']; // es BRILLA

    esperarCodigo('REQUISITO_NO_EXISTE', function () use ($evaluar, $solicitudId): void {
        $evaluar->ejecutar($solicitudId, REQ_NOMINA_SERVICREDITO, 'VALIDADO', ADMIN_ID);
    }, 'Evaluar un requisito de ServiCrédito en una solicitud Brilla');
});

prueba('Evaluar: una solicitud inexistente se rechaza', function () use ($evaluar): void {
    esperarCodigo('SOLICITUD_NO_EXISTE', function () use ($evaluar): void {
        $evaluar->ejecutar(999999, REQ_GAS, 'VALIDADO', ADMIN_ID);
    }, 'Evaluar sobre una solicitud que no existe');
});

prueba('Adjuntar: subir un soporte nuevo reinicia el estado a PENDIENTE', function () use ($solicitudes, $adjuntar, $evaluar, $checklist, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];
    // La cédula (REQ_CEDULA_BRILLA) quedó VALIDADO en la prueba de arriba.

    $adjuntar->ejecutar($solicitudId, REQ_CEDULA_BRILLA, 'soportes/cedula-nueva.pdf');

    $fila = $checklist->buscar($solicitudId, REQ_CEDULA_BRILLA);
    esperarIgual('PENDIENTE', $fila['estado'], 'Debía volver a PENDIENTE');
    esperarIgual('soportes/cedula-nueva.pdf', $fila['soporte_adjunto'], 'Debía guardar la ruta nueva');
    esperarIgual(null, $fila['validado_por'], 'La validación anterior ya no debía aplicar');

    // La dejamos otra vez VALIDADO para los casos de uso de aprobación más abajo.
    $evaluar->ejecutar($solicitudId, REQ_CEDULA_BRILLA, 'VALIDADO', ADMIN_ID);
});

prueba('Adjuntar: un requisito fuera del checklist se rechaza', function () use ($solicitudes, $adjuntar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    esperarCodigo('REQUISITO_NO_EXISTE', function () use ($adjuntar, $solicitudId): void {
        $adjuntar->ejecutar($solicitudId, REQ_NOMINA_SERVICREDITO, 'soportes/x.pdf');
    }, 'Adjuntar un requisito que no es de esta solicitud');
});

// ===========================================================================
// CreditosAprobarCredito
// ===========================================================================

prueba('Aprobar: con el recibo de gas sin validar, lanza REQUISITO_GAS', function () use ($solicitudes, $aprobar, $vBrilla): void {
    // Cédula VALIDADO, referencias RECHAZADO, gas PENDIENTE todavía.
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    esperarCodigo('REQUISITO_GAS', function () use ($aprobar, $solicitudId): void {
        $aprobar->ejecutar($solicitudId, ADMIN_ID);
    }, 'Aprobar sin el recibo de gas validado');

    $fila = $solicitudes->buscarPorId($solicitudId);
    esperarIgual(CreditosSolicitudModelo::ESTADO_EN_PROCESO_CREDITO, $fila['estado'], 'No debía cambiar de estado al fallar');
});

prueba('Aprobar: con todos los obligatorios validados, queda APROBADA', function () use ($solicitudes, $evaluar, $aprobar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    // Faltaban: gas PENDIENTE y referencias RECHAZADO (de la prueba de Evaluar de arriba).
    $evaluar->ejecutar($solicitudId, REQ_GAS, 'VALIDADO', ADMIN_ID);
    $evaluar->ejecutar($solicitudId, REQ_REFERENCIAS_BRILLA, 'VALIDADO', ADMIN_ID);

    $fila = $aprobar->ejecutar($solicitudId, ADMIN_ID);

    esperarIgual(CreditosSolicitudModelo::ESTADO_APROBADA, $fila['estado'], 'Debía quedar APROBADA');
    esperar($fila['aprobado_por'] !== null, 'Debía guardar quién aprobó');
});

prueba('Aprobar: una solicitud que ya no está EN_PROCESO_CREDITO se rechaza', function () use ($solicitudes, $aprobar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id']; // ya quedó APROBADA arriba

    esperarCodigo('SOLICITUD_NO_EDITABLE', function () use ($aprobar, $solicitudId): void {
        $aprobar->ejecutar($solicitudId, ADMIN_ID);
    }, 'Aprobar una solicitud ya APROBADA');
});

prueba('Adjuntar: sobre una solicitud ya APROBADA se rechaza', function () use ($solicitudes, $adjuntar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    esperarCodigo('SOLICITUD_NO_EDITABLE', function () use ($adjuntar, $solicitudId): void {
        $adjuntar->ejecutar($solicitudId, REQ_GAS, 'soportes/tarde.pdf');
    }, 'Adjuntar sobre una solicitud APROBADA');
});

prueba('Evaluar: sobre una solicitud ya APROBADA se rechaza', function () use ($solicitudes, $evaluar, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id'];

    esperarCodigo('SOLICITUD_NO_EDITABLE', function () use ($evaluar, $solicitudId): void {
        $evaluar->ejecutar($solicitudId, REQ_GAS, 'VALIDADO', ADMIN_ID);
    }, 'Evaluar sobre una solicitud APROBADA');
});

prueba('Aprobar: una solicitud inexistente se rechaza', function () use ($aprobar): void {
    esperarCodigo('SOLICITUD_NO_EXISTE', function () use ($aprobar): void {
        $aprobar->ejecutar(999999, ADMIN_ID);
    }, 'Aprobar una solicitud que no existe');
});

prueba('Aprobar: ServiCrédito aprueba con los obligatorios validados (el certificado opcional queda pendiente)', function () use ($solicitudes): void {
    $venta = crearVenta(2, 8000000);
    $radicarLocal = new CreditosRadicarSolicitud($solicitudes, new CreditosSolicitudRequisitoModelo(), new CreditosRecursoModelo());
    $solicitudId  = $radicarLocal->ejecutar($venta, null, 'SERVICREDITO', 8000000, ADMIN_ID);

    $evaluarLocal = new CreditosEvaluarRequisito($solicitudes, new CreditosSolicitudRequisitoModelo());
    $evaluarLocal->ejecutar($solicitudId, REQ_CEDULA_SERVICREDITO, 'VALIDADO', ADMIN_ID);
    $evaluarLocal->ejecutar($solicitudId, REQ_NOMINA_SERVICREDITO, 'VALIDADO', ADMIN_ID);

    $aprobarLocal = new CreditosAprobarCredito($solicitudes, new CreditosSolicitudRequisitoModelo());
    $fila = $aprobarLocal->ejecutar($solicitudId, ADMIN_ID);
    esperarIgual(CreditosSolicitudModelo::ESTADO_APROBADA, $fila['estado'], 'Debía quedar APROBADA (el certificado es opcional)');
});

// ===========================================================================
// CreditosRechazarCredito / CreditosAnularSolicitud
// ===========================================================================

prueba('Rechazar: EN_PROCESO_CREDITO pasa a RECHAZADA', function () use ($radicar, $solicitudes): void {
    $venta = crearVenta(3, 9000000);
    $id = $radicar->ejecutar($venta, null, 'BRILLA', 9000000, ADMIN_ID);
    $rechazar = new CreditosRechazarCredito($solicitudes);

    $fila = $rechazar->ejecutar($id, ADMIN_ID);

    esperarIgual(CreditosSolicitudModelo::ESTADO_RECHAZADA, $fila['estado'], 'Debía quedar RECHAZADA');
});

prueba('Rechazar: una solicitud que ya no está EN_PROCESO_CREDITO se rechaza', function () use ($solicitudes, $radicar): void {
    $venta = crearVenta(3, 9000000);
    $id = $radicar->ejecutar($venta, null, 'BRILLA', 9000000, ADMIN_ID);
    $rechazar = new CreditosRechazarCredito($solicitudes);
    $rechazar->ejecutar($id, ADMIN_ID); // la deja RECHAZADA

    esperarCodigo('SOLICITUD_NO_EDITABLE', function () use ($rechazar, $id): void {
        $rechazar->ejecutar($id, ADMIN_ID);
    }, 'Rechazar una solicitud ya RECHAZADA');
});

prueba('Rechazar: una solicitud inexistente se rechaza', function () use ($solicitudes): void {
    $rechazar = new CreditosRechazarCredito($solicitudes);

    esperarCodigo('SOLICITUD_NO_EXISTE', function () use ($rechazar): void {
        $rechazar->ejecutar(999999, ADMIN_ID);
    }, 'Rechazar una solicitud que no existe');
});

prueba('Anular: EN_PROCESO_CREDITO pasa a ANULADA', function () use ($radicar, $solicitudes): void {
    $venta = crearVenta(1, 7000000);
    $id = $radicar->ejecutar($venta, null, 'SERVICREDITO', 7000000, ADMIN_ID);
    $anular = new CreditosAnularSolicitud($solicitudes);

    $fila = $anular->ejecutar($id);

    esperarIgual(CreditosSolicitudModelo::ESTADO_ANULADA, $fila['estado'], 'Debía quedar ANULADA');
});

prueba('Anular: una solicitud que ya no es editable no se puede anular', function () use ($solicitudes, $vBrilla): void {
    $solicitudId = $solicitudes->buscarPorVenta($vBrilla)['id']; // quedó APROBADA en pruebas anteriores
    $anular = new CreditosAnularSolicitud($solicitudes);

    esperarCodigo('SOLICITUD_NO_EDITABLE', function () use ($anular, $solicitudId): void {
        $anular->ejecutar($solicitudId);
    }, 'Anular una solicitud ya APROBADA');
});

prueba('Anular: una solicitud inexistente se rechaza', function () use ($solicitudes): void {
    $anular = new CreditosAnularSolicitud($solicitudes);

    esperarCodigo('SOLICITUD_NO_EXISTE', function () use ($anular): void {
        $anular->ejecutar(999999);
    }, 'Anular una solicitud que no existe');
});

// ===========================================================================
// Resumen
// ===========================================================================
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
