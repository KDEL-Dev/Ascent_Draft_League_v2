let myActiveUserId = null; 



// -------------------------------------------------
// ---------------- REGISTER PAGE ------------------
// -------------------------------------------------




// -------------------------------------------------
// ---------------- DRAFT PAGE ---------------------
// -------------------------------------------------

// -------------Draft Order - Shuffle---------------
// const draftOrder = document.getElementById("draftOrder");
const randomizeBtn = document.getElementById("randomizeDraft");

if(randomizeBtn)
{
    randomizeBtn.addEventListener("click", function() 
    {
        //Send request to PHP
        fetch("../api/draft/randomize_draft.php")
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
}


// ----------- DRAFT STATE - START DRAFT ------------

const startDraftBtn = document.getElementById("startDraft");

if(startDraftBtn)
{
    startDraftBtn.addEventListener("click", async function()
    {
        try
        {
            const response = await fetch(
                "../api/draft/start_draft.php"
            );

            const data = await response.json();

            console.log("START RESPONSE:", data);

            if (!data.success)
            {
                console.error(data.message);
                return;
            }

            // await loadDraftState();
        }
        catch(error)
        {
            console.error("Starting draft failed:", error);
        }
    });

}





// -------------LOADS LOGGED IN USER ROSTER to DRAFT PAGE---------------

async function loadUserDraftRoster()
{
    const response = await fetch(
        `../api/roster/get_roster.php`
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
    const draftRosterCount =    document.getElementById('draftRosterCount');

    if(draftRosterCount)
    {
        draftRosterCount.textContent =`${data.roster_count}/${data.roster_limit}`;
    }
    
    // ----------------
    // GET ROSTER LISTS
    // ----------------

    const ouRoster = document.getElementById('ouDraftRoster');
    const uuRoster = document.getElementById('uuDraftRoster');
    const ruRoster = document.getElementById('ruDraftRoster');
    const nuRoster = document.getElementById('nuDraftRoster');

    if(!ouRoster && !uuRoster && !ruRoster && !nuRoster)
    {
        return;
    }

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
    const response = await fetch('../api/draft/get_drafted_pokemon.php');

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

// ----- HELPER - POKEMON TYPE FOR DRAFT CARD ------
function setPokemonType(element, type)
{
    if (!element) return;

    // Remove previous type badge classes
    [...element.classList]
        .filter(className => className.startsWith('typeBadge-'))
        .forEach(className => element.classList.remove(className));

    if (!type)
    {
        element.textContent = '';
        return;
    }

    element.classList.add(
        'badge',
        `typeBadge-${type.toLowerCase()}`
    );

    element.textContent = type;
}



// ------------- LOAD MOST RECENT DRAFT PICK to PREVIOUS DRAFT PICK --------------

async function loadDraftedDisplay()
{
    const response = await fetch('../api/draft/get_draft_picks.php');

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

    const draftPickOwner = document.getElementById('draftPickOwner');
    
    if(draftPickOwner)
    {
        draftPickOwner.textContent = currentPick.team_name;
    }
    
    // --------------------
    // PICK OWNER MASCOT
    // --------------------

    const draftPickOwnerMasc = document.getElementById('draftPickOwnerMasc');
    
    if(draftPickOwnerMasc)
    {
        draftPickOwnerMasc.textContent = currentPick.team_mascot;
    }

    // --------------------
    // POKEMON NAME
    // --------------------

    const draftPokemonName = document.getElementById('draftPokemonName');
    
    if(draftPokemonName)
    {
        draftPokemonName.textContent = currentPick.name;
    }
    

    // --------------------
    // TIER
    // --------------------

    const draftPokemonTier = document.getElementById('draftPokemonTier');
    
    if(draftPokemonTier)
    {
        draftPokemonTier.textContent = currentPick.tier;
    }

    // --------------------
    // TYPE
    // --------------------

    const draftPokemonType1 = document.getElementById('draftPokemonType1');
    const draftPokemonType2 = document.getElementById('draftPokemonType2');

    setPokemonType(draftPokemonType1, currentPick.type1);
    setPokemonType(draftPokemonType2, currentPick.type2);



    // --------------------
    // ABILITY
    // --------------------

    const draftAbility1 = document.getElementById('draftAbility1');
    const draftAbility2 = document.getElementById('draftAbility2');
    const draftHiddenAbility = document.getElementById('draftHiddenAbility');
    
    if(draftAbility1 && draftAbility2 && draftHiddenAbility)
    {
        draftAbility1.textContent = currentPick.ability_1;
        draftAbility2.textContent = currentPick.ability_2;
        draftHiddenAbility.textContent = currentPick.hidden_ability;
    }

    // --------------------
    // STATS
    // --------------------

    const draftedPkmnHp = document.getElementById('draftedPkmnHp');
    const draftedPkmnAtk = document.getElementById('draftedPkmnAtk');
    const draftedPkmnDef = document.getElementById('draftedPkmnDef');
    const draftedPkmnSpa = document.getElementById('draftedPkmnSpa');
    const draftedPkmnSpd = document.getElementById('draftedPkmnSpd');
    const draftedPkmnSpe = document.getElementById('draftedPkmnSpe');

    if(draftedPkmnHp && draftedPkmnDef && draftedPkmnSpa && draftedPkmnAtk && draftedPkmnSpd && draftedPkmnSpe)
    {
        draftedPkmnHp.textContent = currentPick.hp;
        draftedPkmnAtk.textContent = currentPick.attack;
        draftedPkmnDef.textContent = currentPick.defense;
        draftedPkmnSpa.textContent = currentPick.sp_attack;
        draftedPkmnSpd.textContent = currentPick.sp_defense;
        draftedPkmnSpe.textContent = currentPick.speed;
    }


    // --------------------
    // IMAGE
    // --------------------

    const cleanName = formatPokemonDbName(currentPick.name); // added to clean up names for pokemondb


    const image = document.createElement('img');

    image.src =
        `https://img.pokemondb.net/artwork/large/${cleanName}.jpg`;

    image.alt = currentPick.name;

    image.classList.add('draftPokemonImage'); //unsure what this is just yet

    const draftPokemonImage = document.getElementById('draftPokemonImage');
    
    if(draftPokemonImage)
    {
        draftPokemonImage.replaceChildren(image);
    }

    // --------------------
    // GET PICK OWNER ROSTER
    // --------------------

    const rosterResponse = await fetch(
        `../api/roster/get_roster.php?active_user_id=${currentPick.active_user_id}`
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

    if(list)
    {
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
}



// ---------------Draft Buttons-------------------

let draftButtons = document.querySelectorAll(".draftBtn")

// select each draft button and display id number
//created for testing purposes
draftButtons.forEach(button => {
    button.addEventListener("click", () => {
        const pokemonId = button.dataset.pokemonId;

        fetch("../api/draft/make_pick.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                pokemon_id: pokemonId,
            })
        })
        .then(async response => {
            const text = await response.text();

            console.log("HTTP STATUS:", response.status);
            console.log("RAW PHP RESPONSE:");
            console.log(text);

            return JSON.parse(text);
        })
        .then(data => {
            console.log("PHP RESPONSE:", data);

            if (!data.success) {
                alert(data.message);
                return;
            }

            loadUserDraftRoster(); 
            loadAllDraftedPokemon();
            loadDraftedDisplay();
            loadDraftState();
            loadDraftLog();
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

    const isMyTurn =
        Number(currentTeam.id) === myActiveUserId;

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

if(pauseDraftBtn)
{
    pauseDraftBtn.addEventListener("click", async function()
    {
        console.log("Pausing with:", timerRemaining);

        try
        {
            const response = await fetch(
                "../api/draft/pause_draft.php",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        timer_remaining: timerRemaining
                    })
                }
            );

            const data = await response.json();

            console.log("PAUSE RESPONSE:", data);

            if (!data.success)
            {
                console.error(data.message);
                return;
            }

            stopTimer();

            await loadDraftState();
        }
        catch(error)
        {
            console.error("Pausing draft failed:", error);
        }
    });

}


// ------------ RESUME DRAFT ------------

const resumeDraftBtn = document.getElementById("resumeDraft");

if(resumeDraftBtn)
{
    resumeDraftBtn.addEventListener("click", async function()
    {
        try
        {
            const response = await fetch(
                "../api/draft/resume_draft.php"
            );

            const data = await response.json();

            console.log("RESUME RESPONSE:", data);

            if (!data.success)
            {
                console.error(data.message);
                return;
            }

            await loadDraftState();
        }
        catch(error)
        {
            console.error("Resuming draft failed:", error);
        }
    });
}




// ----------- SKIP PICK -----------

const skipPickBtn = document.getElementById("skipPick");

if(skipPickBtn)
{
    skipPickBtn.addEventListener("click", function() 
    {
        fetch("../api/draft/skip_pick.php")
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
}


// ------------ END DRAFT ------------

const endDraftBtn = document.getElementById("endDraft");

if (endDraftBtn)
{
    endDraftBtn.addEventListener("click", async function()
    {
        const confirmed = confirm(
            "Are you sure you want to end the draft?"
        );

        if (!confirmed)
        {
            return;
        }

        try
        {
            const response = await fetch(
                "../api/draft/end_draft.php"
            );

            const data = await response.json();

            console.log("END DRAFT RESPONSE:", data);

            if (!data.success)
            {
                console.error(data.message);
                return;
            }

            // The WebSocket event will handle
            // redirecting every browser.

        }
        catch(error)
        {
            console.error("Ending draft failed:", error);
        }
    });
}




// -------------- TIMER ---------------

let timer;
let timerRemaining = 60;

const timerDisplay = document.getElementById("draftTimer");

function stopTimer()
{
    clearInterval(timer);
    timer = null;
}

function startTimer(pickStartedAt)
{
    stopTimer();

    // guard
    if(!timerDisplay)
    {
        return;
    }

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

            stopTimer();

            await autoSkipPick();
        }
    }

    updateTimer();

    timer = setInterval(updateTimer, 250);
}


// ------------- TIMER - AUTO SKIP ---------------

async function autoSkipPick()
{
    try
    {
        const response = await fetch(
            "../api/draft/skip_pick.php"
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
    const response = await fetch('../api/draft/get_draft_state.php');
    const data = await response.json();

    if (!data.success)
    {
        console.error(data.message);
        return;
    }

    myActiveUserId = Number(data.my_active_user.id); // ADDED
    console.log("MY ACTIVE USER ID:", myActiveUserId);


    const draftState = data.draft_state;
    const currentTeam = data.current_team;
    const futureTeam = data.future_team;

    // -------------------------
    // DRAFT DIRECTION
    // -------------------------

    const draftDirection = document.getElementById('draftDirection');

    if(draftDirection)
    {
        if (draftState.draft_direction === 'forward')
        {
            draftDirection.textContent = '↓';
        }
        else
        {
            draftDirection.textContent = '↑';
        }
    }
    
    // -------------------------
    // DRAFT IS NOT ACTIVE
    // -------------------------

    if (!draftState.is_active)
    {
        const onTheClock = document.getElementById('onTheClock');
        const nextTeam = document.getElementById('nextTeam');
        
        if(onTheClock && nextTeam)
        {
            onTheClock.textContent = '-';
            nextTeam.textContent = '-';
        }

        stopTimer();

        if (draftState.timer_remaining !== null)
        {
            timerRemaining = Number(draftState.timer_remaining);
        }

        if(timerDisplay)
        {
            timerDisplay.textContent = timerRemaining;
        }
        

        updateDraftButtons(null);

        return;
    }

 
    // -------------------------
    // ON THE CLOCK
    // -------------------------

    if (currentTeam)
    {
        document.getElementById('onTheClock').textContent =
            currentTeam.default_team_name;
    }
    else
    {
        document.getElementById('onTheClock').textContent = '-';
    }

    // -------------------------
    // ON THE CLOCK - NEXT TEAM
    // -------------------------

    if(futureTeam)
    {
        document.getElementById('nextTeam').textContent = 
        futureTeam.default_team_name;
    }
    else
    {
        document.getElementById('nextTeam').textContent = '-';
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


// -------------- LIVE DRAFT LOG ---------------

async function loadDraftLog()
{
    const response = await fetch(
        '../api/draft/get_draft_picks.php'
    );

    const data = await response.json();

    if (!data.success)
    {
        console.error(data.message);
        return;
    }

    const draftLog = document.getElementById('draftLog');

    if(draftLog)
    {
        draftLog.replaceChildren();
    
        const picks = [...data.picks].reverse();

        picks.forEach(pick => {

            const li = document.createElement('li');
            li.classList.add('draftLogSelection')
            

            // Pick number
            const pickNumber = document.createElement('div');
            pickNumber.classList.add('px-1')
            pickNumber.textContent = pick.pick_number;

            // Team + Pokemon
            const pickInfo = document.createElement('div');

            const teamName = document.createElement('span');
            teamName.textContent = pick.team_name;

            const pokemonName = document.createElement('span');
            pokemonName.textContent = pick.name;

            pickInfo.appendChild(teamName);
            pickInfo.appendChild(pokemonName);

            // Put everything together
            li.appendChild(pickNumber);
            li.appendChild(pickInfo);

            draftLog.appendChild(li);
        });
    }
}

// -------------- DRAFT LIST TOGGLING ---------------

const tierButtons = document.querySelectorAll(".tierButton");
const tierSections = document.querySelectorAll(".tier-section");

function showTier(tier) {

    if(!tierButtons && tierSections)
    {
        return;
    }

    // Hide all tier sections
    tierSections.forEach(section => {
        section.style.display = "none";
    });

    // Show selected tier
    const selectedSection = document.getElementById(`${tier}DraftList`);

    if (selectedSection) {
        selectedSection.style.display = "flex";
    }

    // Update button styling
    tierButtons.forEach(button => {
        if (button.dataset.tier === tier) {
            button.classList.remove("btn-outline-primary");
            button.classList.add("btn-primary");
        } else {
            button.classList.remove("btn-primary");
            button.classList.add("btn-outline-primary");
        }
    });
}

// Button click events
tierButtons.forEach(button => {
    button.addEventListener("click", () => {
        showTier(button.dataset.tier);
    });
});



// --------------LOAD DATA WHEN PAGE OPENS-------------------

loadUserDraftRoster();
loadDraftedDisplay();
loadDraftState();
loadAllDraftedPokemon();
loadDraftLog();
showTier("ou");



// -------------------------------------------------
// ---------------- POKEBOX ------------------------
// -------------------------------------------------

const pokeboxButtons = document.querySelectorAll(".pokeboxBtn");

pokeboxButtons.forEach(button => {

    button.addEventListener("click", () => {

        const pokemonId = button.dataset.pokemonId;
        const pokemonName = button.dataset.pokemonName;
        const tierGroup = button.dataset.tierGroup;

        console.log("Selected:", pokemonName);
        console.log("ID:", pokemonId);
        console.log("Tier:", tierGroup);

        // Next step:
        // Load user's Pokémon from this tier
        // and put them into the drop-down.
    });

});




// -------------- POKEBOX LIST TOGGLING ---------------

const pokeboxTierButtons = document.querySelectorAll(".pokeboxTierButton");
const pokeboxTierSections = document.querySelectorAll(".pokebox-tier-section");

function showPokeboxTier(tier) {

    pokeboxTierSections.forEach(section => {
        section.style.display = "none";
    });

    const selectedSection = document.getElementById(`${tier}PokeboxList`);

    if (selectedSection) {
        selectedSection.style.display = "flex";
    }

    pokeboxTierButtons.forEach(button => {
        if (button.dataset.tier === tier) {
            button.classList.remove("btn-outline-primary");
            button.classList.add("btn-primary");
        } else {
            button.classList.remove("btn-primary");
            button.classList.add("btn-outline-primary");
        }
    });
}

pokeboxTierButtons.forEach(button => {
    button.addEventListener("click", () => {
        showPokeboxTier(button.dataset.tier);
    });
});

// Show OU when page loads
showPokeboxTier("ou");


// -------------------------------------------------
// ---------------- ADMIN SETTINGS ---------------------
// -------------------------------------------------

const resetDraftBtn = document.getElementById('resetDraft');
if(resetDraftBtn)
{
    resetDraftBtn.addEventListener("click", function()
    {
        alert("HELLO!")
        if(!confirm("Are you sure you want to reset the draft?")) 
        return;
    })
}



// -------------------------------------------------
// ---------------- NEW MATCHUP --------------------
// -------------------------------------------------

let team1Id = null;
let team2Id = null;

let team1Name = '';
let team1Mascot = '';

let team2Name = '';
let team2Mascot = '';


const selectTeams = document.getElementById('selectTeams');

if(selectTeams)
{


    selectTeams.addEventListener('click', function () {

        team1Id = document.getElementById('team1').value;
        team2Id = document.getElementById('team2').value;


        if (!team1Id || !team2Id) {
            alert('Please select both teams.');
            return;
        }

        if (team1Id === team2Id) {
            alert('Please select two different teams.');
            return;
        }

        fetch(
            '../api/matchup/get_matchup_roster.php' +
            '?team1_id=' + encodeURIComponent(team1Id) +
            '&team2_id=' + encodeURIComponent(team2Id)
        )
        .then(response => response.json())
        .then(data => {

            if (!data.success) {
                alert(data.message);
                return;
            }

            // Save team information for later
            team1Name = data.team1_name;
            team1Mascot = data.team1_mascot;

            team2Name = data.team2_name;
            team2Mascot = data.team2_mascot;


            displayRoster(
                data.team1_roster,
                document.getElementById('newMatchRoster1'),
                team1Name,
                team1Mascot
            );

            displayRoster(
                data.team2_roster,
                document.getElementById('newMatchRoster2'),
                team2Name,
                team2Mascot
            );


            // Hide team selection
            document
                .getElementById('newMatchTeamSelection')
                .classList.add('d-none');

            // Show rosters
            document
                .getElementById('newMatchRosterSelection')
                .classList.remove('d-none');

        })
        .catch(error => {
            console.error(error);
            alert('Failed to load rosters.');
        });

    });
}


function displayRoster(roster, container, teamName, teamMascot) {

    container.innerHTML = '';

    const list = document.createElement('div');
    list.className = 'list-group';

    // Team heading
    const heading = document.createElement('div');
    heading.className = 'list-group-item';

    const teamHeading = document.createElement('h3');
    teamHeading.className = 'mb-0';
    teamHeading.textContent = `${teamName} ${teamMascot}`;

    heading.appendChild(teamHeading);
    list.appendChild(heading);


    // Pokémon buttons
    roster.forEach(pokemon => {

        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'list-group-item list-group-item-action';

        button.textContent = pokemon.name;

        // Keep the IDs available for later
        button.dataset.pokemonId = pokemon.pokemon_id;
        button.dataset.rosterPokemonId = pokemon.roster_pokemon_id;


        button.addEventListener('click', function () {

            // Already selected?
            if (button.classList.contains('pokemon-selected')) {

                // Remove selection
                button.classList.remove('pokemon-selected');
                button.classList.remove('active');

                return;
            }


            // How many are currently selected?
            const selectedCount =
                list.querySelectorAll('.pokemon-selected').length;


            // Maximum of 6
            if (selectedCount >= 6) {
                return;
            }


            // Select Pokémon
            button.classList.add('pokemon-selected');
            button.classList.add('active');

        });


        list.appendChild(button);
    });

    container.appendChild(list);
}


const continueToStats = document.getElementById('continueToStats');

if(continueToStats)
{


    continueToStats.addEventListener('click', function () {

        const roster1 = document.getElementById('newMatchRoster1');
        const roster2 = document.getElementById('newMatchRoster2');

        const selected1 =
            roster1.querySelectorAll('.pokemon-selected');

        const selected2 =
            roster2.querySelectorAll('.pokemon-selected');


        // Make sure both teams selected at least one
        if (selected1.length === 0 || selected2.length === 0) {
            alert('Please select at least one Pokémon from each team.');
            return;
        }


        // Build stats tables
        displayStatsTable(
            roster1,
            document.getElementById('team1Stats'),
            team1Name,
            team1Mascot
        );

        displayStatsTable(
            roster2,
            document.getElementById('team2Stats'),
            team2Name,
            team2Mascot
        );


        // Populate winner dropdown
        const matchResult = document.getElementById('matchResult');

        matchResult.innerHTML = `
            <option value="">Select Winner</option>

            <option value="${team1Id}">
                ${team1Name} ${team1Mascot}
            </option>

            <option value="${team2Id}">
                ${team2Name} ${team2Mascot}
            </option>
        `;


        // Hide roster selection
        document
            .getElementById('newMatchRosterSelection')
            .classList.add('d-none');


        // Show stats
        document
            .getElementById('newMatchAddStats')
            .classList.remove('d-none');

    });
}



function displayStatsTable(
    rosterContainer,
    statsContainer,
    teamName,
    teamMascot
) {

    statsContainer.innerHTML = '';

    const selectedPokemon =
        rosterContainer.querySelectorAll('.pokemon-selected');


    const tableWrapper = document.createElement('div');
    tableWrapper.className = 'table-responsive';

    const table = document.createElement('table');
    table.className = 'table table-dark table-striped';

    table.innerHTML = `
        <thead>
            <tr>
                <th colspan="3" class="fs-4">
                    ${teamName} ${teamMascot}
                </th>
            </tr>

            <tr>
                <th>Pokemon</th>
                <th>Kills</th>
                <th>Deaths</th>
            </tr>
        </thead>

        <tbody></tbody>
    `;


    const tbody = table.querySelector('tbody');

    selectedPokemon.forEach(button => {

        const pokemonId = button.dataset.pokemonId;
        const rosterPokemonId = button.dataset.rosterPokemonId;
        const pokemonName = button.textContent;

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>
                ${pokemonName}
            </td>

            <td>
                <input
                    type="number"
                    class="form-control pokemon-kills"
                    min="0"
                    max="6"
                    step="1"
                    value="0"
                    data-pokemon-id="${pokemonId}"
                    data-roster-pokemon-id="${rosterPokemonId}"
                >
            </td>

            <td>
                <input
                    type="number"
                    class="form-control pokemon-deaths"
                    min="0"
                    max="6"
                    step="1"
                    value="0"
                    data-pokemon-id="${pokemonId}"
                    data-roster-pokemon-id="${rosterPokemonId}"
                >
            </td>
        `;

        tbody.appendChild(row);
    });

    tableWrapper.appendChild(table);
    statsContainer.appendChild(tableWrapper);
}


// STAT COLLECTION

const submitMatch = document.getElementById('submitMatch');

if(submitMatch)
{


    submitMatch.addEventListener('click', function () {

        const matchData = {
            season_id: 1,

            player1_au_id: team1Id,
            player2_au_id: team2Id,

            match_result: document.getElementById('matchResult').value,
            match_replay: document.getElementById('matchReplay').value.trim(),

            team1_pokemon: [],
            team2_pokemon: []
        };

        if (!matchData.match_result) {
            alert('Please select the match winner.');
            return;
        }

        if (!matchData.match_replay) {
            alert('Please enter the Pokémon Showdown replay link.');
            return;
        }

        if (!isValidShowdownReplay(matchData.match_replay)) {
            alert('Please enter a valid Pokémon Showdown replay link.');
            return;
        }


        // TEAM 1
        document
            .querySelectorAll('#team1Stats .pokemon-kills')
            .forEach(killsInput => {

                const rosterPokemonId =
                    killsInput.dataset.rosterPokemonId;

                const deathsInput =
                    document.querySelector(
                        `#team1Stats .pokemon-deaths[data-roster-pokemon-id="${rosterPokemonId}"]`
                    );

                matchData.team1_pokemon.push({
                    roster_pkmn_id: rosterPokemonId,
                    kills: parseInt(killsInput.value) || 0,
                    deaths: parseInt(deathsInput.value) || 0
                });
            });


        // TEAM 2
        document
            .querySelectorAll('#team2Stats .pokemon-kills')
            .forEach(killsInput => {

                const rosterPokemonId =
                    killsInput.dataset.rosterPokemonId;

                const deathsInput =
                    document.querySelector(
                        `#team2Stats .pokemon-deaths[data-roster-pokemon-id="${rosterPokemonId}"]`
                    );

                matchData.team2_pokemon.push({
                    roster_pkmn_id: rosterPokemonId,
                    kills: parseInt(killsInput.value) || 0,
                    deaths: parseInt(deathsInput.value) || 0
                });
            });



        // VALIDATE TEAM KILLS / DEATHS

        const team1Kills = matchData.team1_pokemon.reduce(
            (total, pokemon) => total + pokemon.kills,
            0
        );

        const team1Deaths = matchData.team1_pokemon.reduce(
            (total, pokemon) => total + pokemon.deaths,
            0
        );

        const team2Kills = matchData.team2_pokemon.reduce(
            (total, pokemon) => total + pokemon.kills,
            0
        );

        const team2Deaths = matchData.team2_pokemon.reduce(
            (total, pokemon) => total + pokemon.deaths,
            0
        );


        if (team1Kills !== team2Deaths) {
            alert(
                `Team 1 has ${team1Kills} kills, but Team 2 has ${team2Deaths} deaths.`
            );
            return;
        }

        if (team2Kills !== team1Deaths) {
            alert(
                `Team 2 has ${team2Kills} kills, but Team 1 has ${team1Deaths} deaths.`
            );
            return;
        }



        fetch('../api/matchup/submit_matchup.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(matchData)
        })
        .then(response => response.json())
        .then(data => {

            if (!data.success) {
                alert(data.message);
                return;
            }

            alert('Match saved successfully!');

            window.location.href = 'matchups.php';

        })
        .catch(error => {

            console.error(error);

            alert('Failed to save match.');

        });

    });
}

function isValidShowdownReplay(url) {

    try {

        const parsedUrl = new URL(url);

        return (
            parsedUrl.protocol === 'https:' &&
            parsedUrl.hostname === 'replay.pokemonshowdown.com'
        );

    } catch {
        return false;
    }
}

// BACK BUTTONS
let prevBtnRoster = document.getElementById("prevBtnRoster");
let prevBtnStatistics = document.getElementById("prevBtnStatistics");

if (prevBtnRoster) {
    prevBtnRoster.addEventListener("click", function () {
        document.getElementById('newMatchRosterSelection').classList.add('d-none');
        document.getElementById('newMatchTeamSelection').classList.remove('d-none');
    });
}

if (prevBtnStatistics) {
    prevBtnStatistics.addEventListener("click", function () {
        document.getElementById('newMatchAddStats').classList.add('d-none');
        document.getElementById('newMatchRosterSelection').classList.remove('d-none');
    });
}



// --------------------------------
// --------- STANDINGS ------------
// --------------------------------

function loadPokemonLeaderImages()
{
    const images = document.querySelectorAll('.pkmnLeaderImg');

    images.forEach(image => {

        const pokemonName = image.dataset.pkmnName;

        const cleanName = pokemonName.toLowerCase();

        image.src =
            `https://img.pokemondb.net/artwork/large/${cleanName}.jpg`;

        image.alt = pokemonName;

    });
}

loadPokemonLeaderImages();


// -------------------------------------------------
// ------------------ WEB SOCKET -------------------
// -------------------------------------------------

const socket = new WebSocket('ws://localhost:8080');

socket.onopen = function() {
    console.log('WebSocket connected');

};

socket.onmessage = async function(event) 
{
    const data = JSON.parse(event.data);

    console.log("DRAFT EVENT RECEIVED:", data);

    //----- DRAFT STARTED -----

    if (data.type === "draft_started")
    {
        console.log("DRAFT HAS STARTED!");

        await loadDraftState();
        await loadUserDraftRoster();
        await loadAllDraftedPokemon();
        await loadDraftedDisplay();
        await loadDraftLog();
    }

    // -----DRAFT PAUSED -----

     if (data.type === "draft_paused")
    {
        console.log("DRAFT PAUSED!");

        console.log(
            "Timer remaining:",
            data.timer_remaining
        );

        // Stop timer on THIS browser
        stopTimer();

        // Load paused state from database
        await loadDraftState();
    }

    //----- DRAFT RESUMED -----
    if (data.type === "draft_resumed")
    {
        console.log("DRAFT RESUMED");

        await loadDraftState();
    }

    //----- DRAFT PICK ------

    if (data.type === "draft_pick") 
    {
        console.log("Pokemon drafted:", data.pokemon_id);
        console.log("Team:", data.team_name);
        console.log("Pick number:", data.pick_number);

        // Refresh draft information
        await loadUserDraftRoster();
        await loadAllDraftedPokemon();
        await loadDraftedDisplay();
        await loadDraftState();
        await loadDraftLog();
    }

    // -----SKIP PICK ------

    if (data.type === "draft_skipped")
    {
        console.log("DRAFT PICK SKIPPED");

        await loadDraftState();
        await loadDraftedDisplay();
        await loadDraftLog();
    }

    //----- DRAFT ENDED -----

    if (data.type === "draft_ended")
    {
        console.log("DRAFT HAS ENDED!");

        stopTimer();

        window.location.href = "roster.php";
    }


};




socket.onclose = function() {
    console.log('WebSocket disconnected');
};

socket.onerror = function(error) {
    console.error('WebSocket error:', error);
};

