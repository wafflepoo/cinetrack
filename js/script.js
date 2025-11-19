// Scroll animations
const fadeElements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .stagger-animation');

const appearOnScroll = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('appear');
        }
    });
}, { threshold: 0.1, rootMargin: "0px 0px -100px 0px" });

fadeElements.forEach(element => {
    appearOnScroll.observe(element);
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav');
    if (window.scrollY > 100) {
        nav.style.backgroundColor = 'rgba(17, 24, 39, 0.95)';
        nav.style.paddingTop = '0.5rem';
        nav.style.paddingBottom = '0.5rem';
    } else {
        nav.style.backgroundColor = 'rgba(17, 24, 39, 0.9)';
        nav.style.paddingTop = '0';
        nav.style.paddingBottom = '0';
    }
});

// Dark mode toggle
const darkModeToggle = document.querySelector('.dark-mode-toggle');
darkModeToggle.addEventListener('click', () => {
    darkModeToggle.classList.toggle('active');
    document.body.classList.toggle('light-mode');
    
    if (document.body.classList.contains('light-mode')) {
        document.body.style.background = 'linear-gradient(135deg, #f0f2f5 0%, #e1e5ea 100%)';
        document.body.style.color = '#1a1f35';
    } else {
        document.body.style.background = 'linear-gradient(135deg, #0a0e14 0%, #05080d 100%)';
        document.body.style.color = 'white';
    }
});

// Search functionality
const searchInput = document.querySelector('input[type="text"]');
const searchButton = document.querySelector('.search-button');

searchButton.addEventListener('click', () => {
    performSearch();
});

searchInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        performSearch();
    }
});

function performSearch() {
    const query = searchInput.value.trim();
    if (query) {
        // Add your search logic here
        console.log('Searching for:', query);
        // You can redirect to search results page or show results dynamically
    }
}

// Movie card interactions
document.querySelectorAll('.movie-card').forEach(card => {
    card.addEventListener('mouseenter', () => {
        card.querySelector('img').style.transform = 'scale(1.1)';
    });
    
    card.addEventListener('mouseleave', () => {
        card.querySelector('img').style.transform = 'scale(1)';
    });
});

// Initialize page
window.addEventListener('load', () => {
    setTimeout(() => {
        document.body.classList.add('loaded');
    }, 500);
});

// Genre filter functionality (if needed)
document.querySelectorAll('.genre-card').forEach(genre => {
    genre.addEventListener('click', () => {
        const genreName = genre.querySelector('h3').textContent;
        console.log('Filtering by genre:', genreName);
        // Add genre filtering logic here
    });
});
