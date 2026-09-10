<?php include "Views/Templates/header.php"; ?>
<div class="app-title">
    <div>
        <h1><i class="fa fa-dashboard"></i> Libros</h1>
    </div>
</div>
<button class="btn btn-primary mb-2" onclick="frmLibros()"><i class="fa fa-plus"></i> Nuevo libro</button>
<div class="row">
    <div class="col-lg-12">
        <div class="tile">
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-light mt-4" id="tblLibros">
                        <thead class="thead-dark">
                            <tr>
                                <th>Id</th>
                                <th>Titulo</th>
                                <th>Cant</th>
                                <th>Disp.</th>
                                <th>Autor</th>
                                <th>Editorial</th>
                                <th>Categoría</th>
                                <th>Foto</th>
                                <th>Descripción</th>
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

<div id="nuevoLibro" class="modal fade" role="dialog" aria-labelledby="my-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="title">Registro Libro</h5>
                <button class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="frmLibro" class="row" onsubmit="registrarLibro(event)">
              
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="titulo">Título</label>
                            <input type="hidden" id="id" name="id">
                            <input id="titulo" class="form-control" type="text" name="titulo" placeholder="Título del libro" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="autor">Autor</label><br>
                            <select id="autor" class="form-control autor" name="autor" required style="width: 100%;">
                                
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="editorial">Editorial</label><br>
                            <select id="editorial" class="form-control editorial" name="editorial" required style="width: 100%;">
                                
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="categoria">Categoría</label><br>
                            <select id="categoria" class="form-control categoria" name="categoria" required style="width: 100%;">
                                
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="cantidad">Cantidad</label>
                            <input id="cantidad" class="form-control" type="text" name="cantidad" placeholder="Cantidad" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="restriccion">Restricción</label><br>
                            <select id="restriccion" class="form-control" name="restriccion" style="width: 100%;">
                            </select>
                            <small class="text-muted">Por defecto "Sin restricción". Un libro restringido permite como máximo 1 reserva pendiente por usuario.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="num_pagina">Cantidad de página</label>
                            <input id="num_pagina" class="form-control" type="number" name="num_pagina" placeholder="Cantidad Página" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="anio_edicion">Año Edición</label>
                            <input id="anio_edicion" class="form-control" type="number" name="anio_edicion" value="<?php echo date("Y"); ?>" min="1500" max="2100" required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea id="descripcion" class="form-control" name="descripcion" rows="2" placeholder="Descripción"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Logo</label>
                            <div class="card border-primary">
                                <div class="card-body">
                                <div class="lds-spinner" style="display:none;"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>

                                    <input type="hidden" id="foto_actual" name="foto_actual">
                                    <label for="imagen" id="icon-image" class="btn btn-primary"><i class="fa fa-cloud-upload"></i></label>
                                    <span id="icon-cerrar"></span>
                                    <input id="imagen" class="d-none" type="file" accept="image/png,image/jpeg" onchange="preview(event);subirPortadaCloudinary(event.target.files[0])">
                                    <img class="img-thumbnail" id="img-preview" src="" width="150">
                                    <div id="progresoPortada" class="small text-muted mt-1"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="imagenUrl" id="urlimggen">

                    <div class="col-md-12">
                        <div class="form-group">
                            <button class="btn btn-primary" type="submit" id="btnAccion">Registrar</button>
                            <button class="btn btn-danger" data-dismiss="modal" type="button">Cancelar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>   


  
  </script>
<?php include "Views/Templates/footer.php"; ?>