#!/bin/bash
# Wrapper script to run the indexer with proper output buffering
# This ensures output is displayed in real-time

INDEXER_PATH="$1"
shift
COMMAND="$@"

# Use stdbuf to force line buffering for real-time output
if [[ "$INDEXER_PATH" == *.js ]]; then
    exec stdbuf -oL -eL node "$INDEXER_PATH" $COMMAND
else
    exec stdbuf -oL -eL "$INDEXER_PATH" $COMMAND
fi
