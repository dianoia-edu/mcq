#!/usr/bin/env python3
import sys
import json
import argparse
import urllib.request
import ssl
from youtube_transcript_api import YouTubeTranscriptApi, TranscriptsDisabled, NoTranscriptFound

# Funktion, um Videoinformationen zu erhalten
def get_video_info(video_id):
    try:
        # YouTube-API umgehen, indem wir die Seite direkt laden
        ssl_context = ssl._create_unverified_context()
        url = f"https://www.youtube.com/watch?v={video_id}"
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept-Language': 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7'
        }
        
        req = urllib.request.Request(url, headers=headers)
        response = urllib.request.urlopen(req, context=ssl_context)
        html = response.read().decode('utf-8')
        
        # Extrahiere Titel
        title_start = html.find('"title":"') + 9
        title_end = html.find('","', title_start)
        title = html[title_start:title_end].replace('\\u0026', '&')
        
        # Extrahiere Beschreibung
        desc_start = html.find('"description":{"simpleText":"') + 28
        if desc_start > 28:  # Falls der Marker gefunden wurde
            desc_end = html.find('"}', desc_start)
            description = html[desc_start:desc_end].replace('\\n', '\n').replace('\\u0026', '&')
        else:
            # Alternative Methode für die Beschreibung
            desc_start = html.find('"shortDescription":"') + 20
            if desc_start > 20:
                desc_end = html.find('","', desc_start)
                description = html[desc_start:desc_end].replace('\\n', '\n').replace('\\u0026', '&')
            else:
                description = "Keine Beschreibung gefunden"
        
        return {
            "title": title,
            "description": description
        }
    except Exception as e:
        return {
            "title": "Titel konnte nicht extrahiert werden",
            "description": "Beschreibung konnte nicht extrahiert werden",
            "error": str(e)
        }

# Funktion zum Abrufen von Transkripten
def get_transcript(video_id, languages=['de', 'en']):
    try:
        # Versuche alle verfügbaren Transkripte zu listen
        transcript_list = YouTubeTranscriptApi.list_transcripts(video_id)
        
        # Sammle Informationen über verfügbare Transkripte
        available_transcripts = []
        for transcript in transcript_list:
            available_transcripts.append({
                'language': transcript.language,
                'language_code': transcript.language_code,
                'is_generated': transcript.is_generated
            })
        
        # Versuche eine der angegebenen Sprachen zu finden
        transcript = None
        
        # Zuerst manuelle Transkripte in den angegebenen Sprachen
        for lang in languages:
            try:
                transcript = transcript_list.find_transcript([lang])
                if not transcript.is_generated:
                    break
            except NoTranscriptFound:
                continue
        
        # Wenn keine manuelle gefunden wurde, versuche generierte
        if transcript is None:
            for lang in languages:
                try:
                    transcript = transcript_list.find_transcript([lang])
                    break
                except NoTranscriptFound:
                    continue
        
        # Wenn immer noch nichts gefunden wurde, nimm das erste verfügbare
        if transcript is None and len(list(transcript_list)) > 0:
            transcript = list(transcript_list)[0]
        
        # Wenn ein Transkript gefunden wurde, hole die Daten
        if transcript:
            transcript_data = transcript.fetch()
            
            # Formatiere das Transkript
            full_text = ""
            for entry in transcript_data:
                full_text += entry['text'] + " "
            
            return {
                "success": True,
                "language": transcript.language,
                "language_code": transcript.language_code,
                "is_generated": transcript.is_generated,
                "available_transcripts": available_transcripts,
                "transcript_data": transcript_data,
                "full_text": full_text.strip()
            }
        else:
            return {
                "success": False,
                "error": "Keine passenden Untertitel gefunden",
                "available_transcripts": available_transcripts
            }
            
    except TranscriptsDisabled:
        return {
            "success": False,
            "error": "Untertitel sind für dieses Video deaktiviert"
        }
    except Exception as e:
        return {
            "success": False,
            "error": str(e)
        }

# Hauptfunktion
def main():
    parser = argparse.ArgumentParser(description='YouTube Transkript Extraktor')
    parser.add_argument('video_id', help='YouTube Video ID')
    parser.add_argument('--languages', default='de,en', help='Komma-getrennte Liste von Sprachcodes (Standard: de,en)')
    
    args = parser.parse_args()
    video_id = args.video_id
    languages = args.languages.split(',')
    
    # Videoinformationen abrufen
    video_info = get_video_info(video_id)
    
    # Transkript abrufen
    transcript_result = get_transcript(video_id, languages)
    
    # Ergebnisse kombinieren
    result = {
        "video_id": video_id,
        "title": video_info.get("title", ""),
        "description": video_info.get("description", ""),
        "transcript": transcript_result
    }
    
    # Als JSON ausgeben
    print(json.dumps(result, ensure_ascii=False, indent=2))

if __name__ == "__main__":
    main() 