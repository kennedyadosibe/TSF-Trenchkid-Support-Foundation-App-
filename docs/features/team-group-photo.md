# Team Group Photo

## Summary

The public Team page now includes a large team group photo section before the leadership/team briefing.

## Admin Editing

Admins can edit the section from the dashboard under `Team Page`:

- `Large Team Group Photo 1` uploads or replaces the first public group image.
- `Large Team Group Photo 2` uploads or replaces the second public group image.
- `Group Photo Title` edits the section heading.
- `Group Photo Brief` edits the supporting text.

Uploaded images use the existing page-image upload flow and are stored in `images/page/`.

The current fallback images are generated collages saved at `images/page/team-group-generated.jpg` and `images/page/team-group-generated-2.jpg`, built from provided TSF team photos. Admins can replace either one from the dashboard at any time.

## Public Rendering

The Team page uses `data-setting-src="team_group_image"` so the shared settings loader can place the uploaded image into a normal `<img>` element. If no image has been uploaded yet, the page shows a styled upload-ready placeholder instead of a broken image.

## Test

Open `team.html`, confirm the two-photo group section appears before the leadership cards, then upload Team group images from the admin dashboard and verify each one replaces its matching fallback.
