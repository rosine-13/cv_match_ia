# ==========================================================
# generate_pdfs.py
# Génère de vrais fichiers PDF pour les candidats fictifs
# Usage : python generate_pdfs.py
# Installer d'abord : pip install reportlab mysql-connector-python
# ==========================================================

import mysql.connector
import os
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, HRFlowable
from reportlab.lib.units import cm

# ✅ Mettez ici le vrai nom de votre base de données
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'cvmatch_ia_db'
}

# Chemin vers le dossier uploads (relatif à ce script)
UPLOADS_DIR = os.path.join(os.path.dirname(__file__), 'uploads')

def create_cv_pdf(filepath, full_name, ville, titre, competences, texte_cv, experience):
    """Génère un vrai fichier PDF de CV professionnel"""

    doc = SimpleDocTemplate(
        filepath,
        pagesize=A4,
        rightMargin=2*cm,
        leftMargin=2*cm,
        topMargin=2*cm,
        bottomMargin=2*cm
    )

    styles = getSampleStyleSheet()

    # Styles personnalisés
    style_nom = ParagraphStyle(
        'Nom',
        parent=styles['Title'],
        fontSize=22,
        textColor=colors.HexColor('#1a237e'),
        spaceAfter=4
    )
    style_titre = ParagraphStyle(
        'Titre',
        parent=styles['Normal'],
        fontSize=13,
        textColor=colors.HexColor('#1565c0'),
        spaceAfter=2
    )
    style_section = ParagraphStyle(
        'Section',
        parent=styles['Heading2'],
        fontSize=12,
        textColor=colors.HexColor('#1a237e'),
        spaceBefore=12,
        spaceAfter=4,
        borderPad=2
    )
    style_normal = ParagraphStyle(
        'Normal2',
        parent=styles['Normal'],
        fontSize=10,
        leading=16,
        spaceAfter=4
    )
    style_competence = ParagraphStyle(
        'Competence',
        parent=styles['Normal'],
        fontSize=10,
        textColor=colors.HexColor('#333333'),
        leading=16
    )

    story = []

    # ---- EN-TETE ----
    story.append(Paragraph(full_name, style_nom))
    story.append(Paragraph(titre, style_titre))
    story.append(Paragraph(
        f"Ville : {ville}  |  Disponibilite : Immediate  |  Experience : {experience} ans",
        style_normal
    ))
    story.append(HRFlowable(width="100%", thickness=2, color=colors.HexColor('#1a237e')))
    story.append(Spacer(1, 0.3*cm))

    # ---- PROFIL ----
    story.append(Paragraph("PROFIL PROFESSIONNEL", style_section))
    story.append(HRFlowable(width="100%", thickness=0.5, color=colors.HexColor('#bbdefb')))
    story.append(Spacer(1, 0.2*cm))
    story.append(Paragraph(texte_cv, style_normal))

    # ---- COMPETENCES ----
    story.append(Paragraph("COMPETENCES TECHNIQUES", style_section))
    story.append(HRFlowable(width="100%", thickness=0.5, color=colors.HexColor('#bbdefb')))
    story.append(Spacer(1, 0.2*cm))

    # On affiche chaque compétence sur une ligne
    for comp in competences.split(','):
        comp = comp.strip()
        if comp:
            story.append(Paragraph(f"  - {comp}", style_competence))

    # ---- EXPERIENCE ----
    story.append(Paragraph("EXPERIENCE PROFESSIONNELLE", style_section))
    story.append(HRFlowable(width="100%", thickness=0.5, color=colors.HexColor('#bbdefb')))
    story.append(Spacer(1, 0.2*cm))

    annee_fin = 2024
    annee_debut = annee_fin - experience

    story.append(Paragraph(
        f"<b>{titre}</b> — Entreprise Tech Abidjan",
        style_normal
    ))
    story.append(Paragraph(
        f"{annee_debut} - {annee_fin} | {ville}, Cote d'Ivoire",
        style_competence
    ))
    story.append(Paragraph(
        "Conception et developpement de solutions informatiques. "
        "Collaboration avec les equipes techniques et fonctionnelles. "
        "Participation aux reunions de projet et aux revues de code.",
        style_normal
    ))

    # ---- FORMATION ----
    story.append(Paragraph("FORMATION", style_section))
    story.append(HRFlowable(width="100%", thickness=0.5, color=colors.HexColor('#bbdefb')))
    story.append(Spacer(1, 0.2*cm))
    story.append(Paragraph(
        "<b>Licence en Informatique</b> — Institut National Polytechnique",
        style_normal
    ))
    story.append(Paragraph("2015 - 2018 | Yamoussoukro, Cote d'Ivoire", style_competence))

    # ---- LANGUES ----
    story.append(Paragraph("LANGUES", style_section))
    story.append(HRFlowable(width="100%", thickness=0.5, color=colors.HexColor('#bbdefb')))
    story.append(Spacer(1, 0.2*cm))
    story.append(Paragraph("Francais : Courant  |  Anglais : Intermediaire", style_normal))

    doc.build(story)


def generate_all_pdfs():
    """Récupère les CV fictifs en BDD et génère les PDF manquants"""

    # Créer le dossier uploads s'il n'existe pas
    if not os.path.exists(UPLOADS_DIR):
        os.makedirs(UPLOADS_DIR)
        print(f"Dossier cree : {UPLOADS_DIR}")

    # Connexion BDD
    db = mysql.connector.connect(**db_config)
    cursor = db.cursor(dictionary=True)

    # Récupérer tous les CV fictifs avec les infos du candidat
    cursor.execute("""
        SELECT 
            cvs.id,
            cvs.cv_file_path,
            cvs.extracted_text,
            cp.full_name,
            cp.city
        FROM cvs
        JOIN candidate_profiles cp ON cvs.candidate_id = cp.id
        WHERE cvs.cv_file_path LIKE 'uploads/cv_fictif_%'
    """)
    cvs = cursor.fetchall()

    print(f"\n{len(cvs)} CV fictifs trouves en base de donnees\n")

    success = 0
    errors = 0

    for cv in cvs:
        filename = os.path.basename(cv['cv_file_path'])
        filepath = os.path.join(UPLOADS_DIR, filename)

        # Ne pas regénérer si le fichier existe déjà
        if os.path.exists(filepath):
            print(f"  Existe deja : {filename}")
            success += 1
            continue

        try:
            # Extraire les infos du texte du CV
            texte = cv['extracted_text'] or "Professionnel IT experimente."

            # Déterminer le titre selon le contenu du texte
            titre = "Professionnel IT"
            if 'PHP' in texte:
                titre = "Developpeur Web PHP"
            elif 'React' in texte or 'Node' in texte:
                titre = "Developpeur Full Stack"
            elif 'Android' in texte:
                titre = "Developpeur Mobile Android"
            elif 'Cisco' in texte or 'Reseau' in texte or 'réseau' in texte:
                titre = "Ingenieur Reseau et Systemes"
            elif 'Power BI' in texte or 'Pandas' in texte:
                titre = "Data Analyst"
            elif 'Django' in texte or 'Flask' in texte:
                titre = "Developpeur Python"
            elif 'Linux' in texte and 'Docker' in texte:
                titre = "Administrateur Systemes Linux"
            elif 'Java' in texte and 'Spring' in texte:
                titre = "Developpeur Java"
            elif 'Support' in texte or 'ITIL' in texte:
                titre = "Technicien Support Informatique"
            elif 'Cybersecurite' in texte or 'pentest' in texte.lower():
                titre = "Ingenieur Cybersecurite"

            # Extraire les compétences du texte
            competences = "Informatique, Developpement, Reseau"
            if 'PHP' in texte:
                competences = "PHP, MySQL, HTML, CSS, JavaScript, Laravel, Git"
            elif 'React' in texte:
                competences = "React, Node.js, JavaScript, MongoDB, Docker"
            elif 'Android' in texte:
                competences = "Java, Kotlin, Android Studio, Firebase"
            elif 'Cisco' in texte:
                competences = "Cisco, Linux, Windows Server, TCP/IP, VPN"
            elif 'Power BI' in texte:
                competences = "Python, SQL, Power BI, Pandas, Tableau"
            elif 'Django' in texte:
                competences = "Python, Django, Flask, PostgreSQL, REST API"
            elif 'Bash' in texte:
                competences = "Linux, Bash, Docker, Kubernetes, Ansible"
            elif 'Spring' in texte:
                competences = "Java, Spring Boot, Hibernate, Maven"
            elif 'Office 365' in texte:
                competences = "Windows, Office 365, Active Directory, ITIL"
            elif 'pentest' in texte.lower():
                competences = "Cybersecurite, Kali Linux, Pentest, Firewall"

            # Extraire l'expérience (cherche un nombre dans le texte)
            import re
            match = re.search(r'(\d+) ans', texte)
            experience = int(match.group(1)) if match else 3

            # Générer le PDF
            create_cv_pdf(
                filepath=filepath,
                full_name=cv['full_name'],
                ville=cv['city'] or 'Abidjan',
                titre=titre,
                competences=competences,
                texte_cv=texte[:500],  # On prend les 500 premiers caractères
                experience=experience
            )

            print(f"  PDF cree : {filename}")
            success += 1

        except Exception as e:
            print(f"  ERREUR pour {filename} : {e}")
            errors += 1

    cursor.close()
    db.close()

    print(f"\n Termine ! {success} PDF generes, {errors} erreurs.")
    print(f"Les fichiers sont dans : {UPLOADS_DIR}")


if __name__ == "__main__":
    print("Generation des PDF pour les candidats fictifs...")
    generate_all_pdfs()