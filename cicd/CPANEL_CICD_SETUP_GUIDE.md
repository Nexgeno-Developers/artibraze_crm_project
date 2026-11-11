# cPanel Automated CI/CD Setup Guide (GitHub Actions via SSH)

This guide sets up automated deployments for **ArFashion** (`https://arfashion.nexgeno.cloud/`) using GitHub Actions and SSH. Once configured, every push or merge into `main` deploys the code to cPanel in about 10 seconds.

Repository: **https://github.com/Zaid5514/arfashion**

---

## How it works

```mermaid
sequenceDiagram
    autonumber
    actor Dev as Developer
    participant GH as GitHub Repository (main)
    participant GHA as GitHub Actions Runner
    participant CP as cPanel Server (arfashion)

    Dev->>GH: Merge Pull Request into 'main'
    GH->>GHA: Trigger .github/workflows/deploy.yml
    GHA->>CP: Connect via SSH
    GHA->>CP: cd to $CPANEL_PROJECT_PATH
    GHA->>CP: git fetch & git reset --hard
    GHA->>CP: Clear CodeIgniter cache (application/cache/*)
    CP-->>GHA: Deployment Success Response
    GHA-->>Dev: GitHub Actions Check Passed ✅
```

**What deploy touches and what it leaves alone**

| Overwritten on every deploy | Never touched (git-ignored / untracked) |
|---|---|
| Every file tracked in git (PHP, JS, CSS, `php.ini`, `.user.ini` …) | `application/config/app-config.php` (DB credentials) |
| | `.htaccess`, `uploads/`, `modules/*/uploads/`, `media/` uploads |
| | `application/vendor/`, `application/logs/`, `application/cache/` |
| | `.well-known/`, `cgi-bin/`, `*.zip`, `*.sql`, `error_log`, `*.bak` |

> [!WARNING]
> Any file edited directly on the server via cPanel File Manager / FTP **will be overwritten on the next deploy** if it is tracked in git. From now on, all code changes go through git only.
> Note this also covers `php.ini` / `.user.ini`: if the webhost edits them on the server again, copy their change into the repo too.

---

## Step 1: Create an SSH Key for GitHub Actions

### Option A: cPanel Terminal
```bash
ssh-keygen -t ed25519 -C "github-actions-deploy-arfashion" -f ~/.ssh/github_deploy_arfashion -N ""
cat ~/.ssh/github_deploy_arfashion.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
chmod 700 ~/.ssh

# Copy this entire output, including the BEGIN and END lines
cat ~/.ssh/github_deploy_arfashion
```

### Option B: cPanel UI
1. **cPanel > SSH Access > Manage SSH Keys > Generate a New Key**
   - Key Name: `github_deploy_arfashion`
   - Key Password: *leave blank*
   - Key Type: `ED25519` (or `RSA 4096`)
2. Under **Public Keys**, click **Manage** next to it, then **Authorize**.
3. Under **Private Keys**, click **View/Download** and copy the private key.

> If ArFashion is on the **same cPanel account** as nexcrm or lym, you can reuse that account's existing deploy key instead of making a new one.

---

## Step 2: Add Secrets in GitHub

👉 **https://github.com/Zaid5514/arfashion/settings/secrets/actions** → **New repository secret**

| Secret Name | Example Value | Description |
|---|---|---|
| `CPANEL_SSH_HOST` | `arfashion.nexgeno.cloud` or server IP | Hostname or IP of the cPanel server |
| `CPANEL_SSH_PORT` | `22` (or the host's custom SSH port) | SSH port |
| `CPANEL_SSH_USER` | `<CPANEL_USER>` | cPanel Linux username |
| `CPANEL_SSH_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----...` | Entire private key from Step 1 |
| `CPANEL_PROJECT_PATH` | `/home/<CPANEL_USER>/arfashion.nexgeno.cloud` | Absolute path to the ArFashion root (the folder containing `index.php`) |

The key must be passwordless; the workflow has no passphrase support.

Find the exact path in the cPanel Terminal with: `cd ~/arfashion.nexgeno.cloud && pwd` (or check **cPanel > Domains** for the document root).

---

## Step 3: One-Time Server Preparation (cPanel Terminal)

```bash
cd /home/<CPANEL_USER>/arfashion.nexgeno.cloud

# 1. Take a safety backup of the live code first
cd .. && zip -rq arfashion_before_cicd_$(date +%F).zip arfashion.nexgeno.cloud -x "*/uploads/*" && cd -

# 2. Make sure git exists
git --version

# 3. Initialise the repo (skip if `git status` already works)
git init
git remote add origin https://github.com/Zaid5514/arfashion.git
git config --global --add safe.directory $(pwd)

# 4. Remove leftovers that are no longer in the repo
rm -f clean_duplicate_receives.php            # one-off maintenance script, publicly reachable
rm -f modules/*.zip                           # old module backups (keep a copy elsewhere first if needed)
```

> [!IMPORTANT]
> The **first** deploy writes every tracked file over the live copy. The repo was synced with the live server on 2026-11-12, so this is safe **as long as nobody edits files on the server between that sync and the first deploy.**

---

## Step 4: First Deployment & Test

1. Merge a Pull Request into `main` (or go to **Actions → Deploy to cPanel Production → Run workflow**).
2. Watch it at 👉 **https://github.com/Zaid5514/arfashion/actions**
3. Open the site, log in, and check a couple of pages (Cash Flow Group report, Warehouse → Opening stock).

---

## Daily workflow

```
git checkout -b feature/my-change
# ...edit, commit...
git push -u origin feature/my-change
# Open a Pull Request on GitHub → Merge into main → auto deploy
```

Enable the local guard that blocks direct pushes to `main` (once per clone):
```bash
git config core.hooksPath cicd/githooks
```

---

## Files in this setup

| File | Purpose |
|---|---|
| `.github/workflows/deploy.yml` | The live workflow (GitHub requires this exact path). |
| `cicd/githooks/pre-push` | Blocks direct pushes to `main`. |
| `cicd/CPANEL_CICD_SETUP_GUIDE.md` | This guide. |

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Permission denied (publickey)` | The public key isn't **Authorized** in cPanel, or `CPANEL_SSH_USER` / `CPANEL_SSH_PORT` is wrong. |
| `fatal: unable to create thread` | Already handled; the workflow forces single-thread git for shared hosting. |
| `detected dubious ownership` | Run `git config --global --add safe.directory <path>` on the server. |
| `cd: ... No such file or directory` | `CPANEL_PROJECT_PATH` is wrong; run `pwd` inside the site folder. |
| Workflow succeeded but changes not visible | Hard-refresh the browser (CSS/JS cache), or check you merged into `main`. |
