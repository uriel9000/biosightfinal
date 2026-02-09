from fastapi import FastAPI

app = FastAPI()

@app.get("/")
async def health():
    return {"status": "healthy"}

@app.get("/api/ping")
async def ping():
    return {"message": "pong"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
