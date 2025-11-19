<!-- Features Section -->
<section class="py-20 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="text-center mb-12 fade-in">
            <h2 class="text-4xl font-bold mb-4">
                Fonctionnalités <span class="text-orange-500">Puissantes</span>
            </h2>
            <p class="text-gray-400">Tout ce dont vous avez besoin pour gérer et découvrir vos films et séries préférés</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 stagger-animation">
            <?php foreach ($features as $feature): ?>
            <div class="glass p-8 rounded-2xl border border-gray-700 hover:border-orange-500/50 transition-all duration-500 transform hover:-translate-y-2">
                <div class="text-4xl mb-4 transform transition-transform duration-300 hover:scale-110 text-orange-500">
                    <i class="fas <?= $feature['icon'] ?>"></i>
                </div>
                <h3 class="text-2xl font-bold mb-3"><?= $feature['title'] ?></h3>
                <p class="text-gray-400 leading-relaxed"><?= $feature['description'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>