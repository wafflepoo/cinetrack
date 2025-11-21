// ../js/films.js

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filters-form');
    const filterSelects = filterForm.querySelectorAll('select');
    
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Add loading state
    const searchBox = document.getElementById('film-search');
    let searchTimeout;
    
    searchBox.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            filterForm.submit();
        }, 500);
    });
});

function viewFilm(filmId) {
    window.location.href = `film-details.php?id=${filmId}`;
}

// Favorite functionality
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-favorite')) {
        const btn = e.target.closest('.btn-favorite');
        const filmId = btn.dataset.id;
        const isActive = btn.classList.contains('active');
        
        toggleFavorite(filmId, 'film', !isActive, btn);
    }
});

function toggleFavorite(id, type, add, element) {
    // Add your favorite logic here
    if (add) {
        element.classList.add('active');
        element.querySelector('i').className = 'fas fa-heart';
        showNotification('Film ajouté aux favoris');
    } else {
        element.classList.remove('active');
        element.querySelector('i').className = 'far fa-heart';
        showNotification('Film retiré des favoris');
    }
}

function showNotification(message) {
    // Add notification logic
    console.log(message);
}