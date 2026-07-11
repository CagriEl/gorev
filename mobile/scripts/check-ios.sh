#!/usr/bin/env bash
# Bel-Sistem mobil — iOS simülatör / Xcode uyumluluk kontrolü
set -euo pipefail

echo "=== Xcode SDK ==="
xcodebuild -showsdks 2>/dev/null | grep -i "Simulator - iOS" || true

echo ""
echo "=== Yüklü simülatör runtime'ları ==="
xcrun simctl list runtimes 2>/dev/null | grep -i ios || echo "(yok)"

echo ""
echo "=== Kullanılabilir simülatörler ==="
xcrun simctl list devices available 2>/dev/null | grep -v "^--" | grep -v "^==" | sed '/^$/d' || true

SDK_VER=$(xcodebuild -showsdks 2>/dev/null | grep "Simulator - iOS" | tail -1 | grep -oE '[0-9]+\.[0-9]+' | head -1 || echo "?")
RUNTIME_COUNT=$(xcrun simctl list runtimes 2>/dev/null | grep -c "iOS ${SDK_VER}" || true)

echo ""
if [[ "$RUNTIME_COUNT" -eq 0 ]] && [[ "$SDK_VER" != "?" ]]; then
  echo "❌ Sorun: Xcode iOS ${SDK_VER} ile derliyor ama iOS ${SDK_VER} Simulator runtime yüklü değil."
  echo ""
  echo "Çözüm:"
  echo "  1. Xcode → Settings (⌘,) → Platforms"
  echo "  2. «iOS ${SDK_VER} Simulator» indirin"
  echo "  3. veya terminal: xcodebuild -downloadPlatform iOS"
  echo "  4. Sonra: cd mobile && npx expo run:ios"
  echo ""
  echo "Geçici (native derleme yok): cd mobile && npx expo start  →  i tuşu"
  exit 1
else
  echo "✅ Simülatör runtime uyumlu görünüyor. Derleme: npx expo run:ios"
fi
