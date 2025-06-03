<?php
require_once 'config/database.php';
require_once 'classes/Match.php';
require_once 'config/session.php';

SessionManager::init();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: matches.php');
    exit;
}

$matchObj = new FootballMatch();
$match = $matchObj->getMatchById($_GET['id']);

if (!$match) {
    header('Location: matches.php');
    exit;
}

$categories = $matchObj->getTicketCategories($_GET['id']);

// Debug après avoir récupéré les données
if (isset($_GET['debug'])) {
    echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px; border: 2px solid #0066cc;'>";
    echo "<h3>🔍 DEBUG - Informations du match</h3>";
    echo "<p><strong>ID du match:</strong> " . $_GET['id'] . "</p>";
    echo "<p><strong>Match trouvé:</strong> " . ($match ? 'Oui' : 'Non') . "</p>";
    
    if ($match) {
        echo "<p><strong>Nom du stade:</strong> " . ($match['stadium_name'] ?? 'NULL') . "</p>";
        echo "<p><strong>Ville:</strong> " . ($match['stadium_city'] ?? 'NULL') . "</p>";
        echo "<p><strong>Adresse:</strong> " . ($match['address'] ?? 'NULL') . "</p>";
        echo "<p><strong>Capacité:</strong> " . ($match['capacity'] ?? 'NULL') . "</p>";
    }
    
    echo "<p><strong>Nombre de catégories:</strong> " . count($categories) . "</p>";
    
    if (!empty($categories)) {
        echo "<h4>Détails des catégories:</h4>";
        foreach ($categories as $cat) {
            echo "<p>- " . $cat['name'] . " : " . ($cat['available_capacity'] ?? $cat['remaining_tickets'] ?? 0) . " places à " . $cat['price'] . " MAD</p>";
        }
    }
    echo "</div>";
}

require_once 'includes/header.php';
?>
<style>
    /* Variables CSS pour la cohérence */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-bg: #f8f9fa;
    --white: #ffffff;
    --text-dark: #2c3e50;
    --text-muted: #6c757d;
    --border-color: #dee2e6;
    --shadow: 0 2px 10px rgba(0,0,0,0.1);
    --border-radius: 8px;
}

/* Layout principal */
.main-content {
    background-color: var(--light-bg);
    min-height: 100vh;
    padding: 20px 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding: 15px 20px;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.breadcrumb a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb .separator {
    margin: 0 10px;
    color: var(--text-muted);
}

.breadcrumb .current {
    color: var(--text-dark);
    font-weight: 600;
}

/* En-tête du match */
.match-header-detail {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: var(--white);
    border-radius: var(--border-radius);
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
}

.competition-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
    backdrop-filter: blur(10px);
}

.teams-display {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 40px;
    align-items: center;
    margin: 30px 0;
}

.team-detail {
    text-align: center;
}

.team-detail.away {
    text-align: center;
}

.team-logo-large {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 15px;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.team-name {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.match-center {
    text-align: center;
    border-left: 2px solid rgba(255,255,255,0.3);
    border-right: 2px solid rgba(255,255,255,0.3);
    padding: 0 30px;
}

.match-date-time .date {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
}

.match-date-time .time {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 15px;
}

.vs-text {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: 2px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stadium-info-detail {
    text-align: center;
    font-size: 16px;
    opacity: 0.9;
    margin-top: 20px;
}

.stadium-info-detail i {
    margin-right: 8px;
}

/* Section billets */
.tickets-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 30px;
    text-align: center;
}

.tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.ticket-category-card {
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.ticket-category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--secondary-color);
}

.ticket-category-card.sold-out {
    opacity: 0.6;
    background: #f8f9fa;
}

.ticket-category-card.sold-out:hover {
    transform: none;
    border-color: transparent;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-bg);
}

.category-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0;
}

.category-price {
    font-size: 24px;
    font-weight: 900;
    color: var(--secondary-color);
}

.category-description {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 15px;
    line-height: 1.5;
}

.category-availability {
    margin-bottom: 20px;
}

.available {
    color: var(--success-color);
    font-weight: 600;
    font-size: 14px;
}

.sold-out {
    color: var(--danger-color);
    font-weight: 600;
    font-size: 14px;
}

.quantity-selector {
    margin-bottom: 20px;
}

.quantity-selector label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.quantity-select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 16px;
    background: var(--white);
    transition: border-color 0.3s ease;
}

.quantity-select:focus {
    outline: none;
    border-color: var(--secondary-color);
}

/* Boutons */
.btn {
    display: inline-block;
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    line-height: 1.5;
}

.btn-primary {
    background: var(--secondary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-disabled {
    background: var(--text-muted);
    color: var(--white);
    cursor: not-allowed;
}

.btn-full {
    width: 100%;
}

.btn-outline {
    background: transparent;
    color: var(--secondary-color);
    border: 2px solid var(--secondary-color);
}

.btn-outline:hover {
    background: var(--secondary-color);
    color: var(--white);
}

/* Informations supplémentaires */
.match-details-extra {
    margin-top: 50px;
}

.info-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
}

.info-card {
    background: var(--white);
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.info-card h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--light-bg);
}

.info-card p {
    margin-bottom: 10px;
    line-height: 1.6;
}

.info-card ul {
    list-style: none;
    padding: 0;
}

.info-card li {
    padding: 8px 0;
    border-bottom: 1px solid var(--light-bg);
    position: relative;
    padding-left: 20px;
}

.info-card li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--success-color);
    font-weight: bold;
}

.info-card li:last-child {
    border-bottom: none;
}

/* Message d'absence de billets */
.no-tickets {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.no-tickets p {
    font-size: 18px;
    color: var(--text-muted);
    margin: 0;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--white);
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 2px solid var(--light-bg);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: var(--text-dark);
    font-size: 20px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-muted);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 30px;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 2px solid var(--light-bg);
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .match-header-detail {
        padding: 30px 20px;
    }
    
    .teams-display {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }
    
    .match-center {
        border: none;
        padding: 20px 0;
        border-top: 2px solid rgba(255,255,255,0.3);
        border-bottom: 2px solid rgba(255,255,255,0.3);
    }
    
    .team-name {
        font-size: 20px;
    }
    
    .tickets-grid {
        grid-template-columns: 1fr;
    }
    
    .info-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20px;
    }
    
    .breadcrumb {
        flex-wrap: wrap;
        padding: 15px;
    }
    
    .breadcrumb .separator {
        margin: 0 5px;
    }
}

@media (max-width: 480px) {
    .match-header-detail {
        padding: 20px 15px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .ticket-category-card {
        padding: 20px;
    }
    
    .category-name {
        font-size: 18px;
    }
    
    .category-price {
        font-size: 20px;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.ticket-category-card {
    animation: fadeIn 0.6s ease forwards;
}

.ticket-category-card:nth-child(2) { animation-delay: 0.1s; }
.ticket-category-card:nth-child(3) { animation-delay: 0.2s; }
.ticket-category-card:nth-child(4) { animation-delay: 0.3s; }
</style>
<main class="main-content">
    <div class="container">
        <!-- Navigation Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Accueil</a>
            <span class="separator">></span>
            <a href="matches.php">Matchs</a>
            <span class="separator">></span>
            <span class="current"><?= htmlspecialchars($match['home_team_name'] ?? 'Équipe inconnue') ?> vs <?= htmlspecialchars($match['away_team_name'] ?? 'Équipe inconnue') ?></span>
        </nav>

        <!-- En-tête du match -->
        <div class="match-header-detail">
            <div class="match-info-main">
                <div class="competition-badge">
                    <?= htmlspecialchars($match['competition'] ?? 'Compétition') ?>
                </div>
                
                <div class="teams-display">
                    <div class="team-detail home">
                        <img src="assets/images/teams/<?= $match['home_team_id'] ?>.svg" 
                            alt="<?= htmlspecialchars($match['home_team_name'] ?? 'Équipe domicile') ?>" 
                             class="team-logo-large"
                             onerror="this.src='assets/images/default-team.svg'">
                        <h2 class="team-name"><?= htmlspecialchars($match['home_team_name'] ?? 'Équipe domicile') ?></h2>
                    </div>
                    
                    <div class="match-center">
                        <div class="match-date-time">
                            <div class="date"><?= date('d/m/Y', strtotime($match['match_date'])) ?></div>
                            <div class="time"><?= date('H:i', strtotime($match['match_date'])) ?></div>
                        </div>
                        <div class="vs-text">VS</div>
                    </div>
                    
                    <div class="team-detail away">
                        <img src="assets/images/teams/<?= $match['away_team_id'] ?>.svg" 
                             alt="<?= htmlspecialchars($match['away_team_name'] ?? 'Équipe visiteur') ?>" 
                             class="team-logo-large"
                             onerror="this.src='assets/images/default-team.svg'">
                        <h2 class="team-name"><?= htmlspecialchars($match['away_team_name'] ?? 'Équipe visiteur') ?></h2>
                    </div>
                </div>
                
                <div class="stadium-info-detail">
                    <i class="icon-location"></i>
                    <span><?= htmlspecialchars($match['stadium_name'] ?? 'Stade') ?>, <?= htmlspecialchars($match['stadium_city'] ?? 'Ville inconnue') ?></span>
                </div>
            </div>
        </div>

        <!-- Section Billets -->
        <div class="tickets-section">
            <h3 class="section-title">Choisissez vos billets</h3>
            
            <?php if (empty($categories)): ?>
            <div class="no-tickets">
                <p>Aucun billet disponible pour ce match.</p>
            </div>
            <?php else: ?>
            <div class="tickets-grid">
               <?php foreach ($categories as $category): ?>
<?php 
// LOGIQUE CORRIGÉE pour calculer les places disponibles
$availableTickets = max(0, $category['remaining_tickets'] ?? 0);
$originalCapacity = $category['original_capacity'] ?? $category['available_capacity'] ?? 0;
$isSoldOut = $availableTickets <= 0;
$soldPercentage = $category['sold_percentage'] ?? 0;
?>
<div class="ticket-category-card <?= $isSoldOut ? 'sold-out' : '' ?>">
    <div class="category-header">
        <h4 class="category-name"><?= htmlspecialchars($category['name']) ?></h4>
        <div class="category-price"><?= number_format($category['price'], 2) ?> MAD</div>
    </div>
    
    <?php if (!empty($category['description'])): ?>
    <p class="category-description"><?= htmlspecialchars($category['description']) ?></p>
    <?php endif; ?>
    
    <div class="category-availability">
        <?php if (!$isSoldOut): ?>
        <span class="available">
            <?= $availableTickets ?> places disponibles
            <?php if ($originalCapacity > 0): ?>
                sur <?= $originalCapacity ?> (<?= 100 - $soldPercentage ?>% restant)
            <?php endif; ?>
        </span>
        <?php else: ?>
        <span class="sold-out">Complet (<?= $originalCapacity ?> places)</span>
        <?php endif; ?>
    </div>
    
    <?php if (!$isSoldOut): ?>
    <div class="quantity-selector">
        <label for="qty_<?= $category['id'] ?>">Quantité :</label>
        <select id="qty_<?= $category['id'] ?>" class="quantity-select">
            <?php for ($i = 1; $i <= min(10, $availableTickets); $i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>
    
    <button type="button" 
            class="btn btn-primary btn-full add-to-cart-btn"
            data-category-id="<?= $category['id'] ?>"
            data-category-name="<?= htmlspecialchars($category['name']) ?>"
            data-price="<?= $category['price'] ?>"
            data-match-id="<?= $match['id'] ?>"
            data-available="<?= $availableTickets ?>">
        Ajouter au panier
    </button>
    <?php else: ?>
    <button type="button" class="btn btn-disabled btn-full" disabled>
        Complet
    </button>
    <?php endif; ?>
</div>
<?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Informations supplémentaires -->
        <div class="match-details-extra">
            <div class="info-cards-grid">
                <div class="info-card">
                    <h4>Informations du stade</h4>
                    <p><strong>Stade :</strong> <?= htmlspecialchars($match['stadium_name'] ?? 'Non spécifié') ?></p>
                    <p><strong>Adresse :</strong> <?= htmlspecialchars($match['address'] ?? 'Non spécifiée') ?></p>
                    <p><strong>Capacité :</strong> <?= isset($match['capacity']) ? number_format($match['capacity']) : 'Non spécifiée' ?> places</p>
                </div>
                
                <div class="info-card">
                    <h4>Conditions d'accès</h4>
                    <ul>
                        <li>Présentation obligatoire du billet</li>
                        <li>Arrivée recommandée 1h avant le match</li>
                        <li>Contrôle de sécurité à l'entrée</li>
                        <li>Interdiction d'objets dangereux</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal de confirmation d'ajout au panier -->
<div id="cart-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Ajouté au panier</h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <p id="cart-message"></p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline modal-close">Continuer</button>
            <a href="cart.php" class="btn btn-primary">Voir le panier</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion des boutons "Ajouter au panier"
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const categoryId = this.dataset.categoryId;
            const categoryName = this.dataset.categoryName;
            const price = this.dataset.price;
            const matchId = this.dataset.matchId;
            const quantitySelect = document.getElementById(`qty_${categoryId}`);
            const quantity = quantitySelect ? parseInt(quantitySelect.value) : 1;
            
            // Désactiver le bouton pendant l'ajout
            this.disabled = true;
            this.textContent = 'Ajout en cours...';
            
            // Appel AJAX pour ajouter au panier
            fetch('ajax/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    category_id: categoryId,
                    match_id: matchId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Afficher le modal
                    const modal = document.getElementById('cart-modal');
                    const message = document.getElementById('cart-message');
                    if (modal && message) {
                        const total = (quantity * price).toFixed(2);
                        message.innerHTML = `
                            <strong>${quantity}x ${categoryName}</strong><br>
                            Prix unitaire: ${parseFloat(price).toFixed(2)} MAD<br>
                            Total: ${total} MAD
                        `;
                        modal.style.display = 'block';
                    }
                } else {
                    alert(result.message || 'Erreur lors de l\'ajout au panier');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            })
            .finally(() => {
                // Réactiver le bouton
                this.disabled = false;
                this.textContent = 'Ajouter au panier';
            });
        });
    });
    
    // Fermeture du modal
    document.querySelectorAll('.modal-close').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('cart-modal').style.display = 'none';
        });
    });

    // Fermeture du modal en cliquant en dehors
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('cart-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>