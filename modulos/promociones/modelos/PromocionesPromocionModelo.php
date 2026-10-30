<?php
require_once __DIR__ . '/PromocionesDescuento.php';

// CRUD de promociones y su alcance (promocion_aplica).
class PromocionesPromocionModelo extends Modelo
{
    public function listar(?string $estadoVigencia = null, string $busqueda = ''): array
    {
        $sql = 'SELECT p.*,
                       (SELECT GROUP_CONCAT(CONCAT(a.criterio, \':\', a.valor)
                                            ORDER BY a.id SEPARATOR \'|\')
                          FROM promocion_aplica a
                         WHERE a.promocion_id = p.id) AS aplica_texto
                  FROM promocion p';
        $params = [];

        if ($busqueda !== '') {
            $sql .= ' WHERE (p.nombre LIKE ? OR p.descripcion LIKE ?)';
            $params[] = '%' . $busqueda . '%';
            $params[] = '%' . $busqueda . '%';
        }

        $sql .= ' ORDER BY p.fecha_inicio DESC, p.id DESC';

        $promociones = [];
        foreach ($this->consultar($sql, $params) as $fila) {
            $promocion = $this->formatear($fila);
            if ($estadoVigencia !== null
                && $promocion['estado_vigencia'] !== strtoupper($estadoVigencia)) {
                continue;
            }
            $promociones[] = $promocion;
        }

        return $promociones;
    }

    public function obtener(int $id): ?array
    {
        $filas = $this->consultar(
            'SELECT p.*,
                    (SELECT GROUP_CONCAT(CONCAT(a.criterio, \':\', a.valor)
                                         ORDER BY a.id SEPARATOR \'|\')
                       FROM promocion_aplica a
                      WHERE a.promocion_id = p.id) AS aplica_texto
               FROM promocion p
              WHERE p.id = ?',
            [$id]
        );

        return $filas ? $this->formatear($filas[0]) : null;
    }

    public function vigentes(?string $fecha = null): array
    {
        $fecha = $fecha ?: date('Y-m-d');
        $filas = $this->consultar(
            'SELECT p.*,
                    (SELECT GROUP_CONCAT(CONCAT(a.criterio, \':\', a.valor)
                                         ORDER BY a.id SEPARATOR \'|\')
                       FROM promocion_aplica a
                      WHERE a.promocion_id = p.id) AS aplica_texto
               FROM promocion p
              WHERE p.estado = \'activa\'
                AND p.fecha_inicio <= ?
                AND p.fecha_fin >= ?
              ORDER BY p.id DESC',
            [$fecha, $fecha]
        );

        return array_map(fn($fila) => $this->formatear($fila), $filas);
    }

    public function crear(array $datos, array $aplicaciones = []): int
    {
        $this->db->beginTransaction();

        try {
            $this->ejecutar(
                'INSERT INTO promocion
                    (nombre, descripcion, tipo_descuento, valor,
                     fecha_inicio, fecha_fin, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $datos['nombre'],
                    $datos['descripcion'],
                    $datos['tipo_descuento'],
                    $datos['valor'],
                    $datos['fecha_inicio'],
                    $datos['fecha_fin'],
                    $datos['estado'],
                ]
            );

            $id = (int) $this->db->lastInsertId();
            $this->insertarAplicaciones($id, $aplicaciones);
            $this->db->commit();

            return $id;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function actualizar(int $id, array $datos, array $aplicaciones = []): bool
    {
        $this->db->beginTransaction();

        try {
            $this->ejecutar(
                'UPDATE promocion
                    SET nombre = ?, descripcion = ?, tipo_descuento = ?,
                        valor = ?, fecha_inicio = ?, fecha_fin = ?, estado = ?
                  WHERE id = ?',
                [
                    $datos['nombre'],
                    $datos['descripcion'],
                    $datos['tipo_descuento'],
                    $datos['valor'],
                    $datos['fecha_inicio'],
                    $datos['fecha_fin'],
                    $datos['estado'],
                    $id,
                ]
            );

            $this->ejecutar('DELETE FROM promocion_aplica WHERE promocion_id = ?', [$id]);
            $this->insertarAplicaciones($id, $aplicaciones);
            $this->db->commit();

            return true;
        } catch (Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }

    public function eliminar(int $id): bool
    {
        return $this->ejecutar('DELETE FROM promocion WHERE id = ?', [$id]) > 0;
    }

    public function cambiarEstado(int $id, string $estado): bool
    {
        return $this->ejecutar(
            'UPDATE promocion SET estado = ? WHERE id = ?',
            [$estado, $id]
        ) > 0;
    }

    public function estadisticas(): array
    {
        $conteo = [
            'TOTAL' => 0,
            'VIGENTE' => 0,
            'PROGRAMADA' => 0,
            'VENCIDA' => 0,
            'INACTIVA' => 0,
        ];

        foreach ($this->listar() as $promocion) {
            $conteo['TOTAL']++;
            $conteo[$promocion['estado_vigencia']]++;
        }

        return $conteo;
    }

    public function opcionesAlcance(): array
    {
        $marcas = array_column(
            $this->consultar('SELECT DISTINCT marca FROM moto ORDER BY marca'),
            'marca'
        );
        $gamas = array_column(
            $this->consultar('SELECT DISTINCT gama FROM moto ORDER BY gama'),
            'gama'
        );

        $motocicletas = [];
        foreach ($this->consultar('SELECT id, marca, modelo FROM moto ORDER BY marca, modelo') as $fila) {
            $motocicletas[] = [
                'id'     => (int) $fila['id'],
                'nombre' => $fila['marca'] . ' ' . $fila['modelo'],
            ];
        }

        return [
            'marcas'       => $marcas,
            'gamas'        => $gamas,
            'motocicletas' => $motocicletas,
        ];
    }

    private function insertarAplicaciones(int $promocionId, array $aplicaciones): void
    {
        foreach ($aplicaciones as $aplicacion) {
            $this->ejecutar(
                'INSERT INTO promocion_aplica (promocion_id, criterio, valor) VALUES (?, ?, ?)',
                [$promocionId, $aplicacion['criterio'], (string) $aplicacion['valor']]
            );
        }
    }

    private function formatear(array $fila): array
    {
        $promocion = [
            'id'             => (int) $fila['id'],
            'nombre'         => $fila['nombre'],
            'descripcion'    => (string) ($fila['descripcion'] ?? ''),
            'tipo_descuento' => $fila['tipo_descuento'],
            'valor'          => (float) $fila['valor'],
            'fecha_inicio'   => $fila['fecha_inicio'],
            'fecha_fin'      => $fila['fecha_fin'],
            'estado'         => $fila['estado'],
            'aplica'         => $this->parsearAplicaciones($fila['aplica_texto'] ?? ''),
        ];

        $promocion['estado_vigencia'] = PromocionesDescuento::estadoVigencia($promocion);

        return $promocion;
    }

    private function parsearAplicaciones(?string $texto): array
    {
        $aplicaciones = [];

        if ($texto === null || $texto === '') {
            return $aplicaciones;
        }

        foreach (explode('|', $texto) as $parte) {
            $separador = strpos($parte, ':');
            if ($separador === false) {
                continue;
            }
            $aplicaciones[] = [
                'criterio' => substr($parte, 0, $separador),
                'valor'    => substr($parte, $separador + 1),
            ];
        }

        return $aplicaciones;
    }
}
