document.addEventListener('DOMContentLoaded', () => {
    // Elements
    const uploadBox = document.getElementById('uploadBox');
    const fileInput = document.getElementById('fileInput');
    const browseBtn = document.getElementById('browseBtn');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameDisplay = document.getElementById('fileName');
    const uploadBtn = document.getElementById('uploadBtn');
    const statusMsg = document.getElementById('uploadStatus');
    
    const chatSection = document.getElementById('chatSection');
    const uploadSection = document.querySelector('.upload-section');
    const chatHistory = document.getElementById('chatHistory');
    const questionInput = document.getElementById('questionInput');
    const askBtn = document.getElementById('askBtn');

    let currentFile = null;

    // --- Drag and Drop Logic ---
    uploadBox.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadBox.classList.add('drag-over');
    });

    uploadBox.addEventListener('dragleave', () => {
        uploadBox.classList.remove('drag-over');
    });

    uploadBox.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadBox.classList.remove('drag-over');
        if (e.dataTransfer.files.length > 0) {
            handleFileSelection(e.dataTransfer.files[0]);
        }
    });

    browseBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelection(e.target.files[0]);
        }
    });

    function handleFileSelection(file) {
        const validExtensions = ['pdf', 'txt'];
        const ext = file.name.split('.').pop().toLowerCase();
        
        if (!validExtensions.includes(ext)) {
            statusMsg.innerHTML = `<span style="color: var(--primary)">Please upload a PDF or TXT file.</span>`;
            return;
        }

        currentFile = file;
        fileNameDisplay.textContent = file.name;
        fileInfo.classList.remove('hidden');
        browseBtn.classList.add('hidden');
        statusMsg.innerHTML = '';
    }

    // --- Upload Logic ---
    uploadBtn.addEventListener('click', async () => {
        if (!currentFile) return;

        const formData = new FormData();
        formData.append('file', currentFile);

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Processing...';
        statusMsg.textContent = 'Extracting text and generating embeddings... This might take a few moments.';

        try {
            const response = await fetch('/api/upload', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const err = await response.json();
                throw new Error(err.detail || 'Upload failed');
            }

            const data = await response.json();
            
            // Transition to Chat UI
            uploadSection.classList.add('hidden');
            chatSection.classList.remove('hidden');
            statusMsg.textContent = '';
            
        } catch (error) {
            statusMsg.innerHTML = `<span style="color: #ef4444">${error.message}</span>`;
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Process Document';
        }
    });

    // --- Chat Logic ---
    askBtn.addEventListener('click', initiateQuestion);
    questionInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') initiateQuestion();
    });

    async function initiateQuestion() {
        const question = questionInput.value.trim();
        if (!question) return;

        // Clear input
        questionInput.value = '';

        // Add User Message
        addMessage('user-msg', question);

        // Add Loading indicator
        const loadingId = addLoadingIndicator();

        try {
            const response = await fetch('/api/ask', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question })
            });

            if (!response.ok) {
                const err = await response.json();
                throw new Error(err.detail || 'Failed to get answer');
            }

            const data = await response.json();
            
            // Remove loading
            document.getElementById(loadingId).remove();
            
            // Add System Message with sources
            addSystemResponse(data.answer, data.sources);

        } catch (error) {
            document.getElementById(loadingId).remove();
            addMessage('system-msg', `Error: ${error.message}`);
        }
    }

    function addMessage(type, text) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${type}`;
        msgDiv.innerHTML = `<div class="msg-bubble">${text}</div>`;
        chatHistory.appendChild(msgDiv);
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }

    function addLoadingIndicator() {
        const id = 'loading-' + Date.now();
        const msgDiv = document.createElement('div');
        msgDiv.className = `message system-msg`;
        msgDiv.id = id;
        msgDiv.innerHTML = `
            <div class="msg-bubble">
                <div class="typing-indicator">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                </div>
            </div>`;
        chatHistory.appendChild(msgDiv);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        return id;
    }

    function addSystemResponse(answer, sources) {
        const id = 'sources-' + Date.now();
        const msgDiv = document.createElement('div');
        msgDiv.className = `message system-msg`;
        
        let sourcesHTML = '';
        if (sources && sources.length > 0) {
            const chunks = sources.map((s, i) => `<div class="source-chunk"><strong>Chunk ${i+1}:</strong><br>${s.content}</div>`).join('');
            sourcesHTML = `
                <div class="sources-container">
                    <button class="sources-btn" onclick="document.getElementById('${id}').classList.toggle('hidden')">
                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        View Source Document Chunks
                    </button>
                    <div id="${id}" class="sources-content hidden">${chunks}</div>
                </div>
            `;
        }

        msgDiv.innerHTML = `
            <div class="msg-bubble">
                ${answer}
                ${sourcesHTML}
            </div>`;
        
        chatHistory.appendChild(msgDiv);
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }
});
