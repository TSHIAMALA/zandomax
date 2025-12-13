# 🔐 Guide de Connexion - ZANDO Market

## Credentials Disponibles

### 1. Super Administrateur
- **URL:** https://zandomax.dynamservices.com/login
- **Username:** `superadmin`
- **Password:** `superadmin`
- **Email:** superadmin@zando.local
- **Rôle:** ROLE_SUPER_ADMIN

### 2. Administrateur Marché
- **URL:** https://zandomax.dynamservices.com/login
- **Username:** `adminmarche`
- **Password:** `adminmarche`
- **Email:** adminmarche@zando.local
- **Rôle:** ROLE_MARKET_ADMIN

### 3. Marchand
- **URL:** https://zandomax.dynamservices.com/login
- **Username:** `jean.marchand`
- **Password:** `merchant`
- **Email:** jean.kasongo@example.com
- **Rôle:** ROLE_MERCHANT

---

## Connexion via l'API

### Endpoint de Login
```
POST https://zandomax.dynamservices.com/api/login_check
Content-Type: application/json
```

### Exemple de requête
```bash
curl -X POST https://zandomax.dynamservices.com/api/login_check \
  -H "Content-Type: application/json" \
  -d '{
    "username": "superadmin",
    "password": "superadmin"
  }'
```

### Réponse attendue
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

---

## Vérifications Effectuées

✅ Utilisateurs créés et activés dans la base de données
✅ Mots de passe hashés correctement avec bcrypt
✅ Configuration de sécurité mise à jour (property: username)
✅ Cache Symfony nettoyé
✅ Routes de login configurées (/login et /api/login_check)
✅ Page de login accessible (HTTP 200)
✅ SSL actif (HTTPS)

---

## Dépannage

### Si vous ne pouvez toujours pas vous connecter :

1. **Vider le cache du navigateur**
   - Ctrl + Shift + Delete (Chrome/Firefox)
   - Vider cookies et cache

2. **Essayer en navigation privée**
   - Ctrl + Shift + N (Chrome)
   - Ctrl + Shift + P (Firefox)

3. **Vérifier que vous utilisez HTTPS**
   - URL correcte : https://zandomax.dynamservices.com/login
   - PAS http:// (sans le S)

4. **Réinitialiser les mots de passe manuellement**
   ```bash
   cd /var/www/zandomax
   php bin/console app:reset-passwords
   php bin/console cache:clear
   ```

5. **Vérifier les logs d'erreur**
   ```bash
   tail -f /var/www/zandomax/var/log/dev.log
   ```

---

## Commandes Utiles

### Réinitialiser tous les mots de passe
```bash
php bin/console app:reset-passwords
```

### Vérifier les utilisateurs
```bash
php bin/console doctrine:query:sql "SELECT username, email, enabled FROM users"
```

### Nettoyer le cache
```bash
php bin/console cache:clear
```

### Voir les routes disponibles
```bash
php bin/console debug:router | grep login
```

---

## Support

Si le problème persiste après avoir essayé toutes ces solutions, veuillez me fournir :
1. Le message d'erreur exact que vous voyez
2. Une capture d'écran si possible
3. Le navigateur que vous utilisez
4. Si vous vous connectez via web ou API

Je pourrai alors diagnostiquer le problème plus précisément.
