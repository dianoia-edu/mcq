#!/usr/bin/env python3
"""
Robuste YouTube Transcript Extraktion
Kombiniert mehrere bewährte Methoden
"""

import sys
import json
import re
import requests
import urllib.parse
from urllib.parse import parse_qs, urlparse
import time
import random

class YouTubeTranscriptExtractor:
    
    def __init__(self):
        # Verschiedene User-Agents rotieren
        self.user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
        ]
        
        self.session = requests.Session()
        self.setup_session()
    
    def setup_session(self):
        """Session mit Browser-ähnlichen Headers einrichten"""
        self.session.headers.update({
            'User-Agent': random.choice(self.user_agents),
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none'
        })
    
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
        
        methods = [
            ('youtube_direct_api', self.method_direct_api),
            ('youtube_html_extraction', self.method_html_extraction),
            ('youtube_mobile_api', self.method_mobile_api),
            ('youtube_embed_extraction', self.method_embed_extraction),
            ('youtube_innertube_api', self.method_innertube_api)
        ]
        
        errors = {}
        
        for method_name, method_func in methods:
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
    
    def method_direct_api(self, video_id, video_url):
        """Methode 1: Direkte YouTube Caption API"""
        languages = ['de', 'en', 'auto']
        formats = ['json3', 'srv3', 'srv1']
        
        for lang in languages:
            for fmt in formats:
                url = f"https://www.youtube.com/api/timedtext?lang={lang}&v={video_id}&fmt={fmt}"
                
                try:
                    print(f"  🌐 API-URL: {url}", file=sys.stderr)
                    response = self.session.get(url, timeout=10)
                    print(f"  📊 Status: {response.status_code}, Länge: {len(response.content)}", file=sys.stderr)
                    
                    if response.status_code == 200 and response.content:
                        if fmt == 'json3':
                            return self.parse_json3_transcript(response.text)
                        else:
                            return self.parse_xml_transcript(response.text)
                    elif response.status_code != 200:
                        print(f"  ❌ HTTP {response.status_code}: {response.text[:200]}", file=sys.stderr)
                except Exception as e:
                    print(f"  ❌ Request-Fehler: {str(e)}", file=sys.stderr)
                    continue
        
        raise Exception("Direkte API liefert keine Captions")
    
    def method_html_extraction(self, video_id, video_url):
        """Methode 2: HTML-Extraktion von der YouTube-Seite"""
        
        # Verschiedene URLs probieren
        urls = [
            video_url,
            f"https://www.youtube.com/watch?v={video_id}",
            f"https://youtube.com/watch?v={video_id}"
        ]
        
        for url in urls:
            try:
                print(f"  🌐 HTML-URL: {url}", file=sys.stderr)
                response = self.session.get(url, timeout=15)
                print(f"  📊 HTML Status: {response.status_code}, Länge: {len(response.content)}", file=sys.stderr)
                
                if response.status_code == 200:
                    html = response.text
                    
                    # Suche nach Caption-Daten im HTML (funktionierendes Pattern zuerst)
                    patterns = [
                        r'"captions":\{"playerCaptionsTracklistRenderer":\{"captionTracks":\[(.*?)\]',
                        r'"captionTracks":\[(.*?)\]',
                        r'ytInitialPlayerResponse.*?"captions".*?"captionTracks":\[(.*?)\]'
                    ]
                    
                    for pattern in patterns:
                        match = re.search(pattern, html, re.DOTALL)
                        if match:
                            print(f"  🎯 Pattern gefunden: {len(match.group(1))} Zeichen", file=sys.stderr)
                            caption_data = '[' + match.group(1) + ']'
                            
                            try:
                                captions = json.loads(caption_data)
                                print(f"  📋 JSON geparst: {len(captions)} Tracks", file=sys.stderr)
                                
                                for i, caption in enumerate(captions):
                                    if 'baseUrl' in caption:
                                        lang = caption.get('languageCode', 'unknown')
                                        print(f"  🌐 Track {i+1} ({lang}): Versuche BaseURL...", file=sys.stderr)
                                        
                                        # Bessere Headers für BaseURL-Request
                                        headers = {
                                            'User-Agent': self.session.headers['User-Agent'],
                                            'Referer': 'https://www.youtube.com/',
                                            'Accept': 'application/xml,text/xml,*/*',
                                            'Accept-Language': 'de,en;q=0.9',
                                            'Origin': 'https://www.youtube.com'
                                        }
                                        
                                        transcript_response = self.session.get(
                                            caption['baseUrl'], 
                                            headers=headers,
                                            timeout=15
                                        )
                                        
                                        print(f"  📊 BaseURL Status: {transcript_response.status_code}, Länge: {len(transcript_response.content)}", file=sys.stderr)
                                        
                                        if transcript_response.status_code == 200 and transcript_response.content:
                                            transcript = self.parse_xml_transcript(transcript_response.text)
                                            if transcript and len(transcript.strip()) > 50:
                                                print(f"  ✅ Transcript erfolgreich: {len(transcript)} Zeichen", file=sys.stderr)
                                                return transcript
                                        else:
                                            print(f"  ❌ BaseURL fehlgeschlagen: HTTP {transcript_response.status_code}", file=sys.stderr)
                                            
                            except json.JSONDecodeError as e:
                                print(f"  ❌ JSON-Fehler: {str(e)}", file=sys.stderr)
                                continue
                    
                    # Alternative: Suche nach JavaScript-Variablen
                    if 'ytInitialPlayerResponse' in html:
                        js_match = re.search(r'var ytInitialPlayerResponse = ({.*?});', html)
                        if js_match:
                            try:
                                player_data = json.loads(js_match.group(1))
                                return self.extract_from_player_data(player_data)
                            except:
                                pass
                        
            except Exception as e:
                continue
        
        raise Exception("HTML-Extraktion fehlgeschlagen")
    
    def method_mobile_api(self, video_id, video_url):
        """Methode 3: Mobile YouTube API (weniger Restrictions)"""
        
        mobile_headers = self.session.headers.copy()
        mobile_headers.update({
            'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15A372 Safari/604.1'
        })
        
        mobile_url = f"https://m.youtube.com/watch?v={video_id}"
        
        response = self.session.get(mobile_url, headers=mobile_headers, timeout=10)
        if response.status_code == 200:
            return self.extract_captions_from_mobile(response.text, video_id)
        
        raise Exception("Mobile API nicht verfügbar")
    
    def method_embed_extraction(self, video_id, video_url):
        """Methode 4: Embed-Player Extraktion"""
        
        embed_url = f"https://www.youtube.com/embed/{video_id}"
        
        response = self.session.get(embed_url, timeout=10)
        if response.status_code == 200:
            html = response.text
            
            # Suche nach Player-Konfiguration
            config_match = re.search(r'"PLAYER_CONFIG":\s*({.*?}),', html)
            if config_match:
                try:
                    config = json.loads(config_match.group(1))
                    return self.extract_from_player_config(config, video_id)
                except:
                    pass
        
        raise Exception("Embed-Extraktion fehlgeschlagen")
    
    def method_innertube_api(self, video_id, video_url):
        """Methode 5: YouTube InnerTube API (interne API)"""
        
        api_url = "https://www.youtube.com/youtubei/v1/player"
        
        # InnerTube API Payload
        payload = {
            "context": {
                "client": {
                    "clientName": "WEB",
                    "clientVersion": "2.20231219.01.00"
                }
            },
            "videoId": video_id
        }
        
        headers = self.session.headers.copy()
        headers.update({
            'Content-Type': 'application/json',
            'X-YouTube-Client-Name': '1',
            'X-YouTube-Client-Version': '2.20231219.01.00'
        })
        
        response = self.session.post(api_url, json=payload, headers=headers, timeout=10)
        
        if response.status_code == 200:
            data = response.json()
            return self.extract_from_innertube_response(data)
        
        raise Exception("InnerTube API fehlgeschlagen")
    
    def parse_json3_transcript(self, json_text):
        """JSON3-Format parsen"""
        try:
            data = json.loads(json_text)
            transcript = ''
            
            if 'events' in data:
                for event in data['events']:
                    if 'segs' in event:
                        for seg in event['segs']:
                            if 'utf8' in seg:
                                transcript += seg['utf8'] + ' '
            
            return transcript.strip()
        except:
            return None
    
    def parse_xml_transcript(self, xml_text):
        """XML-Format parsen"""
        try:
            import xml.etree.ElementTree as ET
            root = ET.fromstring(xml_text)
            
            transcript = ''
            for text_elem in root.findall('.//text'):
                transcript += text_elem.text + ' ' if text_elem.text else ''
            
            return transcript.strip()
        except:
            # Fallback: Regex-basiertes Parsing
            text_matches = re.findall(r'<text[^>]*>(.*?)</text>', xml_text, re.DOTALL)
            return ' '.join(text_matches).strip()
    
    def extract_from_player_data(self, player_data):
        """Aus ytInitialPlayerResponse extrahieren"""
        try:
            captions = player_data.get('captions', {})
            tracks = captions.get('playerCaptionsTracklistRenderer', {}).get('captionTracks', [])
            
            for track in tracks:
                if 'baseUrl' in track:
                    response = self.session.get(track['baseUrl'])
                    if response.status_code == 200:
                        return self.parse_xml_transcript(response.text)
        except:
            pass
        
        return None
    
    def extract_captions_from_mobile(self, html, video_id):
        """Captions aus Mobile-HTML extrahieren"""
        # Mobile-spezifische Patterns
        patterns = [
            r'"caption_tracks":\[(.*?)\]',
            r'"captionTracks":\[(.*?)\]'
        ]
        
        for pattern in patterns:
            match = re.search(pattern, html)
            if match:
                try:
                    tracks = json.loads('[' + match.group(1) + ']')
                    for track in tracks:
                        if 'baseUrl' in track:
                            response = self.session.get(track['baseUrl'])
                            if response.status_code == 200:
                                return self.parse_xml_transcript(response.text)
                except:
                    continue
        
        return None
    
    def extract_from_player_config(self, config, video_id):
        """Aus Player-Konfiguration extrahieren"""
        try:
            if 'args' in config and 'player_response' in config['args']:
                player_response = json.loads(config['args']['player_response'])
                return self.extract_from_player_data(player_response)
        except:
            pass
        
        return None
    
    def extract_from_innertube_response(self, data):
        """Aus InnerTube API Response extrahieren"""
        try:
            captions = data.get('captions', {})
            tracks = captions.get('playerCaptionsTracklistRenderer', {}).get('captionTracks', [])
            
            for track in tracks:
                if 'baseUrl' in track:
                    response = self.session.get(track['baseUrl'])
                    if response.status_code == 200:
                        return self.parse_xml_transcript(response.text)
        except:
            pass
        
        return None
    
    def clean_transcript(self, transcript):
        """Transcript bereinigen"""
        # HTML-Entities dekodieren
        import html
        transcript = html.unescape(transcript)
        
        # Timestamps entfernen
        transcript = re.sub(r'\[?\d{1,2}:\d{2}(?::\d{2})?\]?', '', transcript)
        transcript = re.sub(r'\(\d{1,2}:\d{2}(?::\d{2})?\)', '', transcript)
        
        # Speaker-Labels entfernen
        transcript = re.sub(r'^[A-Za-z\s]+\d*:\s*', '', transcript, flags=re.MULTILINE)
        
        # Mehrfache Leerzeichen reduzieren
        transcript = re.sub(r'\s+', ' ', transcript)
        transcript = re.sub(r'\n+', '\n', transcript)
        
        return transcript.strip()

def main():
    if len(sys.argv) != 2:
        print(json.dumps({'success': False, 'error': 'Usage: python script.py <youtube_url>'}))
        sys.exit(1)
    
    video_url = sys.argv[1]
    extractor = YouTubeTranscriptExtractor()
    
    result = extractor.get_transcript(video_url)
    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main()
