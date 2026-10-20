# Résultats des tests — attaque par force brute et défense

| | |
|---|---|
| **Auteur** | Samuel Combari — BTS SIO option SLAM |
| **Date** | 20 octobre 2026 |
| **Projet** | `lab-bruteforce` — cible d'entraînement au brute force |
| **Outil d'attaque** | Hydra 9.6 |
| **Cadre** | Laboratoire local isolé, cible et attaquant sur la même machine Kali (`127.0.0.1`) |

> Rappel : ces tests ne sont menés que sur une application d'entraînement, sur
> ma propre machine. Toute attaque par force brute sur un système tiers sans
> autorisation écrite est interdite (articles 323-1 et suivants du Code pénal).

---

## 1. Objectif

Démontrer, sur un même formulaire de connexion :

1. qu'une application **sans protection** cède rapidement à une attaque par
   force brute (mode A) ;
2. qu'une **limitation de débit** bloque la même attaque (mode B).

L'application possède deux modes, choisis dans le fichier `.env`
(`LAB_MODE=vulnerable` ou `LAB_MODE=protege`), sans modification du code.

---

## 2. Paramètres de protection (mode B)

Extraits de `config/lab.php` :

| Paramètre | Valeur | Rôle |
|---|---|---|
| `max_tentatives_ip` | 5 | Nombre d'échecs autorisés par adresse IP avant blocage |
| `fenetre_secondes` | 60 | Fenêtre de temps pour compter ces échecs |
| `max_tentatives_compte` | 5 | Échecs consécutifs avant verrouillage du compte visé |
| `duree_verrouillage_minutes` | 15 | Durée du verrouillage du compte |

Au-delà de `max_tentatives_ip` échecs, le serveur répond **HTTP 429
(Too Many Requests)** avec le message « Trop de tentatives depuis cette
adresse. Réessayez plus tard. ».

---

## 3. Correctif préalable : code de réponse sur échec

Lors des premiers essais, l'application renvoyait un code **HTTP 401** sur
chaque échec de connexion. Ce code est destiné à l'authentification HTTP ou aux
API, pas à un formulaire web. Hydra, qui attend un code 200 pour un formulaire,
était perturbé et renvoyait de **faux résultats**.

La réponse d'échec a été corrigée pour renvoyer **HTTP 200** (comportement
standard d'un formulaire : réafficher la page avec le message d'erreur).

```php
// app/Http/Controllers/ConnexionController.php — réponse d'échec
// Avant : Response::HTTP_UNAUTHORIZED  (401)
// Après : Response::HTTP_OK            (200)
```

**Leçon :** un résultat d'outil automatique doit toujours être recoupé. Le code
de réponse HTTP influence directement la fiabilité de la détection.

---

## 4. Mode A — application vulnérable

### Commande

```bash
hydra -l admin -P ~/mots.txt -t 1 -f 127.0.0.1 -s 8000 \
  http-post-form "/login:username=^USER^&password=^PASS^:Identifiants incorrects" -V
```

| Option | Rôle |
|---|---|
| `-l admin` | Identifiant visé |
| `-P ~/mots.txt` | Liste de mots de passe à essayer |
| `-t 1` | Une tentative à la fois (résultat déterministe) |
| `-f` | S'arrête dès qu'un mot de passe valide est trouvé |
| `http-post-form "…"` | Formulaire POST, avec le texte « Identifiants incorrects » comme marqueur d'échec |

### Résultat

```
[ATTEMPT] login "admin" - pass "password" - 5 of 8
[8000][http-post-form] host: 127.0.0.1   login: admin   password: password
1 valid password found
```

**Sans protection, Hydra trouve le mot de passe `password` en 5 tentatives.**
Vérifié par connexion réelle : `admin` / `password` fonctionne.

---

## 5. Mode B — application protégée

Pour prouver le blocage, le vrai mot de passe a été placé **en 14ᵉ position**
de la liste, bien au-delà de la limite de 5 tentatives.

### Preuve directe avec curl

```bash
for i in 1 2 3 4 5 6 7; do
  curl -s -o /dev/null -w "tentative $i -> HTTP %{http_code}\n" \
    -d "username=admin&password=faux" http://127.0.0.1:8000/login
done
```

```
tentative 1 -> HTTP 200
tentative 2 -> HTTP 429
tentative 3 -> HTTP 429
tentative 4 -> HTTP 429
...
```

Après les premières tentatives, le serveur refuse toutes les suivantes avec un
code **429**. C'est la preuve la plus nette de la limitation de débit.

### Attaque Hydra

Même commande qu'au mode A. Résultat :

```
[ATTEMPT] login "admin" - pass "qwerty" - 6 of 14
[8000][http-post-form] host: 127.0.0.1   login: admin   password: qwerty
```

- Tentatives 1 à 5 → échecs normaux (HTTP 200) ;
- Tentative **6** → HTTP 429. Hydra ne connaît pas le message de blocage et le
  prend pour un succès : il annonce `qwerty`, un **faux positif** ;
- Le vrai mot de passe (`password`, 14ᵉ position) **n'est jamais atteint**.

Vérification : `admin` / `qwerty` **ne fonctionne pas**. La page `/journal`
montre 5 tentatives puis l'arrêt de l'attaque.

Le blocage tombe exactement à la 6ᵉ tentative (`max_tentatives_ip` = 5, donc
5 autorisées puis blocage), conformément à la configuration.

---

## 6. Synthèse

| Critère | Mode A (vulnérable) | Mode B (protégé) |
|---|---|---|
| Réponse HTTP sur échec | 200 | 200, puis **429** après 5 échecs |
| Résultat de l'attaque | `password` trouvé (vrai) | bloqué, faux positif `qwerty` |
| Mot de passe réel | **trouvé en 5 essais** | **jamais atteint** |
| Preuve | sortie Hydra + connexion | codes curl 200→429 + journal |

---

## 7. Conclusions

1. **Un formulaire sans limitation est trivial à casser.** Avec un mot de passe
   faible (`password`), quelques essais suffisent.
2. **La limitation de débit est une défense efficace et simple.** En refusant
   les requêtes après 5 échecs, elle empêche l'attaque d'aboutir, même avec un
   mot de passe faible.
3. **Les outils automatiques peuvent mentir.** Hydra a renvoyé des faux positifs
   face au code 429 ; seule la vérification manuelle (connexion réelle, journal,
   codes HTTP) donne la vérité. C'est un réflexe d'analyse essentiel.
4. **Défense en profondeur.** L'application combine deux protections :
   limitation par IP (429) et verrouillage du compte visé. L'une rattrape
   l'autre.

## 8. Recommandations générales (au-delà du labo)

- Imposer des mots de passe forts (longueur, complexité).
- Compléter la limitation de débit par un **CAPTCHA** après plusieurs échecs.
- **Journaliser et alerter** sur les pics de tentatives échouées.
- Ne jamais indiquer si c'est l'identifiant ou le mot de passe qui est faux
  (message d'erreur générique, déjà en place ici).
