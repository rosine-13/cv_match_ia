import mysql.connector
import PyPDF2
import os

# 1. Connexion à la base de données
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="cvmatch_ia_db"
)
cursor = db.cursor(dictionary=True)

def extract_text_from_pdf(file_path):
    try:
        with open(file_path, 'rb') as pdf_file:
            reader = PyPDF2.PdfReader(pdf_file)
            text = ""
            for page in reader.pages:
                text += page.extract_text()
            return text
    except Exception as e:
        print(f"Erreur lors de la lecture du PDF : {e}")
        return None

# 2. Chercher les CV "En attente"
cursor.execute("SELECT id, cv_file_path FROM cvs WHERE status = 'En attente'")
pending_cvs = cursor.fetchall()

for cv in pending_cvs:
    print(f"Traitement du CV ID: {cv['id']}...")
    
    # Chemin complet vers le fichier
    full_path = os.path.join(os.getcwd(), cv['cv_file_path'])
    
    # Extraction (on gère ici le format PDF)
    if cv['cv_file_path'].lower().endswith('.pdf'):
        extracted_text = extract_text_from_pdf(full_path)
        
        if extracted_text:
            # 3. Mise à jour de la base de données
            sql = "UPDATE cvs SET extracted_text = %s, status = 'Analysé' WHERE id = %s"
            cursor.execute(sql, (extracted_text, cv['id']))
            db.commit()
            print(f"Succès : CV {cv['id']} analysé.")

print("Traitement terminé.")
cursor.close()
db.close()