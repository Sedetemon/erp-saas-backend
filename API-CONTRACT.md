# Contrat d'API — erp-saas-backend (module Hôtel)

Document de référence pour le client Flutter. Reflète l'état réel de l'API
au 08/08/2026, vérifié en conditions réelles (Postman/Thunder Client).

## Base

```
Base URL : http://erp-saas-backend.test/api
```

**Headers obligatoires sur TOUTES les requêtes tenant :**
```
X-Tenant: <slug-ou-id-du-tenant>       # ex: neuvieme-entreprise
Authorization: Bearer <token>          # sauf /auth/login
Accept: application/json
Content-Type: application/json         # pour POST/PUT
```

Sans `X-Tenant` → `400`. Tenant introuvable/inactif → `404`/`403`.
Token absent/invalide sur route protégée → `401`.
Module hôtel non activé pour ce tenant → `403`.

## Authentification

```
POST /auth/login
Body: { "email": string, "password": string }
→ 200 { "user": {id, name, email}, "token": "3|xxxxx..." }
```
Le token est au format Sanctum `{id}|{valeur}` — le stocker tel quel,
le renvoyer tel quel dans `Authorization: Bearer <token>`.

```
GET  /auth/me       (auth:sanctum)  → 200 { id, name, email }
POST /auth/logout   (auth:sanctum)  → 204
```

Pas de refresh token pour l'instant — à la 401, renvoyer vers le login.
Pas de "remember me" / durée de vie longue configurée.

## Format des réponses

**Une seule ressource :**
```json
{ "data": { ...champs... } }
```

**Liste paginée (Laravel `paginate()`) :**
```json
{
  "data": [ ... ],
  "links": { "first", "last", "prev", "next" },
  "meta": { "current_page", "from", "last_page", "path", "per_page", "to", "total" }
}
```

**Erreur de validation (422) :**
```json
{ "message": "...", "errors": { "champ": ["message"] } }
```

**Suppression réussie :** `204 No Content`, corps vide.

## Sémantique importante (Reservation)

- `total` = **toutes** les charges du séjour (nuitées + POS facturé sur la
  chambre + toute future source de charge), PAS juste les chambres.
- `balance` = `total - paiements`. `balance > 0` = client doit de l'argent.
  `balance` peut être négatif en cas de trop-perçu (à éviter côté UI, ne
  devrait plus arriver après nettoyage des tests).
- `ledgers` = historique complet débits/crédits du séjour (le "folio").
- Une réservation peut avoir plusieurs `invoices`, mais en usage normal
  il n'y en a qu'une par séjour (celle créée à `store()`, alimentée
  ensuite par le POS `room_charge`).

## Endpoints — Module Hôtel

Tous sous `/hotel`, `auth:sanctum` + module actif requis.

### Types de chambres
```
GET    /hotel/room-types                          liste (paginée)
POST   /hotel/room-types                          { name, code, base_price, capacity_adults, capacity_children?, description?, amenities?[] }
GET    /hotel/room-types/{id}
PUT    /hotel/room-types/{id}
DELETE /hotel/room-types/{id}                      → 204
GET    /hotel/room-types/{id}/availability?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD
                                                    → liste des chambres libres sur la période
```

### Chambres
```
GET    /hotel/rooms?status=&room_type_id=          liste (paginée)
POST   /hotel/rooms                                { room_type_id, number, floor?, status? }
GET    /hotel/rooms/{id}
PUT    /hotel/rooms/{id}
DELETE /hotel/rooms/{id}
```
`status` : `available` | `occupied` | `cleaning` | `maintenance`

### Clients (guests)
```
GET    /hotel/guests?search=                       liste (paginée)
POST   /hotel/guests                               { first_name, last_name, email?, phone?, nationality?, document_type?, document_number?, address? }
GET    /hotel/guests/{id}
PUT    /hotel/guests/{id}
DELETE /hotel/guests/{id}
```

### Réservations
```
GET    /hotel/reservations?status=&guest_id=        liste (paginée)
POST   /hotel/reservations
  Body: {
    guest_id, check_in_date, check_out_date,
    rooms: [{ room_type_id, room_id? }],           // room_id optionnel = auto-assignation
    adults?, children?, source?, notes?
  }
  → 201, crée aussi une facture "draft" automatiquement
GET    /hotel/reservations/{id}                     détail complet (guest, rooms, invoices, ledgers)

POST   /hotel/reservations/{id}/check-in            → statut "checked_in"

POST   /hotel/reservations/{id}/check-out
  Body: { payment_method?, force? }
  - solde = 0 → check-out simple
  - solde > 0 + payment_method → règle le solde puis check-out
  - solde > 0 + rien → 422 (bloqué)
  - solde > 0 + force:true → check-out avec solde impayé (facturation différée)

POST   /hotel/reservations/{id}/cancel
  - si des paiements existent déjà → 422 (rembourser manuellement d'abord)
  - sinon → annule aussi les charges en attente (solde repasse à 0),
    la/les facture(s) draft passent à "cancelled"

POST   /hotel/reservations/{id}/payments
  Body: { amount, payment_method, reference? }     // acompte ou règlement partiel
  → 201

GET    /hotel/reservations/{id}/ledger              folio complet (débits/crédits)
```

### Factures
```
GET    /hotel/invoices?status=&guest_id=            liste (paginée)
GET    /hotel/invoices/{id}
POST   /hotel/invoices/{id}/issue                   draft → issued
POST   /hotel/invoices/{id}/payments                paiement direct hors flux réservation
```

### Housekeeping
```
GET    /hotel/housekeeping-tasks?status=&room_id=&assigned_to=
POST   /hotel/housekeeping-tasks                    { room_id, type, assigned_to?, notes? }
POST   /hotel/housekeeping-tasks/{id}/assign         { assigned_to }
POST   /hotel/housekeeping-tasks/{id}/start
POST   /hotel/housekeeping-tasks/{id}/complete       (remet la chambre "available" si nettoyage)
```
Une tâche `checkout_cleaning` est créée automatiquement à chaque check-out.

### POS restaurant/bar
```
GET/POST/PUT/DELETE  /hotel/pos/categories[/{id}]
GET/POST/PUT/DELETE  /hotel/pos/products[/{id}]      { pos_category_id, name, price, sku? }
GET/POST/PUT/DELETE  /hotel/pos/tables[/{id}]

GET    /hotel/pos/orders?status=
POST   /hotel/pos/orders                             { type, pos_table_id?, guest_id?, reservation_id? }
GET    /hotel/pos/orders/{id}
POST   /hotel/pos/orders/{id}/items                  { pos_product_id, quantity?, notes? }
DELETE /hotel/pos/orders/{id}/items/{itemId}
POST   /hotel/pos/orders/{id}/send-to-kitchen
POST   /hotel/pos/orders/{id}/serve
POST   /hotel/pos/orders/{id}/close                  { payment_method }
  - payment_method: cash|card|mobile_money → encaissement direct
  - payment_method: room_charge → imputé sur la facture du séjour (reservation_id requis)
```

## Points connus, non résolus (à garder en tête côté Flutter)

- `Invoice.balance_due` reste égal au total même sur une facture
  `cancelled` (le montant historique est gardé, seul le statut change).
  Ne pas afficher `balance_due` comme "à payer" si `status === "cancelled"`.
- Type de ledger `refund` existe en base mais n'est pas encore compté
  dans le calcul de `balance` — ne pas l'utiliser pour l'instant.
- Pas de pagination configurable (taille de page fixe côté serveur,
  20 ou 30 selon l'endpoint) — pas de paramètre `per_page` exposé.
