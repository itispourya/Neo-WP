/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 * 
 * NeoNotion WordPress Plugins Terminal & CLI Distribution Console
 * Developer & Lead Architect: Pourya Omidian (https://neonotion.net)
 */

import React, { useState, useMemo } from 'react';
import {
  Download,
  Terminal,
  Code2,
  Copy,
  Check,
  ExternalLink,
  ShieldCheck,
  FolderTree,
  FileCode,
  FileText,
  Search,
  ArrowDownToLine,
  Layers,
  Sparkles,
  Server,
  Zap,
  Info,
  CheckCircle2,
  HelpCircle,
  Play,
  RotateCcw
} from 'lucide-react';
import { PLUGINS_DATA, PluginInfo, PluginFile } from './data/pluginsData';

const BASE_URL = 'https://ais-dev-5odeo7vp2v2h3exesgu2nz-84526810969.europe-west2.run.app';

export default function App() {
  const [activeTab, setActiveTab] = useState<'downloads' | 'online-install' | 'wp-tutorial' | 'inspector' | 'matrix'>('downloads');
  const [selectedPluginId, setSelectedPluginId] = useState<string>('neomarket');
  const [selectedFilePath, setSelectedFilePath] = useState<string>('NeoMarket/neomarket.php');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [copiedId, setCopiedId] = useState<string | null>(null);

  // Online Install Generator State
  const [selectedForOnlineInstall, setSelectedForOnlineInstall] = useState<Record<string, boolean>>({
    neomarket: true,
    neoloadland: true,
    neobooking: true,
    neoformdata: true,
    neolock: true
  });
  const [autoActivate, setAutoActivate] = useState<boolean>(true);
  const [installMethod, setInstallMethod] = useState<'wp-cli' | 'curl' | 'wget'>('wp-cli');

  // Simulated Terminal State
  const [simulating, setSimulating] = useState<boolean>(false);
  const [terminalLogs, setTerminalLogs] = useState<string[]>([]);

  // Currently active plugin
  const currentPlugin = useMemo(() => {
    return PLUGINS_DATA.find(p => p.id === selectedPluginId) || PLUGINS_DATA[0];
  }, [selectedPluginId]);

  // Set default file when plugin changes
  const handleSelectPlugin = (pluginId: string) => {
    setSelectedPluginId(pluginId);
    const p = PLUGINS_DATA.find(item => item.id === pluginId);
    if (p && p.files.length > 0) {
      setSelectedFilePath(p.files[0].path);
    }
  };

  // Currently selected file
  const currentFile = useMemo(() => {
    return currentPlugin.files.find(f => f.path === selectedFilePath) || currentPlugin.files[0];
  }, [currentPlugin, selectedFilePath]);

  // Filtered files inside active plugin
  const filteredFiles = useMemo(() => {
    if (!searchQuery.trim()) return currentPlugin.files;
    const q = searchQuery.toLowerCase();
    return currentPlugin.files.filter(f =>
      f.name.toLowerCase().includes(q) ||
      f.path.toLowerCase().includes(q) ||
      f.description.toLowerCase().includes(q)
    );
  }, [currentPlugin, searchQuery]);

  // All 50 files across all plugins
  const allFilesAcrossPlugins = useMemo(() => {
    const list: { plugin: PluginInfo; file: PluginFile }[] = [];
    PLUGINS_DATA.forEach(plugin => {
      plugin.files.forEach(file => {
        list.push({ plugin, file });
      });
    });
    if (!searchQuery.trim()) return list;
    const q = searchQuery.toLowerCase();
    return list.filter(item =>
      item.file.name.toLowerCase().includes(q) ||
      item.file.path.toLowerCase().includes(q) ||
      item.file.description.toLowerCase().includes(q) ||
      item.plugin.name.toLowerCase().includes(q)
    );
  }, [searchQuery]);

  const copyToClipboard = (text: string, id: string) => {
    navigator.clipboard.writeText(text);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleDownloadSingleFile = (file: PluginFile) => {
    const blob = new Blob([file.content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = file.name;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  // Generated Online Install Command
  const generatedCommand = useMemo(() => {
    const activePlugins = Object.entries(selectedForOnlineInstall)
      .filter(([_, isSelected]) => isSelected)
      .map(([id]) => id);

    if (activePlugins.length === 0) {
      return '# Select at least one plugin from the list below';
    }

    if (installMethod === 'curl') {
      return `curl -sSL ${BASE_URL}/install.sh | bash`;
    }

    if (installMethod === 'wget') {
      return `wget -qO- ${BASE_URL}/install.sh | bash`;
    }

    // Default: WP-CLI
    const urls = activePlugins.map(id => `${BASE_URL}/downloads/${id}.zip`).join(' ');
    const activateFlag = autoActivate ? ' --activate' : '';
    return `wp plugin install ${urls}${activateFlag}`;
  }, [selectedForOnlineInstall, autoActivate, installMethod]);

  const runSimulation = () => {
    setSimulating(true);
    setTerminalLogs([]);
    const logs = [
      `neonotion-cli v2.4.0 (x86_64-linux)`,
      `Connecting to repository remote: ${BASE_URL}...`,
      `[HTTP 200] Connection established. Verified TLS 1.3 session.`,
      `Initializing installation sequence for selected WordPress plugins...`,
      `[1/5] Fetching package: neomarket.zip (73.6 KB)... [OK]`,
      `      Extracting to wp-content/plugins/NeoMarket/ ... [100%]`,
      `[2/5] Fetching package: neoloadland.zip (8.2 KB)... [OK]`,
      `      Extracting to wp-content/plugins/NeoLoadLand/ ... [100%]`,
      `[3/5] Fetching package: neobooking.zip (11.6 KB)... [OK]`,
      `      Extracting to wp-content/plugins/NeoBooking/ ... [100%]`,
      `[4/5] Fetching package: neoformdata.zip (9.6 KB)... [OK]`,
      `      Extracting to wp-content/plugins/NeoFormData/ ... [100%]`,
      `[5/5] Fetching package: neolock.zip (16.3 KB)... [OK]`,
      `      Extracting to wp-content/plugins/NeoLock/ ... [100%]`,
      `Running database migrations: neomarket_db_version -> 2.0.0...`,
      `Created custom tables: wp_neomarket_orders, wp_neobooking_entries, wp_neoformdata_forms.`,
      `Registering unified root menu: neonotion_register_root_menu()... [SUCCESS]`,
      `Activating plugins: neomarket, neoloadland, neobooking, neoformdata, neolock...`,
      `Success: 5 of 5 plugins installed and activated successfully! (0 errors, 0 warnings)`,
      `Status: 100% Free & Open Source | Ready in WordPress Admin Dashboard!`
    ];

    logs.forEach((log, index) => {
      setTimeout(() => {
        setTerminalLogs(prev => [...prev, log]);
        if (index === logs.length - 1) {
          setSimulating(false);
        }
      }, (index + 1) * 140);
    });
  };

  return (
    <div className="min-h-screen bg-[#0d1117] text-[#c9d1d9] font-mono flex flex-col selection:bg-[#238636] selection:text-white">
      {/* GitHub / Terminal Top Window Bar */}
      <div className="border-b border-[#30363d] bg-[#161b22] px-4 py-2.5 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <div className="flex items-center gap-1.5">
            <span className="w-3 h-3 rounded-full bg-[#f85149] inline-block"></span>
            <span className="w-3 h-3 rounded-full bg-[#d29922] inline-block"></span>
            <span className="w-3 h-3 rounded-full bg-[#3fb950] inline-block"></span>
          </div>
          <div className="text-xs text-[#8b949e] flex items-center gap-2">
            <Terminal className="w-3.5 h-3.5 text-[#58a6ff]" />
            <span className="text-[#e6edf3] font-semibold">neonotion-wp-console</span>
            <span>—</span>
            <span>bash — 80x24</span>
          </div>
        </div>

        <div className="flex items-center gap-3 text-xs">
          <span className="text-[#3fb950] bg-[#238636]/20 border border-[#238636]/40 px-2 py-0.5 rounded text-[11px] font-semibold">
            ● 100% FREE & OPEN SOURCE
          </span>
          <a
            href="https://neonotion.net"
            target="_blank"
            rel="noopener noreferrer"
            className="text-[#8b949e] hover:text-[#58a6ff] transition-colors flex items-center gap-1"
          >
            <span>neonotion.net</span>
            <ExternalLink className="w-3 h-3" />
          </a>
        </div>
      </div>

      {/* Main Terminal Shell Container */}
      <div className="max-w-7xl w-full mx-auto p-4 sm:p-6 lg:p-8 flex-1 flex flex-col space-y-6">
        {/* Terminal Header & Free Announcement Banner */}
        <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 shadow-2xl relative overflow-hidden">
          <div className="text-xs text-[#8b949e] mb-2 flex items-center gap-2">
            <span className="text-[#3fb950] font-bold">root@neonotion-server:~$</span>
            <span>cat /etc/motd</span>
          </div>

          <pre className="text-[#58a6ff] text-xs sm:text-sm leading-tight font-mono select-all overflow-x-auto pb-2">
{` _   _             _   _       _   _             
| \\ | | ___  ___  | \\ | | ___ | |_(_) ___  _ __  
|  \\| |/ _ \\/ _ \\ |  \\| |/ _ \\| __| |/ _ \\| '_ \\ 
| |\\  |  __/ (_) || |\\  | (_) | |_| | (_) | | | |
|_| \\_|\\___|\\___/ |_| \\_|\\___/ \\__|_|\\___/|_| |_|`}
          </pre>

          <div className="mt-3 pt-3 border-t border-[#30363d] flex flex-wrap items-center justify-between gap-4 text-xs">
            <div>
              <div className="text-[#e6edf3] font-semibold text-sm">
                Official WordPress Plugin Suite — All 5 Plugins Are 100% Free
              </div>
              <div className="text-[#8b949e] text-xs mt-0.5">
                Architect & Developer: <span className="text-[#f0883e] font-semibold">Pourya Omidian</span> | Commercial & Personal Use: <span className="text-[#3fb950]">UNRESTRICTED (GPL-2.0-or-later)</span>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <a
                href="/downloads/neonotion-plugins-bundle.zip"
                download="neonotion-plugins-bundle.zip"
                className="px-3.5 py-1.5 text-xs font-semibold text-white bg-[#238636] hover:bg-[#2ea043] rounded border border-[#3fb950]/40 transition-colors flex items-center gap-2 shadow-sm"
              >
                <ArrowDownToLine className="w-4 h-4" />
                <span>DOWNLOAD ALL 5 PLUGINS (1-CLICK BUNDLE)</span>
              </a>
            </div>
          </div>
        </div>

        {/* CMD Terminal Navigation Tabs */}
        <div className="flex flex-wrap items-center gap-2 border-b border-[#30363d] pb-2 text-xs">
          <button
            onClick={() => setActiveTab('downloads')}
            className={`px-3 py-1.5 rounded font-mono transition-colors cursor-pointer flex items-center gap-2 ${
              activeTab === 'downloads'
                ? 'bg-[#21262d] text-[#58a6ff] border border-[#30363d]'
                : 'text-[#8b949e] hover:text-[#c9d1d9] hover:bg-[#161b22]'
            }`}
          >
            <span>[1]</span>
            <Download className="w-3.5 h-3.5" />
            <span>QUICK DOWNLOADS</span>
          </button>

          <button
            onClick={() => setActiveTab('online-install')}
            className={`px-3 py-1.5 rounded font-mono transition-colors cursor-pointer flex items-center gap-2 ${
              activeTab === 'online-install'
                ? 'bg-[#21262d] text-[#3fb950] border border-[#30363d]'
                : 'text-[#8b949e] hover:text-[#c9d1d9] hover:bg-[#161b22]'
            }`}
          >
            <span>[2]</span>
            <Terminal className="w-3.5 h-3.5" />
            <span>ONLINE DIRECT INSTALL</span>
            <span className="text-[10px] bg-[#238636]/30 text-[#3fb950] px-1 rounded font-bold">ZERO-DOWNLOAD</span>
          </button>

          <button
            onClick={() => setActiveTab('wp-tutorial')}
            className={`px-3 py-1.5 rounded font-mono transition-colors cursor-pointer flex items-center gap-2 ${
              activeTab === 'wp-tutorial'
                ? 'bg-[#21262d] text-[#d29922] border border-[#30363d]'
                : 'text-[#8b949e] hover:text-[#c9d1d9] hover:bg-[#161b22]'
            }`}
          >
            <span>[3]</span>
            <HelpCircle className="w-3.5 h-3.5" />
            <span>WORDPRESS INSTALL TUTORIAL</span>
          </button>

          <button
            onClick={() => setActiveTab('inspector')}
            className={`px-3 py-1.5 rounded font-mono transition-colors cursor-pointer flex items-center gap-2 ${
              activeTab === 'inspector'
                ? 'bg-[#21262d] text-[#a371f7] border border-[#30363d]'
                : 'text-[#8b949e] hover:text-[#c9d1d9] hover:bg-[#161b22]'
            }`}
          >
            <span>[4]</span>
            <Code2 className="w-3.5 h-3.5" />
            <span>FILE INSPECTOR & CODE</span>
          </button>

          <button
            onClick={() => setActiveTab('matrix')}
            className={`px-3 py-1.5 rounded font-mono transition-colors cursor-pointer flex items-center gap-2 ${
              activeTab === 'matrix'
                ? 'bg-[#21262d] text-[#f0883e] border border-[#30363d]'
                : 'text-[#8b949e] hover:text-[#c9d1d9] hover:bg-[#161b22]'
            }`}
          >
            <span>[5]</span>
            <FolderTree className="w-3.5 h-3.5" />
            <span>50-FILE MATRIX</span>
          </button>
        </div>

        {/* TAB 1: QUICK DOWNLOADS (SIMPLIFIED & ACCESSIBLE) */}
        {activeTab === 'downloads' && (
          <div className="space-y-6">
            {/* Master Bundle Hero Download Box */}
            <div className="border-2 border-[#238636] bg-[#0d1117] rounded-lg p-6 relative overflow-hidden shadow-lg shadow-[#238636]/10">
              <div className="flex flex-wrap items-center justify-between gap-6">
                <div className="space-y-2 max-w-2xl">
                  <div className="flex items-center gap-2">
                    <span className="text-[#3fb950] font-bold text-xs uppercase tracking-wider bg-[#238636]/20 px-2 py-0.5 rounded border border-[#238636]/50">
                      ★ All-In-One Unified Package
                    </span>
                    <span className="text-xs text-[#8b949e]">neonotion-plugins-bundle.zip (120 KB)</span>
                  </div>
                  <h2 className="text-xl sm:text-2xl font-bold text-[#e6edf3] font-mono">
                    Download All 5 Plugins in One ZIP Archive
                  </h2>
                  <p className="text-xs sm:text-sm text-[#8b949e] leading-relaxed">
                    Includes <span className="text-[#e6edf3]">NeoMarket v2.0</span>, <span className="text-[#e6edf3]">NeoLoadLand v7.0</span>, <span className="text-[#e6edf3]">NeoBooking v9.0</span>, <span className="text-[#e6edf3]">NeoFormData v8.0</span>, and <span className="text-[#e6edf3]">NeoLock v6.0</span>. Completely free, no activation keys or subscriptions required.
                  </p>
                </div>

                <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                  <a
                    href="/downloads/neonotion-plugins-bundle.zip"
                    download="neonotion-plugins-bundle.zip"
                    className="px-6 py-3 text-sm font-bold text-white bg-[#238636] hover:bg-[#2ea043] rounded-md transition-all shadow-lg flex items-center justify-center gap-2 border border-[#3fb950]"
                  >
                    <ArrowDownToLine className="w-5 h-5" />
                    <span>DOWNLOAD 5-IN-1 ZIP (120 KB)</span>
                  </a>
                </div>
              </div>
            </div>

            {/* Individual Plugin Cards Grid */}
            <div className="space-y-3">
              <div className="text-xs text-[#8b949e] flex items-center justify-between">
                <span>OR DOWNLOAD INDIVIDUAL PLUGIN PACKAGES:</span>
                <span className="text-[#3fb950]">100% Free · Click any plugin to download .zip</span>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                {PLUGINS_DATA.map((plugin) => (
                  <div
                    key={plugin.id}
                    className="border border-[#30363d] bg-[#161b22] rounded-lg p-5 flex flex-col justify-between hover:border-[#58a6ff] transition-all space-y-4"
                  >
                    <div>
                      <div className="flex items-center justify-between gap-2 mb-2">
                        <span className="text-base font-bold text-[#e6edf3]">{plugin.name}</span>
                        <span className="text-xs font-mono text-[#58a6ff] bg-[#388bfd]/10 border border-[#388bfd]/30 px-2 py-0.5 rounded">
                          v{plugin.version}
                        </span>
                      </div>
                      <div className="text-xs text-[#f0883e] mb-2 font-semibold">
                        {plugin.category}
                      </div>
                      <p className="text-xs text-[#8b949e] line-clamp-3 leading-relaxed">
                        {plugin.description}
                      </p>
                    </div>

                    <div className="pt-3 border-t border-[#30363d] space-y-2">
                      <div className="flex items-center justify-between text-xs text-[#8b949e]">
                        <span>Files: {plugin.totalFiles}</span>
                        <span className="text-[#3fb950] font-semibold">FREE</span>
                      </div>

                      <div className="flex items-center gap-2">
                        <a
                          href={plugin.zipFile}
                          download={`${plugin.id}.zip`}
                          className="flex-1 py-2 text-xs font-semibold text-white bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] hover:border-[#58a6ff] rounded transition-all flex items-center justify-center gap-1.5"
                        >
                          <Download className="w-3.5 h-3.5 text-[#58a6ff]" />
                          <span>Download {plugin.name}.zip</span>
                        </a>

                        <button
                          onClick={() => {
                            setSelectedPluginId(plugin.id);
                            setActiveTab('inspector');
                          }}
                          className="px-3 py-2 text-xs text-[#8b949e] hover:text-[#c9d1d9] bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] rounded transition-colors"
                          title="Inspect files"
                        >
                          <Code2 className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* TAB 2: ONLINE DIRECT INSTALL (ZERO DOWNLOADS REQUIRED) */}
        {activeTab === 'online-install' && (
          <div className="space-y-6">
            <div className="border border-[#30363d] bg-[#161b22] rounded-lg p-5 space-y-4">
              <div className="flex flex-wrap items-center justify-between gap-4">
                <div>
                  <div className="flex items-center gap-2 text-xs text-[#3fb950] font-bold uppercase">
                    <Terminal className="w-4 h-4" />
                    <span>Direct Online Remote Installation (No Download to PC Required)</span>
                  </div>
                  <h2 className="text-lg font-bold text-[#e6edf3] mt-1">
                    Execute directly on your WordPress Server Terminal or SSH Console
                  </h2>
                  <p className="text-xs text-[#8b949e] mt-0.5">
                    Install and activate plugins directly in your WordPress site without downloading or uploading zip files manually.
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <button
                    onClick={() => setInstallMethod('wp-cli')}
                    className={`px-3 py-1 text-xs rounded border transition-colors cursor-pointer ${
                      installMethod === 'wp-cli'
                        ? 'bg-[#238636] text-white border-[#3fb950]'
                        : 'bg-[#21262d] text-[#8b949e] border-[#30363d] hover:text-white'
                    }`}
                  >
                    WP-CLI Command
                  </button>
                  <button
                    onClick={() => setInstallMethod('curl')}
                    className={`px-3 py-1 text-xs rounded border transition-colors cursor-pointer ${
                      installMethod === 'curl'
                        ? 'bg-[#238636] text-white border-[#3fb950]'
                        : 'bg-[#21262d] text-[#8b949e] border-[#30363d] hover:text-white'
                    }`}
                  >
                    cURL Remote Script
                  </button>
                  <button
                    onClick={() => setInstallMethod('wget')}
                    className={`px-3 py-1 text-xs rounded border transition-colors cursor-pointer ${
                      installMethod === 'wget'
                        ? 'bg-[#238636] text-white border-[#3fb950]'
                        : 'bg-[#21262d] text-[#8b949e] border-[#30363d] hover:text-white'
                    }`}
                  >
                    Wget Remote Script
                  </button>
                </div>
              </div>

              {/* Online Command Display Box */}
              <div className="bg-[#0d1117] border border-[#30363d] rounded-md p-4 relative group">
                <div className="text-[11px] text-[#8b949e] mb-2 flex items-center justify-between">
                  <span>RUN THIS ONE-LINER IN YOUR WORDPRESS SERVER TERMINAL:</span>
                  <span className="text-[#3fb950] font-mono text-[10px]">VERIFIED REMOTE ENDPOINT</span>
                </div>
                <div className="font-mono text-xs sm:text-sm text-[#3fb950] overflow-x-auto whitespace-pre-wrap break-all pr-14 select-all">
                  {generatedCommand}
                </div>

                <button
                  onClick={() => copyToClipboard(generatedCommand, 'cmd')}
                  className="absolute right-3 top-3 px-3 py-1.5 bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] hover:border-[#58a6ff] rounded text-xs text-[#c9d1d9] flex items-center gap-1.5 transition-colors cursor-pointer"
                  title="Copy command to clipboard"
                >
                  {copiedId === 'cmd' ? (
                    <Check className="w-3.5 h-3.5 text-[#3fb950]" />
                  ) : (
                    <Copy className="w-3.5 h-3.5 text-[#8b949e]" />
                  )}
                  <span>{copiedId === 'cmd' ? 'COPIED' : 'COPY'}</span>
                </button>
              </div>

              {/* Plugin Selectors for Command Builder */}
              {installMethod === 'wp-cli' && (
                <div className="pt-2 flex flex-wrap items-center justify-between gap-4 text-xs border-t border-[#30363d]">
                  <div className="flex flex-wrap items-center gap-4">
                    <span className="text-[#8b949e]">Select plugins to include:</span>
                    {PLUGINS_DATA.map((p) => (
                      <label key={p.id} className="flex items-center gap-1.5 cursor-pointer text-[#c9d1d9]">
                        <input
                          type="checkbox"
                          checked={selectedForOnlineInstall[p.id]}
                          onChange={(e) =>
                            setSelectedForOnlineInstall(prev => ({
                              ...prev,
                              [p.id]: e.target.checked
                            }))
                          }
                          className="rounded bg-[#0d1117] border-[#30363d] text-[#238636] focus:ring-0"
                        />
                        <span>{p.name}</span>
                      </label>
                    ))}
                  </div>

                  <label className="flex items-center gap-1.5 cursor-pointer text-[#58a6ff]">
                    <input
                      type="checkbox"
                      checked={autoActivate}
                      onChange={(e) => setAutoActivate(e.target.checked)}
                      className="rounded bg-[#0d1117] border-[#30363d] text-[#238636] focus:ring-0"
                    />
                    <span>Auto-activate (--activate)</span>
                  </label>
                </div>
              )}
            </div>

            {/* Interactive Terminal Simulator */}
            <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-4">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2 text-xs font-semibold text-[#e6edf3]">
                  <Terminal className="w-4 h-4 text-[#58a6ff]" />
                  <span>Interactive Terminal Online Install Simulator</span>
                </div>

                <div className="flex items-center gap-2">
                  <button
                    onClick={runSimulation}
                    disabled={simulating}
                    className="px-3 py-1.5 bg-[#238636] hover:bg-[#2ea043] disabled:opacity-50 text-white text-xs rounded transition-all flex items-center gap-1.5 cursor-pointer"
                  >
                    <Play className="w-3.5 h-3.5" />
                    <span>{simulating ? 'INSTALLING...' : 'TEST RUN INSTALLATION'}</span>
                  </button>
                  {terminalLogs.length > 0 && (
                    <button
                      onClick={() => setTerminalLogs([])}
                      className="p-1.5 text-[#8b949e] hover:text-[#c9d1d9] bg-[#21262d] rounded transition-colors"
                      title="Clear terminal"
                    >
                      <RotateCcw className="w-3.5 h-3.5" />
                    </button>
                  )}
                </div>
              </div>

              {/* Terminal Screen */}
              <div className="bg-[#040d1a] border border-[#30363d] rounded p-4 h-64 overflow-y-auto font-mono text-xs text-[#7ee787] space-y-1">
                <div className="text-[#8b949e]">
                  user@wp-production:~$ {generatedCommand}
                </div>
                {terminalLogs.map((line, idx) => (
                  <div key={idx} className={line.includes('Success') || line.includes('FREE') ? 'text-[#3fb950] font-bold' : ''}>
                    {line}
                  </div>
                ))}
                {terminalLogs.length === 0 && !simulating && (
                  <div className="text-[#8b949e] italic py-8 text-center">
                    Click "TEST RUN INSTALLATION" above to simulate real-time remote download, extraction, and activation.
                  </div>
                )}
              </div>
            </div>
          </div>
        )}

        {/* TAB 3: STEP-BY-STEP WORDPRESS ZIP TUTORIAL */}
        {activeTab === 'wp-tutorial' && (
          <div className="space-y-6">
            <div className="border border-[#30363d] bg-[#161b22] rounded-lg p-5 space-y-2">
              <div className="text-xs text-[#3fb950] font-semibold">TUTORIAL / GUIDE</div>
              <h2 className="text-xl font-bold text-[#e6edf3]">
                How to Install NeoNotion Plugins via WordPress Admin (.ZIP Upload)
              </h2>
              <p className="text-xs text-[#8b949e]">
                Follow this simple 5-step visual guide to install any of the NeoNotion plugin ZIP files into your WordPress website.
              </p>
            </div>

            {/* Visual Steps Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-3">
                <div className="flex items-center justify-between text-xs font-mono">
                  <span className="text-[#58a6ff] font-bold">STEP 01</span>
                  <span className="text-[#8b949e]">Downloads Tab</span>
                </div>
                <h3 className="text-sm font-bold text-[#e6edf3]">Download Plugin ZIP</h3>
                <p className="text-xs text-[#8b949e] leading-relaxed">
                  Click the <strong>Download</strong> button for your target plugin (e.g. <code className="text-[#3fb950]">neomarket.zip</code>) or grab the 5-in-1 bundle.
                </p>
                <div className="p-2.5 bg-[#161b22] rounded border border-[#30363d] text-[11px] text-[#3fb950] font-mono">
                  File saved: ~/Downloads/neomarket.zip
                </div>
              </div>

              <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-3">
                <div className="flex items-center justify-between text-xs font-mono">
                  <span className="text-[#58a6ff] font-bold">STEP 02</span>
                  <span className="text-[#8b949e]">WordPress Dashboard</span>
                </div>
                <h3 className="text-sm font-bold text-[#e6edf3]">Go to Plugins → Add New</h3>
                <p className="text-xs text-[#8b949e] leading-relaxed">
                  Open your WordPress administration panel (<code className="text-[#58a6ff]">yourdomain.com/wp-admin</code>) and navigate to <strong>Plugins → Add New Plugin</strong>.
                </p>
                <div className="p-2.5 bg-[#161b22] rounded border border-[#30363d] text-[11px] text-[#8b949e] font-mono">
                  URL: /wp-admin/plugin-install.php
                </div>
              </div>

              <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-3">
                <div className="flex items-center justify-between text-xs font-mono">
                  <span className="text-[#58a6ff] font-bold">STEP 03</span>
                  <span className="text-[#8b949e]">Top Header Button</span>
                </div>
                <h3 className="text-sm font-bold text-[#e6edf3]">Click "Upload Plugin"</h3>
                <p className="text-xs text-[#8b949e] leading-relaxed">
                  At the top of the Add Plugins screen, click the <strong>Upload Plugin</strong> button next to the page heading.
                </p>
                <div className="p-2.5 bg-[#161b22] rounded border border-[#30363d] text-[11px] text-[#e6edf3] font-mono">
                  [ Choose File ] [ Install Now ]
                </div>
              </div>

              <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-3">
                <div className="flex items-center justify-between text-xs font-mono">
                  <span className="text-[#58a6ff] font-bold">STEP 04</span>
                  <span className="text-[#8b949e]">File Upload</span>
                </div>
                <h3 className="text-sm font-bold text-[#e6edf3]">Select ZIP & Click "Install Now"</h3>
                <p className="text-xs text-[#8b949e] leading-relaxed">
                  Choose the downloaded ZIP archive from your computer and click the <strong>Install Now</strong> button. WordPress will unpack the files automatically.
                </p>
                <div className="p-2.5 bg-[#161b22] rounded border border-[#30363d] text-[11px] text-[#3fb950] font-mono">
                  Unpacking the package... Plugin installed successfully.
                </div>
              </div>

              <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-3">
                <div className="flex items-center justify-between text-xs font-mono">
                  <span className="text-[#58a6ff] font-bold">STEP 05</span>
                  <span className="text-[#8b949e]">Final Step</span>
                </div>
                <h3 className="text-sm font-bold text-[#e6edf3]">Click "Activate Plugin"</h3>
                <p className="text-xs text-[#8b949e] leading-relaxed">
                  Click the blue <strong>Activate Plugin</strong> button. The unified <strong>NeoNotion</strong> menu icon (<code className="text-[#f0883e]">dashicons-superhero-alt</code>) will immediately appear in your sidebar!
                </p>
                <div className="p-2.5 bg-[#161b22] rounded border border-[#30363d] text-[11px] text-[#3fb950] font-mono">
                  Plugin activated. Enjoy 100% free functionality!
                </div>
              </div>

              <div className="border border-[#30363d] bg-[#0d1117] rounded-lg p-5 space-y-3">
                <div className="flex items-center justify-between text-xs font-mono">
                  <span className="text-[#3fb950] font-bold">ALTERNATIVE</span>
                  <span className="text-[#8b949e]">Terminal / SSH</span>
                </div>
                <h3 className="text-sm font-bold text-[#e6edf3]">Zero-Download Remote Option</h3>
                <p className="text-xs text-[#8b949e] leading-relaxed">
                  Don't want to download anything to your computer? Use the <strong>Online Direct Install</strong> tab to run the one-liner directly on your server!
                </p>
                <div className="p-2.5 bg-[#161b22] rounded border border-[#30363d] text-[11px] text-[#58a6ff] font-mono">
                  wp plugin install &lt;url&gt; --activate
                </div>
              </div>
            </div>
          </div>
        )}

        {/* TAB 4: FILE INSPECTOR & CODE (FULL 50-FILE CODEBASE) */}
        {activeTab === 'inspector' && (
          <div className="space-y-6">
            {/* Active Plugin Header Bar */}
            <div className="border border-[#30363d] bg-[#161b22] rounded-lg p-4 flex flex-wrap items-center justify-between gap-4">
              <div className="flex items-center gap-3">
                <span className="text-sm font-bold text-[#e6edf3]">
                  {currentPlugin.name} Source Code Inspector
                </span>
                <span className="text-xs font-mono text-[#58a6ff] bg-[#388bfd]/10 border border-[#388bfd]/30 px-2 py-0.5 rounded">
                  v{currentPlugin.version}
                </span>
                <span className="text-xs text-[#3fb950] bg-[#238636]/20 border border-[#238636]/40 px-2 py-0.5 rounded">
                  {currentPlugin.totalFiles} files
                </span>
              </div>

              <div className="flex items-center gap-2">
                <a
                  href={currentPlugin.zipFile}
                  download={`${currentPlugin.id}.zip`}
                  className="px-3 py-1.5 text-xs font-semibold text-white bg-[#238636] hover:bg-[#2ea043] rounded flex items-center gap-1.5 transition-colors"
                >
                  <Download className="w-3.5 h-3.5" />
                  <span>Download {currentPlugin.name}.zip</span>
                </a>
              </div>
            </div>

            {/* Split Screen Explorer: File List (Left) + Code Viewer (Right) */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
              {/* File List Panel (4 cols) */}
              <div className="lg:col-span-4 border border-[#30363d] bg-[#161b22] rounded-lg overflow-hidden flex flex-col h-[650px]">
                {/* Search */}
                <div className="p-3 border-b border-[#30363d] bg-[#0d1117] flex items-center gap-2">
                  <Search className="w-3.5 h-3.5 text-[#8b949e]" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder={`Filter ${currentPlugin.name} files...`}
                    className="w-full bg-transparent text-xs text-[#c9d1d9] placeholder-[#8b949e] focus:outline-none"
                  />
                  {searchQuery && (
                    <button
                      onClick={() => setSearchQuery('')}
                      className="text-xs text-[#8b949e] hover:text-[#c9d1d9]"
                    >
                      ×
                    </button>
                  )}
                </div>

                <div className="px-3 py-2 bg-[#0d1117]/60 border-b border-[#30363d] text-[11px] text-[#8b949e] flex items-center justify-between">
                  <span>{filteredFiles.length} files matched</span>
                  <span>PHP / JS / CSS / MD</span>
                </div>

                {/* File Scroll */}
                <div className="flex-1 overflow-y-auto p-2 space-y-1">
                  {filteredFiles.map((file) => {
                    const isSelected = file.path === currentFile.path;
                    return (
                      <button
                        key={file.path}
                        onClick={() => setSelectedFilePath(file.path)}
                        className={`w-full text-left p-2 rounded transition-colors text-xs flex items-start gap-2 cursor-pointer ${
                          isSelected
                            ? 'bg-[#1f6feb]/20 border border-[#388bfd] text-[#e6edf3]'
                            : 'hover:bg-[#21262d] text-[#8b949e] border border-transparent'
                        }`}
                      >
                        <FileCode className="w-3.5 h-3.5 text-[#58a6ff] shrink-0 mt-0.5" />
                        <div className="min-w-0 flex-1">
                          <div className="flex items-center justify-between gap-1">
                            <span className="font-bold text-[#e6edf3] truncate">{file.name}</span>
                            <span className="text-[10px] text-[#8b949e] shrink-0">{file.sizeFormatted}</span>
                          </div>
                          <div className="text-[10px] text-[#8b949e] truncate">{file.path}</div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>

              {/* Code Viewer Panel (8 cols) */}
              <div className="lg:col-span-8 border border-[#30363d] bg-[#161b22] rounded-lg overflow-hidden flex flex-col h-[650px]">
                {/* Header with actions */}
                <div className="p-3 border-b border-[#30363d] bg-[#0d1117] flex flex-wrap items-center justify-between gap-3">
                  <div className="flex items-center gap-2 min-w-0">
                    <FileCode className="w-4 h-4 text-[#58a6ff]" />
                    <span className="text-xs font-bold text-[#e6edf3] font-mono truncate">
                      {currentFile.path}
                    </span>
                    <span className="text-[10px] bg-[#21262d] text-[#8b949e] px-1.5 py-0.5 rounded font-mono">
                      {currentFile.sizeFormatted}
                    </span>
                  </div>

                  <div className="flex items-center gap-2">
                    <button
                      onClick={() => copyToClipboard(currentFile.content, 'source')}
                      className="px-2.5 py-1 text-xs text-[#c9d1d9] bg-[#21262d] hover:bg-[#30363d] border border-[#30363d] rounded flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      {copiedId === 'source' ? <Check className="w-3 h-3 text-[#3fb950]" /> : <Copy className="w-3 h-3 text-[#8b949e]" />}
                      <span>{copiedId === 'source' ? 'COPIED' : 'COPY CODE'}</span>
                    </button>

                    <button
                      onClick={() => handleDownloadSingleFile(currentFile)}
                      className="px-2.5 py-1 text-xs text-white bg-[#238636] hover:bg-[#2ea043] rounded flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      <Download className="w-3 h-3" />
                      <span>DOWNLOAD FILE</span>
                    </button>
                  </div>
                </div>

                {/* Explanation Box */}
                <div className="p-3 bg-[#1f6feb]/10 border-b border-[#30363d] text-xs text-[#c9d1d9] space-y-1">
                  <div className="flex items-center gap-1.5 text-[#58a6ff] font-semibold text-[11px]">
                    <Info className="w-3.5 h-3.5" />
                    <span>Role & Function in Plugin Architecture:</span>
                  </div>
                  <p className="text-xs text-[#8b949e] leading-relaxed">
                    {currentFile.description}
                  </p>
                </div>

                {/* Code Window */}
                <div className="flex-1 overflow-auto bg-[#0d1117] p-4 text-xs font-mono text-[#c9d1d9] leading-relaxed">
                  <pre className="overflow-x-auto whitespace-pre">
                    <code>{currentFile.content}</code>
                  </pre>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* TAB 5: 50-FILE MATRIX */}
        {activeTab === 'matrix' && (
          <div className="space-y-4">
            <div className="border border-[#30363d] bg-[#161b22] rounded-lg p-4 flex flex-wrap items-center justify-between gap-4">
              <div>
                <h3 className="text-base font-bold text-[#e6edf3]">
                  All 50 Files Master Table
                </h3>
                <p className="text-xs text-[#8b949e]">
                  Index of every PHP, JS, CSS, and Markdown file across all 5 plugins.
                </p>
              </div>

              <div className="w-full sm:w-72 relative">
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Filter 50 files..."
                  className="w-full px-3 py-1.5 bg-[#0d1117] rounded border border-[#30363d] text-xs text-[#c9d1d9] placeholder-[#8b949e] focus:outline-none focus:border-[#58a6ff]"
                />
              </div>
            </div>

            <div className="border border-[#30363d] bg-[#161b22] rounded-lg overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs divide-y divide-[#30363d]">
                  <thead className="bg-[#0d1117] text-[#8b949e] uppercase font-mono text-[10px]">
                    <tr>
                      <th className="px-4 py-2.5">Plugin</th>
                      <th className="px-4 py-2.5">File Path</th>
                      <th className="px-4 py-2.5">Size</th>
                      <th className="px-4 py-2.5">Architectural Role</th>
                      <th className="px-4 py-2.5 text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-[#30363d]/60 font-mono text-[#c9d1d9]">
                    {allFilesAcrossPlugins.map(({ plugin, file }) => (
                      <tr key={file.path} className="hover:bg-[#21262d] transition-colors">
                        <td className="px-4 py-2.5 whitespace-nowrap">
                          <span className="text-[#58a6ff] font-semibold text-[11px]">
                            {plugin.name}
                          </span>
                        </td>
                        <td className="px-4 py-2.5 text-[#e6edf3]">
                          {file.path}
                        </td>
                        <td className="px-4 py-2.5 text-[#8b949e] whitespace-nowrap">
                          {file.sizeFormatted}
                        </td>
                        <td className="px-4 py-2.5 text-[#8b949e] max-w-md font-sans">
                          {file.description}
                        </td>
                        <td className="px-4 py-2.5 text-right whitespace-nowrap">
                          <button
                            onClick={() => {
                              setSelectedPluginId(plugin.id);
                              setSelectedFilePath(file.path);
                              setActiveTab('inspector');
                            }}
                            className="px-2 py-1 text-[11px] text-[#58a6ff] hover:text-white bg-[#1f6feb]/10 hover:bg-[#1f6feb] rounded border border-[#388bfd]/30 transition-all cursor-pointer"
                          >
                            Inspect & Download
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Terminal Footer */}
      <footer className="border-t border-[#30363d] bg-[#161b22] py-4 px-4 text-xs text-[#8b949e]">
        <div className="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-4 font-mono">
          <div>
            <span>neonotion-plugins (c) 2024-2026</span>
            <span className="mx-2">·</span>
            <span>Developer: Pourya Omidian</span>
            <span className="mx-2">·</span>
            <span className="text-[#3fb950]">100% Free & Open Source</span>
          </div>

          <div className="flex items-center gap-4">
            <a href="https://neonotion.net" target="_blank" rel="noopener noreferrer" className="hover:text-[#58a6ff]">
              neonotion.net
            </a>
            <a href="mailto:itispourya@gmail.com" className="hover:text-[#58a6ff]">
              itispourya@gmail.com
            </a>
            <a href="/downloads/neonotion-plugins-bundle.zip" download className="text-[#3fb950] font-semibold">
              Download 5-in-1 Bundle (.zip)
            </a>
          </div>
        </div>
      </footer>
    </div>
  );
}
