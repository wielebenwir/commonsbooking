#!/bin/bash

if [ "$#" -ne 2 ]; then
    echo "Usage: $0 zip1.zip zip2.zip"
    exit 1
fi

ZIP1="$1"
ZIP2="$2"

TMPDIR1=$(mktemp -d)
TMPDIR2=$(mktemp -d)

FILES1=$(unzip -Z1 "$ZIP1" | grep '/assets/')
FILES2=$(unzip -Z1 "$ZIP2" | grep '/assets/')

ALL_FILES=$(echo -e "$FILES1\n$FILES2" | sort -u)

# Extract files
echo "$ALL_FILES" | while read -r FILE; do
    unzip -qq "$ZIP1" "$FILE" -d "$TMPDIR1" 2>/dev/null
    unzip -qq "$ZIP2" "$FILE" -d "$TMPDIR2" 2>/dev/null
done

echo "Files with differing sizes or content:"
echo "-------------------------------------"

echo "$ALL_FILES" | while read -r RELPATH; do
    F1="$TMPDIR1/$RELPATH"
    F2="$TMPDIR2/$RELPATH"

    if [ -f "$F1" ] && [ -f "$F2" ]; then
        SIZE1=$(stat -c%s "$F1")
        SIZE2=$(stat -c%s "$F2")

        if [ "$SIZE1" -ne "$SIZE2" ]; then
            echo
            echo "⚠️  $RELPATH"
            echo "Size in $ZIP1: $SIZE1 bytes"
            echo "Size in $ZIP2: $SIZE2 bytes"

            if file "$F1" | grep -q "text" && file "$F2" | grep -q "text"; then
                LINE1=$(wc -l < "$F1")
                LINE2=$(wc -l < "$F2")
                CHAR1=$(wc -c < "$F1")
                CHAR2=$(wc -c < "$F2")

                if { [ "$LINE1" -eq 1 ] && [ "$LINE2" -gt 1 ] && [ "$CHAR1" -gt 200 ]; } || \
                   { [ "$LINE2" -eq 1 ] && [ "$LINE1" -gt 1 ] && [ "$CHAR2" -gt 200 ]; }; then
                    echo "➤ One file is minified, the other is not:"
                    if [ "$LINE1" -eq 1 ]; then
                        echo "   - $RELPATH is minified in $ZIP1"
                        echo "   - $RELPATH is not minified in $ZIP2"
                    else
                        echo "   - $RELPATH is not minified in $ZIP1"
                        echo "   - $RELPATH is minified in $ZIP2"
                    fi
                else
                    echo
                    echo "diff -y:"
                    diff -y "$F1" "$F2"
                fi
            else
                echo "Binary file — skipping diff"
            fi
        fi
    fi
done

rm -rf "$TMPDIR1" "$TMPDIR2"
