<!-- CTA Section -->
<section class="py-20 px-4">
    <div class="max-w-4xl mx-auto text-center fade-in">
        <div class="bg-gradient-to-br from-orange-500/20 to-purple-500/20 backdrop-blur-lg p-12 rounded-3xl border border-orange-500/30 relative overflow-hidden">
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-orange-500 rounded-full filter blur-3xl opacity-30"></div>
            <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-purple-500 rounded-full filter blur-3xl opacity-30"></div>
            
            <div class="inline-block px-4 py-2 bg-orange-500/20 rounded-full text-orange-500 text-sm font-semibold mb-6 glass">
                <i class="fas fa-star mr-2"></i>Rejoignez 25,000+ cinéphiles
            </div>
            <h2 class="text-5xl font-black mb-6 relative z-10">
                Prêt à découvrir votre<br/>
                <span class="text-orange-500">prochaine obsession ?</span>
            </h2>
            <p class="text-xl text-gray-300 mb-8 relative z-10">
                Créez votre compte gratuitement et accédez à des milliers de<br/>
                films, séries et recommandations personnalisées.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 relative z-10">
                <a href="../pages/inscription.php" 
                   class="px-8 py-4 btn-primary rounded-xl font-bold text-lg flex items-center space-x-2 shadow-lg transform hover:-translate-y-1 transition-all duration-300 pulse-animation inline-block text-center">
                    <span>Commencer Gratuitement</span>
                    <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition-transform"></i>
                </a>
                <button onclick="scrollToTop()" 
                        class="px-8 py-4 glass hover:bg-gray-700/30 rounded-xl font-bold text-lg transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                    Explorer sans compte
                </button>
            </div>
            <div class="flex items-center justify-center gap-8 mt-8 text-sm text-gray-400 relative z-10">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-bolt text-orange-500"></i>
                    <span>Inscription rapide</span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-check-circle text-orange-500"></i>
                    <span>100% gratuit</span>
                </div>
                <div class="flex items-center space-x-2">
                    <i class="fas fa-ban text-orange-500"></i>
                    <span>Sans publicité</span>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
</script>