from urllib.parse import quote

def handler(session, args):
    if not session.ready():
        return

    text = args if args else "Hello from FreeSWITCH Python"
    encoded_text = quote(text)
    tts_url = f"http://127.0.0.1:8000/api/tts?text={encoded_text}"

    session.answer()
    session.execute("playback", tts_url)
    session.hangup()
