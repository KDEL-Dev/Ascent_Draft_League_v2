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
    .then(response => response.text())
    .then(data => {
        console.log(data);

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
                pokemon_id: pokemonId,
                active_user_id: 6 // temp add
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
                loadDraftState();
            }
            
        })
        .catch(error => {
            console.error("Draft Failed:", error);
        });
        
    })
    
})

// -------------------
// DISABLE DRAFT BTNS
//--------------------

function updateDraftButtons(currentTeam)
{
    const draftButtons = document.querySelectorAll(".draftBtn");

    // Draft is not active
    if (!currentTeam)
    {
        draftButtons.forEach(button => {
            button.disabled = true;
        });

        return;
    }

    // Temporary logged-in user
    const activeUserId = 6;

    const isMyTurn =
        Number(currentTeam.id) === activeUserId;

    draftButtons.forEach(button => {

        // Don't enable Pokémon that has already been drafted
        if (button.textContent === "Drafted")
        {
            button.disabled = true;
            return;
        }

        button.disabled = !isMyTurn;
    });
}

// ------------ PAUSE DRAFT ------------

const pauseDraftBtn = document.getElementById("pauseDraft");

pauseDraftBtn.addEventListener("click", function()
{
    console.log("Pausing with:", timerRemaining);

    fetch("api/draft/pause_draft.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            timer_remaining: timerRemaining
        })
    })
    .then(response => response.json())
    .then(data => {

        console.log("PAUSE RESPONSE:", data);

        if (data.success)
        {
            clearInterval(timer);
            loadDraftState();
        }
        else
        {
            console.error(data.message);
        }

    })
    .catch(error => {
        console.error("Pausing draft failed:", error);
    });
});



// ------------ RESUME DRAFT ------------

const resumeDraftBtn = document.getElementById("resumeDraft");

resumeDraftBtn.addEventListener("click", function()
{
    fetch("api/draft/resume_draft.php")
        .then(response => response.json())
        .then(data => {

            console.log("RESUME RESPONSE:", data);

            if (data.success)
            {
                loadDraftState();
            }
            else
            {
                console.error(data.message);
            }

        })
        .catch(error => {
            console.error("Resuming draft failed:", error);
        });
});



// ----------- SKIP PICK -----------

const skipPickBtn = document.getElementById("skipPick");

skipPickBtn.addEventListener("click", function() {

    fetch("api/draft/skip_pick.php")
    .then(response => response.json())
    .then(data => {

        console.log("SKIP RESPONSE:", data);

        if (!data.success)
        {
            console.error(data.message);
            return;
        }

        loadDraftState();
        loadDraftedDisplay();

    })
    .catch(error => {
        console.error("Skip Pick failed:", error);
    });

});



// -------------- TIMER ---------------

let timer;
let timerRemaining = 60;

const timerDisplay = document.getElementById("draftTimer");

function startTimer(pickStartedAt)
{
    clearInterval(timer);

    const duration = 60;

    let skipTriggered = false;

    async function updateTimer()
    {
        const startTime = new Date(
            pickStartedAt.replace(" ", "T")
        );

        const now = new Date();

        const elapsedSeconds =
            Math.floor((now - startTime) / 1000);

        const remaining = Math.max(
            duration - elapsedSeconds,
            0
        );

        timerRemaining = remaining;
        timerDisplay.textContent = remaining;

        if (remaining <= 0 && !skipTriggered)
        {
            skipTriggered = true;

            clearInterval(timer);

            await autoSkipPick();
        }
    }

    updateTimer();

    timer = setInterval(updateTimer, 250);
}

// ------------- TIMER -  AUTO SKIP ---------------

async function autoSkipPick()
{
    try
    {
        const response = await fetch(
            "api/draft/skip_pick.php"
        );

        const data = await response.json();

        console.log("AUTO SKIP RESPONSE:", data);

        if (!data.success)
        {
            console.error(
                "Automatic skip failed:",
                data.message
            );

            return;
        }

        // Refresh draft information
        await loadDraftState();

        // Refresh previous pick display
        await loadDraftedDisplay();

    }
    catch (error)
    {
        console.error(
            "Automatic skip failed:",
            error
        );
    }
}



// -------------- GET DRAFT STATE ---------------

async function loadDraftState()
{
    const response = await fetch('api/draft/get_draft_state.php');

    const data = await response.json();

    if (!data.success)
    {
        console.error(data.message);
        return;
    }

    const draftState = data.draft_state;
    const currentTeam = data.current_team;

    // -------------------------
    // DRAFT BUTTON
    // -------------------------

    const startDraftBtn = document.getElementById("startDraft");

    if (draftState.status === "pending")
    {
        startDraftBtn.textContent = "Start Draft";
    }
    else if (draftState.status === "paused")
    {
        startDraftBtn.textContent = "Resume Draft";
    }
    else if (draftState.status === "active")
    {
        startDraftBtn.textContent = "Pause Draft";
    }


    // -------------------------
    // DRAFT IS NOT ACTIVE
    // -------------------------

    if (!draftState.is_active)
    {
        document.getElementById('onTheClock').textContent = '-';
        return;
    }

    // -------------------------
    // ON THE CLOCK
    // -------------------------

    if (currentTeam)
    {
        document.getElementById('onTheClock').textContent =
            currentTeam.team_name;
    }
    else
    {
        document.getElementById('onTheClock').textContent = '-';
    }

    // -------------------------
    // UPDATE DRAFT BUTTONS
    // -------------------------

    updateDraftButtons(currentTeam);

    // -------------------------
    // TIMER
    // -------------------------

    startTimer(draftState.pick_started_at);
}






























// --------------LOAD DATA WHEN PAGE OPENS-------------------

loadUserDraftRoster();
loadDraftedDisplay();
loadDraftState();
loadAllDraftedPokemon();

