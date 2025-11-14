// Script principal pour CineTrack
document.addEventListener('DOMContentLoaded', function() {
    initializeHeader();
    initializeNavigation();
    initializeSearch();
    initializeFavorites();
    initializeForms();
    initializeTooltips();
    initializeLazyLoading();
});

// Initialisation du header
function initializeHeader() {
    const header = document.querySelector('.main-header');
    const main = document.querySelector('main');
    
    if (header && main) {
        // Ajuster la marge du main en fonction de la hauteur du header
        const headerHeight = header.offsetHeight;
        main.style.marginTop = headerHeight + 'px';
        
        // Gestion du scroll
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }
}

// Initialisation de la navigation
function initializeNavigation() {
    // Menu hamburger mobile
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navAuth = document.querySelector('.nav-auth');
    
    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            if (navAuth) navAuth.classList.toggle('active');
        });
    }
    
    // Fermer le menu en cliquant sur un lien
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (hamburger) hamburger.classList.remove('active');
            if (navMenu) navMenu.classList.remove('active');
            if (navAuth) navAuth.classList.remove('active');
        });
    });
    
    // Gestion des dropdowns
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                const dropdown = this.closest('.dropdown');
                dropdown.classList.toggle('active');
            }
        });
    });
    
    // Fermer les dropdowns en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
}

// Initialisation de la recherche
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (this.value.length >= 2) {
                    performSearch(this.value);
                } else {
                    clearSearchResults();
                }
            }, 500);
        });
        
        // Fermer les résultats en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                clearSearchResults();
            }
        });
    }
}

// Fonction de recherche
async function performSearch(query) {
    try {
        showLoading('search-results');
        
        // Simulation de recherche - À remplacer par votre API
        const response = await fetch(`includes/search.php?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        
        displaySearchResults(results);
    } catch (error) {
        console.error('Erreur de recherche:', error);
        showError('search-results', 'Erreur lors de la recherche');
    }
}

// Affichage des résultats de recherche
function displaySearchResults(results) {
    const resultsContainer = document.getElementById('search-results');
    if (!resultsContainer) return;
    
    resultsContainer.innerHTML = '';
    
    if (!results || results.length === 0) {
        resultsContainer.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>Aucun résultat trouvé</p>
            </div>
        `;
        return;
    }
    
    results.forEach(result => {
        const resultElement = document.createElement('div');
        resultElement.className = 'search-result-item';
        resultElement.innerHTML = `
            <img src="${result.poster || 'images/placeholder.jpg'}" 
                 alt="${result.titre}" 
                 class="result-poster"
                 loading="lazy">
            <div class="result-info">
                <h4>${result.titre}</h4>
                <p class="result-type">${result.type === 'film' ? '🎬 Film' : '📺 Série'}</p>
                <p class="result-year">${result.annee || 'N/A'}</p>
                ${result.note ? `<span class="result-rating"><i class="fas fa-star"></i> ${result.note}</span>` : ''}
            </div>
            <i class="fas fa-chevron-right result-arrow"></i>
        `;
        
        resultElement.addEventListener('click', () => {
            window.location.href = `${result.type === 'film' ? 'film' : 'serie'}.php?id=${result.id}`;
        });
        
        // Accessibilité
        resultElement.setAttribute('role', 'button');
        resultElement.setAttribute('tabindex', '0');
        resultElement.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                window.location.href = `${result.type === 'film' ? 'film' : 'serie'}.php?id=${result.id}`;
            }
        });
        
        resultsContainer.appendChild(resultElement);
    });
}

// Effacement des résultats de recherche
function clearSearchResults() {
    const resultsContainer = document.getElementById('search-results');
    if (resultsContainer) {
        resultsContainer.innerHTML = '';
    }
}

// Initialisation des favoris
function initializeFavorites() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            const contentId = this.dataset.id;
            const contentType = this.dataset.type;
            toggleFavorite(contentId, contentType, this);
        });
        
        // Accessibilité
        button.setAttribute('role', 'button');
        button.setAttribute('tabindex', '0');
        button.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                const contentId = button.dataset.id;
                const contentType = button.dataset.type;
                toggleFavorite(contentId, contentType, button);
            }
        });
    });
}

// Gestion des favoris
async function toggleFavorite(contentId, contentType, button) {
    if (!contentId || !contentType) {
        console.error('ID ou type de contenu manquant');
        return;
    }
    
    try {
        // Ajouter une classe de loading
        button.classList.add('loading');
        
        // Simulation - À remplacer par votre API
        const response = await fetch('includes/favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                contentId: contentId,
                contentType: contentType
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Mise à jour de l'interface
            button.classList.toggle('active', result.isFavorite);
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = result.isFavorite ? 
                    'fas fa-heart' : 'far fa-heart';
            }
            
            // Notification
            showNotification(
                result.isFavorite ? 
                    '✅ Ajouté aux favoris' : 
                    '📝 Retiré des favoris',
                result.isFavorite ? 'success' : 'info'
            );
        } else {
            throw new Error(result.message || 'Erreur inconnue');
        }
        
    } catch (error) {
        console.error('Erreur favori:', error);
        showNotification('❌ Erreur lors de la mise à jour', 'error');
    } finally {
        button.classList.remove('loading');
    }
}

// Initialisation des formulaires
function initializeForms() {
    const forms = document.querySelectorAll('form[data-validate="true"]');
    
    forms.forEach(form => {
        // Validation en temps réel
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
        
        // Validation à la soumission
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showNotification('❌ Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
    });
}

// Validation de formulaire
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

// Validation d'un champ
function validateField(input) {
    const value = input.value.trim();
    let isValid = true;
    let message = '';
    
    clearFieldError(input);
    
    // Validation required
    if (input.hasAttribute('required') && !value) {
        isValid = false;
        message = 'Ce champ est obligatoire';
    }
    
    // Validation email
    if (input.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            message = 'Veuillez entrer une adresse email valide';
        }
    }
    
    // Validation mot de passe
    if (input.type === 'password' && value) {
        if (value.length < 6) {
            isValid = false;
            message = 'Le mot de passe doit contenir au moins 6 caractères';
        }
    }
    
    // Validation confirmation mot de passe
    if (input.name === 'confirm_password' && value) {
        const password = document.querySelector('input[name="password"]');
        if (password && password.value !== value) {
            isValid = false;
            message = 'Les mots de passe ne correspondent pas';
        }
    }
    
    if (!isValid) {
        showFieldError(input, message);
    }
    
    return isValid;
}

// Affichage d'erreur de champ
function showFieldError(input, message) {
    input.classList.add('error');
    
    let errorDiv = input.parentNode.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        input.parentNode.appendChild(errorDiv);
    }
    
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}

// Effacement d'erreur de champ
function clearFieldError(input) {
    input.classList.remove('error');
    
    const errorDiv = input.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.style.display = 'none';
    }
}

// Initialisation des tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
        element.addEventListener('focus', showTooltip);
        element.addEventListener('blur', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = this.getAttribute('data-tooltip');
    if (!tooltipText) return;
    
    // Supprimer tout tooltip existant
    hideTooltip.call(this);
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = tooltipText;
    tooltip.id = 'current-tooltip';
    
    document.body.appendChild(tooltip);
    
    // Positionnement
    const rect = this.getBoundingClientRect();
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = (rect.top + scrollTop) - tooltip.offsetHeight - 10 + 'px';
    
    this.currentTooltip = tooltip;
}

function hideTooltip() {
    if (this.currentTooltip) {
        document.body.removeChild(this.currentTooltip);
        this.currentTooltip = null;
    }
}

// Chargement lazy
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        const lazyObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    lazyObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => lazyObserver.observe(img));
    } else {
        // Fallback pour les navigateurs sans support
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
        });
    }
}

// Notifications
function showNotification(message, type = 'info', duration = 5000) {
    // Supprimer les notifications existantes
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notif => notif.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-message">${message}</span>
            <button class="notification-close" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Bouton de fermeture
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        hideNotification(notification);
    });
    
    // Fermeture automatique
    if (duration > 0) {
        setTimeout(() => {
            hideNotification(notification);
        }, duration);
    }
    
    return notification;
}

function hideNotification(notification) {
    if (notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }
}

// Gestion de la pagination
function setupPagination(containerId, currentPage, totalPages, onPageChange) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let paginationHTML = '';
    const maxVisiblePages = 5;
    
    // Bouton précédent
    if (currentPage > 1) {
        paginationHTML += `
            <button class="pagination-btn pagination-prev" 
                    onclick="handlePageChange(${currentPage - 1}, ${onPageChange})">
                <i class="fas fa-chevron-left"></i>
                Précédent
            </button>
        `;
    }
    
    // Pages
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        paginationHTML += `
            <button class="pagination-btn" onclick="handlePageChange(1, ${onPageChange})">1</button>
            ${startPage > 2 ? '<span class="pagination-ellipsis">...</span>' : ''}
        `;
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            paginationHTML += `<button class="pagination-btn pagination-active">${i}</button>`;
        } else {
            paginationHTML += `<button class="pagination-btn" onclick="handlePageChange(${i}, ${onPageChange})">${i}</button>`;
        }
    }
    
    if (endPage < totalPages) {
        paginationHTML += `
            ${endPage < totalPages - 1 ? '<span class="pagination-ellipsis">...</span>' : ''}
            <button class="pagination-btn" onclick="handlePageChange(${totalPages}, ${onPageChange})">${totalPages}</button>
        `;
    }
    
    // Bouton suivant
    if (currentPage < totalPages) {
        paginationHTML += `
            <button class="pagination-btn pagination-next" 
                    onclick="handlePageChange(${currentPage + 1}, ${onPageChange})">
                Suivant
                <i class="fas fa-chevron-right"></i>
            </button>
        `;
    }
    
    container.innerHTML = paginationHTML;
}

function handlePageChange(page, callback) {
    if (typeof callback === 'function') {
        callback(page);
    } else {
        // Fallback: redirection avec paramètre URL
        const url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }
}

// Utilitaires
function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.classList.add('loading');
    }
}

function hideLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.classList.remove('loading');
    }
}

function showError(containerId, message) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <p>${message}</p>
                <button class="btn btn-secondary" onclick="location.reload()">Réessayer</button>
            </div>
        `;
    }
}

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
});

// Export des fonctions globales
window.toggleFavorite = toggleFavorite;
window.handlePageChange = handlePageChange;
window.showNotification = showNotification;
window.setupPagination = setupPagination;