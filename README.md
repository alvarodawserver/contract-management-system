# Contract Management System — Sanlúcar de Barrameda Town Hall

A web application built during a Web App Development (DAW) internship at the Sanlúcar de Barrameda Town Hall, designed to digitize and streamline the creation, modification, and formalization of contracts under the annual contracting plan.

> ⚠️ This repository showcases the work developed during the internship. Sensitive data, credentials, and internal configuration have been removed or replaced with mock data for public display.

## Overview

Spanish public institutions manage contracts through an annual contracting plan, where department heads (and employees they delegate to) are responsible for registering, updating, and formalizing contracts before they expire. This project replaced a manual, error-prone process with a centralized system that gives each department visibility and control over its own contracts, while automating the reminders that used to be tracked by hand.

## Key Features

- **Departmental dashboard ("Control de Mando")** — filterable view of contracts scoped to each department, so department heads and delegated employees only see what's relevant to them.
- **Role-based access** — department heads can manage and delegate contracts to specific employees, who then get scoped permissions over the contracts assigned to them.
- **Contract lifecycle management** — creation, modification, and formalization of contracts, tracked through their full lifecycle.
- **Automated email notifications** — a scheduled system notifies the responsible party every few months as a contract's formalization deadline approaches, preventing contracts from lapsing due to missed deadlines. Integrated with the Town Hall's internal email domain.

## Technical Highlight: Legacy System Integration

One of the main engineering challenges of this project was integrating with a **pre-existing legacy system**, built years earlier by previous interns using the **Yii framework**, still running on a **Windows XP server**. Rather than rebuilding that system from scratch, this project's biggest technical effort went into designing and implementing a **bridge between the legacy Yii application and the new Laravel back-end**, enabling reliable data exchange between the old and new platforms without disrupting the Town Hall's existing infrastructure.

## Tech Stack

**Back-end:** PHP, Laravel
**Front-end:** React, TypeScript
**Database:** PostgreSQL
**Deployment:** XAMPP (dedicated server)
**Legacy integration:** Yii (bridge layer)

## Status

This project was developed as part of a university internship (Feb 2026 – May 2026) and is deployed internally at the Town Hall. It is not publicly accessible, as it handles internal municipal contract data.

## Author

**Álvaro Vidal** — Web App Development (DAW) student
[GitHub](https://github.com/alvarodawserver) · [LinkedIn](#)
