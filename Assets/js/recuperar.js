let identidadRecuperar = { cedula: "", correo: "" };
let resetTokenRecuperar = "";

// Antes esta pantalla solo mostraba un texto chico dentro del formulario.
// El lado cliente (React) usa ventanas emergentes con título e ícono para
// cada paso (SweetAlert2) — se iguala acá para que el bibliotecario/admin
// vea los mismos mensajes, con la misma claridad, en ambos lados.

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

    const cedula = document.getElementById("cedula").value.trim();
    const correo = document.getElementById("correo").value.trim();

    if (cedula === "" || correo === "") {
        Swal.fire({ icon: "warning", title: "Completá tu cédula y tu correo" });
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
            Swal.fire({ icon: "error", title: "No se pudo enviar el código", text: "No fue posible procesar la solicitud." });
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            identidadRecuperar = { cedula, correo };
            irAPaso(2);
            Swal.fire({
                icon: "success",
                title: "Código enviado",
                text: "Si los datos son correctos, te enviamos un código de verificación a tu correo. Revisá también la carpeta de spam.",
            });
        } else {
            Swal.fire({ icon: "error", title: "No se pudo enviar el código", text: res.msg });
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
        if (this.status !== 200) {
            Swal.fire({ icon: "error", title: "No se pudo reenviar", text: "No fue posible reenviar el código." });
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            Swal.fire({ icon: "success", title: "Código reenviado", text: "Te enviamos un nuevo código a tu correo." });
        } else {
            Swal.fire({ icon: "error", title: "No se pudo reenviar", text: res.msg });
        }
    };
}

function frmVerificarCodigo(e) {
    e.preventDefault();

    const codigo = document.getElementById("codigo").value.trim();
    if (codigo === "") {
        Swal.fire({ icon: "warning", title: "Ingresá el código" });
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
            Swal.fire({ icon: "error", title: "Código incorrecto", text: "No fue posible verificar el código." });
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            resetTokenRecuperar = res.resetToken;
            irAPaso(3);
        } else {
            Swal.fire({ icon: "error", title: "Código incorrecto", text: res.msg });
            // Si se agotaron los intentos o el código venció, hay que
            // volver a pedir uno nuevo desde cero.
            const msg = (res.msg || "").toLowerCase();
            if (msg.includes("agotaron") || msg.includes("inválido") || msg.includes("vencido")) {
                document.getElementById("frmPaso2").reset();
                irAPaso(1);
            }
        }
    };
}

function frmRestablecer(e) {
    e.preventDefault();

    const passwordNueva = document.getElementById("passwordNueva").value;
    const confirmarPassword = document.getElementById("confirmarPassword").value;

    if (passwordNueva.length < 6) {
        Swal.fire({ icon: "warning", title: "La contraseña debe tener al menos 6 caracteres" });
        return;
    }
    if (passwordNueva !== confirmarPassword) {
        Swal.fire({ icon: "warning", title: "Las contraseñas no coinciden" });
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
            Swal.fire({ icon: "error", title: "No se pudo actualizar", text: "No fue posible restablecer la contraseña." });
            return;
        }
        const res = JSON.parse(this.responseText);
        if (res.icono === "success") {
            Swal.fire({
                icon: "success",
                title: "Contraseña restablecida",
                text: "Ya podés iniciar sesión con tu contraseña nueva.",
            }).then(function () {
                window.location = base_url;
            });
        } else {
            Swal.fire({ icon: "error", title: "No se pudo actualizar", text: res.msg });
            const msg = (res.msg || "").toLowerCase();
            if (msg.includes("venció") || msg.includes("utilizada")) {
                document.getElementById("frmPaso1").reset();
                document.getElementById("frmPaso2").reset();
                document.getElementById("frmPaso3").reset();
                irAPaso(1);
            }
        }
    };
}
