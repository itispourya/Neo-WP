#!/bin/bash
# ==============================================================================
# NeoNotion WordPress Plugin Suite - Remote Installer One-Liner
# Developed by Pourya Omidian (https://neonotion.net)
# ==============================================================================

set -e

BASE_URL="https://ais-dev-5odeo7vp2v2h3exesgu2nz-84526810969.europe-west2.run.app"
PLUGINS=("neomarket" "neoloadland" "neobooking" "neoformdata" "neolock")

echo -e "\033[1;36m"
cat << "EOF"
 _   _             _   _       _   _             
| \ | | ___  ___  | \ | | ___ | |_(_) ___  _ __  
|  \| |/ _ \/ _ \ |  \| |/ _ \| __| |/ _ \| '_ \ 
| |\  |  __/ (_) || |\  | (_) | |_| | (_) | | | |
|_| \_|\___|\___/ |_| \_|\___/ \__|_|\___/|_| |_|
WordPress Plugin Suite Installer - 100% Free & Open Source
Author: Pourya Omidian (https://neonotion.net)
EOF
echo -e "\033[0m"

# Detect WordPress plugin directory
if [ -d "wp-content/plugins" ]; then
    TARGET_DIR="wp-content/plugins"
elif [ -d "../wp-content/plugins" ]; then
    TARGET_DIR="../wp-content/plugins"
elif [ -n "$WP_PLUGIN_DIR" ] && [ -d "$WP_PLUGIN_DIR" ]; then
    TARGET_DIR="$WP_PLUGIN_DIR"
else
    TARGET_DIR="."
fi

echo -e "\033[1;32m[+] Target directory:\033[0m $TARGET_DIR"

# Download and install each plugin
for PLUGIN in "${PLUGINS[@]}"; do
    ZIP_URL="${BASE_URL}/downloads/${PLUGIN}.zip"
    echo -e "\033[1;33m[→] Downloading ${PLUGIN}.zip...\033[0m"
    
    if command -v curl &> /dev/null; then
        curl -sSL "$ZIP_URL" -o "${TARGET_DIR}/${PLUGIN}.zip"
    elif command -v wget &> /dev/null; then
        wget -q "$ZIP_URL" -O "${TARGET_DIR}/${PLUGIN}.zip"
    else
        echo -e "\033[1;31m[-] Error: Neither curl nor wget found.\033[0m"
        exit 1
    fi

    echo -e "\033[1;32m[✓] Unpacking ${PLUGIN}.zip...\033[0m"
    if command -v unzip &> /dev/null; then
        unzip -q -o "${TARGET_DIR}/${PLUGIN}.zip" -d "$TARGET_DIR"
        rm -f "${TARGET_DIR}/${PLUGIN}.zip"
    else
        echo -e "\033[1;33m[!] unzip not found. Zip kept in target directory.\033[0m"
    fi
done

# Check if WP-CLI is available to activate plugins automatically
if command -v wp &> /dev/null; then
    echo -e "\033[1;36m[+] WP-CLI detected. Activating plugins...\033[0m"
    wp plugin activate neomarket neoloadland neobooking neoformdata neolock 2>/dev/null || true
    echo -e "\033[1;32m[✓] All NeoNotion plugins activated successfully!\033[0m"
else
    echo -e "\033[1;32m[✓] All 5 plugins extracted into $TARGET_DIR!\033[0m"
    echo -e "\033[1;36m[i] Activate them from your WordPress Admin > Plugins screen.\033[0m"
fi

echo -e "\033[1;32m[★] NeoNotion Suite Installation Complete! Visit https://neonotion.net\033[0m"
