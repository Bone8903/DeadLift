const div = document.getElementById("div")
const f = document.getElementById("loguearse")

const labelMail = document.getElementById('labelMail')
const labelPassword = document.getElementById('labelPassword')

labelMail.innerHTML = `
Mail`

labelPassword.innerHTML = `
Contraseña`


f.addEventListener("submit", (e) => {

    e.preventDefault()

    let form = new FormData(f)

    fetch("./php/loguearseAdmin.php", {
        method: "POST",
        body: form
    })

        .then(res => res.json())
        .then(datos => {

            if (datos.exito) {
                if (datos.r == "admin") {
                    location.href = "./PanelAdmin.html"

                } else {
                    div.innerHTML = `<h3> ${datos.mensaje} </h3>`
                }
            } else {
                div.innerHTML = `<h3> ${datos.mensaje} </h3>`
            }
        })
        .catch(error => {
            console.error("Error en fetch:", error)
            div.innerHTML = "<h3>Error al iniciar sesión</h3>"
            alert("Falle pana :(.")
        })
})



//Marquee
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


