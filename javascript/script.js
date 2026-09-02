// ---------------- DRAFT PAGE --------------------- //

// Draft Order - Shuffle
const draftOrder = document.getElementById("draftOrder");
const randomizeBtn = document.getElementById("randomizeDraft")

let teams = [
    "Normal",
    "Dark",
    "Bug",
    "Fairy",
    "Steel"
]

randomizeBtn.addEventListener("click", function()
{

    teams.sort(() => Math.random() -0.5);

    draftOrder.innerHTML = "";

    teams.forEach((team, index) => {
        const li = document.createElement("li");

        li.textContent = `${index + 1}. ${team}`;

        draftOrder.appendChild(li)
    });
});

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