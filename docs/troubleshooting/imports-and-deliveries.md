# Imports And Deliveries

Troubleshoot provider imports, runtime visibility, and delivery profile confusion.

## Use This Page When

- a family imports successfully but does not appear live
- a family is visible in the library but not on the frontend
- you are unsure which delivery profile or publish state is in effect

## Steps

### 1. Confirm The Family Exists In The Library

Check whether the family is present and whether it is marked:

- `In Use`
- `Published`
- `In Library Only`

`In Library Only` keeps the family stored but out of runtime delivery.

### 2. Confirm The Active Delivery Profile

If a family has multiple delivery profiles, the active delivery profile controls what runtime uses. Confirm that the profile you expect is actually selected.

### 3. Confirm The Role Assignment

Even a valid published family will not affect live output unless:

- it is assigned to the relevant draft role
- and that draft has been applied sitewide when you expect live runtime output

### 4. Confirm Provider-Specific Preconditions

Examples:

- Google live search requires a valid API key
- Bunny self-hosted imports require valid provider download URLs
- Adobe requires a valid web project ID

## Notes

- Families can stay in the library for later use without becoming live immediately.
- Remote CDN deliveries and self-hosted deliveries both work within the same delivery profile model, but they produce different runtime asset behavior.
- If the library state looks correct but runtime still looks stale, continue with the generated CSS checks.

## Related Docs

- [Font Library](../font-library.md)
- [Generated CSS](generated-css.md)
- [Google Fonts](../providers/google-fonts.md)
- [Bunny Fonts](../providers/bunny-fonts.md)
- [Adobe Fonts](../providers/adobe-fonts.md)
