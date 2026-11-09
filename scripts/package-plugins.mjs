import fs from 'fs';
import path from 'path';
import JSZip from 'jszip';

const PLUGINS = [
  { id: 'NeoMarket', zipName: 'neomarket.zip' },
  { id: 'NeoLoadLand', zipName: 'neoloadland.zip' },
  { id: 'NeoBooking', zipName: 'neobooking.zip' },
  { id: 'NeoFormData', zipName: 'neoformdata.zip' },
  { id: 'NeoLock', zipName: 'neolock.zip' }
];

const ROOT_DIR = process.cwd();
const OUT_DIR = path.join(ROOT_DIR, 'public', 'downloads');

if (!fs.existsSync(OUT_DIR)) {
  fs.mkdirSync(OUT_DIR, { recursive: true });
}

function addDirectoryToZip(zip, folderPath, rootRelativePrefix) {
  const items = fs.readdirSync(folderPath);
  for (const item of items) {
    const itemPath = path.join(folderPath, item);
    const stat = fs.statSync(itemPath);
    const zipPath = path.join(rootRelativePrefix, item).replace(/\\/g, '/');

    if (stat.isDirectory()) {
      addDirectoryToZip(zip, itemPath, zipPath);
    } else {
      const content = fs.readFileSync(itemPath);
      zip.file(zipPath, content);
    }
  }
}

async function packagePlugins() {
  console.log('Packaging WordPress plugins into public/downloads...');
  const bundleZip = new JSZip();

  for (const plugin of PLUGINS) {
    const pluginDir = path.join(ROOT_DIR, plugin.id);
    if (!fs.existsSync(pluginDir)) {
      console.warn(`Plugin directory ${pluginDir} not found, skipping.`);
      continue;
    }

    const zip = new JSZip();
    addDirectoryToZip(zip, pluginDir, plugin.id);
    addDirectoryToZip(bundleZip, pluginDir, plugin.id);

    const zipBuffer = await zip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' });
    const targetPath = path.join(OUT_DIR, plugin.zipName);
    fs.writeFileSync(targetPath, zipBuffer);
    console.log(`Created ${targetPath} (${(zipBuffer.length / 1024).toFixed(1)} KB)`);
  }

  const bundleBuffer = await bundleZip.generateAsync({ type: 'nodebuffer', compression: 'DEFLATE' });
  const bundlePath = path.join(OUT_DIR, 'neonotion-plugins-bundle.zip');
  fs.writeFileSync(bundlePath, bundleBuffer);
  console.log(`Created complete bundle ${bundlePath} (${(bundleBuffer.length / 1024).toFixed(1)} KB)`);
}

packagePlugins().catch(err => {
  console.error('Error packaging plugins:', err);
  process.exit(1);
});
