<?php
class Autor extends Controller
{
    public function __construct()
    {
        session_start();
        if (empty($_SESSION['activo'])) {
            header("location: " . base_url);
        }
        parent::__construct();

        // actualmente comento porque es que pide permisos
        // $id_user = $_SESSION['id_usuario'];
        // $perm = $this->model->verificarPermisos($id_user, "Autor");
        // if (!$perm && $id_user != 1) {
        //     $this->views->getView($this, "permisos");
        //     exit;
        // }
    }
    public function index()
    {
        $this->views->getView($this, "index");
    }
    public function listar()
    {
        $data = $this->model->getAutor();
        for ($i = 0; $i < count($data); $i++) {
            // $data[$i]['imagen'] = '<img class="img-thumbnail" src="' . base_url . "Assets/img/autor/" . $data[$i]['imagen'] . '" width="80">';
            if ($data[$i]['Autor_estado'] == 1) {
                $data[$i]['Autor_estado'] = '<span class="badge badge-success">Activo</span>';
                $data[$i]['acciones'] = '<div>
                <button class="btn btn-primary" type="button" onclick="btnEditarAutor(' . $data[$i]['Idautor'] . ');"><i class="fa fa-pencil-square-o"></i></button>
                <button class="btn btn-danger" type="button" onclick="btnEliminarAutor(' . $data[$i]['Idautor'] . ');"><i class="fa fa-trash-o"></i></button>
                <div/>';
            } else {
                $data[$i]['Autor_estado'] = '<span class="badge badge-danger">Inactivo</span>';
                $data[$i]['acciones'] = '<div>
                <button class="btn btn-success" type="button" onclick="btnReingresarAutor(' . $data[$i]['Idautor'] . ');"><i class="fa fa-reply-all"></i></button>
                <div/>';
            }
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function registrar()
    {
        $nombre = strClean($_POST['Autor_nombres']);
        $pais=strClean($_POST['Autor_pais']);
        $id = strClean($_POST['Idautor']);
        if (empty($nombre) || empty($pais)) {
            $msg = array('msg' => 'Todo los campos son requeridos', 'icono' => 'warning');
        } else {
            if ($id == "") {
                    $data = $this->model->insertarAutor($nombre, $pais);
                    if ($data == "ok") {
                        $msg = array('msg' => 'Estudiante registrado', 'icono' => 'success');
                    } else if ($data == "existe") {
                        $msg = array('msg' => 'El estudiante ya existe', 'icono' => 'warning');
                    } else {
                        $msg = array('msg' => 'Error al registrar', 'icono' => 'error');
                    }
            } else {
                $data = $this->model->actualizarAutor($nombre, $pais, $id);
                if ($data == "modificado") {
                    $msg = array('msg' => 'Nombre modificado', 'icono' => 'success');
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
        $data = $this->model->editAutor($id);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function eliminar($id)
    {
        $data = $this->model->estadoAutor(0, $id);
        if ($data == 1) {
            $msg = array('msg' => 'Autor dado de baja', 'icono' => 'success');
        } else {
            $msg = array('msg' => 'Error al eliminar', 'icono' => 'error');
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function reingresar($id)
    {
        $data = $this->model->estadoAutor(1, $id);
        if ($data == 1) {
            $msg = array('msg' => 'Autor restaurado', 'icono' => 'success');
        } else {
            $msg = array('msg' => 'Error al restaurar', 'icono' => 'error');
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
        die();
    }
    public function buscarAutor()
    {
        if (isset($_GET['q'])) {
            $valor = $_GET['q'];
            $data = $this->model->buscarAutor($valor);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            die();
        }
    }
}
