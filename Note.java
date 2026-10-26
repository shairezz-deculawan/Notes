
public class Note {

    // Stores the information and status of a single note.
    private int id;
    private String title;
    private String content;
    private String color;
    private String category;
    private boolean pinned;
    private boolean favorite;
    private boolean deleted;
    private String date;

    // Constructor initializes a note with its provided details.
    public Note(int id, String title, String content, String color,
                String category, boolean pinned, boolean favorite,
                boolean deleted, String date) {

        this.id = id;
        this.title = title;
        this.content = content;
        this.color = color;
        this.category = category;
        this.pinned = pinned;
        this.favorite = favorite;
        this.deleted = deleted;
        this.date = date;
    }

    // Getter methods return the values of the note's fields.
    public int getId() {
        return id;
    }

    public String getTitle() {
        return title;
    }

    public String getContent() {
        return content;
    }

    public String getColor() {
        return color;
    }

    public String getCategory() {
        return category;
    }

    public boolean isPinned() {
        return pinned;
    }

    public boolean isFavorite() {
        return favorite;
    }

    public boolean isDeleted() {
        return deleted;
    }

    public String getDate() {
        return date;
    }

    // Setter methods update the note's editable information and status.
    public void setTitle(String title) {
        this.title = title;
    }

    public void setContent(String content) {
        this.content = content;
    }

    public void setColor(String color) {
        this.color = color;
    }

    public void setCategory(String category) {
        this.category = category;
    }

    public void setPinned(boolean pinned) {
        this.pinned = pinned;
    }

    public void setFavorite(boolean favorite) {
        this.favorite = favorite;
    }

    public void setDeleted(boolean deleted) {
        this.deleted = deleted;
    }

    public void setDate(String date) {
        this.date = date;
    }
}