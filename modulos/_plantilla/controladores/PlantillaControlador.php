<?php
// Copia la carpeta _plantilla como modulos/<tu_modulo>/ y renombra:
//   PlantillaControlador -> <TuModulo>Controlador (igual que el nombre del archivo)
// URL: index.php?r=<tu_modulo>/index
class PlantillaControlador extends Controlador
{
    public function index(): void
    {
        $modelo = $this->modelo('PlantillaEjemploModelo');
        $this->vista('index', ['registros' => $modelo->todos()]);
    }
}
