let identidadRecuperar = { cedula: "", correo: "" };
let resetTokenRecuperar = "";

function mostrarAlerta(idAlerta, mensaje) {
    const alerta = document.getElementById(idAlerta);
    alerta.classList.remove("d-none");
    alerta.innerHTML = mensaje;
}

function ocultarAlerta(idAlerta) {
    document.getElementById(idAlerta).classList.add("d-none");
}

function irAPaso(paso) {
    document.getElementById("frmPaso1").classList.toggle("d-none", paso !== 1);
    document.getElementById("frmPaso2").classList.toggle("d-none", paso !== 2);
    document.getElementById("frmPaso3").classList.toggle("d-none", paso !== 3);
}

function habilitarBoton(idBoton, habilitado) {
    document.getElementById(idBoton).disabled = !habilitado;
}

function frmSolicitarCodigo(e) {
    e.preventDefault();
    ocultarAlerta("alertaPaso1");

    const cedula = document.getElementById("cedula").value.trim();
    const correo = document.getElementById("correo").value.trim();

    if (cedula === "" || correo === "") {
        mostrarAlerta("alertaPaso1", "Completá tu cédula y tu correo");
        return;
    }

    habilitarBoton("btnPaso1", false);

    const http = new XMLHttpRequest();
    http.open("POST", base_url + "Usuarios/recuperarSolicitar", true);
    http.send(new FormData(document.getElementById("frmPaso1")));
    http.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        habilitarBoton("btnPaso1", true);
        if (this.status !== 200) {
            mostrarAlerta("alertaPaso1", "No fue posible procesar la solicitud");
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            identidadRecuperar = { cedula, correo };
            irAPaso(2);
        } else {
            mostrarAlerta("alertaPaso1", res.msg);
        }
    };
}

function reenviarCodigo(e) {
    e.preventDefault();

    const boton = document.getElementById("btnReenviar");
    boton.classList.add("disabled");
    boton.innerHTML = "Reenviando...";

    const form = new FormData();
    form.append("cedula", identidadRecuperar.cedula);
    form.append("correo", identidadRecuperar.correo);

    const http = new XMLHttpRequest();
    http.open("POST", base_url + "Usuarios/recuperarSolicitar", true);
    http.send(form);
    http.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        boton.classList.remove("disabled");
        boton.innerHTML = "Reenviar código";
        if (this.status === 200) {
            const res = JSON.parse(this.responseText);
            mostrarAlerta("alertaPaso2", res.msg);
            document.getElementById("alertaPaso2").classList.remove("alert-danger");
            document.getElementById("alertaPaso2").classList.add("alert-info");
        }
    };
}

function frmVerificarCodigo(e) {
    e.preventDefault();
    ocultarAlerta("alertaPaso2");

    const codigo = document.getElementById("codigo").value.trim();
    if (codigo === "") {
        mostrarAlerta("alertaPaso2", "Ingresá el código");
        return;
    }

    habilitarBoton("btnPaso2", false);

    const form = new FormData();
    form.append("cedula", identidadRecuperar.cedula);
    form.append("correo", identidadRecuperar.correo);
    form.append("codigo", codigo);

    const http = new XMLHttpRequest();
    http.open("POST", base_url + "Usuarios/recuperarVerificar", true);
    http.send(form);
    http.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        habilitarBoton("btnPaso2", true);
        if (this.status !== 200) {
            mostrarAlerta("alertaPaso2", "No fue posible verificar el código");
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            resetTokenRecuperar = res.resetToken;
            irAPaso(3);
        } else {
            mostrarAlerta("alertaPaso2", res.msg);
            document.getElementById("alertaPaso2").classList.remove("alert-info");
            document.getElementById("alertaPaso2").classList.add("alert-danger");
            // Si se agotaron los intentos o el código venció, hay que
            // volver a pedir uno nuevo desde cero.
            const msg = (res.msg || "").toLowerCase();
            if (msg.includes("agotaron") || msg.includes("inválido") || msg.includes("vencido")) {
                setTimeout(function () {
                    document.getElementById("frmPaso2").reset();
                    irAPaso(1);
                }, 2000);
            }
        }
    };
}

function frmRestablecer(e) {
    e.preventDefault();
    ocultarAlerta("alertaPaso3");

    const passwordNueva = document.getElementById("passwordNueva").value;
    const confirmarPassword = document.getElementById("confirmarPassword").value;

    if (passwordNueva.length < 6) {
        mostrarAlerta("alertaPaso3", "La contraseña debe tener al menos 6 caracteres");
        return;
    }
    if (passwordNueva !== confirmarPassword) {
        mostrarAlerta("alertaPaso3", "Las contraseñas no coinciden");
        return;
    }

    habilitarBoton("btnPaso3", false);

    const form = new FormData();
    form.append("resetToken", resetTokenRecuperar);
    form.append("passwordNueva", passwordNueva);
    form.append("confirmarPassword", confirmarPassword);

    const http = new XMLHttpRequest();
    http.open("POST", base_url + "Usuarios/recuperarRestablecer", true);
    http.send(form);
    http.onreadystatechange = function () {
        if (this.readyState !== 4) return;
        habilitarBoton("btnPaso3", true);
        if (this.status !== 200) {
            mostrarAlerta("alertaPaso3", "No fue posible restablecer la contraseña");
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            alert(res.msg);
            window.location = base_url;
        } else {
            mostrarAlerta("alertaPaso3", res.msg);
            const msg = (res.msg || "").toLowerCase();
            if (msg.includes("venció") || msg.includes("utilizada")) {
                setTimeout(function () {
                    document.getElementById("frmPaso1").reset();
                    document.getElementById("frmPaso2").reset();
                    document.getElementById("frmPaso3").reset();
                    irAPaso(1);
                }, 2000);
            }
        }
    };
}
