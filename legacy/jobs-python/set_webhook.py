"""Daftarkan webhook + menu perintah bot (jalankan sekali setelah deploy).

    python set_webhook.py https://domainmu.com
"""
import os
import sys
import requests

if len(sys.argv) != 2:
    sys.exit("Pakai: python set_webhook.py https://domainmu.com")

token = os.environ["TELEGRAM_BOT_TOKEN"]
secret = os.environ["TELEGRAM_WEBHOOK_SECRET"]
api = f"https://api.telegram.org/bot{token}"

r = requests.post(f"{api}/setWebhook", timeout=20, json={
    "url": sys.argv[1].rstrip("/") + "/telegram/webhook",
    "secret_token": secret, "allowed_updates": ["message"]})
print("webhook:", r.json())

r = requests.post(f"{api}/setMyCommands", timeout=20, json={"commands": [
    {"command": "cari", "description": "Cari lowongan (dipandu langkah demi langkah)"},
    {"command": "magang", "description": "Cari magang / internship"},
    {"command": "skill", "description": "Cari cepat dari keahlian: /skill python, flask | Jakarta"},
    {"command": "cv", "description": "Kirim CV (PDF/DOCX) untuk dibaca otomatis"},
    {"command": "web", "description": "Buka website Job Finder"},
    {"command": "batal", "description": "Batalkan pencarian yang sedang diisi"},
    {"command": "help", "description": "Cara pakai bot"}]})
print("menu:", r.json())

# tombol menu di samping kolom chat -> membuka website langsung di Telegram (HP & desktop)
r = requests.post(f"{api}/setChatMenuButton", timeout=20, json={"menu_button": {
    "type": "web_app", "text": "Web", "web_app": {"url": sys.argv[1].rstrip("/")}}})
print("menu button:", r.json())