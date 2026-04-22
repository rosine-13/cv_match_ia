import os
import mysql.connector

# Connexion à ta base de données
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="cvmatch_ia_db"
)
cursor = db.cursor()

# Chemin vers ton dossier de CV
cv_folder = "storage/cv/"

# 1. Lister tous les fichiers PDF du dossier
files = [f for f in os.listdir(cv_folder) if f.endswith('.pdf')]

for filename in files:
    file_path = cv_folder + filename
    
    # Vérifier si ce CV est déjà dans la base de données
    cursor.execute("SELECT id FROM cvs WHERE cv_file_path = %s", (file_path,))
    result = cursor.fetchone()
    
    if not result:
        # Si le CV n'existe pas, on l'ajoute avec un ID candidat par défaut (ex: 1)
        # Note : Dans un vrai projet, on lierait cela à un compte utilisateur
        sql = "INSERT INTO cvs (candidate_id, cv_file_path, status) VALUES (%s, %s, %s)"
        val = (1, file_path, "En attente")
        cursor.execute(sql, val)
        print(f"✅ Ajouté à la base de données : {filename}")

db.commit()
print("Terminé ! Tous les nouveaux fichiers sont enregistrés.")