import streamlit as st
import tempfile
import os
from PyPDF2 import PdfReader
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_community.embeddings import HuggingFaceEmbeddings
from langchain_community.vectorstores import FAISS
from langchain_community.llms.huggingface_pipeline import HuggingFacePipeline
from transformers import pipeline

@st.cache_resource
def load_llm():
    pipe = pipeline(
        "text2text-generation",
        model="google/flan-t5-base",
        max_length=512,
        temperature=0.1,
        truncation=True
    )
    llm = HuggingFacePipeline(pipeline=pipe)
    return llm

@st.cache_resource
def load_embeddings():
    return HuggingFaceEmbeddings(model_name="all-MiniLM-L6-v2")

def extract_text_from_pdf(pdf_file):
    pdf_reader = PdfReader(pdf_file)
    text = ""
    for page in pdf_reader.pages:
        if page.extract_text():
            text += page.extract_text()
    return text

def extract_text_from_txt(txt_file):
    return txt_file.read().decode("utf-8")

def process_document(file):
    if file.name.endswith(".pdf"):
        text = extract_text_from_pdf(file)
    elif file.name.endswith(".txt"):
        text = extract_text_from_txt(file)
    else:
        st.error("Document format not supported!")
        return None
    
    text_splitter = RecursiveCharacterTextSplitter(
        chunk_size=1000,
        chunk_overlap=200,
        length_function=len
    )
    chunks = text_splitter.split_text(text)
    return chunks

def main():
    st.set_page_config(page_title="AI Document QA System", layout="wide")
    st.title("ðŸ“„ AI Question Answering System")
    st.markdown("Upload a PDF or Text document, and ask questions about its content!")

    if "vector_store" not in st.session_state:
        st.session_state.vector_store = None

    with st.sidebar:
        st.header("1. Upload Document")
        uploaded_file = st.file_uploader("Upload a PDF or TXT file", type=["pdf", "txt"])
        
        if uploaded_file is not None:
            if st.button("Process Document"):
                with st.spinner("Extracting text and generating embeddings..."):
                    chunks = process_document(uploaded_file)
                    if chunks:
                        embeddings = load_embeddings()
                        vector_store = FAISS.from_texts(chunks, embeddings)
                        st.session_state.vector_store = vector_store
                        st.success("Document processed successfully! You can now ask questions.")

    st.header("2. Ask Questions")
    user_question = st.text_input("What would you like to know about the document?")

    if user_question:
        if st.session_state.vector_store is None:
            st.warning("Please upload and process a document first.")
        else:
            with st.spinner("Generating answer..."):
                llm = load_llm()
                
                docs = st.session_state.vector_store.similarity_search(user_question, k=3)
                context = "\n".join([f"Chunk {i+1}: {doc.page_content}" for i, doc in enumerate(docs)])
                prompt = f"Use the following context to answer the question.\n\nContext:\n{context}\n\nQuestion: {user_question}\nAnswer:"
                
                result = llm.invoke(prompt)
                
                st.subheader("Answer:")
                st.write(result)
                
                with st.expander("View Source Document Chunks"):
                    for idx, doc in enumerate(docs):
                        st.markdown(f"**Chunk {idx+1}:**")
                        st.caption(doc.page_content)

if __name__ == "__main__":
    main()
