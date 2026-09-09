// -------------------------------------------------
// ---------------- DRAFT PAGE ---------------------
// -------------------------------------------------

// -------------Draft Order - Shuffle---------------
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

// -----------Draft State - Start Draft------------
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


// -------------LOAD USER ROSTER to DRAFT PAGE---------------

async function loadDraftRoster()
{
    const response = await fetch('api/roster/get_roster.php');

    const data = await response.json();

    if(!data.success)
    {
        console.error(data.message);
        return;
    }

    // --------------
    // ROSTER COUNTER
    // --------------

    document.getElementById('draftRosterCount').textContent =
    `${data.roster_count}/${data.roster_limit}`;

    // ------------
    // ROSTER SLOTS
    // ------------

    // Clear existing placeholders
    const ouRoster = document.querySelectorAll('#ouDraftRoster li');
    const uuRoster = document.querySelectorAll('#uuDraftRoster li');
    const ruRoster = document.querySelectorAll('#ruDraftRoster li');
    const nuRoster = document.querySelectorAll('#nuDraftRoster li');


    data.roster.forEach(pokemon => {

        if (['OU', 'UUBL'].includes(pokemon.tier)) {

            for (let i = 0; i < ouRoster.length; i++) {
                if (ouRoster[i].textContent === '—') {
                    ouRoster[i].textContent = pokemon.name;
                    break;
                }
            }

        }

        else if (['UU', 'RUBL'].includes(pokemon.tier)) {

            for (let i = 0; i < uuRoster.length; i++) {
                if (uuRoster[i].textContent === '—') {
                    uuRoster[i].textContent = pokemon.name;
                    break;
                }
            }

        }

        else if (['RU', 'NUBL'].includes(pokemon.tier)) {

            for (let i = 0; i < ruRoster.length; i++) {
                if (ruRoster[i].textContent === '—') {
                    ruRoster[i].textContent = pokemon.name;
                    break;
                }
            }

        }

        else if (['NU', 'PUBL', 'PU', 'ZUBL', 'ZU'].includes(pokemon.tier)) {

            for (let i = 0; i < nuRoster.length; i++) {
                if (nuRoster[i].textContent === '—') {
                    nuRoster[i].textContent = pokemon.name;
                    break;
                }
            }

        }
    });

}


// --------------- LOAD ALL DRAFTED POKEMON ------------------

async function loadDraftedPokemon()
{
    const response = await fetch('api/draft/get_drafted_pokemon.php');

    const data = await response.json();

    if(!data.success)
    {
        console.error(data.message);
        return;
    }

    data.drafted_pokemon.forEach(pokemonId => {

        const button = document.querySelector(
            `.draftBtn[data-pokemon-id="${pokemonId}"]`
        );

        if(button)
        {
            button.textContent = "Drafted";
            button.disabled = true;

            button.classList.remove("btn-primary");
            button.classList.add("btn-secondary");
        }

    });
}

// ------------- LOAD DRAFT PICKS to DISPLAY --------------

async function loadDraftedDisplay()
{
    const response = await fetch('api/draft/get_draft_picks.php');

    const data = await response.json();

    if (!data.success)
    {
        console.error(data.message);
        return;
    }

    // No picks yet
    if (data.picks.length === 0)
    {
        return;
    }

    // Most recent pick
    const currentPick = data.picks[data.picks.length - 1];


    // --------------------
    // PICK OWNER
    // --------------------

    document.getElementById('draftPickOwner').textContent =
        currentPick.team_name;


    // --------------------
    // POKEMON NAME
    // --------------------

    document.getElementById('draftPokemonName').textContent =
        currentPick.name;


    // --------------------
    // TIER
    // --------------------

    document.getElementById('draftPokemonTier').textContent =
        currentPick.tier;


    // --------------------
    // IMAGE
    // --------------------

    const image = document.createElement('img');

    image.src =
        `https://img.pokemondb.net/artwork/large/${currentPick.name.toLowerCase()}.jpg`;

    image.alt = currentPick.name;

    document
        .getElementById('draftPokemonImage')
        .replaceChildren(image);
}

// -------------------------
// LOAD DATA WHEN PAGE OPENS
// -------------------------

loadDraftRoster();
loadDraftedPokemon();
loadDraftedDisplay();











// ---------------Draft Buttons-------------------

let draftButtons = document.querySelectorAll(".draftBtn")

// select each draft button and display id number
//created for testing purposes
draftButtons.forEach(button => {
    button.addEventListener("click", () => {
        const pokemonId = button.dataset.pokemonId;

        fetch("api/draft/make_pick.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                pokemon_id: pokemonId
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log("PHP RESPONSE:", data);

            if(data.success) // This might be causing issues with how list gets filled out. Possibly delete later.
            {
                loadDraftRoster(); 
                loadDraftedPokemon();
            }
            
        })
        .catch(error => {
            console.error("Draft Failed:", error);
        });
        
    })
    
})
