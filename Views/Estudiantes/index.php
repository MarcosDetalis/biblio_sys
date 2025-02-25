<?php include "Views/Templates/header.php"; ?>
<div class="app-title">
    <div>
        <h1><i class="fa fa-dashboard"></i> Estudiantes</h1>
    </div>
</div>
<button class="btn btn-primary mb-2" type="button" onclick="frmEstudiante()"><i class="fa fa-plus"></i></button>
<div class="row">
    <div class="col-lg-12">
        <div class="tile">
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-light mt-4" id="tblEst">
                        <thead class="thead-dark">
                            <tr>
                            <th>Id</th>
                                <th>Nombre</th>
                                <th>Tipo Usuario</th>
                                <th>Carrera</th>
                                <th>Correo</th>
                                <th>Usuario nombre</th>
                                <th>Telefono</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="nuevoEstudiante" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white" id="title">Registro Estudiante</h5>
                <button class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="frmEstudiante">
                    <div class="row">
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="hidden" id="Idusuario" name="Idusuario">
                                <input id="tipousu" class="form-control" type="hidden" name="Tbl_tipo_usuarios_idTipo_usuario" value="2">
                                <input id="nombre" class="form-control" type="text" name="Usuario_nombre1" required placeholder="Ingrese nombre alumno">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="carrera">Carrera</label>
                                <input id="carrera" class="form-control" type="number" name="Tbl_carreras_Idcarrera" required placeholder="Carrera">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="correo">Correo</label>
                                <input id="correo" class="form-control" type="text" name="Usuario_correo" required placeholder="Correo">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="usunombre">Usuario nombre</label>
                                <input id="usunombre" class="form-control" type="text" name="Usuario_nombre_usuario" required placeholder="Usuario nombre">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="telefono">Telefono</label>
                                <input id="telefono" class="form-control" type="text" name="Usuario_ci" required placeholder="Telefono">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <button class="btn btn-primary" type="submit" onclick="registrarEstudiante(event)" id="btnAccion">Registrar</button>
                                <button class="btn btn-danger" type="button" data-dismiss="modal">Atras</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include "Views/Templates/footer.php"; ?>