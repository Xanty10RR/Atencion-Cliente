document.addEventListener("DOMContentLoaded", function () {
    // Crear el contenedor del preloader
    let preloader = document.createElement("div");
    preloader.id = "preloader";
    preloader.style.position = "fixed";
    preloader.style.top = "0";
    preloader.style.left = "0";
    preloader.style.width = "100%";
    preloader.style.height = "100%";
    preloader.style.backgroundColor = "white";
    preloader.style.display = "flex";
    preloader.style.justifyContent = "center";
    preloader.style.alignItems = "center";
    preloader.style.zIndex = "9999";
    
    // Agregar la imagen del logo
    let logo = document.createElement("img");
    logo.src = "img/logo.jpg";
    logo.alt = "Logo";
    logo.style.width = "200px";
    logo.style.height = "200px";
    
    preloader.appendChild(logo);
    document.body.appendChild(preloader);
    
    // Ocultar el preloader después de 2 segundos
    setTimeout(() => {
        preloader.style.transition = "opacity 0.5s ease-out";
        preloader.style.opacity = "0";
        
        setTimeout(() => {
            preloader.style.display = "none";
        }, 500);
    }, 1300);
});

const formularios = document.querySelectorAll('.clasificacion'); // Todos los grupos de estrellas

formularios.forEach(function (formulario) {
    const estrellas = formulario.querySelectorAll('.star'); // Estrellas dentro de un formulario
    estrellas.forEach(function (star, index) {
        star.addEventListener('click', function () {
            // Marcar las estrellas hasta la estrella clickeada
            estrellas.forEach((s, i) => {
                if (i <= index) {
                    s.classList.add('checked');
                } else {
                    s.classList.remove('checked');
                }
            });
        });
    });
});


document.getElementById('formulario').addEventListener('submit', function (event) {
    let todosGruposEstrellas = true;

    formularios.forEach(function (formulario) {
        const estrellasMarcadas = formulario.querySelectorAll('.star.checked');
        if (estrellasMarcadas.length === 0) {
            todosGruposEstrellas = false;
        }
    });

    if (!todosGruposEstrellas) {
        event.preventDefault();
        Swal.fire({
            icon: "error",
            title: "Falta algo..",
            text: "Por favor selecciona todas las opciones!",
        });
    } else {
        event.preventDefault();
        Swal.fire({
            title: "Gracias!",
            text: "Muchas gracias por tu calificacion!",
            icon: "success"
        });
        setTimeout(() => {
            event.target.submit();
        }, 1500);
    }
});



function mostrarAviso() {
    document.getElementById("miModal").style.display = "flex";
}

function cerrarAviso(event) {
    let modal = document.getElementById("miModal");

    // Cierra el modal si se hace clic fuera del contenido o si la función se ejecuta sin un evento
    if (!event || event.target === modal) {
        modal.style.display = "none";
    }
}
function ocultarError() {
    let checkBox = document.getElementById("autorizaDatos");
    let errorMensaje = document.getElementById("errorMensaje");

    if (checkBox.checked) {
        errorMensaje.style.display = "none"; // Oculta el mensaje si el usuario marca el checkbox
    }
}
function validarAutorizacion() {
    let checkBox = document.getElementById("autorizaDatos");
    let errorMensaje = document.getElementById("errorMensaje");

    if (!checkBox.checked) {
        errorMensaje.style.display = "block"; // Muestra el mensaje de error
        event.preventDefault(); // Evita que el enlace funcione
    } else {
        errorMensaje.style.display = "none"; // Oculta el mensaje de error si está marcado
    }
}

function setValor(inputId, valor) {
    // Obtener el input oculto y actualizar su valor
    document.getElementById(inputId).value = valor;

    // Obtener todas las estrellas de la misma pregunta
    let estrellas = document.querySelectorAll(`[onclick="setValor('${inputId}', 1)"], 
                                            [onclick="setValor('${inputId}', 2)"], 
                                            [onclick="setValor('${inputId}', 3)"], 
                                            [onclick="setValor('${inputId}', 4)"], 
                                            [onclick="setValor('${inputId}', 5)"]`);

    // Quitar la clase 'seleccionada' de todas las estrellas
    estrellas.forEach(estrella => estrella.classList.remove("seleccionada"));

    // Resaltar las estrellas seleccionadas hasta la que el usuario clickeó
    for (let i = 0; i < valor; i++) {
        estrellas[i].classList.add("seleccionada");
    }
}




