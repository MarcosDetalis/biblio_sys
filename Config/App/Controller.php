<?php
class Controller{
    protected $views, $model;
    public function __construct()
    {
        $this->views = new Views();
        $this->cargarModel();
    }
    public function cargarModel()
    {
        $model = get_class($this)."Model";
        $ruta = "Models/".$model.".php";
        if (file_exists($ruta)) {
            require_once $ruta;
            $this->model = new $model();
        }
    }
    /**
     * Administrador y Bibliotecario ven/gestionan todo el sistema; cualquier
     * otro rol (Alumno, Profesor) solo debe ver y operar sobre lo propio.
     * Se usa en los listados y en las acciones que reciben un id por URL
     * (para no confiar solo en "no se muestra el botón" del lado del
     * navegador, que un usuario podría saltarse llamando la ruta directo).
     */
    protected function esStaff(): bool
    {
        return in_array($_SESSION['rol'] ?? '', ['Administrador','Bibliotecario'], true);
    }
}

?>