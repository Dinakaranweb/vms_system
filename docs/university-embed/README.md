# Showing tenders on the university website

Two ready-to-use samples that put the university's current tenders on
`vinayakamissions.edu.in`, read live from this system. Pick **one**.

| File | Use when | How it works |
|---|---|---|
| [`wordpress-shortcode.php`](wordpress-shortcode.php) | The site runs **WordPress** — pick this one if it does | The web server fetches the list and caches it. Tenders are in the page's HTML, so Google indexes them and they show even with JavaScript off. |
| [`tender-widget.html`](tender-widget.html) | Any other CMS, a plain HTML page, or a "Custom HTML" block | One paste. No dependencies, nothing to install. The visitor's browser fetches the list. |

Neither needs any change to this system. Both read a feed that is already live.

## WordPress

Either save `wordpress-shortcode.php` as `wp-content/plugins/vmrf-tenders/vmrf-tenders.php`
and activate it, or paste everything below the plugin header into the theme's `functions.php`.
Then put the shortcode on the tenders page:

```
[vmrf_tenders]
[vmrf_tenders limit="5"]
[vmrf_tenders limit="20" heading="Current Tenders"]
```

## Any other site

Paste the whole of `tender-widget.html` into the page. Edit only the `CONFIG` block near the
top — the portal address, how many to show, and where the button goes.

## What the feed sends

```
GET https://tenders.vinayakamissions.edu.in/api/public/university/tenders?limit=10
```

Open to anyone, no key, `Access-Control-Allow-Origin: *`, cached a minute. A tender appears in
it only when **all three** of these are true:

1. its status is **published**,
2. **Show on University Website** is ticked on the tender, and
3. the submission deadline has not passed.

Per tender it returns: `tender_number`, `title`, `university_description`, `tender_type`,
`estimated_value`, `emda_amount`, `published_date`, `submission_deadline`, `submission_time`,
`opening_date`, `opening_time`, `opening_venue`, `postal_address`, `days_remaining`, `is_open`.

Note `university_description` — the separate public summary staff write for this purpose. The
internal description is deliberately not in the feed, and neither is anything about bids,
applicants or vendors. Unticking the box removes a tender from the website within a minute.

## Four things to know before you publish the page

**1. The feed is empty today.** Two tenders are ticked for the university, but neither is
published and both deadlines have passed, so the page will show "no open tenders" until a
published, in-date tender is ticked. That is the widget working, not failing. Tick the box
when creating or editing a tender, or from the tenders list.

**2. The button goes to registration, not to the tender.** There is no public page for an
individual tender: a bidder has to register as a vendor and sign in before they can see the
documents or submit anything. Both samples therefore link to
`https://tenders.vinayakamissions.edu.in/register`. If you want visitors to read the notice
*before* registering, that needs a new public page in this system — it does not exist yet.

**3. Tender documents are not in this feed.** The notice PDF and annexures are not included.
There is a second endpoint that does return them (`/api/public/tenders`), but its document
links are built from `APP_URL`, which on the server is still
`http://192.168.32.249:8099/vms-system` — an internal address no outside visitor can reach.
Fix `APP_URL` before using that endpoint anywhere public.

**4. Serve the university page over HTTPS.** The feed is HTTPS. A browser on an HTTP page will
block the request as mixed content, and the widget will show its "temporarily unavailable"
message.

## "Tender information is temporarily unavailable"

That is the widget's fallback: it could not read the feed. Open the browser console
(**F12 → Console**) — the widget prints the actual reason and the exact address it tried.
In order of likelihood:

**1. `CONFIG.portal` has no `http://` or `https://`.** This is the usual one. A bare
`192.168.32.249:8099` is a *relative path* to a browser, so it gets looked for underneath
your own page and never reaches the portal. The widget now recovers from this and prints a
warning, but set it properly:

```js
portal: 'http://192.168.32.249:8099',      // testing, on the LAN
portal: 'https://tenders.vinayakamissions.edu.in',   // live
```

**2. The page is https and the portal is http.** The browser blocks it as mixed content.
Unavoidable — the portal address has to be https too. This is why a LAN IP works while you
are testing on a local http page, then stops working once it is on the live website.

**3. The address is genuinely wrong or unreachable.** Paste the address the console prints
straight into a browser tab. You should see JSON beginning `{"success":true`. If you get a
login page, an error, or nothing, the problem is the address or the server, not the widget.

For the record, both of these return correct JSON on the current test server, so neither the
path nor a missing subdirectory is the issue:

```
http://192.168.32.249:8099/api/public/university/tenders
http://192.168.32.249:8099/vms-system/api/public/university/tenders
```

**4. The feed is reachable but empty.** Then you get "There are no open tenders", not this
message. See point 1 in the section above.

## Limits and load

The endpoint allows 120 requests a minute per IP address and caps `limit` at 100.

For the HTML widget each visitor's own browser calls it, so the limit is effectively per
visitor. For the WordPress version every call comes from the web server's single IP, which is
why it caches each result for five minutes — that keeps a busy page to a few calls an hour.
Don't lower `VMRF_TENDERS_CACHE_SECONDS` much; tenders are open for days.

## What was tested

Both samples were run before being handed over, not just written.

The feed endpoint was called against this codebase: HTTP 200, `Access-Control-Allow-Origin: *`,
one-minute cache.

**The HTML widget** was loaded in a real browser with the feed stubbed:

- cards render, ₹ amounts group in lakhs (`₹18,50,000`), dates and times format, and a tender
  with no description, value or venue degrades without breaking the layout;
- the portal returning HTTP 500 shows a plain message naming the portal — no stack trace, no
  error code;
- an empty feed shows "no open tenders", not an error;
- a title of `<img src=x onerror=...>` is printed as text: no element created, no script run;
- a `CONFIG.portal` with no scheme, and one with a trailing slash, both still load the list,
  the first with a console warning naming the mistake;
- an unreachable portal shows the notice with a working link, and logs the real reason and
  the exact address it tried.

**The WordPress shortcode** was rendered with the WordPress API stubbed — 14 checks, all
passing: the fields appear and are formatted, the second render is served from cache rather
than re-fetched, an outage produces the plain message, an empty feed the reassuring one, and
`<script>alert(1)</script>` in a title comes out escaped. It also passes `php -l`.

Both put feed values on the page through `textContent` or `esc_html()` — never `innerHTML`.
That matters because the feed carries text typed by staff, and the university's home page is
not the place to find out it was trusted too readily.
