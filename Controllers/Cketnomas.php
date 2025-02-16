<?php
class Cketnomas extends Controller{
    public function __construct() {
        session_start();
        parent::__construct();
    }
    public function index()
    {
        if (empty($_SESSION['activo'])) {
            header("location: " . base_url);
        }
        
        
        $this->views->getView($this, "index");
    }
    
    public function salir()
    {
        session_destroy();
        header("location: ".base_url);
    }




    public function registrarcKETNOMA()
    {
        $id_user = strClean($_POST['id_mov_cab']);
        $permisos = $_POST['id_mov_det'];
       
        if ($permisos != "") {
            foreach ($permisos as $permiso) {
            $this->model->preUPDATE($id_user);
            $data= $this->model->actualizarchet($id_user, $permiso);
               
            }
        }else{
            $data= $this->model->Cancelar_todo($id_user);  
        }
        echo json_encode( $data );
        die();
    }


}
