/**
 * JavaScript del módulo de libros.
 * La portada se sube a Cloudinary (cloud "dylm4lqwh", preset unsigned
 * "mfa6ot9w") apenas se elige el archivo, y se guarda la URL resultante
 * (no el archivo) en la base de datos. Esto es necesario porque el
 * hosting de producción (Clever Cloud) tiene disco efímero: cualquier
 * imagen guardada localmente en el servidor se perdía en cada redeploy.
 */
let tblLibros;
let subiendoPortada=false;

$(function () {
    const language = {
        emptyTable: "No hay información", info: "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
        infoEmpty: "Mostrando 0 a 0 de 0 Entradas", infoFiltered: "(Filtrado de _MAX_ total entradas)",
        lengthMenu: "Mostrar _MENU_ Entradas", loadingRecords: "Cargando...", processing: "Procesando...",
        search: "Buscar:", zeroRecords: "Sin resultados encontrados",
        paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
    };
    if (!document.getElementById('tblLibros')) return;
    tblLibros = $('#tblLibros').DataTable({
        ajax: { url: base_url + 'Libros/listar', dataSrc: '' },
        columns: [
            {data:'id'}, {data:'titulo'}, {data:'cantidad'}, {data:'disponibles'}, {data:'autor'}, {data:'editorial'},
            {data:'categoria'}, {data:'foto'}, {data:'descripcion'}, {data:'estado'}, {data:'acciones'}
        ], language, responsive:true, bDestroy:true
    });
});

function frmLibros() {
    const f=document.getElementById('frmLibro'); if(!f)return;
    f.reset(); document.getElementById('id').value=''; $('#autor,#editorial,#categoria').each(function(){ $(this).val(null).empty().trigger('change'); });
    document.getElementById('title').textContent='Nuevo Libro';
    document.getElementById('btnAccion').textContent='Registrar';
    document.getElementById('img-preview').src='';
    document.getElementById('foto_actual').value='';
    document.getElementById('urlimggen').value='';
    document.getElementById('progresoPortada').textContent='';
    subiendoPortada=false;
    document.getElementById('btnAccion').disabled=false;
    cargarRestricciones(1);
    $('#nuevoLibro').modal('show');
}

// Catálogo de restricciones (Sin restricción / Restringido).
// Por defecto se deja preseleccionada "Sin restricción".
function cargarRestricciones(seleccionar){
    const http=new XMLHttpRequest();
    http.open('GET',base_url+'Libros/restricciones',true);
    http.send();
    http.onreadystatechange=function(){
        if(this.readyState!==4)return;
        try{
            const data=JSON.parse(this.responseText);
            const select=document.getElementById('restriccion');
            select.innerHTML='';
            data.forEach(function(op){
                const opt=document.createElement('option');
                opt.value=op.id; opt.textContent=op.text;
                select.appendChild(opt);
            });
            select.value=seleccionar||1;
        }catch(e){console.error(this.responseText);}
    };
}

// Sube la portada elegida a Cloudinary (unsigned upload) y guarda la URL
// resultante en el campo oculto #urlimggen. Mientras sube, bloquea el botón
// de Guardar para no registrar el libro con una portada a medio subir.
function subirPortadaCloudinary(file){
    if(!file)return;
    if(!/^image\/(jpe?g|png)$/i.test(file.type)){
        return; // preview() ya avisó del formato inválido; no se intenta subir.
    }
    const CLOUD_NAME='dylm4lqwh';
    const UPLOAD_PRESET='mfa6ot9w';
    const progreso=document.getElementById('progresoPortada');
    const btn=document.getElementById('btnAccion');

    subiendoPortada=true;
    btn.disabled=true;
    document.getElementById('urlimggen').value='';
    progreso.textContent='Subiendo portada... 0%';

    const formData=new FormData();
    formData.append('file',file);
    formData.append('upload_preset',UPLOAD_PRESET);

    const xhr=new XMLHttpRequest();
    xhr.open('POST','https://api.cloudinary.com/v1_1/'+CLOUD_NAME+'/image/upload');
    xhr.upload.addEventListener('progress',function(event){
        if(event.lengthComputable){
            const pct=Math.round((event.loaded*100.0)/event.total);
            progreso.textContent='Subiendo portada... '+pct+'%';
        }
    });
    xhr.onreadystatechange=function(){
        if(this.readyState!==4)return;
        subiendoPortada=false;
        btn.disabled=false;
        if(this.status===200){
            try{
                const url=JSON.parse(this.responseText).secure_url;
                document.getElementById('urlimggen').value=url;
                progreso.innerHTML='<span class="text-success"><i class="fa fa-check"></i> Portada subida</span>';
            }catch(e){
                progreso.innerHTML='<span class="text-danger">No se pudo procesar la respuesta de Cloudinary</span>';
            }
        }else{
            progreso.innerHTML='<span class="text-danger">No se pudo subir la portada (se guardará el libro sin imagen, o reintentá)</span>';
        }
    };
    xhr.onerror=function(){
        subiendoPortada=false;
        btn.disabled=false;
        progreso.innerHTML='<span class="text-danger">Error de red al subir la portada</span>';
    };
    xhr.send(formData);
}

function registrarLibro(e){
    e.preventDefault();
    if(subiendoPortada){
        alertas('Esperá a que termine de subirse la portada','warning');
        return;
    }
    const f=document.getElementById('frmLibro');
    const required=['titulo','autor','editorial','categoria','cantidad','num_pagina'];
    if(required.some(id=>!document.getElementById(id).value)){
        alertas('Todos los campos son requeridos','warning'); return;
    }
    const http=new XMLHttpRequest();
    http.open('POST',base_url+'Libros/registrar',true);
    http.send(new FormData(f));
    http.onreadystatechange=function(){
        if(this.readyState===4){
            try{const res=JSON.parse(this.responseText);if(res.icono==='success'){$('#nuevoLibro').modal('hide');f.reset();$('#autor,#editorial,#categoria').each(function(){ $(this).val(null).empty().trigger('change'); });document.getElementById('img-preview').src='';tblLibros.ajax.reload(null,false);}alertas(res.msg,res.icono);}
            catch(e){console.error(this.responseText);alertas('Respuesta inválida del servidor','error');}
        }
    };
}

function btnEditarLibro(id){
    const http=new XMLHttpRequest(); http.open('GET',base_url+'Libros/editar/'+id,true); http.send();
    http.onreadystatechange=function(){
        if(this.readyState!==4)return;
        try{
            const r=JSON.parse(this.responseText);
            document.getElementById('title').textContent='Actualizar Libro';
            document.getElementById('btnAccion').textContent='Modificar';
            document.getElementById('id').value=r.id_libro;
            document.getElementById('titulo').value=r.titulo||'';
            setSelect2Value('autor',r.id_autor,r.autor);
            setSelect2Value('editorial',r.id_editorial,r.editorial);
            setSelect2Value('categoria',r.id_categoria,r.categoria);
            document.getElementById('cantidad').value=r.cantidad||0;
            document.getElementById('num_pagina').value=r.num_pagina||'';
            document.getElementById('anio_edicion').value=r.anio_edicion||'';
            document.getElementById('descripcion').value=r.descripcion||'';
            document.getElementById('foto_actual').value=r.imagen||'';
            document.getElementById('img-preview').src=r.imagen||'';
            document.getElementById('urlimggen').value='';
            document.getElementById('progresoPortada').textContent='';
            cargarRestricciones(r.id_restriccion||1);
            subiendoPortada=false;
            document.getElementById('btnAccion').disabled=false;
            $('#nuevoLibro').modal('show');
        }catch(e){console.error(this.responseText);alertas('No fue posible cargar el libro','error');}
    };
}

function preview(e){
    const input=e.target;
    const img=document.getElementById('img-preview');
    if(!input.files || !input.files[0]){ return; }
    const file=input.files[0];
    if(!/^image\/(jpe?g|png)$/i.test(file.type)){
        alertas('Formato de imagen no permitido','warning');
        input.value='';
        return;
    }
    const reader=new FileReader();
    reader.onload=function(ev){ img.src=ev.target.result; };
    reader.readAsDataURL(file);
}

function setSelect2Value(id,value,text){
    const select=$('#'+id);
    if(select.hasClass('select2-hidden-accessible')) select.val(null).trigger('change');
    select.empty();
    if(value){const option=new Option(text||value,value,true,true);select.append(option).trigger('change');}
}
window.frmLibros=frmLibros; window.registrarLibro=registrarLibro; window.btnEditarLibro=btnEditarLibro; window.btnEliminarLibro=btnEliminarLibro; window.btnReingresarLibro=btnReingresarLibro;
function btnEliminarLibro(id){confirmarAccion('¿Eliminar el libro?','El libro quedará inactivo.',base_url+'Libros/eliminar/'+id,()=>tblLibros.ajax.reload(null,false));}
function btnReingresarLibro(id){confirmarAccion('¿Reingresar el libro?','El libro volverá a estar activo.',base_url+'Libros/reingresar/'+id,()=>tblLibros.ajax.reload(null,false));}
function confirmarAccion(titulo,texto,url,callback){
    Swal.fire({title:titulo,text:texto,icon:'warning',showCancelButton:true,confirmButtonText:'Sí',cancelButtonText:'No'}).then(r=>{
        if(!r.isConfirmed)return;const x=new XMLHttpRequest();x.open('GET',url,true);x.send();x.onreadystatechange=function(){if(this.readyState===4){const res=JSON.parse(this.responseText);alertas(res.msg,res.icono);if(res.icono==='success')callback();}};
    });
}
