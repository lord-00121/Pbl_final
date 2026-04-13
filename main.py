from fastapi import FastAPI, UploadFile, File, HTTPException
from pydantic import BaseModel
import tempfile
import os
from fastapi.staticfiles import StaticFiles
from fastapi.responses import FileResponse
from fastapi.middleware.cors import CORSMiddleware
from PyPDF2 import PdfReader
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_community.embeddings import HuggingFaceEmbeddings
from langchain_community.vectorstores import FAISS
from transformers import AutoTokenizer, AutoModelForSeq2SeqLM

app = FastAPI()

# Enable CORS for frontend interactions
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global variables for single-user state
vector_store = None
tokenizer = None
model = None
embeddings = None

def init_models():
    global embeddings, tokenizer, model
    if embeddings is None:
        embeddings = HuggingFaceEmbeddings(model_name="all-MiniLM-L6-v2")
    if tokenizer is None:
        tokenizer = AutoTokenizer.from_pretrained("google/flan-t5-base")
        model = AutoModelForSeq2SeqLM.from_pretrained("google/flan-t5-base")

class QuestionRequest(BaseModel):
    question: str

def extract_text(file_path: str, filename: str) -> str:
    text = ""
    if filename.endswith(".pdf"):
        pdf_reader = PdfReader(file_path)
        for page in pdf_reader.pages:
            if page.extract_text():
                text += page.extract_text()
    elif filename.endswith(".txt"):
        with open(file_path, "r", encoding="utf-8") as f:
            text = f.read()
    return text

@app.post("/api/upload")
async def upload_document(file: UploadFile = File(...)):
    global vector_store
    if not (file.filename.endswith(".pdf") or file.filename.endswith(".txt")):
        raise HTTPException(status_code=400, detail="Only PDF and TXT files are supported")
    
    with tempfile.NamedTemporaryFile(delete=False, suffix=file.filename) as temp_file:
        content = await file.read()
        temp_file.write(content)
        temp_path = temp_file.name
    
    try:
        text = extract_text(temp_path, file.filename)
        if not text.strip():
            raise HTTPException(status_code=400, detail="Could not extract text from document")
            
        text_splitter = RecursiveCharacterTextSplitter(
            chunk_size=1000,
            chunk_overlap=200,
            length_function=len
        )
        chunks = text_splitter.split_text(text)
        
        global embeddings
        if embeddings is None:
            init_models()
        vector_store = FAISS.from_texts(chunks, embeddings)
        
        return {"message": "Document processed successfully", "chunks": len(chunks)}
    finally:
        os.remove(temp_path)

@app.post("/api/ask")
async def ask_question(req: QuestionRequest):
    global vector_store, tokenizer, model
    if vector_store is None:
        raise HTTPException(status_code=400, detail="Please upload a document first")
    if not req.question.strip():
        raise HTTPException(status_code=400, detail="Question cannot be empty")
        
    docs = vector_store.similarity_search(req.question, k=3)
    
    context = "\n".join([f"Chunk {i+1}: {doc.page_content}" for i, doc in enumerate(docs)])
    prompt = f"Use the following context to answer the question.\n\nContext:\n{context}\n\nQuestion: {req.question}\nAnswer:"
    
    inputs = tokenizer(prompt, return_tensors="pt", max_length=512, truncation=True)
    outputs = model.generate(**inputs, max_new_tokens=200, temperature=0.1)
    result = tokenizer.decode(outputs[0], skip_special_tokens=True)
    
    sources = [{"content": doc.page_content} for doc in docs]
    
    return {
        "answer": result,
        "sources": sources
    }

# Mount static files
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.get("/")
async def root():
    return FileResponse("static/index.html")
