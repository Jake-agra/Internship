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

// Get unique categories for filter
$cat_query = "SELECT DISTINCT category FROM movies WHERE category IS NOT NULL AND category != ''";
$cat_result = $conn->query($cat_query);
$categories = [];
while ($cat = $cat_result->fetch_assoc()) {
    $categories[] = $cat['category'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MovieArena - Dashboard</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <header id="header" class="scrolled">
    <div class="header-content">
      <h1><i class="fas fa-film"></i> MovieArena</h1>
      <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="seriesPage.php">TV Series</a>
        <a href="aboutUs.php">About</a>
        <a href="contactUs.php">Contact</a>
        <span class="user-info">
          <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_email); ?>
        </span>
        <a href="logout.php" class="logout-btn">Logout</a>
      </nav>
    </div>
  </header>

  <div class="search-container">
    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search for movies, TV shows, and more...">
      <i class="fas fa-search search-icon"></i>
    </div>
  </div>

  <div class="movies-section">
    <div class="container">
      <!-- Category Filter -->
      <div class="category-filter">
        <button class="category-btn active" data-category="">All</button>
        <?php foreach ($categories as $cat): ?>
          <button class="category-btn" data-category="<?php echo htmlspecialchars($cat); ?>">
            <?php echo htmlspecialchars($cat); ?>
          </button>
        <?php endforeach; ?>
      </div>

      <h2 class="section-title">Movies & TV Shows</h2>
      
      <div class="movies" id="movieList">
        <?php if ($movies->num_rows > 0): ?>
          <?php while ($movie = $movies->fetch_assoc()) : ?>
            <div class="movie-card" data-category="<?php echo htmlspecialchars($movie['category'] ?? ''); ?>">
              <img src="<?php echo $movie['image_path']; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
              <div class="movie-info">
                <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                <p><?php echo htmlspecialchars($movie['description']); ?></p>
                <div class="movie-actions">
                  <form method="POST" action="dashboard.php" style="display: inline;">
                    <input type="hidden" name="movie_id" value="<?php echo $movie['id']; ?>">
                    <button type="submit" name="save_favorite" class="save-button">
                      <i class="fas fa-heart"></i> Save
                    </button>
                  </form>
                  <button class="play-button">
                    <i class="fas fa-play"></i> Play
                  </button>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No movies found</h3>
            <p>Try adjusting your search or browse our categories</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-content">
      <div class="footer-links">
        <a href="index.php">Home</a>
        <a href="seriesPage.php">TV Series</a>
        <a href="aboutUs.php">About Us</a>
        <a href="contactUs.php">Contact</a>
      </div>
      <div class="social-links">
        <a href="#"><i class="fab fa-facebook"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
        <a href="#"><i class="fab fa-youtube"></i></a>
      </div>
      <p>&copy; 2025 MovieArena. All rights reserved.</p>
    </div>
  </footer>

  <script>
    // Search functionality
    const searchInput = document.getElementById("searchInput");
    const movieList = document.getElementById("movieList");
    const movieCards = movieList.getElementsByClassName("movie-card");

    searchInput.addEventListener("input", () => {
      const filter = searchInput.value.toLowerCase();
      Array.from(movieCards).forEach(card => {
        const title = card.querySelector("h3").textContent.toLowerCase();
        const description = card.querySelector("p").textContent.toLowerCase();
        const isVisible = title.includes(filter) || description.includes(filter);
        card.style.display = isVisible ? "block" : "none";
      });
    });

    // Category filter functionality
    const categoryBtns = document.querySelectorAll('.category-btn');
    
    categoryBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Remove active class from all buttons
        categoryBtns.forEach(b => b.classList.remove('active'));
        // Add active class to clicked button
        btn.classList.add('active');
        
        const selectedCategory = btn.getAttribute('data-category');
        
        Array.from(movieCards).forEach(card => {
          const cardCategory = card.getAttribute('data-category');
          if (selectedCategory === '' || cardCategory === selectedCategory) {
            card.style.display = 'block';
          } else {
            card.style.display = 'none';
          }
        });
      });
    });

    // Header scroll effect
    window.addEventListener('scroll', function() {
      const header = document.getElementById('header');
      if (window.scrollY > 50) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // Play button functionality (placeholder)
    document.querySelectorAll('.play-button').forEach(btn => {
      btn.addEventListener('click', function() {
        const movieCard = this.closest('.movie-card');
        const movieTitle = movieCard.querySelector('h3').textContent;
        alert(`Playing: ${movieTitle}`);
        // Here you would typically redirect to a video player page
      });
    });
  </script>

</body>
</html>
