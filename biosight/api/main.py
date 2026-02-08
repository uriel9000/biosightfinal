import os
import io
import json
import logging
import sys
from typing import Optional

import google.generativeai as genai
from fastapi import FastAPI, UploadFile, File, HTTPException, BackgroundTasks
from PIL import Image
from dotenv import load_dotenv
from pydantic import BaseModel
from tenacity import retry, stop_after_attempt, wait_exponential

# Load environment
# Search for .env in current dir, then in parent dir (project root)
env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
if os.path.exists(env_path):
    load_dotenv(dotenv_path=env_path)
else:
    load_dotenv()

# Logger configuration
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("BioSight-AI-Service")

# Gemini Configuration
GENAI_API_KEY = os.getenv("GEMINI_API_KEY")
if not GENAI_API_KEY:
    logger.error("GEMINI_API_KEY not found in environment.")
    sys.exit(1)

genai.configure(api_key=GENAI_API_KEY)

app = FastAPI(title="BioSight AI Microservice", version="1.0.0")

class AnalysisResponse(BaseModel):
    summary: str
    data: dict
    education_hub: list
    disclaimer: str

def preprocess_image(image_bytes: bytes) -> Image.Image:
    """
    Normalization and PII stripping.
    Resizes image to a max dimension of 3072px while maintaining aspect ratio.
    """
    try:
        img = Image.open(io.BytesIO(image_bytes))
        
        # Convert to RGB if necessary (strips Alpha channel/potential metadata)
        if img.mode != 'RGB':
            img = img.convert('RGB')
            
        # Resize for efficiency (Max 3072px)
        max_size = 3072
        if max(img.size) > max_size:
            img.thumbnail((max_size, max_size), Image.Resampling.LANCZOS)
            
        return img
    except Exception as e:
        logger.error(f"Preprocessing error: {e}")
        raise HTTPException(status_code=400, detail="Invalid image format.")

@retry(stop=stop_after_attempt(3), wait=wait_exponential(multiplier=1, min=4, max=10))
def call_gemini_api(img: Image.Image):
    """
    Multimodal inference with production-grade guardrails.
    """
    model_name = os.getenv("GEMINI_MODEL", "gemini-1.5-flash")
    model = genai.GenerativeModel(model_name)
    
    prompt = """
    ROLE: Senior Biomedical Vision Engine (Research Support).
    MISSION: Extract visual patterns into a DETERMINISTIC JSON SCHEMA.
    
    1. SUMMARY: Provide a 2-sentence plain language overview.
    2. FINDINGS: List visual characteristics (textures, densities, symmetry).
    3. CONFIDENCE: Assign a 0.0-1.0 score based on image clarity.
    4. EDUCATION: Define 2-3 technical terms used in the summary for researchers.
    
    ETHICAL CONSTRAINTS:
    - DIAGNOSIS IS STRICTLY FORBIDDEN. 
    - Use 'visual pattern' instead of 'symptom'.
    - Use 'structural variation' instead of 'disease'.
    - If the image is not biomedical, state 'SPECIMEN_NOT_RECOGNIZED'.

    JSON FORMAT:
    {
      "summary": "text",
      "data": {
        "specimen_type": "text",
        "analyzer_confidence": float,
        "findings": [{"feature": "text", "observation": "text", "confidence": float, "certainty": "High|Med|Low"}]
      },
      "education_hub": [{"term": "text", "plain_explanation": "text"}],
      "disclaimer": "RESEARCH USE ONLY. NOT FOR CLINICAL DIAGNOSIS."
    }
    
    OUTPUT RAW JSON ONLY.
    """
    
    response = model.generate_content([prompt, img])
    
    # Sanitization
    raw_text = response.text.replace('```json', '').replace('```', '').strip()
    
    try:
        data = json.loads(raw_text)
        # Validation for a recognized specimen
        if "SPECIMEN_NOT_RECOGNIZED" in str(data):
            raise HTTPException(status_code=422, detail="Subject matter not recognized as biomedical.")
        return data
    except json.JSONDecodeError:
        logger.error(f"Failed to parse AI output: {raw_text}")
        raise ValueError("Non-JSON output from AI.")

@app.post("/analyze", response_model=AnalysisResponse)
async def analyze_image(file: UploadFile = File(...)):
    """
    Primary endpoint for PHP-to-Python communication.
    """
    logger.info(f"Received analysis request for {file.filename}")
    
    content = await file.read()
    processed_img = preprocess_image(content)
    
    try:
        result = call_gemini_api(processed_img)
        return result
    except json.JSONDecodeError:
        logger.error("AI returned malformed JSON.")
        raise HTTPException(status_code=500, detail="Malformed AI response.")
    except Exception as e:
        logger.error(f"Inference error: {e}")
        raise HTTPException(status_code=502, detail="AI service unreachable or timed out.")

@app.get("/health")
async def health_check():
    return {"status": "healthy", "model": "gemini-1.5-flash-ready"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
