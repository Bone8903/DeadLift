const tabla = document.getElementById("tablaUsuarios");
const mensaje = document.getElementById("mensaje");
 
function mostrarMensaje(texto, tipo = "info") {
    mensaje.innerHTML = `<p style="color:${tipo === "error" ? "red" : "green"};">${texto}</p>`;
}
 
     cargarUsuarios();
 
function cargarUsuarios() {
    fetch("./php/PanelAdmin.php")
        .then(res => res.json())
        .then(datos => {

            if (!datos.exito) {
                mostrarMensaje(datos.mensaje || "No se pudieron cargar los usuarios", "error");
                return;
            }
 
            if (!datos.usuarios || datos.usuarios.length === 0) {
                tabla.innerHTML = `<tr><td colspan="9">No hay usuarios registrados.</td></tr>`;
                return;
            }
 
            tabla.innerHTML =""
 
datos.usuarios.forEach(usuario => {

    if(usuario.Rol!="admin"){

    const bloqueado = Number(usuario.Bloqueado) === 1; // 1 = bloqueado, 0 = activo

        tabla.innerHTML +=  `
            <tr>
                <td>${usuario.Id_usuario ?? "-"}</td>
                <td>${usuario.Nombre ?? "-"}</td>
                <td>${usuario.Apellido ?? "-"}</td>
                <td>${usuario.Mail ?? "-"}</td>
                <td>${usuario.Telefono ?? "-"}</td>
                <td>${usuario.Rol ?? "-"}</td>
                <td>
                    <button type="button" data-id="${usuario.Id_usuario}">Borrar</button>
                    <button type="button" data-bloquear-id="${usuario.Id_usuario}" data-bloquear="${bloqueado ? 0 : 1}">${bloqueado ? "Desbloquear" : "Bloquear"}</button>
                </td>
            </tr>
        `}
            })
        })
        .catch(error => {
            console.error("Error al cargar usuarios:", error);
            mostrarMensaje("Error al cargar la lista de usuarios.", "error");
        });
}
 
document.addEventListener("DOMContentLoaded", () => {
    fetch("./php/checkAdmin.php")
        .then(res => res.json())
        .then(datos => {
            if (datos.r !== "admin") {
                location.href = "../gym/index.html";
                return;
 
 
            }
        })
        .catch(error => {
            console.error("Error en comprobación del admin:", error);
            location.href = "../gym/index.html";
        });
});
 
document.addEventListener("click", (event) => {
    if (event.target.matches("button[data-id]")) {
        const id = event.target.dataset.id;
 
        if (!confirm("¿Seguro que quieres borrar este usuario?")) {
            return;
        }
 
        const form = new FormData();
        form.append("id", id);
 
        fetch("./php/BorrarUsuario.php", {
            method: "POST",
            body: form
        })
            .then(res => res.json())
            .then(datos => {
                 
                if (datos.exito) {
                    mostrarMensaje(datos.mensaje || "Usuario eliminado", "ok");
                    cargarUsuarios();
                } else {
                    mostrarMensaje(datos.mensaje || "No se pudo eliminar", "error");
                }
            })
            .catch(error => {
                console.error("Error al borrar usuario:", error);
                mostrarMensaje("No se pudo eliminar el usuario.", "error");
            });
    }
});
 
// Copia texto al portapapeles (navigator.clipboard solo existe en HTTPS o localhost; si no, usa el método antiguo)
async function copiarAlPortapapeles(texto) {
    try {
        await navigator.clipboard.writeText(texto);
        return true;
    } catch (e) {
        const ta = document.createElement("textarea");
        ta.value = texto;
        ta.style.position = "fixed";
        ta.style.opacity = "0";
        document.body.appendChild(ta);
        ta.select();
        let ok = false;
        try { ok = document.execCommand("copy"); } catch (e2) { ok = false; }
        document.body.removeChild(ta);
        return ok;
    }
}

const btnGenerarCodigo = document.getElementById("btnGenerarCodigo");
 
btnGenerarCodigo.addEventListener("click", () => {
    btnGenerarCodigo.disabled = true;
 
    fetch("./php/generarCodigo.php", {
        method: "POST"
    })
        .then(res => res.json())
        .then(datos => {
            if (datos.exito) {
                Swal.fire({
                    icon: "success",
                    title: "Código creado",
                    html: `<h2 style="letter-spacing:3px;">${datos.codigo}</h2>
                           <p>Compártelo con el instructor.</p>`,
                    showDenyButton: true,
                    denyButtonText: "Copiar código",
                    denyButtonColor: "#6e7881",
                    confirmButtonText: "Aceptar",
                    // El botón "Copiar" no cierra la alerta: se copia y se avisa en el mismo botón
                    preDeny: () => {
                        copiarAlPortapapeles(datos.codigo).then(ok => {
                            const btn = Swal.getDenyButton();
                            if (!btn) return;
                            btn.textContent = ok ? "¡Copiado!" : "No se pudo copiar";
                            setTimeout(() => { btn.textContent = "Copiar código"; }, 2000);
                        });
                        return false; // mantiene abierta la alerta
                    }
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: datos.mensaje || "No se pudo generar el código"
                });
            }
        })
        .catch(error => {
            
            console.error("Error al generar código:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "No se pudo conectar con el servidor"
            });
        })
        .finally(() => {
            btnGenerarCodigo.disabled = false;
        });
});
 
// ---- Bloquear / desbloquear usuario ----
document.addEventListener("click", async (event) => {
    const boton = event.target.closest("button[data-bloquear-id]");
    if (!boton) return;
 
    const id = boton.dataset.bloquearId;
    const bloquear = boton.dataset.bloquear === "1"; // true = bloquear, false = desbloquear
 
    // 1) Alerta de confirmación
    const confirmacion = await Swal.fire({
        icon: "warning",
        title: bloquear ? "¿Estás seguro de bloquear a este usuario?" : "¿Desbloquear a este usuario?",
        text: bloquear
            ? "No podrá iniciar sesión hasta que lo desbloquees."
            : "Podrá volver a iniciar sesión.",
        showCancelButton: true,
        confirmButtonText: bloquear ? "Sí, bloquear" : "Sí, desbloquear",
        cancelButtonText: "Cancelar",
        confirmButtonColor: bloquear ? "#d33" : "#7ed957"
    });
 
    if (!confirmacion.isConfirmed) return;
 
    // 2) Se le avisa al PHP
    const form = new FormData();
    form.append("id", id);
    form.append("bloquear", bloquear ? "1" : "0");
 
    try {
        const res = await fetch("./php/BloquearUsuario.php", { method: "POST", body: form });
        const datos = await res.json();
 
        if (datos.exito) {
            // 3) Alerta de éxito
            await Swal.fire({
                icon: "success",
                title: bloquear ? "Usuario bloqueado exitosamente" : "Usuario desbloqueado exitosamente",
                confirmButtonText: "Aceptar"
            });
            cargarUsuarios(); // recarga la tabla para que el botón cambie
        } else {
            Swal.fire({ icon: "error", title: "Error", text: datos.mensaje || "No se pudo completar la acción" });
        }
    } catch (error) {
        console.error("Error al bloquear usuario:", error);
        Swal.fire({ icon: "error", title: "Error", text: "No se pudo conectar con el servidor" });
    }
});