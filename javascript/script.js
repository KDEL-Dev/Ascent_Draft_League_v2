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


// -------------LOADS LOGGED IN USER ROSTER to DRAFT PAGE---------------

async function loadUserDraftRoster()
{
    const activeUserId = 6; // temp: grab active user from loggedin person later
    const response = await fetch(
        `api/roster/get_roster.php?active_user_id=${activeUserId}`
    );

    // const response = await fetch('api/roster/get_roster.php');

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

    // ----------------
    // GET ROSTER LISTS
    // ----------------

    const ouRoster = document.getElementById('ouDraftRoster');
    const uuRoster = document.getElementById('uuDraftRoster');
    const ruRoster = document.getElementById('ruDraftRoster');
    const nuRoster = document.getElementById('nuDraftRoster');


    // ----------------
    // CLEAR OLD ROSTER
    // ----------------

    ouRoster.replaceChildren();
    uuRoster.replaceChildren();
    ruRoster.replaceChildren();
    nuRoster.replaceChildren();


    // ----------------
    // DISPLAY ROSTER
    // ----------------

    displayUserDraftTier(
        ouRoster,
        data.roster,
        ['OU', 'UUBL']
    );

    displayUserDraftTier(
        uuRoster,
        data.roster,
        ['UU', 'RUBL']
    );

    displayUserDraftTier(
        ruRoster,
        data.roster,
        ['RU', 'NUBL']
    );

    displayUserDraftTier(
        nuRoster,
        data.roster,
        ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU']
    );

}

// -----------
function displayUserDraftTier(list, roster, tiers)
{
    const pokemonForTier = roster.filter(pokemon =>
        tiers.includes(pokemon.tier)
    );


    // ----------------
    // ADD POKEMON
    // ----------------

    pokemonForTier.forEach(pokemon => {

        const li = document.createElement('li');

        li.textContent = pokemon.name;

        list.appendChild(li);
    });


    // ----------------
    // ADD EMPTY SLOTS
    // ----------------

    for (
        let i = pokemonForTier.length;
        i < 3; //Might need to make this dynamic in the future
        i++
    )
    {
        const li = document.createElement('li');

        li.textContent = '—';

        list.appendChild(li);
    }
}



// --------------- LOAD ALL DRAFTED POKEMON ------------------

async function loadAllDraftedPokemon()
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

// ------------- CLEAN NAME for PokemonDB ------------------

function formatPokemonDbName(name) {
    return name
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
        .replace('-galar', '-galarian')
        .replace('-hisui', '-hisuian')
        .replace('-paldea', '-paldean')
        .replace('-alola', '-alolan')
        .replace('-f', '-female'); // This one might cause problems later
}

// ------------ CLEAR DRAFT ----------------
function displayDraftedTier(elementId, roster, tiers)
{
    const list = document.getElementById(elementId);

    // IMPORTANT:
    // Remove everything currently in this list
    list.replaceChildren();

    // Only get Pokémon belonging to this tier
    const pokemonForTier = roster.filter(pokemon =>
        tiers.includes(pokemon.tier)
    );

    // Add the actual Pokémon
    pokemonForTier.forEach(pokemon => {

        const li = document.createElement('li');

        li.textContent = pokemon.name;

        list.appendChild(li);
    });

    // Add placeholders until there are 3 slots
    for (let i = pokemonForTier.length; i < 3; i++)
    {
        const li = document.createElement('li');

        li.textContent = '—';

        list.appendChild(li);
    }
}



// ------------- LOAD MOST RECENT DRAFT PICK to PREVIOUS DRAFT PICK --------------

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

    const cleanName = formatPokemonDbName(currentPick.name); // added to clean up names for pokemondb


    const image = document.createElement('img');

    image.src =
        `https://img.pokemondb.net/artwork/large/${cleanName}.jpg`;

    image.alt = currentPick.name;

    image.classList.add('draftPokemonImage'); //unsure what this is just yet

    document
        .getElementById('draftPokemonImage')
        .replaceChildren(image);

    // --------------------
    // GET PICK OWNER ROSTER
    // --------------------

    const rosterResponse = await fetch(
        `api/roster/get_roster.php?active_user_id=${currentPick.active_user_id}`
    );

    const rosterData = await rosterResponse.json();

    if (!rosterData.success)
    {
        console.error(rosterData.message);
        return;
    }

    console.log("PICK OWNER ROSTER:", rosterData.roster);

    // --------------------
    // DISPLAY ROSTER
    // --------------------

    displayDraftedRoster(rosterData.roster);
}

// --------------------
// DISPLAY DRAFTED ROSTER
// --------------------

function displayDraftedRoster(roster)
{
    displayDraftedTier(
        'ouDraftDisplayRoster',
        roster,
        ['OU', 'UUBL']
    );

    displayDraftedTier(
        'uuDraftDisplayRoster',
        roster,
        ['UU', 'RUBL']
    );

    displayDraftedTier(
        'ruDraftDisplayRoster',
        roster,
        ['RU', 'NUBL']
    );

    displayDraftedTier(
        'nuDraftDisplayRoster',
        roster,
        ['NU', 'PUBL', 'PU', 'ZUBL', 'ZU']
    );
}


// ----------------------
// SORT POKEMON into TIER
// ----------------------

function displayDraftedTier(elementId, roster, tiers)
{
    const list = document.getElementById(elementId);

    // Clear existing contents
    list.replaceChildren();

    // Get only Pokémon belonging to this tier group
    const pokemonForTier = roster.filter(pokemon =>
        tiers.includes(pokemon.tier)
    );


    // Add drafted Pokémon
    pokemonForTier.forEach(pokemon => {

        const li = document.createElement('li');

        li.textContent = pokemon.name;

        list.appendChild(li);

    });


    // Fill remaining roster slots
    for (
        let i = pokemonForTier.length;
        i < 3;
        i++
    )
    {
        const li = document.createElement('li');

        li.textContent = '—';

        list.appendChild(li);
    }
}

    



// --------------LOAD DATA WHEN PAGE OPENS-------------------

loadUserDraftRoster();
loadAllDraftedPokemon();
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
                loadUserDraftRoster(); 
                loadAllDraftedPokemon();
                loadDraftedDisplay();

            }
            
        })
        .catch(error => {
            console.error("Draft Failed:", error);
        });
        
    })
    
})
