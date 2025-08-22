#!/bin/bash
cd /c/temp/sensei-lesson-map-editor

# Pull
git pull origin main

# Add all
git add .

# Commit met datum
git commit -m "Automatische update $(date)"

# Push
git push origin main
