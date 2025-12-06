#!/bin/bash
echo "Stopping WireChat services..."
pkill -f "reverb:start" && echo "✓ Reverb stopped"
pkill -f "queue:work" && echo "✓ Queue workers stopped"
pkill -f "watch-reverb" && echo "✓ Watch scripts stopped"
echo "All services stopped."
