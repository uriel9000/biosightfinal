from fastapi import FastAPI, UploadFile, File, HTTPException, Request, Depends
from fastapi.responses import HTMLResponse, JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from PIL import Image
from dotenv import load_dotenv
import google.generativeai as genai
import aiomysql
import hashlib
import os
import io
import json
import logging
import traceback
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.backends import default_backend
from tenacity import retry, stop_after_attempt, wait_exponential

# ----------------------------------------------------------------------------
# ENV + LOGGING
# ----------------------------------------------------------------------------

load_dotenv()

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("BioSight")

# ----------------------------------------------------------------------------
# FASTAPI APP (SINGLE INSTANCE — DO NOT DUPLICATE)
# ----------------------------------------------------------------------------

app = FastAPI(
    title="BioSight AI",
    version="1.0.0-hackathon",
    description="Biomedical Image Interpretation for Research & Education"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# ----------------------------------------------------------------------------
# GEMINI CONFIG
# ----------------------------------------------------------------------------

GENAI_API_KEY = os.getenv("GEMINI_API_KEY")
if not GENAI_API_KEY:
    raise RuntimeError("GEMINI_API_KEY is missing")

genai.configure(api_key=GENAI_API_KEY)

# ----------------------------------------------------------------------------
# DATABASE (OPTIONAL — SAFE MODE)
# ----------------------------------------------------------------------------

DB_CONFIG = {
    "host": os.getenv("DB_HOST"),
    "user": os.getenv("DB_USER"),
    "password": os.getenv("DB_PASS"),
    "db": os.getenv("DB_NAME"),
    "autocommit": True,
}

pool = None
DB_ENABLED = all(DB_CONFIG.values())

@app.on_event("startup")
async def startup():
    global pool
    if not DB_ENABLED:
        logger.warning("DB disabled — running in demo mode")
        return
    try:
        pool = await aiomysql.create_pool(**DB_CONFIG)
        logger.info("Database connected")
    except Exception as e:
        pool = None
        logger.warning("Database unavailable — demo mode enabled")

@app.on_event("shutdown")
async def shutdown():
    if pool:
        pool.close()
        await pool.wait_closed()

async def get_db():
    if not pool:
        raise HTTPException(status_code=503, detail="Database unavailable (demo mode)")
    async with pool.acquire() as conn:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            yield cur

# ----------------------------------------------------------------------------
# SECURITY UTILITIES
# ----------------------------------------------------------------------------

APP_KEY = (os.getenv("APP_ENCRYPTION_KEY") or "demo_key_32_chars_long________").encode()[:32]

def encrypt(text: str) -> bytes:
    nonce = os.urandom(16)
    cipher = Cipher(algorithms.AES(APP_KEY), modes.CTR(nonce), backend=default_backend())
    return nonce + cipher.encryptor().update(text.encode())

def decrypt(blob: bytes) -> str:
    nonce, data = blob[:16], blob[16:]
    cipher = Cipher(algorithms.AES(APP_KEY), modes.CTR(nonce), backend=default_backend())
    return cipher.decryptor().update(data).decode()

def session_hash(request: Request) -> str:
    return hashlib.sha256(
        request.cookies.get("PHPSESSID", "demo").encode()
    ).hexdigest()

# ----------------------------------------------------------------------------
# AI CORE
# ----------------------------------------------------------------------------

def preprocess(image_bytes: bytes) -> Image.Image:
    try:
        img = Image.open(io.BytesIO(image_bytes)).convert("RGB")
        img.thumbnail((3072, 3072))
        return img
    except Exception:
        raise HTTPException(status_code=400, detail="Invalid image")

@retry(stop=stop_after_attempt(3), wait=wait_exponential(2, 4, 10))
def gemini_analyze(img: Image.Image):
    model = genai.GenerativeModel("gemini-1.5-flash")
    prompt = """
    ROLE: Biomedical Vision Engine (Research Mode).
    RULES:
    - NO diagnosis
    - NO disease claims
    - Visual patterns only
    OUTPUT STRICT JSON:
    {
      "summary": "...",
      "data": {
        "specimen_type": "...",
        "analyzer_confidence": 0.0,
        "findings": []
      },
      "education_hub": [],
      "disclaimer": "RESEARCH USE ONLY"
    }
    """
    response = model.generate_content([prompt, img])
    text = response.text.replace("```json", "").replace("```", "").strip()
    return json.loads(text)

# ----------------------------------------------------------------------------
# SCHEMAS
# ----------------------------------------------------------------------------

class AnalysisResponse(BaseModel):
    summary: str
    data: dict
    education_hub: list
    disclaimer: str

# ----------------------------------------------------------------------------
# JUDGE-FACING ROUTES
# ----------------------------------------------------------------------------

@app.get("/", response_class=HTMLResponse)
async def home():
    return """
    <h1>BioSight is Live ✅</h1>
    <p>Biomedical Image Interpretation powered by Gemini.</p>
    <ul>
      <li><a href="/docs">API Docs</a></li>
      <li><a href="/demo">Demo Overview</a></li>
      <li><a href="/health">Health Check</a></li>
    </ul>
    """

@app.get("/demo")
async def demo():
    return {
        "problem": "Limited access to radiology expertise",
        "solution": "AI-powered visual pattern extraction",
        "stack": ["FastAPI", "Gemini", "Python"],
        "mode": "Hackathon Demo"
    }

@app.get("/health")
async def health():
    return {"status": "healthy", "db": bool(pool)}

# ----------------------------------------------------------------------------
# CORE ENDPOINT
# ----------------------------------------------------------------------------

@app.post("/analyze", response_model=AnalysisResponse)
async def analyze(file: UploadFile = File(...)):
    img = preprocess(await file.read())
    try:
        return gemini_analyze(img)
    except Exception as e:
        logger.error(e)
        raise HTTPException(status_code=502, detail="AI processing failed")

# ----------------------------------------------------------------------------
# OPTIONAL DB ENDPOINTS (SAFE)
# ----------------------------------------------------------------------------

@app.post("/api/consent")
async def consent(request: Request, db=Depends(get_db)):
    await db.execute(
        "INSERT INTO consent_logs (session_hash) VALUES (%s)",
        (session_hash(request),)
    )
    return {"success": True}

@app.get("/api/history")
async def history(request: Request, db=Depends(get_db)):
    await db.execute(
        "SELECT interpretation_blob FROM analysis_logs WHERE session_hash=%s LIMIT 5",
        (session_hash(request),)
    )
    rows = await db.fetchall()
    return {"history": [decrypt(r["interpretation_blob"]) for r in rows]}
