#!/usr/bin/env python3
"""
ULTIMATE YouTube Transcript Extractor
Nutzt Browser-Automation und Session-Management
"""

import sys
import json
import re
import time
import random
import tempfile
import os
from urllib.parse import urlparse, parse_qs
import subprocess

def install_and_import(package):
    """Installiert und importiert Pakete dynamisch"""
    import importlib
    try:
        return importlib.import_module(package)
    except ImportError:
        print(f"Installing {package}...", file=sys.stderr)
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])
        return importlib.import_module(package)

class UltimateYouTubeExtractor:
    
    def __init__(self):
        self.session = None
        self.browser_cookies = None
        self.user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/121.0'
        ]
        
        self.methods = [
            ('cookie_yt_dlp', self.method_cookie_yt_dlp),
            ('selenium_extraction', self.method_selenium_extraction),
            ('session_hijacking', self.method_session_hijacking),
            ('api_with_cookies', self.method_api_with_cookies),
            ('proxy_rotation', self.method_proxy_rotation),
            ('mobile_api_spoofing', self.method_mobile_api_spoofing)
        ]
    
    def extract_video_id(self, url):
        """Video-ID aus URL extrahieren"""
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
        """Hauptmethode: Alle Tricks versuchen"""
        video_id = self.extract_video_id(video_url)
        if not video_id:
            return {'success': False, 'error': 'Ungültige YouTube-URL'}
        
        print(f"🎯 ULTIMATE Extraktion für Video: {video_id}", file=sys.stderr)
        
        errors = {}
        
        for method_name, method_func in self.methods:
            try:
                print(f"🚀 ULTIMATE Methode: {method_name}", file=sys.stderr)
                result = method_func(video_id, video_url)
                
                if result and len(result.strip()) > 100:
                    cleaned = self.clean_transcript(result)
                    print(f"🎉 ULTIMATE ERFOLG: {method_name} ({len(cleaned)} Zeichen)", file=sys.stderr)
                    return {
                        'success': True,
                        'transcript': cleaned,
                        'source': f'ULTIMATE_{method_name}',
                        'length': len(cleaned)
                    }
                else:
                    error_msg = f"Transcript zu kurz: {len(result) if result else 0} Zeichen"
                    print(f"⚠️ {method_name}: {error_msg}", file=sys.stderr)
                    errors[method_name] = error_msg
                    
            except Exception as e:
                error_msg = str(e)
                print(f"💥 {method_name} FAILED: {error_msg}", file=sys.stderr)
                errors[method_name] = error_msg
                continue
        
        return {'success': False, 'error': 'ULTIMATE METHODS FAILED', 'details': errors}
    
    def method_cookie_yt_dlp(self, video_id, video_url):
        """Methode 1: yt-dlp mit Browser-Cookies"""
        print(f"  🍪 Cookie-Extraktion mit yt-dlp", file=sys.stderr)
        
        # Versuche verschiedene Browser-Cookie-Quellen
        browser_options = [
            '--cookies-from-browser chrome',
            '--cookies-from-browser firefox',
            '--cookies-from-browser edge',
            '--cookies-from-browser safari',
            '--cookies-from-browser chromium'
        ]
        
        with tempfile.TemporaryDirectory() as temp_dir:
            for browser_option in browser_options:
                try:
                    print(f"  🌐 Versuche Browser: {browser_option}", file=sys.stderr)
                    
                    cmd = [
                        'yt-dlp',
                        '--write-subs',
                        '--write-auto-subs',
                        '--sub-langs', 'de,en,auto',
                        '--skip-download',
                        browser_option,
                        '--user-agent', random.choice(self.user_agents),
                        '--sleep-interval', '1',
                        '--max-sleep-interval', '3',
                        '--output', f'{temp_dir}/%(title)s.%(ext)s',
                        video_url
                    ]
                    
                    result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)
                    
                    if result.returncode == 0:
                        # Suche nach Subtitle-Dateien
                        for file in os.listdir(temp_dir):
                            if file.endswith(('.vtt', '.srt')):
                                subtitle_path = os.path.join(temp_dir, file)
                                with open(subtitle_path, 'r', encoding='utf-8') as f:
                                    content = f.read()
                                
                                if file.endswith('.vtt'):
                                    return self.parse_vtt_format(content)
                                else:
                                    return self.parse_srt_format(content)
                    else:
                        print(f"  ❌ Browser {browser_option}: {result.stderr[:200]}", file=sys.stderr)
                        
                except subprocess.TimeoutExpired:
                    print(f"  ⏱️ Browser {browser_option}: Timeout", file=sys.stderr)
                except Exception as e:
                    print(f"  💥 Browser {browser_option}: {str(e)}", file=sys.stderr)
                    continue
        
        raise Exception("Cookie-yt-dlp: Alle Browser fehlgeschlagen")
    
    def method_selenium_extraction(self, video_id, video_url):
        """Methode 2: Selenium Browser-Automation"""
        try:
            selenium = install_and_import('selenium')
            from selenium.webdriver import Chrome, ChromeOptions
            from selenium.webdriver.common.by import By
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.webdriver.support import expected_conditions as EC
            
            print(f"  🤖 Selenium Browser-Automation", file=sys.stderr)
            
            # Chrome-Optionen für Anti-Detection
            options = ChromeOptions()
            options.add_argument('--headless')
            options.add_argument('--no-sandbox')
            options.add_argument('--disable-dev-shm-usage')
            options.add_argument('--disable-blink-features=AutomationControlled')
            options.add_experimental_option("excludeSwitches", ["enable-automation"])
            options.add_experimental_option('useAutomationExtension', False)
            options.add_argument(f'--user-agent={random.choice(self.user_agents)}')
            
            driver = Chrome(options=options)
            
            try:
                # Anti-Detection JavaScript
                driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
                
                print(f"  📺 Lade YouTube-Seite...", file=sys.stderr)
                driver.get(video_url)
                
                # Warte auf Seiten-Load
                WebDriverWait(driver, 10).until(
                    EC.presence_of_element_located((By.TAG_NAME, "body"))
                )
                
                # Suche nach Caption-Button und aktiviere ihn
                try:
                    caption_button = WebDriverWait(driver, 5).until(
                        EC.element_to_be_clickable((By.CSS_SELECTOR, "button[aria-label*='Untertitel']"))
                    )
                    caption_button.click()
                    time.sleep(2)
                    print(f"  📋 Untertitel aktiviert", file=sys.stderr)
                except:
                    print(f"  ⚠️ Caption-Button nicht gefunden", file=sys.stderr)
                
                # JavaScript-Zugriff auf YouTube-Player-Daten
                script = """
                var playerResponse = null;
                
                // Suche in verschiedenen globalen Variablen
                if (window.ytInitialPlayerResponse) {
                    playerResponse = window.ytInitialPlayerResponse;
                } else if (window.ytplayer && window.ytplayer.config) {
                    playerResponse = window.ytplayer.config.args.player_response;
                    if (typeof playerResponse === 'string') {
                        playerResponse = JSON.parse(playerResponse);
                    }
                }
                
                if (playerResponse && playerResponse.captions) {
                    var tracks = playerResponse.captions.playerCaptionsTracklistRenderer.captionTracks;
                    if (tracks && tracks.length > 0) {
                        return tracks[0].baseUrl;
                    }
                }
                
                return null;
                """
                
                caption_url = driver.execute_script(script)
                
                if caption_url:
                    print(f"  🎯 Caption-URL gefunden via Selenium", file=sys.stderr)
                    
                    # Lade Caption-Content mit den Browser-Cookies
                    driver.get(caption_url)
                    time.sleep(2)
                    
                    # XML-Content aus dem Browser-Body extrahieren
                    body_text = driver.find_element(By.TAG_NAME, "body").text
                    
                    if body_text and len(body_text) > 100:
                        return self.parse_xml_format(body_text)
                
                raise Exception("Keine Caption-URL via Selenium gefunden")
                
            finally:
                driver.quit()
                
        except Exception as e:
            raise Exception(f"Selenium-Fehler: {str(e)}")
    
    def method_session_hijacking(self, video_id, video_url):
        """Methode 3: Session-Hijacking mit Requests"""
        requests = install_and_import('requests')
        
        print(f"  🔐 Session-Hijacking Methode", file=sys.stderr)
        
        session = requests.Session()
        
        # Anti-Detection Headers
        session.headers.update({
            'User-Agent': random.choice(self.user_agents),
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language': 'de,en-US;q=0.7,en;q=0.3',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none',
            'Cache-Control': 'max-age=0'
        })
        
        # Schritt 1: YouTube-Hauptseite besuchen für Cookies
        print(f"  🍪 Sammle YouTube-Cookies...", file=sys.stderr)
        session.get('https://www.youtube.com')
        time.sleep(random.uniform(1, 3))
        
        # Schritt 2: Video-Seite laden
        print(f"  📺 Lade Video-Seite...", file=sys.stderr)
        response = session.get(video_url)
        
        if response.status_code != 200:
            raise Exception(f"Video-Seite nicht erreichbar: {response.status_code}")
        
        html = response.text
        
        # Schritt 3: ytInitialPlayerResponse extrahieren
        player_response_match = re.search(r'var ytInitialPlayerResponse = ({.*?});', html)
        if not player_response_match:
            player_response_match = re.search(r'"ytInitialPlayerResponse":({.*?}),"ytInitialData"', html)
        
        if player_response_match:
            try:
                player_data = json.loads(player_response_match.group(1))
                
                if 'captions' in player_data:
                    tracks = player_data['captions']['playerCaptionsTracklistRenderer']['captionTracks']
                    
                    for track in tracks:
                        if 'baseUrl' in track:
                            lang = track.get('languageCode', 'unknown')
                            print(f"  📋 Versuche Caption: {lang}", file=sys.stderr)
                            
                            # Caption mit Session-Cookies laden
                            caption_response = session.get(track['baseUrl'])
                            
                            if caption_response.status_code == 200 and caption_response.content:
                                return self.parse_xml_format(caption_response.text)
            except json.JSONDecodeError:
                pass
        
        raise Exception("Session-Hijacking: Keine Captions gefunden")
    
    def method_api_with_cookies(self, video_id, video_url):
        """Methode 4: Direkte API mit gestohlenen Cookies"""
        requests = install_and_import('requests')
        
        print(f"  🎭 API mit Cookie-Spoofing", file=sys.stderr)
        
        # Fake-Cookies die YouTube erwarten könnte
        fake_cookies = {
            'VISITOR_INFO1_LIVE': 'abcdefghijk',
            'YSC': 'random_session_' + str(random.randint(100000, 999999)),
            'PREF': 'f4=4000000&tz=Europe.Berlin',
            'GPS': '1',
            'CONSENT': 'YES+cb.20210328-17-p0.en+FX+123'
        }
        
        session = requests.Session()
        session.cookies.update(fake_cookies)
        
        # Verschiedene API-Endpoints mit Cookies
        api_endpoints = [
            f"https://www.youtube.com/api/timedtext?v={video_id}&lang=de&fmt=json3",
            f"https://www.youtube.com/api/timedtext?v={video_id}&lang=en&fmt=json3",
            f"https://youtubei.googleapis.com/youtubei/v1/get_transcript?videoId={video_id}",
            f"https://www.youtube.com/youtubei/v1/player?videoId={video_id}"
        ]
        
        for endpoint in api_endpoints:
            try:
                headers = {
                    'User-Agent': random.choice(self.user_agents),
                    'Referer': video_url,
                    'Origin': 'https://www.youtube.com',
                    'X-YouTube-Client-Name': '1',
                    'X-YouTube-Client-Version': '2.20240101.00.00'
                }
                
                response = session.get(endpoint, headers=headers)
                
                if response.status_code == 200 and len(response.content) > 100:
                    if 'json3' in endpoint:
                        return self.parse_json3_format(response.text)
                    else:
                        # Versuche als JSON zu parsen
                        try:
                            data = response.json()
                            if 'captions' in str(data):
                                # Extrahiere Captions aus der API-Response
                                return self.extract_captions_from_api_response(data)
                        except:
                            pass
                        
                        return self.parse_xml_format(response.text)
                        
            except Exception as e:
                print(f"  ❌ API-Endpoint {endpoint}: {str(e)}", file=sys.stderr)
                continue
        
        raise Exception("API mit Cookies: Alle Endpoints fehlgeschlagen")
    
    def method_proxy_rotation(self, video_id, video_url):
        """Methode 5: Proxy-Rotation für IP-Wechsel"""
        requests = install_and_import('requests')
        
        print(f"  🔄 Proxy-Rotation Methode", file=sys.stderr)
        
        # Freie Proxy-Listen (In Produktiv würden echte Proxies verwendet)
        # Hier nur als Demonstration
        proxy_list = [
            # {'http': 'http://proxy1:port', 'https': 'https://proxy1:port'},
            # {'http': 'http://proxy2:port', 'https': 'https://proxy2:port'},
        ]
        
        # Ohne echte Proxies, nutze verschiedene Header-Kombinationen
        header_combinations = [
            {
                'User-Agent': 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'X-Forwarded-For': f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"
            },
            {
                'User-Agent': 'Mozilla/5.0 (Android 10; Mobile; rv:109.0) Gecko/111.0 Firefox/109.0',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'X-Real-IP': f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"
            }
        ]
        
        for i, headers in enumerate(header_combinations):
            try:
                print(f"  🎭 Header-Kombination {i+1}", file=sys.stderr)
                
                session = requests.Session()
                session.headers.update(headers)
                
                # Mobile YouTube verwenden
                mobile_url = video_url.replace('www.youtube.com', 'm.youtube.com')
                response = session.get(mobile_url)
                
                if response.status_code == 200:
                    html = response.text
                    
                    # Mobile-spezifische Pattern
                    patterns = [
                        r'"captionTracks":\[(.*?)\]',
                        r'"captions".*?"captionTracks":\[(.*?)\]'
                    ]
                    
                    for pattern in patterns:
                        match = re.search(pattern, html)
                        if match:
                            caption_data = '[' + match.group(1) + ']'
                            captions = json.loads(caption_data)
                            
                            for caption in captions:
                                if 'baseUrl' in caption:
                                    caption_response = session.get(caption['baseUrl'])
                                    if caption_response.status_code == 200:
                                        return self.parse_xml_format(caption_response.text)
                                        
            except Exception as e:
                print(f"  ❌ Header-Kombination {i+1}: {str(e)}", file=sys.stderr)
                continue
        
        raise Exception("Proxy-Rotation: Alle Kombinationen fehlgeschlagen")
    
    def method_mobile_api_spoofing(self, video_id, video_url):
        """Methode 6: Mobile-App API Spoofing"""
        requests = install_and_import('requests')
        
        print(f"  📱 Mobile-App API Spoofing", file=sys.stderr)
        
        # YouTube Mobile App API
        api_url = "https://youtubei.googleapis.com/youtubei/v1/player"
        
        # Mobile App Headers
        headers = {
            'User-Agent': 'com.google.android.youtube/17.36.4 (Linux; U; Android 11) gzip',
            'X-YouTube-Client-Name': '3',
            'X-YouTube-Client-Version': '17.36.4',
            'Content-Type': 'application/json'
        }
        
        # Mobile App Payload
        payload = {
            "context": {
                "client": {
                    "clientName": "ANDROID",
                    "clientVersion": "17.36.4",
                    "androidSdkVersion": 30,
                    "userAgent": "com.google.android.youtube/17.36.4 (Linux; U; Android 11) gzip"
                }
            },
            "videoId": video_id
        }
        
        try:
            response = requests.post(api_url, json=payload, headers=headers, timeout=15)
            
            if response.status_code == 200:
                data = response.json()
                
                # Suche nach Captions in der Mobile-API-Response
                if 'captions' in data:
                    captions_data = data['captions']
                    if 'playerCaptionsTracklistRenderer' in captions_data:
                        tracks = captions_data['playerCaptionsTracklistRenderer']['captionTracks']
                        
                        for track in tracks:
                            if 'baseUrl' in track:
                                caption_response = requests.get(track['baseUrl'], headers={'User-Agent': headers['User-Agent']})
                                if caption_response.status_code == 200:
                                    return self.parse_xml_format(caption_response.text)
                
        except Exception as e:
            pass
        
        raise Exception("Mobile-API-Spoofing fehlgeschlagen")
    
    # Parsing-Funktionen
    def parse_vtt_format(self, content):
        """VTT-Format parsen"""
        lines = content.split('\n')
        text_parts = []
        
        for line in lines:
            line = line.strip()
            if line and '-->' not in line and not line.startswith('WEBVTT'):
                text_parts.append(line)
        
        return ' '.join(text_parts)
    
    def parse_srt_format(self, content):
        """SRT-Format parsen"""
        lines = content.split('\n')
        text_parts = []
        
        for line in lines:
            line = line.strip()
            if line and not line.isdigit() and '-->' not in line:
                text_parts.append(line)
        
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
    
    def extract_captions_from_api_response(self, api_data):
        """Captions aus API-Response extrahieren"""
        # Rekursive Suche nach Caption-Daten
        def find_captions(obj):
            if isinstance(obj, dict):
                if 'captionTracks' in obj:
                    return obj['captionTracks']
                for value in obj.values():
                    result = find_captions(value)
                    if result:
                        return result
            elif isinstance(obj, list):
                for item in obj:
                    result = find_captions(item)
                    if result:
                        return result
            return None
        
        caption_tracks = find_captions(api_data)
        if caption_tracks:
            for track in caption_tracks:
                if 'baseUrl' in track:
                    # Lade Caption-Content
                    import requests
                    response = requests.get(track['baseUrl'])
                    if response.status_code == 200:
                        return self.parse_xml_format(response.text)
        
        return ""
    
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
    extractor = UltimateYouTubeExtractor()
    
    result = extractor.get_transcript(video_url)
    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main()
