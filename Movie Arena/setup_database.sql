-- MovieArena Database Setup Script
-- Run this script in your MySQL database to create the necessary tables

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS moviearena;
USE moviearena;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create movies table
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    image_path VARCHAR(500),
    release_year INT,
    rating DECIMAL(3,1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create favorites table
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    movie_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, movie_id)
);

-- Insert sample movies
INSERT INTO movies (title, description, category, image_path, release_year, rating) VALUES
('The Shawshank Redemption', 'Two imprisoned men bond over a number of years, finding solace and eventual redemption through acts of common decency.', 'Drama', 'https://image.tmdb.org/t/p/w500/q6y0Go1tsGEsmtFryDOJo3dEmqu.jpg', 1994, 9.3),
('The Godfather', 'The aging patriarch of an organized crime dynasty transfers control of his clandestine empire to his reluctant son.', 'Crime', 'https://image.tmdb.org/t/p/w500/3bhkrj58Vtu7enYsRolD1fZdja1.jpg', 1972, 9.2),
('Pulp Fiction', 'The lives of two mob hitmen, a boxer, a gangster and his wife, and a pair of diner bandits intertwine in four tales of violence and redemption.', 'Crime', 'https://image.tmdb.org/t/p/w500/d5iIlFn5s0ImszYzBPb8JPIfbXD.jpg', 1994, 8.9),
('The Dark Knight', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 'Action', 'https://image.tmdb.org/t/p/w500/qJ2tW6WMUDux911r6m7haRef0WH.jpg', 2008, 9.0),
('Fight Club', 'An insomniac office worker and a devil-may-care soapmaker form an underground fight club that evolves into something much, much more.', 'Drama', 'https://image.tmdb.org/t/p/w500/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg', 1999, 8.8),
('Inception', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', 'Sci-Fi', 'https://image.tmdb.org/t/p/w500/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg', 2010, 8.8),
('The Matrix', 'A computer programmer discovers that reality as he knows it is a simulation created by machines, and joins a rebellion to break free.', 'Sci-Fi', 'https://image.tmdb.org/t/p/w500/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg', 1999, 8.7),
('Goodfellas', 'The story of Henry Hill and his life in the mob, covering his relationship with his wife Karen Hill and his mob partners Jimmy Conway and Tommy DeVito.', 'Crime', 'https://image.tmdb.org/t/p/w500/aKuFiU82suyIS7oUYvK3RizMtGn.jpg', 1990, 8.7),
('The Silence of the Lambs', 'A young F.B.I. cadet must receive the help of an incarcerated and manipulative cannibal killer to help catch another serial killer, a madman who skins his victims.', 'Thriller', 'https://image.tmdb.org/t/p/w500/rplLJ2hPcOQmkFhTqUte0MkEaO2.jpg', 1991, 8.6),
('Interstellar', 'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.', 'Sci-Fi', 'https://image.tmdb.org/t/p/w500/gEU2QniE6E77NI6lCU6MxlNBvIx.jpg', 2014, 8.6),
('The Lion King', 'Lion prince Simba and his father are targeted by his bitter uncle, who wants to ascend the throne himself.', 'Animation', 'https://image.tmdb.org/t/p/w500/2bXbqYdUdNVa8VIWXVfclP2ICtT.jpg', 1994, 8.5),
('Gladiator', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.', 'Action', 'https://image.tmdb.org/t/p/w500/6WBIzCgmDElK0d0c3d6V3ULC1ZQ.jpg', 2000, 8.5);

-- Create indexes for better performance
CREATE INDEX idx_movies_category ON movies(category);
CREATE INDEX idx_movies_title ON movies(title);
CREATE INDEX idx_favorites_user ON favorites(user_id);
CREATE INDEX idx_favorites_movie ON favorites(movie_id);

-- Show success message
SELECT 'Database setup completed successfully!' as message; 