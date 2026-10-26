<?php

$file = "notes.json";


/* =========================
   LOAD NOTES
========================= */

function loadNotes()
{
    global $file;

    if (!file_exists($file)) {
        file_put_contents($file, "[]");
    }

    $data = file_get_contents($file);

    $notes = json_decode($data, true);

    return is_array($notes) ? $notes : [];
}


/* =========================
   SAVE NOTES
========================= */

function saveNotes($notes)
{
    global $file;

    file_put_contents(
        $file,
        json_encode(
            $notes,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        )
    );
}


/* =========================
   JSON RESPONSE
========================= */

function jsonResponse($data)
{
    header("Content-Type: application/json");

    echo json_encode($data);

    exit;
}


/* =========================
   API
========================= */

if (isset($_GET["api"])) {

    $action = $_GET["api"];

    $notes = loadNotes();


    /* GET NOTES */

    if ($action === "notes") {

        jsonResponse($notes);

    }


    /* CREATE */

    if ($action === "create") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        $nextId = 1;

        foreach ($notes as $note) {

            if ($note["id"] >= $nextId) {
                $nextId = $note["id"] + 1;
            }

        }


        $newNote = [

            "id" => $nextId,

            "title" =>
                $input["title"] ?? "Untitled",

            "content" =>
                $input["content"] ?? "",

            "pinned" =>
                !empty($input["pinned"]),

            "favorite" =>
                !empty($input["favorite"]),

            "deleted" => false,

            "date" =>
                date("M d, Y H:i")

        ];


        array_unshift($notes, $newNote);

        saveNotes($notes);

        jsonResponse([
            "success" => true,
            "note" => $newNote
        ]);

    }


    /* UPDATE */

    if ($action === "update") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        foreach ($notes as &$note) {

            if (
                (int)$note["id"] ===
                (int)$input["id"]
            ) {

                $note["title"] =
                    $input["title"] ?? "Untitled";

                $note["content"] =
                    $input["content"] ?? "";

                $note["pinned"] =
                    !empty($input["pinned"]);

                $note["favorite"] =
                    !empty($input["favorite"]);

                $note["date"] =
                    date("M d, Y H:i");

                break;
            }

        }

        unset($note);

        saveNotes($notes);

        jsonResponse([
            "success" => true
        ]);

    }


    /* MOVE TO TRASH */

    if ($action === "delete") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        foreach ($notes as &$note) {

            if (
                (int)$note["id"] ===
                (int)$input["id"]
            ) {

                $note["deleted"] = true;

                break;
            }

        }

        unset($note);

        saveNotes($notes);

        jsonResponse([
            "success" => true
        ]);

    }


    /* RESTORE */

    if ($action === "restore") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        foreach ($notes as &$note) {

            if (
                (int)$note["id"] ===
                (int)$input["id"]
            ) {

                $note["deleted"] = false;

                break;
            }

        }

        unset($note);

        saveNotes($notes);

        jsonResponse([
            "success" => true
        ]);

    }


    /* PERMANENT DELETE */

    if ($action === "permanent-delete") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        $notes = array_values(
            array_filter(
                $notes,
                function ($note) use ($input) {

                    return
                        (int)$note["id"] !==
                        (int)$input["id"];

                }
            )
        );


        saveNotes($notes);

        jsonResponse([
            "success" => true
        ]);

    }


    /* TOGGLE PIN */

    if ($action === "toggle-pin") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        foreach ($notes as &$note) {

            if (
                (int)$note["id"] ===
                (int)$input["id"]
            ) {

                $note["pinned"] =
                    !$note["pinned"];

                break;
            }

        }

        unset($note);

        saveNotes($notes);

        jsonResponse([
            "success" => true
        ]);

    }


    /* TOGGLE FAVORITE */

    if ($action === "toggle-favorite") {

        $input =
            json_decode(
                file_get_contents("php://input"),
                true
            );


        foreach ($notes as &$note) {

            if (
                (int)$note["id"] ===
                (int)$input["id"]
            ) {

                $note["favorite"] =
                    !$note["favorite"];

                break;
            }

        }

        unset($note);

        saveNotes($notes);

        jsonResponse([
            "success" => true
        ]);

    }


    jsonResponse([
        "success" => false,
        "message" => "Unknown API action"
    ]);

}


/* =========================
   INITIAL NOTES
========================= */

$notes = loadNotes();

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Notes Clone - PHP</title>


<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background: #f7f8fa;
    color: #202124;
}


/* HEADER */

header {
    height: 70px;

    background: white;

    border-bottom: 1px solid #ddd;

    display: flex;

    align-items: center;

    padding: 0 25px;

    gap: 20px;

    position: sticky;

    top: 0;

    z-index: 10;
}

.logo {
    font-size: 24px;

    font-weight: bold;

    min-width: 120px;
}

.search {
    flex: 1;

    max-width: 600px;

    margin: auto;

    background: #f1f3f4;

    border-radius: 25px;

    padding: 12px 20px;
}

.search input {
    border: none;

    outline: none;

    background: transparent;

    width: 90%;

    font-size: 15px;
}

.new-btn {
    border: none;

    background: #333;

    color: white;

    padding: 12px 20px;

    border-radius: 22px;

    cursor: pointer;

    font-weight: bold;
}

.new-btn:hover {
    background: #111;
}


/* LAYOUT */

.layout {
    display: flex;

    min-height: calc(100vh - 70px);
}


/* SIDEBAR */

aside {
    width: 230px;

    background: white;

    border-right: 1px solid #ddd;

    padding: 25px 15px;
}

.menu-title {
    color: #888;

    font-size: 12px;

    font-weight: bold;

    margin: 10px 15px 15px;
}

.menu button {
    width: 100%;

    border: none;

    background: transparent;

    text-align: left;

    padding: 13px 15px;

    border-radius: 10px;

    cursor: pointer;

    font-size: 15px;

    margin-bottom: 5px;
}

.menu button:hover,
.menu button.active {
    background: #eeeeee;

    font-weight: bold;
}


/* MAIN */

main {
    flex: 1;

    padding: 30px;
}

.top-row {
    margin-bottom: 25px;
}

.top-row h1 {
    font-size: 28px;
}

.note-count {
    color: #888;

    margin-top: 5px;

    font-size: 14px;
}


/* NOTES */

.notes-grid {
    display: grid;

    grid-template-columns:
        repeat(auto-fill, minmax(240px, 1fr));

    gap: 18px;
}

.note {
    background: white;

    border: 1px solid #ddd;

    border-radius: 12px;

    padding: 20px;

    min-height: 180px;

    cursor: pointer;

    position: relative;

    transition: 0.2s;
}

.note:hover {
    transform: translateY(-3px);

    box-shadow:
        0 5px 15px rgba(0,0,0,0.08);
}

.note.pinned {
    border: 2px solid #555;
}

.note h3 {
    font-size: 18px;

    margin-bottom: 12px;

    padding-right: 50px;
}

.note p {
    color: #555;

    line-height: 1.5;

    white-space: pre-wrap;

    max-height: 90px;

    overflow: hidden;
}

.note-icons {
    position: absolute;

    right: 15px;

    top: 15px;
}

.note-date {
    position: absolute;

    bottom: 12px;

    left: 20px;

    color: #999;

    font-size: 11px;
}


/* TRASH ACTIONS */

.trash-actions {
    margin-top: 15px;

    display: flex;

    gap: 6px;
}

.trash-actions button {
    border: none;

    border-radius: 6px;

    padding: 6px 9px;

    cursor: pointer;

    font-size: 12px;
}

.restore {
    background: #e8f5e9;
}

.permanent {
    background: #ffebee;
    color: #c62828;
}


/* EMPTY */

.empty {
    grid-column: 1 / -1;

    text-align: center;

    padding: 80px 20px;

    color: #888;

    font-size: 17px;
}


/* MODAL */

.modal {
    display: none;

    position: fixed;

    inset: 0;

    background: rgba(0,0,0,0.45);

    align-items: center;

    justify-content: center;

    z-index: 100;

    padding: 20px;
}

.modal.show {
    display: flex;
}

.modal-box {
    background: white;

    width: 100%;

    max-width: 600px;

    border-radius: 15px;

    padding: 25px;

    max-height: 90vh;

    overflow-y: auto;
}

.modal-header {
    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 20px;
}

.close {
    border: none;

    background: transparent;

    font-size: 28px;

    cursor: pointer;
}

.modal-box label {
    display: block;

    font-weight: bold;

    margin: 15px 0 7px;
}

.modal-box input[type="text"],
.modal-box textarea {
    width: 100%;

    border: 1px solid #ccc;

    border-radius: 8px;

    padding: 12px;

    font-size: 15px;

    outline: none;
}

.modal-box textarea {
    min-height: 220px;

    resize: vertical;
}

.checkbox {
    display: flex !important;

    align-items: center;

    gap: 8px;

    font-weight: normal !important;
}

.checkbox input {
    width: auto;
}

.modal-actions {
    display: flex;

    justify-content: space-between;

    margin-top: 20px;
}

.modal-actions button {
    border: none;

    padding: 11px 18px;

    border-radius: 8px;

    cursor: pointer;

    margin-left: 5px;
}

.save {
    background: #333;

    color: white;
}

.cancel {
    background: #eee;
}

.delete {
    background: #f44336;

    color: white;
}


/* MOBILE */

@media (max-width: 700px) {

    aside {
        width: 70px;

        padding: 15px 8px;
    }

    .menu-title {
        display: none;
    }

    .menu button {
        text-align: center;

        font-size: 20px;

        padding: 12px 5px;
    }

    .menu button span {
        display: none;
    }

    .logo {
        min-width: auto;

        font-size: 20px;
    }

    header {
        padding: 0 12px;

        gap: 10px;
    }

    .new-btn {
        padding: 10px 13px;
    }

    main {
        padding: 20px 12px;
    }

    .notes-grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>


<body>


<header>

    <div class="logo">
        📝 Notes
    </div>


    <div class="search">

        🔍

        <input
            type="text"
            id="searchInput"
            placeholder="Search notes..."
        >

    </div>


    <button
        class="new-btn"
        onclick="openNewNote()"
    >
        + New Note
    </button>

</header>


<div class="layout">


<aside>

    <div class="menu-title">
        MENU
    </div>


    <div class="menu">

        <button
            class="active"
            onclick="changeSection('all', this)"
        >
            📝 <span>All Notes</span>
        </button>


        <button
            onclick="changeSection('pinned', this)"
        >
            📌 <span>Pinned</span>
        </button>


        <button
            onclick="changeSection('favorites', this)"
        >
            ⭐ <span>Favorites</span>
        </button>


        <button
            onclick="changeSection('trash', this)"
        >
            🗑️ <span>Trash</span>
        </button>

    </div>

</aside>


<main>

    <div class="top-row">

        <h1 id="sectionTitle">
            All Notes
        </h1>

        <div
            class="note-count"
            id="noteCount"
        >
            0 notes
        </div>

    </div>


    <div
        id="notesContainer"
        class="notes-grid"
    >

        <?php foreach ($notes as $note): ?>

        <?php if (!$note["deleted"]): ?>

        <div
            class="note
            <?= $note["pinned"] ? "pinned" : "" ?>"
            data-id="<?= $note["id"] ?>"
            data-title="<?= htmlspecialchars(strtolower($note["title"])) ?>"
            data-content="<?= htmlspecialchars(strtolower($note["content"])) ?>"
            data-pinned="<?= $note["pinned"] ? "true" : "false" ?>"
            data-favorite="<?= $note["favorite"] ? "true" : "false" ?>"
        >

            <div class="note-icons">

                <?= $note["pinned"] ? "📌" : "" ?>

                <?= $note["favorite"] ? "⭐" : "" ?>

            </div>


            <h3>
                <?= htmlspecialchars($note["title"]) ?>
            </h3>


            <p>
                <?= htmlspecialchars($note["content"]) ?>
            </p>


            <div class="note-date">
                <?= htmlspecialchars($note["date"]) ?>
            </div>

        </div>

        <?php endif; ?>

        <?php endforeach; ?>

    </div>

</main>

</div>


<!-- MODAL -->

<div
    class="modal"
    id="noteModal"
>

<div class="modal-box">


<div class="modal-header">

    <h2 id="modalTitle">
        New Note
    </h2>


    <button
        class="close"
        onclick="closeModal()"
    >
        ×
    </button>

</div>


<label>
    Title
</label>

<input
    type="text"
    id="titleInput"
    placeholder="Note title"
>


<label>
    Note
</label>

<textarea
    id="contentInput"
    placeholder="Write your note here..."
></textarea>


<label class="checkbox">

    <input
        type="checkbox"
        id="pinInput"
    >

    📌 Pin this note

</label>


<label class="checkbox">

    <input
        type="checkbox"
        id="favoriteInput"
    >

    ⭐ Add to favorites

</label>


<div class="modal-actions">

    <button
        class="delete"
        id="deleteButton"
        onclick="deleteCurrentNote()"
        style="display:none;"
    >
        Delete
    </button>


    <div>

        <button
            class="cancel"
            onclick="closeModal()"
        >
            Cancel
        </button>


        <button
            class="save"
            onclick="saveNote()"
        >
            Save
        </button>

    </div>

</div>


</div>

</div>


<script>

let notes =
    <?= json_encode($notes, JSON_UNESCAPED_UNICODE) ?>;

let currentNoteId = null;

let currentSection = "all";


/* =========================
   RENDER
========================= */

function renderNotes() {

    const container =
        document.getElementById(
            "notesContainer"
        );


    container.innerHTML = "";


    let visibleNotes =
        notes.filter(note => {

            if (
                currentSection === "trash"
            ) {

                return note.deleted;

            }


            if (note.deleted) {

                return false;

            }


            if (
                currentSection === "pinned"
            ) {

                return note.pinned;

            }


            if (
                currentSection === "favorites"
            ) {

                return note.favorite;

            }


            return true;

        });


    const search =
        document.getElementById(
            "searchInput"
        )
        .value
        .toLowerCase()
        .trim();


    if (search) {

        visibleNotes =
            visibleNotes.filter(note =>

                note.title
                    .toLowerCase()
                    .includes(search)

                ||

                note.content
                    .toLowerCase()
                    .includes(search)

            );

    }


    document.getElementById(
        "noteCount"
    ).textContent =
        visibleNotes.length +
        (
            visibleNotes.length === 1
                ? " note"
                : " notes"
        );


    if (!visibleNotes.length) {

        container.innerHTML = `
            <div class="empty">
                📝<br><br>
                No notes here yet.
            </div>
        `;

        return;
    }


    visibleNotes.forEach(note => {

        const card =
            document.createElement("div");


        card.className =
            "note" +
            (note.pinned ? " pinned" : "");


        card.innerHTML = `

            <div class="note-icons">

                ${note.pinned ? "📌" : ""}

                ${note.favorite ? "⭐" : ""}

            </div>

            <h3>
                ${escapeHtml(note.title)}
            </h3>

            <p>
                ${escapeHtml(note.content)}
            </p>

            <div class="note-date">
                ${escapeHtml(note.date)}
            </div>

            ${
                currentSection === "trash"
                ?
                `
                <div class="trash-actions">

                    <button
                        class="restore"
                        onclick="restoreNote(${note.id}, event)"
                    >
                        ↩ Restore
                    </button>

                    <button
                        class="permanent"
                        onclick="permanentDelete(${note.id}, event)"
                    >
                        Delete Forever
                    </button>

                </div>
                `
                :
                ""
            }

        `;


        if (
            currentSection !== "trash"
        ) {

            card.onclick = () =>
                openEditNote(note.id);

        }


        container.appendChild(card);

    });

}


/* =========================
   NEW NOTE
========================= */

function openNewNote() {

    currentNoteId = null;


    document.getElementById(
        "modalTitle"
    ).textContent = "New Note";


    document.getElementById(
        "titleInput"
    ).value = "";


    document.getElementById(
        "contentInput"
    ).value = "";


    document.getElementById(
        "pinInput"
    ).checked = false;


    document.getElementById(
        "favoriteInput"
    ).checked = false;


    document.getElementById(
        "deleteButton"
    ).style.display = "none";


    document.getElementById(
        "noteModal"
    ).classList.add("show");

}


/* =========================
   EDIT
========================= */

function openEditNote(id) {

    const note =
        notes.find(
            n => Number(n.id) === Number(id)
        );


    if (!note) return;


    currentNoteId = note.id;


    document.getElementById(
        "modalTitle"
    ).textContent = "Edit Note";


    document.getElementById(
        "titleInput"
    ).value = note.title;


    document.getElementById(
        "contentInput"
    ).value = note.content;


    document.getElementById(
        "pinInput"
    ).checked = note.pinned;


    document.getElementById(
        "favoriteInput"
    ).checked = note.favorite;


    document.getElementById(
        "deleteButton"
    ).style.display = "block";


    document.getElementById(
        "noteModal"
    ).classList.add("show");

}


/* =========================
   SAVE
========================= */

async function saveNote() {

    const title =
        document.getElementById(
            "titleInput"
        ).value.trim();


    const content =
        document.getElementById(
            "contentInput"
        ).value.trim();


    const pinned =
        document.getElementById(
            "pinInput"
        ).checked;


    const favorite =
        document.getElementById(
            "favoriteInput"
        ).checked;


    if (!title && !content) {

        alert(
            "Please enter a title or note."
        );

        return;

    }


    const data = {

        title:
            title || "Untitled",

        content:

            content,

        pinned:
            pinned,

        favorite:
            favorite

    };


    if (currentNoteId === null) {

        const response =
            await fetch(
                "?api=create",
                {

                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/json"
                    },

                    body:
                        JSON.stringify(data)

                }
            );


        const result =
            await response.json();


        notes.unshift(
            result.note
        );

    }

    else {

        data.id =
            currentNoteId;


        await fetch(
            "?api=update",
            {

                method: "POST",

                headers: {
                    "Content-Type":
                        "application/json"
                },

                body:
                    JSON.stringify(data)

            }
        );


        const note =
            notes.find(
                n => Number(n.id) ===
                     Number(currentNoteId)
            );


        if (note) {

            note.title =
                data.title;

            note.content =
                data.content;

            note.pinned =
                data.pinned;

            note.favorite =
                data.favorite;

            note.date =
                new Date()
                    .toLocaleString();

        }

    }


    closeModal();

    renderNotes();

}


/* =========================
   DELETE
========================= */

async function deleteCurrentNote() {

    if (currentNoteId === null)
        return;


    await fetch(
        "?api=delete",
        {

            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({

                id:
                    currentNoteId

            })

        }
    );


    const note =
        notes.find(
            n => Number(n.id) ===
                 Number(currentNoteId)
        );


    if (note) {

        note.deleted = true;

    }


    closeModal();

    renderNotes();

}


/* =========================
   RESTORE
========================= */

async function restoreNote(
    id,
    event
) {

    event.stopPropagation();


    await fetch(
        "?api=restore",
        {

            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({
                id: id
            })

        }
    );


    const note =
        notes.find(
            n => Number(n.id) === Number(id)
        );


    if (note) {

        note.deleted = false;

    }


    renderNotes();

}


/* =========================
   PERMANENT DELETE
========================= */

async function permanentDelete(
    id,
    event
) {

    event.stopPropagation();


    if (
        !confirm(
            "Permanently delete this note?"
        )
    ) {

        return;

    }


    await fetch(
        "?api=permanent-delete",
        {

            method: "POST",

            headers: {
                "Content-Type":
                    "application/json"
            },

            body: JSON.stringify({
                id: id
            })

        }
    );


    notes =
        notes.filter(
            n => Number(n.id) !==
                 Number(id)
        );


    renderNotes();

}


/* =========================
   CHANGE SECTION
========================= */

function changeSection(
    section,
    button
) {

    currentSection = section;


    document
        .querySelectorAll(".menu button")
        .forEach(btn =>
            btn.classList.remove("active")
        );


    button.classList.add("active");


    const titles = {

        all:
            "All Notes",

        pinned:
            "Pinned Notes",

        favorites:
            "Favorite Notes",

        trash:
            "Trash"

    };


    document.getElementById(
        "sectionTitle"
    ).textContent =
        titles[section];


    renderNotes();

}


/* =========================
   SEARCH
========================= */

document.getElementById(
    "searchInput"
).addEventListener(
    "input",
    renderNotes
);


/* =========================
   CLOSE MODAL
========================= */

function closeModal() {

    document.getElementById(
        "noteModal"
    ).classList.remove("show");

}


/* =========================
   ESCAPE HTML
========================= */

function escapeHtml(text) {

    const div =
        document.createElement("div");

    div.textContent = text;

    return div.innerHTML;

}


/* =========================
   CLOSE OUTSIDE
========================= */

document.getElementById(
    "noteModal"
).addEventListener(
    "click",
    function(event) {

        if (
            event.target === this
        ) {

            closeModal();

        }

    }
);


/* =========================
   INITIALIZE
========================= */

renderNotes();

</script>


</body>

</html>