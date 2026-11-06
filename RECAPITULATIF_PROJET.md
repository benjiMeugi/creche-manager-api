# 🌙 Récapitulatif Complet — `creche-manager-api` (Crèche Petite Lune)

**Projet :** API Métier de gestion des inscriptions et du séjour des enfants à la crèche (de 3 mois à 4 ans)  
**Emplacement :** `/Users/benjliksoft/Documents/bendev/laravel/creche-manager-api`  
**Stack & Architecture :** Laravel 12 + `benjimeugi/laravel-api-requests-parsers` + `authentication-api`

---

## 1. Alignement à 100% avec ton Écosystème (`employe-api` & `authentication-api`)

Pour que tu sois immédiatement chez toi dans le code de `creche-manager-api`, l'architecture reprend **exactement** tes conventions :

1. **Package `benjimeugi/laravel-api-requests-parsers`** :
   - Tous les modèles implémentent `BenjiMeugi\Contracts\IModel` (`rules()`, `update_rules()`, `getMigrateKey()`, `$relation_methods`).
   - Tous les contrôleurs passent par `App\Http\Controllers\Repository\Repository` qui orchestre `BenjiMeugi\Parser` :
     * Filtrage dynamique avancé via `?query_={"where":..., "with":..., "whereHas":...}`.
     * Création imbriquée transactionnelle (`HasOne` / `HasMany`) en un seul appel `POST`.
     * Suppression en cascade via `delete_relations`.
2. **Format de réponse JSON standardisé (`ApiResponse` + `bootstrap/app.php`)** :
   - Toutes les réponses (`200`, `201`, `401`, `403`, `404`, `405`, `422`, `500`) retournent la structure :
     ```json
     {
       "success": true,
       "code": 200,
       "message": null,
       "body": {
         "error_message": null,
         "errors": null,
         "response_data": {}
       }
     }
     ```
3. **Sécurité & Permissions Centralisées (`authentication-api`)** :
   - `VerifyAuthApiToken` vérifie le token Bearer auprès de `GET {AUTH_API_BASE_URL}/api/validate-token` et injecte `auth_user` dans la `Request`.
   - `CheckAbility` (alias `ability`) vérifie chaque permission (`{ressource}-{action}`, ex: `child-create`, `registration-approve`, `child_event-create`) directement dans `auth_user.permissions`.
   - `routes/api.php` utilise tes helpers `resolveAbility()` et `registerApiCrud()`.

---

## 2. Correspondance Fiche PDF Petite Lune $\leftrightarrow$ Modèles & Endpoints

| Section du PDF | Contenu Métier | Modèle / Table | Endpoints Principaux |
| :--- | :--- | :--- | :--- |
| **I. Infos Enfant** | Nom, prénoms, date/lieu de naissance, âge, sexe (`M`/`F`), langues parlées | `Child` (`children`) | `CRUD /api/child`<br/>`GET /api/child/{id}/dossier` |
| **II. Parents / Tuteur** | Père, Mère, Tuteur légal (nom, profession, téléphone, adresse) | `Guardian` (`guardians`) | `CRUD /api/guardian` (ou imbriqué dans `POST /api/child`) |
| **III. Contact d'urgence** | Nom, lien avec l'enfant, téléphone | `Guardian` (`role = emergency_contact`) | `CRUD /api/guardian` |
| **IV. Infos Médicales** | Médecin traitant, vaccins à jour, allergies, maladies, traitements | `ChildMedicalProfile` (`child_medical_profiles`) | `CRUD /api/child_medical_profile` |
| **V. Habitudes & Besoins** | Alimentation (lait, allergies), sommeil, propreté, comportement | `Child` (`dietary_habits`, `sleep_habits`, `other_useful_info`) | Rattaché à `Child` pour évoluer durant tout le séjour |
| **VI. Autorisations** | Premiers soins d'urgence, photos/vidéos, récupération par tiers | `Registration` (`consent_*`) | `CRUD /api/registration` |
| **VII. Pièces à joindre** | Acte naissance, carnet vaccins, certificat médical, 2 photos | `Attachment` (`attachments` polymorphique) | `POST /api/attachment/registration/{id}`<br/>`GET /api/attachment/{id}/download` |
| **VIII. Partie Financière** | Grille 4 tranches d'âge (`50 000 FCFA` + 4 tranches : Inscription, 30 Avril, 31 Juillet, 31 Octobre) + Repas (`1 500 FCFA/j`) + Garderie (`2 000 FCFA/h`) | `Section`, `TuitionInstallment`, `AdditionalCharge` | `CRUD /api/section`<br/>`PUT /api/tuition_installment/{id}/pay`<br/>`PUT /api/additional_charge/{id}/pay` |
| **IX. Horaires** | Lundi–Vendredi `07h30–18h30`, facturation auto au-delà de `18h30` | `Attendance` (`attendances`) | `POST /api/attendance/check_in`<br/>`PUT /api/attendance/{id}/check_out` |
| **X. Engagement Parents** | Déclarant, lieu, date de signature, acceptation du règlement | `Registration` (`declarant_name`, `signed_at_place`, `signed_date`) | `CRUD /api/registration` |
| **XI. Réservé Admin** | Numéro d'inscription (`PL-2026-XXXX`), date d'admission, section, observations | `Registration` | `PUT /api/registration/{id}/approve`<br/>`PUT /api/registration/{id}/reject` |
| **Séjour & Suivi Différé** | Repas, siestes, couches/hygiène, petits soins, demandes de fournitures (couches/lingettes), activités | `ChildEvent` (`child_events`) | `CRUD /api/child_event`<br/>`GET /api/child/{id}/timeline`<br/>`PUT /api/child_event/{id}/fulfill_supply` |

---

## 3. Automatisations Métier Implémentées

1. **Inscription complète en 1 seul appel (`POST /api/child`)** :
   - Grâce à `BenjiMeugi\Parser`, envoyer un `Child` contenant les clés `guardians`, `medicalProfile` et `registrations` crée toute la hiérarchie dans une transaction unique.
   - Le matricule enfant (`ENF-2026-XXXX`) et la section d'âge (`3-12_months`, `1-2_years`, `2-3_years`, `3-4_years`) sont calculés automatiquement à partir de `birth_date` si non fournis.
2. **Approbation Administrative & Génération de l'Échéancier (`PUT /api/registration/{id}/approve`)** :
   - Passe le dossier à `approved` et l'enfant à `active`.
   - Génère automatiquement les **5 lignes dans `tuition_installments`** selon les tarifs de la section de l'enfant (`50 000 FCFA` d'inscription + 4 tranches aux dates officielles : Inscription, 30 Avril, 31 Juillet, 31 Octobre).
3. **Détection et Facturation Automatique des Dépassements après 18h30 (`PUT /api/attendance/{id}/check_out`)** :
   - Si `check_out_time > 18:30`, calcule automatiquement `overtime_minutes`, `overtime_hours_billed` (toute heure entamée) et `overtime_amount` (`2 000 FCFA / heure`), et crée automatiquement la ligne correspondante dans `additional_charges` (`aftercare_hourly`).
4. **Suivi Différé Parent & Gestion des Fournitures (`GET /api/child/{id}/timeline`)** :
   - Retourne les événements de la journée (`meal`, `nap`, `hygiene`, `health`, `activity`), les pointages et la liste des demandes de réapprovisionnement non encore satisfaites (`pending_supply_requests`, ex: manque de couches/lingettes).

---

## 4. Permissions à ajouter dans `authentication-api` (`PermissionSeeder.php`)

Pour déclarer les permissions de `creche-manager-api` dans ton `authentication-api`, voici le bloc prêt à ajouter dans `authentication-api/database/seeders/PermissionSeeder.php` :

```php
$crecheCrudResources = [
    'section',
    'child',
    'guardian',
    'child_medical_profile',
    'registration',
    'attendance',
    'child_event',
    'tuition_installment',
    'additional_charge',
];

$crecheSpecificPermissions = [
    // child
    ['label' => 'child-timeline', 'description' => '[Creche API] Consulter la timeline de suivi différé d’un enfant'],

    // registration
    ['label' => 'registration-approve', 'description' => '[Creche API] Approuver un dossier d’inscription et générer l’échéancier'],
    ['label' => 'registration-reject', 'description' => '[Creche API] Rejeter un dossier d’inscription'],

    // attendance
    ['label' => 'attendance-check_in', 'description' => '[Creche API] Pointer l’arrivée d’un enfant'],
    ['label' => 'attendance-check_out', 'description' => '[Creche API] Pointer le départ d’un enfant'],

    // tuition_installment & additional_charge
    ['label' => 'tuition_installment-pay', 'description' => '[Creche API] Enregistrer le paiement d’une tranche de scolarité'],
    ['label' => 'additional_charge-pay', 'description' => '[Creche API] Enregistrer le paiement d’un service optionnel'],

    // attachment
    ['label' => 'attachment-list', 'description' => '[Creche API] Lister les pièces jointes'],
    ['label' => 'attachment-show', 'description' => '[Creche API] Voir une pièce jointe'],
    ['label' => 'attachment-create', 'description' => '[Creche API] Ajouter une pièce jointe'],
    ['label' => 'attachment-update', 'description' => '[Creche API] Mettre à jour une pièce jointe'],
    ['label' => 'attachment-delete', 'description' => '[Creche API] Supprimer une pièce jointe'],
    ['label' => 'attachment-download', 'description' => '[Creche API] Télécharger une pièce jointe'],
];
```

---

## 5. Comment démarrer et tester avec Postman

1. **Initialiser la base de données avec les 4 sections du PDF et les données de démo** :
   ```bash
   cd /Users/benjliksoft/Documents/bendev/laravel/creche-manager-api
   php artisan migrate:fresh --seed
   ```
2. **Lancer la suite de tests automatisés PHPUnit** :
   ```bash
   php artisan test
   ```
3. **Tester avec Postman** :
   - Importer le fichier `/Users/benjliksoft/Documents/bendev/laravel/creche-manager-api/creche-manager-api.postman_collection.json` dans Postman.
   - Lancer `php artisan serve --port=8000`.
   - Exécuter la requête `01 - Auth & Permissions -> 1. Login sur authentication-api` (elle met automatiquement à jour la variable `{{token}}`), puis tester tous les dossiers.
