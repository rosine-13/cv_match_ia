import sys
import requests
import json

# Colle ta clé Groq ici
API_KEY = "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"
URL = "https://api.groq.com/openai/v1/chat/completions"

def handle_chat(user_message):
    headers = {
        "Authorization": f"Bearer {API_KEY}",
        "Content-Type": "application/json"
    }
    
    data = {
        "model": "llama-3.3-70b-versatile", # Le modèle le plus équilibré et gratuit
        "messages": [
            {
                "role": "system", 
                "content": "Tu es l'assistant de CVMatch IA. Aide le recruteur à filtrer les CV par ville, score ou genre."
            },
            {"role": "user", "content": user_message}
        ]
    }

    try:
        response = requests.post(URL, headers=headers, data=json.dumps(data))
        
        if response.status_code == 200:
            result = response.json()
            return result['choices'][0]['message']['content']
        else:
            return f"Erreur Groq ({response.status_code}): {response.text}"
            
    except Exception as e:
        return f"Erreur de connexion : {str(e)}"

if __name__ == "__main__":
    if len(sys.argv) > 1:
        # On force l'encodage de la sortie pour éviter les caractères bizarres
        sys.stdout.reconfigure(encoding='utf-8') 
        print(handle_chat(sys.argv[1]))
       