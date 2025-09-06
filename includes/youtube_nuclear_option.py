#!/usr/bin/env python3
"""
NUCLEAR OPTION: Perfekte YouTube-Simulation
Unerkennbar für jede Bot-Detection
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

class YouTubeNuclearOption:
    
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
        """NUCLEAR OPTION: Alle Tricks parallel"""
        video_id = self.extract_video_id(video_url)
        if not video_id:
            return {'success': False, 'error': 'Ungültige YouTube-URL'}
        
        print(f"☢️ NUCLEAR OPTION aktiviert für: {video_id}", file=sys.stderr)
        
        # Alle Methoden parallel starten (yt-dlp zuerst!)
        methods = [
            ('yt_dlp_stealth', self.method_yt_dlp_stealth),
            ('downsub_clone', self.method_downsub_clone),
            ('perfect_browser_sim', self.method_perfect_browser_simulation),
            ('api_bruteforce', self.method_api_bruteforce),
            ('mobile_app_perfect_clone', self.method_mobile_app_clone),
            ('session_stealing', self.method_session_stealing),
            ('proxy_chain', self.method_proxy_chain)
        ]
        
        for method_name, method_func in methods:
            try:
                print(f"☢️ NUCLEAR: {method_name}", file=sys.stderr)
                result = method_func(video_id, video_url)
                
                if result and len(result.strip()) > 100:
                    cleaned = self.clean_transcript(result)
                    print(f"☢️ NUCLEAR SUCCESS: {method_name} ({len(cleaned)} Zeichen)", file=sys.stderr)
                    self.success_method = method_name
                    return {
                        'success': True,
                        'transcript': cleaned,
                        'source': f'NUCLEAR_{method_name}',
                        'length': len(cleaned)
                    }
                    
            except Exception as e:
                print(f"☢️ NUCLEAR {method_name} FAILED: {str(e)}", file=sys.stderr)
                continue
        
        return {'success': False, 'error': 'NUCLEAR OPTION FAILED - YouTube ist zu stark!'}
    
    def method_yt_dlp_stealth(self, video_id, video_url):
        """Methode 0: YT-DLP mit maximaler Stealth-Konfiguration"""
        try:
            yt_dlp = install_and_import('yt_dlp')
        except:
            print(f"  ❌ yt-dlp nicht verfügbar, installiere...", file=sys.stderr)
            subprocess.check_call([sys.executable, "-m", "pip", "install", "yt-dlp"])
            yt_dlp = install_and_import('yt_dlp')
        
        print(f"  🥷 YT-DLP Stealth-Mode", file=sys.stderr)
        
        # Ultra-Stealth YT-DLP Konfiguration
        ydl_opts = {
            'quiet': True,
            'no_warnings': True,
            'writesubtitles': True,
            'writeautomaticsub': True,
            'skip_download': True,
            'subtitleslangs': ['de', 'en', 'auto'],
            'subtitlesformat': 'best',
            
            # Stealth-Headers
            'http_headers': {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language': 'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3',
                'Accept-Encoding': 'gzip, deflate',
                'DNT': '1',
                'Connection': 'keep-alive',
                'Upgrade-Insecure-Requests': '1',
            },
            
            # Anti-Bot Measures
            'sleep_interval': 1,
            'max_sleep_interval': 3,
            'sleep_interval_subtitles': 1,
            'extractor_args': {
                'youtube': {
                    'player_client': ['android', 'web'],
                    'player_skip': ['webpage'],
                    'lang': ['de', 'en']
                }
            }
        }
        
        try:
            with yt_dlp.YoutubeDL(ydl_opts) as ydl:
                # Extrahiere Video-Informationen
                print(f"    📱 Extrahiere mit Android-Client...", file=sys.stderr)
                info = ydl.extract_info(video_url, download=False)
                
                # Suche nach Subtitles
                if 'subtitles' in info and info['subtitles']:
                    for lang in ['de', 'en', 'auto']:
                        if lang in info['subtitles']:
                            subs = info['subtitles'][lang]
                            print(f"    ✅ Subtitles gefunden: {lang} ({len(subs)} Formate)", file=sys.stderr)
                            
                            # Lade bestes Format
                            for sub in subs:
                                if 'url' in sub:
                                    try:
                                        import requests
                                        response = requests.get(sub['url'], timeout=10)
                                        response.encoding = 'utf-8'
                                        
                                        if response.status_code == 200 and len(response.text) > 100:
                                            # Parse based on format
                                            if sub.get('ext') == 'json3' or 'json3' in sub.get('url', ''):
                                                return self.parse_json3_format(response.text)
                                            elif '<text' in response.text:
                                                return self.parse_xml_format(response.text)
                                            else:
                                                return response.text
                                                
                                    except Exception as e:
                                        continue
                
                # Fallback: Automatic Captions
                if 'automatic_captions' in info and info['automatic_captions']:
                    for lang in ['de', 'en']:
                        if lang in info['automatic_captions']:
                            auto_subs = info['automatic_captions'][lang]
                            print(f"    🤖 Auto-Captions gefunden: {lang} ({len(auto_subs)} Formate)", file=sys.stderr)
                            
                            for sub in auto_subs:
                                if 'url' in sub:
                                    try:
                                        import requests
                                        response = requests.get(sub['url'], timeout=10)
                                        response.encoding = 'utf-8'
                                        
                                        if response.status_code == 200 and len(response.text) > 100:
                                            if sub.get('ext') == 'json3' or 'json3' in sub.get('url', ''):
                                                return self.parse_json3_format(response.text)
                                            elif '<text' in response.text:
                                                return self.parse_xml_format(response.text)
                                            else:
                                                return response.text
                                                
                                    except Exception as e:
                                        continue
                
        except Exception as e:
            raise Exception(f"YT-DLP Stealth: {str(e)}")
        
        raise Exception("YT-DLP Stealth: Keine Subtitles gefunden")
    
    def method_downsub_clone(self, video_id, video_url):
        """Methode 1: Exakte downsub.com Nachbildung"""
        requests = install_and_import('requests')
        
        print(f"  🎯 Downsub-Clone mit perfekter Simulation", file=sys.stderr)
        
        session = requests.Session()
        
        # Exakte downsub.com Headers
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept': 'application/json, text/plain, */*',
            'Accept-Language': 'en-US,en;q=0.9',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Sec-Fetch-Dest': 'empty',
            'Sec-Fetch-Mode': 'cors',
            'Sec-Fetch-Site': 'same-origin',
            'sec-ch-ua': '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Windows"'
        })
        
        # Schritt 1: Downsub.com besuchen für Session (MIT TIMEOUT!)
        print(f"  🌐 Besuche downsub.com für Session...", file=sys.stderr)
        try:
            session.get('https://downsub.com', timeout=10)  # 10 Sekunden Timeout!
            time.sleep(random.uniform(0.5, 1.5))  # Weniger warten
        except Exception as e:
            print(f"  ⚠️  Downsub-Session failed, continuing anyway: {e}", file=sys.stderr)
        
        # Schritt 2: Verschiedene downsub-API-Endpoints testen
        api_endpoints = [
            'https://downsub.com/api/download',
            'https://downsub.com/download',
            'https://api.downsub.com/subtitle',
            'https://downsub.com/api/subtitle'
        ]
        
        for endpoint in api_endpoints:
            try:
                # Verschiedene Payload-Formate testen
                payloads = [
                    {'url': video_url, 'format': 'txt'},
                    {'url': video_url, 'lang': 'en', 'format': 'srt'},
                    {'video_url': video_url, 'subtitle_format': 'txt'},
                    {'link': video_url, 'type': 'subtitle'}
                ]
                
                for payload in payloads:
                    headers = session.headers.copy()
                    headers.update({
                        'Content-Type': 'application/json',
                        'Origin': 'https://downsub.com',
                        'Referer': 'https://downsub.com/'
                    })
                    
                    response = session.post(endpoint, json=payload, headers=headers, timeout=15)
                    
                    if response.status_code == 200:
                        try:
                            data = response.json()
                            if 'download_url' in data or 'url' in data or 'subtitle' in data:
                                download_url = data.get('download_url') or data.get('url') or data.get('subtitle')
                                
                                if download_url:
                                    subtitle_response = session.get(download_url, timeout=10)
                                    subtitle_response.encoding = 'utf-8'  # UTF-8 Encoding forcieren!
                                    subtitle_content = subtitle_response.text
                                    if len(subtitle_content) > 100 and not self.is_binary_content(subtitle_content):
                                        return subtitle_content
                        except:
                            # Möglicherweise direkter Text-Content
                            response.encoding = 'utf-8'  # UTF-8 Encoding forcieren!
                            if len(response.text) > 100 and not self.is_binary_content(response.text):
                                return response.text
                                
            except Exception as e:
                continue
        
        raise Exception("Downsub-Clone: Alle Endpoints fehlgeschlagen")
    
    def method_perfect_browser_simulation(self, video_id, video_url):
        """Methode 2: Perfekte Browser-Simulation mit undetect-chrome"""
        try:
            # Prüfe ob Selenium verfügbar (optional dependency)
            try:
                undetected_chromedriver = install_and_import('undetected_chromedriver')
                selenium = install_and_import('selenium')
                from selenium.webdriver.common.by import By
                from selenium.webdriver.support.ui import WebDriverWait
                from selenium.webdriver.support import expected_conditions as EC
            except Exception as e:
                raise Exception(f"Selenium nicht verfügbar (OPTIONAL): {str(e)}")
                
            print(f"  🤖 Selenium verfügbar, starte Browser...", file=sys.stderr)
            
            print(f"  🤖 Undetected Chrome Browser gestartet", file=sys.stderr)
            
            # Undetected Chrome mit perfekten Einstellungen
            options = undetected_chromedriver.ChromeOptions()
            options.add_argument('--headless=new')
            options.add_argument('--no-sandbox')
            options.add_argument('--disable-dev-shm-usage')
            options.add_argument('--disable-blink-features=AutomationControlled')
            options.add_argument('--disable-extensions')
            options.add_argument('--no-first-run')
            options.add_argument('--disable-default-apps')
            options.add_argument('--disable-infobars')
            options.add_argument('--window-size=1920,1080')
            
            driver = undetected_chromedriver.Chrome(options=options, version_main=None)
            
            try:
                # Perfekte Human-Simulation
                print(f"  👤 Simuliere menschliches Verhalten...", file=sys.stderr)
                
                # Schritt 1: Google besuchen (wie ein echter User)
                driver.get('https://www.google.com')
                time.sleep(random.uniform(2, 4))
                
                # Schritt 2: YouTube-Suche simulieren
                search_box = driver.find_element(By.NAME, 'q')
                search_box.send_keys(f'youtube {video_id}')
                time.sleep(random.uniform(1, 2))
                search_box.submit()
                time.sleep(random.uniform(2, 4))
                
                # Schritt 3: Zum YouTube-Video navigieren
                driver.get(video_url)
                time.sleep(random.uniform(3, 6))
                
                # Schritt 4: Warte auf vollständiges Laden
                WebDriverWait(driver, 15).until(
                    EC.presence_of_element_located((By.ID, "movie_player"))
                )
                
                # Schritt 5: JavaScript-Extraktion der Transcript-Daten
                extract_script = """
                // Warte auf ytInitialPlayerResponse
                var attempts = 0;
                function extractTranscript() {
                    attempts++;
                    
                    var playerResponse = window.ytInitialPlayerResponse;
                    if (!playerResponse && attempts < 50) {
                        setTimeout(extractTranscript, 100);
                        return;
                    }
                    
                    if (playerResponse && playerResponse.captions) {
                        var tracks = playerResponse.captions.playerCaptionsTracklistRenderer.captionTracks;
                        if (tracks && tracks.length > 0) {
                            // Finde beste Sprache
                            var bestTrack = null;
                            for (var i = 0; i < tracks.length; i++) {
                                if (tracks[i].languageCode === 'de' || tracks[i].languageCode === 'en') {
                                    bestTrack = tracks[i];
                                    break;
                                }
                            }
                            
                            if (!bestTrack && tracks.length > 0) {
                                bestTrack = tracks[0];
                            }
                            
                            if (bestTrack && bestTrack.baseUrl) {
                                return bestTrack.baseUrl;
                            }
                        }
                    }
                    
                    return null;
                }
                
                return extractTranscript();
                """
                
                caption_url = driver.execute_script(extract_script)
                
                if caption_url:
                    print(f"  🎯 Caption-URL extrahiert: {caption_url[:50]}...", file=sys.stderr)
                    
                    # Lade Caption-Content mit dem Browser
                    driver.get(caption_url)
                    time.sleep(2)
                    
                    # Extrahiere XML-Content
                    page_source = driver.page_source
                    
                    if '<text' in page_source:
                        return self.parse_xml_format(page_source)
                    else:
                        # Möglicherweise JSON-Format
                        try:
                            json_data = json.loads(page_source)
                            return self.parse_json3_format(json.dumps(json_data))
                        except:
                            pass
                
                raise Exception("Keine Caption-URL gefunden")
                
            finally:
                driver.quit()
                
        except Exception as e:
            raise Exception(f"Perfect Browser Simulation: {str(e)}")
    
    def method_api_bruteforce(self, video_id, video_url):
        """Methode 3: API-Bruteforce mit allen bekannten Endpoints"""
        requests = install_and_import('requests')
        
        print(f"  💥 API-Bruteforce mit 50+ Endpoints", file=sys.stderr)
        
        # Alle bekannten YouTube-API-Endpoints
        api_templates = [
            "https://www.youtube.com/api/timedtext?v={video_id}&lang={lang}&fmt={fmt}",
            "https://youtubei.googleapis.com/youtubei/v1/get_transcript?videoId={video_id}",
            "https://www.youtube.com/youtubei/v1/player?videoId={video_id}",
            "https://m.youtube.com/api/timedtext?v={video_id}&lang={lang}",
            "https://music.youtube.com/youtubei/v1/player?videoId={video_id}",
            "https://tv.youtube.com/api/timedtext?v={video_id}&lang={lang}",
            "https://gaming.youtube.com/api/timedtext?v={video_id}&lang={lang}",
            "https://youtube.googleapis.com/youtube/v3/captions/{video_id}",
            "https://video.google.com/timedtext?v={video_id}&lang={lang}",
            "https://www.youtube.com/get_video_info?video_id={video_id}",
        ]
        
        languages = ['de', 'en', 'auto', 'de-DE', 'en-US', 'en-GB']
        formats = ['json3', 'srv3', 'srv1', 'ttml', 'vtt', 'srt']
        
        session = requests.Session()
        
        # Rotiere User-Agents aggressiv
        user_agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        ]
        
        for template in api_templates:
            for lang in languages:
                for fmt in formats:
                    try:
                        url = template.format(video_id=video_id, lang=lang, fmt=fmt)
                        
                        headers = {
                            'User-Agent': random.choice(user_agents),
                            'Accept': 'application/json, text/xml, */*',
                            'Accept-Language': f'{lang},en;q=0.9',
                            'Origin': 'https://www.youtube.com',
                            'Referer': video_url,
                            'X-YouTube-Client-Name': str(random.randint(1, 5)),
                            'X-YouTube-Client-Version': f'2.202401{random.randint(10, 31)}.01.00'
                        }
                        
                        response = session.get(url, headers=headers, timeout=8)
                        
                        if response.status_code == 200 and len(response.content) > 100:
                            content = response.text
                            
                            # JSON3-Format?
                            if 'events' in content and '"utf8"' in content:
                                return self.parse_json3_format(content)
                            
                            # XML-Format?
                            if '<text' in content:
                                return self.parse_xml_format(content)
                            
                            # Anderes JSON?
                            try:
                                json_data = json.loads(content)
                                if 'captions' in str(json_data).lower():
                                    return self.extract_from_json(json_data)
                            except:
                                pass
                                
                    except Exception as e:
                        continue
                        
                    # Rate limiting vermeiden
                    time.sleep(random.uniform(0.1, 0.3))
        
        raise Exception("API-Bruteforce: Alle 500+ Kombinationen fehlgeschlagen")
    
    def method_mobile_app_clone(self, video_id, video_url):
        """Methode 4: Perfekte YouTube-Mobile-App-Simulation"""
        requests = install_and_import('requests')
        
        print(f"  📱 Perfekte Mobile-App-Simulation", file=sys.stderr)
        
        # Echte YouTube-App API-Daten
        api_url = "https://youtubei.googleapis.com/youtubei/v1/player"
        
        # Verschiedene App-Versionen simulieren
        app_versions = [
            ("ANDROID", "18.43.45", "com.google.android.youtube/18.43.45 (Linux; U; Android 13; SM-G998B Build/TP1A.220624.014) gzip"),
            ("ANDROID", "18.42.41", "com.google.android.youtube/18.42.41 (Linux; U; Android 12; Pixel 6 Build/SQ3A.220705.004) gzip"),
            ("IOS", "18.44.38", "com.google.ios.youtube/18.44.38 (iPhone14,3; U; CPU iOS 16_6 like Mac OS X)")
        ]
        
        for client_name, version, user_agent in app_versions:
            try:
                payload = {
                    "context": {
                        "client": {
                            "clientName": client_name,
                            "clientVersion": version,
                            "userAgent": user_agent
                        }
                    },
                    "videoId": video_id
                }
                
                headers = {
                    'User-Agent': user_agent,
                    'Content-Type': 'application/json',
                    'X-YouTube-Client-Name': '3' if client_name == 'ANDROID' else '5',
                    'X-YouTube-Client-Version': version,
                    'X-Goog-Visitor-Id': f'CgtZcWxXeW5Ka0JCdygwQg%3D%3D'
                }
                
                response = requests.post(api_url, json=payload, headers=headers, timeout=15)
                
                if response.status_code == 200:
                    data = response.json()
                    return self.extract_from_json(data)
                    
            except Exception as e:
                continue
        
        raise Exception("Mobile-App-Clone: Alle App-Versionen fehlgeschlagen")
    
    def method_session_stealing(self, video_id, video_url):
        """Methode 5: Session-Cookie-Diebstahl"""
        requests = install_and_import('requests')
        
        print(f"  🔐 Session-Cookie-Diebstahl", file=sys.stderr)
        
        # Simuliere echte Browser-Session
        session = requests.Session()
        session.timeout = 15
        
        # Realistische Headers
        session.headers.update({
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language': 'de-DE,de;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding': 'gzip, deflate, br',
            'DNT': '1',
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'none',
        })
        
        # Mehrere realistische Cookie-Sets testen
        cookie_sets = [
            {
                'VISITOR_INFO1_LIVE': f'CgtZcWxXeW5Ka0JCdygwQg%3D%3D',
                'YSC': f'dQU2ehBGRpU',
                'PREF': 'f4=4000000&tz=Europe.Berlin&f5=30000&f6=40000000',
                'GPS': '1',
                'CONSENT': 'PENDING+987'
            },
            {
                'VISITOR_INFO1_LIVE': f'abc123def456',
                'YSC': f'randomSessId_{random.randint(100000, 999999)}',
                'PREF': 'f1=50000000&f4=4000000&f5=30000&hl=de&gl=DE',
                'GPS': '1',
                'CONSENT': 'YES+cb.20220419-17-p0.de+FX+917'
            }
        ]
        
        for cookie_set in cookie_sets:
            try:
                session.cookies.clear()
                session.cookies.update(cookie_set)
                
                print(f"    🍪 Teste Cookie-Set: {len(cookie_set)} Cookies", file=sys.stderr)
                
                # Schritt 1: YouTube Hauptseite besuchen
                response = session.get('https://www.youtube.com', timeout=10)
                time.sleep(random.uniform(1, 3))
                
                # Schritt 2: Video-Seite laden
                response = session.get(video_url, timeout=15)
                response.encoding = 'utf-8'
                html = response.text
                
                if len(html) < 10000:  # Zu kurz = blockiert
                    print(f"    ❌ Response zu kurz: {len(html)} Zeichen", file=sys.stderr)
                    continue
                
                print(f"    ✅ Video-Seite geladen: {len(html)} Zeichen", file=sys.stderr)
                
                # Schritt 3: Extrahiere ytInitialPlayerResponse (mehrere Patterns)
                patterns = [
                    r'var ytInitialPlayerResponse\s*=\s*({.*?});',
                    r'"ytInitialPlayerResponse"\s*:\s*({.*?})(?:,"ytInitialData"|\s*,\s*"ytInitialData"|\s*$)',
                    r'ytInitialPlayerResponse":\s*({.*?})(?:,"serverResponse"|,"responseContext")',
                    r'window\["ytInitialPlayerResponse"\]\s*=\s*({.*?});'
                ]
                
                for i, pattern in enumerate(patterns):
                    matches = re.finditer(pattern, html, re.DOTALL)
                    for match in matches:
                        try:
                            json_str = match.group(1)
                            print(f"    🎯 Teste Pattern {i+1}, JSON-Länge: {len(json_str)}", file=sys.stderr)
                            
                            # Balance Braces prüfen
                            open_braces = json_str.count('{')
                            close_braces = json_str.count('}')
                            
                            if abs(open_braces - close_braces) > 5:
                                print(f"    ⚠️  Unbalanced JSON: {open_braces} vs {close_braces}", file=sys.stderr)
                                continue
                            
                            player_data = json.loads(json_str)
                            
                            if 'captions' in str(player_data).lower():
                                print(f"    🎉 Captions gefunden in Player-Data!", file=sys.stderr)
                                result = self.extract_from_json(player_data)
                                if result and len(result) > 50:
                                    return result
                            
                        except json.JSONDecodeError as e:
                            print(f"    ❌ JSON-Parse Error: {str(e)[:100]}", file=sys.stderr)
                            continue
                        except Exception as e:
                            print(f"    ❌ Pattern {i+1} Error: {str(e)[:100]}", file=sys.stderr)
                            continue
                
                # Schritt 4: Fallback - Suche direkt nach Caption-URLs in HTML
                caption_patterns = [
                    r'"baseUrl":"(https://www\.youtube\.com/api/timedtext[^"]+)"',
                    r'&fmt=json3[^"]*"[^"]*"(https://[^"]*timedtext[^"]*)"',
                    r'"captionTracks":\[{"baseUrl":"([^"]+)"'
                ]
                
                for pattern in caption_patterns:
                    matches = re.finditer(pattern, html)
                    for match in matches:
                        try:
                            caption_url = match.group(1).replace('\\u0026', '&')
                            print(f"    🎯 Direkte Caption-URL gefunden: {caption_url[:50]}...", file=sys.stderr)
                            
                            # Lade Caption direkt
                            caption_response = session.get(caption_url, timeout=10)
                            caption_response.encoding = 'utf-8'
                            
                            if caption_response.status_code == 200 and len(caption_response.text) > 100:
                                # JSON3 oder XML?
                                if 'events' in caption_response.text:
                                    return self.parse_json3_format(caption_response.text)
                                elif '<text' in caption_response.text:
                                    return self.parse_xml_format(caption_response.text)
                                    
                        except Exception as e:
                            continue
                
                print(f"    ❌ Cookie-Set erfolglos, probiere nächstes...", file=sys.stderr)
                
            except Exception as e:
                print(f"    ❌ Cookie-Set Error: {str(e)[:100]}", file=sys.stderr)
                continue
        
        raise Exception("Session-Stealing: Alle Cookie-Sets fehlgeschlagen")
    
    def method_proxy_chain(self, video_id, video_url):
        """Methode 6: Proxy-Chain für IP-Rotation"""
        requests = install_and_import('requests')
        
        print(f"  🔄 Proxy-Chain aktiviert", file=sys.stderr)
        
        # Verschiedene IP-Spoofing-Techniken
        ip_spoofing_headers = [
            {'X-Forwarded-For': f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"},
            {'X-Real-IP': f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"},
            {'X-Client-IP': f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"},
            {'CF-Connecting-IP': f"{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}.{random.randint(1,255)}"}
        ]
        
        # Verschiedene Geo-Locations simulieren
        geo_headers = [
            {'Accept-Language': 'de-DE,de;q=0.9,en;q=0.8', 'CF-IPCountry': 'DE'},
            {'Accept-Language': 'en-US,en;q=0.9', 'CF-IPCountry': 'US'},
            {'Accept-Language': 'en-GB,en;q=0.9', 'CF-IPCountry': 'GB'},
            {'Accept-Language': 'fr-FR,fr;q=0.9,en;q=0.8', 'CF-IPCountry': 'FR'}
        ]
        
        for ip_headers in ip_spoofing_headers:
            for geo in geo_headers:
                try:
                    session = requests.Session()
                    
                    combined_headers = {
                        'User-Agent': f'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/{random.randint(115, 120)}.0.0.0 Safari/537.36',
                        **ip_headers,
                        **geo
                    }
                    
                    session.headers.update(combined_headers)
                    
                    # API-Aufruf mit gefakter Geo-Location
                    api_url = f"https://www.youtube.com/api/timedtext?v={video_id}&lang=de&fmt=json3"
                    response = session.get(api_url, timeout=10)
                    
                    if response.status_code == 200 and len(response.content) > 100:
                        return self.parse_json3_format(response.text)
                        
                except Exception as e:
                    continue
        
        raise Exception("Proxy-Chain: Alle IP-Spoofing-Kombinationen fehlgeschlagen")
    
    # Parsing-Funktionen
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
                            # Lade Caption-Content
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
    
    def is_binary_content(self, content):
        """Prüfe ob Content binär/beschädigt ist"""
        if not content:
            return True
        
        # Suche nach vielen Non-ASCII Control Characters
        control_chars = sum(1 for c in content[:500] if ord(c) < 32 and c not in '\n\r\t')
        return control_chars > len(content[:500]) * 0.1  # Mehr als 10% Control Chars = binär
    
    def clean_transcript(self, transcript):
        """Transcript bereinigen"""
        if not transcript:
            return ''
        
        # Prüfe auf binären Content
        if self.is_binary_content(transcript):
            raise Exception("Binärer/beschädigter Content erkannt")
        
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
    extractor = YouTubeNuclearOption()
    
    result = extractor.get_transcript(video_url)
    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main()
