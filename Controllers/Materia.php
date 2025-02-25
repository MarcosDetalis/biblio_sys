<?php
class Materia extends Controller
{
    public function __construct()
    {
        session_start();
        if (empty($_SESSION['activo'])) {
            header("location: " . base_url);
        }
        parent::__construct();
        $id_user = $_SESSION['id_usuario'];
        $perm = $this->model->verificarPermisos($id_user, "Materia");
        if (!$perm && $id_user != 1) {
            $this->views->getView($this, "permisos");
            exit;
        }
    }
    public function index()
    {
        $this->views->getView($this, "index");
    }
   
    public function listar()
    {
        $data = $this->model->getMaterias();
        for ($i = 0; $i < count($data); $i++) {
            if ($data[$i]['materia_estado'] == 1) {
                $data[$i]['materia_estado'] = '<span class="badge badge-success">Activo</span>';
                $data[$i]['acciones'] = '<div>
                <button class="btn btn-primary" type="button" onclick="btnEditarMat(' . $data[$i]['Idmateria'] . ');"><i class="fa fa-pencil-square-o"></i></button>
                <button class="btn btn-danger" type="button" onclick="btnEliminarMat(' . $data[$i]['Idmateria'] . ');"><i class="fa fa-trash-o"></i></button>
                <div/>';
            } else {
                $data[$i]['materia_estado'] = '<span class="badge badge-danger">Inactivo</span>';
                $data[$i]['acciones'] = '<div>
                <button class="btn btn-success" type="button" onclick="btnReingresarMat(' . $data[$i]['Idmateria'] . ');"><i class="fa fa-reply-all"></i></button>
                <div/>';
            }
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function registrar()
    {
        $carrera = strClean($_POST['Tbl_carreras_idCarrera']);
        $materia = strClean($_POST['Materia_descripcion']);
        $id = strClean($_POST['Idmateria']);
        if (empty($carrera) || empty($materia)  ) {
            $msg = array('msg' => 'todos los campos son requeridos', 'icono' => 'warning');
        } else {
            if ($id == "") {
                $data = $this->model->insertarMateria($carrera,$materia);
                if ($data == "ok") {
                    $msg = array('msg' => 'Materia registrado', 'icono' => 'success');
                } else if ($data == "existe") {
                    $msg = array('msg' => 'La materia ya existe', 'icono' => 'warning');
                } else {
                    $msg = array('msg' => 'Error al registrar', 'icono' => 'error');
                }
            } else {
                $data = $this->model->actualizarMateria($carrera,$materia, $id);
                if ($data == "modificado") {
                    $msg = array('msg' => 'Materia modificado', 'icono' => 'success');
                } else {
                    $msg = array('msg' => 'Error al modificar', 'icono' => 'error');
                }
            }
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function editar($id)
    {
        $data = $this->model->editMateria($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function eliminar($id)
    {
        $data = $this->model->estadoMateria(0, $id);
        if ($data == 1) {
            $msg = array('msg' => 'Materia dado de baja', 'icono' => 'success');
        } else {
            $msg = array('msg' => 'Error al eliminar', 'icono' => 'error');
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        die();
    }
    
    public function reingresar($id)
    {
        $data = $this->model->estadoMateria(1, $id);
        if ($data == 1) {
            $msg = array('msg' => 'Materia restaurado', 'icono' => 'success');
        } else {
            $msg = array('msg' => 'Error al restaurar', 'icono' => 'error');
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function buscarMateria()
    {
        if (isset($_GET['q'])) {
            $valor = $_GET['q'];
            $data = $this->model->buscarMateria($valor);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            die();
        }
    }
}
