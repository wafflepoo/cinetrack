<!-- Trending Section -->
<section class="py-20 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="flex items-center justify-between mb-8 slide-in-left">
            <h2 class="text-4xl font-bold">
                <span class="text-orange-500">Tendances</span> du Moment
            </h2>
            <a href="#" class="text-orange-500 hover:text-orange-400 transition-all duration-300 font-semibold group">
                Voir tout <i class="fas fa-arrow-right ml-1 transform group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
        <p class="text-gray-400 mb-8 slide-in-left" style="transition-delay: 0.1s;">Les films et séries les plus populaires cette semaine</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 stagger-animation">
            <?php foreach ($trending_movies as $movie): ?>
            <div class="card-hover glass rounded-2xl overflow-hidden cursor-pointer movie-card">
                <div class="relative overflow-hidden">
                    <img src="<?= $movie['image'] ?>" alt="<?= $movie['title'] ?>" class="w-full h-80 object-cover transition-transform duration-500">
                    <div class="absolute top-4 right-4 bg-orange-500 px-3 py-1 rounded-full flex items-center space-x-1 shadow-lg">
                        <i class="fas fa-star text-white"></i>
                        <span class="font-bold text-white"><?= $movie['rating'] ?></span>
                    </div>
                    <div class="absolute inset-0 hero-gradient opacity-0 hover:opacity-100 transition-opacity duration-300 flex items-end p-6">
                        <button class="w-full py-2 bg-orange-500 hover:bg-orange-600 rounded-lg font-semibold transition-all duration-300 transform hover:scale-105">
                            Voir détails
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-bold mb-2"><?= $movie['title'] ?></h3>
                    <div class="flex items-center space-x-4 text-sm text-gray-400">
                        <span class="flex items-center space-x-1">
                            <i class="fas fa-star text-yellow-500"></i>
                            <span><?= $movie['rating'] ?></span>
                        </span>
                        <span><?= $movie['year'] ?></span>
                        <span class="px-3 py-1 bg-orange-500/20 text-orange-500 rounded-full text-xs">
                            <?= $movie['genre'] ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>