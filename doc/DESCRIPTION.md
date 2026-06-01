Nginx Logger is a tiny debug endpoint.

Every request hitting any sub-route of its URL is:

- echoed back as a simple HTML page showing the route, query params, POST/form
  params, JSON/raw body, headers and client metadata, and
- written to a dedicated nginx access/error log for this route, whose tail is
  shown at the bottom of the same page.

Useful for inspecting webhooks, redirects, and "what is actually hitting this
URL?" questions.
