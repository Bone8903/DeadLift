
//Este archivo sirve para poder llamar a las 

(function () {

    const tablaRutinas = document.getElementById("tablaRutinas");
    if (!tablaRutinas) return;


    function decodificar(texto) { //los textos se guardan con htmlspecialchars (&amp;, &quot;...): esto los vuelve a texto normal
        const aux = document.createElement("textarea");
        aux.innerHTML = texto ?? "";
        return aux.value;
    }

    function crear(etiqueta, clase, texto) { //crea un elemento; el texto va con textContent, que no interpreta HTML
        const e = document.createElement(etiqueta);
        if (clase) e.className = clase;
        if (texto !== undefined && texto !== null) e.textContent = texto; //este if devuelve un texto de error si hay algo erroneo o vacio
        return e;
    }

    function valorOGuion(valor) { //series, repeticiones y notas son opcionales: si no hay dato se muestra "-"
        return valor === null || valor === undefined || valor === "" ? "-" : decodificar(String(valor));
    }

    
    // Permite la creacion de las rutinas

    function celda(contenido, clase, etiqueta) { //"etiqueta" se usa en celular, donde cada fila se apila y cada dato lleva su título
        const td = crear("td", clase);
        if (etiqueta) td.dataset.label = etiqueta;
        if (contenido instanceof Node) td.appendChild(contenido);
        else td.textContent = contenido;
        return td;
    }

    function botonToggle() { //el "+" / "−" que muestra u oculta las columnas extra
        const b = crear("button", "vr-toggle", "+");
        b.type = "button";
        b.title = "Mostrar detalle";
        b.setAttribute("aria-expanded", "false");
        return b;
    }

    function armarTablaDia(ejercicios) {
        const tabla = crear("table", "vr-tabla");

        // Encabezados: mismas columnas y mismo orden que la pantalla del cliente
        const thead = crear("thead");
        const cab = crear("tr");
        const thEjercicio = crear("th", null, "Ejercicio");
        thEjercicio.appendChild(botonToggle());
        cab.appendChild(thEjercicio);
        cab.appendChild(crear("th", "vr-extra", "Imagen"));
        cab.appendChild(crear("th", "vr-extra", "Descripción ejercicio"));
        cab.appendChild(crear("th", "vr-extra", "Notas adicionales"));
        cab.appendChild(crear("th", null, "Series"));
        cab.appendChild(crear("th", null, "Repeticiones"));
        cab.appendChild(crear("th", null, "Notas"));
        thead.appendChild(cab);
        tabla.appendChild(thead);

        const tbody = crear("tbody");
        ejercicios.forEach(ej => {
            const fila = crear("tr");

            fila.appendChild(celda(decodificar(ej.nombre)));

            // Imagen (si el ejercicio no tiene, se avisa)
            if (ej.imagen) {
                const img = crear("img");
                img.src = ej.imagen;
                img.alt = decodificar(ej.nombre);
                fila.appendChild(celda(img, "vr-extra", "Imagen"));
            } else {
                fila.appendChild(celda("Sin imagen", "vr-extra", "Imagen"));
            }

            // Descripción del ejercicio + los músculos que trabaja
            const desc = crear("div");
            desc.appendChild(document.createTextNode(valorOGuion(ej.descripcion)));
            if (ej.musculos) desc.appendChild(crear("span", "vr-musculos", "Músculos: " + decodificar(ej.musculos)));
            fila.appendChild(celda(desc, "vr-extra", "Descripción ejercicio"));

            fila.appendChild(celda(valorOGuion(ej.nota_ejercicio), "vr-extra", "Notas adicionales"));
            fila.appendChild(celda(valorOGuion(ej.series), null, "Series"));
            fila.appendChild(celda(valorOGuion(ej.repeticiones), null, "Repeticiones"));
            fila.appendChild(celda(valorOGuion(ej.notas), null, "Notas"));

            tbody.appendChild(fila);
        });
        tabla.appendChild(tbody);

        const contenedor = crear("div", "vr-scroll"); //permite deslizar la tabla de costado en pantallas chicas
        contenedor.appendChild(tabla);
        return contenedor;
    }

    function armarRutina(datos) {
        const tarjeta = crear("div", "vr-tarjeta");
        const r = datos.rutina;

        const header = crear("div", "vr-header");
        header.appendChild(crear("h4", "vr-titulo", "Rutina"));
        header.appendChild(crear("div", "vr-nombre", decodificar(r.nombre)));
        tarjeta.appendChild(header);

        const horario = crear("div", "vr-campo");
        horario.appendChild(crear("span", "vr-label", "Horario"));
        horario.appendChild(crear("div", "vr-texto", r.horario || "-"));
        tarjeta.appendChild(horario);

        const descripcion = crear("div", "vr-campo");
        descripcion.appendChild(crear("span", "vr-label", "Descripcion"));
        descripcion.appendChild(crear("div", "vr-texto", valorOGuion(r.descripcion)));
        tarjeta.appendChild(descripcion);

        if (!datos.dias.length) {
            tarjeta.appendChild(crear("p", "vr-mensaje", "Esta rutina todavía no tiene ejercicios."));
        }

        datos.dias.forEach(dia => {
            const bloque = crear("div", "vr-dia");
            bloque.appendChild(crear("div", "vr-dia__nombre", dia.nombre));
            bloque.appendChild(armarTablaDia(dia.ejercicios));
            tarjeta.appendChild(bloque);
        });

        return tarjeta;
    }

    // ---------- Abrir / cerrar ----------

    function vistaAbierta(item) { //el contenedor de la rutina desplegada dentro de la tarjeta, si existe
        return item.querySelector(":scope > .vr-fila");
    }

    function cerrarVista(boton, item) {
        const vista = vistaAbierta(item);
        if (vista) vista.remove();
        boton.textContent = "Ver";
        boton.setAttribute("aria-expanded", "false");
    }

    function abrirVista(boton, item) {
        // Contenedor nuevo al final de la tarjeta, mientras carga muestra un aviso
        const vista = crear("div", "vr-fila");
        vista.appendChild(crear("p", "vr-mensaje", "Cargando rutina..."));
        item.appendChild(vista);

        boton.textContent = "Ocultar";
        boton.setAttribute("aria-expanded", "true");

        fetch("./php/verRutina.php?id=" + encodeURIComponent(boton.dataset.id))
            .then(res => res.json())
            .then(datos => {
                if (!vista.isConnected) return; //si la cerraron mientras cargaba, no se dibuja nada
                vista.textContent = "";
                if (!datos.exito) {
                    vista.appendChild(crear("p", "vr-mensaje", datos.mensaje || "No se pudo cargar la rutina"));
                    return;
                }
                vista.appendChild(armarRutina(datos));
            })
            .catch(error => {
                console.error("Error al cargar la rutina:", error);
                if (!vista.isConnected) return;
                vista.textContent = "";
                vista.appendChild(crear("p", "vr-mensaje", "No se pudo cargar la rutina"));
            });
    }

    // Un solo listener sobre la lista: las tarjetas se dibujan de nuevo cada vez que cargarRutina() recarga la lista
    tablaRutinas.addEventListener("click", (e) => {

        const ver = e.target.closest(".btn-ver-rutina");
        if (ver) {
            const item = ver.closest(".rutina-item");
            if (vistaAbierta(item)) cerrarVista(ver, item);
            else abrirVista(ver, item);
            return;
        }

        // "+" / "−": muestra u oculta las columnas extra de TODOS los días de esa rutina
        const toggle = e.target.closest(".vr-toggle");
        if (toggle) {
            const tarjeta = toggle.closest(".vr-tarjeta");
            const visible = tarjeta.classList.toggle("vr-detalle");
            tarjeta.querySelectorAll(".vr-toggle").forEach(b => {
                b.textContent = visible ? "−" : "+";
                b.title = visible ? "Ocultar detalle" : "Mostrar detalle";
                b.setAttribute("aria-expanded", visible);
            });
        }
    });

})();
