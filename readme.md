# CineTrack

## Présentation générale

CineTrack est une application web dédiée à la consultation, la recherche et la gestion de contenus audiovisuels (films et séries).  
Elle permet aux utilisateurs de découvrir des œuvres, de créer une watchlist personnalisée et de publier des critiques.

Les données sont récupérées dynamiquement via l’API **TMDb (The Movie Database)**.

---

## Objectifs du projet

- Proposer une plateforme centralisée de découverte de films et de séries
- Permettre un suivi personnalisé des contenus
- Mettre en place une authentification sécurisée
- Intégrer une API externe dans une application web dynamique

Projet réalisé dans un cadre académique.

---

## Fonctionnalités

### Consultation des films et séries

- Affichage des films et séries populaires
- Recherche par titre (AJAX)
- Filtres par genre et par année
- Pagination des résultats
- Pages de détails avec :
  - Synopsis
  - Note moyenne
  - Casting
  - Bande-annonce
  - Saisons et épisodes pour les séries

---

### Gestion des utilisateurs

- Inscription
- Connexion avec gestion de session
- Accès sécurisé aux pages privées
- Gestion du profil utilisateur (pseudo, email, avatar)

---

### Watchlist

- Ajout de films et séries à une watchlist personnelle
- Vérification des doublons
- Notifications visuelles lors de l’ajout
- Page dédiée à la watchlist utilisateur

---

### Avis et critiques

- Publication de critiques par les utilisateurs connectés
- Attribution d’une note sur 5
- Limitation à une critique par utilisateur et par œuvre
- Affichage des critiques récentes

---

## Interface utilisateur

- Interface responsive (mobile et desktop)
- Design moderne
- Animations et transitions fluides
- Navigation claire et structurée

---

## Sécurité

- Requêtes SQL préparées
- Protection des pages privées
- Validation des entrées utilisateur
- Gestion sécurisée des sessions PHP

---

## Technologies utilisées

### Frontend
- HTML
- CSS
- JavaScript (AJAX, Fetch API)

### Backend
- PHP
- MySQL

### API externe
- TMDb API
- OverPass

---

## Architecture du projet

