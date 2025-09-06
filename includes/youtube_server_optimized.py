#!/usr/bin/env python3
"""
SERVER-OPTIMIZED NUCLEAR OPTION
Speziell für Live-Server ohne Chrome optimiert
"""

import sys
import json
import re
import time
import random
import subprocess
import tempfile
import os
from urllib.parse import urlparse, parse_qs

def install_and_import(package):
    """Installiert und importiert Pakete"""
    import importlib
    try:
        return importlib.import_module(package)
    except ImportError:
        print(f"Installing {package}...", file=sys.stderr)
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])
        return importlib.import_module(package)

class YouTubeServerOptimized:
    
    def __init__(self):
        self.success_method = None
        
    def extract_video_id(self, url):
        """Video-ID extrahieren"""
        patterns = [
            r'(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)',
            r'youtube\.com\/v\/([^&\n?#]+)',
        ]
        
        for pattern in patterns:
            match = re.search(pattern, url)
            if match:
                return match.group(1)
        return None
    
    def get_transcript(self, video_url):
        """SERVER-OPTIMIZED: Methoden ohne Chrome/Selenium"""
        video_id = self.extract_video_id(video_url)
        if not video_id:
            return {'success': False, 'error': 'Ungültige YouTube-URL'}
        
        print(f"🖥️ SERVER-OPTIMIZED für: {video_id}", file=sys.stderr)
        
        # Nur Server-kompatible Methoden
        methods = [
            ('yt_dlp_residential_proxy', self.method_yt_dlp_residential_proxy),
            ('youtube_transcript_api_stealth', self.method_youtube_transcript_api_stealth),
            ('embedded_player_extraction', self.method_embedded_player_extraction),
            ('mobile_website_scraping', self.method_mobile_website_scraping),
            ('invidious_proxy', self.method_invidious_proxy),
            ('session_stealing_advanced', self.method_session_stealing_advanced)
        ]
        
        for method_name, method_func in methods:
            try:
                print(f"🖥️ SERVER: {method_name}", file=sys.stderr)
                result = method_func(video_id, video_url)
                
                if result and len(result.strip()) > 100:
                    cleaned = self.clean_transcript(result)
                    print(f"🖥️ SERVER SUCCESS: {method_name} ({len(cleaned)} Zeichen)", file=sys.stderr)
                    self.success_method = method_name
                    return {
                        'success': True,
                        'transcript': cleaned,
                        'source': f'SERVER_{method_name}',
                        'length': len(cleaned)
                    }
                    
            except Exception as e:
                print(f"🖥️ SERVER {method_name} FAILED: {str(e)}", file=sys.stderr)
                continue
        
        return {'success': False, 'error': 'SERVER-OPTIMIZED FAILED - Alle Methoden blockiert!'}
    
    def method_yt_dlp_residential_proxy(self, video_id, video_url):
        """Methode 1: YT-DLP mit Residential-Proxy-Simulation"""
        try:
            yt_dlp = install_and_import('yt_dlp')
        except:
            print(f"  ❌ yt-dlp nicht verfügbar, installiere...", file=sys.stderr)
            subprocess.check_call([sys.executable, "-m", "pip", "install", "yt-dlp"])
            yt_dlp = install_and_import('yt_dlp')
        
        print(f"  🏠 YT-DLP mit Residential-Proxy-Simulation", file=sys.stderr)
        
        # Verschiedene Residential-IPs simulieren
        proxy_configs = [
            {
                'http_headers': {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language': 'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
                    'Accept-Encoding': 'gzip, deflate, br',
                    'DNT': '1',
                    'Connection': 'keep-alive',
                    'Upgrade-Insecure-Requests': '1',
                    'X-Forwarded-For': '78.47.183.23',  # Deutsche Residential IP
                    'CF-IPCountry': 'DE'
                },
                'extractor_args': {
                    'youtube': {
                        'player_client': ['ios', 'android'],
                        'player_skip': ['dash', 'hls'],
                        'lang': ['de']
                    }
                }
            },
            {
                'http_headers': {
                    'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language': 'en-US,en;q=0.9',
                    'X-Forwarded-For': '93.184.216.34',  # US Residential IP
                    'CF-IPCountry': 'US'
                },
                'extractor_args': {
                    'youtube': {
                        'player_client': ['tv_embedded', 'android'],
                        'player_skip': ['webpage'],
                        'lang': ['en', 'de']
                    }
                }
            }
        ]
        
        for i, config in enumerate(proxy_configs):
            try:
                print(f"    🏠 Teste Proxy-Config {i+1}/2...", file=sys.stderr)
                
                ydl_opts = {
                    'quiet': True,
                    'no_warnings': True,
                    'writesubtitles': True,
                    'writeautomaticsub': True,
                    'skip_download': True,
                    'subtitleslangs': ['de', 'en', 'auto'],
                    'subtitlesformat': 'best',
                    'sleep_interval': 2,
                    'max_sleep_interval': 5,
                    **config
                }
                
                with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                    info = ydl.extract_info(video_url, download=False)
                    
                    # Versuche alle verfügbaren Subtitles
                    for sub_type in ['subtitles', 'automatic_captions']:
                        if sub_type in info and info[sub_type]:
                            for lang in ['de', 'en', 'auto']:
                                if lang in info[sub_type]:
                                    subs = info[sub_type][lang]
                                    
                                    for sub in subs:
                                        if 'url' in sub:
                                            try:
                                                import requests
                                                response = requests.get(sub['url'], timeout=15, headers=config['http_headers'])
                                                response.encoding = 'utf-8'
                                                
                                                if response.status_code == 200 and len(response.text) > 100:
                                                    if 'json3' in sub.get('url', '') or sub.get('ext') == 'json3':
                                                        return self.parse_json3_format(response.text)
                                                    elif '<text' in response.text:
                                                        return self.parse_xml_format(response.text)
                                                    else:
                                                        return response.text
                                                        
                                            except Exception as e:
                                                continue
                                                
                time.sleep(random.uniform(3, 7))  # Pause zwischen Proxies
                                
            except Exception as e:
                print(f"    ❌ Proxy-Config {i+1} failed: {str(e)[:100]}", file=sys.stderr)
                continue
        
        raise Exception("YT-DLP Residential-Proxy: Alle Konfigs fehlgeschlagen")
    
    def method_youtube_transcript_api_stealth(self, video_id, video_url):
        """Methode 2: YouTube-Transcript-API mit Stealth"""
        try:
            youtube_transcript_api = install_and_import('youtube_transcript_api')
        except:
            print(f"  📦 youtube-transcript-api installieren...", file=sys.stderr)
            subprocess.check_call([sys.executable, "-m", "pip", "install", "youtube-transcript-api"])
            youtube_transcript_api = install_and_import('youtube_transcript_api')
        
        print(f"  📜 YouTube-Transcript-API mit Stealth", file=sys.stderr)
        
        # Verschiedene API-Zugriffsmethoden
        try:
            # Methode 1: Direkt mit deutscher Sprache
            transcript_list = youtube_transcript_api.YouTubeTranscriptApi.list_transcripts(video_id)
            
            # Priorisiere deutsche Transcripts
            for transcript in transcript_list:
                if transcript.language_code in ['de', 'de-DE']:
                    print(f"    ✅ Deutsches Transcript gefunden: {transcript.language_code}", file=sys.stderr)
                    data = transcript.fetch()
                    return self.format_transcript_data(data)
            
            # Fallback: Englische Transcripts
            for transcript in transcript_list:
                if transcript.language_code in ['en', 'en-US', 'en-GB']:
                    print(f"    ✅ Englisches Transcript gefunden: {transcript.language_code}", file=sys.stderr)
                    data = transcript.fetch()
                    return self.format_transcript_data(data)
            
            # Fallback: Auto-generated
            for transcript in transcript_list:
                if transcript.is_generated:
                    print(f"    🤖 Auto-Transcript gefunden: {transcript.language_code}", file=sys.stderr)
                    data = transcript.fetch()
                    return self.format_transcript_data(data)
                    
        except Exception as e:
            print(f"    ❌ Transcript-API Error: {str(e)}", file=sys.stderr)
        
        raise Exception("YouTube-Transcript-API: Keine Transcripts verfügbar")
    
    def method_embedded_player_extraction(self, video_id, video_url):
        """Methode 3: Embedded Player Caption-Extraktion"""
        requests = install_and_import('requests')
        
        print(f"  📺 Embedded Player Caption-Extraktion", file=sys.stderr)
        
        # Verschiedene Embedded Player URLs
        embed_urls = [
            f"https://www.youtube.com/embed/{video_id}",
            f"https://www.youtube-nocookie.com/embed/{video_id}",
            f"https://m.youtube.com/embed/{video_id}",
            f"https://tv.youtube.com/watch?v={video_id}"
        ]
        
        session = requests.Session()
        
        for i, embed_url in enumerate(embed_urls):
            try:
                print(f"    📺 Teste Embed-URL {i+1}/4: {embed_url[:50]}...", file=sys.stderr)
                
                headers = {
                    'User-Agent': f'Mozilla/5.0 (Smart TV; Tizen 4.0) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 TV Safari/537.36',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language': 'de-DE,de;q=0.8,en;q=0.3',
                    'Referer': 'https://www.google.com/',
                    'DNT': '1'
                }
                
                response = session.get(embed_url, headers=headers, timeout=15)
                response.encoding = 'utf-8'
                html = response.text
                
                if len(html) > 5000:  # Mindestgröße für validen Player
                    print(f"    ✅ Embed-Player geladen: {len(html)} Zeichen", file=sys.stderr)
                    
                    # Suche nach Caption-URLs in verschiedenen Formaten
                    caption_patterns = [
                        r'"captionTracks":\s*\[\s*\{\s*"baseUrl":\s*"([^"]+)"',
                        r'"timedTextUrl":\s*"([^"]+)"',
                        r'/api/timedtext[^"]*"[^"]*"([^"]*timedtext[^"]*)"',
                        r'https://www\.youtube\.com/api/timedtext[^"]+',
                        r'"url":\s*"(https://[^"]*timedtext[^"]*)"'
                    ]
                    
                    for pattern in caption_patterns:
                        matches = re.finditer(pattern, html)
                        for match in matches:
                            try:
                                caption_url = match.group(1).replace('\\u0026', '&').replace('\\/', '/')
                                
                                if 'timedtext' in caption_url:
                                    print(f"    🎯 Caption-URL gefunden: {caption_url[:60]}...", file=sys.stderr)
                                    
                                    caption_response = session.get(caption_url, headers=headers, timeout=10)
                                    caption_response.encoding = 'utf-8'
                                    
                                    if caption_response.status_code == 200 and len(caption_response.text) > 100:
                                        if 'events' in caption_response.text:
                                            return self.parse_json3_format(caption_response.text)
                                        elif '<text' in caption_response.text:
                                            return self.parse_xml_format(caption_response.text)
                                            
                            except Exception as e:
                                continue
                
                time.sleep(random.uniform(2, 4))
                
            except Exception as e:
                print(f"    ❌ Embed-URL {i+1} failed: {str(e)[:50]}", file=sys.stderr)
                continue
        
        raise Exception("Embedded Player: Keine Caption-URLs gefunden")
    
    def method_mobile_website_scraping(self, video_id, video_url):
        """Methode 4: Mobile Website Scraping"""
        requests = install_and_import('requests')
        
        print(f"  📱 Mobile Website Scraping", file=sys.stderr)
        
        session = requests.Session()
        
        # Mobile URLs mit verschiedenen User-Agents
        mobile_configs = [
            {
                'url': f'https://m.youtube.com/watch?v={video_id}',
                'headers': {
                    'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language': 'de-de',
                    'Accept-Encoding': 'gzip, deflate, br'
                }
            },
            {
                'url': f'https://m.youtube.com/watch?v={video_id}&app=m',
                'headers': {
                    'User-Agent': 'Mozilla/5.0 (Linux; Android 13; SM-G998B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    'Accept-Language': 'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3'
                }
            }
        ]
        
        for i, config in enumerate(mobile_configs):
            try:
                print(f"    📱 Teste Mobile-Config {i+1}/2...", file=sys.stderr)
                
                response = session.get(config['url'], headers=config['headers'], timeout=15)
                response.encoding = 'utf-8'
                html = response.text
                
                if len(html) > 10000:
                    print(f"    ✅ Mobile-Seite geladen: {len(html)} Zeichen", file=sys.stderr)
                    
                    # Mobile-spezifische Pattern
                    mobile_patterns = [
                        r'"player_response":\s*"([^"]+)"',
                        r'"playerResponse":\s*({[^}]+captionTracks[^}]+})',
                        r'ytInitialPlayerResponse["\'\s]*[:=]\s*({[^;]+})',
                        r'"captions":({[^}]+captionTracks[^}]+})'
                    ]
                    
                    for pattern in mobile_patterns:
                        matches = re.finditer(pattern, html, re.DOTALL)
                        for match in matches:
                            try:
                                json_str = match.group(1)
                                if json_str.startswith('"') and json_str.endswith('"'):
                                    json_str = json_str[1:-1].replace('\\"', '"')
                                
                                player_data = json.loads(json_str)
                                result = self.extract_from_json(player_data)
                                if result and len(result) > 50:
                                    return result
                                    
                            except json.JSONDecodeError:
                                continue
                
                time.sleep(random.uniform(3, 6))
                
            except Exception as e:
                print(f"    ❌ Mobile-Config {i+1} failed: {str(e)[:50]}", file=sys.stderr)
                continue
        
        raise Exception("Mobile Website: Keine Player-Daten gefunden")
    
    def method_invidious_proxy(self, video_id, video_url):
        """Methode 5: Invidious Proxy-Instances"""
        requests = install_and_import('requests')
        
        print(f"  🔄 Invidious Proxy-Instances", file=sys.stderr)
        
        # Bekannte Invidious-Instanzen
        invidious_instances = [
            'https://invidious.io',
            'https://invidious.snopyta.org',
            'https://invidious.kavin.rocks',
            'https://vid.puffyan.us',
            'https://invidious.namazso.eu'
        ]
        
        session = requests.Session()
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
            'Accept': 'application/json, text/plain, */*'
        })
        
        for instance in invidious_instances:
            try:
                print(f"    🔄 Teste Instance: {instance}", file=sys.stderr)
                
                api_url = f"{instance}/api/v1/videos/{video_id}?fields=captions"
                response = session.get(api_url, timeout=10)
                
                if response.status_code == 200:
                    data = response.json()
                    
                    if 'captions' in data and data['captions']:
                        for caption in data['captions']:
                            if caption.get('languageCode') in ['de', 'en', 'de-DE', 'en-US']:
                                caption_url = caption.get('url')
                                
                                if caption_url:
                                    if not caption_url.startswith('http'):
                                        caption_url = instance + caption_url
                                    
                                    caption_response = session.get(caption_url, timeout=10)
                                    
                                    if caption_response.status_code == 200 and len(caption_response.text) > 100:
                                        return self.parse_xml_format(caption_response.text)
                
                time.sleep(1)
                
            except Exception as e:
                print(f"    ❌ Instance {instance} failed: {str(e)[:50]}", file=sys.stderr)
                continue
        
        raise Exception("Invidious Proxy: Alle Instanzen fehlgeschlagen")
    
    def method_session_stealing_advanced(self, video_id, video_url):
        """Methode 6: Erweiterte Session-Stealing mit Perfect Headers"""
        requests = install_and_import('requests')
        
        print(f"  🔐 Erweiterte Session-Stealing", file=sys.stderr)
        
        session = requests.Session()
        
        # Perfekte Desktop-Browser-Simulation
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language': 'de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none',
            'Sec-Fetch-User': '?1',
            'sec-ch-ua': '"Not_A Brand";v="8", "Chromium";v="139", "Google Chrome";v="139"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Windows"'
        })
        
        # Realistische Cookie-Simulation
        realistic_cookies = {
            'VISITOR_INFO1_LIVE': 'CgtZcWxXeW5Ka0JCdygwQg%3D%3D',
            'YSC': 'dQU2ehBGRpU',
            'PREF': 'f1=50000000&f4=4000000&f5=30000&hl=de&gl=DE&tz=Europe.Berlin',
            'GPS': '1',
            'CONSENT': 'YES+cb.20220419-17-p0.de+FX+917'
        }
        
        session.cookies.update(realistic_cookies)
        
        # Schritt 1: Google besuchen (Referrer etablieren)
        try:
            session.get('https://www.google.com', timeout=10)
            time.sleep(random.uniform(2, 4))
        except:
            pass
        
        # Schritt 2: YouTube Hauptseite
        try:
            session.get('https://www.youtube.com', timeout=10)
            time.sleep(random.uniform(2, 4))
        except:
            pass
        
        # Schritt 3: Video-Seite mit perfekten Headers
        print(f"    🔐 Lade Video-Seite mit perfekter Session...", file=sys.stderr)
        
        response = session.get(video_url, timeout=20)
        response.encoding = 'utf-8'
        html = response.text
        
        if len(html) < 20000:
            raise Exception(f"Video-Seite zu kurz: {len(html)} Zeichen")
        
        print(f"    ✅ Video-Seite geladen: {len(html)} Zeichen", file=sys.stderr)
        
        # Erweiterte Pattern für Caption-Extraktion
        advanced_patterns = [
            r'ytInitialPlayerResponse":\s*({.+?})\s*(?:;|\n|,\s*")',
            r'var\s+ytInitialPlayerResponse\s*=\s*({.+?})\s*;',
            r'"playerResponse":\s*"([^"]+)"',
            r'player_response=({[^&]+})&',
            r'"captions":\s*({[^}]+captionTracks[^}]+})'
        ]
        
        for i, pattern in enumerate(advanced_patterns):
            matches = re.finditer(pattern, html, re.DOTALL)
            
            for match in matches:
                try:
                    json_str = match.group(1)
                    
                    # URL-decode wenn nötig
                    if json_str.startswith('"') and json_str.endswith('"'):
                        import urllib.parse
                        json_str = urllib.parse.unquote(json_str[1:-1])
                    
                    print(f"    🎯 Teste Pattern {i+1}, JSON-Länge: {len(json_str)}", file=sys.stderr)
                    
                    player_data = json.loads(json_str)
                    
                    if 'captions' in str(player_data).lower():
                        result = self.extract_from_json(player_data)
                        if result and len(result) > 50:
                            return result
                            
                except json.JSONDecodeError:
                    continue
                except Exception as e:
                    continue
        
        raise Exception("Advanced Session-Stealing: Keine Player-Daten extrahierbar")
    
    # Hilfsfunktionen
    def format_transcript_data(self, data):
        """Formatiert Transcript-API Daten"""
        text_parts = []
        for item in data:
            if 'text' in item:
                text_parts.append(item['text'])
        return ' '.join(text_parts)
    
    def parse_json3_format(self, content):
        """JSON3-Format parsen"""
        try:
            data = json.loads(content)
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
    
    def parse_xml_format(self, content):
        """XML-Format parsen"""
        try:
            import xml.etree.ElementTree as ET
            root = ET.fromstring(content)
            
            text_parts = []
            for text_elem in root.findall('.//text'):
                if text_elem.text:
                    text_parts.append(text_elem.text)
            
            return ' '.join(text_parts)
        except:
            # RegEx-Fallback
            text_matches = re.findall(r'<text[^>]*>(.*?)</text>', content, re.DOTALL)
            return ' '.join(text_matches)
    
    def extract_from_json(self, data):
        """Captions aus beliebigen JSON-Strukturen extrahieren"""
        def find_captions(obj, path=""):
            if isinstance(obj, dict):
                if 'captionTracks' in obj:
                    tracks = obj['captionTracks']
                    for track in tracks:
                        if 'baseUrl' in track:
                            try:
                                import requests
                                response = requests.get(track['baseUrl'], timeout=10)
                                if response.status_code == 200:
                                    return self.parse_xml_format(response.text)
                            except:
                                pass
                
                for key, value in obj.items():
                    result = find_captions(value, f"{path}.{key}")
                    if result:
                        return result
                        
            elif isinstance(obj, list):
                for i, item in enumerate(obj):
                    result = find_captions(item, f"{path}[{i}]")
                    if result:
                        return result
            
            return None
        
        return find_captions(data)
    
    def clean_transcript(self, transcript):
        """Transcript bereinigen"""
        if not transcript:
            return ''
        
        import html
        transcript = html.unescape(transcript)
        transcript = re.sub(r'\[?\d{1,2}:\d{2}(?::\d{2})?\]?', '', transcript)
        transcript = re.sub(r'\s+', ' ', transcript)
        
        return transcript.strip()

def main():
    if len(sys.argv) != 2:
        print(json.dumps({'success': False, 'error': 'Usage: python script.py <youtube_url>'}))
        sys.exit(1)
    
    video_url = sys.argv[1]
    extractor = YouTubeServerOptimized()
    
    result = extractor.get_transcript(video_url)
    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main()
