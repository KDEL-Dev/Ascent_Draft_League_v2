// ---------------- DRAFT PAGE --------------------- //

// Draft Order - Shuffle
const draftOrder = document.getElementById("draftOrder");
const randomizeBtn = document.getElementById("randomizeDraft")

randomizeBtn.addEventListener("click", function() {
    //Send request to PHP
    fetch("api/draft/randomize_draft.php")
    // Wait for PHP Response --which normally is echo"Draft order randomized successfully";
    .then(response => response.text())
    .then(data => {
        // Print the response in the browswer console
        console.log(data);
        // Refresh the page
        window.location.reload();
    })
    .catch(error => {
        console.error("Randomization failed:", error);
    });
});

// Draft State - Start Draft
const startDraftBtn = document.getElementById("startDraft");

startDraftBtn.addEventListener("click", function(){
    fetch('api/draft/start_draft.php')
    .then(response => response.text)
    .then(data => {
        console.log(data)
        window.location.reload();
    })
    .catch(error => {
        console.error("Starting draft failed:", error)
    })
})


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