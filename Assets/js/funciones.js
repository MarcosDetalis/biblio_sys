let tblUsuarios, tblEst, tblMateria, tblCategoria, tblAutor, tblEditorial, tblPrestar;


document.addEventListener("DOMContentLoaded", function() {
    document.querySelector("#modalPass").addEventListener("click", function() {
        document.querySelector('#frmCambiarPass').reset();
        $('#cambiarClave').modal('show');
    });
    const language = {
        "decimal": "",
        "emptyTable": "No hay información",
        "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
        "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
        "infoFiltered": "(Filtrado de _MAX_ total entradas)",
        "infoPostFix": "",
        "thousands": ",",
        "lengthMenu": "Mostrar _MENU_ Entradas",
        "loadingRecords": "Cargando...",
        "processing": "Procesando...",
        "search": "Buscar:",
        "zeroRecords": "Sin resultados encontrados",
        "The results could not be loaded.": "te resultasos es ",
        "paginate": {
            "first": "Primero",
            "last": "Ultimo",
            "next": "Siguiente",
            "previous": "Anterior"
        }

    }
    const buttons = [{
            //Botón para Excel
            extend: 'excel',
            footer: true,
            title: 'Archivo',
            filename: 'Export_File',

            //Aquí es donde generas el botón personalizado
            text: '<button class="btn btn-success"><i class="fa fa-file-excel-o"></i></button>'
        },
        //Botón para PDF
        {
            extend: 'pdf',
            footer: true,
            title: 'Archivo PDF',
            filename: 'reporte',
            text: '<button class="btn btn-danger"><i class="fa fa-file-pdf-o"></i></button>'
        },
        //Botón para print
        {
            extend: 'print',
            footer: true,
            title: 'Reportes',
            filename: 'Export_File_print',
            text: '<button class="btn btn-info"><i class="fa fa-print"></i></button>'
        }
    ]

    tblUsuarios = $('#tblUsuarios').DataTable({ajax:{url:base_url+'Usuarios/listar',dataSrc:''},columns:[{data:'id'},{data:'cedula'},{data:'nombre'},{data:'correo'},{data:'tipo_usuario'},{data:'carrera'},{data:'rol'},{data:'estado'},{data:'acciones'}],responsive:true,bDestroy:true,iDisplayLength:10,order:[[0,'desc']],language,dom:"<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",buttons
    });
    //Fin de la tabla usuarios
   
    tblEst = $('#tblEst').DataTable({ajax:{url:base_url+'Estudiantes/listar',dataSrc:''},columns:[{data:'Idusuario'},{data:'Usuario_nombre1'},{data:'tipo_usuario'},{data:'carrera'},{data:'Usuario_correo'},{data:'Usuario_ci'},{data:'Usuario_telefono'},{data:'Usuario_estado'},{data:'acciones'}],language,responsive:true,bDestroy:true,dom:"<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",buttons
    });
    //Fin de la tabla Estudiantes
  
    tblMateria = $('#tblMateria').DataTable({ajax:{url:base_url+'Materia/listar',dataSrc:''},columns:[{data:'Idmateria'},{data:'Materia_descripcion'},{data:'materia_estado'},{data:'acciones'}],language,responsive:true,bDestroy:true,dom:"<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",buttons
    });
    //Fin de la tabla Materias
    tblCategoria = $('#tblCategoria').DataTable({ajax:{url:base_url+'Categoria/listar',dataSrc:''},columns:[{data:'Idcategoria'},{data:'Categoria_descripcion'},{data:'categoria_estado'},{data:'acciones'}],language,responsive:true,bDestroy:true,dom:"<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",buttons
    });
    //Fin de la tabla Categorías
    tblAutor = $('#tblAutor').DataTable({ajax:{url:base_url+'Autor/listar',dataSrc:''},columns:[{data:'Idautor'},{data:'Autor_nombres'},{data:'Autor_apellidos'},{data:'Autor_pais'},{data:'Autor_estado'},{data:'acciones'}],language,responsive:true,bDestroy:true,dom:"<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",buttons
    });
    //Fin de la tabla Autor

    tblEditorial = $('#tblEditorial').DataTable({
        ajax: {
            url: base_url + "Editorial/listar",
            dataSrc: ''
        },
        columns: [{
                'data': 'Ideditorial'
            },
            {
                'data': 'Editorial_descripcion'
            },
            {
                'data': 'edi_estado'
            },
            {
                'data': 'acciones'
            }
        ],
        language,
        dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons
    }); 
    //Fin de la tabla editorial
 
    //fin Libros
    tblPrestar = $('#tblPrestar').DataTable({ajax:{url:base_url+'Prestamos/listar',dataSrc:''},columns:[{data:'Idreserva_cab'},{data:'Usuario_nombre1'},{data:'Usuario_correo'},{data:'Usuario_ci'},{data:'Reserva_cab_fecha_solicitud'},{data:'fecha_devuelto'},{data:'Estado_solicitud_descripcion'},{data:'acciones'}],language,responsive:true,bDestroy:true,iDisplayLength:10,order:[[0,'desc']],dom:"<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>><'row'<'col-sm-12'tr>><'row'<'col-sm-5'i><'col-sm-7'p>>",buttons
    });
    // ============================================================
    // SELECT2 REMOTOS
    // Cada resultado debe devolver {id,text}. dropdownParent mantiene
    // el menú dentro del modal Bootstrap para que los clics funcionen.
    // ============================================================
    function initRemoteSelect2(selector,url,placeholder,paramName){
        $(selector).each(function(){
            const $select=$(this);
            if($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
            const $modal=$select.closest('.modal');
            $select.select2({
                placeholder:placeholder,allowClear:true,minimumInputLength:0,width:'100%',
                dropdownParent:$modal.length?$modal:$(document.body),
                ajax:{url:base_url+url,dataType:'json',delay:250,data:function(params){const x={};x[paramName]=params.term||'';return x;},processResults:function(data){
                    // Compatibilidad con respuestas antiguas y nuevas: Select2 siempre recibe {id,text}.
                    const rows=Array.isArray(data)?data:((data&&Array.isArray(data.results))?data.results:[]);
                    return {results:rows.map(function(row){return {id:row.id ?? row.Idusuario ?? row.Idlibro ?? row.Idcategoria ?? row.Idautor ?? row.Ideditorial,text:row.text ?? row.Usuario_nombre1 ?? row.nombre ?? row.titulo ?? row.Categoria_descripcion ?? row.Autor_nombres ?? row.Editorial_descripcion};})};
                },cache:false},
                language:{noResults:function(){return 'No hay resultado';},searching:function(){return 'Buscando..';},errorLoading:function(){return 'No se pudo cargar la información';}}
            });
        });
    }
    initRemoteSelect2('.estudiante','Estudiantes/buscarEstudiante','Buscar estudiante','est');
    initRemoteSelect2('.libro','Libros/buscarLibro','Buscar libro','lb');
    initRemoteSelect2('.autor','Autor/buscarAutor','Buscar autor','q');
    initRemoteSelect2('.editorial','Editorial/buscarEditorial','Buscar editorial','q');
    initRemoteSelect2('.categoria','Categoria/buscarCategoria','Buscar categoría','q');
    // Re-inicializar al abrir cada modal evita problemas de foco/DOM de Bootstrap.
    $(document).on('shown.bs.modal', '.modal', function(){
        const $m=$(this);
        $m.find('.estudiante').each(function(){initRemoteSelect2(this,'Estudiantes/buscarEstudiante','Buscar estudiante','est');});
        $m.find('.libro').each(function(){initRemoteSelect2(this,'Libros/buscarLibro','Buscar libro','lb');});
        $m.find('.autor').each(function(){initRemoteSelect2(this,'Autor/buscarAutor','Buscar autor','q');});
        $m.find('.editorial').each(function(){initRemoteSelect2(this,'Editorial/buscarEditorial','Buscar editorial','q');});
        $m.find('.categoria').each(function(){initRemoteSelect2(this,'Categoria/buscarCategoria','Buscar categoría','q');});
    });

    // Campanita de notificaciones: para Alumno/Profesor muestra solo lo suyo
    // (reserva lista para retirar, por vencer, devolución pendiente o vencida);
    // para Administrador/Bibliotecario muestra un resumen agregado de todo
    // el sistema, con acceso al reporte completo en PDF.
    cargarNotificaciones();
});

function iconoNotificacion(tipo){
    const iconos={
        retiro_pendiente:'fa-calendar-check-o text-secondary',
        retiro_por_vencer:'fa-clock-o text-warning',
        devolucion_pendiente:'fa-book text-primary',
        devolucion_vencida:'fa-exclamation-triangle text-danger'
    };
    return iconos[tipo]||'fa-info-circle text-secondary';
}
function cargarNotificaciones(){
    fetch(base_url+'Configuracion/notificaciones').then(r=>r.json()).then(res=>{
        document.getElementById('not').innerHTML=res.total;
        const cont=document.getElementById('listaNotificaciones');
        if(!res.items || !res.items.length){
            cont.innerHTML='<li class="app-notification__item text-muted small px-3 py-2">Sin novedades.</li>';
        }else{
            cont.innerHTML=res.items.map(it=>`
                <a class="app-notification__item" href="javascript:;">
                    <span class="app-notification__icon"><i class="fa ${iconoNotificacion(it.tipo)} fa-lg"></i></span>
                    <div><p class="app-notification__message">${it.mensaje}</p></div>
                </a>
            `).join('');
        }
        const footer=document.getElementById('footerNotificaciones');
        if(footer){
            footer.innerHTML=esStaff
                ? `<a href="${base_url}Configuracion/libros" target="_blank">Ver reporte de préstamos vencidos.</a>`
                : `<a href="${base_url}EscanerQr/index">Ver mis reservas y préstamos.</a>`;
        }
    }).catch(()=>{});
}

function cargarCatalogosUsuario(callback){
    const http=new XMLHttpRequest();http.open('GET',base_url+'Usuarios/catalogos',true);http.send();
    http.onreadystatechange=function(){if(this.readyState!==4)return;try{const d=JSON.parse(this.responseText);llenarSelect('#Usuario_tipo',d.tipos);llenarSelect('#Usuario_carrera',d.carreras);llenarSelect('#Usuario_rol',d.roles);if(callback)callback(d);}catch(e){console.error(this.responseText);alertas('No se pudieron cargar los catálogos del usuario','error');}};
}
function llenarSelect(selector,data){const el=$(selector);if(!el.length)return;el.empty().append(new Option('Seleccione...','',true,true));(data||[]).forEach(x=>el.append(new Option(x.text,x.id)));el.trigger('change');}
function actualizarCarreraUsuario(){const tipo=parseInt($('#Usuario_tipo').val()||0,10);const carrera=$('#Usuario_carrera');if(tipo===1){carrera.prop('disabled',false).prop('required',true);}else{carrera.val('').prop('disabled',true).prop('required',false);}}
// "Tipo de usuario" (tabla tipos_usuario) y "Rol" (tabla roles) son catálogos
// independientes con IDs distintos aunque comparten nombres (Alumno, Profesor,
// Bibliotecario, Administrador). Si no se sincronizan, al cambiar el Tipo el
// Rol se queda con el valor anterior (por eso "sale antiguo alumno").
function sincronizarRolConTipo(){
    const tipoTexto=$('#Usuario_tipo option:selected').text().trim();
    const rolSelect=$('#Usuario_rol');
    if(!tipoTexto||!rolSelect.length)return;
    const opcion=rolSelect.find('option').filter(function(){return $(this).text().trim()===tipoTexto;});
    if(opcion.length)rolSelect.val(opcion.val()).trigger('change');
}
function frmUsuario(){const f=document.getElementById('frmUsuario');if(!f)return;f.reset();$('#Idusuario').val('');$('#title').text('Nuevo usuario');$('#btnAccion').text('Registrar');$('#claves').removeClass('d-none');$('#clave,#confirmar').prop('required',true);$('#Usuario_carrera').prop('disabled',false);$('#nuevo_usuario').modal('show');cargarCatalogosUsuario();}
$(document).on('change','#Usuario_tipo',actualizarCarreraUsuario);
$(document).on('change','#Usuario_tipo',sincronizarRolConTipo);
function registrarUser(e){e.preventDefault();const f=document.getElementById('frmUsuario');if(!f.checkValidity()){f.reportValidity();return;}const http=new XMLHttpRequest();http.open('POST',base_url+'Usuarios/registrar',true);http.send(new FormData(f));http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);alertas(r.msg,r.icono);if(r.icono==='success'){$('#nuevo_usuario').modal('hide');f.reset();tblUsuarios.ajax.reload(null,false);}}catch(e){console.error(this.responseText);alertas('Respuesta inválida del servidor','error');}};}
function btnEditarUser(id){const http=new XMLHttpRequest();http.open('GET',base_url+'Usuarios/editar/'+id,true);http.send();http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);cargarCatalogosUsuario(function(){ $('#Idusuario').val(r.Idusuario);$('#Usuario_ci').val(r.cedula);$('#Usuario_nombres').val(r.nombres);$('#Usuario_apellidos').val(r.apellidos);$('#Usuario_correo').val(r.correo);$('#Usuario_telefono').val(r.telefono||'');$('#Usuario_tipo').val(r.id_tipo_usuario).trigger('change');$('#Usuario_carrera').val(r.id_carrera||'').trigger('change');$('#Usuario_rol').val(r.id_rol||'').trigger('change');actualizarCarreraUsuario();});$('#title').text('Actualizar usuario');$('#btnAccion').text('Modificar');$('#claves').addClass('d-none');$('#clave,#confirmar').prop('required',false);$('#nuevo_usuario').modal('show');}catch(e){console.error(this.responseText);alertas('No fue posible cargar el usuario','error');}};}
function btnEliminarUser(id){confirmarAccion('¿Está seguro de eliminar?','El usuario quedará inactivo.',base_url+'Usuarios/eliminar/'+id,()=>tblUsuarios.ajax.reload(null,false));}
function btnReingresarUser(id){confirmarAccion('¿Reingresar usuario?','El usuario volverá a estar activo.',base_url+'Usuarios/reingresar/'+id,()=>tblUsuarios.ajax.reload(null,false));}

function cargarCarrerasEstudiante(callback){const http=new XMLHttpRequest();http.open('GET',base_url+'Usuarios/catalogos',true);http.send();http.onreadystatechange=function(){if(this.readyState!==4)return;try{const d=JSON.parse(this.responseText),s=$('#id_carrera');s.empty().append(new Option('Seleccione una carrera','',true,true));(d.carreras||[]).forEach(v=>s.append(new Option(v.text,v.id)));if(callback)callback();}catch(e){console.error(this.responseText);alertas('No se pudieron cargar las carreras','error');}};}
function frmEstudiante(){const f=document.getElementById('frmEstudiante');if(!f)return;f.reset();$('#Idusuario').val('');$('#title').text('Nuevo estudiante');$('#btnAccion').text('Registrar');$('#nuevoEstudiante').modal('show');cargarCarrerasEstudiante();}
function registrarEstudiante(e){e.preventDefault();const f=document.getElementById('frmEstudiante');if(!f.checkValidity()){f.reportValidity();return;}const http=new XMLHttpRequest();http.open('POST',base_url+'Estudiantes/registrar',true);http.send(new FormData(f));http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);alertas(r.msg,r.icono);if(r.icono==='success'){$('#nuevoEstudiante').modal('hide');f.reset();tblEst.ajax.reload(null,false);}}catch(e){console.error(this.responseText);alertas('Respuesta inválida del servidor','error');}};}
function btnEditarEst(id){const http=new XMLHttpRequest();http.open('GET',base_url+'Estudiantes/editar/'+id,true);http.send();http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);cargarCarrerasEstudiante(function(){$('#Idusuario').val(r.Idusuario);$('#nombres').val(r.nombres);$('#apellidos').val(r.apellidos);$('#id_carrera').val(r.Tbl_carreras_Idcarrera).trigger('change');$('#correo').val(r.Usuario_correo);$('#cedula').val(r.Usuario_ci);$('#telefono').val(r.Usuario_telefono||'');$('#title').text('Actualizar estudiante');$('#btnAccion').text('Modificar');$('#nuevoEstudiante').modal('show');});}catch(e){console.error(this.responseText);alertas('No fue posible cargar el estudiante','error');}};}
function btnEliminarEst(id){confirmarAccion('¿Está seguro de eliminar?','El estudiante quedará inactivo.',base_url+'Estudiantes/eliminar/'+id,()=>tblEst.ajax.reload(null,false));}
function btnReingresarEst(id){confirmarAccion('¿Reingresar estudiante?','El estudiante volverá a estar activo.',base_url+'Estudiantes/reingresar/'+id,()=>tblEst.ajax.reload(null,false));}

function frmMateria(){const f=document.getElementById('frmMateria');if(!f)return;f.reset();$('#Idmateria').val('');$('#title').text('Nueva materia');$('#btnAccion').text('Registrar');$('#nuevoMateria').modal('show');}
function registrarMateria(e){e.preventDefault();const f=document.getElementById('frmMateria');if(!f.checkValidity()){f.reportValidity();return;}const http=new XMLHttpRequest();http.open('POST',base_url+'Materia/registrar',true);http.send(new FormData(f));http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);alertas(r.msg,r.icono);if(r.icono==='success'){$('#nuevoMateria').modal('hide');f.reset();tblMateria.ajax.reload(null,false);}}catch(e){console.error(this.responseText);alertas('Respuesta inválida del servidor','error');}};}
function btnEditarMat(id){const http=new XMLHttpRequest();http.open('GET',base_url+'Materia/editar/'+id,true);http.send();http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);$('#Idmateria').val(r.Idmateria);$('#materia').val(r.Materia_descripcion);$('#title').text('Actualizar materia');$('#btnAccion').text('Modificar');$('#nuevoMateria').modal('show');}catch(e){console.error(this.responseText);alertas('No fue posible cargar la materia','error');}};}
function btnEliminarMat(id){confirmarAccion('¿Eliminar materia?','La materia quedará inactiva.',base_url+'Materia/eliminar/'+id,()=>tblMateria.ajax.reload(null,false));}
function btnReingresarMat(id){confirmarAccion('¿Reingresar materia?','La materia volverá a estar activa.',base_url+'Materia/reingresar/'+id,()=>tblMateria.ajax.reload(null,false));}

function frmCategoria(){const f=document.getElementById('frmCategoriaForm');if(!f)return;f.reset();$('#Idcategoria').val('');$('#titleCat').text('Nueva categoría');$('#btnAccionCat').text('Registrar');$('#nuevoCategoria').modal('show');}
function registrarCategoria(e){e.preventDefault();const f=document.getElementById('frmCategoriaForm');if(!f.checkValidity()){f.reportValidity();return;}const http=new XMLHttpRequest();http.open('POST',base_url+'Categoria/registrar',true);http.send(new FormData(f));http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);alertas(r.msg,r.icono);if(r.icono==='success'){$('#nuevoCategoria').modal('hide');f.reset();tblCategoria.ajax.reload(null,false);}}catch(e){console.error(this.responseText);alertas('Respuesta inválida del servidor','error');}};}
function btnEditarCat(id){const http=new XMLHttpRequest();http.open('GET',base_url+'Categoria/editar/'+id,true);http.send();http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);$('#Idcategoria').val(r.Idcategoria);$('#categoria').val(r.Categoria_descripcion);$('#titleCat').text('Actualizar categoría');$('#btnAccionCat').text('Modificar');$('#nuevoCategoria').modal('show');}catch(e){console.error(this.responseText);alertas('No fue posible cargar la categoría','error');}};}
function btnEliminarCat(id){confirmarAccion('¿Eliminar categoría?','La categoría quedará inactiva y dejará de mostrarse en el filtro del catálogo.',base_url+'Categoria/eliminar/'+id,()=>tblCategoria.ajax.reload(null,false));}
function btnReingresarCat(id){confirmarAccion('¿Reingresar categoría?','La categoría volverá a estar activa.',base_url+'Categoria/reingresar/'+id,()=>tblCategoria.ajax.reload(null,false));}

function frmAutor(){const f=document.getElementById('frmAutor');if(!f)return;f.reset();$('#Idautor').val('');$('#title').text('Nuevo autor');$('#btnAccion').text('Registrar');$('#nuevoAutor').modal('show');}
function registrarAutor(e){e.preventDefault();const f=document.getElementById('frmAutor');if(!f.checkValidity()){f.reportValidity();return;}const http=new XMLHttpRequest();http.open('POST',base_url+'Autor/registrar',true);http.send(new FormData(f));http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);alertas(r.msg,r.icono);if(r.icono==='success'){$('#nuevoAutor').modal('hide');f.reset();tblAutor.ajax.reload(null,false);}}catch(e){console.error(this.responseText);alertas('Respuesta inválida del servidor','error');}};}
function btnEditarAutor(id){const http=new XMLHttpRequest();http.open('GET',base_url+'Autor/editar/'+id,true);http.send();http.onreadystatechange=function(){if(this.readyState!==4)return;try{const r=JSON.parse(this.responseText);$('#Idautor').val(r.Idautor);$('#Autor_nombres').val(r.Autor_nombres);$('#Autor_apellidos').val(r.Autor_apellidos);$('#Autor_pais').val(r.Autor_pais||'');$('#title').text('Actualizar autor');$('#btnAccion').text('Modificar');$('#nuevoAutor').modal('show');}catch(e){console.error(this.responseText);alertas('No fue posible cargar el autor','error');}};}
function btnEliminarAutor(id){confirmarAccion('¿Eliminar autor?','El autor quedará inactivo.',base_url+'Autor/eliminar/'+id,()=>tblAutor.ajax.reload(null,false));}
function btnReingresarAutor(id){confirmarAccion('¿Reingresar autor?','El autor volverá a estar activo.',base_url+'Autor/reingresar/'+id,()=>tblAutor.ajax.reload(null,false));}

function frmEditorial() {
    document.getElementById("title").textContent = "Nuevo Editorial";
    document.getElementById("btnAccion").textContent = "Registrarlossss";
    document.getElementById("frmEditorial").reset();
    document.getElementById("id").value = "";
    $("#nuevoEditorial").modal("show");
}

 
function registrarEditorial(e) {
    e.preventDefault();
    const editorial = document.getElementById("descripcion");
    if (editorial.value == "") {
        alertas('El editorial es requerido', 'warning');
    } else {
        const url = base_url + "Editorial/registrar";
        const frm = document.getElementById("frmEditorial");
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                $("#nuevoEditorial").modal("hide");
                tblEditorial.ajax.reload();
                alertas(res.msg, res.icono);
            }
        }
    }
}

function btnEditarEdi(id) {
    document.getElementById("title").textContent = "Actualizar Editorial";
    document.getElementById("btnAccion").textContent = "Modificar";
    const url = base_url + "Editorial/editar/" + id;
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            document.getElementById("id").value = res.Ideditorial;
            document.getElementById("descripcion").value = res.Editorial_descripcion;
            $("#nuevoEditorial").modal("show");
        }
    }
}

function btnEliminarEdi(id) {
    Swal.fire({
        title: 'Esta seguro de eliminar?',
        text: "El Editorial no se eliminará de forma permanente, solo cambiará el estado a inactivo!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Editorial/eliminar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblEditorial.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function btnReingresarEdi(id) {
    Swal.fire({
        title: 'Esta seguro de reingresar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Editorial/reingresar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblEditorial.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}


//Fin editorial

function frmConfig(e) {
    e.preventDefault();
    const nombre = document.getElementById("nombre");
    const telefono = document.getElementById("telefono");
    const direccion = document.getElementById("direccion");
    const correo = document.getElementById("correo");
    if (nombre.value == "" || telefono.value == "" || direccion.value == "" || correo.value == "") {
        alertas('Todo los campos son requeridos', 'warning');
    } else {
        const url = base_url + "Configuracion/actualizar";
        const frm = document.getElementById("frmConfig");
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                alertas(res.msg, res.icono);
            }
        }
    }
}

function frmPrestar() {
    document.getElementById("frmPrestar").reset();
    $("#prestar").modal("show");
}
// comentado ya que el insert  se realizara dede cliente
// function btnEntregar(id) {
//     Swal.fire({
//         title: 'Recibir de libro?',
//         icon: 'warning',
//         showCancelButton: true,
//         confirmButtonColor: '#3085d6',
//         cancelButtonColor: '#d33',
//         confirmButtonText: 'Si!',
//         cancelButtonText: 'No'
//     }).then((result) => {
//         if (result.isConfirmed) {
//             const url = base_url + "Prestamos/entregar/" + id;
//             const http = new XMLHttpRequest();
//             http.open("GET", url, true);
//             http.send();
//             http.onreadystatechange = function() {
//                 if (this.readyState == 4 && this.status == 200) {
//                     const res = JSON.parse(this.responseText);
//                     tblPrestar.ajax.reload();
//                     alertas(res.msg, res.icono);
//                 }
//             }

//         }
//     })
// }

// nueva funcion cmabia estado de reserva a devuelto
function btnEntregar(id) {
    Swal.fire({
        title: '¿Confirmar esta reserva?',
        text: "Quedará lista para que el usuario la retire",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Prestamos/activarPrestamo/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblPrestar.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

// funcion nuevo cambia estado a pendiente otravez
function btnEstadoDevuelto(id) {
    Swal.fire({
        title: '¿Registrar la devolución?',
        text: "El préstamo asociado a esta reserva quedará devuelto",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Prestamos/devolucionPrestamo/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblPrestar.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}



function registroPrestamos(e) {
    e.preventDefault();
    const libro = document.getElementById("libro").value;
    const estudiante = document.getElementById("estudiante").value;
    const cantidad = document.getElementById("cantidad").value;
    const fecha_prestamo = document.getElementById("fecha_prestamo").value;
    const fecha_devolucion = document.getElementById("fecha_devolucion").value;


    if (libro == ' ' || estudiante == '' || cantidad == '' || fecha_prestamo == '' || fecha_devolucion == '') {
        alertas('Todo los campos son requeridos', 'warning');
    } else {
        const frm = document.getElementById("frmPrestar");
        const url = base_url + "Prestamos/registrar";
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                tblPrestar.ajax.reload();

                frm.reset();

                $("#prestar").modal("hide");

                alertas(res.msg, res.icono);

                if (res.icono == 'success') {
                    setTimeout(() => {
                        window.open(base_url + 'Prestamos/ticked/' + res.id, '_blank');
                    }, 3000);
                }

            }
        }
    }
}

function btnRolesUser(id) {
    const http = new XMLHttpRequest();
    const url = base_url + "Usuarios/permisos/" + id;
    http.open("GET", url);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            document.getElementById("frmPermisos").innerHTML = this.responseText;
            $("#permisos").modal("show");
        }
    }
}

function registrarPermisos(e) {
    e.preventDefault();
    const http = new XMLHttpRequest();
    const frm = document.getElementById("frmPermisos");
    const url = base_url + "Usuarios/registrarPermisos";
    http.open("POST", url);
    http.send(new FormData(frm));
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            $("#permisos").modal("hide");
            if (res == 'ok') {
                alertas('Permisos Asignado', 'success');
            } else {
                alertas('Error al asignar los permisos', 'error');
            }
        }
    }
}

function modificarClave(e) {
    e.preventDefault();
    var formClave = document.querySelector("#frmCambiarPass");
    formClave.onsubmit = function(e) {
        e.preventDefault();
        const clave_actual = document.querySelector("#clave_actual").value;
        const nueva_clave = document.querySelector("#clave_nueva").value;
        const confirmar_clave = document.querySelector("#clave_confirmar").value;
        if (clave_actual == "" || nueva_clave == "" || confirmar_clave == "") {
            alertas('Todo los campos son requeridos', 'warning');
        } else if (nueva_clave != confirmar_clave) {
            alertas('Las contraseñas no coinciden', 'warning');
        } else {
            const http = new XMLHttpRequest();
            const frm = document.getElementById("frmPermisos");
            const url = base_url + "Usuarios/cambiarPas";
            http.open("POST", url);
            http.send(new FormData(formClave));
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    $('#cambiarClave').modal("hide");
                    alertas(res.msg, res.icono);
                }
            }
        }

    }
}
if (document.getElementById("reportePrestamo")) {
    const url = base_url + "Configuracion/grafico";
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const data = JSON.parse(this.responseText);
            let nombre = [];
            let cantidad = [];
            for (let i = 0; i < data.length; i++) {
                nombre.push(data[i]['titulo']);
                cantidad.push(data[i]['cantidad']);
            }
            var ctx = document.getElementById("reportePrestamo");
            var myPieChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: nombre,
                    datasets: [{
                        label: 'Libros',
                        data: cantidad,
                        backgroundColor: ['#dc143c'],
                    }],
                },
            });

        }
    }
}

function alertas(msg, icono) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icono,
        title: msg,
        showConfirmButton: false,
        timer: 3000
    })
}

function verificarLibro(e) {
    const libro = document.getElementById('libro').value;
    const cant = document.getElementById('cantidad').value;
    const http = new XMLHttpRequest();
    const url = base_url + 'Libros/verificar/' + libro;
    http.open("GET", url);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            if (res.icono == 'success') {
                document.getElementById('msg_error').innerHTML = `<span class="badge badge-primary">Disponible: ${res.cantidad}</span>`;
            } else {
                alertas(res.msg, res.icono);
                return false;
            }
        }
    }
}





//napePE la logica

function registrarcKETNOMA(e) {
    e.preventDefault();
    const http = new XMLHttpRequest();
    const frm = document.getElementById("frm_id_mov_det");
    const url = base_url + "Cketnomas/registrarcKETNOMA";
    http.open("POST", url);
    http.send(new FormData(frm));
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            console.log(res)
            if (res == 'ok') {
                alertas('Ckeckeado olude', 'success');
            }if (res == 'cancelado') {
                alertas('SE CANCELO', 'success');
            }
            if (res ==''){
                alertas('Error pos error', 'error');
            }
            
        }
    }
}
// API pública dos módulos usados pelos botones inline das vistas.
window.frmMateria=frmMateria; window.registrarMateria=registrarMateria; window.btnEditarMat=btnEditarMat; window.btnEliminarMat=btnEliminarMat; window.btnReingresarMat=btnReingresarMat;
window.frmCategoria=frmCategoria; window.registrarCategoria=registrarCategoria; window.btnEditarCat=btnEditarCat; window.btnEliminarCat=btnEliminarCat; window.btnReingresarCat=btnReingresarCat;
window.frmAutor=frmAutor; window.registrarAutor=registrarAutor; window.btnEditarAutor=btnEditarAutor; window.btnEliminarAutor=btnEliminarAutor; window.btnReingresarAutor=btnReingresarAutor;

if(typeof frmPrestamos==='function') window.frmPrestamos=frmPrestamos; if(typeof registroPrestamos==='function') window.registroPrestamos=registroPrestamos; if(typeof verificarLibro==='function') window.verificarLibro=verificarLibro;
