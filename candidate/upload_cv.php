<?php
require_once '../config.php';

// 1. SÉCURITÉ : Vérification de l'accès
if (!is_logged_in() || !is_candidate()) {
    header("Location: login.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['cv_file'])) {
    $file = $_FILES['cv_file'];
    $validation = validate_uploaded_file($file);

    if ($validation['success']) {

        // 2. Créer le dossier uploads s'il n'existe pas
        if (!is_dir('../uploads')) {
            mkdir('../uploads', 0777, true);
        }

        // 3. Nom unique pour éviter les doublons
        $filename = time() . '_' . basename($file['name']);
        $target_path = '../uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {

            // 4. Chemin relatif pour le navigateur
            $db_path = 'uploads/' . $filename;

            // 5. ✅ CORRECTION : on récupère l'id du profil candidat
            //    lié à cet utilisateur connecté
            //    car candidate_profiles.id ≠ users.id
            $stmt_profile = $pdo->prepare("
                SELECT id 
                FROM candidate_profiles 
                WHERE user_id = ?
            ");
            $stmt_profile->execute([$_SESSION['user_id']]);
            $profile = $stmt_profile->fetch();

            if ($profile) {
                // 6. On insère le CV avec le bon candidate_id
                $stmt = $pdo->prepare("
                    INSERT INTO cvs (candidate_id, cv_file_path, status) 
                    VALUES (?, ?, 'En attente')
                ");
                $stmt->execute([$profile['id'], $db_path]);
                $message = "<div class='alert alert-success'>
                                <strong>✅ Succès !</strong> Votre CV a été envoyé avec succès.
                            </div>";
            } else {
                // Le profil candidat n'existe pas encore
                $message = "<div class='alert alert-danger'>
                                <strong>❌ Erreur :</strong> Profil candidat introuvable. 
                                Veuillez d'abord compléter votre profil.
                            </div>";
            }

        } else {
            $message = "<div class='alert alert-danger'>
                            <strong>❌ Erreur :</strong> Impossible de déplacer le fichier. 
                            Vérifiez les permissions du dossier uploads.
                        </div>";
        }

    } else {
        $message = "<div class='alert alert-danger'>" . $validation['error'] . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déposer mon CV - CVMatch IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand">
            <i class="fas fa-file-upload"></i> CVMatch IA | Dépôt de CV
        </span>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</nav>

<div class="container py-3">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm p-4">

                <h4 class="mb-1">
                    <i class="fas fa-cloud-upload-alt text-primary"></i> Déposer mon CV
                </h4>
                <p class="text-muted mb-4">Formats acceptés : PDF, DOCX, JPG, PNG</p>

                <?= $message ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Choisir un fichier</label>
                        <input type="file" 
                               name="cv_file" 
                               class="form-control" 
                               accept=".pdf,.docx,.jpg,.jpeg,.png"
                               required>
                        <div class="form-text">Taille maximale : 5 Mo</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer mon CV
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>