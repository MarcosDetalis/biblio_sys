function btncambiarEstado(event){
    event.preventDefault();
    console.log("holaa")
    const http = new XMLHttpRequest();
    const frm = document.getElementById("myForm");
    const url = base_url + "EscanerQR/cambiarEstadosChecks";
    http.open("POST", url);
    http.send(new FormData(frm));
    http.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            const res = JSON.parse(this.responseText);
            console.log(res)
            if (res == 'ok') {
                alertas('Libros Chequeados', 'success');
                // location.reload();
            }if (res == 'cancelado') {
                alertas('SE CANCELO', 'success');
            }
            if (res ==''){
                alertas('Error pos error', 'error');
            }
            
        }
     }
  }