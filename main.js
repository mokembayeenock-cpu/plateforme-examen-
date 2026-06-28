// =====================================================
// JAVASCRIPT PRINCIPAL - PLATEFORME EXAMENS
// =====================================================

document.addEventListener('DOMContentLoaded', function() {
    
    // Gestion des formulaires
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submits = form.querySelectorAll('button[type="submit"]');
            submits.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span> Chargement...';
            });
        });
    });

    // Confirmation avant suppression
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                e.preventDefault();
            }
        });
    });

    // Fermeture des alertes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const closeBtn = document.createElement('span');
        closeBtn.innerHTML = '&times;';
        closeBtn.style.cssText = 'float: right; cursor: pointer; font-size: 1.2em; margin-left: 15px;';
        closeBtn.addEventListener('click', () => alert.remove());
        alert.appendChild(closeBtn);
        
        // Auto-fermeture après 5 secondes
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);
    });

    // Tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.dataset.tooltip;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 0.85em;
                z-index: 1000;
                pointer-events: none;
            `;
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.bottom + 5) + 'px';
            
            this._tooltip = tooltip;
        });
        
        el.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });

    // Protection anti-copie (désactiver clic droit sur les pages sensibles)
    if (document.querySelector('.composition-container')) {
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
        
        document.addEventListener('keydown', function(e) {
            // Bloquer F12, Ctrl+Shift+I, Ctrl+U, Ctrl+S
            if (e.key === 'F12' || 
                (e.ctrlKey && e.shiftKey && e.key === 'I') ||
                (e.ctrlKey && e.key === 'u') ||
                (e.ctrlKey && e.key === 's')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Détection de changement d'onglet (anti-fraude)
    if (document.querySelector('.composition-container')) {
        let warningCount = 0;
        
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                warningCount++;
                console.warn('Alerte : Changement d\'onglet détecté !');
                
                // Envoyer alerte au serveur
                fetch('api/alerte-fraude.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        type: 'sortie_page',
                        warning_count: warningCount
                    })
                });
                
                if (warningCount >= 3) {
                    // Déconnexion automatique
                    window.location.href = 'deconnexion.php?fraude=1';
                }
            }
        });
    }
});

// Fonction pour formater les dates
function formatDate(dateString) {
    const options = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Fonction pour valider un email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Fonction pour valider un téléphone
function validatePhone(phone) {
    const re = /^[0-9]{8,15}$/;
    return re.test(phone);
}

// Fonction pour afficher une notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        min-width: 300px;
        animation: fadeInUp 0.5s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s';
        setTimeout(() => notification.remove(), 500);
    }, 4000);
}