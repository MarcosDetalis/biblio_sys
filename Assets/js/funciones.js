let tblUsuarios, tblEst, tblMateria, tblAutor, tblEditorial, tblPrestar;


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

    tblUsuarios = $('#tblUsuarios').DataTable({
        ajax: {
            url: base_url + "Usuarios/listar",
            dataSrc: ''
        },
        columns: [
            { 'data': 'id' },
            { 'data': 'usuario' },
            { 'data': 'nombre' },
            { 'data': 'estado' },
            { 'data': 'acciones' }
        ],
        responsive: true,
        bDestroy: true,
        iDisplayLength: 10,
        order: [
            [0, "desc"]
        ],
        language,
        dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons
    });
    //Fin de la tabla usuarios
   
    tblEst = $('#tblEst').DataTable({
        ajax: {
            url: base_url + "Estudiantes/listar",
            dataSrc: ''
        },
        // Idusuario,Usuario_nombre1,Tbl_carreras_Idcarrera,Usuario_correo,Usuario_nombre_usuario,Usuario_ci,Usuario_estado
        columns: [{ 'data': 'Idusuario' },
            { 'data': 'Usuario_nombre1' },
            { 'data': 'Tbl_tipo_usuarios_idTipo_usuario'},
            { 'data': 'Tbl_carreras_Idcarrera'},
            { 'data': 'Usuario_correo' },
            { 'data': 'Usuario_nombre_usuario'},
            { 'data': 'Usuario_ci' },
            { 'data': 'Usuario_estado'},
            { 'data': 'acciones' }
        ],
        language,
        dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons
    });
    //Fin de la tabla Estudiantes
  
    tblMateria = $('#tblMateria').DataTable({
        ajax: {
            url: base_url + "Materia/listar",
            dataSrc: ''
        },
        columns: [{
                'data': 'Idmateria'
            },
            {
                'data': 'Carrera_descripcion'   
            },
            {
                'data': 'Materia_descripcion'
            },
            {
                'data': 'materia_estado'
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
    //Fin de la tabla Materias
    tblAutor = $('#tblAutor').DataTable({
        ajax: {
            url: base_url + "Autor/listar",
            dataSrc: '',
            // linea agregada para ver error en consola
            error: function (xhr, error, thrown) {
                console.error("Error en la carga de datos: ", xhr.responseText);
            }
        },
        columns: [{'data': 'Idautor'}, 
                  { 'data': 'Autor_nombres'},
                  { 'data': 'Autor_pais'},
                  {'data': 'Autor_estado'},
                  {'data': 'acciones'}
        ],
        language,
        dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons
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
    tblPrestar = $('#tblPrestar').DataTable({
        ajax: {
            url: base_url + "Prestamos/listar",
            dataSrc: ''
        },
        columns: [
            {'data': 'Idreserva_cab'},
            {'data': 'Usuario_nombre1'},
            {'data': 'Usuario_correo'},
            {'data': 'Usuario_ci'},
            {'data': 'Reserva_cab_fecha_solicitud'},
            {'data': 'fecha_devuelto'},
            {'data': 'Tbl_Estados_solicitudes_idEstado_solicitud'},
            {'data': 'acciones'}
        ],
        language,
        dom: "<'row'<'col-sm-4'l><'col-sm-4 text-center'B><'col-sm-4'f>>" +
            "<'row'<'col-sm-12'tr>>" +
            "<'row'<'col-sm-5'i><'col-sm-7'p>>",
        buttons,
        "resonsieve": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [
            [0, "desc"]
        ]
    });
    $('.estudiante').select2({
        placeholder: 'Buscar Estudiante',
        minimumInputLength: 0,
        ajax: {
            url: base_url + 'Estudiantes/buscarEstudiante',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    est: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        language: {

            noResults: function() {

                return "No hay resultado";
            },
            searching: function() {

                return "Buscando..";
            },
            errorLoading: function() {

                return "Ingrese el nombre del estudiante..";
            },
        }
    });
    $('.libro').select2({
        placeholder: 'Buscar Libro',
        minimumInputLength: 0,
        ajax: {
            url: base_url + 'Libros/buscarLibro',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    lb: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },

        language: {

            noResults: function() {

                return "No hay resultado";
            },
            searching: function() {

                return "Buscando..";
            },
            errorLoading: function() {

                return "Ingrese el nombre del libro..";
            },
        }

    });

    $('.autor').select2({
        placeholder: 'Buscar Autor',
        minimumInputLength: 0,
        ajax: {
            url: base_url + 'Autor/buscarAutor',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: 'ultima'
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        language: {

            noResults: function() {

                return "No hay resultado";
            },
            searching: function() {

                return "Buscando..";
            },
            errorLoading: function() {

                return "Ingrese el nombre del autor..";
            },
        }

    });
    $('.editorial').select2({
        placeholder: 'Buscar Editorial',
        minimumInputLength: 0,
        ajax: {
            url: base_url + 'Editorial/buscarEditorial',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        language: {

            noResults: function() {

                return "No hay resultado";
            },
            searching: function() {

                return "Buscando..";
            },
            errorLoading: function() {

                return "Ingrese el nombre de la editorial..";
            },
        }
    });
    $('.materia').select2({
        placeholder: 'Buscar Materia',
        minimumInputLength: 0,
        ajax: {
            url: base_url + 'Materia/buscarMateria',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        language: {

            noResults: function() {

                return "No hay resultado";
            },
            searching: function() {

                return "Buscando..";
            },
            errorLoading: function() {

                return "Ingrese el nombre de la materia..";
            },
        }
    });

    // comentando temporalmente
    // if (document.getElementById('nombre_estudiante')) {
    //     const http = new XMLHttpRequest();
    //     const url = base_url + 'Configuracion/verificar';
    //     http.open("GET", url);
    //     http.send();
    //     http.onreadystatechange = function() {
    //         if (this.readyState == 4 && this.status == 200) {
    //             const res = JSON.parse(this.responseText);
    //             let html = '';
    //             console.log(res.length)
    //             res.forEach(row => {
    //                 html += `
    //                 <a class="app-notification__item" href="javascript:;"><span class="app-notification__icon"><span class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x text-primary"></i><i class="fa fa-user-o fa-stack-1x fa-inverse"></i></span></span>
    //                     <div>
    //                         <p class="app-notification__message" id="nombre_estudiante">${row.nombre}</p>
    //                         <p class="app-notification__meta" id="fecha_entrega">${row.fecha_devolucion}</p>
    //                     </div>
    //                 </a>
    //                 `;
    //             });
    //             document.getElementById('nombre_estudiante').innerHTML = html;
    //             document.getElementById('not').innerHTML = res.length;
    //         }
    //     }
    // }
})

function frmUsuario() {
    document.getElementById("title").textContent = "Nuevo Usuario";
    document.getElementById("btnAccion").textContent = "Registrar";
    document.getElementById("claves").classList.remove("d-none");
    document.getElementById("frmUsuario").reset();
    document.getElementById("Idusuario").value = "";
    $("#nuevo_usuario").modal("show");
}

function registrarUser(e) {
    e.preventDefault();
    const usuario = document.getElementById("Usuario_nombre_usuario");
    const nombre = document.getElementById("Usuario_nombre1");
    const tipousu = document.getElementById("Tbl_tipo_usuarios_idTipo_usuario");
    const carrera = document.getElementById("Tbl_carreras_Idcarrera");
    const clave = document.getElementById("clave");
    const confirmar = document.getElementById("confirmar");
    if (usuario.value == "" || nombre.value == "") {
        alertas('Todo los campos son requeridos', 'warning');
    } else {
        const url = base_url + "Usuarios/registrar";
        const frm = document.getElementById("frmUsuario");
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                $("#nuevo_usuario").modal("hide");
                frm.reset();
                tblUsuarios.ajax.reload();
                alertas(res.msg, res.icono);
            }
        }
    }
}

function btnEditarUser(id) {
    document.getElementById("title").textContent = "Actualizar usuario";
    document.getElementById("btnAccion").textContent = "Modificar";
    const url = base_url + "Usuarios/editar/" + id;
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            document.getElementById("Idusuario").value = res.Idusuario;
            document.getElementById("Usuario_nombre_usuario").value = res.Usuario_nombre_usuario;
            document.getElementById("Usuario_nombre1").value = res.Usuario_nombre1;
            document.getElementById("claves").classList.add("d-none");
            $("#nuevo_usuario").modal("show");
        }
    }
}

function btnEliminarUser(id) {
    Swal.fire({
        title: 'Esta seguro de eliminar?',
        text: "El usuario no se eliminará de forma permanente, solo cambiará el estado a inactivo!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Usuarios/eliminar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblUsuarios.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function btnReingresarUser(id) {
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
            const url = base_url + "Usuarios/reingresar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblUsuarios.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}
//Fin Usuarios
function frmEstudiante() {
    document.getElementById("title").textContent = "Nuevo Estudiante";
    document.getElementById("btnAccion").textContent = "Registrar";
    document.getElementById("frmEstudiante").reset();
    document.getElementById("Idusuario").value = "";
    $("#nuevoEstudiante").modal("show");
}

function registrarEstudiante(e) {
    e.preventDefault();
    const nombre = document.getElementById("nombre");
    const tipousu = document.getElementById("tipousu");
    const carrera = document.getElementById("carrera");
    const correo = document.getElementById("correo");
    const usunombre = document.getElementById("usunombre");
     const telefono = document.getElementById("telefono");
     
    if (nombre.value == "" || tipousu.value == "" || carrera.value == "" || correo.value == "" || usunombre.value == "" 
        || telefono.value == "") {
        alertas('Todo los campos son requeridos', 'warning');
    } else {
        const url = base_url + "Estudiantes/registrar";
        const frm = document.getElementById("frmEstudiante");
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                console.log(res);
                $("#nuevoEstudiante").modal("hide");
                frm.reset();
                tblEst.ajax.reload();
                alertas(res.msg, res.icono);
            }
          
        }
    }
}

function btnEditarEst(id) {
    console.log(id)
    document.getElementById("title").textContent = "Actualizar estudiante";
    document.getElementById("btnAccion").textContent = "Modificar";
    const url = base_url + "Estudiantes/editar/" + id;
    console.log(url);
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            document.getElementById("Idusuario").value = res.Idusuario;
           // console.log( document.getElementById("Idusuario").value = res.id);
            document.getElementById("nombre").value = res.Usuario_nombre1;
            document.getElementById("tipousu").value = res.Tbl_tipo_usuarios_idTipo_usuario;
            document.getElementById("carrera").value = res.Tbl_carreras_Idcarrera;
            document.getElementById("correo").value = res.Usuario_correo;
            document.getElementById("usunombre").value = res.Usuario_nombre_usuario;
            document.getElementById("telefono").value = res.Usuario_ci;
            $("#nuevoEstudiante").modal("show");
        }
    }
}

function btnEliminarEst(id) {
    Swal.fire({
        title: 'Esta seguro de eliminar?',
        text: "El estudiante no se eliminará de forma permanente, solo cambiará el estado a inactivo!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Estudiantes/eliminar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblEst.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function btnReingresarEst(id) {
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
            const url = base_url + "Estudiantes/reingresar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblEst.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}
//Fin Estudiante
function frmMateria() {
    document.getElementById("title").textContent = "Nueva Materia";
    document.getElementById("btnAccion").textContent = "Registrar";
    document.getElementById("frmMateria").reset();
    document.getElementById("id").value = "";
    $("#nuevoMateria").modal("show");
}

function registrarMateria(e) {
    e.preventDefault();
    const carrera = document.getElementById("carrera");
    const materia = document.getElementById("materia");
    if (carrera.value == "" || materia.value == "") {
        alertas('Todos los campos son requeridos', 'warning');
    } else {
        const url = base_url + "Materia/registrar";
        const frm = document.getElementById("frmMateria");
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                $("#nuevoMateria").modal("hide");
                frm.reset();
                tblMateria.ajax.reload();
                alertas(res.msg, res.icono);
            }
        }
    }
}

function btnEditarMat(id) {
    document.getElementById("title").textContent = "Actualizar caja";
    document.getElementById("btnAccion").textContent = "Modificar";
    const url = base_url + "Materia/editar/" + id;
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            document.getElementById("id").value = res.Idmateria;
            document.getElementById("carrera").value = res.Tbl_carreras_idCarrera;
            document.getElementById("materia").value = res.Materia_descripcion;
            $("#nuevoMateria").modal("show");
        }
    }
}

function btnEliminarMat(id) {
    Swal.fire({
        title: 'Esta seguro de eliminar?',
        text: "La materia no se eliminará de forma permanente, solo cambiará el estado a inactivo!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Materia/eliminar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblMateria.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function btnReingresarMat(id) {
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
            const url = base_url + "Materia/reingresar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblMateria.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}
//Fin Materia
// se tiene que modificar
function registrarAutor(e) {
    e.preventDefault();
    console.log('registrar')
    const nombre = document.getElementById("nombre");
    const pais = document.getElementById("pais");
    if (nombre.value == "") {
        alertas('El nombre es requerido', 'warning');
    } else {
        const url = base_url + "Autor/registrar";
        const frm = document.getElementById("frmAutor");
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                $("#nuevoAutor").modal("hide");
                frm.reset();
                tblAutor.ajax.reload();
                alertas(res.msg, res.icono);
            }
        }
    }
}

function btnEditarAutor(id) {
    document.getElementById("title").textContent = "Actualizar Autor";
    document.getElementById("btnAccion").textContent = "Modificar";
    const url = base_url + "Autor/editar/" + id;
    const http = new XMLHttpRequest();
    http.open("GET", url, true);
    http.send();
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            document.getElementById("id").value = res.id;
            document.getElementById("autor").value = res.autor;
            document.getElementById("foto_actual").value = res.imagen;
            document.getElementById("img-preview").src = base_url + 'Assets/img/autor/' + res.imagen;
            document.getElementById("icon-image").classList.add("d-none");
            document.getElementById("icon-cerrar").innerHTML = `
            <button class="btn btn-danger" onclick="deleteImg()">
            <i class="fa fa-times-circle"></i></button>`;
            $("#nuevoAutor").modal("show");
        }
    }
}

function btnEliminarAutor(id) {
    Swal.fire({
        title: 'Esta seguro de eliminar?',
        text: "El Autor no se eliminará de forma permanente, solo cambiará el estado a inactivo!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Autor/eliminar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblAutor.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function btnReingresarAutor(id) {
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
            const url = base_url + "Autor/reingresar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblAutor.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}
//Fin Autor
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
        title: 'Desea Marcar como devuelto?',
        text: "La reserva cambiara a estado devuelto",
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
        title: 'Desea cambiar el estado?',
        text: "La reserva volvera a estado pendiente",
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