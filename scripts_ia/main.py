# main.py - Le microservice IA
from flask import Flask, request, jsonify
import mysql.connector
import pdfplumber
import spacy

app = Flask(__name__)

# Chargement du modèle de langue française (IA)
nlp = spacy.load("fr_core_news_sm")

# Configuration de la connexion à ta nouvelle base de données
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'cvmatch_ia_db' # Le nouveau nom de ta BDD
}

def extract_text_from_pdf(file_path):
    """Fonction pour extraire le texte d'un PDF"""
    text = ""
    with pdfplumber.open(file_path) as pdf:
        for page in pdf.pages:
            text += page.extract_text()
    return text

@app.route('/process_cv', methods=['POST'])
def process_cv():
    """Route appelée par PHP quand un nouveau CV est uploadé"""
    data = request.json
    cv_id = data.get('cv_id')
    file_path = data.get('file_path') # Chemin envoyé par PHP

    try:
        # 1. Extraire le texte du fichier
        # Note : file_path doit être un chemin absolu ou correct par rapport à ce script
        extracted_text = extract_text_from_pdf("../" + file_path)

        # 2. Sauvegarder dans la base de données
        conn = mysql.connector.connect(**db_config)
        cursor = conn.cursor()
        
        query = "UPDATE cvs SET extracted_text = %s, status = 'Analysé' WHERE id = %s"
        cursor.execute(query, (extracted_text, cv_id))
        
        conn.commit()
        cursor.close()
        conn.close()

        return jsonify({"status": "success", "message": "CV analysé avec succès"})
    except Exception as e:
        return jsonify({"status": "error", "message": str(e)})

if __name__ == '__main__':
    # Le serveur tourne sur le port 5000
    app.run(port=5000, debug=True)