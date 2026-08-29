// ---------------- DRAFT PAGE --------------------- //

// select draft buttons by class name
let draftButtons = document.querySelectorAll(".draftBtn")

// select each draft button and display id number
//created for testing purposes
draftButtons.forEach(button => {
    button.addEventListener("click", () => {
        const pokemonId = button.dataset.pokemonId;

        console.log("Trying to draft:", pokemonId);
    })
})