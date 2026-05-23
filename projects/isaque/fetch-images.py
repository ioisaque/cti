#!/usr/bin/env python3
"""
Download Wikimedia Commons originals and encode to AVIF in ./imgs/
Run from projects/isaquecosta:  python3 fetch-images.py
"""
import json
import os
import subprocess
import sys
import time
import urllib.parse
import urllib.request
from typing import Optional

UA = {"User-Agent": "Mozilla/5.0 (compatible; IsaqueCostaPortfolio/1.0; +https://isaque.it)"}
API = "https://commons.wikimedia.org/w/api.php"
ROOT = os.path.dirname(os.path.abspath(__file__))
IMGS = os.path.join(ROOT, "imgs")

# (slug.avif basename without .avif, Wikimedia File: title OR direct upload URL)
SOURCES = [
    ("petra", "File:Al Khazneh Petra edit 2.jpg"),
    ("christ-redeemer", "File:Christ the Redeemer 1.jpg"),
    ("machu-picchu", "File:Machu Picchu, Peru.jpg"),
    ("chichen-itza", "File:Chichen Itza 3.jpg"),
    ("colosseum", "File:Colosseo 2020.jpg"),
    ("taj-mahal", "File:Taj Mahal, Agra, India edit3.jpg"),
    ("statue-of-liberty", "File:Statue of Liberty 7.jpg"),
    ("eiffel-tower", "File:Tour Eiffel Wikimedia Commons.jpg"),
    ("stonehenge", "File:Stonehenge.jpg"),
    ("easter-island", "File:Rano Raraku quarry.jpg"),
    ("pyramids-giza", "File:All Gizah Pyramids.jpg"),
    ("angkor-wat", "File:Angkor Wat.jpg"),
    ("sydney-opera", "File:Sydney Opera House - Dec 2008.jpg"),
    ("golden-gate", "File:GoldenGateBridge-001.jpg"),
    ("sagrada-familia", "File:Sagrada Familia May 2022 01.jpg"),
    ("acropolis", "File:The Parthenon in Athens.jpg"),
    ("niagara-falls", "File:3Falls Niagara.jpg"),
    ("palace-westminster", "File:Palace of Westminster, London, UK.jpg"),
    ("mount-fuji", "File:Mount Fuji from Lake Kawaguchi.jpg"),
    ("louvre", "File:Louvre Museum Wikimedia Commons.jpg"),
    ("forbidden-city", "File:Hall of Supreme Harmony.JPG"),
    ("mount-rushmore", "File:Mount Rushmore Monument.jpg"),
    ("empire-state", "File:Empire State Building (aerial view).jpg"),
    ("great-wall", "File:The Great Wall of China at Jinshanling-edit.jpg"),
    # New locations
    ("buckingham-palace", "File:Buckingham Palace from side, London, UK - Diliff.jpg"),
    ("big-ben", "File:Big Ben Elizabeth Tower London 2023 01.jpg"),
    ("tower-of-london", "File:Tower of London viewed from the River Thames.jpg"),
    ("london-eye", "File:London Eye at night.jpg"),
    ("temple-bar", "File:Temple Bar Dublin.jpg"),
    ("trevi-fountain", "File:Trevi Fountain, Rome, Italy - May 2007.jpg"),
    ("pantheon-rome", "File:Pantheon (Rome).jpg"),
    ("basilica-sao-pedro", "File:St. Peter's Basilica and its dome.jpg"),
    ("piazza-venezia", "File:Rome, Italy, Piazza Venezia.jpg"),
    ("tower-of-pisa", "File:Leaning Tower of Pisa.jpg"),
    ("congresso-nacional", "File:Congresso Nacional do Brasil.jpg"),
    ("catedral-brasilia", "File:Catedral Metropolitana de Brasília.jpg"),
    ("times-square", "File:Times Square, New York City (HDR).jpg"),
    ("hayden-planetarium", "File:Rose Center for Earth and Space.jpg"),
    ("miami-beach", "File:South Beach, Miami Beach, Florida 8 June 2021.jpg"),
]


def api_image_url(title: str) -> Optional[str]:
    q = urllib.parse.urlencode(
        {"action": "query", "titles": title, "prop": "imageinfo", "iiprop": "url", "format": "json"}
    )
    req = urllib.request.Request(API + "?" + q, headers=UA)
    with urllib.request.urlopen(req, timeout=45) as r:
        data = json.load(r)
    page = next(iter(data["query"]["pages"].values()))
    ii = page.get("imageinfo", [{}])[0]
    u = ii.get("url")
    if not u:
        print("MISSING:", title, page.get("title"), file=sys.stderr)
        return None
    return u.split("?")[0]


def download(url: str, dest: str) -> bool:
    req = urllib.request.Request(url, headers=UA)
    with urllib.request.urlopen(req, timeout=120) as r:
        data = r.read()
    with open(dest, "wb") as f:
        f.write(data)
    return True


def to_avif(src_jpg: str, dest_avif: str) -> None:
    # Good balance of size vs quality for web
    cmd = [
        "avifenc",
        "--min",
        "0",
        "--max",
        "63",
        "-a",
        "end-usage=q",
        "-a",
        "cq-level=28",
        "-a",
        "sharpness=0",
        "-s",
        "2",
        src_jpg,
        dest_avif,
    ]
    subprocess.run(cmd, check=True)


def main() -> int:
    os.makedirs(IMGS, exist_ok=True)
    for slug, title_or_url in SOURCES:
        out = os.path.join(IMGS, f"{slug}.avif")
        if os.path.isfile(out) and os.path.getsize(out) > 5000:
            print(slug, "exists, skip")
            continue
        if title_or_url.startswith("http"):
            url = title_or_url
        else:
            time.sleep(0.35)
            url = api_image_url(title_or_url)
        if not url:
            print(slug, "SKIP (no URL)")
            continue
        ext = os.path.splitext(urllib.parse.urlparse(url).path)[1].lower() or ".jpg"
        if ext not in (".jpg", ".jpeg", ".png", ".webp"):
            ext = ".jpg"
        typed = os.path.join(IMGS, f"_{slug}{ext}")
        print(slug, "...", end=" ", flush=True)
        try:
            time.sleep(0.4)
            download(url, typed)
            to_avif(typed, out)
            os.remove(typed)
            print("ok", os.path.getsize(out), "bytes")
        except Exception as e:
            print("FAIL", e, file=sys.stderr)
            if os.path.isfile(typed):
                os.remove(typed)
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
