import google.generativeai as genai
import os
from dotenv import load_dotenv

# Load .env from project root
env_path = os.path.join(os.path.dirname(__file__), '..', '.env')
load_dotenv(dotenv_path=env_path)

api_key = os.getenv("GEMINI_API_KEY")
print(f"API Key found: {'Yes' if api_key else 'No'}")

if api_key:
    genai.configure(api_key=api_key)
    try:
        print("Available models supporting generateContent:")
        for m in genai.list_models():
            if 'generateContent' in m.supported_generation_methods:
                print(m.name)
    except Exception as e:
        print(f"Error listing models: {e}")
