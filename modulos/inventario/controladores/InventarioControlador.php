<?php

class InventarioControlador extends Controlador
{
    // index.php?r=inventario/index
    public function index(): void
    {
        $this->requiereLogin();
        
        $modelo = $this->modelo('InventarioModelo');
        $tab = $_GET['tab'] ?? 'stock';

        $filtros = [];
        $filtros['q'] = isset($_GET['q']) ? trim($_GET['q']) : '';
        $filtros['marca_id'] = isset($_GET['marca_id']) ? trim($_GET['marca_id']) : '';
        $filtros['color'] = isset($_GET['color']) ? trim($_GET['color']) : '';
        $filtros['disponibilidad'] = isset($_GET['disponibilidad']) ? trim($_GET['disponibilidad']) : '';

        $marcasBD = $modelo->obtenerTodasLasMarcas();
        $todosLosProveedores = $modelo->obtenerProveedores('');

        $catalogoCompleto = $modelo->obtenerCatalogoBaseCompleto();

        $productos = [];
        $proveedoresLista = [];

        if ($tab === 'proveedores') {
            $proveedoresLista = $modelo->obtenerProveedores($filtros['q']);
        } else {
            $productos = $modelo->obtenerInventarioCompleto($filtros);
        }

        $this->vista('index', [
            'productos'        => $productos,
            'proveedores'      => $tab === 'proveedores' ? $proveedoresLista : $todosLosProveedores,
            'marcas'           => $marcasBD,
            'distribuidores'   => $todosLosProveedores,
            'catalogo_base'    => $catalogoCompleto
        ]);
    }

    /**
     * Guarda un nuevo modelo técnico en el catálogo base (producto_moto)
     */
    public function guardarmodelo(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->modelo('InventarioModelo');

            $cilindraje = isset($_POST['cilindraje']) ? intval($_POST['cilindraje']) : 0;
            
            $gama = ($cilindraje >= 150) ? 'media' : 'baja';
            if ($cilindraje >= 400) { $gama = 'alta'; }

            $datos = [
                'marca_id'        => isset($_POST['marca_id']) ? intval($_POST['marca_id']) : 0,
                'proveedor'       => isset($_POST['distribuidor_id']) ? intval($_POST['distribuidor_id']) : 3,
                'categoria_id'    => isset($_POST['electrica']) ? 1 : 2, 
                'modelo'          => isset($_POST['modelo']) ? trim($_POST['modelo']) : '',
                'precio'          => isset($_POST['precio_venta']) ? floatval($_POST['precio_venta']) : 0,
                'anio'            => isset($_POST['anio']) ? intval($_POST['anio']) : 2026,
                'cilindraje'      => $cilindraje,
                'propulsion'      => isset($_POST['electrica']) ? 'electrica' : 'gasolina',
                'gama_cilindraje' => $gama
            ];

            if ($datos['marca_id'] > 0 && !empty($datos['modelo'])) {
                $modelo->registrarVehiculo($datos);
            }

            header('Location: index.php?r=inventario/index&tab=registrar_modelo');
            exit;
        }
    }

        /**
     * Guarda el ingreso de una unidad física real al stock (unidad_moto)
     */
     public function guardaringreso(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $modelo = $this->modelo('InventarioModelo');

        // Capturamos y mapeamos los campos enviados desde el formulario
        $datos = [
            // CORREGIDO: Ahora lee 'moto_id' tal como lo definiste en el <select name="moto_id">
            'moto_id' => isset($_POST['moto_id']) ? intval($_POST['moto_id']) : 0,
            'chasis'  => isset($_POST['chasis']) ? trim(strtoupper($_POST['chasis'])) : '',
            'motor'   => isset($_POST['motor']) ? trim(strtoupper($_POST['motor'])) : '',
            'color'   => isset($_POST['color']) ? trim(strtoupper($_POST['color'])) : '',
            'estado'  => 'disponible' 
        ];

        // Validación de seguridad: procesar solo si tenemos el vehículo base, chasis y motor
        if ($datos['moto_id'] > 0 && !empty($datos['chasis']) && !empty($datos['motor'])) {
            $modelo->registrarUnidadFisica($datos);
        }

        header('Location: index.php?r=inventario/index&tab=registrar_ingreso');
        exit;
    }
}
    
        /**
     * Registra un nuevo proveedor en el sistema
     */
    public function guardarproveedor(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->modelo('InventarioModelo');

            $datos = [
                'nombre'   => isset($_POST['nombre']) ? trim($_POST['nombre']) : '',
                'nit'      => isset($_POST['nit']) ? trim($_POST['nit']) : '',
                'telefono' => isset($_POST['telefono']) ? trim($_POST['telefono']) : '',
                'correo'   => isset($_POST['correo']) ? trim($_POST['correo']) : '',
                'activo'   => 1
            ];

            if (!empty($datos['nombre']) && !empty($datos['nit'])) {
                $modelo->registrarProveedor($datos);
            }

            header('Location: index.php?r=inventario/index&tab=proveedores');
            exit;
        }
    }

        /**
     * Registra una nueva marca de motocicletas en la base de datos
     */
    public function guardarmarca(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $modelo = $this->modelo('InventarioModelo');
            
            $nombreMarca = isset($_POST['nombre_marca_nuevo']) ? trim($_POST['nombre_marca_nuevo']) : '';

            // Solo procesamos si el campo de texto no viaja vacío
            if (!empty($nombreMarca)) {
                $modelo->registrarNuevaMarca($nombreMarca);
            }

            header('Location: index.php?r=inventario/index&tab=registrar_modelo');
            exit;
        }
    }
}
