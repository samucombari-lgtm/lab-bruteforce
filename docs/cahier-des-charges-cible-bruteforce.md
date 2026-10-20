# Cahier des charges — Application cible pour l'étude du brute force

> **Document destiné à Claude Code.** Il décrit une application d'entraînement
> à construire entièrement : code, base de données, documentation. Claude Code
> a le libre accès pour créer les fichiers, la base et les jeux de données de test.

---

## 1. Contexte et cadre d'usage

### 1.1 Objectif pédagogique

Cette application est une **cible d'entraînement** créée dans le cadre d'un BTS SIO
option SLAM. Elle sert à :

1. comprendre comment fonctionne une **attaque par force brute** sur un formulaire
   de connexion (avec l'outil Hydra) ;
2. comprendre et mettre en œuvre les **protections** contre ce type d'attaque
   (limitation du nombre de tentatives, délai, verrouillage de compte,
   journalisation).

L'intérêt est double : jouer l'attaquant pour comprendre la menace, puis jouer
le défenseur pour la bloquer. C'est le cœur du travail.

### 1.2 Cadre d'exécution — OBLIGATOIRE

- L'application tourne **uniquement dans un laboratoire virtuel isolé**
  (réseau libvirt `lab-isole`, `192.168.100.0/24`, sans accès Internet).
- La cible est le poste de l'étudiant (`192.168.100.1`), l'attaquant est une
  VM de test (`192.168.100.12`).
- L'application ne doit **jamais** être déployée sur un réseau réel, public ou
  partagé. C'est une application volontairement faible : la diffuser serait
  dangereux.
- Elle affiche en permanence un bandeau d'avertissement rappelant ce cadre.

> Rappel légal : tester le brute force n'est autorisé que sur ses **propres**
> machines ou sur des cibles explicitement prévues pour ça. Toute autre cible
> relève des articles 323-1 et suivants du Code pénal.

---

## 2. Technologies imposées

| Élément | Choix | Raison |
|---|---|---|
| Langage / framework | **Laravel 12 / PHP 8.2+** | Déjà maîtrisé par l'étudiant, cohérent avec son autre projet |
| Base de données | **MariaDB** | Déjà installée et configurée sur le poste |
| Front | **Blade + CSS simple**, sans framework JS | Code lisible et maintenable sans dépendance lourde |
| Serveur | `php artisan serve` | Suffisant pour un labo |

> Contrainte de lisibilité : pas de bibliothèque ou de motif « haut niveau »
> que l'étudiant ne pourrait pas expliquer et maintenir lui-même. Chaque
> dépendance ajoutée doit être justifiée dans la documentation.

---

## 3. Périmètre fonctionnel

### 3.1 Une seule fonctionnalité : un formulaire de connexion

L'application se limite à un formulaire de connexion (identifiant + mot de passe)
et une page d'accueil accessible uniquement une fois connecté. Pas d'inscription,
pas de gestion de profil : ce serait hors sujet.

### 3.2 Deux modes de fonctionnement

L'application propose un **commutateur de mode**, pour comparer avant / après.

#### Mode A — « vulnérable »
- Aucune limite sur le nombre de tentatives de connexion.
- Aucun délai entre deux essais.
- Message d'erreur générique (« Identifiants incorrects »).
- C'est la cible du brute force avec Hydra.

#### Mode B — « protégé »
- **Limitation du débit** : au-delà de N tentatives échouées (paramétrable,
  5 par défaut) depuis la même adresse IP dans une fenêtre de temps (60 s par
  défaut), les tentatives suivantes sont refusées avec un code HTTP 429.
- **Verrouillage temporaire du compte** visé après X échecs (paramétrable).
- **Journalisation** de chaque tentative (voir §4.3).
- Message d'erreur toujours générique, pour ne pas indiquer si l'identifiant
  existe.

Le mode actif est stocké dans la configuration (`.env`), pas en dur dans le code.

### 3.3 Comptes de test à créer en base

Claude Code crée, via les *seeders* Laravel, des comptes volontairement simples
pour que l'exercice aboutisse en un temps raisonnable :

| Identifiant | Mot de passe | Rôle dans l'exercice |
|---|---|---|
| `admin` | `password` | Mot de passe très courant, trouvé vite |
| `samuel` | `123456` | Mot de passe numérique, cible d'un `crunch` |
| `test` | `azerty` | Présent dans les listes de mots courantes |

Ces mots de passe sont **stockés hachés** en base (hachage Laravel `bcrypt` par
défaut) : le brute force se fait côté formulaire, pas sur la base. Cela reste
réaliste.

---

## 4. Exigences techniques

### 4.1 Écoute réseau

- Par défaut, l'application écoute sur `0.0.0.0:8000` **pour permettre à la VM
  de l'atteindre**. C'est voulu et acceptable **parce que** le réseau est isolé.
- La documentation doit rappeler ce point et son cadre.

### 4.2 Modèle de données

Table `users` minimale :

| Colonne | Type | Rôle |
|---|---|---|
| `id` | entier auto | Clé primaire |
| `username` | chaîne, unique | Identifiant de connexion |
| `password` | chaîne | Mot de passe haché |
| `failed_attempts` | entier | Compteur d'échecs (mode protégé) |
| `locked_until` | horodatage, nullable | Fin du verrouillage (mode protégé) |

Table `login_attempts` (journal) :

| Colonne | Type | Rôle |
|---|---|---|
| `id` | entier auto | Clé primaire |
| `username` | chaîne | Identifiant essayé |
| `ip_address` | chaîne | Adresse source |
| `success` | booléen | Tentative réussie ou non |
| `created_at` | horodatage | Date et heure |

### 4.3 Journalisation

Chaque tentative de connexion (réussie ou non) est enregistrée dans
`login_attempts`. Une page `/journal`, accessible une fois connecté, affiche les
dernières tentatives : c'est ce qui permettra de **voir l'attaque Hydra en
direct** dans le mode vulnérable, puis de la voir être bloquée dans le mode
protégé.

### 4.4 Compatibilité avec Hydra

Le formulaire doit être simple à cibler avec la commande type :

```bash
hydra -l admin -P liste_mots.txt 192.168.100.1 -s 8000 \
      http-post-form "/login:username=^USER^&password=^PASS^:Identifiants incorrects"
```

Pour cela, le formulaire :
- utilise la **méthode POST** vers `/login` ;
- a des champs nommés exactement `username` et `password` ;
- renvoie, en cas d'échec, une page contenant la chaîne exacte
  **`Identifiants incorrects`** (c'est le marqueur d'échec pour Hydra).

> Note sur le jeton CSRF : Laravel protège les formulaires par un jeton CSRF,
> ce qui empêche Hydra de poster directement. Pour le mode vulnérable
> **uniquement**, la route `/login` doit être **exclue de la protection CSRF**
> (via le middleware), afin que l'exercice de brute force soit possible. Ce
> contournement doit être clairement commenté dans le code et expliqué dans la
> documentation comme un choix délibéré de laboratoire, jamais à reproduire en
> production. En mode protégé, c'est la limitation de débit — et non le CSRF —
> qui démontre la défense.

---

## 5. Documentation à produire (OBLIGATOIRE)

Claude Code doit livrer, en plus du code :

### 5.1 `README.md`
- Titre et court descriptif, avec un bandeau clair « cible d'entraînement —
  labo isolé uniquement ».
- Technologies utilisées et versions.
- Procédure d'installation complète (`composer install`, `.env`, `migrate --seed`,
  `serve`).
- Comment lancer chaque mode (A et B).
- Un exemple d'attaque Hydra et un exemple de résultat attendu dans chaque mode.
- Lien vers `docs/architecture.md`.

### 5.2 `docs/architecture.md`
Deux schémas **Mermaid** :

1. **Schéma fonctionnel** (compréhensible par un non-développeur) : l'attaquant
   (VM), la cible (appli), la base, le journal, et le flux d'une tentative de
   connexion.
2. **Schéma technique** : Laravel, routes (`/login`, `/journal`), contrôleur,
   middleware de limitation, modèles, tables MariaDB, le point d'écoute réseau.

Sous chaque schéma, une explication en quelques phrases de chaque bloc et des
choix techniques.

### 5.3 Qualité de code
- Noms de variables et de fonctions explicites (si un nom a besoin d'un
  commentaire pour être compris, il faut changer le nom).
- Commentaires réservés à ce que le code ne peut pas dire (le **pourquoi** d'un
  choix de labo, pas le **quoi**).
- Fonctions courtes, nommées et documentées.

---

## 6. Critères d'acceptation

L'application est considérée comme terminée quand :

1. Elle s'installe et démarre en suivant uniquement le `README.md`.
2. Les trois comptes de test existent en base avec mots de passe hachés.
3. **Mode A** : une attaque Hydra avec une petite liste de mots trouve au moins
   un mot de passe, et les tentatives apparaissent dans `/journal`.
4. **Mode B** : la même attaque est bloquée (réponses 429 / compte verrouillé),
   et le journal montre l'arrêt des tentatives.
5. Les deux schémas Mermaid s'affichent correctement et sont cohérents avec le
   code livré.
6. Le bandeau d'avertissement « labo isolé » est présent sur toutes les pages.

---

## 7. Hors périmètre

- Aucune inscription d'utilisateur ni réinitialisation de mot de passe.
- Aucun déploiement en ligne, aucun hébergement hors du labo.
- Aucune autre faille ajoutée volontairement (injection SQL, XSS…) : le sujet
  est le brute force et sa protection, rien d'autre. On garde l'exercice ciblé.
