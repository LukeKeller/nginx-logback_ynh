# Nginx Logger for YunoHost

A tiny YunoHost app that echoes the metadata of any incoming request and shows
the per-route nginx access/error logs.

Two behaviours:

- **`/logs`** — shows the tail of this route's nginx `access.log` and
  `error.log` (auto-refreshing).
- **any other sub-route**, with any method — echoes the request back as a
  simple HTML page showing: method, host, path, full URI, protocol, remote IP,
  user-agent; parsed query params; parsed POST / form params; decoded JSON body
  (when `Content-Type: application/json`) and the raw body; and all request
  headers.

Every request is also written to a dedicated log directory at
`/var/log/nginx_logger/` (`access.log` + `error.log`), rotated weekly, which is
what `/logs` displays.

## How it works

- `conf/nginx.conf` routes every sub-route to `index.php` via `try_files` and
  points `access_log` / `error_log` at the per-app log directory.
- `conf/index.php` (PHP-FPM) renders the request metadata and tails the logs.
- The endpoint is installed for the `visitors` group, so it is public and
  accepts requests from anywhere.

## Install

This app is not in the YunoHost catalogue; install it straight from this repo.

```bash
sudo yunohost app install https://github.com/LukeKeller/nginx-logback_ynh \
  --args "domain=nginx-logs.p10.club&path=/&init_main_permission=visitors"
```

Or, from a local checkout on the server:

```bash
sudo yunohost app install /path/to/nginx-logger \
  --args "domain=nginx-logs.p10.club&path=/&init_main_permission=visitors"
```

Then browse to <https://nginx-logs.p10.club/> (or POST to any sub-route such as
`https://nginx-logs.p10.club/webhook/test?foo=bar`).

## Upgrade / remove

```bash
sudo yunohost app upgrade nginx_logger -u https://github.com/LukeKeller/nginx-logback_ynh
sudo yunohost app remove  nginx_logger
```

## Notes

- The viewer page is **public** by design, so the logs it shows are visible to
  anyone with the URL. Don't point sensitive traffic at it.
- Tested against YunoHost 11.2+ (packaging v2, PHP 8.2).
