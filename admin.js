// =====================================================
// JAVASCRIPT ADMIN - PLATEFORME EXAMENS
// =====================================================

// Toggle sidebar mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('open');
}

// Fermer sidebar en cliquant en dehors
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.menu-toggle');
    
    if (sidebar.classList.contains('open') && 
        !sidebar.contains(e.target) && 
        !toggle.contains(e.target)) {
        sidebar.classList.remove('open');
    }
});

// Rafraîchissement automatique des alertes (toutes les 30 secondes)
let alerteInterval;

function startAlerteRefresh() {
    alerteInterval = setInterval(fetchAlertes, 30000);
}

async function fetchAlertes() {
    try {
        const response = await fetch('../api/alertes-fraudes.php?format=json');
        const data = await response.json();
        
        if (data.count > 0) {
            // Notification
            if (Notification.permission === 'granted') {
                new Notification('🚨 Alerte Fraude', {
                    body: `${data.count} nouvelle(s) alerte(s) détectée(s)`,
                    icon: '../assets/images/alert.png'
                });
            }
            
            // Mise à jour du badge
            const badge = document.querySelector('.sidebar-menu .badge-danger');
            if (badge) {
                badge.textContent = data.count;
            }
        }
    } catch (error) {
        console.error('Erreur rafraîchissement alertes:', error);
    }
}

// Demander permission notifications
if ('Notification' in window) {
    Notification.requestPermission();
}

// Démarrer le rafraîchissement
startAlerteRefresh();

// Modal
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Fermer modal en cliquant en dehors
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Confirmation actions
function confirmerAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Export données
function exporterDonnees(format, tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let data = '';
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = [];
        cells.forEach(cell => {
            rowData.push('"' + cell.textContent.trim() + '"');
        });
        data += rowData.join(',') + '\n';
    });
    
    const blob = new Blob([data], { type: 'text/' + format });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'export_' + new Date().toISOString().slice(0,10) + '.' + format;
    a.click();
    URL.revokeObjectURL(url);
}

// Recherche dans les tableaux
function rechercherTableau(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const filter = this.value.toUpperCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toUpperCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Tri des tableaux
function trierTableau(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = table.dataset.sortDirection === 'asc';
    table.dataset.sortDirection = isAscending ? 'desc' : 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.children[columnIndex].textContent.trim();
        const bValue = b.children[columnIndex].textContent.trim();
        
        if (isAscending) {
            return aValue.localeCompare(bValue);
        } else {
            return bValue.localeCompare(aValue);
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Timer déconnexion automatique
let inactivityTime;

function resetInactivityTimer() {
    clearTimeout(inactivityTime);
    inactivityTime = setTimeout(() => {
        window.location.href = 'deconnexion.php?inactivite=1';
    }, 30 * 60 * 1000); // 30 minutes
}

['mousemove', 'keypress', 'click', 'scroll'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer);
});

resetInactivityTimer();

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Panel Admin initialisé');
});