const div = document.getElementById("div")
const f = document.getElementById("loguearse")

const labelMail = document.getElementById('labelMail')
const labelPassword = document.getElementById('labelPassword')

labelMail.innerHTML = /*estas son inyecciones de datos de parte del js a html*/ ` 
Mail * `

labelPassword.innerHTML = /*estas son inyecciones de datos de parte del js a html*/ `
Contraseña * `

f.addEventListener("submit", (e) => { //escucha el evento submit del formulario y ejecuta la funcion que se encuentra adentro de los parentesis
    
    e.preventDefault() //previene que el formulario se envie de forma tradicional y recargue la pagina

    let form = new FormData(f)
    
    fetch("./php/loguearse.php", { //llama a al archivo loguearse.php para que revise si el usuario esta registrado y que lo mande a su "hub"
        method: "POST",
        body: form
    })



        .then(res => res.json()) // recibe la informacion de parte del php
        .then(datos => { //esta informacion de php es almacenada en datos
            
            const rol = datos.Rol ?? datos.r; /*rol se crea con la idea en mente de que puede ser llamada datos.Rol (y los signos de ? dan a entender que puede ser null) 
                                                o tambien datos.r, sirven para que hayan multiples maneras de escribirlo*/

            if (rol) { // si la condicion existe, entonces entra al if
                if (datos.exito == true && rol === "cliente") { //si datos.exito devuelve true y el rol es "cliente", hace lo de adentro del if
                    location.href = "./cliente.html" //envia al usuario a cliente.html

                    
                } else if (datos.exito == true && rol == "instructor") { //si datos.exito devuelve true y el rol es "instructor", hace lo de adentro del if
                    location.href = "./instructor/instructor.html" //envia al usuario a instructor.html
                    
                }
            } else { //si ninguna de las anteriores se cumple, entra aqui
                div.innerHTML = `<h3> ${datos.mensaje} </h3>`
            }
        })
}
)

//las animaciones que se encuentran en el html
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


