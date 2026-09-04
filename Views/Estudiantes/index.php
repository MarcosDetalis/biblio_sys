<?php include "Views/Templates/header.php"; ?>
<div class="app-title"><div><h1><i class="fa fa-graduation-cap"></i> Estudiantes</h1></div></div>
<button class="btn btn-primary mb-2" type="button" onclick="frmEstudiante()"><i class="fa fa-plus"></i> Nuevo estudiante</button>
<div class="row"><div class="col-lg-12"><div class="tile"><div class="tile-body"><div class="table-responsive">
<table class="table table-bordered table-hover" id="tblEst"><thead class="thead-dark"><tr><th>Id</th><th>Nombre</th><th>Tipo</th><th>Carrera</th><th>Correo</th><th>Cédula</th><th>Teléfono</th><th>Estado</th><th></th></tr></thead><tbody></tbody></table>
</div></div></div></div></div>
<div id="nuevoEstudiante" class="modal fade" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5 class="modal-title" id="title">Registro Estudiante</h5><button class="close" data-dismiss="modal"><span>&times;</span></button></div><div class="modal-body">
<form id="frmEstudiante" onsubmit="registrarEstudiante(event)"><input type="hidden" id="Idusuario" name="Idusuario">
<div class="row">
<div class="col-md-6"><label>Nombres *</label><input id="nombres" class="form-control" name="nombres" required></div>
<div class="col-md-6"><label>Apellidos *</label><input id="apellidos" class="form-control" name="apellidos" required></div>
<div class="col-md-6 mt-2"><label>Cédula *</label><input id="cedula" class="form-control" name="cedula" required></div>
<div class="col-md-6 mt-2"><label>Teléfono *</label><input id="telefono" class="form-control" name="telefono" required></div>
<div class="col-md-6 mt-2"><label>Correo *</label><input id="correo" type="email" class="form-control" name="correo" required></div>
<div class="col-md-6 mt-2"><label>Carrera *</label><select id="id_carrera" class="form-control" name="id_carrera" required></select></div>
</div>
<small class="text-muted d-block mt-2">La contraseña inicial para nuevos estudiantes es <strong>123456</strong>. Se recomienda cambiarla después del primer acceso.</small>
<div class="mt-3"><button class="btn btn-primary" type="submit" id="btnAccion">Registrar</button> <button class="btn btn-danger" type="button" data-dismiss="modal">Cancelar</button></div>
</form></div></div></div></div>
<?php include "Views/Templates/footer.php"; ?>
