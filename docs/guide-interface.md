# Guide d’interface

## Charte

- **Bleu nuit `#17324d`** : navigation, titres et actions principales.
- **Vert `#237a57`** : succès, conformité et actions positives.
- **Ambre `#9a6700`** : avertissements et règles à confirmer.
- **Rouge `#b42318`** : erreurs bloquantes et actions dangereuses.
- **Gris neutres** : surfaces, bordures et textes secondaires.

Les valeurs sont définies dans [`public/assets/css/app.css`](../public/assets/css/app.css).
L’interface n’utilise aucun CDN et fonctionne hors ligne.

## Structure des vues

- `app/Views/layouts/main.php` : layout HTML commun.
- `app/Views/components/` : composants partagés à ajouter pour les blocs
  réutilisables.
- `app/Views/home/`, `societe/`, `dossier/` : écrans métier.
- `app/Views/pdf/` : gabarits Dompdf ; leur CSS est dans
  `public/assets/css/pdf.css`.
- `app/Views/_layout.php` : compatibilité historique ; les nouvelles vues
  doivent étendre directement `layouts/main`.

## Classes principales

| Classe | Usage |
|---|---|
| `.card`, `.panel` | Surface de contenu |
| `.button`, `.button.secondary`, `.button.success`, `.button.danger` | Actions |
| `.alert`, `.alert-info`, `.alert-success`, `.alert-error` | Messages |
| `.status-badge` et variantes | Statuts métier |
| `.table-wrap` | Tableau défilable sur mobile |
| `.tabs`, `.stepper`, `.step` | Navigation et progression |
| `.field`, `.help`, `.error-text` | Formulaire accessible |
| `.empty-state` | État sans données |

## Conventions

1. Une vue HTML étend `layouts/main` et fournit une section `content`.
2. Les sorties dynamiques sont toujours échappées avec `esc()`.
3. Chaque formulaire POST contient `csrf_field()`.
4. Les comportements communs vont dans `public/assets/js/app.js`.
5. Les lignes dynamiques utilisent `data-add`, `data-row` et `data-remove`.
6. Aucun `style=""`, `<style>` ou `<script>` inline dans les écrans HTML.
7. Les dates et montants fournis par les services ne sont pas recalculés dans
   les vues.
8. Les PDF utilisent uniquement CSS 2.1 compatible Dompdf : tableaux et blocs,
   sans flex ni grid.
