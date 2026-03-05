# 🧡 EASY-PAY - Système de Paiement Mobile 

**Lien du projet en ligne** : [https://snow-panther-442077.hostingersite.com/](https://snow-panther-442077.hostingersite.com/)

**Contexte & Problématique** : Dans de nombreux pays émergents, notamment en Afrique, l'accès aux services bancaires traditionnels est limité par des frais élevés et la rareté des terminaux de paiement. **EASY-PAY** répond à la problématique suivante : *Comment favoriser l'inclusion financière et sécuriser les transactions de proximité en transformant chaque smartphone en terminal de paiement dématérialisé ?*.

## 🚀 Fonctionnalités

### 📱 Côté Client
* **Paiement Flexible** : Possibilité de payer instantanément en scannant un **Code QR** ou en saisissant manuellement le **Code Marchand**.
* **Validation sécurisée** : Confirmation de chaque transaction par un code PIN à 4 chiffres pour protéger les fonds de l'utilisateur.
* **Historique des transactions** : Suivi détaillé des dépenses incluant le nom des marchands, les montants et les références.
* **Réinitialisation du PIN** : Procédure de récupération sécurisée via le numéro de téléphone enregistré.

### 🏪 Côté Marchand (EASY-PAY Admin)
* **Activation du Terminal** : Personnalisation du nom de la boutique et configuration d'un identifiant de paiement unique.
* **Génération de QR Code** : Création d'un QR Code dynamique permettant d'encaisser des paiements sans matériel coûteux.
* **Tableau de bord (Stats)** : Visualisation des performances via un graphique (Chart.js) et calcul automatique du solde total.
* **Mise à jour en temps réel** : Système de rafraîchissement automatique et manuel pour suivre l'arrivée des nouvelles ventes.

## 🛠️ Technologies utilisées

* **Frontend** : HTML5, CSS3 (Flexbox), JavaScript (Vanilla JS), Chart.js pour le rendu visuel.
* **Backend** : PHP 8.x (Architecture API JSON) pour la gestion des données et des transactions.
* **Base de données** : MySQL (MariaDB) avec PDO pour garantir la sécurité et l'intégrité des informations.
* **Hébergement** : Solution déployée sur **Hostinger** pour un accès en ligne permanent.

---
*Projet réalisé dans le cadre du Bachelor 1 SIN à l'EPSI Paris.*