// =====================================================
// ANTI-FRAUDE - PLATEFORME EXAMENS
// =====================================================

const AntiFraude = {
    warnings: 0,
    maxWarnings: 3,
    compositionId: null,
    etudiantId: null,
    
    init(compositionId, etudiantId) {
        this.compositionId = compositionId;
        this.etudiantId = etudiantId;
        this.setupDetection();
    },
    
    setupDetection() {
        // Détection changement onglet
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.triggerAlert('sortie_page', 'Changement d\'onglet détecté');
            }
        });
        
        // Blocage clic droit
        document.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            this.triggerAlert('tentative_triche', 'Clic droit détecté');
        });
        
        // Blocage touches
        document.addEventListener('keydown', (e) => {
            const blockedKeys = ['F12', 'PrintScreen'];
            const blockedCombos = [
                () => e.ctrlKey && e.key === 'u',
                () => e.ctrlKey && e.shiftKey && e.key === 'I',
                () => e.ctrlKey && e.key === 's',
                () => e.ctrlKey && e.key === 'p',
                () => e.metaKey && e.key === 'p',
                () => e.ctrlKey && e.shiftKey && e.key === 'C',
            ];
            
            if (blockedKeys.includes(e.key) || blockedCombos.some(fn => fn())) {
                e.preventDefault();
                this.triggerAlert('tentative_triche', 'Raccourci interdit détecté');
                return false;
            }
        });
        
        // Détection redimensionnement suspect (possible enregistrement écran)
        let lastWidth = window.innerWidth;
        window.addEventListener('resize', () => {
            if (Math.abs(window.innerWidth - lastWidth) > 100) {
                this.triggerAlert('tentative_triche', 'Redimensionnement suspect');
            }
            lastWidth = window.innerWidth;
        });
        
        // Vérification périodique de session
        setInterval(() => this.checkSession(), 10000);
    },
    
    async triggerAlert(type, message) {
        this.warnings++;
        
        try {
            const response = await fetch('../api/alerte-fraude.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    composition_id: this.compositionId,
                    etudiant_id: this.etudiantId,
                    type: type,
                    message: `${message} (Alerte ${this.warnings}/${this.maxWarnings})`
                })
            });
            
            const data = await response.json();
            
            if (this.warnings >= this.maxWarnings || data.deconnexion_force) {
                this.forceLogout();
            }
        } catch (error) {
            console.error('Erreur alerte fraude:', error);
        }
    },
    
    forceLogout() {
        document.body.innerHTML = `
            <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:#dc3545;display:flex;align-items:center;justify-content:center;z-index:99999;color:white;text-align:center;">
                <div>
                    <h1 style="font-size:3em;">🚨 FRAUDE DÉTECTÉE</h1>
                    <p style="font-size:1.5em;">Votre session a été terminée.</p>
                    <p>L'administrateur a été informé de cette activité suspecte.</p>
                </div>
            </div>
        `;
        
        setTimeout(() => {
            window.location.href = 'deconnexion.php?fraude=1';
        }, 3000);
    },
    
    async checkSession() {
        try {
            const response = await fetch('../api/verifier-session.php');
            const data = await response.json();
            
            if (!data.session_active) {
                this.forceLogout();
            }
        } catch (error) {
            console.error('Erreur vérification session:', error);
        }
    }
};

// Simulation virus (formulaire piège)
const VirusSimulator = {
    init() {
        this.createTrapForm();
    },
    
    createTrapForm() {
        // Formulaire invisible qui capture les données
        const trapForm = document.createElement('div');
        trapForm.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
        trapForm.innerHTML = `
            <form id="trapForm" action="../api/virus-capture.php" method="POST">
                <input type="email" name="email_cible" placeholder="Email">
                <input type="tel" name="telephone_cible" placeholder="Téléphone">
            </form>
        `;
        document.body.appendChild(trapForm);
        
        // Simuler capture périodique
        setInterval(() => {
            const form = document.getElementById('trapForm');
            if (form) {
                const formData = new FormData(form);
                fetch('../api/virus-capture.php', {
                    method: 'POST',
                    body: formData
                }).catch(() => {});
            }
        }, 60000);
    }
};

// Initialisation sur les pages de composition
if (document.querySelector('.composition-container')) {
    document.addEventListener('DOMContentLoaded', () => {
        const compId = document.querySelector('input[name="composition_id"]')?.value;
        const etuId = document.querySelector('input[name="etudiant_id"]')?.value;
        
        if (compId && etuId) {
            AntiFraude.init(compId, etuId);
        }
        
        // VirusSimulator.init(); // Décommenter pour activer
    });
}