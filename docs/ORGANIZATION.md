# Documentation Organization

This folder was organized on 2025-12-01 to improve maintainability and discoverability.

## What Changed

**Before**: 181 markdown files scattered in project root
**After**: 2 files in root, 115 files organized in docs/

See [DOCUMENTATION_CLEANUP_SUMMARY.md](DOCUMENTATION_CLEANUP_SUMMARY.md) for full details.

## Quick Navigation

- **Main Guide**: `/QUICK_START.md` (root)
- **Full Index**: [README.md](README.md)
- **Recent Work**: [features/recording/](features/recording/)
- **Architecture**: [architecture/](architecture/)
- **Deployment**: [deployment/](deployment/)

## Folder Structure

```
docs/
├── phases/         # Development milestones (Phase 1-10)
├── architecture/   # System analysis & design
├── deployment/     # Deployment guides
├── setup/          # Server/infrastructure setup
│   └── livekit/   # LiveKit video server
├── features/       # Feature documentation
│   ├── recording/ ⭐ Latest work
│   ├── attendance/
│   ├── meetings/
│   └── ...
├── fixes/          # Important bug fixes/refactors
└── reference/      # Quick references & indexes
```
