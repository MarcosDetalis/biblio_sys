let   tblCarrera
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


tblCarrera = $('#tblCarrera').DataTable({
    ajax: {
        url: base_url + "Carrera/listar",
        dataSrc: ''
    },
    columns: [{
            'data': 'id_carrera'
        },
        {
            'data': 'carrera'
        },
        {
            'data': 'estado'
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

})


function frmCarrera() {
    document.getElementById("title").textContent = "Nueva Carrera";
    document.getElementById("btnAccion").textContent = "Registrar";
    document.getElementById("frmCarrera").reset();
    document.getElementById("id").value = "";
    $("#nuevaCarrera").modal("show");
}


function registrarCarrera(e) {
    e.preventDefault();
    const editorial = document.getElementById("editorial");
    if (editorial.value == "") {
        alertas('El editorial es requerido', 'warning');
    } else {
        const url = base_url + "Carrera/registrar";
        const frm = document.getElementById("frmCarrera");

        console.log(frm,",,")
        const http = new XMLHttpRequest();
        http.open("POST", url, true);
        http.send(new FormData(frm));
        http.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                const res = JSON.parse(this.responseText);
                $("#nuevaCarrera").modal("hide");
                tblCarrera.ajax.reload();
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
            document.getElementById("id").value = res.id;
            document.getElementById("editorial").value = res.editorial;
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
                    tblCarrera.ajax.reload();
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
                    tblCarrera.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}
//Fin editorial