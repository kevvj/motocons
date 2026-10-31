<?php

class InventarioModelo extends Modelo
{
    /**
     * Consulta el stock físico unificando las tablas unidad_moto y producto_moto
     */
    
public function obtenerInventarioCompleto($filtros = [])
{
    $sql = "SELECT
                um.*,
                m.modelo,
                m.anio,
                m.cilindraje,
                ma.nombre AS nombre_marca,
                um.estado AS disponibilidad
            FROM unidad_moto um
            LEFT JOIN moto m ON um.moto_id = m.id
            LEFT JOIN marca ma ON m.marca_id = ma.marca_id
            WHERE 1=1";

    $params = [];

    // 1. Filtro por texto
    if (!empty($filtros['q'])) {
        $sql .= " AND (
                    m.modelo LIKE :q
                    OR um.chasis LIKE :q
                    OR um.motor LIKE :q
                  )";

        $params[':q'] = '%' . $filtros['q'] . '%';
    }

    // 2. Filtro por marca
    if (!empty($filtros['marca_id'])) {
        $sql .= " AND m.marca_id = :marca_id";
        $params[':marca_id'] = $filtros['marca_id'];
    }

    // 3. Filtro por color
    if (!empty($filtros['color'])) {
        $sql .= " AND um.color = :color";
        $params[':color'] = $filtros['color'];
    }

    // 4. Filtro por disponibilidad
    if (!empty($filtros['disponibilidad'])) {
        $sql .= " AND um.estado = :disponibilidad";
        $params[':disponibilidad'] = $filtros['disponibilidad'];
    }

    $sql .= " ORDER BY um.unidad_moto_id DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    /**
     * Obtiene el listado de proveedores con opción de búsqueda por nombre
     */
    public function obtenerProveedores($busqueda = '')
    {
        $sql = "SELECT * FROM proveedor WHERE 1=1";
        $params = [];

        if (!empty($busqueda)) {
            $sql .= " AND nombre LIKE :nombre";
            $params[':nombre'] = '%' . $busqueda . '%';
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * NUEVO MÉTODO: Obtiene todas las marcas del catálogo
     */
    public function obtenerTodasLasMarcas()
    {
        $sql = "SELECT * FROM marca ORDER BY marca_id ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

        /**
     * Registra un nuevo modelo base en el catálogo técnico (moto)
     */
    public function registrarVehiculo($datos)
    {
        $stmtMarca = $this->db->prepare("SELECT nombre FROM marca WHERE marca_id = :marca_id");
        $stmtMarca->execute([':marca_id' => $datos['marca_id']]);
        $nombreMarca = $stmtMarca->fetchColumn();

        if ($nombreMarca === false) {
            return false; 
        }

        // moto.propulsion es ENUM('combustion','electrica'); el controlador envía 'gasolina'
        $propulsion = ($datos['propulsion'] === 'electrica') ? 'electrica' : 'combustion';

        $sql = "INSERT INTO moto
                    (marca_id, marca, proveedor_id, categoria_id, modelo, anio, cilindraje, precio, propulsion, gama)
                VALUES
                    (:marca_id, :marca, :proveedor_id, :categoria_id, :modelo, :anio, :cilindraje, :precio, :propulsion, :gama)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':marca_id'     => $datos['marca_id'],
            ':marca'        => $nombreMarca,
            ':proveedor_id' => $datos['proveedor'],
            ':categoria_id' => $datos['categoria_id'],
            ':modelo'       => $datos['modelo'],
            ':anio'         => $datos['anio'],
            ':cilindraje'   => $datos['cilindraje'],
            ':precio'       => $datos['precio'],
            ':propulsion'   => $propulsion,
            ':gama'         => $datos['gama_cilindraje']
        ]);
    }

        /**
     * Obtiene todos los modelos puros del catálogo base sin depender del stock físico
     */
    public function obtenerCatalogoBaseCompleto()
    {
        $sql = "SELECT pm.*, m.nombre as nombre_marca 
                FROM producto_moto pm 
                LEFT JOIN marca m ON pm.marca_id = m.marca_id 
                ORDER BY pm.modelo ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

        /**
     * Registra una unidad física en el stock real (tabla unidad_moto)
     */
    public function registrarUnidadFisica($datos)
    {
        $sql = "INSERT INTO unidad_moto (moto_id, chasis, motor, color, estado) 
                VALUES (:moto_id, :chasis, :motor, :color, :estado)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':moto_id' => $datos['moto_id'],
            ':chasis'  => $datos['chasis'],
            ':motor'   => $datos['motor'],
            ':color'   => $datos['color'],
            ':estado'  => $datos['estado']
        ]);
    }

        /**
     * Inserta un nuevo proveedor en la base de datos
     */
    public function registrarProveedor($datos)
    {
        $sql = "INSERT INTO proveedor (nombre, nit, telefono, correo, activo) 
                VALUES (:nombre, :nit, :telefono, :correo, :activo)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nombre'   => $datos['nombre'],
            ':nit'      => $datos['nit'],
            ':telefono' => $datos['telefono'],
            ':correo'   => $datos['correo'],
            ':activo'   => $datos['activo']
        ]);
    }

        /**
     * Inserta una nueva marca en la tabla marca de la base de datos
     */
    public function registrarNuevaMarca($nombre)
    {
        $sql = "INSERT INTO marca (nombre) VALUES (:nombre)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':nombre' => strtoupper($nombre)]);
    }

}