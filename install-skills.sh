#!/bin/bash
# Cola_CaO Blog — Skills Installer
# Run: bash install-skills.sh
# Add new skills by appending lines below

set -e

SKILLS=(
  mattpocock/skills@improve-codebase-architecture
  mattpocock/skills@setup-matt-pocock-skills
  mattpocock/skills@to-spec
  mattpocock/skills@implement
  mattpocock/skills@grill-with-docs
  mattpocock/skills@handoff
  mattpocock/skills@domain-modeling
)

for skill in "${SKILLS[@]}"; do
  echo "Installing $skill..."
  npx skills add "$skill" -g -y
done

echo ""
echo "All skills installed."
