
import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpServer;
import java.io.*;
import java.net.InetSocketAddress;
import java.net.URLDecoder;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

public class NotesServer {

    // Defines the server port and stores all notes in memory.
    private static final int PORT = 8080;
    private static final List<Note> notes = new ArrayList<>();
    private static int nextId = 1;

    // Sets the format used to display note dates and times.
    private static final DateTimeFormatter DATE_FORMAT =
            DateTimeFormatter.ofPattern("MMM dd, yyyy HH:mm");

    // Starts the server and registers the available pages and API endpoints.
    public static void main(String[] args) throws Exception {

        loadSampleNotes();

        HttpServer server = HttpServer.create(
                new InetSocketAddress(PORT), 0
        );

        // Connects each URL endpoint to its corresponding handler method.
        server.createContext("/", NotesServer::handleHome);
        server.createContext("/api/notes", NotesServer::handleNotes);
        server.createContext("/api/create", NotesServer::handleCreate);
        server.createContext("/api/update", NotesServer::handleUpdate);
        server.createContext("/api/delete", NotesServer::handleDelete);
        server.createContext("/api/restore", NotesServer::handleRestore);
        server.createContext("/api/permanent-delete",
                NotesServer::handlePermanentDelete);
        server.createContext("/api/toggle-pin",
                NotesServer::handleTogglePin);
        server.createContext("/api/toggle-favorite",
                NotesServer::handleToggleFavorite);

        server.setExecutor(null);
        server.start();

        System.out.println("======================================");
        System.out.println("      NOTES CLONE - JAVA SERVER");
        System.out.println("======================================");
        System.out.println("Server running at:");
        System.out.println("http://localhost:" + PORT);
        System.out.println("Press CTRL+C to stop.");
    }

    // HOME PAGE: Serves index.html when the browser opens the root URL.
    private static void handleHome(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestURI().getPath().equals("/")) {
            sendResponse(exchange, 404, "Not Found", "text/plain");
            return;
        }

        String html = readIndexFile();

        sendResponse(
                exchange,
                200,
                html,
                "text/html"
        );
    }

    // GET NOTES: Returns all notes as a JSON array for the frontend.
    private static void handleNotes(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("GET")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        StringBuilder json = new StringBuilder("[");
        boolean first = true;

        for (Note note : notes) {
            if (!first) {
                json.append(",");
            }

            json.append(noteToJson(note));
            first = false;
        }

        json.append("]");

        sendResponse(
                exchange,
                200,
                json.toString(),
                "application/json"
        );
    }

    // CREATE NOTE: Reads submitted form data and adds a new note.
    private static void handleCreate(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);

        // Uses default values when optional fields are missing.
        String title = data.getOrDefault("title", "Untitled");
        String content = data.getOrDefault("content", "");
        String color = data.getOrDefault("color", "#fff9c4");
        String category = data.getOrDefault("category", "Personal");
        String date = getCurrentDate();

        Note note = new Note(
                nextId++,
                title,
                content,
                color,
                category,
                false,
                false,
                false,
                date
        );

        notes.add(note);

        sendResponse(
                exchange,
                200,
                noteToJson(note),
                "application/json"
        );
    }

    // UPDATE NOTE: Finds an existing note and changes its information.
    private static void handleUpdate(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);
        int id = parseInt(data.get("id"));

        Note note = findNote(id);

        // Returns an error if the requested note does not exist.
        if (note == null) {
            sendResponse(exchange, 404, "Note not found", "text/plain");
            return;
        }

        note.setTitle(data.getOrDefault("title", note.getTitle()));
        note.setContent(data.getOrDefault("content", note.getContent()));
        note.setColor(data.getOrDefault("color", note.getColor()));
        note.setCategory(
                data.getOrDefault("category", note.getCategory())
        );
        note.setDate(getCurrentDate());

        sendResponse(
                exchange,
                200,
                noteToJson(note),
                "application/json"
        );
    }

    // DELETE NOTE: Moves a note to the trash instead of removing it.
    private static void handleDelete(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);
        int id = parseInt(data.get("id"));

        Note note = findNote(id);

        if (note == null) {
            sendResponse(exchange, 404, "Note not found", "text/plain");
            return;
        }

        note.setDeleted(true);
        note.setDate(getCurrentDate());

        sendResponse(
                exchange,
                200,
                "{\"success\":true}",
                "application/json"
        );
    }

    // RESTORE NOTE: Removes the deleted status so the note can be recovered.
    private static void handleRestore(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);
        int id = parseInt(data.get("id"));

        Note note = findNote(id);

        if (note == null) {
            sendResponse(exchange, 404, "Note not found", "text/plain");
            return;
        }

        note.setDeleted(false);
        note.setDate(getCurrentDate());

        sendResponse(
                exchange,
                200,
                "{\"success\":true}",
                "application/json"
        );
    }

    // PERMANENT DELETE: Removes a note completely from the list.
    private static void handlePermanentDelete(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);
        int id = parseInt(data.get("id"));

        Note note = findNote(id);

        if (note == null) {
            sendResponse(exchange, 404, "Note not found", "text/plain");
            return;
        }

        notes.remove(note);

        sendResponse(
                exchange,
                200,
                "{\"success\":true}",
                "application/json"
        );
    }

    // PIN / UNPIN: Reverses the current pinned status of a note.
    private static void handleTogglePin(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);
        int id = parseInt(data.get("id"));

        Note note = findNote(id);

        if (note == null) {
            sendResponse(exchange, 404, "Note not found", "text/plain");
            return;
        }

        note.setPinned(!note.isPinned());

        sendResponse(
                exchange,
                200,
                "{\"success\":true}",
                "application/json"
        );
    }

    // FAVORITE: Toggles whether a note is marked as a favorite.
    private static void handleToggleFavorite(HttpExchange exchange)
            throws IOException {

        if (!exchange.getRequestMethod().equals("POST")) {
            sendResponse(exchange, 405, "Method Not Allowed", "text/plain");
            return;
        }

        String body = readBody(exchange);
        Map<String, String> data = parseForm(body);
        int id = parseInt(data.get("id"));

        Note note = findNote(id);

        if (note == null) {
            sendResponse(exchange, 404, "Note not found", "text/plain");
            return;
        }

        note.setFavorite(!note.isFavorite());

        sendResponse(
                exchange,
                200,
                "{\"success\":true}",
                "application/json"
        );
    }

    // FIND NOTE: Searches the list using a note's unique ID.
    private static Note findNote(int id) {
        for (Note note : notes) {
            if (note.getId() == id) {
                return note;
            }
        }

        return null;
    }

    // SAMPLE NOTES: Adds three example notes when the server starts.
    private static void loadSampleNotes() {

        notes.add(new Note(
                nextId++,
                "Welcome to Notes",
                "This is your first note. You can edit, pin, favorite, or delete it.",
                "#fff9c4",
                "Personal",
                true,
                false,
                false,
                getCurrentDate()
        ));

        notes.add(new Note(
                nextId++,
                "School Tasks",
                "Finish the MCO1 project and prepare for the presentation.",
                "#d9f7be",
                "School",
                false,
                true,
                false,
                getCurrentDate()
        ));

        notes.add(new Note(
                nextId++,
                "Shopping List",
                "Notebook\nBallpen\nFolder\nBond paper",
                "#bae7ff",
                "Shopping",
                false,
                false,
                false,
                getCurrentDate()
        ));
    }

    // JSON: Converts a Note object into JSON for communication with the frontend.
    private static String noteToJson(Note note) {
        return "{"
                + "\"id\":" + note.getId() + ","
                + "\"title\":\"" + escapeJson(note.getTitle()) + "\","
                + "\"content\":\"" + escapeJson(note.getContent()) + "\","
                + "\"color\":\"" + escapeJson(note.getColor()) + "\","
                + "\"category\":\"" + escapeJson(note.getCategory()) + "\","
                + "\"pinned\":" + note.isPinned() + ","
                + "\"favorite\":" + note.isFavorite() + ","
                + "\"deleted\":" + note.isDeleted() + ","
                + "\"date\":\"" + escapeJson(note.getDate()) + "\""
                + "}";
    }

    // Escapes special characters so note text can be placed safely in JSON.
    private static String escapeJson(String value) {
        if (value == null) {
            return "";
        }

        return value
                .replace("\\", "\\\\")
                .replace("\"", "\\\"")
                .replace("\n", "\\n")
                .replace("\r", "\\r");
    }

    // FORM DATA: Decodes URL-encoded key-value pairs submitted by the frontend.
    private static Map<String, String> parseForm(String body) {
        Map<String, String> data = new HashMap<>();

        if (body == null || body.isEmpty()) {
            return data;
        }

        String[] pairs = body.split("&");

        for (String pair : pairs) {
            String[] parts = pair.split("=", 2);

            if (parts.length == 2) {
                try {
                    String key = URLDecoder.decode(
                            parts[0],
                            StandardCharsets.UTF_8
                    );

                    String value = URLDecoder.decode(
                            parts[1],
                            StandardCharsets.UTF_8
                    );

                    data.put(key, value);
                } catch (Exception ignored) {
                    // Skips a form parameter that cannot be decoded.
                }
            }
        }

        return data;
    }

    // READ REQUEST BODY: Reads the data sent by the browser.
    private static String readBody(HttpExchange exchange)
            throws IOException {

        InputStream input = exchange.getRequestBody();

        return new String(
                input.readAllBytes(),
                StandardCharsets.UTF_8
        );
    }

    // DATE: Returns the current date and time in the chosen format.
    private static String getCurrentDate() {
        return LocalDateTime.now().format(DATE_FORMAT);
    }

    // INTEGER: Converts a string to an ID, returning -1 if conversion fails.
    private static int parseInt(String value) {
        try {
            return Integer.parseInt(value);
        } catch (Exception e) {
            return -1;
        }
    }

    // READ HTML: Loads the frontend page from index.html.
    private static String readIndexFile() throws IOException {
        Path path = Paths.get("index.html");

        return Files.readString(
                path,
                StandardCharsets.UTF_8
        );
    }

    // SEND RESPONSE: Sends the HTTP status, content type, and response data.
    private static void sendResponse(
            HttpExchange exchange,
            int status,
            String response,
            String contentType
    ) throws IOException {

        exchange.getResponseHeaders().set(
                "Content-Type",
                contentType + "; charset=UTF-8"
        );

        byte[] bytes = response.getBytes(StandardCharsets.UTF_8);

        exchange.sendResponseHeaders(
                status,
                bytes.length
        );

        OutputStream output = exchange.getResponseBody();
        output.write(bytes);
        output.close();
    }
}