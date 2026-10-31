<?php
// Controlador del módulo créditos. Cada método público es una acción: ?r=creditos/<accion>
// Carga el dominio, los modelos y los casos de uso una sola vez (el Router hace require_once
// de este archivo antes de instanciar la clase).
require_once __DIR__ . '/../dominio/CreditosDominio.php';
require_once __DIR__ . '/../modelos/CreditosRequisitoModelo.php';
require_once __DIR__ . '/../modelos/CreditosSolicitudModelo.php';
require_once __DIR__ . '/../modelos/CreditosSolicitudRequisitoModelo.php';
require_once __DIR__ . '/../modelos/CreditosRecursoModelo.php';
require_once __DIR__ . '/../aplicacion/CreditosAplicacion.php';

class CreditosControlador extends Controlador
{
    // Quién radica y gestiona soportes (el asesor que lleva la venta).
    private const ROLES_GESTION = ['admin', 'vendedor'];
    // Quién dictamina el crédito (aprueba, rechaza, evalúa el checklist).
    private const ROLES_ANALISTA = ['admin', 'analista'];
    // Quién puede ver el tablero y el detalle.
    private const ROLES_TODOS = ['admin', 'vendedor', 'analista'];

    private const SOPORTE_MAX_BYTES = 5 * 1024 * 1024; // 5 MB
    private const SOPORTE_MIME_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/png'        => 'png',
        'image/jpeg'       => 'jpg',
    ];

    // index.php?r=creditos/index&estado=APROBADA&q=perez
    public function index(): void
    {
        $this->requiereLogin(self::ROLES_TODOS);

        $solicitudModelo = $this->modelo('CreditosSolicitudModelo');
        $checklistModelo = $this->modelo('CreditosSolicitudRequisitoModelo');
        $estado   = strtoupper(trim($_GET['estado'] ?? ''));
        $busqueda = trim((string) ($_GET['q'] ?? ''));

        $solicitudes = $solicitudModelo->listar($estado !== '' ? $estado : null);

        if ($busqueda !== '') {
            $solicitudes = array_values(array_filter(
                $solicitudes,
                fn (array $s): bool => $this->coincideBusqueda($s, $busqueda)
            ));
        }

        // Avance del checklist (dominio: CreditosSolicitudCredito::porcentajeAvance()).
        // N+1 consultas, aceptable: el tablero nunca tiene más que unas pocas solicitudes abiertas.
        foreach ($solicitudes as &$s) {
            $checklist = $checklistModelo->listarPorSolicitud((int) $s['id']);
            $s['avance'] = CreditosAgregado::reconstruir($s, $checklist)->porcentajeAvance();
        }
        unset($s);

        $this->vista('index', [
            'resumen'      => $solicitudModelo->resumen(),
            'solicitudes'  => $solicitudes,
            'estadoFiltro' => $estado,
            'busqueda'     => $busqueda,
            'mensaje'      => $_GET['ok'] ?? null,
        ]);
    }

    // GET index.php?r=creditos/radicar[&venta_id=1&medio_pago=BRILLA]  |  POST para crear la solicitud
    public function radicar(): void
    {
        $this->requiereLogin(self::ROLES_GESTION);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ventaId   = (int) ($_POST['venta_id'] ?? 0);
            $medioPago = (string) ($_POST['medio_pago'] ?? '');
            try {
                $caso = new CreditosRadicarSolicitud(
                    $this->modelo('CreditosSolicitudModelo'),
                    $this->modelo('CreditosSolicitudRequisitoModelo'),
                    $this->modelo('CreditosRecursoModelo')
                );
                $id = $caso->ejecutar(
                    $ventaId,
                    isset($_POST['vehiculo_id']) && $_POST['vehiculo_id'] !== '' ? (int) $_POST['vehiculo_id'] : null,
                    $medioPago,
                    (float) ($_POST['monto_financiado'] ?? 0),
                    (int) ($this->usuario()['id'] ?? 0),
                    trim((string) ($_POST['observacion'] ?? '')) ?: null
                );
                $this->redirigir('creditos/detalle&id=' . $id . '&ok=' . rawurlencode('Solicitud radicada. Ya puedes adjuntar los soportes.'));
                return;
            } catch (Throwable $e) {
                $this->vista('radicar', $this->datosFormularioRadicar($ventaId, $medioPago, $this->mensajeError($e, 'No se pudo radicar la solicitud.')));
                return;
            }
        }

        $ventaId   = (int) ($_GET['venta_id'] ?? 0);
        $medioPago = (string) ($_GET['medio_pago'] ?? '');
        $this->vista('radicar', $this->datosFormularioRadicar($ventaId, $medioPago));
    }

    // index.php?r=creditos/detalle&id=5
    public function detalle(): void
    {
        $this->requiereLogin(self::ROLES_TODOS);

        $solicitudId = (int) ($_GET['id'] ?? 0);
        $solicitudes = $this->modelo('CreditosSolicitudModelo');
        $solicitud   = $solicitudes->buscarPorId($solicitudId);

        if ($solicitud === null) {
            http_response_code(404);
            $this->vista('detalle', [
                'solicitud' => null, 'checklist' => [], 'error' => 'No existe esa solicitud.',
                'mensaje' => null, 'puedeGestionar' => false, 'puedeDictaminar' => false, 'editable' => false,
                'avance' => 0.0,
            ]);
            return;
        }

        $rol       = (string) ($this->usuario()['rol'] ?? '');
        $checklist = $this->modelo('CreditosSolicitudRequisitoModelo')->listarPorSolicitud($solicitudId);

        $this->vista('detalle', [
            'solicitud'       => $solicitud,
            'checklist'       => $checklist,
            'avance'          => CreditosAgregado::reconstruir($solicitud, $checklist)->porcentajeAvance(),
            'puedeGestionar'  => in_array($rol, self::ROLES_GESTION, true),
            'puedeDictaminar' => in_array($rol, self::ROLES_ANALISTA, true),
            'editable'        => CreditosSolicitudModelo::esEditable($solicitud['estado']),
            'mensaje'         => $_GET['ok'] ?? null,
            'error'           => $_GET['error'] ?? null,
        ]);
    }

    // POST index.php?r=creditos/adjuntar  (multipart/form-data: solicitud_id, requisito_id, soporte)
    public function adjuntar(): void
    {
        $this->requiereLogin(self::ROLES_GESTION);
        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('creditos/detalle&id=' . $solicitudId);
            return;
        }

        try {
            $ruta = $this->guardarSoporte($_FILES['soporte'] ?? null);
            $caso = new CreditosAdjuntarSoporte($this->modelo('CreditosSolicitudModelo'), $this->modelo('CreditosSolicitudRequisitoModelo'));
            $caso->ejecutar($solicitudId, (int) ($_POST['requisito_id'] ?? 0), $ruta);
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&ok=' . rawurlencode('Soporte guardado.'));
        } catch (Throwable $e) {
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&error=' . rawurlencode($this->mensajeError($e, 'No se pudo guardar el soporte.')));
        }
    }

    // POST index.php?r=creditos/evaluar  (solicitud_id, requisito_id, estado=VALIDADO|RECHAZADO)
    public function evaluar(): void
    {
        $this->requiereLogin(self::ROLES_ANALISTA);
        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('creditos/detalle&id=' . $solicitudId);
            return;
        }

        try {
            $caso = new CreditosEvaluarRequisito($this->modelo('CreditosSolicitudModelo'), $this->modelo('CreditosSolicitudRequisitoModelo'));
            $caso->ejecutar($solicitudId, (int) ($_POST['requisito_id'] ?? 0), (string) ($_POST['estado'] ?? ''), (int) ($this->usuario()['id'] ?? 0));
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&ok=' . rawurlencode('Requisito actualizado.'));
        } catch (Throwable $e) {
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&error=' . rawurlencode($this->mensajeError($e, 'No se pudo evaluar el requisito.')));
        }
    }

    // POST index.php?r=creditos/aprobar  (solicitud_id)
    public function aprobar(): void
    {
        $this->requiereLogin(self::ROLES_ANALISTA);
        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('creditos/detalle&id=' . $solicitudId);
            return;
        }

        try {
            $caso = new CreditosAprobarCredito($this->modelo('CreditosSolicitudModelo'), $this->modelo('CreditosSolicitudRequisitoModelo'));
            $caso->ejecutar($solicitudId, (int) ($this->usuario()['id'] ?? 0));
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&ok=' . rawurlencode('Crédito aprobado.'));
        } catch (Throwable $e) {
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&error=' . rawurlencode($this->mensajeError($e, 'No se pudo aprobar el crédito.')));
        }
    }

    // POST index.php?r=creditos/rechazar  (solicitud_id)
    public function rechazar(): void
    {
        $this->requiereLogin(self::ROLES_ANALISTA);
        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('creditos/detalle&id=' . $solicitudId);
            return;
        }

        try {
            $caso = new CreditosRechazarCredito($this->modelo('CreditosSolicitudModelo'));
            $caso->ejecutar($solicitudId, (int) ($this->usuario()['id'] ?? 0));
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&ok=' . rawurlencode('Crédito rechazado.'));
        } catch (Throwable $e) {
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&error=' . rawurlencode($this->mensajeError($e, 'No se pudo rechazar el crédito.')));
        }
    }

    // POST index.php?r=creditos/anular  (solicitud_id)
    public function anular(): void
    {
        $this->requiereLogin(self::ROLES_GESTION);
        $solicitudId = (int) ($_POST['solicitud_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirigir('creditos/detalle&id=' . $solicitudId);
            return;
        }

        try {
            $caso = new CreditosAnularSolicitud($this->modelo('CreditosSolicitudModelo'));
            $caso->ejecutar($solicitudId);
            $this->redirigir('creditos/index&ok=' . rawurlencode('Solicitud anulada.'));
        } catch (Throwable $e) {
            $this->redirigir('creditos/detalle&id=' . $solicitudId . '&error=' . rawurlencode($this->mensajeError($e, 'No se pudo anular la solicitud.')));
        }
    }

    // index.php?r=creditos/soporte&solicitud=5&requisito=1 — sirve el archivo adjunto.
    // No vive en public/: solo se entrega con sesión, nunca por URL directa.
    public function soporte(): void
    {
        $this->requiereLogin(self::ROLES_TODOS);

        $solicitudId = (int) ($_GET['solicitud'] ?? 0);
        $requisitoId = (int) ($_GET['requisito'] ?? 0);

        $fila = $this->modelo('CreditosSolicitudRequisitoModelo')->buscar($solicitudId, $requisitoId);
        if ($fila === null || empty($fila['soporte_adjunto'])) {
            http_response_code(404);
            exit('No hay soporte adjunto para ese requisito.');
        }

        $ruta = $this->carpetaSoportes() . '/' . basename($fila['soporte_adjunto']);
        if (! is_file($ruta)) {
            http_response_code(404);
            exit('El archivo ya no está disponible en el servidor.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        header('Content-Type: ' . $finfo->file($ruta));
        header('Content-Disposition: inline; filename="soporte-' . $solicitudId . '-' . $requisitoId . '"');
        readfile($ruta);
    }

    // Coincide si $busqueda aparece en el cliente, la factura, la placa o el chasis.
    // Sin mbstring (el CI solo instala pdo_mysql): comparación simple por bytes, suficiente
    // para un filtro de UI, no para búsqueda con acentos perfecta.
    private function coincideBusqueda(array $solicitud, string $busqueda): bool
    {
        foreach ([$solicitud['cliente'] ?? '', $solicitud['numero_factura'] ?? '', $solicitud['placa'] ?? '', $solicitud['chasis'] ?? ''] as $campo) {
            if ($campo !== null && $campo !== '' && stripos((string) $campo, $busqueda) !== false) {
                return true;
            }
        }

        return false;
    }

    // Datos que necesita el formulario de radicación (y su reintento si falla).
    // El checklist que se previsualiza viene del mismo catálogo que copiará CreditosRadicarSolicitud
    // (CreditosListarRequisitos), para que lo que se muestra sea exactamente lo que se va a crear.
    private function datosFormularioRadicar(int $ventaId, string $medioPago = '', ?string $error = null): array
    {
        $recursos   = $this->modelo('CreditosRecursoModelo');
        $requisitos = $this->modelo('CreditosRequisitoModelo');
        $medioPago  = strtoupper(trim($medioPago));

        return [
            'ventas'           => $recursos->ventasSinSolicitud(),
            'ventaId'          => $ventaId,
            'vehiculos'        => $ventaId > 0 ? $recursos->vehiculosDeVenta($ventaId) : [],
            'venta'            => $ventaId > 0 ? $recursos->buscarVenta($ventaId) : null,
            'mediosDePago'     => $requisitos->mediosDePagoDisponibles(),
            'medioPago'        => $medioPago,
            'checklistPreview' => $medioPago !== '' ? (new CreditosListarRequisitos($requisitos))->ejecutar($medioPago) : [],
            'error'            => $error,
        ];
    }

    // Valida el archivo subido y lo guarda con nombre aleatorio. Devuelve el nombre guardado
    // (no la ruta completa: eso se resuelve siempre dentro de carpetaSoportes()).
    private function guardarSoporte(?array $archivo): string
    {
        if ($archivo === null || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new CreditosExcepcion('Selecciona un archivo para adjuntar.', 'SOPORTE_VACIO');
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            throw new CreditosExcepcion('No se pudo subir el archivo (código ' . $archivo['error'] . ').', 'SOPORTE_ERROR_SUBIDA');
        }
        if ($archivo['size'] > self::SOPORTE_MAX_BYTES) {
            throw new CreditosExcepcion('El archivo pesa más de 5 MB.', 'SOPORTE_MUY_GRANDE');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($archivo['tmp_name']);
        $extension = self::SOPORTE_MIME_PERMITIDOS[$mime] ?? null;
        if ($extension === null) {
            throw new CreditosExcepcion('Solo se aceptan archivos PDF, PNG o JPG.', 'SOPORTE_TIPO_INVALIDO');
        }

        $carpeta = $this->carpetaSoportes();
        if (! is_dir($carpeta) && ! mkdir($carpeta, 0775, true) && ! is_dir($carpeta)) {
            throw new CreditosExcepcion('No se pudo preparar el almacenamiento de soportes.', 'SOPORTE_SIN_CARPETA');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . $extension;
        if (! move_uploaded_file($archivo['tmp_name'], $carpeta . '/' . $nombre)) {
            throw new CreditosExcepcion('No se pudo guardar el archivo en el servidor.', 'SOPORTE_NO_GUARDADO');
        }

        return $nombre;
    }

    // Fuera de public/: los soportes de crédito no son navegables por URL directa.
    private function carpetaSoportes(): string
    {
        return __DIR__ . '/../almacenamiento/soportes';
    }

    // Mensaje para mostrarle al usuario: el texto de negocio si es nuestra excepción,
    // uno genérico si es un error de infraestructura (no se expone detalle técnico).
    private function mensajeError(Throwable $e, string $generico): string
    {
        return $e instanceof CreditosExcepcion ? $e->getMessage() : $generico;
    }
}
