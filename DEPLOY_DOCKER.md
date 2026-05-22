# AgriNest — Docker deploy (same style as your friend's project)

## Can you use your friend's files?

**Yes**, with small changes for AgriNest:

| Friend's project | Your AgriNest |
|------------------|---------------|
| `importmap:install` in entrypoint | **`npm run build`** (Webpack Encore) in Dockerfile |
| `GOOGLE_CLIENT_ID` | `GOOGLE_OAUTH_CLIENT_ID`, `GOOGLE_OAUTH_REDIRECT_URI`, etc. |
| May not use JWT | **JWT keys** generated in `entrypoint.sh` |
| Generic Symfony | Same stack — Symfony 7 + MySQL |

Files added in this repo: `Dockerfile`, `entrypoint.sh`, `nginx.conf`, `nginx-main.conf`, `.dockerignore`, `docker-compose.deploy.yaml`.

Your existing `docker-compose.yaml` (MySQL on port 3313 only) is unchanged for local dev without Docker app.

---

## Test locally (Docker, like your friend)

1. Copy env template:
   ```bash
   copy .env.docker.example .env.docker
   ```
   Edit `.env.docker` with your passwords.

2. Build and run:
   ```bash
   docker compose -f docker-compose.deploy.yaml --env-file .env.docker up --build
   ```

3. Open:
   - App: http://127.0.0.1:8000
   - phpMyAdmin: http://127.0.0.1:8082

---

## Deploy on Railway (Dockerfile)

Railway uses **only the app container** from `Dockerfile`. Database is a **separate Railway MySQL** service (not `mysql` hostname from compose).

### Steps

1. Push this repo to GitHub.
2. Railway → **New Project** → deploy **AGRINEST** repo.
3. Railway should detect **`Dockerfile`** automatically.
4. Add **MySQL** plugin in the same project.
5. On the **web service**, set variables (Variables tab):

   | Variable | Value |
   |----------|--------|
   | `APP_ENV` | `prod` |
   | `APP_DEBUG` | `0` |
   | `APP_SECRET` | (random string) |
   | `DATABASE_URL` | Reference from Railway MySQL (not `@mysql` — use Railway's URL) |
   | `JWT_PASSPHRASE` | Same as you use locally |
   | `JWT_SECRET_KEY` | `%kernel.project_dir%/config/jwt/private.pem` |
   | `JWT_PUBLIC_KEY` | `%kernel.project_dir%/config/jwt/public.pem` |
   | `DEFAULT_URI` | `https://YOUR-APP.up.railway.app` |
   | `CORS_ALLOW_ORIGIN` | `^https?://.*$` |
   | Google / mailer vars | From your `.env` as needed |

6. **Networking** → Generate domain.
7. Update Google OAuth redirect to `https://YOUR-APP.up.railway.app/connect/google/check`.
8. Mobile `api.ts`: `https://YOUR-APP.up.railway.app/api`.

Railway sets **`PORT`**; `entrypoint.sh` passes it to Nginx (same as your friend's setup).

### Important

- Do **not** copy your friend's `.env` file — it contains **their** passwords and API keys.
- Do **not** commit `.env.docker` with real secrets.
- `config/jwt/*.pem` stay gitignored; keys are created on container start.

---

## Friend's files on Desktop → this project

| Desktop file | AgriNest file |
|--------------|---------------|
| `Dockerfile` | `Dockerfile` (+ Node + `npm run build`) |
| `entrypoint.sh` | `entrypoint.sh` (+ JWT, no importmap) |
| `nginx.conf` | `nginx.conf` |
| `nginx-main.conf` | `nginx-main.conf` |
| `dockerignore` | `.dockerignore` |
| `docker-compose.yaml` | `docker-compose.deploy.yaml` |
| `env` | `.env.docker.example` (template only) |
| `gitignore` | Keep your existing `.gitignore` (do not replace) |
