/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 * 
 * NeoNotion (Neo OS) - Connected Business Operating System
 * Architected by Pourya Omidian (https://neonotion.net)
 */

import React, { useState } from 'react';
import {
  ShieldCheck,
  Database,
  Globe,
  Terminal,
  FileText,
  Download,
  Copy,
  Check,
  ExternalLink,
  Layers,
  Activity,
  Server,
  Lock,
  Boxes,
  Users,
  Building2,
  Code2,
  GitBranch,
  BookOpen,
  ArrowRight,
  Sparkles
} from 'lucide-react';

export default function App() {
  const [activeTab, setActiveTab] = useState<'overview' | 'modules' | 'sovereignty' | 'docs' | 'persian'>('overview');
  const [copiedCommand, setCopiedCommand] = useState<string | null>(null);
  const [exportFormat, setExportFormat] = useState<'json' | 'sql' | 'csv'>('json');
  const [exportStatus, setExportStatus] = useState<string | null>(null);

  const copyToClipboard = (text: string, id: string) => {
    navigator.clipboard.writeText(text);
    setCopiedCommand(id);
    setTimeout(() => setCopiedCommand(null), 2000);
  };

  const handleSimulateExport = () => {
    setExportStatus('Generating cryptographic package...');
    setTimeout(() => {
      setExportStatus(`Export complete: neonotion_full_backup.${exportFormat} (Verified SHA-256)`);
      setTimeout(() => setExportStatus(null), 4000);
    }, 900);
  };

  const exportSnippets = {
    json: `{\n  "organization": "Acme Holdings Group",\n  "sovereignty": "guaranteed",\n  "entities": [\n    { "id": "ENT-01", "name": "Global Trading LLC", "jurisdiction": "DE-FRA" },\n    { "id": "ENT-02", "name": "Apex Logistics FZE", "jurisdiction": "AE-DXB" }\n  ],\n  "crm_contacts_count": 1420,\n  "erp_ledgers_balance": "$4,850,200.00",\n  "zero_ai_training_verified": true\n}`,
    sql: `-- NeoNotion Sovereign Data Export\n-- Generated on demand. No vendor lock-in.\nCREATE TABLE organizations (id UUID PRIMARY KEY, name VARCHAR(255), jurisdiction VARCHAR(50));\nINSERT INTO organizations VALUES ('e9b01f', 'Acme Holdings Group', 'EU-Frankfurt');\nCREATE TABLE financial_ledgers (id UUID, org_id UUID, currency VARCHAR(3), amount NUMERIC(14,2));\nINSERT INTO financial_ledgers VALUES ('a1b2c3', 'e9b01f', 'EUR', 4850200.00);`,
    csv: `Entity_ID,Entity_Name,Jurisdiction,Primary_Currency,Active_Workflows,Data_Center\nENT-01,Global Trading LLC,Germany (EU),EUR,24,Frankfurt (FRA-01)\nENT-02,Apex Logistics FZE,United Arab Emirates,AED,18,Dubai (DXB-02)\nENT-03,Britannia Ventures Ltd,United Kingdom,GBP,12,London (LHR-01)`
  };

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-indigo-600 selection:text-white">
      {/* Top Bar Contract: Brand Wordmark — Navigation Links — Primary Action */}
      <header className="border-b border-slate-800/80 bg-slate-950/90 backdrop-blur-md sticky top-0 z-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-8">
          {/* Zone 1: Brand */}
          <div className="flex items-center gap-3 shrink-0">
            <div className="w-9 h-9 rounded-lg bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center shadow-lg shadow-indigo-500/20">
              <Boxes className="w-5 h-5 text-white" />
            </div>
            <a href="https://neonotion.net" target="_blank" rel="noopener noreferrer" className="text-lg font-bold tracking-tight text-white hover:text-indigo-400 transition-colors">
              NeoNotion
            </a>
          </div>

          {/* Zone 2: Navigation Links */}
          <nav className="hidden md:flex items-center gap-6 text-sm font-medium text-slate-400">
            <button
              onClick={() => setActiveTab('overview')}
              className={`hover:text-white transition-colors cursor-pointer ${activeTab === 'overview' ? 'text-indigo-400' : ''}`}
            >
              Overview
            </button>
            <button
              onClick={() => setActiveTab('modules')}
              className={`hover:text-white transition-colors cursor-pointer ${activeTab === 'modules' ? 'text-indigo-400' : ''}`}
            >
              Neo OS Modules
            </button>
            <button
              onClick={() => setActiveTab('sovereignty')}
              className={`hover:text-white transition-colors cursor-pointer ${activeTab === 'sovereignty' ? 'text-indigo-400' : ''}`}
            >
              Data Sovereignty
            </button>
            <button
              onClick={() => setActiveTab('docs')}
              className={`hover:text-white transition-colors cursor-pointer ${activeTab === 'docs' ? 'text-indigo-400' : ''}`}
            >
              Open Source Docs
            </button>
            <button
              onClick={() => setActiveTab('persian')}
              className={`hover:text-white transition-colors cursor-pointer ${activeTab === 'persian' ? 'text-indigo-400' : ''}`}
            >
              راهنمای فارسی
            </button>
          </nav>

          {/* Zone 3: Primary Action */}
          <div className="flex items-center gap-3 shrink-0">
            <a
              href="https://neonotion.net"
              target="_blank"
              rel="noopener noreferrer"
              className="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-md shadow-indigo-600/30 flex items-center gap-1.5"
            >
              <span>Visit NeoNotion.net</span>
              <ExternalLink className="w-3.5 h-3.5" />
            </a>
          </div>
        </div>
      </header>

      {/* Main Content Viewport */}
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
        {/* Tab 1: Overview */}
        {activeTab === 'overview' && (
          <div className="space-y-12">
            {/* Hero Section */}
            <section className="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-900/80 via-slate-900/40 to-slate-950 p-8 sm:p-12">
              <div className="max-w-3xl space-y-6">
                <div className="flex items-center gap-2 text-xs font-mono text-indigo-400 tracking-wider uppercase">
                  <span>Open Source Business OS</span>
                  <span aria-hidden="true">·</span>
                  <span>Architected by Pourya Omidian</span>
                </div>

                <h1 className="text-3xl sm:text-5xl font-extrabold tracking-tight text-white leading-tight">
                  Sovereign Business Operating System for Connected Organizations
                </h1>

                <p className="text-base sm:text-lg text-slate-300 leading-relaxed">
                  NeoNotion eradicates departmental data silos for cross-border SMBs and multi-entity enterprises. 
                  Unify CRM, ERP, and operational intelligence into a single high-performance workspace while guaranteeing 
                  <strong className="text-white font-semibold"> 100% data sovereignty</strong> and zero public AI model training on your private ledgers.
                </p>

                <div className="flex flex-wrap items-center gap-4 pt-2">
                  <button
                    onClick={() => setActiveTab('docs')}
                    className="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-all shadow-lg shadow-indigo-600/30 flex items-center gap-2 cursor-pointer"
                  >
                    <BookOpen className="w-4 h-4" />
                    <span>Explore GitHub Documentation</span>
                  </button>
                  <button
                    onClick={() => setActiveTab('sovereignty')}
                    className="px-5 py-2.5 text-sm font-semibold text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700/80 border border-slate-700 rounded-lg transition-all flex items-center gap-2 cursor-pointer"
                  >
                    <ShieldCheck className="w-4 h-4 text-emerald-400" />
                    <span>Data Sovereignty Demo</span>
                  </button>
                </div>
              </div>

              {/* Founder & Project Metadata Badge */}
              <div className="mt-8 pt-6 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-400 font-mono">
                <div className="flex items-center gap-3">
                  <span className="text-slate-500">Founder & Lead Architect:</span>
                  <span className="text-slate-200 font-semibold">Pourya Omidian</span>
                  <span className="text-slate-500">·</span>
                  <a href="mailto:itispourya@gmail.com" className="text-indigo-400 hover:underline">itispourya@gmail.com</a>
                </div>
                <div className="flex items-center gap-3">
                  <span>License: <strong className="text-slate-200">Apache 2.0</strong></span>
                  <span className="text-slate-500">·</span>
                  <span>Stack: <strong className="text-slate-200">React 19 / Vite / Tailwind v4</strong></span>
                </div>
              </div>
            </section>

            {/* Quick Terminal Quickstart Box */}
            <section className="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-sm font-medium text-slate-200">
                  <Terminal className="w-4 h-4 text-indigo-400" />
                  <span>Quick Clone & Development Launch</span>
                </div>
                <span className="text-xs text-slate-500 font-mono">Ready for GitHub Open Source</span>
              </div>

              <div className="bg-slate-950 rounded-lg p-4 font-mono text-xs sm:text-sm text-slate-300 border border-slate-800/80 flex items-center justify-between gap-4 overflow-x-auto">
                <span className="text-indigo-300 select-all">
                  git clone https://github.com/itispourya/neonotion.git && cd neonotion && npm install && npm run dev
                </span>
                <button
                  onClick={() => copyToClipboard('git clone https://github.com/itispourya/neonotion.git && cd neonotion && npm install && npm run dev', 'clone')}
                  className="px-3 py-1.5 text-xs font-sans font-medium text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 rounded transition-colors flex items-center gap-1.5 shrink-0 cursor-pointer"
                  title="Copy command"
                >
                  {copiedCommand === 'clone' ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                  <span>{copiedCommand === 'clone' ? 'Copied' : 'Copy'}</span>
                </button>
              </div>
            </section>

            {/* Key Pillars Bento Grid */}
            <section className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-3">
                <div className="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                  <ShieldCheck className="w-5 h-5" />
                </div>
                <h3 className="text-lg font-semibold text-white">Client Data Sovereignty</h3>
                <p className="text-sm text-slate-400 leading-relaxed">
                  Complete independence from proprietary cloud lock-in. Export your entire raw business state in standardized JSON, SQL, or CSV formats at any second with 1 click.
                </p>
              </div>

              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-3">
                <div className="w-10 h-10 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                  <Lock className="w-5 h-5" />
                </div>
                <h3 className="text-lg font-semibold text-white">Zero Public AI Scraping</h3>
                <p className="text-sm text-slate-400 leading-relaxed">
                  Strict contractual and architectural guarantee: client financial statements, supplier contracts, and CRM interactions are never exposed or used to train public LLMs.
                </p>
              </div>

              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-3">
                <div className="w-10 h-10 rounded-lg bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
                  <Globe className="w-5 h-5" />
                </div>
                <h3 className="text-lg font-semibold text-white">Multi-Region Infrastructure</h3>
                <p className="text-sm text-slate-400 leading-relaxed">
                  Geographically isolated data residency nodes across Frankfurt (EU), London (UK), UAE, and Bahrain to ensure strict compliance with local financial and data protection laws.
                </p>
              </div>
            </section>
          </div>
        )}

        {/* Tab 2: Neo OS Modules */}
        {activeTab === 'modules' && (
          <div className="space-y-8">
            <div>
              <h2 className="text-2xl font-bold text-white">Neo OS Unified Architecture</h2>
              <p className="text-sm text-slate-400 mt-1">
                Eliminating fragmented micro-tools by connecting core operational engines into a unified database schema.
              </p>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Module 1: CRM & Pipeline */}
              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-indigo-500/10 text-indigo-400 rounded-lg border border-indigo-500/20">
                      <Users className="w-5 h-5" />
                    </div>
                    <div>
                      <h3 className="text-base font-semibold text-white">Sovereign CRM Engine</h3>
                      <p className="text-xs text-slate-500">Client Relationships & Deal Lifecycles</p>
                    </div>
                  </div>
                  <span className="text-xs font-mono px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                    Active
                  </span>
                </div>
                <div className="bg-slate-950 rounded-lg p-3 font-mono text-xs text-slate-300 space-y-2 border border-slate-800/80">
                  <div className="flex justify-between text-slate-400 pb-1 border-b border-slate-800">
                    <span>Contact / Organization</span>
                    <span>Pipeline Value</span>
                    <span>Status</span>
                  </div>
                  <div className="flex justify-between">
                    <span>EuroTrans Logistics GmbH</span>
                    <span className="text-emerald-400 font-semibold">$380,000</span>
                    <span className="text-indigo-400">Proposal Sent</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Gulf Minerals Trading FZE</span>
                    <span className="text-emerald-400 font-semibold">$1,250,000</span>
                    <span className="text-amber-400">Contract Negotiation</span>
                  </div>
                  <div className="flex justify-between">
                    <span>London Maritime Capital</span>
                    <span className="text-emerald-400 font-semibold">$740,000</span>
                    <span className="text-emerald-400">Closed Won</span>
                  </div>
                </div>
              </div>

              {/* Module 2: ERP & Multi-Entity Ledger */}
              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-emerald-500/10 text-emerald-400 rounded-lg border border-emerald-500/20">
                      <Building2 className="w-5 h-5" />
                    </div>
                    <div>
                      <h3 className="text-base font-semibold text-white">Multi-Entity ERP & Financials</h3>
                      <p className="text-xs text-slate-500">Cross-Border Balances & Ledgers</p>
                    </div>
                  </div>
                  <span className="text-xs font-mono px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/20">
                    Synchronized
                  </span>
                </div>
                <div className="bg-slate-950 rounded-lg p-3 font-mono text-xs text-slate-300 space-y-2 border border-slate-800/80">
                  <div className="flex justify-between text-slate-400 pb-1 border-b border-slate-800">
                    <span>Legal Entity</span>
                    <span>Jurisdiction</span>
                    <span>Net Balance</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Neos Europe GmbH</span>
                    <span className="text-slate-400">Frankfurt (EU)</span>
                    <span className="text-emerald-400 font-semibold">€2,410,500</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Neos Gulf FZE</span>
                    <span className="text-slate-400">Dubai (UAE)</span>
                    <span className="text-emerald-400 font-semibold">AED 9,140,000</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Neos Atlantic Ltd</span>
                    <span className="text-slate-400">London (UK)</span>
                    <span className="text-emerald-400 font-semibold">£1,890,200</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Architecture Flow Diagram */}
            <div className="rounded-xl border border-slate-800 bg-slate-900/40 p-6 space-y-4">
              <h3 className="text-base font-semibold text-white flex items-center gap-2">
                <Layers className="w-4 h-4 text-indigo-400" />
                <span>Single-Schema Data Fabric</span>
              </h3>
              <p className="text-sm text-slate-400">
                Instead of synchronizing through third-party webhooks that break, NeoNotion executes on a unified core data schema. When a customer in CRM agrees to terms, the ERP ledger generates an immutable multi-currency invoice automatically without external sync tools.
              </p>
              <div className="grid grid-cols-1 sm:grid-cols-4 gap-3 text-center text-xs font-mono pt-2">
                <div className="p-3 bg-slate-950 rounded border border-slate-800 text-slate-300">
                  1. Unified Auth
                </div>
                <div className="p-3 bg-slate-950 rounded border border-slate-800 text-slate-300">
                  2. Web Crypto Layer
                </div>
                <div className="p-3 bg-slate-950 rounded border border-slate-800 text-slate-300">
                  3. Relational Core
                </div>
                <div className="p-3 bg-slate-950 rounded border border-slate-800 text-indigo-300 font-semibold">
                  4. Raw Export API
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Tab 3: Data Sovereignty Interactive Demo */}
        {activeTab === 'sovereignty' && (
          <div className="space-y-8">
            <div>
              <h2 className="text-2xl font-bold text-white">Live Data Sovereignty Simulator</h2>
              <p className="text-sm text-slate-400 mt-1">
                Test the 1-click sovereign export mechanism. No gated exports, no lock-in fees, and no proprietary formats.
              </p>
            </div>

            <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-6 space-y-6">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-2 p-1 bg-slate-950 rounded-lg border border-slate-800">
                  {(['json', 'sql', 'csv'] as const).map((fmt) => (
                    <button
                      key={fmt}
                      onClick={() => setExportFormat(fmt)}
                      className={`px-3 py-1.5 text-xs font-mono font-medium rounded-md uppercase transition-colors cursor-pointer ${
                        exportFormat === fmt ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-white'
                      }`}
                    >
                      {fmt}
                    </button>
                  ))}
                </div>

                <button
                  onClick={handleSimulateExport}
                  className="px-4 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg transition-all flex items-center gap-2 cursor-pointer shadow-md shadow-emerald-600/20"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span>Execute 1-Click Export ({exportFormat.toUpperCase()})</span>
                </button>
              </div>

              {exportStatus && (
                <div className="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs rounded-lg font-mono animate-fade-in flex items-center gap-2">
                  <Check className="w-4 h-4 text-emerald-400 shrink-0" />
                  <span>{exportStatus}</span>
                </div>
              )}

              {/* Code Export Preview */}
              <div className="relative">
                <div className="absolute top-3 right-3">
                  <button
                    onClick={() => copyToClipboard(exportSnippets[exportFormat], 'export')}
                    className="p-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded text-xs flex items-center gap-1 cursor-pointer transition-colors"
                    title="Copy schema"
                  >
                    {copiedCommand === 'export' ? <Check className="w-3 h-3 text-emerald-400" /> : <Copy className="w-3 h-3" />}
                    <span>{copiedCommand === 'export' ? 'Copied' : 'Copy'}</span>
                  </button>
                </div>
                <pre className="p-4 bg-slate-950 rounded-lg border border-slate-800 text-xs font-mono text-slate-300 overflow-x-auto leading-relaxed">
                  {exportSnippets[exportFormat]}
                </pre>
              </div>

              {/* Multi-Region Node Verification */}
              <div className="pt-4 border-t border-slate-800">
                <h4 className="text-xs font-mono uppercase tracking-wider text-slate-400 mb-3">
                  Multi-Region Node Status & Zero-Leakage Latency
                </h4>
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                  <div className="p-3 bg-slate-950 rounded-lg border border-slate-800/80">
                    <div className="text-xs font-medium text-slate-300">Frankfurt (EU)</div>
                    <div className="text-lg font-bold text-emerald-400 font-mono mt-1">12 ms</div>
                    <div className="text-[11px] text-slate-500">GDPR Sovereign</div>
                  </div>
                  <div className="p-3 bg-slate-950 rounded-lg border border-slate-800/80">
                    <div className="text-xs font-medium text-slate-300">London (UK)</div>
                    <div className="text-lg font-bold text-emerald-400 font-mono mt-1">18 ms</div>
                    <div className="text-[11px] text-slate-500">FCA Compliant</div>
                  </div>
                  <div className="p-3 bg-slate-950 rounded-lg border border-slate-800/80">
                    <div className="text-xs font-medium text-slate-300">Dubai (UAE)</div>
                    <div className="text-lg font-bold text-emerald-400 font-mono mt-1">24 ms</div>
                    <div className="text-[11px] text-slate-500">DIFC Compliant</div>
                  </div>
                  <div className="p-3 bg-slate-950 rounded-lg border border-slate-800/80">
                    <div className="text-xs font-medium text-slate-300">Manama (Bahrain)</div>
                    <div className="text-lg font-bold text-emerald-400 font-mono mt-1">29 ms</div>
                    <div className="text-[11px] text-slate-500">GCC Sovereign</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Tab 4: Open Source Docs */}
        {activeTab === 'docs' && (
          <div className="space-y-8">
            <div className="flex flex-wrap items-center justify-between gap-4">
              <div>
                <h2 className="text-2xl font-bold text-white">Open Source GitHub Readiness</h2>
                <p className="text-sm text-slate-400 mt-1">
                  Complete repository manifests, licenses, and CI/CD pipelines created for publishing on GitHub.
                </p>
              </div>

              <div className="flex items-center gap-2">
                <span className="text-xs font-mono text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded">
                  Status: Ready for Push
                </span>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* File Manifest Card */}
              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
                <h3 className="text-base font-semibold text-white flex items-center gap-2">
                  <FileText className="w-4 h-4 text-indigo-400" />
                  <span>Configured Open Source Files</span>
                </h3>
                <ul className="space-y-2 text-xs font-mono text-slate-300">
                  <li className="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                    <span>README.md</span>
                    <span className="text-emerald-400">Comprehensive Docs & Badges</span>
                  </li>
                  <li className="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                    <span>LICENSE</span>
                    <span className="text-indigo-400">Apache 2.0 (Pourya Omidian)</span>
                  </li>
                  <li className="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                    <span>CONTRIBUTING.md</span>
                    <span className="text-slate-400">Open Source Guidelines</span>
                  </li>
                  <li className="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                    <span>CODE_OF_CONDUCT.md</span>
                    <span className="text-slate-400">Contributor Covenant 2.1</span>
                  </li>
                  <li className="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                    <span>SECURITY.md</span>
                    <span className="text-slate-400">Vulnerability Policy</span>
                  </li>
                  <li className="p-2 rounded bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                    <span>.github/workflows/ci.yml</span>
                    <span className="text-slate-400">Automated Lint & Build CI</span>
                  </li>
                </ul>
              </div>

              {/* GitHub Publish Steps */}
              <div className="rounded-xl border border-slate-800 bg-slate-900/50 p-6 space-y-4">
                <h3 className="text-base font-semibold text-white flex items-center gap-2">
                  <GitBranch className="w-4 h-4 text-emerald-400" />
                  <span>How to Publish to GitHub</span>
                </h3>
                <p className="text-xs text-slate-400 leading-relaxed">
                  To push these initialized open-source files to your GitHub account:
                </p>
                <div className="bg-slate-950 rounded-lg p-3 text-xs font-mono text-slate-300 space-y-2 border border-slate-800/80">
                  <div className="text-slate-500"># 1. Initialize git (if not already done)</div>
                  <div className="text-indigo-300">git init</div>
                  <div className="text-slate-500"># 2. Add all configured files</div>
                  <div className="text-indigo-300">git add .</div>
                  <div className="text-slate-500"># 3. Create your first commit</div>
                  <div className="text-indigo-300">git commit -m "feat: open source release of NeoNotion"</div>
                  <div className="text-slate-500"># 4. Link your remote repository and push</div>
                  <div className="text-indigo-300">git branch -M main</div>
                  <div className="text-indigo-300">git remote add origin https://github.com/itispourya/neonotion.git</div>
                  <div className="text-indigo-300">git push -u origin main</div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Tab 5: Persian Guide (راهنمای فارسی) */}
        {activeTab === 'persian' && (
          <div className="space-y-8" dir="rtl">
            <div className="space-y-2">
              <h2 className="text-2xl font-bold text-white">پروژه نئو‌نوشن (NeoNotion.net) - پوریا امیدیان</h2>
              <p className="text-sm text-slate-400">
                سیستم عامل جامع و ماژولار ابری برای کسب‌وکارها، شرکت‌های چندملیتی و سازمان‌های مدرن با حاکمیت کامل بر داده‌ها.
              </p>
            </div>

            <div className="rounded-xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 space-y-6">
              <div className="space-y-4 text-slate-300 text-sm leading-relaxed">
                <h3 className="text-lg font-bold text-indigo-400">معماری و چشم‌انداز پلتفرم</h3>
                <p>
                  پلتفرم <strong className="text-white">NeoNotion (Neo OS)</strong> توسط <strong className="text-white">پوریا امیدیان</strong> طراحی و پیاده‌سازی شده است تا جزیره‌ای بودن داده‌ها (Data Silos) را در سازمان‌ها به کلی حذف کند. در سامانه‌های سنتی، اطلاعات مشتریان در CRM، دفاتر مالی در نرم‌افزارهای حسابداری و تسک‌ها در پلتفرم‌های دیگر پراکنده بودند. نئو‌نوشن تمام این ماژول‌ها را در یک بستر یکپارچه گرد هم می‌آورد.
                </p>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div className="p-4 bg-slate-950 rounded-lg border border-slate-800 space-y-2">
                  <div className="text-emerald-400 font-bold text-sm">حاکمیت کامل بر داده‌ها</div>
                  <p className="text-xs text-slate-400">
                    امکان دریافت خروجی با فرمت‌های استاندارد JSON، SQL و CSV در هر لحظه تنها با یک کلیک بدون وابستگی به سرورهای انحصاری.
                  </p>
                </div>

                <div className="p-4 bg-slate-950 rounded-lg border border-slate-800 space-y-2">
                  <div className="text-indigo-400 font-bold text-sm">عدم استفاده تجاری از داده‌ها</div>
                  <p className="text-xs text-slate-400">
                    تضمین عدم فروش اطلاعات و عدم آموزش مدل‌های هوش مصنوعی عمومی بر روی دفاتر مالی و اسناد حساس شرکت‌ها.
                  </p>
                </div>

                <div className="p-4 bg-slate-950 rounded-lg border border-slate-800 space-y-2">
                  <div className="text-violet-400 font-bold text-sm">آماده برای گیت‌هاب (Open Source)</div>
                  <p className="text-xs text-slate-400">
                    ایجاد تمام مستندات استاندارد بین‌المللی شامل README، مجوز Apache 2.0، راهنمای CONTRIBUTING، و پایپ‌لاین CI.
                  </p>
                </div>
              </div>

              <div className="p-4 bg-slate-950 rounded-lg border border-slate-800/80 space-y-2">
                <div className="text-xs font-bold text-slate-200">دستورات راه‌اندازی سریع در ترمینال:</div>
                <div className="font-mono text-xs text-indigo-300 text-left" dir="ltr">
                  git clone https://github.com/itispourya/neonotion.git<br/>
                  cd neonotion<br/>
                  npm install<br/>
                  npm run dev
                </div>
              </div>
            </div>
          </div>
        )}
      </main>

      {/* Quiet Footer */}
      <footer className="border-t border-slate-800/80 bg-slate-950 py-6 mt-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-500">
          <div className="flex items-center gap-2">
            <span>© 2024–2026 NeoNotion.net</span>
            <span>·</span>
            <span>Pourya Omidian</span>
            <span>·</span>
            <span>All rights reserved</span>
          </div>

          <div className="flex items-center gap-6">
            <a href="https://neonotion.net" target="_blank" rel="noopener noreferrer" className="hover:text-slate-300 transition-colors">
              NeoNotion.net
            </a>
            <a href="mailto:itispourya@gmail.com" className="hover:text-slate-300 transition-colors">
              itispourya@gmail.com
            </a>
            <button onClick={() => setActiveTab('docs')} className="hover:text-slate-300 transition-colors cursor-pointer">
              Open Source (Apache 2.0)
            </button>
          </div>
        </div>
      </footer>
    </div>
  );
}
