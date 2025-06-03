<?php
require_once 'config/database.php';
require_once 'classes/Match.php';
require_once 'includes/header.php';

$matchObj = new FootballMatch();

// Préparation des filtres
$filters = [];
$competition = $_GET['competition'] ?? '';

if ($competition) {
    $filters['competition'] = $competition;
}

// Récupération des matchs avec filtres
$matches = $matchObj->getAllMatches(1, 50, $filters);
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Tous les matchs</h1>
            <p>Découvrez tous les matchs disponibles à la réservation</p>
        </div>

        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="competition">Compétition :</label>
                    <select name="competition" id="competition" class="filter-select">
                        <option value="">Toutes les compétitions</option>
                        <option value="Botola Pro" <?= $competition === 'Botola Pro' ? 'selected' : '' ?>>Botola Pro</option>
                        <option value="Coupe du Trône" <?= $competition === 'Coupe du Trône' ? 'selected' : '' ?>>Coupe du Trône</option>
                        <option value="Champions League" <?= $competition === 'Champions League' ? 'selected' : '' ?>>Champions League</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrer</button>
                
                <?php if ($competition): ?>
                <a href="matches.php" class="btn btn-outline">Réinitialiser</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Liste des matchs -->
        <div class="matches-list">
            <?php if (empty($matches)): ?>
            <div class="no-matches">
                <p>Aucun match trouvé pour cette compétition.</p>
                <a href="matches.php" class="btn btn-primary">Voir tous les matchs</a>
            </div>
            <?php else: ?>
            <?php foreach ($matches as $match): ?>
            <div class="match-card-horizontal">
                <div class="match-date-badge">
                    <div class="day"><?= date('d', strtotime($match['match_date'])) ?></div>
                    <div class="month"><?= date('M', strtotime($match['match_date'])) ?></div>
                </div>
                
                <div class="match-content">
                    <div class="match-header">
                        <span class="competition-tag"><?= htmlspecialchars($match['competition'] ?? 'N/A') ?></span>
                        <span class="match-time"><?= date('H:i', strtotime($match['match_date'])) ?></span>
                    </div>
                    
                    <div class="teams-row">
                        <div class="team">
                            <img src="assets/images/teams/<?= $match['home_team_id'] ?>.svg" 
                                 alt="<?= htmlspecialchars($match['home_team_name'] ?? 'N/A') ?>" 
                                 class="team-logo-small"
                                 onerror="this.src='assets/images/default-team.svg'">
                            <span class="team-name"><?= htmlspecialchars($match['home_team_name'] ?? 'N/A') ?></span>
                        </div>
                        
                        <span class="vs">vs</span>
                        
                        <div class="team">
                            <img src="assets/images/teams/<?= $match['away_team_id'] ?>.svg" 
                                 alt="<?= htmlspecialchars($match['away_team_name'] ?? 'N/A') ?>" 
                                 class="team-logo-small"
                                 onerror="this.src='assets/images/default-team.svg'">
                            <span class="team-name"><?= htmlspecialchars($match['away_team_name'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    
                    <div class="match-venue">
                        <i class="icon-location"></i>
                        <?= htmlspecialchars($match['stadium_name'] ?? 'N/A') ?>, <?= htmlspecialchars($match['stadium_city'] ?? 'N/A') ?>
                    </div>
                </div>
                
                <div class="match-actions">
                    <div class="price-from">
                        À partir de<br>
                        <strong><?= isset($match['min_price']) ? number_format($match['min_price'], 2) . ' MAD' : 'Prix à définir' ?></strong>
                    </div>
                    <a href="match-details.php?id=<?= $match['id'] ?>" class="btn btn-primary">
                        Réserver
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>