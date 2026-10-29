<?php
// Página principal común. La mantiene el dueño del repositorio.
class InicioControlador extends Controlador
{
    public function index(): void
    {
        $this->vista('index');
    }
}
