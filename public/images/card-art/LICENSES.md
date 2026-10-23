# Card art assets

Used by `App\Services\CardArt\CardArtPresenter` to draw gift card artwork for categories without a poster image.

| Folder | Source | Version | Licence |
|--------|--------|---------|---------|
| `flags/` | [flag-icons](https://github.com/lipis/flag-icons) (`flags/4x3/*.svg`) | 7.6.0 | MIT, (c) 2013 Panayiotis Lipiridis |
| `flags/globe.svg` | Drawn for this project (shown for "Global" cards) | — | Same as this repository |
| `brands/` | [Simple Icons](https://github.com/simple-icons/simple-icons) (`icons/*.svg`, recoloured white) | 16.35.0 | CC0 1.0 |

Brand logos are trademarks of their owners and are shown only to identify the gift card being sold.
Simple Icons publishes each brand's own usage guidelines; check them before using a logo outside this purpose.

To add a country: drop `flags/<iso2>.svg` from flag-icons and add it to `CardArtCatalog::COUNTRIES`.
To add a brand: drop a white `brands/<key>.svg` and add the key to `CardArtCatalog::BRANDS`.
