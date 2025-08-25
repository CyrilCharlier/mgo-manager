# MGO Manager

![PHPStan](https://github.com/CyrilCharlier/mgo-manager/actions/workflows/phpstan.yml/badge.svg)
![CodeQL](https://github.com/CyrilCharlier/mgo-manager/actions/workflows/codeql.yml/badge.svg)

**MGO Manager** est une application web permettant de gérer efficacement les comptes du jeu *Monopoly GO!* (MGO). Elle offre une interface intuitive pour suivre les cartes, organiser les échanges, et visualiser les statistiques globales.

---

## 🎯 Objectifs

- Centraliser la gestion de plusieurs comptes MGO
- Visualiser les cartes possédées, manquantes, échangées
- Aider à organiser des transferts de cartes optimaux
- Offrir des statistiques

---

## ⚙️ Fonctionnalités principales

- ✅ Vue par collection, sets, et cartes manquantes
- ✅ Système de notifications intelligentes pour les transferts
- ✅ Multi-comptes par utilisateur
- ✅ Historique des actions
- ✅ Interface responsive (PC / mobile)
- ✅ Page publique pour partager son avancée
- ✅ Gestion de comptes "groupe" pour une gestion partagée
- 🔄 Export CSV (à venir)
- 🔄 Statistiques utilisateurs (à venir)

---

## 📸 Aperçu (Screenshots)

<img width="973" alt="image" src="https://github.com/user-attachments/assets/2bcf1ae1-bea4-4716-b859-a0bc82d0a18f" />
<img width="958" alt="image" src="https://github.com/user-attachments/assets/2fde9adf-3885-4ae5-aacc-c6c9a19bfc95" />
<img width="959" alt="image" src="https://github.com/user-attachments/assets/91d2baff-7791-40ca-8f82-2d94bb5c3b18" />

---

## 🛠️ Stack technique

- **Backend :** Symfony 6 / PHP 8.2
- **Frontend :** HTML, Twig, Bootstrap 5 (dark theme)
- **Database :** MySQL
- **Graphiques :** Chart.js
- **CI :** GitHub Actions + PHPStan ![PHPStan](https://github.com/CyrilCharlier/mgo-manager/actions/workflows/phpstan.yml/badge.svg)

---

## 🚀 Lancer le projet en local

```bash
git clone https://github.com/CyrilCharlier/mgo-manager.git
cd mgo-manager

# Installer les dépendances
composer install

# Copier et adapter le fichier .env
cp .env .env.local

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Lancer le serveur Symfony
symfony server:start
```

Accède à l'application : [http://localhost:8000](http://localhost:8000)

---

## 🔒 Sécurité & Qualité

- Analyse statique avec [PHPStan](https://github.com/phpstan/phpstan)
- Contrôle des accès par rôle (admin, membre)
- Validation back + front des entrées utilisateur

---

## 📄 Licence

Ce projet est open-source sous licence MIT.  
© Cyril Charlier

---

## 🙌 Contribuer

Les contributions sont les bienvenues !  
Propose une idée, une amélioration ou une correction via [issues](https://github.com/CyrilCharlier/mgo-manager/issues) ou [pull request](https://github.com/CyrilCharlier/mgo-manager/pulls).
