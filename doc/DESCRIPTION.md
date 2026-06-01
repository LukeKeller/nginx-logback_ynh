Nginx Logger is a tiny debug endpoint.

- Visiting **`/logs`** shows the tail of this route's nginx access/error logs.
- Any **other** sub-route, with any method, is echoed back as a simple HTML page
  showing the route, query params, POST/form params, JSON/raw body, headers and
  client metadata.

Every request is written to a dedicated nginx access/error log for this route
(which `/logs` displays).

Useful for inspecting webhooks, redirects, and "what is actually hitting this
URL?" questions.
