<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_email = $_SESSION['user_email'];
$user_id = $_SESSION['user_id'];

// Handle favorite saving
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_favorite'])) {
    $movie_id = $_POST['movie_id'];

    // Check if favorite exists
    $check = $conn->prepare("SELECT * FROM favorites WHERE user_id = ? AND movie_id = ?");
    $check->bind_param("ii", $user_id, $movie_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows === 0) {
        $insert = $conn->prepare("INSERT INTO favorites (user_id, movie_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $movie_id);
        $insert->execute();
    }
}

// Fetch movies (with optional filter)
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT * FROM movies WHERE 1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND title LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

if (!empty($category)) {
    $query .= " AND category = ?";
    $params[] = $category;
    $types .= "s";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$movies = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MovieArena - Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

  <header>
    <h1>MovieArena</h1>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <div class="container">
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search movies...">
    </div>

    <div class="movies" id="movieList">
      <?php while ($movie = $movies->fetch_assoc()) : ?>
        <div class="movie-card">
          <img src="<?php echo $movie['image_path']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
          <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
          <p><?php echo htmlspecialchars($movie['description']); ?></p>
          <form method="POST" action="dashboard.php">
              <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
              <button type="submit" name="save_favorite" class="save-button">❤️ Save</button>
          </form>
        </div>
      <?php endwhile; ?>
    </div>
  </div>

  <script>
    const searchInput = document.getElementById("searchInput");
    const movieList = document.getElementById("movieList");
    const movieCards = movieList.getElementsByClassName("movie-card");

    searchInput.addEventListener("input", () => {
      const filter = searchInput.value.toLowerCase();
      Array.from(movieCards).forEach(card => {
        const title = card.querySelector("h3").textContent.toLowerCase();
        card.style.display = title.includes(filter) ? "block" : "none";
      });
    });
  </script>

</body>
</html>
