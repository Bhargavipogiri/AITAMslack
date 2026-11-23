<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AITAM Slack | College Community</title>
<style>
/* ==============================
   AITAM SLACK - DESIGN TOKENS
================================= */

:root {
  --color-navy-950: #0F2854;
  --color-blue-800: #1C4D8D;
  --color-blue-500: #4988C4;
  --color-cyan-100: #BDE8F5;

  --color-white: #FFFFFF;
  --color-slate-50: #F7FAFC;
  --color-slate-100: #EEF3F8;
  --color-slate-200: #D9E3ED;
  --color-slate-500: #64748B;
  --color-slate-700: #334155;
  --color-slate-900: #172B45;

  --color-primary: var(--color-blue-800);
  --color-primary-hover: var(--color-navy-950);
  --color-secondary: var(--color-blue-500);
  --color-accent: var(--color-cyan-100);

  --bg-app: #F4F8FC;
  --bg-surface: var(--color-white);
  --bg-surface-muted: #EAF2F9;
  --bg-sidebar: var(--color-navy-950);
  --bg-header: var(--color-white);

  --text-primary: #172B45;
  --text-secondary: #52657B;
  --text-muted: #7B8DA2;
  --text-on-brand: var(--color-white);

  --border-default: #DCE6EF;
  --border-strong: #C4D5E5;
  --focus-ring: rgba(73,136,196,.35);

  --color-success: #16835D;
  --color-warning: #B7791F;
  --color-danger: #C2414B;

  --gradient-brand: linear-gradient(
    135deg,
    #0F2854 0%,
    #1C4D8D 58%,
    #4988C4 100%
  );

  --gradient-accent: linear-gradient(
    135deg,
    #4988C4 0%,
    #BDE8F5 100%
  );

  --font-family-base: Inter, ui-sans-serif, system-ui,
    -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;

  --radius-sm: 6px;
  --radius-md: 10px;
  --radius-lg: 16px;
  --radius-xl: 22px;

  --shadow-sm: 0 1px 3px rgba(15,40,84,.08);
  --shadow-md: 0 8px 24px rgba(15,40,84,.10);
  --shadow-lg: 0 16px 40px rgba(15,40,84,.14);

  --transition-fast: 160ms ease;
}

/* ==============================
   DARK THEME
================================= */

[data-theme="dark"] {
  color-scheme: dark;

  --bg-app: #0A1730;
  --bg-surface: #102344;
  --bg-surface-muted: #17345B;
  --bg-sidebar: #091A38;
  --bg-header: #102344;

  --text-primary: #F2F7FC;
  --text-secondary: #C0D2E4;
  --text-muted: #91A9C2;
  --text-on-brand: #FFFFFF;

  --border-default: #29466B;
  --border-strong: #3A5D85;

  --color-primary: #4988C4;
  --color-primary-hover: #68A4D8;
  --color-secondary: #72A9D8;
  --color-accent: #BDE8F5;

  --shadow-sm: 0 1px 3px rgba(0,0,0,.20);
  --shadow-md: 0 8px 24px rgba(0,0,0,.24);
  --shadow-lg: 0 16px 40px rgba(0,0,0,.30);
}

/* ==============================
   RESET & GLOBAL STYLES
================================= */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  scroll-behavior: smooth;
  scroll-padding-top: 100px;
}

body {
  font-family: var(--font-family-base);
  background: var(--bg-app);
  color: var(--text-primary);
  min-width: 320px;
  min-height: 100vh;
  line-height: 1.6;
  transition: background var(--transition-fast),
              color var(--transition-fast);
}

a {
  text-decoration: none;
  color: inherit;
}

button,
input {
  font: inherit;
}

button {
  cursor: pointer;
}

button:focus-visible,
a:focus-visible,
input:focus-visible {
  outline: 3px solid var(--focus-ring);
  outline-offset: 3px;
}

img {
  max-width: 100%;
  display: block;
}

.container {
  width: min(1180px, 90%);
  margin: 0 auto;
}

.section {
  padding: 90px 0;
}

.section-heading {
  display: flex;
  justify-content: space-between;
  align-items: end;
  gap: 24px;
  margin-bottom: 36px;
}

.section-heading h2 {
  font-size: clamp(26px, 3vw, 36px);
  letter-spacing: -1px;
  line-height: 1.2;
  margin-bottom: 12px;
}

.section-heading p {
  color: var(--text-secondary);
  max-width: 570px;
}

.eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-size: 12px;
  font-weight: 800;
  letter-spacing: 1.6px;
  text-transform: uppercase;
  color: var(--color-blue-500);
  margin-bottom: 16px;
}

.eyebrow::before {
  content: "";
  width: 25px;
  height: 2px;
  background: currentColor;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 13px 22px;
  border-radius: 10px;
  border: 1px solid transparent;
  font-size: 14px;
  font-weight: 700;
  transition: .2s ease;
}

.btn-primary {
  background: var(--color-primary);
  color: white;
}

.btn-primary:hover {
  background: var(--color-primary-hover);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.btn-outline {
  border-color: var(--border-strong);
  background: var(--bg-surface);
  color: var(--text-primary);
}

.btn-outline:hover {
  border-color: var(--color-blue-500);
  color: var(--color-primary);
  transform: translateY(-2px);
}

.btn-light {
  background: white;
  color: var(--color-navy-950);
}

.btn-light:hover {
  background: var(--color-cyan-100);
  transform: translateY(-2px);
}

/* ==============================
   HEADER & NAVIGATION
================================= */

.header {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: var(--bg-header);
  border-bottom: 1px solid var(--border-default);
  box-shadow: var(--shadow-sm);
}

.navbar {
  min-height: 82px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}

.brand-logo {
  width: 49px;
  height: 49px;
  object-fit: contain;
  border-radius: 50%;
  background: white;
  padding: 3px;
}

.brand-name {
  font-size: 22px;
  font-weight: 850;
  color: var(--color-primary);
  letter-spacing: -.7px;
  line-height: 1.2;
}

.brand-name span {
  display: block;
  font-size: 10px;
  font-weight: 600;
  color: var(--text-muted);
  letter-spacing: 1.5px;
  margin-top: 5px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 30px;
}

.nav-links a {
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 600;
  transition: color .2s;
}

.nav-links a:hover {
  color: var(--color-primary);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.theme-toggle {
  width: 42px;
  height: 42px;
  border: 1px solid var(--border-default);
  border-radius: 50%;
  background: var(--bg-surface);
  color: var(--text-primary);
  font-size: 18px;
}

.theme-toggle:hover {
  background: var(--bg-surface-muted);
}

.menu-toggle {
  display: none;
  background: transparent;
  border: none;
  color: var(--text-primary);
  font-size: 25px;
}

/* ==============================
   HERO SECTION
================================= */

.hero {
  position: relative;
  overflow: hidden;
  padding: 90px 0 100px;
  background:
    radial-gradient(circle at 85% 15%,
      rgba(189,232,245,.45), transparent 30%),
    var(--bg-app);
}

.hero::before {
  content: "";
  position: absolute;
  width: 430px;
  height: 430px;
  border: 1px solid rgba(73,136,196,.12);
  border-radius: 50%;
  right: -170px;
  bottom: -220px;
}

.hero-grid {
  display: grid;
  grid-template-columns: 1.05fr .95fr;
  align-items: center;
  gap: 70px;
  position: relative;
  z-index: 1;
}

.hero-content h1 {
  font-size: clamp(40px, 5vw, 64px);
  letter-spacing: -2.8px;
  line-height: 1.1;
  margin-bottom: 24px;
  max-width: 650px;
}

.hero-content h1 span {
  color: var(--color-blue-500);
}

.hero-description {
  color: var(--text-secondary);
  font-size: 17px;
  line-height: 1.9;
  max-width: 540px;
  margin-bottom: 32px;
}

.hero-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-bottom: 35px;
}

.hero-note {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-muted);
  font-size: 13px;
}

.online-dot {
  width: 9px;
  height: 9px;
  background: #22C58B;
  border-radius: 50%;
  box-shadow: 0 0 0 5px rgba(34,197,139,.12);
}

/* Hero visual */

.hero-visual {
  position: relative;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.hero-image {
  display: block;
  width: 100%;
  height: 380px;
  object-fit: contain;
  object-position: center;
  background: transparent;
}

.hero-image-caption {
  text-align: center;
  padding-top: 18px;
}

.hero-image-caption strong {
  display: block;
  font-size: 17px;
  color: var(--text-primary);
}

.hero-image-caption span {
  font-size: 12px;
  color: var(--text-muted);
}

.floating-card {
  position: absolute;
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  box-shadow: var(--shadow-md);
  border-radius: 14px;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 2;
  animation: float 4s ease-in-out infinite;
}

.floating-card strong {
  display: block;
  font-size: 13px;
}

.floating-card small {
  color: var(--text-muted);
  font-size: 11px;
}

.floating-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-size: 21px;
  background: var(--bg-surface-muted);
}

.floating-one {
  left: -40px;
  top: 65px;
}

.floating-two {
  right: -20px;
  bottom: 70px;
  animation-delay: 1s;
}

@keyframes float {
  0%,100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

/* ==============================
   STATS
================================= */

.stats {
  background: var(--bg-surface);
  border-top: 1px solid var(--border-default);
  border-bottom: 1px solid var(--border-default);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  padding: 32px 0;
}

.stat {
  text-align: center;
  padding: 8px 20px;
  border-right: 1px solid var(--border-default);
}

.stat:last-child {
  border-right: none;
}

.stat h3 {
  font-size: 30px;
  color: var(--color-primary);
  letter-spacing: -1px;
}

.stat p {
  color: var(--text-muted);
  font-size: 13px;
  margin-top: 5px;
}

/* ==============================
   CHANNELS
================================= */

.channels-section {
  background: var(--bg-app);
}

.channel-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.channel-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  padding: 28px;
  transition: .25s ease;
  display: flex;
  flex-direction: column;
  min-height: 265px;
}

.channel-card:hover {
  transform: translateY(-6px);
  box-shadow: var(--shadow-md);
  border-color: var(--color-blue-500);
}

.channel-icon {
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  background: var(--bg-surface-muted);
  border-radius: 14px;
  font-size: 24px;
  margin-bottom: 22px;
}

.channel-card h3 {
  font-size: 19px;
  margin-bottom: 10px;
}

.channel-card p {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.8;
  margin-bottom: 22px;
}

.channel-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: auto;
  padding-top: 18px;
  border-top: 1px solid var(--border-default);
  color: var(--text-muted);
  font-size: 12px;
}

.channel-footer a {
  color: var(--color-primary);
  font-weight: 700;
}

/* ==============================
   AI CONTENT VERIFICATION
================================= */

.qa-verify-section {
  background: var(--bg-surface);
  border-top: 1px solid var(--border-default);
  border-bottom: 1px solid var(--border-default);
}

.qa-verify-grid {
  display: grid;
  grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
  align-items: center;
  gap: 64px;
}

.qa-verify-copy h2 {
  font-size: clamp(30px, 3vw, 42px);
  line-height: 1.2;
  letter-spacing: -1.5px;
  margin-bottom: 20px;
}

.qa-verify-copy > p {
  color: var(--text-secondary);
  line-height: 1.9;
  margin-bottom: 28px;
}

.qa-verify-features {
  list-style: none;
  display: grid;
  gap: 14px;
  margin-bottom: 0;
}

.qa-verify-features li {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
}

.qa-verify-feature-icon {
  width: 23px;
  height: 23px;
  display: grid;
  place-items: center;
  border-radius: 50%;
  background: var(--bg-surface-muted);
  color: var(--color-primary);
  font-size: 13px;
  font-weight: 800;
  flex-shrink: 0;
}

.qa-verify-timeline {
  position: relative;
  min-width: 0;
  scroll-margin-top: 70px;
}

.qa-verify-flow {
  position: relative;
}

.qa-verify-flow::before {
  position: absolute;
  top: 15px;
  bottom: 15px;
  left: 15px;
  width: 1px;
  background: var(--border-strong);
  content: "";
}

.qa-verify-stage {
  position: relative;
  display: grid;
  grid-template-columns: 31px minmax(0, 1fr);
  gap: 13px;
  padding-bottom: 20px;
}

.qa-verify-stage:last-of-type {
  padding-bottom: 0;
}

.qa-verify-marker {
  width: 31px;
  height: 31px;
  display: grid;
  place-items: center;
  z-index: 1;
  border: 1px solid var(--border-strong);
  border-radius: 50%;
  background: var(--bg-surface);
  color: var(--color-primary);
}

.qa-verify-marker svg {
  width: 16px;
  height: 16px;
}

.qa-verify-stage-content {
  min-width: 0;
  padding-top: 2px;
}

.qa-verify-stage-content h3 {
  color: var(--text-primary);
  font-size: 15px;
  line-height: 1.4;
}

.qa-verify-stage-content > p {
  margin-top: 4px;
  color: var(--text-secondary);
  font-size: 12px;
  line-height: 1.55;
}

.qa-verify-checks,
.qa-verify-report-types {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.qa-verify-checks span,
.qa-verify-report-types span {
  padding: 4px 8px;
  border: 1px solid var(--border-default);
  border-radius: 999px;
  background: var(--bg-surface-muted);
  color: var(--text-secondary);
  font-size: 10px;
  font-weight: 600;
  line-height: 1.3;
}

.qa-verify-outcomes {
  display: grid;
  gap: 3px;
  margin-top: 8px;
}

.qa-verify-outcome {
  display: grid;
  grid-template-columns: minmax(132px, .8fr) minmax(0, 1.2fr);
  align-items: start;
  gap: 8px;
  padding: 5px 0;
  border-top: 1px solid var(--border-default);
}

.qa-verify-outcome strong {
  display: flex;
  align-items: baseline;
  gap: 7px;
  color: var(--text-primary);
  font-size: 11px;
  line-height: 1.45;
}

.qa-verify-outcome strong::before {
  width: 7px;
  height: 7px;
  flex: 0 0 7px;
  border-radius: 50%;
  background: var(--color-success);
  content: "";
}

.qa-verify-outcome.is-review strong::before {
  background: var(--color-warning);
}

.qa-verify-outcome p {
  color: var(--text-secondary);
  font-size: 11px;
  line-height: 1.45;
}

.qa-verify-disclaimer {
  margin: 12px 0 0 44px;
  color: var(--text-muted);
  font-size: 10px;
  line-height: 1.5;
}

/* ==============================
   ANNOUNCEMENTS
================================= */

.announcements {
  background: var(--bg-app);
}

.announcement-grid {
  display: grid;
  grid-template-columns: 1.2fr .8fr;
  gap: 28px;
}

.announcement-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: var(--radius-lg);
  padding: 30px;
  transition: .2s ease;
}

.announcement-card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-3px);
}

.announcement-tag {
  display: inline-block;
  padding: 6px 11px;
  background: var(--bg-surface-muted);
  color: var(--color-primary);
  border-radius: 6px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .5px;
  margin-bottom: 18px;
}

.announcement-card h3 {
  font-size: 21px;
  line-height: 1.5;
  margin-bottom: 14px;
}

.announcement-card p {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.9;
  margin-bottom: 22px;
}

.announcement-meta {
  font-size: 12px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ==============================
   EVENTS
================================= */

.events-section {
  background: var(--bg-surface);
  border-top: 1px solid var(--border-default);
}

.events-list {
  display: grid;
  gap: 16px;
}

.event-card {
  display: grid;
  grid-template-columns: 85px 1fr auto;
  align-items: center;
  gap: 24px;
  padding: 22px;
  background: var(--bg-app);
  border: 1px solid var(--border-default);
  border-radius: 14px;
  transition: .2s ease;
}

.event-card:hover {
  border-color: var(--color-blue-500);
  transform: translateX(4px);
}

.event-date {
  background: var(--bg-surface);
  border: 1px solid var(--border-default);
  border-radius: 12px;
  padding: 12px;
  text-align: center;
}

.event-date strong {
  display: block;
  font-size: 23px;
  color: var(--color-primary);
  line-height: 1.2;
}

.event-date span {
  font-size: 11px;
  color: var(--text-muted);
  font-weight: 700;
  letter-spacing: 1px;
}

.event-info h3 {
  font-size: 16px;
  margin-bottom: 6px;
}

.event-info p {
  font-size: 13px;
  color: var(--text-secondary);
}

.event-type {
  font-size: 11px;
  font-weight: 700;
  padding: 7px 12px;
  background: var(--bg-surface-muted);
  color: var(--color-primary);
  border-radius: 20px;
}

/* ==============================
   CTA
================================= */

.cta-section {
  padding: 20px 0 90px;
  background: var(--bg-surface);
}

.cta-box {
  position: relative;
  overflow: hidden;
  padding: 65px 35px;
  border-radius: 24px;
  background: var(--gradient-brand);
  color: white;
  text-align: center;
}

.cta-box::before,
.cta-box::after {
  content: "";
  position: absolute;
  width: 240px;
  height: 240px;
  border: 1px solid rgba(255,255,255,.15);
  border-radius: 50%;
}

.cta-box::before {
  left: -80px;
  top: -130px;
}

.cta-box::after {
  right: -80px;
  bottom: -140px;
}

.cta-box h2 {
  font-size: clamp(27px, 4vw, 42px);
  letter-spacing: -1px;
  margin-bottom: 16px;
}

.cta-box p {
  color: rgba(255,255,255,.8);
  max-width: 580px;
  margin: 0 auto 28px;
  line-height: 1.8;
}

.cta-box .btn {
  position: relative;
  z-index: 1;
}

/* ==============================
   FOOTER
================================= */

.footer {
  background: var(--bg-sidebar);
  color: white;
  padding: 65px 0 25px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.4fr 1fr 1fr 1fr;
  gap: 50px;
  padding-bottom: 45px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.footer-brand img {
  width: 45px;
  height: 45px;
  object-fit: contain;
  background: white;
  border-radius: 50%;
  padding: 3px;
}

.footer-brand strong {
  font-size: 20px;
}

.footer-about {
  color: #B5C7DF;
  font-size: 13px;
  line-height: 1.9;
  max-width: 300px;
}

.footer h4 {
  font-size: 14px;
  margin-bottom: 22px;
}

.footer-links {
  display: grid;
  gap: 13px;
}

.footer-links a {
  color: #B5C7DF;
  font-size: 13px;
  transition: color .2s;
}

.footer-links a:hover {
  color: white;
}

.footer-bottom {
  border-top: 1px solid rgba(255,255,255,.15);
  padding-top: 24px;
  display: flex;
  justify-content: space-between;
  gap: 20px;
  color: #9CB0CC;
  font-size: 12px;
}

/* ==============================
   SEARCH MODAL
================================= */

.search-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(9,26,56,.65);
  z-index: 2000;
  padding: 20px;
  align-items: flex-start;
  justify-content: center;
}

.search-overlay.active {
  display: flex;
}

.search-box {
  width: min(600px, 100%);
  margin-top: 12vh;
  padding: 25px;
  background: var(--bg-surface);
  border-radius: 18px;
  box-shadow: var(--shadow-lg);
}

.search-box input {
  width: 100%;
  padding: 16px;
  background: var(--bg-app);
  color: var(--text-primary);
  border: 1px solid var(--border-default);
  border-radius: 10px;
  margin-bottom: 15px;
}

.search-box p {
  font-size: 12px;
  color: var(--text-muted);
}

/* ==============================
   TOAST NOTIFICATION
================================= */

.toast {
  position: fixed;
  bottom: 25px;
  left: 50%;
  transform: translate(-50%, 30px);
  background: var(--color-navy-950);
  color: white;
  padding: 14px 22px;
  border-radius: 10px;
  font-size: 13px;
  box-shadow: var(--shadow-lg);
  opacity: 0;
  pointer-events: none;
  transition: .3s ease;
  z-index: 3000;
  text-align: center;
  max-width: 90%;
}

.toast.show {
  opacity: 1;
  transform: translate(-50%, 0);
}

/* ==============================
   RESPONSIVE DESIGN
================================= */

@media (max-width: 1000px) {
  .nav-links {
    gap: 18px;
  }

  .hero-grid {
    gap: 40px;
  }

  .hero-image {
    height: 310px;
  }

  .channel-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .qa-verify-grid {
    gap: 40px;
  }

  .footer-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 760px) {
  .section {
    padding: 65px 0;
  }

  .navbar {
    min-height: 72px;
  }

  .menu-toggle {
    display: block;
  }

  .nav-links {
    display: none;
    position: absolute;
    top: 72px;
    left: 0;
    right: 0;
    padding: 25px 5%;
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border-default);
    flex-direction: column;
    align-items: flex-start;
    gap: 22px;
    box-shadow: var(--shadow-md);
  }

  .nav-links.active {
    display: flex;
  }

  .nav-actions .btn {
    display: none;
  }

  .hero {
    padding: 65px 0 75px;
  }

  .hero-grid {
    grid-template-columns: 1fr;
    gap: 55px;
  }

  .hero-content h1 {
    font-size: 44px;
  }

  .hero-visual {
    width: min(500px, 100%);
    margin: 0 auto;
  }

  .hero-image {
    height: 300px;
  }

  .floating-one {
    left: -10px;
  }

  .floating-two {
    right: -10px;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    row-gap: 25px;
  }

  .stat:nth-child(2) {
    border-right: none;
  }

  .stat:nth-child(3),
  .stat:nth-child(4) {
    border-top: 1px solid var(--border-default);
    padding-top: 25px;
  }

  .section-heading {
    align-items: flex-start;
    flex-direction: column;
  }

  .qa-verify-grid,
  .announcement-grid {
    grid-template-columns: 1fr;
    gap: 35px;
  }

  .event-card {
    grid-template-columns: 65px 1fr;
    gap: 16px;
  }

  .event-type {
    grid-column: 2;
    justify-self: start;
  }

  .cta-box {
    padding: 50px 24px;
  }

  .footer-grid {
    gap: 35px;
  }
}

@media (max-width: 480px) {
  .container {
    width: 92%;
  }

  .brand-logo {
    width: 40px;
    height: 40px;
  }

  .brand-name {
    font-size: 19px;
  }

  .brand-name span {
    font-size: 9px;
  }

  .hero-content h1 {
    font-size: 37px;
    letter-spacing: -1.8px;
  }

  .hero-description {
    font-size: 15px;
  }

  .hero-buttons .btn {
    width: 100%;
  }

  .hero-image {
    height: 250px;
  }

  .floating-card {
    padding: 10px;
  }

  .floating-icon {
    width: 35px;
    height: 35px;
  }

  .floating-card strong {
    font-size: 11px;
  }

  .floating-card small {
    font-size: 10px;
  }

  .channel-grid {
    grid-template-columns: 1fr;
  }

  .channel-card {
    min-height: auto;
  }

  .qa-verify-stage {
    grid-template-columns: 30px minmax(0, 1fr);
    gap: 11px;
    padding-bottom: 17px;
  }

  .qa-verify-outcome {
    grid-template-columns: minmax(110px, .8fr) minmax(0, 1.2fr);
    gap: 6px;
  }

  .qa-verify-disclaimer {
    margin-left: 41px;
  }

  .announcement-card {
    padding: 22px;
  }

  .event-card {
    padding: 15px;
  }

  .footer-grid {
    grid-template-columns: 1fr;
    gap: 35px;
  }

  .footer-bottom {
    flex-direction: column;
    text-align: center;
  }
}

@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .01ms !important;
    scroll-behavior: auto !important;
  }
}
</style>
</head>

<body>




<!-- ==============================
     HERO
================================= -->

<?php include 'nav.php'; ?>

<main>

<section class="hero" id="home">
  <div class="container hero-grid">

    <div class="hero-content">

      <div class="eyebrow">
        One college. One community.
      </div>

      <h1>
        Your campus.<br>
        Your people.<br>
        <span>One connection.</span>
      </h1>

      <p class="hero-description">
        Welcome to AITAM Slack — a connected space for students,
        faculty, alumni, and everyone who makes our college special.
        Discover campus updates, collaborate on projects, and get
        the answers you need, all in one place.
      </p>

      <div class="hero-buttons">
        <a href="#channels" class="btn btn-primary">
          Explore Community <span>→</span>
        </a>
      </div>

      <div class="hero-note">
        <span class="online-dot"></span>
        A shared space for the entire AITAM community
      </div>

    </div>

    <div class="hero-visual">

      <img class="hero-image"
           src="https://adityatekkali.edu.in/ahlaad2k26/25years.png"
           alt="AITAM celebrating 25 years of excellence">

      <div class="hero-image-caption">
        <strong>Celebrating 25 Years of Excellence</strong>
        <span>Silver Jubilee • Est. 2001</span>
      </div>

      <div class="floating-card floating-one">
        <div class="floating-icon">🎓</div>
        <div>
          <strong>Student Community</strong>
          <small>Learn and grow together</small>
        </div>
      </div>

      <div class="floating-card floating-two">
        <div class="floating-icon">✦</div>
        <div>
          <strong>AI Campus Assistant</strong>
          <small>Find answers faster</small>
        </div>
      </div>

    </div>

  </div>
</section>


<!-- ==============================
     COMMUNITY STATS
================================= -->

<section class="stats">
  <div class="container stats-grid">

    <div class="stat">
      <h3>01</h3>
      <p>Connected Community</p>
    </div>

    <div class="stat">
      <h3>24/7</h3>
      <p>Access to Campus Information</p>
    </div>

    <div class="stat">
      <h3>AI</h3>
      <p>Smart Campus Assistance</p>
    </div>

    <div class="stat">
      <h3>∞</h3>
      <p>Opportunities to Collaborate</p>
    </div>

  </div>
</section>


<!-- ==============================
     COMMUNITY CHANNELS
================================= -->

<section class="section channels-section" id="channels">
  <div class="container">

    <div class="section-heading">
      <div>
        <div class="eyebrow">Find your community</div>
        <h2>Everything happens in channels.</h2>
        <p>
          From classroom discussions to campus events, find the
          right space to connect, collaborate, and stay informed.
        </p>
      </div>

      <a href="#join" class="btn btn-outline">
        Explore Community →
      </a>
    </div>

    <div class="channel-grid">

      <article class="channel-card">
        <div class="channel-icon">📚</div>
        <h3>Academic Hub</h3>
        <p>
          Discuss subjects, share study materials, ask questions,
          and collaborate with classmates on academic work.
        </p>
        <div class="channel-footer">
          <span>Learning & academics</span>
          <a href="#join">Explore →</a>
        </div>
      </article>

      <article class="channel-card">
        <div class="channel-icon">💻</div>
        <h3>Tech & Projects</h3>
        <p>
          Share coding ideas, find project partners, discuss
          technologies, and showcase your latest innovations.
        </p>
        <div class="channel-footer">
          <span>Innovation & coding</span>
          <a href="#join">Explore →</a>
        </div>
      </article>

      <article class="channel-card">
        <div class="channel-icon">📢</div>
        <h3>Campus Announcements</h3>
        <p>
          Stay updated with college notices, academic schedules,
          important announcements, and campus information.
        </p>
        <div class="channel-footer">
          <span>College updates</span>
          <a href="#announcements">Explore →</a>
        </div>
      </article>

      <article class="channel-card">
        <div class="channel-icon">🎯</div>
        <h3>Placements & Careers</h3>
        <p>
          Discover internship opportunities, placement updates,
          interview preparation resources, and career guidance.
        </p>
        <div class="channel-footer">
          <span>Career development</span>
          <a href="#join">Explore →</a>
        </div>
      </article>

      <article class="channel-card">
        <div class="channel-icon">🎉</div>
        <h3>Clubs & Events</h3>
        <p>
          Connect with student clubs, discover campus activities,
          and participate in cultural and technical events.
        </p>
        <div class="channel-footer">
          <span>Campus life</span>
          <a href="#events">Explore →</a>
        </div>
      </article>

      <article class="channel-card">
        <div class="channel-icon">🌐</div>
        <h3>Alumni Network</h3>
        <p>
          Connect with AITAM alumni, learn from their experiences,
          and discover mentorship and networking opportunities.
        </p>
        <div class="channel-footer">
          <span>Alumni connections</span>
          <a href="#join">Explore →</a>
        </div>
      </article>

    </div>
  </div>
</section>


<!-- ==============================
     AI CONTENT VERIFICATION
================================= -->

<section class="section qa-verify-section" id="ai-verification">
  <div class="container qa-verify-grid">

    <div class="qa-verify-copy">
      <div class="eyebrow">AI-powered content moderation</div>

      <h2>Making Academic Discussions Smarter with AI.</h2>

      <p>
        AITAM-SLACK uses Artificial Intelligence to analyze questions and
        answers shared by students, helping maintain meaningful, relevant,
        and quality academic discussions across the platform.
      </p>

      <ul class="qa-verify-features">
        <li>
          <span class="qa-verify-feature-icon" aria-hidden="true">✓</span>
          Detect irrelevant and meaningless questions and answers.
        </li>
        <li>
          <span class="qa-verify-feature-icon" aria-hidden="true">✓</span>
          Identify spam, duplicate, and inappropriate content.
        </li>
        <li>
          <span class="qa-verify-feature-icon" aria-hidden="true">✓</span>
          Automatically report suspicious content to administrators.
        </li>
        <li>
          <span class="qa-verify-feature-icon" aria-hidden="true">✓</span>
          Support administrators in maintaining a quality learning environment.
        </li>
      </ul>

    </div>

    <div class="qa-verify-timeline" id="ai-verification-workflow">
      <div class="qa-verify-flow">
      <article class="qa-verify-stage">
        <div class="qa-verify-marker" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M7 3.75h7l4.25 4.5v11A1.75 1.75 0 0 1 16.5 21h-9A1.75 1.75 0 0 1 5.75 19.25v-13A2.5 2.5 0 0 1 8.25 3.75Z"/>
            <path d="M14 4v5h4.5M9 13h6M9 16.5h6"/>
          </svg>
        </div>
        <div class="qa-verify-stage-content">
          <h3>Student Submission</h3>
          <p>Students submit their questions and answers on the AITAM-SLACK platform.</p>
        </div>
      </article>

      <article class="qa-verify-stage">
        <div class="qa-verify-marker" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M12 3.5v2M12 18.5v2M3.5 12h2m13 0h2M6 6l1.4 1.4m9.2 9.2L18 18m0-12-1.4 1.4m-9.2 9.2L6 18"/>
            <circle cx="12" cy="12" r="5.25"/>
            <path d="m10.25 12 1.2 1.2 2.4-2.6"/>
          </svg>
        </div>
        <div class="qa-verify-stage-content">
          <h3>AI Content Analysis</h3>
          <p>AI analyzes submitted content based on quality and potential issues.</p>
          <div class="qa-verify-checks" aria-label="Analysis checks">
            <span>Relevance</span>
            <span>Clarity</span>
            <span>Meaningfulness</span>
            <span>Spam Detection</span>
            <span>Duplicate Content</span>
          </div>
        </div>
      </article>

      <article class="qa-verify-stage">
        <div class="qa-verify-marker" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M12 3.5 19 6v5.3c0 4.4-2.8 7.6-7 9.2-4.2-1.6-7-4.8-7-9.2V6l7-2.5Z"/>
            <path d="m9 12 2 2 4-4"/>
          </svg>
        </div>
        <div class="qa-verify-stage-content">
          <h3>Issue Detection</h3>
          <p>AI identifies potentially unnecessary, irrelevant, duplicate, or inappropriate content and generates a report.</p>
          <div class="qa-verify-outcomes">
            <div class="qa-verify-outcome">
              <strong>Acceptable Content</strong>
              <p>Passes initial AI quality checks and continues through the normal platform workflow.</p>
            </div>
            <div class="qa-verify-outcome is-review">
              <strong>Potentially Unnecessary Content</strong>
              <p>A possible issue is identified and an automated report is generated for administrator review.</p>
            </div>
          </div>
        </div>
      </article>

      <article class="qa-verify-stage">
        <div class="qa-verify-marker" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="8" r="3.25"/>
            <path d="M5 20a7 7 0 0 1 14 0M18 5.5a3 3 0 0 1 0 5.8M20 14a5.4 5.4 0 0 1 2.5 4.6"/>
          </svg>
        </div>
        <div class="qa-verify-stage-content">
          <h3>Admin Review</h3>
          <p>Generated reports appear in the appropriate admin sections, where administrators review the content and decide what action should be taken.</p>
          <div class="qa-verify-report-types" aria-label="Admin report sections">
            <span>Reported Questions</span>
            <span>Reported Answers</span>
          </div>
        </div>
      </article>
      </div>

      <p class="qa-verify-disclaimer">
        Illustrative timeline only — this section does not process submissions. AI-generated reports assist administrators; final moderation decisions remain with them.
      </p>
    </div>

  </div>
</section>


<!-- ==============================
     ANNOUNCEMENTS
================================= -->

<section class="section announcements" id="announcements">
  <div class="container">

    <div class="section-heading">
      <div>
        <div class="eyebrow">Stay in the loop</div>
        <h2>What's happening at AITAM?</h2>
        <p>
          A dedicated space for important campus updates,
          notices, and information from the college community.
        </p>
      </div>

      <a href="#events" class="btn btn-outline">
        View Events →
      </a>
    </div>

    <div class="announcement-grid">

      <article class="announcement-card">
        <span class="announcement-tag">CAMPUS COMMUNITY</span>

        <h3>
          Welcome to AITAM Slack — your college community starts here.
        </h3>

        <p>
          Connect with fellow students, faculty, and alumni.
          Explore academic channels, campus updates, career resources,
          and opportunities to collaborate with the AITAM community.
        </p>

        <div class="announcement-meta">
          <span>✦</span>
          <span>AITAM Slack Community</span>
        </div>
      </article>

      <article class="announcement-card">
        <span class="announcement-tag">COMMUNITY UPDATES</span>

        <h3>
          Stay connected with campus information.
        </h3>

        <p>
          Use the announcements channel to access college notices,
          academic updates, and important information shared by
          authorized college administrators.
        </p>

        <div class="announcement-meta">
          <span>📢</span>
          <span>Campus Announcements</span>
        </div>
      </article>

    </div>

  </div>
</section>


<!-- ==============================
     EVENTS
================================= -->

<section class="section events-section" id="events">
  <div class="container">

    <div class="section-heading">
      <div>
        <div class="eyebrow">Be part of campus life</div>
        <h2>Discover. Participate. Grow.</h2>
        <p>
          Find events, technical activities, workshops, and
          opportunities to make the most of your college experience.
        </p>
      </div>
    </div>

    <div class="events-list">

      <article class="event-card">
        <div class="event-date">
          <strong>01</strong>
          <span>EVENT</span>
        </div>

        <div class="event-info">
          <h3>Technical Workshops</h3>
          <p>
            Explore emerging technologies and develop practical skills.
          </p>
        </div>

        <span class="event-type">Technical</span>
      </article>

      <article class="event-card">
        <div class="event-date">
          <strong>02</strong>
          <span>EVENT</span>
        </div>

        <div class="event-info">
          <h3>Student Club Activities</h3>
          <p>
            Connect with clubs, participate in activities,
            and discover new interests.
          </p>
        </div>

        <span class="event-type">Campus Life</span>
      </article>

      <article class="event-card">
        <div class="event-date">
          <strong>03</strong>
          <span>EVENT</span>
        </div>

        <div class="event-info">
          <h3>Career & Placement Sessions</h3>
          <p>
            Discover career resources, interview preparation,
            and professional development opportunities.
          </p>
        </div>

        <span class="event-type">Career</span>
      </article>

    </div>

    <p style="margin-top:18px;font-size:12px;color:var(--text-muted)">
      Sample event categories. Replace these with verified college
      events, dates, and registration links.
    </p>

  </div>
</section>


<!-- ==============================
     CALL TO ACTION
================================= -->

<section class="cta-section" id="join">
  <div class="container">

    <div class="cta-box">
      <h2>Your AITAM community is waiting.</h2>

      <p>
        Connect with your college community, explore opportunities,
        and make campus life more collaborative with AITAM Slack.
      </p>

      <button class="btn btn-light" id="joinButton">
        Get Started <span>→</span>
      </button>
    </div>

  </div>
</section>

</main>


<!-- ==============================
     SEARCH MODAL
================================= -->

<div class="search-overlay" id="searchOverlay">
  <div class="search-box">
    <form id="searchForm">
      <input type="search" id="siteSearch"
             placeholder="Search channels, events, announcements..."
             aria-label="Search website">
    </form>
    <p>Press Enter to search this page.</p>
  </div>
</div>


<!-- Notification -->
<div class="toast" id="toast" role="status" aria-live="polite"></div>


<!-- ==============================
     JAVASCRIPT
================================= -->

<script>
/* --------------------------------
   MOBILE NAVIGATION
--------------------------------- */

const menuToggle = document.getElementById("menuToggle");
const navLinks = document.getElementById("navLinks");

menuToggle.addEventListener("click", () => {
  const isOpen = navLinks.classList.toggle("active");

  menuToggle.setAttribute("aria-expanded", isOpen);
  menuToggle.textContent = isOpen ? "✕" : "☰";
});

navLinks.querySelectorAll("a").forEach(link => {
  link.addEventListener("click", () => {
    navLinks.classList.remove("active");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.textContent = "☰";
  });
});


/* --------------------------------
   DARK / LIGHT MODE
--------------------------------- */

const themeToggle = document.getElementById("themeToggle");

function updateThemeButton() {
  const isDark = document.documentElement.dataset.theme === "dark";

  themeToggle.textContent = isDark ? "☀" : "☾";
  themeToggle.setAttribute(
    "aria-label",
    isDark ? "Switch to light mode" : "Switch to dark mode"
  );
}

themeToggle.addEventListener("click", () => {
  const isDark = document.documentElement.dataset.theme === "dark";

  document.documentElement.dataset.theme = isDark ? "light" : "dark";

  updateThemeButton();
});

updateThemeButton();


/* --------------------------------
   SEARCH MODAL
--------------------------------- */

const searchButton = document.getElementById("searchButton");
const searchOverlay = document.getElementById("searchOverlay");
const siteSearch = document.getElementById("siteSearch");

function openSearch() {
  searchOverlay.classList.add("active");
  setTimeout(() => siteSearch.focus(), 100);
}

function closeSearch() {
  searchOverlay.classList.remove("active");
}

searchButton.addEventListener("click", openSearch);

searchOverlay.addEventListener("click", event => {
  if (event.target === searchOverlay) {
    closeSearch();
  }
});

document.addEventListener("keydown", event => {
  if (event.key === "Escape") {
    closeSearch();
  }
});

document.getElementById("searchForm").addEventListener("submit", event => {
  event.preventDefault();

  const query = siteSearch.value.trim().toLowerCase();

  if (!query) return;

  const searchableElements = document.querySelectorAll(
    ".channel-card, .announcement-card, .event-card, .qa-verify-copy"
  );

  let matchFound = false;

  searchableElements.forEach(element => {
    const matches = element.textContent.toLowerCase().includes(query);

    if (matches && !matchFound) {
      element.scrollIntoView({
        behavior: "smooth",
        block: "center"
      });

      element.style.outline = "3px solid var(--color-blue-500)";

      setTimeout(() => {
        element.style.outline = "";
      }, 2000);

      matchFound = true;
    }
  });

  closeSearch();

  if (!matchFound) {
    showToast("No matching information found. Try another search.");
  }
});


/* --------------------------------
   TOAST NOTIFICATION
--------------------------------- */

const toast = document.getElementById("toast");
let toastTimer;

function showToast(message) {
  toast.textContent = message;
  toast.classList.add("show");

  clearTimeout(toastTimer);

  toastTimer = setTimeout(() => {
    toast.classList.remove("show");
  }, 3500);
}


/* --------------------------------
   GET STARTED BUTTON
--------------------------------- */

document.getElementById("joinButton").addEventListener("click", () => {
  showToast(
    "Welcome to AITAM Slack! Connect your registration system to get started."
  );
});


/* --------------------------------
   FOOTER YEAR
--------------------------------- */

document.getElementById("currentYear").textContent =
  new Date().getFullYear();

</script>
<?php include 'footer.php'; ?>

</body>
</html>
