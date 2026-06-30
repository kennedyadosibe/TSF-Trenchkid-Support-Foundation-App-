# TSF App Agent Rules

These rules guide every coding session in this project.

## Git Workflow

- If git is not enabled in this folder, initialize it before changing code.
- Maintain two primary branches:
  - `dev` is for testing, active changes, and review.
  - `main` is for official approved code.
- Do feature work on `dev`.
- Commit every completed, testable change to `dev`.
- Move work to `main` only when the user approves it as official code.
- Do not discard or overwrite user changes without explicit permission.

## Documentation

- Create or update a document in `docs/features/` for every new feature added.
- Create or update a progress note in `progress/` for meaningful project progress.
- Keep docs practical: what changed, where it lives, how to test it, and any setup requirements.

## Research

- Read official online documentation when clarification is needed, especially for payment APIs, frameworks, database behavior, security, or deployment details.
- Prefer primary sources such as official docs, API references, and vendor guides.

## Site Editing Direction

- Preserve the TSF visual style unless the user asks for a redesign.
- Prefer admin-editable content for public copy and contact details instead of hard-coded page edits.
- Keep public user-facing pages working through normal browser URLs; PHP may be used when it improves backend/admin control.

