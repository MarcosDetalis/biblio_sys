<?php include "Views/Templates/header.php"; ?>
<script src="<?php echo base_url; ?>Assets/node_modules/html5-qrcode/html5-qrcode.min.js"></script>
<script src="<?php echo base_url; ?>Assets/node_modules/pdfjs-dist/build/pdf.min.js"></script>
<script>pdfjsLib.GlobalWorkerOptions.workerSrc='<?php echo base_url; ?>Assets/node_modules/pdfjs-dist/build/pdf.worker.min.js';</script>
<div class="app-title">
    <div><h1><i class="fa fa-list-alt"></i> Reservas y préstamos</h1><p>Reserva → Retirado → Devuelto.</p></div>
</div>
<button class="btn btn-primary mb-2" data-toggle="modal" data-target="#modalQr"><i class="fa fa-qrcode"></i> Escanear QR</button>

<div class="tile mt-2">
    <ul class="nav nav-tabs" id="tabsEstado">
        <li class="nav-item"><a class="nav-link active" href="#" onclick="filtrarListado(null,this)">Todas</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="filtrarListado('Reserva',this)">Reserva</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="filtrarListado('Parcial',this)">Parcial</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="filtrarListado('Retirado',this)">Retirado</a></li>
        <li class="nav-item"><a class="nav-link" href="#" onclick="filtrarListado('Devuelto',this)">Devuelto</a></li>
    </ul>
    <div class="table-responsive mt-2"><table class="table table-bordered table-hover" id="tblTransacciones">
        <thead class="thead-dark"><tr><th>Reserva</th><th>Usuario</th><th>Libros</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
        <tbody></tbody>
    </table></div>
</div>

<!-- Todo el escaneo (cámara + búsqueda sin cámara + resultado con la acción a tomar) vive dentro de este modal -->
<div id="modalQr" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg" style="max-width:950px"><div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title"><i class="fa fa-qrcode"></i> Escanear reserva / préstamo</h5>
            <button class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div id="reader"></div>
                    <div id="reader-file" class="d-none"></div>
                    <div class="mt-2">
                        <label class="btn btn-outline-secondary btn-sm btn-block" for="pdfQrInput"><i class="fa fa-file-pdf-o"></i> Subir PDF con el código QR</label>
                        <input type="file" id="pdfQrInput" accept="application/pdf" class="d-none" onchange="leerPdfQr(this.files[0])">
                        <div id="pdfQrEstado" class="small text-muted text-center"></div>
                    </div>
                    <hr>
                    <label class="small text-muted">¿No tenés cámara a mano? Buscá por nombre, cédula o N° de reserva/préstamo:</label>
                    <div class="input-group">
                        <input type="text" id="txtBuscar" class="form-control" placeholder="Nombre, cédula, N° de reserva o de préstamo" onkeydown="if(event.key==='Enter')buscarTransaccion()">
                        <div class="input-group-append"><button class="btn btn-secondary" onclick="buscarTransaccion()"><i class="fa fa-search"></i></button></div>
                    </div>
                    <div id="resultadosBusqueda" class="mt-2"></div>
                </div>
                <div class="col-md-6">
                    <div class="card"><div class="card-header bg-info text-white">Resultado</div>
                        <div class="card-body" id="result"><p class="text-muted">Escaneá un código o buscá arriba.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div></div>
</div>

<script>
let scanner=null;
let lectorArchivo=null;
let tblTransacciones;
let idReservaPendiente=null; // seteado por gestionarReserva(); lo consume el handler de shown.bs.modal

window.addEventListener('load',function(){
    tblTransacciones=$('#tblTransacciones').DataTable({
        ajax:{url:base_url+'EscanerQr/listar',dataSrc:''},
        columns:[
            {data:'numero_reserva'},
            {data:'usuario'},
            {data:'libros'},
            {data:'estado_simple',render:renderBadgeEstado},
            {data:'fecha_reserva'},
            {data:null,render:renderAccionFila,orderable:false,searchable:false}
        ],
        language:{lengthMenu:'Mostrar _MENU_ Entradas',info:'Mostrando _START_ a _END_ de _TOTAL_ Entradas',infoEmpty:'Mostrando 0 a 0 de 0 Entradas',infoFiltered:'(Filtrado de _MAX_ total entradas)',zeroRecords:'Sin resultados encontrados',emptyTable:'No hay registros',search:'Buscar:',processing:'Procesando...',paginate:{first:'Primero',last:'Último',next:'Siguiente',previous:'Anterior'}}
    });

    // La cámara solo se pide/enciende cuando el operador realmente abre el modal
    // (no en la carga de la página), y se apaga al cerrarlo para no dejarla
    // encendida de fondo. Estas dos ligaduras necesitan que jQuery/Bootstrap
    // (cargados en footer.php, que se incluye DESPUÉS de este script) ya
    // existan, por eso van también dentro de 'load' y no sueltas más abajo.
    $('#modalQr').on('shown.bs.modal',function(){
        document.getElementById('resultadosBusqueda').innerHTML='';
        document.getElementById('txtBuscar').value='';
        if(idReservaPendiente){
            document.getElementById('result').innerHTML='<p class="text-muted">Cargando...</p>';
            fetch(base_url+'EscanerQr/obtenerEstadoReserva/'+idReservaPendiente).then(r=>r.json()).then(mostrarReserva);
            idReservaPendiente=null;
        }else{
            document.getElementById('result').innerHTML='<p class="text-muted">Escaneá un código o buscá arriba.</p>';
        }
        if(!scanner){
            scanner=new Html5QrcodeScanner('reader',{qrbox:{width:250,height:250},fps:10});
            scanner.render(success,error);
        }
    });
    $('#modalQr').on('hidden.bs.modal',function(){
        if(scanner){ scanner.clear().catch(()=>{}); scanner=null; }
    });
});

function renderBadgeEstado(estado){
    const colores={Reserva:'secondary',Parcial:'info',Retirado:'warning',Devuelto:'success',Cancelada:'danger',Vencida:'dark'};
    return `<span class="badge badge-${colores[estado]||'secondary'}">${estado}</span>`;
}
function renderAccionFila(d,t,r){
    if(r.estado_simple==='Devuelto'||r.estado_simple==='Cancelada'||r.estado_simple==='Vencida')return '';
    return `<button class="btn btn-sm btn-primary" onclick="gestionarReserva(${r.id_reserva})"><i class="fa fa-tasks"></i> Gestionar</button>`;
}
function filtrarListado(estado,el){
    document.querySelectorAll('#tabsEstado .nav-link').forEach(a=>a.classList.remove('active'));
    el.classList.add('active');
    tblTransacciones.ajax.url(base_url+'EscanerQr/listar'+(estado?'/'+encodeURIComponent(estado):'')).load();
}
function recargarListado(){tblTransacciones.ajax.reload(null,false);}

// Abre el mismo modal/resultado que usa el escaneo por QR, pero yendo
// directo por id_reserva (para "Gestionar" desde el listado o la búsqueda,
// sin necesidad de tener el código QR a mano).
function gestionarReserva(idReserva){
    if($('#modalQr').hasClass('show')){
        // El modal ya está abierto (ej: se clickeó "Gestionar" desde la búsqueda
        // interna) -> Bootstrap no vuelve a disparar shown.bs.modal, así que se
        // busca directo en vez de dejarlo en la cola para ese evento.
        document.getElementById('result').innerHTML='<p class="text-muted">Cargando...</p>';
        fetch(base_url+'EscanerQr/obtenerEstadoReserva/'+idReserva).then(r=>r.json()).then(mostrarReserva);
        return;
    }
    idReservaPendiente=idReserva;
    $('#modalQr').modal('show');
}

// ---- Búsqueda por nombre / cédula / N° reserva / N° préstamo (dentro del modal) ----
function buscarTransaccion(){
    const texto=document.getElementById('txtBuscar').value.trim();
    if(!texto){document.getElementById('resultadosBusqueda').innerHTML='';return;}
    fetch(base_url+'EscanerQr/buscar/'+encodeURIComponent(texto)).then(r=>r.json()).then(rows=>{
        if(!rows.length){document.getElementById('resultadosBusqueda').innerHTML='<p class="text-muted small">Sin resultados.</p>';return;}
        let html='<table class="table table-sm"><tbody>';
        rows.forEach(r=>{
            const accion=(r.estado_simple==='Devuelto'||r.estado_simple==='Cancelada'||r.estado_simple==='Vencida')?'<span class="text-muted small">Sin acción</span>':`<button class="btn btn-sm btn-primary" onclick="gestionarReserva(${r.id_reserva})">Gestionar</button>`;
            html+=`<tr><td>${r.numero_reserva}</td><td>${r.usuario}</td><td>${renderBadgeEstado(r.estado_simple)}</td><td>${accion}</td></tr>`;
        });
        html+='</tbody></table>';
        document.getElementById('resultadosBusqueda').innerHTML=html;
    });
}

// ---- Acciones de transición de estado (comparten selección con checkboxes) ----
function marcarTodos(clase,valor){document.querySelectorAll('.'+clase).forEach(c=>c.checked=valor);}
function mostrarBanner(msg,icono){
    const clase=icono==='success'?'alert-success':(icono==='warning'?'alert-warning':'alert-danger');
    const el=document.getElementById('bannerAccion');
    if(el)el.innerHTML=`<div class="alert ${clase} py-2">${msg}</div>`;
}
function confirmarRetiroSeleccion(idReserva){
    const marcados=[...document.querySelectorAll('.chk-pendiente:checked')];
    const sinMarcar=[...document.querySelectorAll('.chk-pendiente:not(:checked)')];
    if(!marcados.length){alertas('Tildá al menos un libro para retirar','warning');return;}
    const ids=marcados.map(c=>c.value);
    const btn=document.getElementById('btnConfirmarRetiro');

    const confirmarYEnviar=()=>{
        // Se deshabilita de inmediato para que un segundo clic (mientras el
        // primero todavía está en camino) no dispare un pedido duplicado.
        if(btn){btn.disabled=true;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Procesando...';}
        const body=new URLSearchParams();
        body.append('id_reserva',idReserva);
        ids.forEach(id=>body.append('ids[]',id));
        fetch(base_url+'EscanerQr/confirmarRetiroParcial',{method:'POST',body}).then(r=>r.json()).then(r=>{
            alertas(r.msg,r.icono);
            mostrarBanner(r.msg,r.icono);
            if(r.icono==='success'){
                recargarListado();
                // Pequeña espera para que el mensaje de éxito realmente se
                // alcance a leer antes de que se refresque el contenido del modal.
                setTimeout(()=>gestionarReserva(idReserva),1200);
            }else if(btn){
                btn.disabled=false;btn.innerHTML='<i class="fa fa-check"></i> Confirmar retiro';
            }
        }).catch(()=>{
            mostrarBanner('Error de conexión, intentá de nuevo','error');
            if(btn){btn.disabled=false;btn.innerHTML='<i class="fa fa-check"></i> Confirmar retiro';}
        });
    };

    if(sinMarcar.length){
        const titulos=sinMarcar.map(c=>c.closest('label').textContent.trim()).join(', ');
        Swal.fire({
            title:'¿Confirmar retiro?',
            html:`Se va${sinMarcar.length===1?'':'n'} a <strong>cancelar automáticamente</strong> ${sinMarcar.length===1?'el libro que no tildaste':'los libros que no tildaste'}: <br>${titulos}`,
            icon:'warning',showCancelButton:true,confirmButtonText:'Sí, retirar y cancelar el resto',cancelButtonText:'Cancelar'
        }).then(res=>{ if(res.isConfirmed) confirmarYEnviar(); });
    }else{
        confirmarYEnviar();
    }
}
function confirmarDevolucionSeleccion(idReserva){
    const ids=[...document.querySelectorAll('.chk-prestado:checked')].map(c=>c.value);
    if(!ids.length){alertas('Tildá al menos un libro para devolver','warning');return;}
    const btn=document.getElementById('btnConfirmarDevolucion');
    if(btn){btn.disabled=true;btn.innerHTML='<i class="fa fa-spinner fa-spin"></i> Procesando...';}
    const body=new URLSearchParams();
    ids.forEach(id=>body.append('ids[]',id));
    fetch(base_url+'EscanerQr/confirmarDevolucionParcial',{method:'POST',body}).then(r=>r.json()).then(r=>{
        alertas(r.msg,r.icono);
        mostrarBanner(r.msg,r.icono);
        if(r.icono==='success'){
            recargarListado();
            setTimeout(()=>gestionarReserva(idReserva),1200);
        }else if(btn){
            btn.disabled=false;btn.innerHTML='<i class="fa fa-undo"></i> Confirmar devolución de lo tildado';
        }
    }).catch(()=>{
        mostrarBanner('Error de conexión, intentá de nuevo','error');
        if(btn){btn.disabled=false;btn.innerHTML='<i class="fa fa-undo"></i> Confirmar devolución de lo tildado';}
    });
}

// ---- Escaneo por cámara o "Gestionar": mismo resultado mixto, con checkboxes ----
function mostrarReserva(data){
    if(data.tipo==='error'){
        const mensajes={
            no_existe:'Este código QR no corresponde a ninguna reserva.',
            expirado:'El QR venció: la fecha límite de retiro ya pasó.',
            ya_devuelto:'Esta reserva ya fue retirada y devuelta por completo. No hay nada pendiente.',
            todo_cancelado:'En esta reserva no se retiró ningún libro: todo quedó cancelado.'
        };
        document.getElementById('result').innerHTML=`<div class="alert alert-warning">${mensajes[data.motivo]||'QR inválido, expirado o ya utilizado.'}</div>`;
        return;
    }
    const r=data.reserva;
    let html=`<div id="bannerAccion"></div><p><strong>Reserva:</strong> ${r.numero_reserva}</p><p><strong>Usuario:</strong> ${r.usuario}</p><p><strong>Límite de retiro:</strong> ${r.fecha_limite_retiro}</p>`;

    if(data.pendientes && data.pendientes.length){
        html+=`<hr><p class="mb-1"><strong>Pendientes de retirar</strong> <button type="button" class="btn btn-link btn-sm p-0" onclick="marcarTodos('chk-pendiente',true)">Tildar todos</button></p>
        <p class="small text-muted mb-1">Lo que no tildes acá se cancela automáticamente al confirmar (no queda pendiente para otro día).</p>
        <ul class="list-unstyled">`;
        data.pendientes.forEach(it=>{
            html+=`<li><label><input type="checkbox" class="chk-pendiente" value="${it.id_reserva_detalle}"> ${it.titulo} <span class="text-muted small">(${it.codigo_ejemplar})</span></label></li>`;
        });
        html+=`</ul><button id="btnConfirmarRetiro" class="btn btn-success btn-sm" onclick="confirmarRetiroSeleccion(${r.id_reserva})"><i class="fa fa-check"></i> Confirmar retiro</button>`;
    }

    if(data.prestados && data.prestados.length){
        html+=`<hr><p class="mb-1"><strong>Prestados, pendientes de devolver</strong> <button type="button" class="btn btn-link btn-sm p-0" onclick="marcarTodos('chk-prestado',true)">Tildar todos</button></p><ul class="list-unstyled">`;
        data.prestados.forEach(it=>{
            html+=`<li><label><input type="checkbox" class="chk-prestado" value="${it.id_prestamo_detalle}"> ${it.titulo} <span class="text-muted small">(vence ${it.fecha_vencimiento})</span></label></li>`;
        });
        html+=`</ul><button id="btnConfirmarDevolucion" class="btn btn-primary btn-sm" onclick="confirmarDevolucionSeleccion(${r.id_reserva})"><i class="fa fa-undo"></i> Confirmar devolución de lo tildado</button>`;
    }

    if(data.sinDisponibilidad && data.sinDisponibilidad.length){
        html+=`<hr><p class="mb-1 text-danger"><strong><i class="fa fa-exclamation-triangle"></i> Sin disponibilidad</strong></p>
        <p class="small text-muted mb-1">Se pidieron, pero al llegar el momento del retiro ya no quedaba ningún ejemplar libre. Quedaron canceladas automáticamente.</p>
        <ul class="list-unstyled text-danger small">`;
        data.sinDisponibilidad.forEach(it=>{ html+=`<li><i class="fa fa-ban"></i> ${it.titulo}</li>`; });
        html+='</ul>';
    }

    if(data.devueltos && data.devueltos.length){
        html+=`<hr><p class="mb-1 text-muted"><strong>Ya devueltos</strong></p><ul class="list-unstyled text-muted small">`;
        data.devueltos.forEach(it=>{ html+=`<li><i class="fa fa-check-circle"></i> ${it.titulo} (${it.fecha_devolucion})</li>`; });
        html+='</ul>';
    }

    if(data.cancelados && data.cancelados.length){
        html+=`<hr><p class="mb-1 text-muted"><strong>Cancelados (no se retiraron)</strong></p><ul class="list-unstyled text-muted small">`;
        data.cancelados.forEach(it=>{ html+=`<li><i class="fa fa-times-circle"></i> ${it.titulo}</li>`; });
        html+='</ul>';
    }

    document.getElementById('result').innerHTML=html;
}
function success(decodedText){
    fetch(base_url+'EscanerQr/obtenerDatosQr/'+encodeURIComponent(decodedText)).then(r=>r.json()).then(mostrarReserva).catch(()=>document.getElementById('result').innerHTML='<div class="alert alert-danger">No fue posible consultar el QR.</div>');
}
function error(err){ /* Los errores de lectura continua no se muestran para evitar ruido. */ }

// ---- Subir un PDF con el QR: se renderiza la primera página a una imagen
// en el navegador (pdf.js) y esa imagen se pasa al mismo decodificador que
// usa la cámara (html5-qrcode), sin depender de ningún servicio externo.
function leerPdfQr(file){
    if(!file)return;
    const estado=document.getElementById('pdfQrEstado');
    estado.textContent='Leyendo PDF...';
    const lector=new FileReader();
    lector.onload=function(ev){
        pdfjsLib.getDocument({data:new Uint8Array(ev.target.result)}).promise.then(pdf=>pdf.getPage(1)).then(page=>{
            const escala=3; // resolución alta para que el QR se lea bien aunque sea chico en la hoja
            const viewport=page.getViewport({scale:escala});
            const canvas=document.createElement('canvas');
            canvas.width=viewport.width;canvas.height=viewport.height;
            const ctx=canvas.getContext('2d');
            return page.render({canvasContext:ctx,viewport:viewport}).promise.then(()=>canvas);
        }).then(canvas=>{
            estado.textContent='Buscando el código dentro del PDF...';
            return canvas.toBlob ? new Promise(res=>canvas.toBlob(res,'image/png')) : null;
        }).then(blob=>{
            const archivoImagen=new File([blob],'pagina.png',{type:'image/png'});
            if(!lectorArchivo) lectorArchivo=new Html5Qrcode('reader-file');
            return lectorArchivo.scanFileV2(archivoImagen,false);
        }).then(resultado=>{
            estado.textContent='';
            success(resultado.decodedText);
        }).catch(err=>{
            estado.textContent='';
            document.getElementById('result').innerHTML='<div class="alert alert-warning">No se encontró ningún código QR legible en ese PDF.</div>';
        });
    };
    lector.readAsArrayBuffer(file);
}
</script>
<?php include "Views/Templates/footer.php"; ?>
