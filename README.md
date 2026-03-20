# RideX — Premium Cab Booking System

> **Built & Owned by Mohammad Shakeel**  
> A zero-dependency, single-file cab booking and fare estimation tool built for small-scale cab operators across India.

---

## The Problem It Solves

If you run a small cab business in India — one car, three cars, maybe a small fleet — you already know the drill. A customer calls, you try to figure out the distance on the fly, quote a rough number off the top of your head, and half the time there's a dispute at the end because nobody agreed on toll charges upfront. There's no paper trail, no booking reference, and you're chasing down details over the phone while driving.

Big platforms like Ola and Uber were never built for you. They take a cut, they control pricing, and independent drivers get squeezed. Building a custom app costs lakhs and takes months.

**RideX solves this cleanly.** It's a single HTML file — no server, no app store, no subscription — that gives a small cab operator a professional booking and fare estimation interface they can share with customers as a hosted page or even run locally on a laptop.

The moment a customer submits a booking, the full details land in the **operator's WhatsApp** instantly, structured and ready to act on. No missed calls, no miscommunication, no fare disputes.

---

## What It Does

### For Customers
- Pick a vehicle type (Auto, Sedan, or SUV), select pickup and drop locations, choose a date and time, and submit
- Get a live fare estimate before booking — distance calculated using the Haversine formula with a road-factor multiplier
- Choose a **toll preference upfront**: either include toll in the final fare (fixed price, no surprises) or agree to pay toll separately at booths
- See the full route drawn on a real India map with the distance marked
- Get a booking reference number and a confirmation screen on submit

### For the Operator (Mohammad Shakeel)
- Every booking arrives on WhatsApp (+91-7470712404) the moment the customer submits, with the full breakdown: name, phone, route, vehicle, date/time, fare, toll preference, and any special instructions
- No app needed, no backend server, no database to maintain
- Pricing is fully in your control — rates are set directly in the code (₹10/km for Auto, ₹12/km for Sedan, ₹15/km for SUV)
- Works on any device with a browser

---

## Features

| Feature | Details |
|---|---|
| Vehicle selection | Auto (₹10/km), Sedan (₹12/km), SUV (₹15/km) with animated car display |
| Location search | 110+ Indian cities with type-ahead autocomplete |
| India map | Canvas-rendered map with accurate boundary, state borders, major rivers, tiered city dots, and compass |
| Route visualization | Live curved route line drawn between pickup and drop with distance badge |
| Fare calculator | Haversine distance × rate + 5% service fee + toll logic |
| Toll preference | Customer chooses: toll included in fare (fixed total) or toll paid by passenger at booth |
| Booking mode | Full booking form with date, time, trip type, passenger count, and notes |
| Estimate mode | Quick fare estimate without requiring personal details |
| WhatsApp dispatch | Formatted booking summary sent to admin WhatsApp on submit |
| Thank you screen | Animated confirmation page with booking reference |
| Zero dependencies | No CDN, no backend, no database — one `.html` file |

---

## How the Code Works

The entire application lives in a single `cab-booking.html` file. There is no build step, no package manager, and no server. Here's how each part is put together.

### 1. Map Engine (Canvas-Based)

Since the app cannot rely on external map libraries (no CDN, works offline), the map is drawn entirely using the HTML5 `<canvas>` API.

**Coordinate system** — A `ll2xy()` function converts real latitude/longitude coordinates to canvas pixel positions using a linear projection with padding. The inverse `xy2ll()` converts a canvas click back to geographic coordinates.

```
ll2xy(lat, lng, canvasWidth, canvasHeight)
  → x = padding + (lng - minLng) / (maxLng - minLng) × width
  → y = padding + (1 - (lat - minLat) / (maxLat - minLat)) × height
```

**India boundary** — A hand-curated polygon of ~140 coordinate points traces the actual coastline, international borders, and the Northeast. This is drawn as a filled path with a land-toned gradient fill and a glowing amber stroke.

**State borders** — A set of polyline arrays approximate major state boundaries, drawn as faint dashed lines inside the land fill.

**Rivers** — 8 major rivers (Ganga, Yamuna, Brahmaputra, Indus, Godavari, Krishna, Narmada, Mahanadi) are drawn as semi-transparent blue polylines with their real geographic paths.

**City dots** — Cities are tiered by importance (metro, major, medium, small). Metros like Mumbai and Delhi get larger gold dots with always-visible labels. Smaller cities appear as ambient light dots. Labels use the Rajdhani font, drawn directly on canvas.

**Route line** — When both pickup and drop are selected, a quadratic Bézier curve connects them. The control point is offset upward proportionally to the straight-line distance, creating a natural arc. Static waypoint dots are placed at intervals along the curve, and a rounded-corner distance badge is rendered at the midpoint.

**Pins** — Selected cities get a three-layer glow pin (outer ring → mid ring → core dot) with a color-bordered label box, drawn using a `roundRect()` helper.

### 2. City Database

The app ships with a hard-coded array of 110+ Indian cities, each with `name`, `state`, `latitude`, and `longitude`. This powers both the autocomplete search and the map positioning. No API calls needed.

```javascript
const C = [
  {n:"Mumbai", s:"Maharashtra", lat:19.076, lng:72.878},
  {n:"Delhi",  s:"NCR",         lat:28.614, lng:77.209},
  // ...110+ entries
];
```

### 3. Fare Calculation

Distance is calculated using the **Haversine formula**, which gives the great-circle (straight-line) distance between two coordinates on a sphere. A **1.35× road factor** is applied to approximate actual road distance, which is typically longer than the straight line.

```
road_distance ≈ haversine(lat1, lng1, lat2, lng2) × 1.35
base_fare = road_distance × rate_per_km
service_fee = base_fare × 0.05
```

A lookup table of 30+ known highway toll corridors (e.g., Delhi–Agra: ₹140, Mumbai–Pune: ₹280) provides confirmed toll amounts for common routes. For routes not in the table, toll is estimated by distance bracket:
- Under 80 km → ₹0
- 80–200 km → 25% of distance
- Over 200 km → 40% of distance

### 4. Toll Preference Logic

When the customer selects **"Toll Included"**, the estimated toll amount is added into the total fare. The estimate box shows it as part of the fixed amount with a green confirmation notice. The WhatsApp message sent to the operator clearly states that toll is covered in the quoted fare.

When the customer selects **"Toll on Me"**, the toll is excluded from the fare total. The amber notice tells the customer they'll pay at toll booths. The WhatsApp message flags this explicitly so the driver doesn't get into a conversation about it later.

### 5. Location Selection (Two Ways)

**Autocomplete** — As the user types, the city array is filtered client-side and matching results appear in a dropdown. Selecting a city stores its coordinates in state and triggers a map redraw and fare recalculation.

**Map modal** — Clicking "Select on India Map" opens a full-screen modal with the same canvas-rendered India map. The user can either search for a city (results appear as a clickable list) or click directly on the map canvas. A click triggers `xy2ll()` to convert the canvas position to coordinates, then `findNearest()` walks the city array to find the closest city using Euclidean distance on the lat/lng plane. The selected city is shown with a pulsing highlight ring before confirmation.

### 6. WhatsApp Dispatch

On booking submission, a structured message string is assembled with all booking details and encoded using `encodeURIComponent()`. The browser then opens:

```
https://wa.me/917470712404?text=<encoded_message>
```

This opens WhatsApp (web or app) with the pre-filled message ready to send to the admin's number. The customer sees the thank-you page immediately; the WhatsApp tab opens in the background after a 900ms delay.

### 7. State Management

The app uses simple JavaScript module-level variables to track state:

```javascript
let selV = null;      // selected vehicle type
let selR = 0;         // selected rate per km
let pickup = null;    // pickup city object
let drop = null;      // drop city object
let tollOpt = null;   // 'included' | 'user'
let mode = 'book';    // 'book' | 'estimate'
```

Every UI interaction calls `updateEst()` to recompute the fare and `validate()` to enable/disable the submit button. No frameworks, no reactive state — just direct DOM updates.

---

## File Structure

```
cab-booking.html    ← The entire application. One file, self-contained.
README.md           ← This document.
```

---

## How to Deploy

**Simplest option — open locally:**
```
Just double-click cab-booking.html in any browser. Done.
```

**Host it for customers:**
Upload `cab-booking.html` to any static hosting service. Some free options:

- [GitHub Pages](https://pages.github.com) — free, reliable
- [Netlify Drop](https://app.netlify.com/drop) — drag and drop the file, get a URL instantly
- [Vercel](https://vercel.com) — free tier, custom domain support
- Any shared web hosting (just upload via FTP)

No database, no Node.js, no PHP. Just the HTML file.

---

## Customizing Rates

Open `cab-booking.html` in any text editor and find this section:

```javascript
// Vehicle selection
pickV('auto', 10)    // ← change 10 to your Auto rate per km
pickV('sedan', 12)   // ← change 12 to your Sedan rate per km
pickV('suv', 15)     // ← change 15 to your SUV rate per km
```

And in the vehicle card HTML:
```html
<div class="vrate">₹10 / km</div>   <!-- update display text to match -->
```

To update the admin WhatsApp number, search for `7470712404` and replace with your number (include country code, no `+`).

---

## Browser Support

Works in all modern browsers — Chrome, Firefox, Safari, Edge. No polyfills needed. The canvas API and ES6 features used are universally supported.

---

## License

Built with  **Mohammad Shakeel**. Free to use and adapt for personal or business purposes. If you share or redistribute a modified version, a credit mention is appreciated.

---

*RideX — because every cab driver deserves a professional booking system, not just the big platforms.*

<div align="center">
Made with ❤️ by Mohammad Shakeel
</div>
