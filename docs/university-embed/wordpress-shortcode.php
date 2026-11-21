<?php
/**
 * Plugin Name: VMRF-DU Tenders
 * Description: Shows the university's current tenders, read live from the Vendor Management System.
 * Version:     1.0.0
 *
 * Use this instead of the JavaScript widget when the site runs WordPress. The page is built
 * on the server, so the tenders are in the HTML itself: search engines index them, and the
 * list still appears for a visitor whose browser blocks scripts.
 *
 * INSTALL — either way works:
 *   a) Save as wp-content/plugins/vmrf-tenders/vmrf-tenders.php and activate it, or
 *   b) paste everything below the plugin header into the active theme's functions.php.
 *
 * USE — put the shortcode on any page:
 *   [vmrf_tenders]
 *   [vmrf_tenders limit="5"]
 *   [vmrf_tenders limit="20" heading="Current Tenders"]
 *
 * Nothing here writes to the portal. It only reads the public feed.
 */

if (!defined('ABSPATH')) {
    exit; // Not called from WordPress.
}

const VMRF_TENDERS_PORTAL    = 'https://tenders.vinayakamissions.edu.in';
const VMRF_TENDERS_APPLY_URL = 'https://tenders.vinayakamissions.edu.in/register';

/**
 * How long a fetched list is reused, in seconds.
 *
 * This matters more than it looks. Every visitor hitting an uncached page would otherwise
 * make the web server call the portal again, and all those calls arrive from one IP address,
 * which the portal rate-limits to 120 a minute. Five minutes of cache keeps a busy page to a
 * dozen calls an hour. Tenders are open for days, so nothing is lost by it.
 */
const VMRF_TENDERS_CACHE_SECONDS = 300;

/**
 * Fetches the feed, or returns a WP_Error.
 *
 * A failure is cached briefly too, so an outage at the portal does not mean every single
 * page view sits waiting for a connection that is not coming.
 */
function vmrf_tenders_fetch(int $limit)
{
    $limit = max(1, min($limit, 100));
    $key   = 'vmrf_tenders_' . $limit;
    $hit   = get_transient($key);

    if ($hit !== false) {
        return $hit === 'unavailable'
            ? new WP_Error('vmrf_cached_failure', 'Tender list unavailable')
            : $hit;
    }

    $url = add_query_arg(
        ['limit' => $limit],
        VMRF_TENDERS_PORTAL . '/api/public/university/tenders'
    );

    $response = wp_remote_get($url, [
        'timeout' => 10,
        'headers' => ['Accept' => 'application/json'],
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        set_transient($key, 'unavailable', 60);
        return new WP_Error('vmrf_unreachable', 'Tender list unavailable');
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body) || empty($body['success']) || !isset($body['tenders'])) {
        set_transient($key, 'unavailable', 60);
        return new WP_Error('vmrf_bad_payload', 'Tender list unavailable');
    }

    $tenders = is_array($body['tenders']) ? $body['tenders'] : [];
    set_transient($key, $tenders, VMRF_TENDERS_CACHE_SECONDS);

    return $tenders;
}

/** 2026-11-21 -> 21 Nov 2026 */
function vmrf_tenders_date(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $ts = strtotime($iso);
    return $ts ? date_i18n('j M Y', $ts) : $iso;
}

/** 14:30:00 -> 2:30 PM */
function vmrf_tenders_time(?string $raw): string
{
    if (!$raw) {
        return '';
    }
    $ts = strtotime($raw);
    return $ts ? date_i18n('g:i A', $ts) : '';
}

/**
 * ₹18,50,000 — Indian grouping (last three digits, then pairs), not 1,850,000.
 * number_format() cannot do this, and the amounts here are read by people who expect lakhs.
 */
function vmrf_tenders_money($value): string
{
    if ($value === null || $value === '') {
        return '';
    }

    $n = (int) round((float) $value);
    $sign = $n < 0 ? '-' : '';
    $digits = (string) abs($n);

    if (strlen($digits) <= 3) {
        return $sign . '₹' . $digits;
    }

    $last3 = substr($digits, -3);
    $rest  = substr($digits, 0, -3);
    $rest  = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);

    return $sign . '₹' . $rest . ',' . $last3;
}

/** One label/value pair, or nothing when the value is empty. */
function vmrf_tenders_pair(string $label, string $value): string
{
    if ($value === '') {
        return '';
    }
    return '<div><dt>' . esc_html($label) . '</dt><dd>' . esc_html($value) . '</dd></div>';
}

function vmrf_tenders_shortcode($atts): string
{
    $atts = shortcode_atts(
        [
            'limit'   => 10,
            'heading' => '',
            'empty'   => 'There are no open tenders at the moment. Please check back later.',
        ],
        $atts,
        'vmrf_tenders'
    );

    $tenders = vmrf_tenders_fetch((int) $atts['limit']);

    $out = '<div class="vmrf-tenders">';

    if ($atts['heading'] !== '') {
        $out .= '<h2 class="vmrf-heading">' . esc_html($atts['heading']) . '</h2>';
    }

    if (is_wp_error($tenders)) {
        // Never show a visitor an error code; send them somewhere useful instead.
        return $out . '<div class="vmrf-state is-error">Tender information is temporarily '
            . 'unavailable. Please visit <a href="' . esc_url(VMRF_TENDERS_PORTAL) . '">the tender portal</a>.'
            . '</div></div>';
    }

    if (!$tenders) {
        return $out . '<div class="vmrf-state">' . esc_html($atts['empty']) . '</div></div>';
    }

    $out .= '<ul class="vmrf-list">';

    foreach ($tenders as $t) {
        $deadline = vmrf_tenders_date($t['submission_deadline'] ?? null);
        $dTime    = vmrf_tenders_time($t['submission_time'] ?? null);
        $opening  = vmrf_tenders_date($t['opening_date'] ?? null);
        $oTime    = vmrf_tenders_time($t['opening_time'] ?? null);
        $days     = isset($t['days_remaining']) ? (int) $t['days_remaining'] : null;

        $out .= '<li class="vmrf-card">';

        if (!empty($t['tender_number'])) {
            $out .= '<div class="vmrf-no">' . esc_html($t['tender_number']) . '</div>';
        }

        $out .= '<h3 class="vmrf-title">' . esc_html($t['title'] ?? 'Untitled tender');
        if (!empty($t['tender_type'])) {
            $out .= ' <span class="vmrf-tag">' . esc_html($t['tender_type']) . '</span>';
        }
        $out .= '</h3>';

        // The staff-written public summary. The internal description is not in this feed.
        if (!empty($t['university_description'])) {
            $out .= '<p class="vmrf-desc">' . esc_html($t['university_description']) . '</p>';
        }

        $out .= '<dl class="vmrf-meta">'
            . vmrf_tenders_pair('Last date to submit', $dTime ? "$deadline, $dTime" : $deadline)
            . vmrf_tenders_pair('Opening', $opening ? ($oTime ? "$opening, $oTime" : $opening) : '')
            . vmrf_tenders_pair('Estimated value', vmrf_tenders_money($t['estimated_value'] ?? null))
            . vmrf_tenders_pair('EMD', vmrf_tenders_money($t['emda_amount'] ?? null))
            . vmrf_tenders_pair('Venue', (string) ($t['opening_venue'] ?? ''))
            . '</dl>';

        $urgent = ($days !== null && $days <= 3) ? ' is-urgent' : '';
        if ($days === null) {
            $left = '';
        } elseif ($days <= 0) {
            $left = 'Closes today';
        } elseif ($days === 1) {
            $left = 'Closes tomorrow';
        } else {
            $left = $days . ' days left';
        }

        $out .= '<div class="vmrf-foot">'
            . '<span class="vmrf-left' . $urgent . '">' . esc_html($left) . '</span>'
            . '<a class="vmrf-apply" rel="noopener" href="' . esc_url(VMRF_TENDERS_APPLY_URL) . '">View &amp; apply</a>'
            . '</div>';

        $out .= '</li>';
    }

    return $out . '</ul></div>';
}
add_shortcode('vmrf_tenders', 'vmrf_tenders_shortcode');

/**
 * Styles, loaded only on pages that actually use the shortcode.
 * Class names are prefixed so they cannot collide with the theme.
 */
function vmrf_tenders_styles(): void
{
    if (!is_singular() || !has_shortcode((string) get_post_field('post_content', get_the_ID()), 'vmrf_tenders')) {
        return;
    }

    $css = <<<CSS
.vmrf-tenders{--vmrf-ink:#1f2937;--vmrf-muted:#6b7280;--vmrf-line:#e5e7eb;--vmrf-brand:#1e3a8a;--vmrf-brand-soft:#eef2ff;--vmrf-urgent:#b91c1c;color:var(--vmrf-ink);line-height:1.5}
.vmrf-tenders *,.vmrf-tenders *::before,.vmrf-tenders *::after{box-sizing:border-box}
.vmrf-heading{margin:0 0 16px}
.vmrf-state{padding:24px 16px;text-align:center;color:var(--vmrf-muted);border:1px dashed var(--vmrf-line);border-radius:8px}
.vmrf-state.is-error{color:var(--vmrf-urgent);border-color:currentColor}
.vmrf-list{display:grid;gap:16px;margin:0;padding:0;list-style:none}
.vmrf-card{border:1px solid var(--vmrf-line);border-left:4px solid var(--vmrf-brand);border-radius:8px;padding:16px 18px;background:#fff}
.vmrf-no{font-size:.8rem;letter-spacing:.04em;color:var(--vmrf-muted);text-transform:uppercase}
.vmrf-title{margin:2px 0 8px;font-size:1.1rem;font-weight:600;color:var(--vmrf-brand)}
.vmrf-desc{margin:0 0 12px}
.vmrf-meta{display:flex;flex-wrap:wrap;gap:8px 22px;margin:0 0 14px;padding:0}
.vmrf-meta dt{font-size:.78rem;color:var(--vmrf-muted);margin:0}
.vmrf-meta dd{margin:0;font-weight:600}
.vmrf-foot{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between}
.vmrf-left{font-size:.85rem;color:var(--vmrf-muted)}
.vmrf-left.is-urgent{color:var(--vmrf-urgent);font-weight:600}
.vmrf-apply{display:inline-block;padding:9px 18px;border-radius:6px;background:var(--vmrf-brand);color:#fff;text-decoration:none;font-weight:600}
.vmrf-apply:hover,.vmrf-apply:focus{background:#172b63;color:#fff;text-decoration:none}
.vmrf-tag{display:inline-block;margin-left:8px;padding:2px 8px;border-radius:999px;background:var(--vmrf-brand-soft);color:var(--vmrf-brand);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;vertical-align:middle}
@media(max-width:540px){.vmrf-foot{flex-direction:column;align-items:stretch}.vmrf-apply{text-align:center}}
CSS;

    wp_register_style('vmrf-tenders', false);
    wp_enqueue_style('vmrf-tenders');
    wp_add_inline_style('vmrf-tenders', $css);
}
add_action('wp_enqueue_scripts', 'vmrf_tenders_styles');
