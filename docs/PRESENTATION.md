# Configure Form Flow presentation

The baseline contract shipped in `3neti/form-flow` v1.10.0.
Tokens, screen overrides, semantic notices and action roles below are the
unreleased extension implemented alongside the companion handler/x-change changes. This guide describes the released
`form-flow.presentation.v1` contract, not a fully configurable page designer.

## Ownership and installation

Form Flow owns the schema, validation, inheritance, shared screen/actions,
status icons, and artwork surfaces. Integration packages own their named
profiles and domain-specific drivers. Handler packages own capture interactions.

Install compatible handlers and publish their pages using their own installation
instructions. For a standalone host, publish the manager components:

```bash
php artisan vendor:publish --tag=form-flow-views --no-interaction
npm run build
```

Review existing host customizations before forcing publication. In an x-change
host, use its unified publication command instead; see the x-change guide.
Do not edit installed `vendor/` files as durable package source.

## Configure a driver without a named profile

Add a top-level `presentation` block to your existing YAML driver:

```yaml
presentation:
  schema: form-flow.presentation.v1
  brand:
    name: Example
    logo: /images/example-logo.svg
    placement: top_left
    logo_height: 32
    logo_max_width: 112
  layout:
    density: compact
    capture_surface: fill_available
    scrolling: when_needed
    artwork_aspect_ratio: natural
  actions:
    placement: viewport_bottom
  progress:
    show: true
  diagnostics:
    show_versions: false
```

The logo URL must point to an asset available in the host. Form Flow does not
create that file. Keep labels unset globally when different steps have different
actions such as capture, send, verify, or continue.

Override a supported setting on a particular step under `config.presentation`:

```yaml
steps:
  details:
    handler: form
    step_name: details
    title: Your details
    config:
      presentation:
        actions:
          primary_label: Continue
    fields:
      - name: full_name
        type: text
        label: Full name
        required: true
```

These snippets belong in a complete driver; retain its source/target,
mappings, callbacks, and other required configuration.

## Register and inherit a profile

Register a trusted array at `form-flow.presentation.profiles.<name>` in Laravel
configuration or an integration service provider. The manager does not
automatically scan arbitrary profile YAML files. An integration may load YAML
and register the parsed array, as x-change does for `guided-claim-v1`.
Then reference it in the driver:

```yaml
presentation:
  schema: form-flow.presentation.v1
  profile: example-v1
```

Resolution order, from lowest to highest priority:

1. Manager defaults.
2. The selected named profile.
3. Driver `presentation` settings.
4. Named `screens.<key>` settings.
5. Step `config.presentation` settings.

Arrays merge recursively. A step may select a different registered profile.
Profiles cannot inherit another profile. Unknown profiles and unsupported
keys/values raise validation errors instead of being silently accepted.

## Supported settings

| Group | Settings and bounds |
| --- | --- |
| `brand` | `name` (nullable, up to 100 characters), `logo` (nullable URL/path string, up to 2048 characters), `placement` (`top_left`, `center`), `logo_height` (8–96), `logo_max_width` (32–240) |
| `layout` | `density` (`default`, `compact`, `immersive`), `capture_surface` (`fill_available`, `contained`), `scrolling` (`when_needed`), `artwork_aspect_ratio` (`natural`, `1200/630`, `1/1`, `4/3`, `3/2`, `16/9`) |
| `actions` | `placement` (`inline`, `bottom`, `bottom_sticky`, `viewport_bottom`), nullable `primary_label` and `secondary_label` (up to 100 characters); `primary_position: right`, `secondary_position: left`, `reserve_content_space: true` are constrained invariants |
| `progress` | `show` (boolean); current/total are supplied by the engine |
| `diagnostics` | `show_versions` (boolean); server suppresses versions outside local/testing environments |
| `appearance` | `mode` (`light`, `dark`, `system`); `light` and `dark` palettes with `canvas`, `surface`, `foreground`, `muted`, `border`, `accent`, `accent_foreground`, each a six-digit hex color |
| `icons` | `size` (16–64); roles `review`, `pending`, `success`, `attention`, `error`, each with `name` and `color` |
| `artwork.background` | `enabled` (boolean), `opacity` (0–1), `blur` (integer 0–40), `overlay` (0–1) |
| `artwork.foreground` | `position` (`top`, `center`), `top_spacing` (0–96), `gap` (0–48), `padding` (8–40), `max_width` (240–1200), dimensions in pixels |

Icon names: `receipt`, `clipboard-check`, `clock`, `circle-check`,
`triangle-alert`, `circle-alert`, `shield-check`. Colors accept `foreground`
or a six-digit hex color. See the resolver for authoritative defaults.

## Configure artwork

```yaml
presentation:
  layout:
    artwork_aspect_ratio: natural
  artwork:
    background:
      enabled: true
      opacity: 0.55
      blur: 18
      overlay: 0.8
    foreground:
      position: top
      top_spacing: 32
      gap: 20
      padding: 20
      max_width: 672
```

`natural` preserves source proportions. An explicit ratio selects a frame.
Background opacity and overlay are separate controls: `overlay: 0.8` means
80% overlay opacity, not 80% artwork visibility. A higher overlay covers more
of the decorative background. The foreground remains the complete composition.
Custom HTML renders once in the foreground; the background uses a decorative,
non-interactive image. Artwork must be supplied by the integration/content;
these settings do not invent an image for links without artwork.

## Design tokens and screen/action overrides

```yaml
presentation:
  tokens:
    content_width: wide
    spacing: spacious
    typography: large
    control_size: large
  notices:
    attention:
      background: '#fffbeb'
      foreground: '#92400e'
      border: '#fde68a'
      icon: triangle-alert
  actions:
    roles:
      capture:
        label: Take photo
        processing_label: Taking photo…
  screens:
    signature:
      layout:
        capture_surface: contained
      tokens:
        content_width: narrow
```

`content_width` accepts `narrow` (24rem), `standard` (28rem), `wide` (42rem).
`spacing` accepts `compact` (0.75rem), `comfortable` (1rem), `spacious` (1.5rem).
`typography` accepts `small`, `standard`, `large`: body sizes are 0.875rem,
1rem, 1.125rem; headings are 1.25rem, 1.5rem, 1.75rem.
`control_size` accepts `standard` (44px minimum) or `large` (52px minimum).
Omit tokens to retain component defaults. Text can still enlarge in the browser.
Panel padding is capped by the viewport and 24px so enlarged text does not lose
its usable content width; action labels may wrap instead of overflowing.

Notice roles are `info`, `pending`, `success`, `attention`, `error`. Each supports
six-digit hex `background`, `foreground`, `border` and an icon from the registry.
These affect presentation only; components select the actual semantic state.

Screen keys must be registered by their owner through
`FormFlowPresentationResolver::registerScreens()`. Core keys are `form`,
`splash`, `completion`, `missing_handler`; companion handlers register their own
names. Select a screen with step `config.presentation.screen`, or let the engine
use the registered handler name. Integrations register their outer-stage keys.
Unknown keys and recursive `screens`/`profile`/`screen` definitions inside an
override are rejected. Explicit step settings have final priority.

Action roles are `continue`, `back`, `capture`, `retake`, `clear`, `send_code`,
`verify`, `retry`, `confirm_claim`, `cancel`, `open_link`, `create_payment`,
`download`, `submit`. Each accepts a plain-text `label` and `processing_label` (1–100
characters, no HTML). A role never changes the operation, URL, permission,
disabled state, or visibility. The component owns those decisions.

Role-specific copy takes precedence. Legacy global labels apply to unclassified
controls and continue/back navigation; they cannot rename a migrated capture,
verification, retry, or financial action. Hosts remain responsible for wording
that accurately describes the action.

## Component integration and boundaries

`FormFlowPresentation` resolves the frozen screen override, provides policy to
children, and applies a scoped palette and token variables. It does not replace
the host's saved theme. `FormFlowScreen`, `FormFlowActions`, `FormFlowAction`,
`FormFlowNotice`, and `FormFlowStatusIcon` consume that policy. Custom handlers
must use these components or explicitly consume the policy; accepting a prop
alone does not implement the contract.

Use `data-flow-capture-surface` on a capture container, `data-flow-content` for
content-width policy, and `data-flow-stack` for spacing. Capture policy changes
the visible container, not evidence requirements. Preserve source proportions
and bitmap/coordinate integrity when resizing signature surfaces.

Capture/verify/retry labels are state-specific. Native permission dialogs and
external provider pages remain outside this schema. Field composition,
validation and safe scrolling stay in components; this is not arbitrary CSS or
a page-template language.

Do not put arbitrary CSS, executable templates, provider credentials, or
security/workflow decisions into this presentation block.

## Snapshots, verification, and troubleshooting

Presentation is resolved and frozen when a flow is prepared. The resolved
profile is stored in flow metadata and step configuration. Existing active flows
keep their presentation; use a new flow to verify changes. Integrations should
include the profile in preview cache identity and render the same frozen data.

If a change is missing, check the selected profile, driver and step overrides,
config cache, flow snapshot, installed package versions, published Vue copies,
and frontend build. Do not assume an old preview proves a new profile was used.

Existing coverage lives in `tests/Unit/FormFlowPresentationTest.php` and
`tests/Unit/DriverPresentationTest.php`. From the package checkout:

```bash
vendor/bin/pest tests/Unit/FormFlowPresentationTest.php tests/Unit/DriverPresentationTest.php
```

When extending the contract, test precedence, rejected values, production
version suppression, frozen state, and actual rendered effects. Test handler
states and mobile layouts, not just that configuration reaches page props.
