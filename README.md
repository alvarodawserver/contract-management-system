# Contract Management System

A contract management module for a town hall's annual contracting plan: department heads and the employees they delegate to register, edit, formalize and track contracts through their full lifecycle, with a management dashboard, an audit trail of every change, and automated email reminders.

> This is a **solo portfolio rebuild**, not the original production system. It's inspired by a module I built during a Web App Development (DAW) internship at the Sanlúcar de Barrameda Town Hall, together with a fellow intern. This version is a from-scratch rewrite: a different stack, no legacy integration, and entirely local, with fictional data. It has never been deployed and holds no real municipal data. See [Status](#status) for details.

## Overview

Spanish public institutions manage contracts through an annual contracting plan, where department heads (and employees they delegate to) are responsible for registering, editing and formalizing contracts before they lapse. This project models that workflow: each department only sees and manages its own contracts, formalization is derived from the contract's own data rather than a status a user has to remember to flip, and a scheduled job reminds whoever registered a contract before its deadline passes.

Since there's no real authentication system to demo, a simulated "act as" switcher lets you experience the app as any seeded user — admin, department head or delegated employee — and see how the interface and the available actions change with the role.

## Key Features

- **Role-based access** — an admin sees every department; a department head manages everything in their own; a delegated employee can create contracts and view their department's, but only edit, delete or restore the ones they created themselves.
- **Dashboard ("Control de Mando")** — for department heads and admins: summary cards (formalized/pending/lapsed counts, total amount) and a contract table, both filterable by department and date range, with quick actions per row.
- **"My contracts"** — the equivalent day-to-day list for everyone, with search and status filters.
- **Full contract lifecycle** — a contract can be created with just a title and completed later as its data becomes known. It's marked _formalized_ automatically once its final amount, both dates and the responsible party are all filled in — there's no separate status field to keep in sync. A compact "Formalize" dialog fills in exactly those fields without opening the full edit form.
- **Soft delete, restore and permanent delete** — a deleted contract goes to a trash that its own creator, its department head, or an admin can restore from; only an admin can delete one for good.
- **Movement history** — every create, edit, delete, restore and permanent delete is logged with who did it and the old/new values, in both a per-contract view and a cross-department log for managers.
- **Automated formalization reminders** — a scheduled command emails whoever registered a contract, weekly, until either it's formalized or under a week remains before its deadline; an unformalized contract past its deadline is automatically moved to the trash.

## About the Original Internship Project

The original town hall project's biggest engineering challenge had nothing to do with contracts themselves: it was integrating with a **pre-existing legacy system**, built years earlier by previous interns on the **Yii framework** and still running on a **Windows XP server**. Rather than rebuilding that system, most of the effort went into a **bridge between the legacy Yii application and the new Laravel back-end**, keeping data in sync between the old and new platforms without disrupting the town hall's existing infrastructure.

That bridge — and the legacy server it talked to — is **not part of this repository**. This rebuild is scoped to the contract management module alone, running as a standalone application with its own local database.

## Tech Stack

- **Back-end:** PHP 8.4, Laravel 13
- **Front-end:** React 19, TypeScript, Inertia.js v3
- **Styling / UI:** Tailwind CSS v4, Radix UI primitives
- **Database:** SQLite (local file, zero setup)
- **Mail:** Laravel's `log` driver by default; Mailtrap supported out of the box for a real-looking local inbox
- **Testing & quality:** Pest, Larastan (PHPStan), Laravel Pint, GitHub Actions CI

## Running Locally

```bash
composer setup   # installs dependencies, generates the app key, migrates the database, builds assets
composer run dev # serves the app, watches assets, and runs the queue listener
```

The database seeds itself with a fictional town hall: one admin, three department heads and three delegated employees, each with their own contracts. There's no login — use the account switcher in the sidebar's user menu to try the app as any of them.

## Screenshots

<!-- TODO: dashboard, my contracts, create/edit form, movement history -->

## Demo Video

<!-- TODO -->

## Status

This is an active personal project, built and maintained solo for my portfolio. It runs entirely locally with fictional data and is not deployed anywhere public.

## Author

**Álvaro Vidal** — Web App Development (DAW) graduate
[GitHub](https://github.com/alvarodawserver) · [LinkedIn](https://www.linkedin.com/in/%C3%A1lvaro-vidal-ca%C3%B1as-117163302/)
