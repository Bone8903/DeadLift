const tabla = document.getElementById("tablaEjercicios");
const tablaRutinas = document.getElementById("tablaRutinas");
const salida = document.getElementById("salida");
const crearEjercicio =document.getElementById("crearEjercicio");
const crearRutina=document.getElementById("crearRutina");
 
 
document.addEventListener("DOMContentLoaded", () => { 
    fetch("./php/check.php")// le pregunta a php si hay una sesion inicializada y que rol tiene el usuario 
        .then(res => res.json())
        .then(datos => {
          
            if (!datos.exito) { //si no hay session, lo devuelve a index
                location.href = "./../index.html";
                return;
            }
            if (datos.Rol === "cliente") { //si es cliente, lo envie a cliente.html
                location.href = "../cliente.html";
                return;
            }
            cargarEjercicios(); //usa la funcion cargarEjercicios
            cargarRutina(); //usa la funcion cargarRutina
            cargarOpcionesMusculos(); //llena el <select> con el arreglo de musculo.js
            cargarClientes(); //llena el <select> "asignarUsuario" con los usuarios de rol cliente
        })
        .catch(error => { //agarra un error
            console.error("Error en fetch:", error);
            alert("No se pudo verificar la sesión.");
        });

    });
 
function cargarClientes(){ //pide al PHP los usuarios con rol "cliente" y los pone como opciones del <select> de asignar rutina

    const select = document.getElementById("asignarUsuario");
    if (!select) return;

    fetch("php/obtenerCliente.php")
    .then(res => res.json())
    .then(datos => {

        select.innerHTML = "";

        //opcion inicial, no elegible. Los dos "true" (defaultSelected y selected) hacen que crearRutina.reset() vuelva a esta opcion
        select.appendChild(new Option("Seleccione un Cliente", "", true, true));
        select.options[0].disabled = true;

        if (!Array.isArray(datos)) { //si el PHP devolvio un error, no hay lista que dibujar
            select.options[0].text = "No se pudieron cargar los clientes";
            return;
        }
        if (datos.length === 0) {
            select.options[0].text = "No hay clientes registrados";
            return;
        }

        datos.forEach(c => {
            //Option(texto visible, value): el value es el Id_usuario que se guardara en Cliente_rutina
            select.appendChild(new Option(`${decodificar(c.Nombre)} ${decodificar(c.Apellido)} (${c.Mail})`, c.Id_usuario));
        });
    })
    .catch(error => {
        console.error("Error al cargar los clientes:", error);
        select.innerHTML = "";
        select.appendChild(new Option("No se pudieron cargar los clientes", "", true, true));
    });
}

//las tres funciones que estan abajo permiten crear un ejercicio, rutina o musculo

crearEjercicio.addEventListener("submit", (e)=> {
    e.preventDefault()

    //se arma el FormData con los campos del formulario (nombre, descripcion, notas, imagen)
    const form = new FormData(crearEjercicio);

    //qSe le suma un "musculos[]" por cada chip marcado, permitiendo mas de una seleccion
    document.querySelectorAll("#listaMusculos .tag--activo").forEach(chip => {
        form.append("musculos[]", chip.dataset.nombre);
    });

    // y ahora se envía ese resultado al php
    fetch("php/crearEjercicio.php", {
        method: "POST",
        body: form
    })
    .then(res => res.json())
    .then(datos => {

        if (datos.exito) {
            crearEjercicio.reset();
            document.getElementById("listaMusculos").innerHTML = ""; //quita los chips de musculos del ejercicio anterior
            salida.style.display = "none";
            cargarEjercicios();

            Swal.fire({ icon: "success", title: datos.mensaje, showConfirmButton: false, timer: 1500 });
        } else {
            Swal.fire({ icon: "error", title: datos.mensaje });
            
        }
    })
    .catch(error => {
        console.error("Error al crear el ejercicio:", error);
        alert("No se pudo guardar el ejercicio.");
    })
})



const DIAS_SEMANA = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"];
const diasRutina = document.getElementById("diasRutina"); //contenedor donde se dibujan las tarjetas de día
let ejerciciosDisponibles = []; //lista de ejercicios de la base; la llena cargarEjercicios() y alimenta los <select> de cada fila

function decodificar(texto) { //los textos se guardan con htmlspecialchars (&amp;, &quot;...): esto los vuelve a texto normal para mostrarlos en <option>
    const aux = document.createElement("textarea");
    aux.innerHTML = texto ?? "";
    return aux.value;
}

//

function agregarDia() {
    const usados = [...diasRutina.querySelectorAll(".dia-card__nombre")].map(s => s.value);
    const libre = DIAS_SEMANA.find(d => !usados.includes(d)); //primer día de la semana que todavía no se usó
    if (!libre) {
        Swal.fire({ icon: "info", title: "Ya agregaste todos los días de la semana" });
        return;
    }

    const tarjeta = document.getElementById("plantillaDia").content.firstElementChild.cloneNode(true);
    const select = tarjeta.querySelector(".dia-card__nombre");
    DIAS_SEMANA.forEach(d => select.appendChild(new Option(d, d)));
    select.value = libre;

    diasRutina.appendChild(tarjeta);
    agregarSlot(tarjeta); //cada día nace con una fila vacía lista para completar
    actualizarOpcionesDias();
}

function actualizarOpcionesDias() { //deshabilita en cada <select> los días que ya eligió otra tarjeta, así no se repiten
    const selects = [...diasRutina.querySelectorAll(".dia-card__nombre")];
    const usados = selects.map(s => s.value);
    selects.forEach(s => {
        [...s.options].forEach(o => { o.disabled = o.value !== s.value && usados.includes(o.value); });
    });
}

// ---------- Filas de ejercicio (slots) ----------

function agregarSlot(tarjeta) {
    const fila = document.getElementById("plantillaSlot").content.firstElementChild.cloneNode(true);
    llenarSelectEjercicio(fila.querySelector(".slot__select"));

    const detalle = document.createElement("tr"); //Es la funcion que permita mostrar o ocultgar el ejercicio dentro de la rutina
    detalle.className = "slot-detalle oculto";
    const celda = document.createElement("td");
    celda.colSpan = 5;
    detalle.appendChild(celda);

    const cuerpo = tarjeta.querySelector("tbody");
    cuerpo.append(fila, detalle);
    return fila;
}

function llenarSelectEjercicio(select) { //crea las <option> con los ejercicios de la base, conservando la elección si ya había una
    const anterior = select.value;
    select.innerHTML = "";

    const primera = new Option(ejerciciosDisponibles.length ? "Selecciona un ejercicio" : "No hay ejercicios creados", "");
    select.appendChild(primera);

    ejerciciosDisponibles.forEach(e => {
        select.appendChild(new Option(decodificar(e.Nombre), e.Id_ejercicio)); //Option(texto visible, value)
    });

    if ([...select.options].some(o => o.value === anterior)) select.value = anterior;
}

function actualizarSelectsEjercicio() { //se llama cuando cargarEjercicios() trae (o vuelve a traer) la lista
    diasRutina.querySelectorAll(".slot__select").forEach(llenarSelectEjercicio);
    diasRutina.querySelectorAll(".slot").forEach(pintarDetalle);
}

function pintarDetalle(fila) { //completa la fila desplegable con los datos del ejercicio elegido
    const celda = fila.nextElementSibling.firstElementChild;
    celda.textContent = "";

    const id = fila.querySelector(".slot__select").value;
    const ej = ejerciciosDisponibles.find(e => String(e.Id_ejercicio) === id);
    if (!ej) {
        celda.textContent = "Elige un ejercicio para ver sus detalles.";
        return;
    }

    const lineas = [
        ["Descripción", ej.Descripcion_ejercicio],
        ["Músculos", ej.Nombre_musculo],
        ["Nota del ejercicio", ej.Nota_adicional]
    ];
    lineas.forEach(([titulo, texto]) => {
        if (!texto) return;
        const p = document.createElement("p");
        const b = document.createElement("strong");
        b.textContent = titulo + ": ";
        p.append(b, decodificar(texto)); //textContent/append: no interpreta HTML
        celda.appendChild(p);
    });
    if (!celda.children.length) celda.textContent = "Este ejercicio no tiene más detalles.";
}

// ---------- Eventos del formulario ----------

crearRutina.addEventListener("click", (e) => {

    if (e.target.closest("#btnAgregarDia")) {
        agregarDia();
        return;
    }

    const btnSlot = e.target.closest(".btn-agregar-slot");
    if (btnSlot) {
        agregarSlot(btnSlot.closest(".dia-card")).querySelector(".slot__select").focus();
        return;
    }

    const quitarDia = e.target.closest(".dia-card__quitar");
    if (quitarDia) {
        const tarjeta = quitarDia.closest(".dia-card");
        const conDatos = [...tarjeta.querySelectorAll(".slot__select")].some(s => s.value !== "");
        if (conDatos && !confirm("¿Quitar este día y sus ejercicios?")) return;
        tarjeta.remove();
        actualizarOpcionesDias();
        return;
    }

    const quitarSlot = e.target.closest(".slot__quitar");
    if (quitarSlot) {
        const fila = quitarSlot.closest(".slot");
        fila.nextElementSibling.remove(); //también se va su fila de detalle
        fila.remove();
        return;
    }

    const expandir = e.target.closest(".dia-card__expandir");
    if (expandir) {
        const fila = expandir.closest(".slot");
        const detalle = fila.nextElementSibling;
        const visible = detalle.classList.toggle("oculto") === false;
        if (visible) pintarDetalle(fila);
        expandir.setAttribute("aria-expanded", visible);
        expandir.textContent = visible ? "\u25B2" : "\u25BC"; //▲ abierto / ▼ cerrado
    }
});

crearRutina.addEventListener("change", (e) => {

    if (e.target.matches(".dia-card__nombre")) actualizarOpcionesDias();
    if (e.target.matches(".slot__select")) {
        const fila = e.target.closest(".slot");
        fila.classList.remove("slot--error");
        if (!fila.nextElementSibling.classList.contains("oculto")) pintarDetalle(fila); //si el detalle está abierto, se actualiza
    }

});

// ---------- Validación y envío ----------

function marcarError(elemento, mensaje) { //resalta la fila con problema, la muestra en pantalla y avisa
    if (elemento) {
        elemento.classList.add("slot--error");
        elemento.scrollIntoView({ behavior: "smooth", block: "center" });
    }
    Swal.fire({ icon: "error", title: mensaje });
    return null;
}

function recolectarDias() { //lee el DOM y arma el arreglo de días; si algo está mal avisa y devuelve null
    crearRutina.querySelectorAll(".slot--error").forEach(f => f.classList.remove("slot--error"));

    const tarjetas = [...diasRutina.querySelectorAll(".dia-card")];
    if (!tarjetas.length) return marcarError(null, "Agrega al menos un día con ejercicios");

    const dias = [];
    for (const tarjeta of tarjetas) {
        const nombreDia = tarjeta.querySelector(".dia-card__nombre").value;
        const filas = [...tarjeta.querySelectorAll(".slot")];
        if (!filas.length) return marcarError(tarjeta, `El día ${nombreDia} no tiene ejercicios. Agrega uno o quita el día`);

        const vistos = new Set();
        const ejercicios = [];
        for (const fila of filas) {
            const id = fila.querySelector(".slot__select").value;
            if (id === "") return marcarError(fila, `Elige un ejercicio en cada fila del día ${nombreDia} (o quita la fila vacía)`);
            if (vistos.has(id)) return marcarError(fila, `Ese ejercicio ya está en el día ${nombreDia}`);
            vistos.add(id);

            const series = fila.querySelector(".slot__series").value.trim();
            const reps = fila.querySelector(".slot__reps").value.trim();
            for (const [valor, etiqueta] of [[series, "Series"], [reps, "Repeticiones"]]) {
                if (valor === "") continue; //son opcionales
                const n = Number(valor);
                if (!Number.isInteger(n) || n < 1 || n > 999) {
                    return marcarError(fila, `${etiqueta} tiene que ser un número entero entre 1 y 999`);
                }
            }

            ejercicios.push({
                id: id,
                series: series,
                repeticiones: reps,
                notas: fila.querySelector(".slot__notas").value.trim()
            });
        }
        dias.push({ nombre: nombreDia, ejercicios: ejercicios });
    }
    return dias;
}

function reiniciarFormularioRutina() { //deja el formulario como recién cargado: sin datos y con un día vacío
    crearRutina.reset();
    diasRutina.innerHTML = "";
    agregarDia();
}

crearRutina.addEventListener("submit", (e) => { //se ejecuta cuando se envia el formulario de rutina, recibiendo la id del asignar usuario, los nombres, descripciones, series, repeticiones y notas que tendra la rutina :D
    e.preventDefault();

    if (document.getElementById("nombreRutina").value.trim() === "") {
        Swal.fire({ icon: "error", title: "El nombre de la rutina es obligatorio" });
        return;
    }

    const selectCliente = document.getElementById("asignarUsuario");
    if (selectCliente.value === "") { //la rutina se asigna si o si a un cliente
        Swal.fire({ icon: "error", title: "Selecciona el cliente al que se le asigna la rutina" });
        selectCliente.focus();
        return;
    }

    const dias = recolectarDias();
    if (!dias) return; //recolectarDias ya mostró el error

    const form = new FormData(crearRutina); //trae Nombre_rutina, Horario, Descripcion_rutina e Id_cliente (el <select> de clientes) por sus "name"
    form.append("dias", JSON.stringify(dias));

    const boton = document.getElementById("btnPublicarRutina");
    boton.disabled = true; //evita enviar dos veces si se hace doble click

    fetch("php/crearRutina.php", { //El fetch devuelve la informacion al js
        method: "POST",
        body: form
    })
        .then(res => res.json())
        .then(datos => {

            console.log(datos);
            

            if (datos.exito) {
                reiniciarFormularioRutina();
                cargarRutina(); //vuelve a pedir la lista para que aparezca la rutina nueva
                Swal.fire({ icon: "success", title: datos.mensaje, showConfirmButton: false, timer: 1500 });
            } else {
                Swal.fire({ icon: "error", title: datos.mensaje });
            }
        })
        .catch(error => { //agarra los errores
            console.error("Error al crear la rutina:", error);
            Swal.fire({ icon: "error", title: "No se pudo guardar la rutina" });
        })
        .finally(() => { boton.disabled = false; });
});

reiniciarFormularioRutina(); //primer día vacío al cargar la página

 
function agregarMusculo() { //toma el musculo elegido en el <select> y lo agrega como chip al ejercicio
    const select = document.getElementById("nuevoMusculo");
    const btnAgregar = document.getElementById("btnGuardarMusculo");
    const lista = document.getElementById("listaMusculos");
    if (!select || !lista) return;

    const nombre = select.value;
    if (nombre === "") {
        Swal.fire({ icon: "warning", title: "Selecciona un músculo" });
        return;
    }

    //no se permite el mismo musculo dos veces en el mismo ejercicio
    const repetido = [...lista.querySelectorAll(".tag")].some(c => c.dataset.nombre === nombre);
    if (repetido) {
        Swal.fire({ icon: "info", title: "Ese músculo ya está agregado" });
        return;
    }

    const chip = document.createElement("span");
    chip.className = "tag tag--seleccionable tag--activo"; //nace marcado: el submit envia los chips con tag--activo
    chip.dataset.nombre = nombre;

    const texto = document.createElement("span");
    texto.textContent = nombre; //textContent: no interpreta HTML

    const x = document.createElement("button"); //la "x" quita el chip (solo de este formulario, no toca la base de datos)
    x.type = "button"; //sin type seria un boton submit dentro del form
    x.className = "tag__remove";
    x.setAttribute("aria-label", "Quitar músculo " + nombre);
    x.innerHTML = "&times;";

    chip.append(texto, x);
    lista.appendChild(chip);

    select.selectedIndex = 0; //vuelve a "Selecciona un músculo"
    select.classList.add("oculto"); //vuelve a esconder el select y el boton
    if (btnAgregar) btnAgregar.classList.add("oculto");
}

// esto de aqui es muy importante para ocultar y mostrar el input de musculos, permitiendo que el estilo (.oculto) se active

document.addEventListener("click", (e) => {
 
    // 1) Boton "+ Agregar": muestra u oculta el input y el boton Guardar
    if (e.target.closest(".btn-agregar")) {
        const input = document.getElementById("nuevoMusculo");
        const btnGuardar = document.getElementById("btnGuardarMusculo");
        if (input) input.classList.toggle("oculto");
        if (btnGuardar) btnGuardar.classList.toggle("oculto");
        if (input && !input.classList.contains("oculto")) input.focus();
        return;
    }
 
    // 2) Boton "Añadir": agrega el musculo elegido como chip
    if (e.target.closest("#btnGuardarMusculo")) {
        agregarMusculo();
        return;
    }
 
    // 3) "x" de un musculo: quita el chip (va antes de la seleccion para que no lo des/marque)
    const botonX = e.target.closest(".tag__remove");
    if (botonX) {
        botonX.closest(".tag").remove();
        return;
    }
 
    // 4) Click en un chip: lo marca/desmarca (solo los marcados se envian al guardar el ejercicio)
    const chip = e.target.closest(".tag--seleccionable");
    if (chip) chip.classList.toggle("tag--activo");
});
 
document.addEventListener("keydown", (e) => { //Enter dentro del input guarda el musculo en vez de enviar el ejercicio
    if (e.key === "Enter" && e.target.id === "nuevoMusculo") {
        e.preventDefault();
        agregarMusculo();
    }
});


// Estas tres cosas abajo son las funciones que permiten cargar las diferentes tablas como musculos, rutina y ejercicio

function cargarOpcionesMusculos() { //crea una <option> por cada musculo del arreglo "musculos" (musculo.js)
    const select = document.getElementById("nuevoMusculo");
    if (!select) return;

    if (typeof musculos === "undefined") { //si musculo.js no se cargo antes que este archivo
        console.error("No se encontró el arreglo 'musculos'. Revisá que musculo.js esté antes de instructor.js en el HTML.");
        return;
    }

    select.innerHTML = ""; //limpia por si se vuelve a llamar

    const primera = new Option("Selecciona un músculo", ""); //opcion inicial, no elegible
    primera.disabled = true;
    primera.selected = true;
    select.appendChild(primera);

    musculos.forEach(musculo => {
        select.appendChild(new Option(musculo, musculo)); //Option(texto visible, value)
    });
}
 
function cargarEjercicios(){ //esta es la funcion que se encarga de cargar los ejercicios
 
    fetch("php/obtenerEjercicios.php") //El fetch devuelve la informacion al js
    .then(res => res.json())
    .then(datos => {
 
 tabla.innerHTML ="" //limpia la tabla
 
if (!Array.isArray(datos)) return; //si el PHP devolvio un error, no hay lista que dibujar
 
        ejerciciosDisponibles = datos; //se guarda la lista para los <select> del formulario de rutina
        actualizarSelectsEjercicio();

datos.forEach(ejercicio => {
 
    const imagen = `<img class="ejercicio-card__img"
    src="./img/ejercicio/${ejercicio.Id_e}.jpg"
    alt="${ejercicio.Nombre}"
    onerror="this.outerHTML='<div class=\\'ejercicio-card__img ejercicio-card__img--vacia\\'>Sin imagen</div>'">`;
    
         tabla.innerHTML += `
        <div class="ejercicio-card">
            ${imagen}
            <div class="ejercicio-card__body">
                <h4 class="ejercicio-card__nombre">${ejercicio.Nombre}</h4>
                <p class="ejercicio-card__descripcion">${ejercicio.Descripcion_ejercicio}</p>
                <p class="ejercicio-card__notas">Músculo: ${ejercicio.Nombre_musculo ?? "sin músculo"}</p>
            </div>
            <div class="ejercicio-card__acciones">
                <button type="button" class="boton boton--pequeno boton--borrar" onclick="eliminarEjercicio(${ejercicio.Id_ejercicio})">Borrar</button>
            </div>
        </div>
    `;
    }) //muestra todos los ejercicios con su musculo
 
 })
}
 
function cargarRutina(){ //esta funcion se encarga de mostrar todas las rutinas
    if (!tablaRutinas) return; //el HTML todavia no tiene un elemento con id="tablaRutinas"
    fetch("./php/obtenerRutina.php")
 
    .then(res => res.json())
    .then(datos=> {
        if (!Array.isArray(datos)) return; //si el PHP devolvio un error, no hay lista que dibujar
 
tablaRutinas.innerHTML="" //limpia la tabla
    datos.forEach(rutina => {
         
        tablaRutinas.innerHTML +=
         `
         <div class="rutina-item">
             <div class="rutina-item__cabecera">
                 <div class="rutina-item__texto">
                     <h4 class="rutina-item__nombre">${rutina.Nombre_rutina}</h4>
                     <p class="rutina-item__descripcion">${rutina.Descripcion_rutina ?? ""}</p>
                 </div>
                 <div class="rutina-item__acciones">
                     <button type="button" class="btn-ver-rutina" data-id="${rutina.Id_rutina}" aria-expanded="false">Ver</button> <!-- lo maneja rutinasCreadas.js -->
                     <button type="button" class="boton boton--pequeno boton--borrar" onclick="eliminarRutina(${rutina.Id_rutina})">Borrar</button>
                 </div>
             </div>
         </div>
     `     
    }) //muestra las tarjetas con sus valores
})
}
 

// Estas tres funciones permiten borrar las rutina, los ejercicios y los musculos

function eliminarRutina(id){ //recibe el id de la rutina desde el boton
    if (!confirm("¿Está seguro de eliminar esta rutina?")) return; //ventana de Aceptar/Cancelar
 
    const form = new FormData();
    form.append("id", id); //borrarRutina.php lo lee con $_POST['id']
 
    fetch("./php/borrarRutina.php", { method: "POST", body: form })
        .then(res => res.json())
        .then(datos => {
            Swal.fire({
                icon: datos.exito ? "success" : "error", //tilde verde solo si el PHP devolvio exito:true
                title: datos.mensaje,
                showConfirmButton: false,
                timer: 1500
            });
            cargarRutina(); //vuelve a pedir la lista para que se vea el cambio
        })
        .catch(error => {
            console.error("Error al eliminar la rutina:", error);
            Swal.fire({ icon: "error", title: "No se pudo eliminar la rutina" });
        });
}
 
function eliminarEjercicio(id){ //recibe el id del ejercicio desde el boton
 
    r = confirm("Esta seguro de eliminar el ejercicio") //pide confirmacion antes de borrar
 
    if(r){
 
        fetch ("./php/borrarEjercicio.php?id="+id) //manda el id por la URL; si aca llega "undefined" es porque el boton uso un nombre de campo que no existe
        .then(res => res.json())
        .then(datos => {
    Swal.fire({
        position: "top-end", //aparece arriba a la derecha
        icon: datos.exito ? "success" : "error", //tilde verde solo si el PHP devolvio exito:true
        title: datos.mensaje,
        showConfirmButton: false,
        timer: 1500
        });
        cargarEjercicios(); //recarga la lista; si se borro, ya no aparece
    })
    }
 
 
}