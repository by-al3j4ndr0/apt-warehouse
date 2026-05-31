const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progressBar');
        const resultDiv = document.getElementById('result');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const fileDate = document.getElementById('fileDate');
        const manualUploadBtn = document.getElementById('manualUploadBtn');
        const statsDiv = document.getElementById('stats');
        
        let selectedFile = null;
        
        // Eventos de drop zone
        dropZone.addEventListener('click', () => fileInput.click());
        
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });
        
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });
        
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            handleFileSelection(file);
        });
        
        fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            handleFileSelection(file);
        });
        
        manualUploadBtn.addEventListener('click', () => {
            if (selectedFile) {
                uploadFile(selectedFile);
            }
        });
        
        function handleFileSelection(file) {
            if (!file) return;
            
            // Validar tipo de archivo
            const allowedExtensions = ['csv'];
            const extension = file.name.split('.').pop().toLowerCase();
            
            if (!allowedExtensions.includes(extension)) {
                showResult('error', '❌ Tipo de archivo no permitido. Solo se permiten archivos CSV (.csv)');
                return;
            }
            
            // Validar tamaño (10MB máximo)
            if (file.size > 1 * 1024 * 1024) {
                showResult('error', '❌ El archivo es demasiado grande. Máximo 1MB');
                return;
            }
            
            selectedFile = file;
            
            // Mostrar información del archivo
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            fileDate.textContent = new Date(file.lastModified).toLocaleString();
            fileInfo.style.display = 'block';
            manualUploadBtn.style.display = 'block';
            
            // Subir automáticamente (opcional)
            uploadFile(file);
        }
        
        function uploadFile(file) {
            const formData = new FormData();
            formData.append('archivo', file);
            
            const xhr = new XMLHttpRequest();
            
            xhr.open('POST', '../api/putUploadManifest.php', true);
            
            // Mostrar progreso
            progressContainer.style.display = 'block';
            resultDiv.style.display = 'none';
            statsDiv.style.display = 'none';
            
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percent + '%';
                    progressBar.textContent = percent + '%';
                }
            });
            
            xhr.onload = () => {
                progressContainer.style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showResult('success', response.message);
                            if (response.stats) {
                                document.getElementById('statsShipments').textContent = response.stats.shipments || 0;
                                document.getElementById('statsClients').textContent = response.stats.clients || 0;
                                document.getElementById('statsTotal').textContent = response.stats.total || 0;
                                statsDiv.style.display = 'flex';
                            }
                        } else {
                            showResult('error', '❌ ' + response.message);
                        }
                    } catch (e) {
                        showResult('success', xhr.responseText);
                    }
                } else {
                    showResult('error', '❌ Error en el servidor. Código: ' + xhr.status);
                }
                
                progressBar.style.width = '0%';
                progressBar.textContent = '0%';
            };
            
            xhr.onerror = () => {
                progressContainer.style.display = 'none';
                showResult('error', '❌ Error de conexión. Verifica tu conexión a internet.');
            };
            
            xhr.send(formData);
        }
        
        function showResult(type, message) {
            resultDiv.className = '';
            resultDiv.classList.add(`result-${type}`);
            resultDiv.innerHTML = message;
            resultDiv.style.display = 'block';
            
            // Auto ocultar después de 5 segundos
            setTimeout(() => {
                if (resultDiv.style.display !== 'none') {
                    resultDiv.style.opacity = '0';
                    setTimeout(() => {
                        resultDiv.style.display = 'none';
                        resultDiv.style.opacity = '1';
                    }, 500);
                }
            }, 5000);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }