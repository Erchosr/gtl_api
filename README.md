# **Email API**

## Introduction
L'API Email permet de récupérer les détails des emails, de lister les emails non lus, ainsi que les emails contenant des pièces jointes. Elle affiche également les destinataires des emails. Les données sont retournées sous forme de JSON. Cette API est conçue pour être utilisée dans un environnement local ou sur un serveur de développement.

## Features
- **Récupération des détails d'un email spécifique** : Utilisez le paramètre `email_number` pour obtenir les détails d'un email.
- **Liste des emails non lus** : Si le paramètre `email_number` n'est pas fourni, l'API retourne tous les emails non lus.
- **Liste des emails avec pièces jointes** : Identifiez les emails contenant des pièces jointes avec une icône 📎.
- **Installation des pièce jointes d'un emaill spécifique** : Utilisez le paramètre `email_number` pour installer les pièce jointe dans le dossier attachements local
- **Affichage des destinataires** : Les destinataires (À, CC, CCI) des emails sont inclus dans la réponse.

---

## Installation

### Prérequis
- PHP 7.4 ou supérieur
- Extension IMAP activée dans votre configuration PHP.

### Étapes d'installation
1. Clonez ce dépôt ou téléchargez les fichiers.
2. Configurez la boîte de réception en suivant les instructions ci-dessous.

---

## Configuration

### Fichier de configuration
Créez un fichier `config.php` dans le même répertoire que `function.php` avec le contenu suivant :
```http
return{
    'hostname' => '{[nom du serveur]:993/imap/ssl}INBOX',
    'username' => '[nom utillisateur du mail]',
    'password' => '[mot de passe du mail]
}
```
# Endpoints

## 1. Récupération des détails d'un email spécifique
**Description** : Récupère les détails d'un email spécifique.
```http
URL : /stage_gtl/project_api/view_email.php?email_number=[id_du_mail]

```

**Paramètres** :  
| Paramètre     | Type  | Description|
|---------------|-------|------------------------------|
| email_number  | int   | **Requis**. L'ID de l'email.|

**Exemple de réponse** :  
```json
{
    "email_id": 1,
    "subject": "Bienvenue",
    "from": "admin@example.com",
    "date": "2025-01-22 10:30:00",
    "read": "no",
    "plain_content": "Bienvenue dans notre service.",
    "html_content": "<p>Bienvenue dans notre service.</p>",
    "attachments": [
        {
            "filename": "document.pdf",
            "path": "http://localhost/stage_gtl/project_api/attachments/20250122_103000_document.pdf"
        }
    ],
    "recipients": ["user1@example.com", "user2@example.com"]
}
```
#
## 2. Liste des emails non lus
**Description** : Retourne tous les emails non lus.

```http
URL : /stage_gtl/project_api/view_email.php
```

**Exemple de réponse**:

```http
[
    {
        "email_id": 1,
        "subject": "Bienvenue",
        "from": "admin@example.com",
        "date": "2025-01-22 10:30:00",
        "read": "no",
        "plain_content": "Bienvenue dans notre service.",
        "html_content": "<p>Bienvenue dans notre service.</p>",
        "attachments": [],
        "recipients": ["user1@example.com"]
    },
    {
        "email_id": 2,
        "subject": "Facture",
        "from": "billing@example.com",
        "date": "2025-01-22 11:00:00",
        "read": "no",
        "plain_content": "Votre facture est disponible.",
        "html_content": "<p>Votre facture est disponible.</p>",
        "attachments": [],
        "recipients": ["user2@example.com"]
    }
]
```

# 
## 3. Liste des emails avec pièces jointes
**Description** : Affiche une liste d'emails de faacon visuelle et affiche les different destinataires du mail. A partir de cette page, il est possible d'aller sur les different URL API du mail

```http
URL : /stage_gtl/project_api/index.php
```
**Exemple de réponse**:

#
## 4. Telechager les piéce jointe d'un mail spécifique

**Description** : Récupère et installe les pièces jointes d'un email spécifique.

**Paramètres** :

| Paramètre     | Type  | Description|
|---------------|-------|------------------------------|
| email_number  | int   | **Requis**. L'ID de l'email.|

**Exemple de réponse**:

```json
{
    "message": "The attachments of the email [1] have been installed successfully.",
    "attachments": [
        {
            "filename": "document.pdf",
            "path": "http://localhost/stage_gtl/project_api/attachments/20250122_103000_document.pdf"
        }
    ]
}
```

## Fonctionnalités avancées

### Gestion des pièces jointes
Les pièces jointes sont stockées dans le dossier attachments.
Si le fichier existe déjà, il ne sera pas écrasé.

### Nettoyage des contenus
Les contenus texte brut et html sont normalisés pour etre lilsible.

### Développement

#### Structure des fichiers :
* **function.php** : Contient les classes principales pour la gestion des emails.
* **config.php** : Contient les informations de configuration pour la connexion à la boîte mail.
* **view_email.php** : Récupère les détails d'un email ou liste les emails non lus.
* **get_file.php** : Gère le téléchargement des pièces jointes.
* **index.php** : Affiche une liste d'emails avec une interface HTML.

### Auteurs
Ce projet a été développé par Erwan Arnaud.

## Documentation

[Documentation de l'API](documentation/index.html)