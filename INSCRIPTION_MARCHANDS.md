# 🎉 Système d'Inscription des Marchands

## ✅ Fonctionnalité ajoutée

Les marchands peuvent maintenant s'inscrire directement depuis la page de connexion !

## 📋 Processus d'inscription

### 1. Accès
- Page de connexion : `/login`
- Cliquer sur "Inscrivez-vous" en bas de la page
- Redirection vers `/register`

### 2. Formulaire d'inscription
Le marchand doit fournir :
- **Prénom** (obligatoire)
- **Nom** (obligatoire)
- **Téléphone** (obligatoire) - Servira de nom d'utilisateur
- **Email** (optionnel)
- **Catégorie d'activité** (obligatoire)
- **Type de personne** (Physique ou Morale)
- **Mot de passe** (minimum 6 caractères)
- **Confirmation du mot de passe**

### 3. Validation
Après inscription :
- Le compte est créé avec le statut `PENDING_VALIDATION`
- Le niveau KYC est défini à `BASIC`
- Un message de succès s'affiche
- Redirection vers la page de connexion

### 4. Activation par l'administrateur
- L'administrateur voit le nouveau marchand dans la liste
- Statut : "En attente de validation"
- L'admin peut modifier le statut à "Actif" pour activer le compte

## 🔒 Sécurité

- **Validation des données** : Tous les champs obligatoires sont vérifiés
- **Unicité du téléphone** : Impossible de créer deux comptes avec le même numéro
- **Mot de passe** : Hashé avec bcrypt
- **Statut initial** : PENDING_VALIDATION (le marchand ne peut pas se connecter avant validation)

## 🎨 Interface

- Design moderne et responsive
- Gradient violet/indigo cohérent avec le reste de l'application
- Messages flash pour les erreurs et succès
- Icônes Font Awesome
- Formulaire en 2 colonnes sur desktop

## 📝 Modifications apportées

### Fichiers créés
1. **src/Controller/RegistrationController.php** - Contrôleur d'inscription
2. **templates/security/register.html.twig** - Page d'inscription

### Fichiers modifiés
1. **templates/security/login.html.twig** - Ajout du lien "Inscrivez-vous"

## 🚀 Test

### Créer un compte test
1. Aller sur `/login`
2. Cliquer sur "Inscrivez-vous"
3. Remplir le formulaire :
   - Prénom : Test
   - Nom : Marchand
   - Téléphone : +243999999999
   - Email : test@example.com
   - Catégorie : (choisir une catégorie)
   - Type : Personne Physique
   - Mot de passe : test123
   - Confirmation : test123
4. Cliquer sur "Créer mon compte"
5. Message de succès affiché
6. Redirection vers `/login`

### Activer le compte (Admin)
1. Se connecter en tant qu'admin
2. Aller dans "Marchands"
3. Trouver le nouveau marchand (statut "En attente")
4. Cliquer sur "Modifier"
5. Changer le statut à "Actif"
6. Sauvegarder

### Se connecter avec le nouveau compte
1. Aller sur `/login`
2. Username : +243999999999
3. Password : test123
4. Se connecter

## 🔄 Améliorations futures possibles

- Email de confirmation
- Validation par SMS
- Upload de documents KYC lors de l'inscription
- Captcha pour éviter les inscriptions automatiques
- Vérification de l'email
- Récupération de mot de passe
