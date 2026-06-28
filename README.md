# 📚 Plateforme d'Examens en Ligne

## Documentation Complète

---

## 📋 Table des matières

1. [Présentation](#présentation)
2. [Installation](#installation)
3. [Structure du projet](#structure)
4. [Fonctionnalités](#fonctionnalités)
5. [Guide administrateur](#guide-administrateur)
6. [Guide étudiant](#guide-étudiant)
7. [Système anti-fraude](#système-anti-fraude)
8. [Calculs des moyennes](#calculs)
9. [Sécurité](#sécurité)
10. [Dépannage](#dépannage)

---

## 🎯 Présentation

Plateforme web complète de gestion d'examens en ligne permettant :
- La création et gestion de sujets d'examens
- La composition en ligne par les étudiants
- La surveillance anti-fraude en temps réel
- Le calcul automatique des moyennes
- La publication et l'export des résultats

### Technologies utilisées
- **Frontend** : HTML5, CSS3, JavaScript
- **Backend** : PHP 7.4+
- **Base de données** : MySQL 5.7+ / MariaDB 10.2+
- **Serveur** : Apache 2.4+ avec mod_rewrite

---

## 🔧 Installation

### Prérequis serveur
- PHP >= 7.4
- Extensions PHP : PDO, PDO_MySQL, GD, MBString, JSON, Session
- MySQL >= 5.7 ou MariaDB >= 10.2
- Apache avec mod_rewrite activé
- Espace disque : 50 Mo minimum

### Installation automatique

1. **Télécharger et extraire** les fichiers sur votre serveur
2. **Accéder** à `http://votre-domaine.com/install.php`
3. **Suivre** l'assistant d'installation en 5 étapes
4. **Supprimer** le fichier `install.php` après installation

### Installation manuelle

1. **Créer la base de données** :
```sql
CREATE DATABASE plateforme_examens CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;