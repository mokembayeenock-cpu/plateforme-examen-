// =====================================================
// JAVASCRIPT ÉTUDIANT - PLATEFORME EXAMENS
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion timer sur page composition
    const timerElement = document.getElementById('timer');
    if (timerElement) {
        const dateLimite = timerElement.dataset.limite;
        if (dateLimite) {
            startTimer(new Date(dateLimite).getTime());
        }
    }
    
    // Navigation questions
    const navButtons = document.querySelectorAll('.question-nav-btn');
    navButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const questionId = this.dataset.question;
            const questionBlock = document.getElementById('question-' + questionId);
            if (questionBlock) {
                questionBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            
            // Marquer actif
            navButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Sélection options quiz
    const optionItems = document.querySelectorAll('.option-item');
    optionItems.forEach(item => {
        item.addEventListener('click', function() {
            const parent = this.parentElement;
            parent.querySelectorAll('.option-item').forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
            
            updateProgress();
        });
    });
    
    // Mise à jour progression
    const reponseInputs = document.querySelectorAll('.reponse-textarea, input[type="radio"]');
    reponseInputs.forEach(input => {
        input.addEventListener('change', updateProgress);
        input.addEventListener('keyup', updateProgress);
    });
    
    // Validation formulaire
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Envoi...';
            }
        });
    });
    
    // Prévisualisation photo
    const photoInput = document.getElementById('photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2048) {
                    alert('Photo trop grande. Maximum 2KB.');
                    this.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('photoPreview');
                    if (preview) {
                        preview.innerHTML = '<img src="' + event.target.result + '" alt="Aperçu">';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Confirmation soumission
    const submitComposition = document.querySelector('.submit-composition');
    if (submitComposition) {
        submitComposition.addEventListener('click', function(e) {
            if (!confirm('Confirmer la soumission ? Action irréversible.')) {
                e.preventDefault();
            }
        });
    }
});

// Fonction timer
function startTimer(dateLimite) {
    function update() {
        const now = new Date().getTime();
        const distance = dateLimite - now;
        
        if (distance <= 0) {
            document.getElementById('timerDisplay').textContent = '00:00:00';
            document.getElementById('compositionForm')?.submit();
            return;
        }
        
        const hours = Math.floor(distance / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        const display = document.getElementById('timerDisplay');
        if (display) {
            display.textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
        }
        
        // Alerte 5 minutes
        if (distance < 300000 && distance > 0) {
            const timerBox = document.getElementById('timer');
            if (timerBox && !timerBox.classList.contains('warning')) {
                timerBox.classList.add('warning');
                if (Notification.permission === 'granted') {
                    new Notification('⏰ Attention !', {
                        body: 'Il ne vous reste que 5 minutes !',
                    });
                }
            }
        }
    }
    
    update();
    setInterval(update, 1000);
}

// Fonction progression
function updateProgress() {
    const totalQuestions = document.querySelectorAll('.question-block').length;
    let answeredCount = 0;
    
    document.querySelectorAll('.question-block').forEach(block => {
        const textarea = block.querySelector('textarea');
        const radioChecked = block.querySelector('input[type="radio"]:checked');
        
        if ((textarea && textarea.value.trim() !== '') || radioChecked) {
            answeredCount++;
            block.classList.add('repondu');
        } else {
            block.classList.remove('repondu');
        }
    });
    
    const answeredEl = document.getElementById('answeredCount');
    const progressBar = document.getElementById('progressBar');
    
    if (answeredEl) answeredEl.textContent = answeredCount;
    if (progressBar) progressBar.style.width = (answeredCount / totalQuestions * 100) + '%';
    
    // Mise à jour navigation
    document.querySelectorAll('.question-nav-btn').forEach(btn => {
        const index = btn.dataset.question;
        const block = document.getElementById('question-' + index);
        if (block && block.classList.contains('repondu')) {
            btn.classList.add('repondu');
        }
    });
}

// Demander permission notifications
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Protection anti-fraude légère
if (document.querySelector('.composition-container')) {
    let visibilityWarnings = 0;
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            visibilityWarnings++;
            console.warn('Changement onglet détecté');
        }
    });
    
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
}

console.log('JS Étudiant chargé ✓');