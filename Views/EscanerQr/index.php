<?php include "Views/Templates/header.php"; ?>
<script src="<?php echo base_url; ?>Assets/node_modules/html5-qrcode/html5-qrcode.min.js"></script>

<div class="app-title">
    <div>
        <h1><i class="fa fa-dashboard"></i> Lector QR</h1>
    </div>
</div>




<div class="row">
    <div class="col-md-5 mx-auto">
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


    scanner.render(success, error);
    // Starts scanner

    function success(result) {

        document.getElementById('result').innerHTML = `
        <h2>Success!</h2>
        <p>
        
        a href="${result}">${result}</a>

        </p>
        `;
        // Prints result as a link inside result element

        scanner.clear();
        // Clears scanning instance

        document.getElementById('reader').remove();
        // Removes reader element from DOM since no longer needed

      
    }

    function error(err) {
        console.error(err);
        // Prints any errors to the console
    }




</script>
<?php include "Views/Templates/footer.php"; ?>