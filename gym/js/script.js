// 1. Contenedor donde va a aparecer el contenido dinámico

const app = document.getElementById("app") 

// 2. Inyectamos los botones con innerHTML (en vez de escribirlos en el HTML)
app.innerHTML = `
    <button type="button" id="reg" class="boton">Registrarse</button>
    <button type="button" id="reginst" class="boton">Registrarse como Instructor</button>
    <button type="button" id="log" class="boton">Inicio de sesión</button>
`

// 3. Se crean las variables para los botones para luego agregarles eventos
const f = document.getElementById("reg") //boton registrarse como cliente
const f2 = document.getElementById("reginst") //boton registrarse como instructor
const f3 = document.getElementById("log") //loguearse como cliente o instructor

// 4. Lógica de redirección (igual que antes)

f.addEventListener("click", (e) => {
    e.preventDefault()

            location.href="registrarse.html" //la funcion de registrarse como cliente

        })

f2.addEventListener("click", (e) => {
    e.preventDefault()

            location.href="instructor/registrarseInstructor.html" //la funcion de registrarse como instructor
        })
        
f3.addEventListener("click", (e) => {
    e.preventDefault()

        location.href="loguearse.html" //la funcion de loguearse como cliente o instructor

    })

//gran parte de este codigo se encarga de armar el marquee, que es la animacion de las imagenes que se mueven de derecha a izquierda y de izquierda a derecha

function armarMarquee(idGrupo1, idGrupo2) {
    const grupo1 = document.getElementById(idGrupo1)
    const grupo2 = document.getElementById(idGrupo2)

    const imgPrueba = new Image()
    imgPrueba.src = "./img/index/vector.png"

    imgPrueba.onload = () => {
        const escala = 90 / imgPrueba.naturalHeight
        const anchoImg = imgPrueba.naturalWidth * escala
        const copias = Math.ceil(window.innerWidth / anchoImg) + 2

        let html = ""
        for (let i = 0; i < copias; i++) {
            html += `<img src="./img/index/vector.png" alt="" class="marquee__img">`
        }

        grupo1.innerHTML = html
        grupo2.innerHTML = html
    }

    imgPrueba.onerror = () => {
        console.error("No se pudo cargar vector.png")
    }
}

// Armamos las dos filas
armarMarquee("marqueeGroup1", "marqueeGroup2") // fila de abajo
armarMarquee("marqueeGroup3", "marqueeGroup4") // fila de arriba

window.addEventListener("resize", () => {
    armarMarquee("marqueeGroup1", "marqueeGroup2")
    armarMarquee("marqueeGroup3", "marqueeGroup4")
})

//ngl, wth is a marquee?


