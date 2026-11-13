# ArFashion CRM — AI Agent Operating Rules

> For every AI coding assistant (Claude Code, Cursor, Copilot, Windsurf, Cline, Antigravity …) and every developer.
> Live site: https://arfashion.nexgeno.cloud/ · Repo: https://github.com/Nexgeno-Developers/artibraze_crm_project

---

## 1. Git workflow (CRITICAL)

**`main` is production.** Every push to `main` is deployed to the live site within seconds by `.github/workflows/deploy.yml`.

1. **Always work on a feature branch.** Before changing any code, create a branch from an up-to-date `main`:
   ```bash
   git checkout main && git pull
   git checkout -b feat/short-description     # or fix/…, chore/…, docs/…, ci/…
   ```
   Never commit on `main`.
2. **Never push to `main`.** Only push your feature branch: `git push -u origin <branch>`.
3. **Never merge.** Do not run `git merge` into `main`, and do not merge a PR yourself. Changes reach `main` **only through a Pull Request on GitHub, merged by a human**.
   If the user explicitly asks for a local merge, use `git merge --no-ff <branch>`. Fast-forward merges are not allowed.
4. **Hand over a PR link.** After pushing, give the user the compare link to open the PR:
   `https://github.com/Nexgeno-Developers/artibraze_crm_project/compare/main...<branch>`
5. **One task per branch.** Keep commits focused, with messages that say *what* and *why*.
6. **Never force-push**, rewrite published history, or delete branches unless the user asks.
7. Enable the local guard once per clone: `git config core.hooksPath cicd/githooks` (blocks pushes to `main`).

---

## 2. Deployment facts

- `.github/workflows/deploy.yml` SSHes into the server, updates the cPanel clone at `~/repositories/artibraze_crm_project`, and runs `cicd/deploy.sh`.
- `cicd/deploy.sh` rsyncs into `public_html` **without `--delete`**. If you delete or rename a file, tell the user to remove the old one from the server by hand.
- These are never deployed and must never be committed: `application/config/app-config.php`, `.htaccess`, `uploads/`, `modules/*/uploads/`, `media/`, `temp/`, `application/cache/`, `application/logs/`, `ci_sessions/`.
- Never edit files directly on the live server. The next deploy overwrites them.
- Full guide: `cicd/CPANEL_CICD_SETUP_GUIDE.md`.

---

## 3. Codebase

- **Perfex CRM** on **CodeIgniter 3**, PHP 7.4–8.2, MySQL/MariaDB, Bootstrap + jQuery + DataTables.
- Most custom work lives in modules: `modules/manufacturing`, `modules/purchase`, `modules/warehouse`, `modules/nex_reports`, …
- **Prefer module code and hooks** over editing core (`application/`, `system/`). If a core edit is unavoidable, keep it minimal and call it out in the PR description.

### Code rules
- **Database:** use `db_prefix()` (never a hardcoded `tbl`). Use Query Builder or `?` bindings, and never concatenate user input into SQL. Schema changes go in module migrations/`install.php` and must be idempotent (`IF NOT EXISTS`, `field_exists()`).
- **Security:** check `has_permission()`/`staff_can()` in every controller action, and hide UI the user can't use. Verify ownership on records loaded by ID (IDOR). Use `form_open()`/the CSRF token for forms and AJAX. Escape output (`html_escape()`, `htmlspecialchars()`).
- **UI:** use Perfex panels (`panel_s`), form helpers (`render_input()` …), and `_l()` language strings instead of hardcoded text.

---

## 4. How to work

1. Understand the request and read the affected code first.
2. For anything non-trivial, share a short plan before making large changes.
3. Implement on the feature branch, then check it works (`php -l` on changed PHP files at minimum).
4. Commit, push the branch, and give the PR link with a summary of what changed and anything to check on the live site.
5. Never commit secrets, database dumps (`.sql`), spreadsheets with client data, or scratch scripts.
