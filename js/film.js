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

// Watchlist functionality
function addToWatchlist(mediaId, mediaType, button) {
    // Show loading state
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    button.disabled = true;
    
    fetch('../api/add-to-watchlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            media_id: mediaId,
            media_type: mediaType,
            status: 'plan_to_watch'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success state
            button.innerHTML = '<i class="fas fa-bookmark text-orange-500"></i>';
            button.style.color = '#ff8c00';
            showNotification(data.message, 'success');
        } else {
            // Error state
            button.innerHTML = originalHTML;
            button.disabled = false;
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalHTML;
        button.disabled = false;
        showNotification('Erreur de connexion', 'error');
    });
}

function showLoginPrompt() {
    if (confirm('Connectez-vous pour ajouter des films à votre watchlist!')) {
        window.location.href = 'connexion.php?redirect=' + encodeURIComponent(window.location.href);
    }
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.custom-notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `custom-notification fixed top-4 right-4 px-6 py-3 rounded-lg text-white font-semibold z-50 transition-all duration-300 transform translate-x-32 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
    notification.textContent = message;
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <i class="fas ${type === 'success' ? 'fa-check' : type === 'error' ? 'fa-exclamation-triangle' : 'fa-info'}"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-32');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-32');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Quick status change for watchlist items
function quickStatusChange(selectionId, newStatus) {
    fetch('../api/update-watchlist-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            selection_id: selectionId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Statut mis à jour!', 'success');
            // Reload the page to show updated status
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.message, 'error');
        }
    });
}