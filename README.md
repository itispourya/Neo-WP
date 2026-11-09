# NeoNotion (Neo OS)

> **A Connected Business Operating System & Modular Workspace Architecture for Modern Enterprises and Cross-Border SMBs.**  
> Founded & Architected by **[Pourya Omidian](https://neonotion.net)** · [NeoNotion.net](https://neonotion.net)

[![License: Apache 2.0](https://img.shields.io/badge/License-Apache%202.0-blue.svg)](LICENSE)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x%20%7C%207.x-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![React](https://img.shields.io/badge/React-19.0-61DAFB?logo=react&logoColor=black)](https://react.dev/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.0-38B2AC?logo=tailwind-css&logoColor=white)](https://tailwindcss.com/)
[![Data Sovereignty](https://img.shields.io/badge/Data_Sovereignty-100%25_Guaranteed-success)](https://neonotion.net)
[![Author](https://img.shields.io/badge/Author-Pourya_Omidian-orange)](mailto:itispourya@gmail.com)

---

## 🌐 Overview

**NeoNotion** (`neonotion.net`) is an open, modular business operating system designed to eradicate departmental data silos and provide organizations with sovereign, high-velocity operational workflows.

Traditional SaaS ecosystems fragment company intelligence across disconnected CRMs, ERP tools, financial spreadsheets, and task managers. NeoNotion brings these workflows into a single cohesive, high-performance web interface while guaranteeing **100% client data sovereignty**.

---

## ⚡ Core Principles & Architecture

### 1. 🛡️ Absolute Data Sovereignty
- **1-Click Raw Export**: Organizations retain complete ownership of their data with instant export capabilities to JSON, SQL, and CSV.
- **Zero Public AI Training**: Client financial ledgers, trade secrets, and customer data are never monetized, leaked, or used to train public language models.
- **Multi-Region Deployment**: Engineered for compliant distributed deployment across EU (Frankfurt), UK (London), UAE, and Bahrain data hubs.

### 2. 🧩 Unified Modular Workflow
- **Connected Workspace**: Integrates customer relationship management (CRM), inventory tracking, multi-currency invoicing, and project lifecycles.
- **Reactive State Management**: Real-time optimistic UI updates with zero perceptible latency.
- **Accessible & Responsive**: Built following modern WCAG AA accessibility standards, responsive grid layouts, and keyboard-first navigation.

### 3. ⚙️ Modern Technology Stack
- **Frontend Framework**: React 19 with strict TypeScript
- **Styling Engine**: Tailwind CSS v4 via `@tailwindcss/vite`
- **Build System**: Vite 8 for blazing-fast HMR and optimized production bundles
- **Iconography & Motion**: Lucide React and Motion
- **Backend & Proxy Support**: Express 4 with TypeScript execution (`tsx`)
- **AI Integration Support**: Server-side `@google/genai` proxy architecture with strict API key encapsulation

---

## 🚀 Quick Start

### Prerequisites
- **Node.js**: `v18.0.0` or higher (Node 20+ recommended)
- **Package Manager**: `npm`, `pnpm`, or `bun`

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/itispourya/neonotion.git
   cd neonotion
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Configure environment variables:**
   ```bash
   cp .env.example .env
   ```
   Edit `.env` to supply any required configuration keys:
   ```env
   # GEMINI_API_KEY: Optional server-side AI processing
   GEMINI_API_KEY="your_api_key_here"

   # APP_URL: Base host URL for deployment
   APP_URL="http://localhost:3000"
   ```

4. **Launch development server:**
   ```bash
   npm run dev
   ```
   Open your browser at `http://localhost:3000`.

5. **Build for production:**
   ```bash
   npm run build
   ```

6. **Type check & lint:**
   ```bash
   npm run lint
   ```

---

## 📁 Repository Structure

```text
neonotion/
├── .github/
│   ├── workflows/
│   │   └── ci.yml             # Continuous integration (lint & build checks)
│   └── ISSUE_TEMPLATE/        # Standard bug & feature request templates
├── src/
│   ├── App.tsx                # NeoNotion core application entrypoint & dashboard
│   ├── main.tsx               # DOM root mount
│   └── index.css              # Global styles & Tailwind CSS v4 setup
├── index.html                 # HTML shell with OpenGraph and SEO metadata
├── metadata.json              # Project configuration and capabilities manifest
├── package.json               # Package manifests and npm scripts
├── tsconfig.json              # TypeScript strict configuration
├── vite.config.ts             # Vite build and Tailwind integration
├── LICENSE                    # Apache 2.0 Open Source License
├── CONTRIBUTING.md            # Guidelines for open-source contributors
├── CODE_OF_CONDUCT.md         # Community standards & Contributor Covenant
├── SECURITY.md                # Vulnerability disclosure & security policies
└── README.md                  # Project documentation (this file)
```

---

## 👥 Author & Leadership

- **Founder & Lead Architect**: [Pourya Omidian](mailto:itispourya@gmail.com)
- **Organization**: Neos Technologies Inc. / [NeoNotion.net](https://neonotion.net)
- **Email**: [itispourya@gmail.com](mailto:itispourya@gmail.com)

---

## 🤝 Contributing

We welcome contributions from the open-source community! Whether fixing bugs, improving documentation, or proposing new modular capabilities, please check [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the **Apache License 2.0** - see the [LICENSE](LICENSE) file for details.

Copyright (c) 2024-2026 Pourya Omidian / Neos Technologies Inc. / NeoNotion.net.

---

## 🇮🇷 راهنمای فارسی (Persian Summary)

**نئو‌نوشن (NeoNotion.net)** یک سیستم عامل جامع ابری و پلتفرم یکپارچه برای کسب‌وکارهای مدرن و سازمان‌های بین‌المللی است که توسط **پوریا امیدیان (Pourya Omidian)** معماری و توسعه داده شده است.

### ویژگی‌های کلیدی:
- **حاکمیت کامل داده‌ها (Data Sovereignty)**: امکان خروجی گرفتن فوری (JSON، SQL، CSV) با یک کلیک بدون وابستگی به ارائه‌دهنده (Zero Vendor Lock-in).
- **حفظ ۱۰۰٪ حریم خصوصی**: داده‌ها و دفاتر مالی کاربران هرگز فروخته نشده و برای آموزش مدل‌های عمومی هوش مصنوعی استفاده نمی‌شوند.
- **یکپارچه‌سازی ماژولار**: پیوند یکپارچه میان ارتباط با مشتریان (CRM)، مدیریت زنجیره و انبارداری (ERP)، صدور صورتحساب و هوش تجاری.
- **آماده‌سازی برای گیت‌هاب**: این مخزن دارای فایل‌های استاندارد متن‌باز شامل مجوز Apache 2.0، راهنمای مشارکت (CONTRIBUTING)، تست‌های خودکار CI و تمپلیت‌های ایشو است.
