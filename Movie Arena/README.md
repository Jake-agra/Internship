# MovieArena - Netflix-style Movie Streaming Website

A modern, responsive movie streaming website built with HTML, CSS, PHP, and JavaScript that mimics Netflix's interface while providing Moviebox functionality.

## 🎬 Features

### Core Features
- **User Authentication**: Secure login and registration system
- **Movie Dashboard**: Browse and search through a curated collection of movies
- **TV Series Page**: Dedicated page for TV series content
- **Favorites System**: Save your favorite movies and shows
- **Category Filtering**: Filter content by genre/category
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

### Netflix-style Interface
- **Modern UI/UX**: Clean, dark theme with Netflix-inspired design
- **Hero Sections**: Engaging landing pages with call-to-action buttons
- **Movie Cards**: Hover effects and smooth animations
- **Fixed Header**: Transparent header that becomes solid on scroll
- **Search Functionality**: Real-time search with instant results
- **Category Filters**: Easy navigation through different content types

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP

### Installation

1. **Clone or download the project**
   ```bash
   # If using git
   git clone <repository-url>
   cd Movie-Arena
   ```

2. **Set up the database**
   - Create a MySQL database named `moviearena`
   - Import the `setup_database.sql` file or run the SQL commands manually
   - Update database credentials in `includes/db.php` if needed

3. **Configure the web server**
   - Place the project files in your web server's document root
   - Ensure PHP and MySQL are properly configured

4. **Access the website**
   - Open your browser and navigate to `http://localhost/Movie-Arena`
   - The homepage should load with the Netflix-style interface

## 📁 Project Structure

```
Movie-Arena/
├── assets/
│   └── css/
│       ├── style.css          # Main stylesheet
│       └── images/            # Image assets
├── includes/
│   ├── db.php                # Database connection
│   └── auth.php              # Authentication functions
├── index.html                # Homepage
├── dashboard.php             # Main movie browsing page
├── seriesPage.html           # TV series page
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # Logout functionality
├── aboutUs.html              # About page
├── contactUs.html            # Contact page
├── setup_database.sql        # Database setup script
└── README.md                 # This file
```

## 🎨 Design Features

### Netflix-inspired Elements
- **Dark Theme**: Deep blacks and reds (#141414, #e50914)
- **Gradient Overlays**: Beautiful hero sections with movie backgrounds
- **Card Hover Effects**: Smooth scaling and shadow effects
- **Typography**: Clean, modern fonts with proper hierarchy
- **Icons**: Font Awesome icons throughout the interface
- **Responsive Grid**: CSS Grid for flexible movie layouts

### Interactive Elements
- **Hover Animations**: Cards scale and show additional information
- **Smooth Transitions**: All interactions have smooth CSS transitions
- **Search Functionality**: Real-time filtering of content
- **Category Filters**: Dynamic filtering by movie categories
- **Scroll Effects**: Header changes appearance on scroll

## 🔧 Customization

### Adding New Movies
1. Access your MySQL database
2. Insert new records into the `movies` table:
   ```sql
   INSERT INTO movies (title, description, category, image_path, release_year, rating) 
   VALUES ('Movie Title', 'Description', 'Category', 'image_url', 2024, 8.5);
   ```

### Styling Changes
- Main styles are in `assets/css/style.css`
- Color scheme can be modified by changing CSS variables
- Layout adjustments can be made in the grid and flexbox properties

### Adding New Pages
1. Create new HTML/PHP files following the existing structure
2. Include the header and footer components
3. Add navigation links in the header
4. Style new content using the existing CSS classes

## 🎯 Key Pages

### Homepage (`index.html`)
- Netflix-style hero section
- Featured content cards
- Call-to-action buttons
- Responsive navigation

### Dashboard (`dashboard.php`)
- Movie browsing interface
- Search and filter functionality
- Save to favorites feature
- Category filtering

### TV Series (`seriesPage.html`)
- Dedicated series content
- Consistent styling with movies
- Interactive play and save buttons

### Authentication (`login.php`, `register.php`)
- Modern form design
- Form validation
- Error handling
- Secure password hashing

## 🔒 Security Features

- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: Prepared statements for all database queries
- **Session Management**: Secure session handling for user authentication
- **Input Validation**: Server-side validation for all user inputs

## 📱 Responsive Design

The website is fully responsive and works on:
- **Desktop**: Full-featured experience with hover effects
- **Tablet**: Optimized layout for medium screens
- **Mobile**: Touch-friendly interface with simplified navigation

## 🚀 Performance Optimizations

- **CSS Grid**: Efficient layout system for movie cards
- **Optimized Images**: High-quality movie posters from TMDB
- **Minimal JavaScript**: Lightweight interactions
- **Database Indexing**: Optimized queries with proper indexes

## 🛠️ Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/db.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **Images Not Loading**
   - Check internet connection (images are loaded from TMDB)
   - Verify image URLs are accessible

3. **Styling Issues**
   - Clear browser cache
   - Check if CSS file is loading properly
   - Verify Font Awesome CDN is accessible

## 📈 Future Enhancements

Potential features to add:
- **Video Player**: Actual video streaming functionality
- **User Profiles**: Personalized user dashboards
- **Reviews & Ratings**: User-generated content
- **Watchlist**: Separate from favorites
- **Recommendations**: AI-powered content suggestions
- **Admin Panel**: Content management system

## 📄 License

This project is for educational purposes. Movie images and data are sourced from The Movie Database (TMDB).

## 🤝 Contributing

Feel free to contribute to this project by:
- Reporting bugs
- Suggesting new features
- Improving documentation
- Enhancing the design

---

**Enjoy your MovieArena experience! 🎬✨** 