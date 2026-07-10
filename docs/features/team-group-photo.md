# Team Group Photo

## Summary

The public Team page now includes a large team group photo section before the leadership/team briefing.

## Admin Editing

Admins can edit the section from the dashboard under `Team Page`:

- `Large Team Group Photo` uploads or replaces the public group image.
- `Group Photo Title` edits the section heading.
- `Group Photo Brief` edits the supporting text.

Uploaded images use the existing page-image upload flow and are stored in `images/page/`.

## Public Rendering

The Team page uses `data-setting-src="team_group_image"` so the shared settings loader can place the uploaded image into a normal `<img>` element. If no image has been uploaded yet, the page shows a styled upload-ready placeholder instead of a broken image.

## Test

Open `team.html`, confirm the group photo section appears before the leadership cards, then upload a Team group image from the admin dashboard and verify it replaces the placeholder.
