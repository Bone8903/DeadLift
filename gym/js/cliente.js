document.addEventListener("DOMContentLoaded", () => {
    fetch("./php/check.php") //el fetch llama al archivo check para verificar si el usuario tiene la sesion activa.
        .then(res => res.json()) //el fetch devuelve una promesa que se resuelve en un objeto JSON
        .then(datos => { //los datos que devuelve el fetch se guardan en la variable datos
 
            if (!datos.exito) { //si datos.exito es falso, significa que el usuario no tiene la sesion activa
                location.href = "./index.html"; // lo rederige al index.html
                return; // ESTE return es importante: sin el, el codigo de abajo sigue y llama a cargarRutina() aunque no haya sesion
            }
            if (datos.Rol === "instructor") { //si el usuario tiene la sesion activa y es instructor
                location.href = "./instructor/instructor.html"; //lo redirige al instructor.html
                return;
            }
 
            cargarRutina(); //si la sesion es valida y es cliente, carga la rutina en la tabla
        })
        .catch(error => { //esta linea agarra los errores que se puedan dar en el fetch y los muestra en la consola y en un alert
            console.error("Error en fetch:", error);
            alert("No se pudo verificar la sesión."); //aparece un cartel en la parte superior del html diciendo que no se pudo verificar la sesion
        });
    configurarToggle(); //activa el boton + / − de la tabla
});
 
function configurarToggle() { //la funcion configurarToggle se encarga de mostrar y ocultar las columnas extra de la tabla
    const tablaRutina = document.getElementById("tablaRutina");
 
    if (!tablaRutina) return; //si la pagina no tiene la tabla, no hace nada
 
    // Los botones "+" / "−" los dibuja cargarRutina() (uno por cada rutina), asi que no existen cuando carga la pagina.
    // Por eso el click se escucha en la tabla entera y se mira si lo que se apreto fue un boton con la clase "btn-toggle".
    tablaRutina.addEventListener("click", (e) => {
        if (!e.target.closest(".btn-toggle")) return;
        const visible = tablaRutina.classList.toggle("mostrar-detalle"); //agrega o quita la clase que muestra las columnas .extra
 
        tablaRutina.querySelectorAll(".btn-toggle").forEach(btn => { //se actualizan TODOS los botones (hay uno por rutina)
            btn.textContent = visible ? "−" : "+";
            btn.title = visible ? "Ocultar detalle" : "Mostrar detalle";
            btn.setAttribute("aria-expanded", visible);
        });
    });
}
function cargarRutina() { //la funcion cargarRutina se encarga de cargar la rutina del usuario en la tabla de ejercicios
    // Busca el <tbody> del HTML por su id
    const tabla = document.getElementById("tablaEjercicios");
 
    // Los encabezados de columna (Ejercicio, Series...) ahora los dibuja esta funcion debajo del titulo de cada rutina.
    // Si en el HTML todavia queda el <thead> viejo, se quita para que no aparezca otro encabezado arriba de todo.
    const theadViejo = document.querySelector("#tablaRutina thead");
    if (theadViejo) theadViejo.remove();
 
    fetch("./php/mostrarRutina.php")
        .then(res => res.json())
        .then(datos => {
            // Si el PHP devolvió un error (no un arreglo), no seguimos
            if (!Array.isArray(datos)) {
                alert(datos.mensaje || "No se pudieron cargar los ejercicios");
                return;
            }
            // Si el cliente todavia no tiene ninguna rutina asignada, se avisa en la propia tabla
            if (datos.length === 0) {
                tabla.innerHTML = `<tr><td colspan="7">Todavía no tenés una rutina asignada</td></tr>`;
                return;
            }
 
            // Cada elemento de "datos" es UN ejercicio y trae tambien los datos de su rutina.
            // Los ejercicios llegan ordenados por rutina y por dia, asi que alcanza con detectar cuando cambian
            // para insertar una fila de titulo antes de cada rutina y antes de cada dia.
            let rutinaActual = null;
            let diaActual = null;
            let html = ""; // se arma todo el HTML y se asigna una sola vez al final
            // Si las columnas extra ya estaban visibles (por ejemplo, al recargar la lista), los botones nacen como "−"
            const detalleVisible = tabla.closest("table").classList.contains("mostrar-detalle");
 
            datos.forEach(ejercicio => {
 
                // Cambio de rutina: fila de titulo con el horario y la descripcion de la rutina,
                // y justo DEBAJO la fila de encabezados de columna
                if (ejercicio.id_rutina !== rutinaActual) {
                    rutinaActual = ejercicio.id_rutina;
                    diaActual = null; // en una rutina nueva el dia vuelve a empezar
                    html += `
                    <tr class="fila-rutina">
                        <td colspan="7">Rutina: ${ejercicio.horario}${ejercicio.descripcion_rutina ? " — " + ejercicio.descripcion_rutina : ""}</td>
                    </tr>`;

                    // Las celdas van en el mismo orden que las celdas de cada ejercicio (mas abajo).
                    html += `
                    <tr class="fila-encabezado">
                        <th>
                            Ejercicio
                            <button type="button" class="btn-toggle" aria-expanded="${detalleVisible}" title="${detalleVisible ? "Ocultar detalle" : "Mostrar detalle"}">${detalleVisible ? "−" : "+"}</button>
                        </th>
                        <th class="extra">Imagen</th>
                        <th class="extra">Descripción ejercicio</th>
                        <th class="extra">Notas adicionales</th>
                        <th>Series</th>
                        <th>Repeticiones</th>
                        <th>Notas</th>
                    </tr>`;
                }

                // Cambio de dia (va FUERA del bloque de rutina: si no, solo se mostraria el primer dia de cada rutina)
                if (ejercicio.nombre_dia !== diaActual) {
                    diaActual = ejercicio.nombre_dia;
                    html += `
                    <tr class="fila-dia">
                        <td colspan="7">${diaActual}</td>
                    </tr>`;
                }

                // La fila del ejercicio. Las celdas van en el MISMO orden que los <th> del HTML.
                // Las que tienen class="extra" se ocultan/muestran con el boton.
                html += `
                    <tr>
                        <td>${ejercicio.nombre_ejercicio}${ejercicio.musculos ? `<br><small class="musculos">${ejercicio.musculos}</small>` : ""}</td>
                        <td class="extra">${ejercicio.imagen ? `<img src="${ejercicio.imagen}" alt="${ejercicio.nombre_ejercicio}" width="100">` : "No hay imagen"}</td>
                        <td class="extra">${ejercicio.descripcion_ejercicio}</td>
                        <td class="extra">${ejercicio.notas_adicionales ?? "-"}</td>
                        <td>${ejercicio.series ?? "-"}</td>
                        <td>${ejercicio.repeticiones ?? "-"}</td>
                        <td>${ejercicio.notas ?? "-"}</td>
                    </tr>`;
            });
 
            tabla.innerHTML = html; //esto reemplaza lo anterior, asi que no hace falta limpiar la tabla ni se duplican los datos
        })
        .catch(error => { //esta linea agarra los errores que se puedan dar en el fetch y los muestra en la consola
            console.error("Error al cargar la rutina:", error);
        });
}