# Cible Brute Force — application d'entraînement

> ⚠️ **CIBLE D'ENTRAÎNEMENT — LABORATOIRE ISOLÉ UNIQUEMENT.**
> Cette application est **volontairement vulnérable**. Elle ne doit **jamais**
> être déployée sur un réseau réel, public ou partagé. Elle est conçue pour
> tourner dans un laboratoire virtuel isolé (réseau `lab-isole`,
> `192.168.100.0/24`, sans accès Internet).
>
> Rappel légal : tester le brute force n'est autorisé que sur ses **propres**
> machines ou sur des cibles explicitement prévues pour cela. Toute autre cible
> relève des articles 323-1 et suivants du Code pénal.

Application cible, développée dans le cadre d'un BTS SIO option SLAM, pour
étudier concrètement :

1. une **attaque par force brute** sur un formulaire de connexion (avec Hydra) ;
2. les **protections** contre ce type d'attaque (limitation du débit,
   verrouillage de compte, journalisation).

L'application propose **deux modes** que l'on compare avant / après :

| Mode | Nom | Comportement |
|---|---|---|
| **A** | `vulnerable` | Aucune limite, aucun délai : cible du brute force. |
| **B** | `protege` | Limitation du débit (HTTP 429), verrouillage de compte, journal. |

Le mode actif se règle dans le fichier `.env`, jamais en dur dans le code.

---

## Technologies utilisées

| Élément | Choix | Version |
|---|---|---|
| Framework | Laravel | 12.x (testé avec 12.70) |
| Langage | PHP | 8.2+ (testé avec 8.4) |
| Base de données | MariaDB | 10.6+ (testé avec 11.8) |
| Front | Blade + CSS simple | sans framework JS |
| Serveur de dev | `php artisan serve` | — |

Aucune dépendance « haut niveau » supplémentaire n'a été ajoutée : le code
n'utilise que des briques standard de Laravel (routage, Blade, Eloquent, session,
middleware), afin de rester lisible et maintenable.

---

## Installation

> Prérequis : PHP 8.2+, Composer, MariaDB installés et le service MariaDB démarré.

### 1. Récupérer les dépendances

```bash
composer install
```

### 2. Créer la base et l'utilisateur MariaDB

Un script SQL est fourni. Il crée la base `lab_bruteforce` et l'utilisateur
`lab_user` (identifiants utilisés par défaut dans `.env`) :

```bash
sudo mariadb < database/sql/00-creer-base.sql
```

> Vous pouvez choisir d'autres identifiants : adaptez alors le script SQL
> **et** les lignes `DB_*` du fichier `.env`.

### 3. Configurer l'environnement

```bash
cp .env.example .env          # si le .env n'existe pas déjà
php artisan key:generate      # génère la clé APP_KEY
```

Vérifiez dans `.env` les lignes `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
ainsi que `LAB_MODE`.

### 4. Créer les tables et les comptes de test

```bash
php artisan migrate --seed
```

Cela crée les tables `users` et `login_attempts`, puis les trois comptes de test.

### 5. Démarrer le serveur

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

> **Écoute sur `0.0.0.0:8000`** : c'est volontaire, pour que la VM attaquante
> (`192.168.100.12`) puisse atteindre la cible (`192.168.100.1`). C'est
> acceptable **uniquement parce que le réseau est isolé**.

L'application est alors disponible sur `http://192.168.100.1:8000/login`.

---

## Comptes de test

Mots de passe volontairement simples et courants (imposés par le sujet pour que
l'exercice aboutisse), stockés **hachés** en base (bcrypt) :

| Identifiant | Mot de passe |
|---|---|
| `admin` | `password` |
| `samuel` | `123456` |
| `test` | `azerty` |

---

## Choisir le mode (A ou B)

Le mode se règle dans `.env` :

```ini
# Mode A — vulnérable (cible du brute force)
LAB_MODE=vulnerable

# Mode B — protégé (limitation de débit, verrouillage, journal)
LAB_MODE=protege
```

Après modification du `.env`, vider le cache de configuration puis relancer :

```bash
php artisan config:clear
php artisan serve --host=0.0.0.0 --port=8000
```

Les seuils du Mode B sont aussi réglables dans `.env` :

| Variable | Rôle | Défaut |
|---|---|---|
| `LAB_MAX_TENTATIVES_IP` | Échecs tolérés par IP avant blocage 429 | 5 |
| `LAB_FENETRE_SECONDES` | Fenêtre de comptage des échecs par IP | 60 |
| `LAB_MAX_TENTATIVES_COMPTE` | Échecs avant verrouillage du compte | 5 |
| `LAB_DUREE_VERROUILLAGE_MINUTES` | Durée du verrouillage | 15 |
| `LAB_TAILLE_JOURNAL` | Nombre de tentatives affichées dans /journal | 50 |

---

## Exemple d'attaque Hydra

Depuis la VM attaquante, avec une petite liste de mots contenant notamment
`password`, `123456` et `azerty` :

```bash
hydra -l admin -P liste_mots.txt 192.168.100.1 -s 8000 \
      http-post-form "/login:username=^USER^&password=^PASS^:Identifiants incorrects"
```

- `-l admin` : identifiant ciblé (ou `-L liste_identifiants.txt` pour en tester plusieurs).
- `-P liste_mots.txt` : liste de mots de passe à essayer.
- La chaîne finale `Identifiants incorrects` est le **marqueur d'échec** : tant
  qu'une réponse la contient, Hydra considère la tentative comme ratée.

### Résultat attendu

**Mode A (`vulnerable`)** : Hydra essaie tous les mots sans entrave et finit par
trouver le mot de passe. Exemple de sortie :

```
[8000][http-post-form] host: 192.168.100.1   login: admin   password: password
1 of 1 target successfully completed, 1 valid password found
```

La page `/journal` montre alors une longue rafale de tentatives (succès + échecs)
venant de l'IP de l'attaquant.

**Mode B (`protege`)** : au-delà de 5 échecs par IP en 60 secondes, les requêtes
suivantes reçoivent un code **HTTP 429** et le compte visé finit **verrouillé**.
Hydra ne trouve plus le mot de passe (ou signale des erreurs de réponse), et la
page `/journal` montre que les tentatives **s'arrêtent** une fois le seuil atteint.

---

## Documentation d'architecture

Les schémas fonctionnel et technique (Mermaid) sont dans
[docs/architecture.md](docs/architecture.md).

---

## Équipe et licence

- **Auteur** : COMBARI (étudiant BTS SIO SLAM).
- **Cadre** : projet pédagogique, usage en laboratoire isolé uniquement.
- **Licence** : usage pédagogique interne. Diffusion déconseillée (application
  volontairement vulnérable).
