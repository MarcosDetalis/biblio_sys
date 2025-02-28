<?php include "Views/Templates/header.php"; ?>
<script src="<?php echo base_url; ?>Assets/node_modules/html5-qrcode/html5-qrcode.min.js"></script>

<!-- importamos el codigo de logaQr -->
<script src="<?php echo base_url; ?>Assets/js/logicaQr.js"></script>


<div class="app-title">
    <div>
        <h1><i class="fa fa-dashboard"></i> Lector QR</h1>
    </div>
</div>

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header text-center bg-primary">
                <h4 class="text-white">Lector QR</h4>
            </div>
            <div class="card-body">
                <div id="reader"></div>
                <div id="result"></div>
            </div>
        </div>
    </div>
</div>

<script>
    const scanner = new Html5QrcodeScanner('reader', {
        // Scanner will be initialized in DOM inside element with id of 'reader'
        qrbox: {
            width: 250,
            height: 250,
        }, // Sets dimensions of scanning box (set relative to reader element width)
        fps: 20, // Frames per second to attempt a scan
    });


    // renderiza
    scanner.render(success, error);
    // Starts scanner

    

    function success() {
        
        fetch('http://localhost/biblio2/EscanerQr/obtenerDatos/1')
            .then(res => res.json())
            .then(result => {
                const resultHtml = `
    <div class="container mt-5 px-2">
    <div class="mb-2 d-flex justify-content-between align-items-center"> 
    </div>
    <div class="table-responsive">
     <form id="myForm">
     
     
    <table class="table table-responsive table-borderless">
    <thead>
        <tr class="bg-light">
        <th scope="col" width="10%"></th>
        <th scope="col" width="5%">#</th>
        <th scope="col" width="20%">Fecha devolucion</th>
        <th scope="col" width="20%">Libro</th>
        <th scope="col" width="20%">Alumno</th>
        <th scope="col" class="text-end" width="10%"><span>Estado</span></th>
        <th scope="col" width="20%">Cantidad</th>
       
        </tr>
    </thead>
  <tbody>
  ${result.map((item, index) =>` 
  <input type="hidden" name="id_mov_cabecera" value="${item.Tbl_reservas_cab_idReserva_cab}">
    <tr>
    <td scope="row">
    <input class="form-check-input" type="checkbox" name="id_detalle[]" value="${item.Idreserva_det}" data-id="${item.Idreserva_det}">
    </td>
    <td>${index + 1}</td>
    <td>${item.Reserva_det_fecha_entraga}</td>
    <td><span class="ms-1">${item.Libro_nombre}</span></td>
    <td><img src="" width="25">Gustavo Rivas</td>
    <td>${item.Estado_solicitud_descripcion}</td>
    <td class="text-end"><span class="fw-bolder">${item.Reserva_det_cantidad_solicitud}</span></td> 
    </tr>
<td class="text-end">
    </td>`)}
</table>
<button class="btn btn-primary mt-3 btn-block" type="button" onclick="btncambiarEstado(event);">Actualizar</button>';  
</form> 
</div> 
</div> `;
                document.getElementById('result').innerHTML = resultHtml;
            })

        scanner.clear();
        // Clears scanning instance
        document.getElementById('reader').remove();
        // Removes reader element from DOM since no longer needed
    }
 

    

    
    function error(err) {
        console.error(err);

    }
</script>
<?php include "Views/Templates/footer.php"; ?>