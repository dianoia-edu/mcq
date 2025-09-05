#!/usr/bin/env python3
"""
Erweiterte YouTube Transcript Extraktion
Nutzt bewährte Libraries wie youtube-transcript-api
"""

import sys
import json
import re
from urllib.parse import urlparse, parse_qs
import time

def install_and_import(package):
    """Installiert und importiert Pakete dynamisch"""
    import subprocess
    import importlib
    
    try:
        return importlib.import_module(package)
    except ImportError:
        print(f"Installing {package}...", file=sys.stderr)
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])
        return importlib.import_module(package)

class AdvancedYouTubeExtractor:
    
    def __init__(self):
        self.methods = [
            ('youtube_transcript_api', self.method_transcript_api),
            ('pytube_method', self.method_pytube),
            ('yt_dlp_method', self.method_yt_dlp),
            ('direct_api_method', self.method_direct_api),
            ('osiris_js_equivalent', self.method_osiris_equivalent)
        ]
    
    def extract_video_id(self, url):
        """Video-ID aus verschiedenen YouTube-URL-Formaten extrahieren"""
        patterns = [
            r'(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)',
            r'youtube\.com\/v\/([^&\n?#]+)',
            r'youtube\.com\/watch\?.*v=([^&\n?#]+)'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, url)
            if match:
                return match.group(1)
        
        return None
    
    def get_transcript(self, video_url):
        """Hauptmethode: Versucht alle verfügbaren Methoden"""
        video_id = self.extract_video_id(video_url)
        if not video_id:
            return {'success': False, 'error': 'Ungültige YouTube-URL'}
        
        print(f"🎯 Video-ID extrahiert: {video_id}", file=sys.stderr)
        
        errors = {}
        
        for method_name, method_func in self.methods:
            try:
                print(f"🔄 Versuche Methode: {method_name}", file=sys.stderr)
                result = method_func(video_id, video_url)
                
                if result and len(result.strip()) > 100:
                    cleaned = self.clean_transcript(result)
                    print(f"✅ Erfolg mit: {method_name} ({len(cleaned)} Zeichen)", file=sys.stderr)
                    return {
                        'success': True,
                        'transcript': cleaned,
                        'source': method_name,
                        'length': len(cleaned)
                    }
                else:
                    error_msg = f"Transcript zu kurz oder leer (Länge: {len(result) if result else 0})"
                    print(f"⚠️ {method_name}: {error_msg}", file=sys.stderr)
                    errors[method_name] = error_msg
                    
            except Exception as e:
                error_msg = str(e)
                print(f"❌ {method_name} fehlgeschlagen: {error_msg}", file=sys.stderr)
                errors[method_name] = error_msg
                continue
        
        return {'success': False, 'error': 'Alle Methoden fehlgeschlagen', 'details': errors}
    
    def method_transcript_api(self, video_id, video_url):
        """Methode 1: youtube-transcript-api (wie downsub)"""
        try:
            # Dynamisch installieren und importieren
            youtube_transcript_api = install_and_import('youtube_transcript_api')
            
            print(f"  📚 youtube-transcript-api geladen", file=sys.stderr)
            
            # Versuche verschiedene Sprachen
            languages = ['de', 'en', 'auto']
            
            for lang in languages:
                try:
                    print(f"  🌐 Versuche Sprache: {lang}", file=sys.stderr)
                    
                    if lang == 'auto':
                        # Alle verfügbaren Transcripts abrufen
                        transcript_list = youtube_transcript_api.YouTubeTranscriptApi.list_transcripts(video_id)
                        
                        # Erst nach deutschen/englischen suchen
                        for transcript in transcript_list:
                            if transcript.language_code in ['de', 'en', 'de-DE', 'en-US']:
                                print(f"  📋 Gefunden: {transcript.language_code}", file=sys.stderr)
                                transcript_data = transcript.fetch()
                                return self.format_transcript_data(transcript_data)
                        
                        # Fallback: Erstes verfügbares
                        for transcript in transcript_list:
                            print(f"  📋 Fallback: {transcript.language_code}", file=sys.stderr)
                            transcript_data = transcript.fetch()
                            return self.format_transcript_data(transcript_data)
                            
                    else:
                        # Spezifische Sprache
                        transcript_data = youtube_transcript_api.YouTubeTranscriptApi.get_transcript(video_id, languages=[lang])
                        return self.format_transcript_data(transcript_data)
                        
                except Exception as lang_error:
                    print(f"  ❌ Sprache {lang}: {str(lang_error)}", file=sys.stderr)
                    continue
            
            raise Exception("Keine Transcripts für verfügbare Sprachen gefunden")
            
        except Exception as e:
            raise Exception(f"youtube-transcript-api Fehler: {str(e)}")
    
    def method_pytube(self, video_id, video_url):
        """Methode 2: pytube für Captions"""
        try:
            pytube = install_and_import('pytube')
            
            print(f"  📚 pytube geladen", file=sys.stderr)
            
            # YouTube-Objekt erstellen
            yt = pytube.YouTube(video_url)
            
            print(f"  📺 Video: {yt.title[:50]}...", file=sys.stderr)
            
            # Captions abrufen
            captions = yt.captions
            
            if not captions:
                raise Exception("Keine Captions verfügbar")
            
            print(f"  📋 Verfügbare Captions: {list(captions.keys())}", file=sys.stderr)
            
            # Priorisierte Sprachen
            preferred_langs = ['de', 'en', 'a.de', 'a.en']
            
            for lang in preferred_langs:
                if lang in captions:
                    print(f"  ✅ Verwende Caption: {lang}", file=sys.stderr)
                    caption = captions[lang]
                    transcript = caption.generate_srt_captions()
                    return self.parse_srt_format(transcript)
            
            # Fallback: Erste verfügbare Caption
            first_caption = list(captions.values())[0]
            print(f"  🔄 Fallback Caption verwendet", file=sys.stderr)
            transcript = first_caption.generate_srt_captions()
            return self.parse_srt_format(transcript)
            
        except Exception as e:
            raise Exception(f"pytube Fehler: {str(e)}")
    
    def method_yt_dlp(self, video_id, video_url):
        """Methode 3: yt-dlp für Subtitles (ohne Audio-Download)"""
        import subprocess
        import tempfile
        import os
        
        print(f"  🔧 yt-dlp Subtitle-Extraktion", file=sys.stderr)
        
        with tempfile.TemporaryDirectory() as temp_dir:
            # Nur Subtitles downloaden, kein Video/Audio
            cmd = [
                'yt-dlp',
                '--write-subs',
                '--write-auto-subs',
                '--sub-langs', 'de,en',
                '--skip-download',
                '--output', f'{temp_dir}/%(title)s.%(ext)s',
                video_url
            ]
            
            try:
                result = subprocess.run(cmd, capture_output=True, text=True, timeout=30)
                
                if result.returncode != 0:
                    raise Exception(f"yt-dlp Fehler: {result.stderr}")
                
                # Suche nach .vtt oder .srt Dateien
                subtitle_files = []
                for file in os.listdir(temp_dir):
                    if file.endswith(('.vtt', '.srt')):
                        subtitle_files.append(os.path.join(temp_dir, file))
                
                if not subtitle_files:
                    raise Exception("Keine Subtitle-Dateien gefunden")
                
                # Erste Subtitle-Datei lesen
                subtitle_file = subtitle_files[0]
                print(f"  📄 Lese Subtitle-Datei: {os.path.basename(subtitle_file)}", file=sys.stderr)
                
                with open(subtitle_file, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                if subtitle_file.endswith('.vtt'):
                    return self.parse_vtt_format(content)
                else:
                    return self.parse_srt_format(content)
                    
            except subprocess.TimeoutExpired:
                raise Exception("yt-dlp Timeout")
            except Exception as e:
                raise Exception(f"yt-dlp Subprocess-Fehler: {str(e)}")
    
    def method_direct_api(self, video_id, video_url):
        """Methode 4: Direkte YouTube-API (verbessert)"""
        import requests
        import xml.etree.ElementTree as ET
        
        # Session mit Browser-like Headers
        session = requests.Session()
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1'
        })
        
        languages = ['de', 'en', 'auto']
        formats = ['json3', 'srv3', 'srv1', 'ttml']
        
        for lang in languages:
            for fmt in formats:
                url = f"https://www.youtube.com/api/timedtext"
                params = {
                    'lang': lang,
                    'v': video_id,
                    'fmt': fmt,
                    'tlang': lang if lang != 'auto' else 'de'
                }
                
                try:
                    print(f"  🌐 API-Request: {fmt}/{lang}", file=sys.stderr)
                    response = session.get(url, params=params, timeout=10)
                    
                    if response.status_code == 200 and len(response.content) > 100:
                        print(f"  ✅ API-Response: {len(response.content)} bytes", file=sys.stderr)
                        
                        if fmt == 'json3':
                            return self.parse_json3_format(response.text)
                        elif fmt in ['srv3', 'srv1', 'ttml']:
                            return self.parse_xml_format(response.text)
                    else:
                        print(f"  ❌ API-Response: {response.status_code}", file=sys.stderr)
                        
                except Exception as e:
                    print(f"  ❌ API-Request Fehler: {str(e)}", file=sys.stderr)
                    continue
        
        raise Exception("Direkte API liefert keine verwendbaren Captions")
    
    def method_osiris_equivalent(self, video_id, video_url):
        """Methode 5: Osiris-SDK-ähnlicher Ansatz"""
        import requests
        import re
        
        print(f"  🔍 Osiris-ähnliche HTML-Extraktion", file=sys.stderr)
        
        # Session mit rotierenden User-Agents
        user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]
        
        import random
        session = requests.Session()
        session.headers.update({
            'User-Agent': random.choice(user_agents),
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language': 'de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding': 'gzip, deflate, br',
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
        })
        
        # YouTube-Seite laden
        response = session.get(video_url, timeout=15)
        
        if response.status_code != 200:
            raise Exception(f"YouTube-Seite nicht erreichbar: {response.status_code}")
        
        html = response.text
        print(f"  📄 HTML geladen: {len(html)} Zeichen", file=sys.stderr)
        
        # Erweiterte Pattern für Caption-Tracks
        patterns = [
            r'"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]',
            r'"captionTracks":\[(.*?)\]',
            r'ytInitialPlayerResponse.*?"captionTracks":\[(.*?)\]',
            r'"playerCaptionsRenderer".*?"captionTracks":\[(.*?)\]'
        ]
        
        for i, pattern in enumerate(patterns):
            match = re.search(pattern, html, re.DOTALL)
            if match:
                print(f"  🎯 Pattern {i+1} gefunden", file=sys.stderr)
                caption_data = '[' + match.group(1) + ']'
                
                try:
                    import json
                    captions = json.loads(caption_data)
                    
                    # Beste Caption auswählen
                    for caption in captions:
                        if 'baseUrl' in caption:
                            lang_code = caption.get('languageCode', 'unknown')
                            print(f"  📋 Versuche Caption: {lang_code}", file=sys.stderr)
                            
                            # Caption-Content laden mit Session
                            caption_response = session.get(caption['baseUrl'], timeout=10)
                            
                            if caption_response.status_code == 200 and caption_response.content:
                                print(f"  ✅ Caption geladen: {len(caption_response.content)} bytes", file=sys.stderr)
                                return self.parse_xml_format(caption_response.text)
                            else:
                                print(f"  ❌ Caption-Request fehlgeschlagen: {caption_response.status_code}", file=sys.stderr)
                    
                except json.JSONDecodeError as e:
                    print(f"  ❌ JSON-Parse-Fehler: {str(e)}", file=sys.stderr)
                    continue
        
        raise Exception("Osiris-Methode: Keine Caption-Tracks gefunden")
    
    def format_transcript_data(self, transcript_data):
        """Formatiert youtube-transcript-api Daten"""
        if not transcript_data:
            return ""
        
        text_parts = []
        for entry in transcript_data:
            if 'text' in entry:
                text_parts.append(entry['text'])
        
        return ' '.join(text_parts)
    
    def parse_srt_format(self, srt_content):
        """Parst SRT-Format"""
        lines = srt_content.split('\n')
        text_parts = []
        
        for line in lines:
            line = line.strip()
            # Skip Zeilen-Nummern und Timestamps
            if line and not line.isdigit() and '-->' not in line:
                text_parts.append(line)
        
        return ' '.join(text_parts)
    
    def parse_vtt_format(self, vtt_content):
        """Parst VTT-Format"""
        lines = vtt_content.split('\n')
        text_parts = []
        
        skip_header = True
        for line in lines:
            line = line.strip()
            
            if skip_header and line == '':
                skip_header = False
                continue
            
            if skip_header:
                continue
            
            # Skip Timestamps
            if '-->' in line or line == '':
                continue
            
            text_parts.append(line)
        
        return ' '.join(text_parts)
    
    def parse_json3_format(self, json_content):
        """Parst JSON3-Format"""
        try:
            import json
            data = json.loads(json_content)
            
            if 'events' in data:
                text_parts = []
                for event in data['events']:
                    if 'segs' in event:
                        for seg in event['segs']:
                            if 'utf8' in seg:
                                text_parts.append(seg['utf8'])
                
                return ' '.join(text_parts)
        except:
            pass
        
        return ""
    
    def parse_xml_format(self, xml_content):
        """Parst XML-Format"""
        try:
            import xml.etree.ElementTree as ET
            root = ET.fromstring(xml_content)
            
            text_parts = []
            for text_elem in root.findall('.//text'):
                if text_elem.text:
                    text_parts.append(text_elem.text)
            
            return ' '.join(text_parts)
        except:
            # Fallback: RegEx-basiertes Parsing
            import re
            text_matches = re.findall(r'<text[^>]*>(.*?)</text>', xml_content, re.DOTALL)
            return ' '.join(text_matches)
    
    def clean_transcript(self, transcript):
        """Transcript bereinigen"""
        if not transcript:
            return ''
        
        # HTML-Entities dekodieren
        import html
        transcript = html.unescape(transcript)
        
        # Timestamps entfernen
        transcript = re.sub(r'\[?\d{1,2}:\d{2}(?::\d{2})?\]?', '', transcript)
        transcript = re.sub(r'\(\d{1,2}:\d{2}(?::\d{2})?\)', '', transcript)
        
        # Mehrfache Leerzeichen reduzieren
        transcript = re.sub(r'\s+', ' ', transcript)
        transcript = re.sub(r'\n+', '\n', transcript)
        
        return transcript.strip()

def main():
    if len(sys.argv) != 2:
        print(json.dumps({'success': False, 'error': 'Usage: python script.py <youtube_url>'}))
        sys.exit(1)
    
    video_url = sys.argv[1]
    extractor = AdvancedYouTubeExtractor()
    
    result = extractor.get_transcript(video_url)
    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main()
