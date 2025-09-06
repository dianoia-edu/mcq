#!/usr/bin/env python3
"""
YouTube Transcript Extractor
Extrahiert Untertitel von YouTube-Videos ohne externe APIs
"""

import sys
import json
import re
import urllib.request
import urllib.parse
import html

def log_error(message):
    """Fehler-Logging"""
    print(f"ERROR: {message}", file=sys.stderr)

def extract_video_id(url_or_id):
    """Extrahiert Video-ID aus YouTube-URL oder gibt ID zurück"""
    if len(url_or_id) == 11 and re.match(r'^[a-zA-Z0-9_-]+$', url_or_id):
        return url_or_id
    
    patterns = [
        r'(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})',
        r'youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})'
    ]
    
    for pattern in patterns:
        match = re.search(pattern, url_or_id)
        if match:
            return match.group(1)
    
    return None

def get_video_page(video_id):
    """Lädt YouTube-Video-Seite"""
    try:
        url = f"https://www.youtube.com/watch?v={video_id}"
        
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        }
        
        request = urllib.request.Request(url, headers=headers)
        
        with urllib.request.urlopen(request, timeout=30) as response:
            content = response.read().decode('utf-8')
            return content
            
    except Exception as e:
        log_error(f"Fehler beim Laden der Video-Seite: {e}")
        return None

def extract_caption_tracks(page_content):
    """Extrahiert Caption-Track-URLs aus Video-Seite"""
    try:
        # Suche nach Caption-Tracks in der Seite
        patterns = [
            r'"captionTracks":\s*(\[.*?\])',
            r'"captions":\s*{[^}]*"playerCaptionsTracklistRenderer":\s*{[^}]*"captionTracks":\s*(\[.*?\])'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, page_content)
            if match:
                try:
                    tracks_json = match.group(1)
                    tracks = json.loads(tracks_json)
                    return tracks
                except json.JSONDecodeError:
                    continue
        
        log_error("Keine Caption-Tracks gefunden")
        return None
        
    except Exception as e:
        log_error(f"Fehler beim Extrahieren der Caption-Tracks: {e}")
        return None

def download_caption(caption_url):
    """Lädt Caption-Datei herunter"""
    try:
        headers = {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        }
        
        request = urllib.request.Request(caption_url, headers=headers)
        
        with urllib.request.urlopen(request, timeout=30) as response:
            content = response.read().decode('utf-8')
            return content
            
    except Exception as e:
        log_error(f"Fehler beim Download der Caption: {e}")
        return None

def parse_xml_caption(xml_content):
    """Parsed XML-Caption zu strukturiertem Format"""
    try:
        transcript = []
        
        # Einfache XML-Parsing ohne externe Libraries
        text_pattern = r'<text[^>]*start="([^"]*)"[^>]*dur="([^"]*)"[^>]*>(.*?)</text>'
        matches = re.findall(text_pattern, xml_content, re.DOTALL)
        
        for start_time, duration, text in matches:
            # Bereinige Text
            text = html.unescape(text)
            text = re.sub(r'<[^>]+>', '', text)  # Entferne HTML-Tags
            text = text.strip()
            
            if text:
                transcript.append({
                    'start': float(start_time),
                    'duration': float(duration),
                    'text': text
                })
        
        return transcript
        
    except Exception as e:
        log_error(f"Fehler beim Parsen der XML-Caption: {e}")
        return None

def get_transcript(video_id):
    """Hauptfunktion: Extrahiert Transcript für Video-ID"""
    try:
        log_error(f"Starte Transcript-Extraktion für: {video_id}")
        
        # 1. Lade Video-Seite
        page_content = get_video_page(video_id)
        if not page_content:
            return None
        
        log_error("Video-Seite erfolgreich geladen")
        
        # 2. Extrahiere Caption-Tracks
        caption_tracks = extract_caption_tracks(page_content)
        if not caption_tracks:
            return None
        
        log_error(f"Caption-Tracks gefunden: {len(caption_tracks)}")
        
        # 3. Wähle besten Caption-Track (Deutsch oder Englisch)
        preferred_languages = ['de', 'en', 'auto']
        selected_track = None
        
        for lang in preferred_languages:
            for track in caption_tracks:
                if track.get('languageCode', '').startswith(lang):
                    selected_track = track
                    break
            if selected_track:
                break
        
        if not selected_track and caption_tracks:
            # Fallback: Nimm ersten verfügbaren Track
            selected_track = caption_tracks[0]
        
        if not selected_track:
            log_error("Kein geeigneter Caption-Track gefunden")
            return None
        
        log_error(f"Caption-Track ausgewählt: {selected_track.get('languageCode', 'unknown')}")
        
        # 4. Lade Caption-Datei
        caption_url = selected_track.get('baseUrl')
        if not caption_url:
            log_error("Caption-URL nicht gefunden")
            return None
        
        # Format-Parameter hinzufügen
        if '?' in caption_url:
            caption_url += '&fmt=srv3'
        else:
            caption_url += '?fmt=srv3'
        
        xml_content = download_caption(caption_url)
        if not xml_content:
            return None
        
        log_error("Caption-Datei erfolgreich heruntergeladen")
        
        # 5. Parse XML zu strukturiertem Format
        transcript = parse_xml_caption(xml_content)
        if not transcript:
            return None
        
        log_error(f"Transcript erfolgreich geparst: {len(transcript)} Einträge")
        
        return transcript
        
    except Exception as e:
        log_error(f"Unerwarteter Fehler: {e}")
        return None

def main():
    """Hauptprogramm"""
    if len(sys.argv) != 2:
        print(json.dumps({
            'success': False,
            'error': 'Usage: python youtube_transcript.py <video_id_or_url>'
        }))
        sys.exit(1)
    
    video_input = sys.argv[1]
    video_id = extract_video_id(video_input)
    
    if not video_id:
        print(json.dumps({
            'success': False,
            'error': 'Ungültige YouTube-URL oder Video-ID'
        }))
        sys.exit(1)
    
    transcript = get_transcript(video_id)
    
    if transcript:
        print(json.dumps({
            'success': True,
            'transcript': transcript,
            'video_id': video_id,
            'count': len(transcript)
        }, ensure_ascii=False, indent=2))
    else:
        print(json.dumps({
            'success': False,
            'error': 'Transcript konnte nicht extrahiert werden',
            'video_id': video_id
        }))
        sys.exit(1)

if __name__ == '__main__':
    main()
