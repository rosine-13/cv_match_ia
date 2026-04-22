<?php
require_once '../config.php';

// 1. SÉCURITÉ : Vérification de l'accès
if (!is_logged_in() || !is_recruiter()) {
    header("Location: login.php");
    exit;
}

$results = [];
$query = isset($_GET['query']) ? clean_input($_GET['query']) : '';

// 2. LOGIQUE : Appel du script Python matcher.py
if (!empty($query)) {

    // On construit la commande shell pour appeler Python
    // realpath() donne le chemin absolu du fichier matcher.py
    // escapeshellarg() sécurise la requête pour éviter les injections
    $matcher_path = realpath("../matcher.py");
    $command = "python \"$matcher_path\" " . escapeshellarg($query) . " 2>&1";
    $output = shell_exec($command);

    // On décode le JSON retourné par Python
    $scores = json_decode($output, true);

    if ($scores && is_array($scores)) {
        foreach ($scores as $item) {

            // Vérification : s'assurer que c'est un vrai résultat (pas une erreur)
            if (!isset($item['id'])) continue;

            // Pour chaque CV scoré, on récupère les infos complètes depuis la BDD
            $stmt = $pdo->prepare("
                SELECT 
                    cvs.id, 
                    cvs.cv_file_path, 
                    cp.full_name, 
                    cp.city, 
                    u.email 
                FROM cvs 
                LEFT JOIN candidate_profiles cp ON cvs.candidate_id = cp.id
                LEFT JOIN users u ON cp.user_id = u.id 
                WHERE cvs.id = ?
            ");
            // ✅ CORRECTION : cp.id (et non cp.user_id)
            // car cvs.candidate_id pointe vers candidate_profiles.id

            $stmt->execute([$item['id']]);
            $cv_data = $stmt->fetch();

            if ($cv_data) {
                // On ajoute le score de matching aux données du CV
                $cv_data['match_score'] = $item['score'];
                $results[] = $cv_data;
            }
        }
    }
}

// 3. TRI FINAL : Du meilleur score au moins bon
if (!empty($results)) {
    usort($results, function($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats du Matching IA - CVMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .card-top-candidate { border: 2px solid #0d6efd !important; }
        .score-badge { font-size: 1.2rem; font-weight: bold; }
        .progress { background-color: #e9ecef; }
    </style>
</head>
<body class="bg-light">
<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">Analyse comparative IA</h2>
            <p class="text-muted">
                Mots-clés recherchés : 
                <span class="badge bg-info text-dark"><?= htmlspecialchars($query) ?></span>
            </p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Retour Dashboard
        </a>
    </div>

    <?php if (empty($results)): ?>
        <div class="alert alert-warning shadow-sm">
            <i class="fas fa-info-circle"></i> Aucun candidat ne correspond à cette recherche.
            <br><small class="text-muted">Vérifiez que les CV ont bien été analysés (statut = "Analysé")</small>
        </div>
    <?php else: ?>
        <div class="row">
            <?php 
            $isFirst = true; 
            foreach ($results as $cv): 
                $specialClass = ($isFirst && $cv['match_score'] > 0) 
                    ? 'card-top-candidate shadow-lg' 
                    : 'shadow-sm';

                // Couleur de la barre selon le score
                $barColor = 'bg-danger';
                if ($cv['match_score'] >= 10 && $cv['match_score'] < 25) {
                    $barColor = 'bg-warning text-dark';
                } elseif ($cv['match_score'] >= 25) {
                    $barColor = 'bg-success';
                }
            ?>
            <div class="col-12 mb-4">
                <div class="card h-100 <?= $specialClass ?>">

                    <?php if ($isFirst && $cv['match_score'] > 0): ?>
                        <div class="card-header bg-primary text-white py-1">
                            <i class="fas fa-crown"></i> Meilleur profil trouvé
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="row align-items-center">

                            <!-- Nom et ville -->
                            <div class="col-md-4">
                                <h4 class="mb-1 text-primary">
                                    <?= !empty($cv['full_name']) 
                                        ? htmlspecialchars($cv['full_name']) 
                                        : "Candidat (" . basename($cv['cv_file_path']) . ")" ?>
                                </h4>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <?= !empty($cv['city']) 
                                        ? htmlspecialchars($cv['city']) 
                                        : 'Ville non précisée' ?>
                                </p>
                            </div>

                            <!-- Barre de score -->
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-bold text-uppercase text-muted">
                                        Indice de matching
                                    </span>
                                    <span class="score-badge"><?= $cv['match_score'] ?>%</span>
                                </div>
                                <div class="progress" style="height: 12px; border-radius: 10px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated <?= $barColor ?>" 
                                         role="progressbar" 
                                         style="width: <?= max($cv['match_score'], 5) ?>%">
                                    </div>
                                </div>
                            </div>

                            <!-- Boutons actions -->
                            <div class="col-md-3 text-end">
                                <div class="d-grid gap-2">
                                    <a href="../<?= htmlspecialchars($cv['cv_file_path']) ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-file-pdf"></i> Voir le CV
                                    </a>
                                    <?php if (!empty($cv['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($cv['email']) ?>?subject=Votre candidature sur CVMatch" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-envelope"></i> Contacter
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $isFirst = false; 
            endforeach; 
            ?>
        </div>
    <?php endif; ?>

</div>

<!-- Chat IA flottant -->
<div id="chat-icon" onclick="toggleChat()" style="position:fixed;bottom:20px;right:20px;background:#0d6efd;color:white;width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);z-index:1000;">
    <i class="fas fa-robot fa-2x"></i>
</div>

<div id="chat-window" style="display:none;position:fixed;bottom:90px;right:20px;width:350px;height:450px;background:white;border-radius:15px;box-shadow:0 5px 25px rgba(0,0,0,0.2);z-index:1000;flex-direction:column;overflow:hidden;border:1px solid #ddd;">
    <div style="background:#0d6efd;color:white;padding:15px;font-weight:bold;display:flex;justify-content:space-between;align-items:center;">
        <span><i class="fas fa-magic"></i> Assistant CVMatch IA</span>
        <button onclick="toggleChat()" style="background:none;border:none;color:white;cursor:pointer;">&times;</button>
    </div>
    <div id="chat-content" style="flex:1;padding:15px;overflow-y:auto;font-size:0.9rem;background:#f8f9fa;">
        <div class="mb-2">
            <span style="background:#e9ecef;padding:8px 12px;border-radius:15px;display:inline-block;">
                Bonjour ! Essayez : "Montre-moi les candidats de Bouaké" ou "Scores > 15%"
            </span>
        </div>
    </div>
    <div style="padding:10px;border-top:1px solid #eee;">
        <div class="input-group">
            <input type="text" id="chat-input" class="form-control" placeholder="Affiner la recherche...">
            <button class="btn btn-primary" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
function toggleChat() {
    const chat = document.getElementById('chat-window');
    chat.style.display = chat.style.display === 'none' ? 'flex' : 'none';
}

function sendMessage() {
    const input = document.getElementById('chat-input');
    const content = document.getElementById('chat-content');
    const userMsg = input.value.trim();
    if (userMsg !== "") {
        content.innerHTML += `<div class="text-end mb-2"><span class="bg-primary text-white p-2 rounded d-inline-block">${userMsg}</span></div>`;
        input.value = "";
        fetch('ajax_chat.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'message=' + encodeURIComponent(userMsg)
        })
        .then(response => response.text())
        .then(data => {
            content.innerHTML += `<div class="mb-2"><span class="bg-light p-2 rounded d-inline-block border">🤖 ${data}</span></div>`;
            content.scrollTop = content.scrollHeight;
        });
    }
}
</script>
</body>
</html>