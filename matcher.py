# ==========================================================
# matcher.py - Calcul du score de matching IA
# Appelé par PHP via shell_exec()
# Usage : python matcher.py "Développeur PHP Abidjan"
# ==========================================================

import mysql.connector
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import sys
import json

def get_matching_scores(job_description):
    try:
        # 1. Connexion BDD
        # ✅ CORRECTION : nom de la base unifié avec PHP
        db = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="cvmatch_ia_db"  # ✅ Corrigé (était cvmatch_ia_db)
        )
        cursor = db.cursor(dictionary=True)

        # 2. Récupérer uniquement les CV qui ont été analysés
        # (ceux qui ont un texte extrait)
        cursor.execute("""
            SELECT id, extracted_text 
            FROM cvs 
            WHERE status = 'Analysé' 
            AND extracted_text IS NOT NULL 
            AND extracted_text != ''
        """)
        cvs = cursor.fetchall()

        cursor.close()
        db.close()

        # Si aucun CV analysé → retourner liste vide
        if not cvs:
            return []

        # 3. Préparer les textes pour le calcul
        # Le premier texte = la requête du recruteur
        # Les suivants = les textes des CV
        texts = [job_description] + [cv['extracted_text'] for cv in cvs]

        # 4. Calcul TF-IDF + Similarité cosinus
        # TF-IDF : mesure l'importance de chaque mot
        # Cosine Similarity : mesure la ressemblance entre 2 textes
        vectorizer = TfidfVectorizer(
            lowercase=True,      # Ignore la casse (PHP = php)
            sublinear_tf=True,   # Booste les mots trouvés
            use_idf=True         # Pondère par fréquence inverse
        )
        tfidf_matrix = vectorizer.fit_transform(texts)

        # Compare la requête (ligne 0) avec chaque CV (lignes 1 à N)
        scores = cosine_similarity(tfidf_matrix[0:1], tfidf_matrix[1:])

        # 5. Construire les résultats
        results = []
        for i, cv in enumerate(cvs):
            score = round(float(scores[0][i]) * 100, 2)
            # On n'inclut que les CV avec un score > 0
            if score > 0:
                results.append({
                    "id": cv['id'],
                    "score": score
                })

        # 6. Trier du meilleur score au moins bon
        results = sorted(results, key=lambda x: x['score'], reverse=True)

        return results

    except Exception as e:
        # En cas d'erreur, on la retourne en JSON pour debug
        return [{"error": str(e)}]


if __name__ == "__main__":
    # PHP appelle ce script avec la requête en argument
    # Ex: python matcher.py "Développeur PHP avec 2 ans expérience"
    if len(sys.argv) > 1:
        query = sys.argv[1]
        results = get_matching_scores(query)
        # On imprime en JSON pour que PHP puisse le lire
        print(json.dumps(results))
    else:
        print(json.dumps({"error": "Aucune requête fournie"}))