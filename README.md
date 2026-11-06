# Creche Manager API — Petite Lune

API métier de gestion des inscriptions et du séjour quotidien des enfants à la **Crèche Petite Lune** (de 3 mois à 4 ans).

## Documentation & Collection Postman

- **Récapitulatif d'architecture & correspondance PDF :** Voir [`RECAPITULATIF_PROJET.md`](./RECAPITULATIF_PROJET.md)
- **Collection Postman v2.1 :** Importer [`creche-manager-api.postman_collection.json`](./creche-manager-api.postman_collection.json)

## Commandes Rapides

```bash
# Migrer et charger la grille tarifaire officielle + données de démo
php artisan migrate:fresh --seed

# Exécuter les tests automatisés (SQLite :memory:)
php artisan test

# Démarrer le serveur de développement
php artisan serve --port=8000
```
