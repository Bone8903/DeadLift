const f = document.getElementById("registro")


const labelNombre = document.getElementById('labelNombre')
const labelApellido = document.getElementById('labelApellido')
const labelMail = document.getElementById('labelMail')
const labelTelefono = document.getElementById('labelTelefono')
const labelContraseña = document.getElementById('labelContraseña')
const labelConfContraseña = document.getElementById('labelConfContraseña')
const labelCodigo = document.getElementById('labelCodigo')

labelNombre.innerHTML = `
Nombre *`

labelApellido.innerHTML = `
Apellido *`

labelMail.innerHTML = `
Mail *`

labelTelefono.innerHTML = `
Número de Teléfono `

labelContraseña.innerHTML = `
Contraseña *`

labelConfContraseña.innerHTML = `
Confirmar Contraseña *`

labelCodigo.innerHTML = `
Código *`
//esta es la inyeccion que realiza el JS a HTML

f.addEventListener("submit", (e) => { //escucha el submit del usuario y realiza lo que esta dentro de los corchetes {}
    e.preventDefault() //previene que el formulario se envie de forma tradicional y recargue la pagina

    let form = new FormData(f)

    fetch("./php/registrarseinstructor.php", { //llama a al archivo loguearse.php para que revise si el usuario esta registrado y que lo mande a su "hub"
        method: "POST",
        body: form
    })
        .then(res => res.json()) // recibe la informacion de parte del php
        .then(datos => { //esta informacion de php es almacenada en datos


            if (datos.exito === true && datos.r === "instructor") { //si datos.exito devuelve true y el rol es "instructor", hace lo de adentro del if
                location.href = "./instructor.html"//envia al usuario a intructor.html
            } else if (datos.exito === true && datos.r === "cliente") { //si datos.exito devuelve true y el rol es "cliente", hace lo de adentro del if
                location.href = "./cliente.html"//envia al usuario a cliente.html
            } else {//si ninguna de las anteriores se cumple, entra aqui y muestra que hubo un error por medio de un alerta.
                alert(datos.mensaje || "Error en el registro")
            }
        })
        .catch(error => {//agarra los errores que puedan ocurrir dentro del codigo y los redirige aqui
            console.error(error)
            alert("No se pudo conectar con el servidor")
        })
})




//Marquee
function armarMarquee(idGrupo1, idGrupo2) {
    const grupo1 = document.getElementById(idGrupo1)
    const grupo2 = document.getElementById(idGrupo2)

    const imgPrueba = new Image()
    imgPrueba.src = "../../gym/img/index/vector.png"

    imgPrueba.onload = () => {
        const escala = 90 / imgPrueba.naturalHeight
        const anchoImg = imgPrueba.naturalWidth * escala
        const copias = Math.ceil(window.innerWidth / anchoImg) + 2

        let html = ""
        for (let i = 0; i < copias; i++) {
            html += `<img src="../../gym/img/index/vector.png" alt="" class="marquee__img">`
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


