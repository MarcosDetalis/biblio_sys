let tblLibros;

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


tblLibros = $('#tblLibros').DataTable({
    ajax: {
        url: base_url + "Libros/listar",
        dataSrc: ''
    },
    columns: [{
            'data': 'id'
        },
        {
            'data': 'titulo'
        },
        {
            'data': 'cantidad'
        },
        {
            'data': 'autor'
        },
        {
            'data': 'editorial'
        },
        {
            'data': 'materia'
        },
        {
            'data': 'foto'
        },
        {
            'data': 'descripcion'
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

function frmLibros() {
    document.getElementById("title").textContent = "Nuevo Libro";
    document.getElementById("btnAccion").textContent = "Registrar";
    document.getElementById("frmLibro").reset();
    document.getElementById("id").value = "";
    $("#nuevoLibro").modal("show");
    document.querySelector('.lds-spinner').hidden = true;
    deleteImg();

    document.getElementById("autor").innerText = null;
    var autor = document.createElement('option');
    autor.value = res.id_autor;
    autor.innerHTML = res.autor;
    document.getElementById("autor").appendChild(autor);



}
var imgg;

console.log("asda")
const log = document.getElementById("imagen");
log.addEventListener("change", (event) => {
const file = event.target.files[0];
const imgname = event.target.files[0].name;
const reader = new FileReader();
reader.readAsDataURL(file);
reader.onloadend = () => {
  const img = new Image();
  img.src = reader.result;
  img.onload = () => {
    const canvas = document.createElement("canvas");
    const maxSize = Math.max(img.width, img.height);
    canvas.width = maxSize;
    canvas.height = maxSize;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(
      img,
      (maxSize - img.width) / 2,
      (maxSize - img.height) / 2
    );
    canvas.toBlob(
      (blob) => {
        const file = new File([blob], imgname, {
          type: "image/png",
          lastModified: Date.now(),
        });
        console.log(file);
        imgg =file
      },
      "image/jpeg",
      0.8
    );
  };
};

});


function registrarLibro(e) {
    
    e.preventDefault();

     const titulo = document.getElementById("titulo");
     const autor = document.getElementById("autor");
     const editorial = document.getElementById("editorial");
     const materia = document.getElementById("materia");
     const cantidad = document.getElementById("cantidad");
     const num_pagina = document.getElementById("num_pagina");
     if (titulo.value == '' || autor.value == '' || editorial.value == '' ||
         materia.value == '' || cantidad.value == '' || num_pagina.value == '') {
         alertas('Todo los campos son requeridos', 'warning');
     } else {
        
        const capagris = document.querySelector('.app-content');
        document.querySelector('.lds-spinner').hidden = false;
        capagris.classList.add('newClass');
        
            let formData=new FormData();
            formData.append("file",imgg)
             formData.append("upload_preset","mfa6ot9w")
              
          async function main() {
            let progress
            let urlimg
          const xhr = new XMLHttpRequest();
          const success = await new Promise((resolve) => { 
             // setboto(true)
            xhr.upload.addEventListener("progress", (event) => {
              if (event.lengthComputable) {
                console.log("upload progress:", Math.round((event.loaded*100.0)  / event.total ));
                progress =  Math.round((event.loaded*100.0)  / event.total )
                console.log(progress) 
                
              }
              console.log("En proceso")
           
            });
            xhr.addEventListener("loadend", () => {
              resolve(xhr.readyState === 4 && xhr.status === 200);
              console.log("Listo")
            Swal.fire({
              position: "top-end",
              icon: "success",
              title: "Datos Guardados",
              showConfirmButton: false,
              timer: 1500,
            });
            //location.reload();
            });
            xhr.open("POST", "https://api.cloudinary.com/v1_1/dylm4lqwh/image/upload");
          
            xhr.send(formData);
            xhr.onreadystatechange = function () {
              if (this.readyState == 4 && this.status == 200) {
                  console.log(",rl",JSON.parse(this.responseText).secure_url);

                  urlimg =JSON.parse(this.responseText).secure_url

                   
                   document.getElementById("urlimggen").value=urlimg;
                  
                   document.querySelector('.app-content').classList.remove('newClass');

             }
              //seturl(url)
              console.log(urlimg,"url")
          }
          }); 
          
          console.log("success:", success);
          console.log( "FIN")  
          
               const url = base_url + "Libros/registrar";
               const frm = document.getElementById("frmLibro");
               const http = new XMLHttpRequest();
               http.open("POST", url, true);
               http.send(new FormData(frm));
               http.onreadystatechange = function() {
                   if (this.readyState == 4 && this.status == 200) {
                       const res = JSON.parse(this.responseText);
                       $("#nuevoLibro").modal("hide");
                      tblLibros.ajax.reload();
                      frm.reset();
                    // location.reload();
                      alertas(res.msg, res.icono);   
                  }
              }   
         }
          main().catch(console.error);
    }
}

function btnEditarLibro(id) {

    document.getElementById("title").textContent = "Actualizar Libro";
    document.getElementById("btnAccion").textContent = "Modificar";
    document.querySelector('.lds-spinner').hidden = true;
     /*
        let formData=new FormData();
        formData.append("file",imgg)
        formData.append("upload_preset","mfa6ot9w")
          
      async function main() {
        let progress
        let urlimg
      const xhr = new XMLHttpRequest();
      const success = await new Promise((resolve) => { 
         // setboto(true)
        xhr.upload.addEventListener("progress", (event) => {
          if (event.lengthComputable) {
            console.log("upload progress:", Math.round((event.loaded*100.0)  / event.total ));
            progress =  Math.round((event.loaded*100.0)  / event.total )
            console.log(progress) 
            
          }
          console.log("En proceso")
       
        });

        xhr.addEventListener("loadend", () => {
          resolve(xhr.readyState === 4 && xhr.status === 200);
          console.log("Listo")
         

        //location.reload();
        });
        xhr.open("POST", "https://api.cloudinary.com/v1_1/dylm4lqwh/image/upload");
      
        xhr.send(formData);
        xhr.onreadystatechange = function () {
          if (this.readyState == 4 && this.status == 200) {
              console.log(",rl",JSON.parse(this.responseText).secure_url);

              urlimg =JSON.parse(this.responseText).secure_url

               
               document.getElementById("urlimggen").value=urlimg;
              
               document.querySelector('.app-content').classList.remove('newClass');

         }
          //seturl(url)
          console.log(urlimg,"url")
      }
      }); 
      
      console.log("success:", success);
      console.log( "FIN") */

      const url = base_url + "Libros/editar/" + id;
      const http = new XMLHttpRequest();
      http.open("GET", url, true);
      http.send();
      http.onreadystatechange = function() {
          if (this.readyState == 4 && this.status == 200) {
              const res = JSON.parse(this.responseText);
              console.log(res)

              document.getElementById("id").value = res.id_libro;
              document.getElementById("titulo").value = res.titulo;

                document.getElementById("autor").value=res.id_autor;
                var autor = document.createElement('option');
                autor.value = res.id_autor;
                autor.innerHTML = res.autor;
                document.getElementById("autor").appendChild(autor);


                document.getElementById("editorial").value=res.id_editorial;
                var editorial = document.createElement('option');
                editorial.value = res.id_editorial;
                editorial.innerHTML = res.editorial;
                document.getElementById("editorial").appendChild(editorial);

                document.getElementById("materia").value=res.id_materia;
                var materia = document.createElement('option');
                materia.value = res.id_materia;
                materia.innerHTML = res.materia;
                document.getElementById("materia").appendChild(materia);


              document.getElementById("cantidad").value = res.cantidad;
              document.getElementById("num_pagina").value = res.num_pagina;
              document.getElementById("anio_edicion").value = res.anio_edicion;
              document.getElementById("descripcion").value = res.descripcion;
              document.getElementById("img-preview").src =  res.img_libros;
              document.getElementById("icon-cerrar").innerHTML = `
              <button class="btn btn-danger" onclick="deleteImg()">
              <i class="fa fa-times-circle"></i></button>`;
              document.getElementById("icon-image").classList.add("d-none");
              document.getElementById("foto_actual").value = res.img_libros;
              $("#nuevoLibro").modal("show");
          }
      } 
     //}
     //main().catch(console.error);




    
}

function btnEliminarLibro(id) {
    Swal.fire({
        title: 'Esta seguro de eliminar?',
        text: "El libro no se eliminará de forma permanente, solo cambiará el estado a inactivo!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si!',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = base_url + "Libros/eliminar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblLibros.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function btnReingresarLibro(id) {
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
            const url = base_url + "Libros/reingresar/" + id;
            const http = new XMLHttpRequest();
            http.open("GET", url, true);
            http.send();
            http.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    const res = JSON.parse(this.responseText);
                    tblLibros.ajax.reload();
                    alertas(res.msg, res.icono);
                }
            }

        }
    })
}

function preview(e) {
    console.log("first")
    var input = document.getElementById('imagen');
    var filePath = input.value;
    var extension = /(\.png|\.jpeg|\.jpg)$/i;
    if (!extension.exec(filePath)) {
        alertas('Seleccione un archivo valido', 'warning');
        deleteImg();
        return false;
    } else {
        const url = e.target.files[0];
        const urlTmp = URL.createObjectURL(url);
        document.getElementById("img-preview").src = urlTmp;
        document.getElementById("icon-image").classList.add("d-none");
        document.getElementById("icon-cerrar").innerHTML = `
        <button class="btn btn-danger" onclick="deleteImg()"><i class="fa fa-times-circle"></i></button>
        `;
    }
}

function deleteImg() {
    document.getElementById("icon-cerrar").innerHTML = '';
    document.getElementById("icon-image").classList.remove("d-none");
    document.getElementById("img-preview").src = '';
    document.getElementById("imagen").value = '';
    document.getElementById("foto_actual").value = '';
}